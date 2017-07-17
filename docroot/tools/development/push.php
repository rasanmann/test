<?php
use Drupal\yqb_reminders\general\Reminders;
use Drupal\user\Entity\User;

// LOAD DRUPAL

define('DRUPAL_DIR', __DIR__ .'/../..');

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

require_once DRUPAL_DIR . '/core/includes/database.inc';
require_once DRUPAL_DIR . '/core/includes/schema.inc';
$autoloader = require_once DRUPAL_DIR . '/autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->prepareLegacyRequest($request);


// Get data
$data = json_decode("{
    \"eventKind\": \"UPDATED\",
    \"flight\": {
        \"@type\": \".DepartureFlight\",
        \"flightUniqueId\": \"DEP_114366\",
        \"airline\": {
            \"iataCode\": \"QK\",
            \"icaoCode\": \"JZA\",
            \"name\": \"Air Canada Express (Jazz)\"
        },
        \"flightNumber\": \"8711\",
        \"gate\": \"22\",
        \"isInternal\": false,
        \"codeShares\": null,
        \"kind\": \"DOMESTIC\",
        \"destination\": [
            {
                \"iataCode\": \"YUL\",
                \"icaoCode\": null,
                \"airportCityName\": \"Montr\u00e9al \",
                \"airportName\": \"Montreal-Pierre Elliott Trudeau International Airport\"
            }
        ],
        \"departureStatus\": \"Delayed\",
        \"etd\": \"2016-09-24T12:30:00.000\",
        \"std\": \"2016-09-24T11:30:00.000\",
        \"atd\": \"2016-09-24T11:30:00.000\",
        \"checkInCounters\": [
            {
                \"startTime\": \"2016-09-24T03:30:00\",
                \"endTime\": \"2016-09-24T20:00:00\",
                \"name\": \"100\"
            },
            {
                \"startTime\": \"2016-09-24T03:30:00\",
                \"endTime\": \"2016-09-24T20:00:00\",
                \"name\": \"101\"
            },
            {
                \"startTime\": \"2016-09-24T03:30:00\",
                \"endTime\": \"2016-09-24T20:00:00\",
                \"name\": \"102\"
            },
            {
                \"startTime\": \"2016-09-24T03:30:00\",
                \"endTime\": \"2016-09-24T05:00:00\",
                \"name\": \"103\"
            },
            {
                \"startTime\": \"2016-09-24T03:30:00\",
                \"endTime\": \"2016-09-24T05:00:00\",
                \"name\": \"104\"
            }
        ]
    },
    \"isMajor\": true,
    \"eventType\": \"FLIGHT\"
}");

// Parse only FLIGHT type data
if(isset($data->eventType) && $data->eventType === 'FLIGHT' && $data->isMajor) {
  // Flight number
  $flightNumber = $data->flight->flightNumber;

  // Log data
  $jsonEncodedData = json_encode($data, JSON_PRETTY_PRINT);

  // Prepare variables
  $realICAO = $flightAirlineICAO = $data->flight->airline->icaoCode;

  // Exception for Air Canada operated flights
  if ($flightAirlineICAO === 'JZA' || $flightAirlineICAO === 'QZ') $flightAirlineICAO = 'ACA';

  $flightType = ($data->flight->{'@type'} === '.DepartureFlight') ? 'departure' : 'arrival';
  $scheduleDate = ($data->flight->{'@type'} === '.DepartureFlight') ? $data->flight->std : $data->flight->sta;
  $flightStatus = ($data->flight->{'@type'} === '.DepartureFlight') ? $data->flight->departureStatus : $data->flight->arrivalStatus;
  $flightDate = date('Y-m-d', strtotime($scheduleDate));

  $flightData = [
    'jsonEncodedData' => $jsonEncodedData,
    'flightNumber' => $flightNumber,
    'flightType' => $flightType,
    'flightDate' => $flightDate,
    'flightStatus' => $flightStatus,
    'scheduleDate' => $scheduleDate
  ];


  // Fetch user_flight with flight number / flight date combination
  $query = \Drupal::entityQuery('node')
                  ->condition('type', 'user_flight')
                  ->condition('field_flight_number', $flightNumber)
                  ->condition('field_flight_airline', $realICAO)
                  ->condition('field_flight_type', $flightType)
                  ->condition('field_flight_date', $flightDate)
                  ->condition('field_completed', false);

  $results = $query->execute();
  
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $nodes = $node_storage->loadMultiple($results);

  \Drupal::logger('yqb_reminders')->notice(sprintf('Found %d associated flights to notify via push', sizeof($nodes)));

  updateUserFlights($nodes, $flightData, $data);
}

function updateUserFlights($nodes, $flightData, $data){
  foreach($nodes as $userFlight){
      // Completed already, skip
      if($userFlight->field_completed->value) {
        continue;
      
      // Change status to completed
      }elseif($flightData['flightStatus'] == 'Arrived' || $flightData['flightStatus'] == 'Departed'){
        $userFlight->field_completed->value = true;
      }
      
      // Fetch status
      $query = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', 'flight_statuses')
            ->condition('name', $flightData['flightStatus'])
            ->pager(1);
      
      $status = $query->execute();
    
      $status = (!empty($status)) ? current($status) : 0;
      
      // Update field data
      $estimatedDate = ($data->flight->{'@type'} === '.DepartureFlight') ? $data->flight->etd : $data->flight->eta;
      $userFlight->field_status = $status;
      $userFlight->field_gate = $data->flight->gate;
      $userFlight->field_flight_date = date('Y-m-d', strtotime($estimatedDate));
      $userFlight->field_flight_time = date('H:i', strtotime($estimatedDate));
      
      // Save user flight
      $userFlight->save();
    
      // Send push notifications
      $user = $userFlight->field_user->first()->get('entity')->getTarget()->getValue();
    
      $Reminders = new Reminders();
      $Reminders->sendPushNotifications($user, $userFlight);
    }
}