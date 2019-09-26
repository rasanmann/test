<?php
/**
 * @file
 * Contains \Drupal\yqb_flight_planner\Controller\FlightPlannerController.
 */

namespace Drupal\yqb_reminders\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\yqb_reminders\general\Reminders;
use Services_Twilio;
use Services_Twilio_RestException;


/**
 * Provides route responses for the Reminders module.
 */
class RemindersWebhookController extends ControllerBase {

  /**
   * Receives push from ArcBees
   * Notify users with text message
   * Update user_flight info
   */
  public function index() {
    // Get data
    $data = json_decode(file_get_contents("php://input"));

    // Parse only FLIGHT type data
    if(isset($data->eventType) && $data->eventType === 'FLIGHT' && $data->isMajor) {
      // Flight number
      $flightNumber = $data->flight->flightNumber;

      // Log data
      $logDate = date('Y-m-d_H-i-s');
      $jsonEncodedData = json_encode($data, JSON_PRETTY_PRINT);
      $this->logAndClean($jsonEncodedData, $flightNumber . '_' . $logDate, '', 200);

      // Prepare variables
      $realICAO = $flightAirlineICAO = $data->flight->airline->icaoCode;

      // Exception for Air Canada operated flights
      if ($flightAirlineICAO === 'JZA' || $flightAirlineICAO === 'QZ') $flightAirlineICAO = 'ACA';

      $flightType = ($data->flight->{'@type'} === '.DepartureFlight') ? 'departure' : 'arrival';
      $scheduleDate = ($data->flight->{'@type'} === '.DepartureFlight') ? $data->flight->std : $data->flight->sta;
      $flightStatus = ($data->flight->{'@type'} === '.DepartureFlight') ? $data->flight->departureStatus : $data->flight->arrivalStatus;
      $flightDate = date('Y-m-d', strtotime($scheduleDate));

      // Radio silence. Abort.
      $reminders = $this->config('yqb_reminders.settings');
      $blockedFlights = explode(',',$reminders->get('yqb_reminders.blocked_flights'));
      if(in_array($flightNumber, $blockedFlights)) exit();

      $flightData = [
        'jsonEncodedData' => $jsonEncodedData,
        'flightNumber' => $flightNumber,
        'flightType' => $flightType,
        'flightDate' => $flightDate,
        'flightStatus' => $flightStatus,
        'scheduleDate' => $scheduleDate
      ];

      // Log data received
      \Drupal::logger('yqb_reminders')->notice(sprintf('Webhook received for flight « %s - %d - %s » on %s', $flightType, $flightNumber, $data->flight->airline->name, $flightDate));

      // Fetch reminders with flight number / flight date combination
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'reminder')
        ->condition('field_flight', $flightNumber)
        ->condition('field_flight_airline', $flightAirlineICAO)
        ->condition('field_flight_type', $flightType)
        ->condition('field_flight_date', $flightDate)
        ->condition('field_completed', false);

      $results = $query->execute();

      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($results);

      \Drupal::logger('yqb_reminders')->notice(sprintf('Found %d reminders to notify via SMS', sizeof($nodes)));

      $this->sendSMSAlerts($nodes, $flightData, $data, $logDate);


      // Fetch user_flight with flight number / flight date combination
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'user_flight')
        ->condition('field_flight_number', $flightNumber)
        ->condition('field_flight_airline', $realICAO)
        ->condition('field_flight_type', $flightType)
        ->condition('field_flight_date', $flightDate)
        ->condition('field_archived', false);

      $results = $query->execute();

      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($results);

      \Drupal::logger('yqb_reminders')->notice(sprintf('Found %d associated flights to notify via push (%s)', sizeof($nodes), implode('-', $results)));

      $this->updateUserFlights($nodes, $flightData, $data, $logDate);

