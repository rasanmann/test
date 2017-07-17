<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\data_exchange_layer\Connector\DataExchangeLayerConnector;
use Drupal\node\Entity\Node;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "flight_rest_resource",
 *   label = @Translation("Flight rest resource"),
 *   serialization_class = "Drupal\yqb_api\Normalizer\JsonDenormalizer",
 *   uri_paths = {
 *     "canonical" = "/v1/flights",
 *     "https://www.drupal.org/link-relations/create" = "/v1/flights"
 *   }
 * )
 */
class FlightRestResource extends YQBResourceBase  {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $current_user);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('yqb_api'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of flights registered on the website
   */
  public function get() {
    $build = array(
      '#cache' => array(
        'max-age' => 60*10, // 10 minutes
      ),
    );
    
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      return $this->getUnauthorizedResponse($build);
    }

    /**
     * Fetch arrivals and departures
     */
    $query = \Drupal::entityQuery('node')
      ->condition('type', array('arrival', 'departure'), 'in');
    
    $result = $query->execute();
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($result);

    /**
     * Remash data for pretty response
     */
    $flights = array();
    foreach($nodes as $node){
      $type = $node->bundle();
      
      /**
       * Fetch airline
       */
      $airlineNode = $node->field_airline->first()->get('entity')->getTarget()->getValue();
      
      /**
       * Fetch Destination Airport
       */
      if($type == 'departure'){
        $destinationNode = $node->field_destination_airport->first()->get('entity')->getTarget()->getValue();
        
        $destination = [
          "id" => $destinationNode->id(),
          'iata' => $destinationNode->field_iata->value,
          'city' => $destinationNode->field_city->value,
          'name' => $destinationNode->title->value,
          'image' => $destinationNode->field_image->value
        ];
        
        $origin = $this->yqb;
      }else{
        $destination = $this->yqb;
        
        $originNode = $node->field_origin_airport->first()->get('entity')->getTarget()->getValue();
        
        $origin = [
          "id" => $originNode->id(),
          'iata' => $originNode->field_iata->value,
          'city' => $originNode->field_city->value,
          'name' => $originNode->title->value,
          'image' => $originNode->field_image->value
        ];
      }
      
      $flights[] = [
        'id' => $node->nid->value,
        'type' => $type,
        'number' => $node->field_flight_number->value,
        'date' => ($type == 'arrival') ? date('Y-m-d H:i:s', strtotime($node->field_sta->value)) : date('Y-m-d H:i:s', strtotime($node->field_std->value)),
        "airline" => [
          "id" => $airlineNode->id(),
          "icao" => ($airlineNode->field_icao->value === 'JZA' || $airlineNode->field_icao->value === 'QZ') ? 'ACA' : $airlineNode->field_icao->value,
          "realIcao" => $airlineNode->field_icao->value,
          "name" => $airlineNode->title->value,
        ],
        'destination' => $destination,
        'origin' => $origin
      ];
    }
    
    return (new ResourceResponse($flights))->addCacheableDependency($build);
  }
  
  /**
   * Responds to POST requests.
   *
   * Search for flights on Data Exchange Layer 
   *
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($data){
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content') || !$this->currentUser->id()) {
      return $this->getUnauthorizedResponse();
    }
    
    if(!isset($data['state'])){
      return new ResourceResponse(['error' => $this->t("Flight state is required", [], ['langcode' => $this->language])], 400);
    }

    $userFlights = $this->searchDEL($data);
    
    if(sizeof($userFlights) == 1){
      /**
       * Only 1 result, save user_flight
       */
      $returnFlight = $this->fetchFlightRelations(current($userFlights));
      $returnFlight['state'] = $data['state'];
      
      if($this->validateUserFlight($returnFlight, $this->currentUser)) {
        if ($userFlight = $this->createUserFlight($returnFlight, $this->currentUser)) {
          $returnFlight['id'] = $userFlight['id'];
          
          // Clean
          if(isset($returnFlight['flight_id'])) unset($returnFlight['flight_id']);

          return new ResourceResponse([$returnFlight], 201);
        } else {
          return new ResourceResponse(['error' => $this->t("Error saving flight", [], ['langcode' => $this->language])], 400);
        }
      }else{
        return new ResourceResponse(['error' => $this->t("This flight is already registered for this user.", [], ['langcode' => $this->language])], 409);
      }
    }elseif(!empty($userFlights)){
      /**
       * If multiple flights correspond to user criterias, show them
       */
      foreach($userFlights as &$userFlight){
        $userFlight = $this->fetchFlightRelations($userFlight);
        $userFlight['state'] = $data['state'];
        
        // Cache Identifier for future POST
        $this->cacheAndClean($userFlight, $userFlight['flight_id'], 'flights', 100);
      }
      
      return new ResourceResponse($userFlights);
    }else{
      return new ResourceResponse([]);
    }
  }
  
  public function searchDEL($data){
    /**
     * Prepare DataExchangeLayer and fetch according to query
     */
    $layer = new DataExchangeLayerConnector();
    
    // Make sure there is at least a type and a date
    if(empty($data) || !isset($data['type']) || !isset($data['date'])){
      return new ResourceResponse(['error' => $this->t("Bad request", [], ['langcode' => $this->language])], 400);
    }
    
    $from = $to = $data['date'];
    
    // Increased time limit
    set_time_limit(360);
    
    switch($data['type']) {
      case 'departure':
        $flights = $layer->getDepartures($from, $to);
        break;
      
      default:
        $flights = $layer->getArrivals($from, $to);
        break;
    }

    /**
     * Remash results for pretty response
     */
    $userFlights = [];
    foreach ($flights as $flight) {
      if($flight->{'@type'} == '.DepartureFlight'){
        $origin = $this->yqb;
        
        $destination = [
          "iata" => $flight->destination[0]->iataCode,
          "city" => $flight->destination[0]->airportCityName,
          "name" => $flight->destination[0]->airportName
        ];
        
        $status = $flight->departureStatus;
        
        $time = date('H:i', strtotime($flight->etd));
        
        $carousel = null;
      }else{
        $origin = [
          "iata" => $flight->origin[0]->iataCode,
          "city" => $flight->origin[0]->airportCityName,
          "name" => $flight->origin[0]->airportName
        ];
        
        $destination = $this->yqb;
        
        $status = $flight->arrivalStatus;
        
        $time = date('H:i', strtotime($flight->eta));
        
        $carousel = $flight->carousel->name;
      }
      
      $item = [
        "flight_id" => $flight->flightUniqueId,
        "type" => $data['type'],
        "number" => $flight->flightNumber,
        "date" => $data['date'],
        "time" => $time,
        "flightDateTime" => strtotime($data['date'].' '.$time.':00')*1000,
        "gate" => $flight->gate,
        "status" => [
          "title" => $status,
        ],
        "airline" => [
          "icao" => ($flight->airline->icaoCode === 'JZA' || $flight->airline->icaoCode === 'QZ') ? 'ACA' : $flight->airline->icaoCode,
          "realIcao" => $flight->airline->icaoCode,
          "name" => $flight->airline->name,
        ],
        "destination" => $destination,
        "origin" => $origin,
        "carousel" => $carousel
      ];

      /**
       * Flight matches request
       */
      if(isset($data['number']) && $item['number'] == $data['number']){
        $userFlights[] = $item;
      }
    }
    
    return $userFlights;
  }

  /**
   * Fetch relationships data and add it to results
   * @param $data
   * @return ResourceResponse
   */
  public function fetchFlightRelations($data){
    /**
     * Fetch Status
     */
    $query = \Drupal::entityQuery('taxonomy_term')
          ->condition('vid', 'flight_statuses')
          ->condition('name', $data['status'])
          ->pager(1);
    
    $status = $query->execute();
    
    if(empty($status)) return new ResourceResponse(['error' => $this->t("Status not found", [], ['langcode' => $this->language])], 404);
    
    $status = current($status);
    
    $taxonomy_term = Term::load($status);
    $statusNode = \Drupal::service('entity.repository')->getTranslationFromContext($taxonomy_term, $this->language);
    
    /**
     * Fetch Airline
     */
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'airline')
      ->condition('field_icao', $data['airline']['realIcao'])
      ->pager(1);
    
    $airline = $query->execute();
    
    if(empty($airline)) return new ResourceResponse(['error' => $this->t("Airline not found", [], ['langcode' => $this->language])], 404);
    
    $airline = current($airline);

    /**
     * Fetch Destination Airport
     */
    if($data['type'] == 'departure') {
      $query = \Drupal::entityQuery('node')
                      ->condition('type', 'airport')
                      ->condition('field_iata', $data['destination']['iata'])
                      ->pager(1);

      $destination = $query->execute();

      if (empty($destination)) return new ResourceResponse(['error' => $this->t("Destination airport not found", [], ['langcode' => $this->language])], 404);
      
      $destination = current($destination);
    }else{
      $destination = 0;
    }

    /**
     * Fetch Origin Airport
     */
    if($data['type'] == 'arrival') {
      $query = \Drupal::entityQuery('node')
                      ->condition('type', 'airport')
                      ->condition('field_iata', $data['origin']['iata'])
                      ->pager(1);

      $origin = $query->execute();

      if (empty($origin)) return new ResourceResponse(['error' => $this->t("Origin airport not found", [], ['langcode' => $this->language])], 404);
      
      $origin = current($origin);
    }else{
      $origin = 0;
    }
    
    /**
     * Fetch Image
     */
    $query = \Drupal::entityQuery('node')
             ->condition('type', 'destination')
             ->condition('field_airport', ($data['type'] == 'arrival') ? $origin : $destination)
             ->pager(1);
    
    $city = $query->execute();
          
    if (!empty($city)){
      $city = Node::load(current($city));
      if(!empty($city->field_header_image->entity->uri->value)){
        $image = file_create_url($city->field_header_image->entity->uri->value);
      }
    }
    
    $data['status']['id'] = $status;
    $data['status']['title'] = $statusNode->name->value;
    $data['status']['message'] = $this->getListStatusMessage($statusNode->id(), $data, $this->language, true);
    $data['airline']['id'] = $airline;
    if(!empty($data['destination'])){} $data['destination']['id'] = $destination;
    if(!empty($data['origin'])) $data['origin']['id'] = $origin;
    
    if($data['type'] == 'arrival'){
      $data['origin']['image'] = (isset($image)) ? $image : file_create_url($this->defaultDestinationImage);
    }else{
      $data['destination']['image'] = (isset($image)) ? $image : file_create_url($this->defaultDestinationImage);
    }
    
    return $data;
  }
}

