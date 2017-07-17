<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use DateTime;
use DateTimeZone;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\rest\ResourceResponse;
use Drupal\yqb_reminders\general\Reminders;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "user_flight_rest_resource",
 *   label = @Translation("User Flight rest resource"),
 *   serialization_class = "Drupal\yqb_api\Normalizer\JsonDenormalizer",
 *   uri_paths = {
 *     "canonical" = "/v1/users/flights",
 *     "https://www.drupal.org/link-relations/create" = "/v1/users/flights"
 *   }
 * )
 */
class UserFlightRestResource extends YQBResourceBase  {

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
   * Returns a list of flights mapped to current user
   */
  public function get() {
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content') || !$this->currentUser->id()) {
      return $this->getUnauthorizedResponse($this->currentUser);
    }
    
    // Prepare state for flight fetching
    $state = (isset($_GET['state'])) ? $_GET['state'] : 'travel';
    if(!in_array($state, $this->states)) $state = 'travel';
    
    $response = $this->fetchUserFlights($state);
    
    $response = new ResourceResponse($response);
    $response->addCacheableDependency($this->currentUser);
    return $response;
  }
  
  /**
   * Responds to POST requests.
   *
   * Creates a new user flight entry 
   *
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   */
  public function post($data) {
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content') || !$this->currentUser->id()) {
      return $this->getUnauthorizedResponse();
    }
    
    if(!isset($data['state'])){
      return new ResourceResponse(['error' => $this->t("Flight state is required", [], ['langcode' => $this->language])], 400);
    }
    
    if(isset($data['flight_id'])){
      // Fetch from cache, multiple results response
      $returnFlight = $this->readCache($data['flight_id'], 'flights');
      $returnFlight['state'] = $data['state'];
      
      if(!$returnFlight){
        return new ResourceResponse(['error' => $this->t("Flight not found", [], ['langcode' => $this->language])], 404);
      }
    }else{
      $returnFlight = $data;
    }
    
    if($this->validateUserFlight($returnFlight, $this->currentUser)) {
      if ($userFlight = $this->createUserFlight($returnFlight, $this->currentUser)) {
        $returnFlight['id'] = $userFlight['id'];

        return new ResourceResponse([$returnFlight], 201);
      } else {
        return new ResourceResponse(['error' => $this->t("Error saving flight", [], ['langcode' => $this->language])], 400);
      }
    }else{
      return new ResourceResponse(['error' => $this->t("The flight is already registered to this user", [], ['langcode' => $this->language])], 409);
    }
  }
  
  /**
   * Responds to PATCH requests.
   *
   * Update a user flight relationship
   *
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   */
  public function patch($data) {
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content') || !$this->currentUser->id()) {
      return $this->getUnauthorizedResponse();
    }
    
    if(!isset($data['id'])){
      return new ResourceResponse(['error' => $this->t('Missing parameter : Id', [], ['langcode' => $this->language])], 400);
    }

    /**
     * Get user flight to update and validate if it exists
     */
    $currentFlight = Node::load($data['id']);
        
    if($currentFlight){
      if(isset($data['flight_id']) || $currentFlight->field_flight_type->value == $data['type']) {
        $user = $currentFlight->field_user->first()->get('entity')->getTarget()->getValue();

        if ($user->id() == $this->currentUser->id()) {
          // Get Flight Rest Resource
          $rest = new FlightRestResource($this->configuration, $this->pluginId, $this->pluginDefinition, $this->serializerFormats, $this->logger, $this->currentUser);

          if (isset($data['flight_id'])) {
            /**
             * Fetch from cache, multiple results response
             */
            $cachedFlight = $this->readCache($data['flight_id'], 'flights');

            if (!$cachedFlight) {
              return new ResourceResponse(['error' => $this->t("Flight not found", [], ['langcode' => $this->language])], 404);
            } else {
              if($currentFlight->field_flight_type->value == $cachedFlight['type']) {
                $userFlights = [$cachedFlight];
              }else{
                return new ResourceResponse(['error' => $this->t("Flight searched has to be of the same type than the flight replaced.", [], ['langcode' => $this->language])], 400);
              }
            }
          } else {
            /**
             * Search DEL
             */
            $userFlights = $rest->searchDEL($data);
          }

          if (sizeof($userFlights) == 1) {
            /**
             * Only 1 result, update user_flight
             */
            $returnFlight = (isset($data['flight_id'])) ? current($userFlights) : $rest->fetchFlightRelations(current($userFlights));

            if ($this->validateUserFlight($returnFlight, $this->currentUser)) {
              if ($this->updateUserFlight($returnFlight, $this->currentUser, $currentFlight)) {
                // Clean
                if(isset($returnFlight['flight_id'])) unset($returnFlight['flight_id']);
                $returnFlight['id'] = $data['id'];
                $returnFlight['state'] = $currentFlight->field_state->value;
                
                return new ResourceResponse([$returnFlight], 201);
              } else {
                return new ResourceResponse(['error' => $this->t("Error updating flight", [], ['langcode' => $this->language])], 400);
              }
            } else {
              return new ResourceResponse(['error' => $this->t("This flight is already registered for this user.", [], ['langcode' => $this->language])], 409);
            }
          } elseif (!empty($userFlights)) {
            /**
             * If multiple flights correspond to user criterias, show them
             */
            foreach ($userFlights as &$userFlight) {
              $userFlight = $rest->fetchFlightRelations($userFlight);

              // Cache Identifier for future POST
              $this->cacheAndClean($userFlight, $userFlight['id'], 'flights', 100);
            }

            return new ResourceResponse($userFlights);
          } else {
            return new ResourceResponse(['error' => $this->t("No result found. The flight was not updated", [], ['langcode' => $this->language])], 404);
          }
        } else {
          return new ResourceResponse(['error' => $this->t("User flight requested doesn't belong to user", [], ['langcode' => $this->language])], 403);
        }
      }else{
        return new ResourceResponse(['error' => $this->t("Flight searched has to be of the same type than the flight replaced.", [], ['langcode' => $this->language])], 400);
      }
    }else{
      return  new ResourceResponse([
        'error' => $this->t("User flight not found", [], ['langcode' => $this->language]) 
      ], 404); 
    }
  }
  
  /**
   * Responds to DELETE requests.
   *
   * Delete a user flight relationship
   *
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   */
  public function delete($data) {
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content') || !$this->currentUser->id()) {
      return $this->getUnauthorizedResponse();
    }
    
    $node = Node::load($data['id']);
    
    if(!empty($node)) {
      if($node->bundle() == 'user_flight') {
        $user = $node->field_user->first()->get('entity')->getTarget()->getValue();

        if ($user->id() == $this->currentUser->id()) {
          $node->set('field_archived', true);
          $node->save();
          
          return new ResourceResponse(null);
        } else {
          return new ResourceResponse(['error' => $this->t("User flight requested doesn't belong to user", [], ['langcode' => $this->language])], 403);
        }
      }else{
        return new ResourceResponse(['error' => $this->t("User flight not found", [], ['langcode' => $this->language])], 404);
      }
    }else{
      return new ResourceResponse(['error' => $this->t("User flight not found", [], ['langcode' => $this->language])], 404);
    }
  }
  
  /*************************************************************
   *  HELPERS
   *************************************************************/

  /**
   * Get user flights and map them with correct ids
   * @param $state
   * @return array
   */
  public function fetchUserFlights($state){
    /**
     * Fetch user flights
     */
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'user_flight')
      ->sort('field_flight_date', 'ASC')
      ->sort('field_flight_time', 'ASC')
      ->condition('field_user', $this->currentUser->id())
      ->condition('field_state', $state)
      ->condition('field_archived', false);
    
    $results = $query->execute();
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $flights = $node_storage->loadMultiple($results);

    /**
     * Remash data for pretty response
     */
    $response = $parkings = [];
    foreach($flights as $flight){
      /**
       * Ignore completed flights that have been completed for more than 2 hours, Archive them
       */
      if(!empty($flight->field_completed_time->value) && time() >= strtotime('+2 hours', $flight->field_completed_time->value)){
        $node = Node::load($flight->id());
        $node->set('field_archived', true);
        $node->save();
        continue;
      };
      
      /**
       * Fetch status
       */
      $statusNode = $flight->field_status->first()->get('entity')->getTarget()->getValue();
      $statusNode = \Drupal::service('entity.repository')->getTranslationFromContext($statusNode, $this->language);
      
      /**
       * Fetch airline
       */
      $airlineNode = $flight->field_airline->first()->get('entity')->getTarget()->getValue();

      /**
       * Fetch Destination Airport
       */
      if($flight->field_destination_airport->first()->target_id){
        /** @var \Drupal\node\Entity\Node $destinationNode */
        $destinationNode = $flight->field_destination_airport->first()->get('entity')->getTarget()->getValue();
        
        $destination = [
          "id" => $destinationNode->id(),
          'iata' => $destinationNode->field_iata->value,
          'city' => $destinationNode->field_city->value,
          'name' => $destinationNode->title->value,
          'image' => ($destinationNode->hasField('field_image')) ? $destinationNode->field_image->value : null
        ];
      }else{
        $destination = $this->yqb;
      }

      /**
       * Fetch Origin Aiport
       */
      if($flight->field_origin_airport->first()->target_id){
        /** @var \Drupal\node\Entity\Node $originNode */
        $originNode = $flight->field_origin_airport->first()->get('entity')->getTarget()->getValue();
        
        $origin = [
          "id" => $originNode->id(),
          'iata' => $originNode->field_iata->value,
          'city' => $originNode->field_city->value,
          'name' => $originNode->title->value,
          'image' => ($originNode->hasField('field_image')) ? $originNode->field_image->value : null
        ];
      }else{
        $origin = $this->yqb;
      }
      
      /**
       * Fetch Image
       */
      $query = \Drupal::entityQuery('node')
         ->condition('type', 'destination')
         ->condition('field_airport', ($flight->field_flight_type->value == 'arrival') ? $origin['id'] : $destination['id'])
         ->pager(1);
      
      $city = $query->execute();
            
      if (!empty($city)){
        $city = Node::load(current($city));
        if(!empty($city->field_header_image->entity->uri->value)){
          $image = file_create_url($city->field_header_image->entity->uri->value);
        }
      }

      /**
       * Prepare user flight array;
       */
      $userFlight = [
        "id" => $flight->id(),
        "type" => $flight->field_flight_type->value,
        "number" => $flight->field_flight_number->value,
        "date" => $flight->field_flight_date->value,
        "time" => $flight->field_flight_time->value,
        "flightDateTime" => strtotime($flight->field_flight_date->value.' '.$flight->field_flight_time->value.':00')*1000,
        "gate" => $flight->field_gate->value,
        "state" => $flight->field_state->value,
        "airline" => [
          "id" => $airlineNode->id(),
          "icao" => ($airlineNode->field_icao->value === 'JZA' || $airlineNode->field_icao->value === 'QZ') ? 'ACA' : $airlineNode->field_icao->value,
          "realIcao" => $airlineNode->field_icao->value,
          "name" => $airlineNode->title->value,
        ],
        "status" => [
          "id" => $statusNode->id(),
          "title" => $statusNode->name->value,
          "message" => $this->getListStatusMessage($statusNode->id(), $flight, $this->language)
        ],
        "destination" => $destination,
        "origin" => $origin,
        "carousel" => ($flight->field_flight_type->value == 'arrival') ? $flight->field_carousel_name->value : null,
      ];
      
      $response[] = $userFlight;
    }
    
    return $response;
  }
}