      exit('OK');
    }

    exit('IGNORED');
  }

  /**
   * Receives Twilio incoming SMS and deals with responses and unsubscriptions
   */
  public function response(){
    if (!is_dir('public://webhooks')) {
      mkdir('public://webhooks');
    }

    $message = $this->t("Bonjour. Pour arrêter de recevoir les mises à jour, répondez « Fin ».");

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
      $data = $_POST;

      $this->logAndClean(json_encode($data, JSON_PRETTY_PRINT), date('Y-m-d_H-i-s'), 'incoming', 50);

      // Remove all alerts this user is subscribed to
      if(mb_strtolower($data['Body'], 'UTF-8') == 'fin' || mb_strtolower($data['Body'], 'UTF-8') == 'done'){
        $phone_number = $data['From'];

        // Delete all reminders with current phone number
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'reminder')
          ->condition('field_phone_number', $phone_number, '=');

        $result = $query->execute();

        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $nodes = $node_storage->loadMultiple($result);

        if(!empty($nodes)) {
          $node_storage->delete($nodes);
        }

        // Warn user of current reminder status
        $message = $this->t("Vous cesserez de recevoir des notifications pour toutes les mises à jour des vols auxquels vous êtes inscrit.");
      }
    }

    // Send reply
    if($message) {
      header("content-type: text/xml");
      echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

      echo '<Response>
        <Message>'.$message.'</Message>
      </Response>';
    }

    exit;
  }

  /**
   *
   * @param \Drupal\node\Entity\ $nodes
   * @param $flightData
   * @param $data
   * @param $logDate
   */
  private function sendSMSAlerts($nodes, $flightData, $data, $logDate){
    // Get all numbers to call
    $phoneNumbers = [];
    foreach($nodes as $reminder){
      // Make sure it's not the same data
      if($reminder->field_last_update->value !== $flightData['jsonEncodedData']) {
        // Completed already, skip
        if($reminder->field_completed->value) {
          continue;

        // Change status to completed
        }elseif($flightData['flightStatus'] == 'Arrived' || $flightData['flightStatus'] == 'Departed'){
          $reminder->field_completed->value = true;

        // Ignore arrival flight that status and eta stays the same
        }elseif($data->flight->{'@type'} === '.ArrivalFlight'){
          $lastUpdate = json_decode($reminder->field_last_update->value);

          $sameStatus = ($lastUpdate->flight->arrivalStatus === $flightData['flightStatus']);
          $sameETA = ($lastUpdate->flight->eta === $data->flight->eta);

          if($sameStatus && $sameETA) continue;
        }

        // Add to send list
        $phoneNumbers[] = $reminder->field_phone_number->value;

        // Save last log
        $reminder->field_last_update->value = $flightData['jsonEncodedData'];
        $reminder->save();
      }
    }

    // Send sms to all affected phone numbers
    if(!empty($phoneNumbers)){
      // Prepare message
      if($data->flight->{'@type'} === '.DepartureFlight'){
        switch($flightData['flightStatus']){
          case 'Cancelled':
            $message = $this->t("ATTENTION. Le vol @flight à destination de @city le @departure_date à @departure_time est annulé. Veuillez communiquer avec votre transporteur pour plus de détails.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->destination[0]->airportCityName),
              '@departure_date' => date($this->t('d/m'), strtotime($flightData['scheduleDate'])),
              '@departure_time' => date('H:i', strtotime($flightData['scheduleDate']))
            ]);
          break;
          case 'Delayed':
            $estimatedDate = $data->flight->etd;

            $message = $this->t("ATTENTION. Le vol @flight à destination de @city le @departure_date à @departure_time est décalé. L'heure estimée de départ est @estimated_time à la porte @gate.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->destination[0]->airportCityName),
              '@departure_date' => date($this->t('d/m'), strtotime($flightData['scheduleDate'])),
              '@departure_time' => date('H:i', strtotime($flightData['scheduleDate'])),
              '@estimated_time' => date('H:i', strtotime($estimatedDate)),
              '@gate' => $data->flight->gate
            ]);
          break;
          case 'Departed':
            $message = $this->t("Le vol @flight à destination de @city a décollé.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->destination[0]->airportCityName),
              '@departure_date' => date($this->t('d/m'), strtotime($flightData['scheduleDate'])),
              '@departure_time' => date('H:i', strtotime($flightData['scheduleDate'])),
            ]);
          break;
          case 'Early':
            $estimatedDate = $data->flight->etd;

            $message = $this->t("ATTENTION. Le vol @flight à destination de @city le @departure_date à @departure_time est devancé. L'heure estimée de départ est @estimated_time à la porte @gate.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->destination[0]->airportCityName),
              '@departure_date' => date($this->t('d/m'), strtotime($flightData['scheduleDate'])),
              '@departure_time' => date('H:i', strtotime($flightData['scheduleDate'])),
              '@estimated_time' => date('H:i', strtotime($estimatedDate)),
              '@gate' => $data->flight->gate
            ]);
          break;
          default :
            $estimatedDate = $data->flight->etd;

            $message = $this->t("Le vol @flight à destination de @city est à l'heure. Le départ aura lieu à la porte @gate à @estimated_time.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->destination[0]->airportCityName),
              '@estimated_time' => date('H:i', strtotime($estimatedDate)),
              '@gate' => $data->flight->gate
            ]);
          break;
        }
      }else{
        switch($flightData['flightStatus']){
          case 'Arrived':
            $message = $this->t("Le vol @flight en provenance de @city est arrivé.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->origin[0]->airportCityName),
              '@departure_date' => date($this->t('d/m'), strtotime($flightData['scheduleDate'])),
              '@departure_time' => date('H:i', strtotime($flightData['scheduleDate'])),
            ]);
          break;
          case 'Cancelled':
            $message = $this->t("ATTENTION. Le vol @flight en provenance de @city le @departure_date à @departure_time est annulé. Veuillez communiquer avec votre transporteur pour plus de détails.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->origin[0]->airportCityName),
              '@departure_date' => date($this->t('d/m'), strtotime($flightData['scheduleDate'])),
              '@departure_time' => date('H:i', strtotime($flightData['scheduleDate']))
            ]);
          break;
          case 'Delayed':
            $estimatedDate = $data->flight->eta;

            $message = $this->t("ATTENTION. Le vol @flight en provenance de @city le @departure_date à @departure_time est décalé. L'heure estimée d'arrivée est @estimated_time.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->origin[0]->airportCityName),
              '@departure_date' => date($this->t('d/m'), strtotime($flightData['scheduleDate'])),
              '@departure_time' => date('H:i', strtotime($flightData['scheduleDate'])),
              '@estimated_time' => date('H:i', strtotime($estimatedDate))
            ]);
          break;
          case 'Early':
            $estimatedDate = $data->flight->eta;

            $message = $this->t("ATTENTION. Le vol @flight en provenance de @city le @departure_date à @departure_time est devancé. L'heure estimée d'arrivée est @estimated_time.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->origin[0]->airportCityName),
              '@departure_date' => date($this->t('d/m'), strtotime($flightData['scheduleDate'])),
              '@departure_time' => date('H:i', strtotime($flightData['scheduleDate'])),
              '@estimated_time' => date('H:i', strtotime($estimatedDate))
            ]);
          break;
          default :
            $estimatedDate = $data->flight->eta;

            $message = $this->t("Le vol @flight en provenance de @city est à l'heure. L'arrivée aura lieu à @estimated_time.", [
              '@flight' => $flightData['flightNumber'],
              '@city' => trim($data->flight->origin[0]->airportCityName),
              '@estimated_time' => date('H:i', strtotime($estimatedDate))
            ]);
          break;
        }
      }

      // Clean log files + log new
      $data->message = $message;
      $this->logAndClean(json_encode($data, JSON_PRETTY_PRINT), $logDate, 'outgoing', 50);

      // Send message via Twilio
      $config = $this->config('twilio.settings');
      $client = new Services_Twilio($config->get('twilio.sid'), $config->get('twilio.token'));

      try {
        foreach($phoneNumbers as $phoneNumber) {
          $sms = $client->account->messages->create([
            "From" => $config->get('twilio.number'),
            "To" => $phoneNumber,
            "Body" => $message,
          ]);
        }
      } catch (Services_Twilio_RestException $e) {
        file_put_contents(sprintf('public://webhooks/outgoing/error-%s.log', $logDate), $e->getMessage());
      }
    }
  }

  private function updateUserFlights($nodes, $flightData, $data, $logDate){
    // Init Reminders class
    $Reminders = new Reminders();

    foreach($nodes as $userFlight){
      // Keep reference
      $userFlightStatus = $userFlight->field_status->first()->get('entity')->getTarget()->getValue()->id();
      $userFlightGate =  $userFlight->field_gate->value;

      // Completed already, skip
      if(!empty($userFlight->field_completed_time->value)) {
        continue;

      // Change status to completed
      }elseif($flightData['flightStatus'] == 'Arrived' || $flightData['flightStatus'] == 'Departed' || $flightData['flightStatus'] == 'Cancelled'){
        // Set completed time
        $userFlight->field_completed_time->value = time();
      }

      // Fetch status
      $query = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', 'flight_statuses')
            ->condition('name', $flightData['flightStatus'])
            ->pager(1);

      $status = $query->execute();

      $status = (!empty($status)) ? current($status) : 0;

      // Gate has changed, but not the status, force status to be Gate changed (44)
      if($userFlightStatus == $status && $userFlightGate != $data->flight->gate){
        $status = 44;
      }

      // Update field data
      $estimatedDate = ($data->flight->{'@type'} === '.DepartureFlight') ? $data->flight->etd : $data->flight->eta;
      $userFlight->field_status = $status;
      $userFlight->field_gate = $data->flight->gate;
      if($data->flight->{'@type'} === '.ArrivalFlight') $userFlight->field_carousel_name = $data->flight->carousel->name;
      $userFlight->field_flight_date = date('Y-m-d', strtotime($estimatedDate));
      $userFlight->field_flight_time = date('H:i', strtotime($estimatedDate));

      // Save user flight
      $userFlight->save();

      // Fetch user for his language
      $user = $userFlight->field_user->first()->get('entity')->getTarget()->getValue();
      $language = (!empty($user->field_language->value)) ? $user->field_language->value : 'fr';
      $contentAvailable = false;
      $msg = '';

      switch(true){
        // Status has changed
        case ($userFlightStatus != $status) :
          $contentAvailable = true;
          $msg = $Reminders->getStatusMessage($status, $userFlight->field_state->value, $userFlight, $language);
        break;
      }

      if($contentAvailable){
        // Log data received
        \Drupal::logger('yqb_reminders')->notice(sprintf('Sending push notification to user #%s for flight « %d - %s » on %s', $user->id(), $userFlight->field_flight_number->value,
          $data->flight->airline->name, $userFlight->field_flight_date->value));

        // Send push
        $pushData = [
          'user_flight_id' => $userFlight->id(),
          'flight_number' => $userFlight->field_flight_number->value,
          'time' => date('H:i', strtotime($userFlight->field_flight_time->value)),
          'gate' => $userFlight->field_gate->value,
          'state' => $userFlight->field_state->value,
          'status' => $userFlight->field_status->first()->get('entity')->getTarget()->getValue()->id(),
          'redirection' => 'update'
        ];

        $Reminders->sendPushNotifications($user, $msg, $pushData);
      }
    }
  }

  /**
   * Log data into files and trigger cleaning process
   * @param $data
   * @param $logDate
   * @param $folder
   * @param int $limit
   */
  private function logAndClean($data, $logDate, $folder, $limit = 50){
    // Log outgoing events and add message to log
    if (!is_dir('public://webhooks/'.$folder)) {
      mkdir('public://webhooks/'.$folder);
    }

    if(!empty($folder)) $folder .= '/';

    // Get all log files and order by most recent
    $realPublicPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $files = glob($realPublicPath . '/webhooks/'.$folder.'*.log');

    $this->cleanLogFiles($files, $limit);

    if(file_exists(sprintf('public://webhooks/'.$folder.'%s.log', $logDate))){
      $logDate .= "_duplicate";
    }

    if (!file_put_contents(sprintf('public://webhooks/'.$folder.'%s.log', $logDate), $data)) {
      echo "Couldn't log file" . PHP_EOL;
    }
  }

  /**
   * Clear oldest files
   * @param $files
   * @param int $limit
   */
  private function cleanLogFiles($files, $limit = 10){
    usort($files, function ($a, $b) {
      return filemtime($a) < filemtime($b);
    });

    // Clean up, keep most recent files
    if (count($files) > $limit) {
        $deletes = array_slice($files, $limit - 1);
        foreach ($deletes as $delete) {
            @unlink($delete);
        }
    }
  }
}
