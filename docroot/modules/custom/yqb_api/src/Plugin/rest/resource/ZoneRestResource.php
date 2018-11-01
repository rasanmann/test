<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Drupal\yamlform\Plugin\YamlFormElement\DateTime;
use Drupal\yqb_reminders\general\Reminders;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "zone_rest_resource",
 *   label = @Translation("Zone rest resource"),
 *   serialization_class = "Drupal\yqb_api\Normalizer\JsonDenormalizer",
 *   uri_paths = {
 *     "canonical" = "/v1/zones",
 *     "https://www.drupal.org/link-relations/create" = "/v1/zones"
 *   }
 * )
 */
class ZoneRestResource extends YQBResourceBase {

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
   * Returns a list of beacons grouped by zones with their tips
   */
  public function get() {
    /**
     * Fetch user flights
     */
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'zone');
    
    $results = $query->execute();
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $zones_raw = $node_storage->loadMultiple($results);

    /**
     * Remash data for pretty response
     */
    $zones = [];
    foreach($zones_raw as $zone){
      $zone = $zone->getTranslation($this->language);
      
      /**
       * Fetch beacons
       */
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'beacon')
        ->condition('field_zone', $zone->id());
      
      $results = $query->execute();
      
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $beacons_raw = $node_storage->loadMultiple($results);
      
      $beacons = [];
      foreach($beacons_raw as $beacon){
        $beacon = $beacon->getTranslation($this->language);
        $beacons[] = [
          'id' => $beacon->id(),
          'title' => $beacon->title->value,
          'uid' => $beacon->field_uid->value,
          'serial_number' => $beacon->field_serial_number->value,
          'major' => $beacon->field_major->value,
          'minor' => $beacon->field_minor->value,
        ];
      }

      /**
       * Fetch tips
       */
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'tip', '=', $this->language)
        ->condition('field_zone', $zone->id());
      
      $results = $query->execute();
      
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $tips_raw = $node_storage->loadMultiple($results);
      
      $tips = [];
      foreach($tips_raw as $tip){
        $tip = $tip->getTranslation($this->language);
        
        $tips[] = [
          'id' => $tip->id(),
          'title' => $tip->title->value,
          'content' => strip_tags(html_entity_decode($tip->body->value)),
        ];
      }

      /**
       * Combine response
       */
      $zones[] = [
        'id' => $zone->field_uid->value,
        'title' => $zone->title->value,
        'beacons' => $beacons,
        'tips' => $tips
      ];
    }
    
    $build = [
      '#cache' => [
        'contexts' => ['languages'],
        'max-age' => 10
      ],
    ];
    
    $response = new ResourceResponse($zones);
    $response->addCacheableDependency($build);
    
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
      return $this->getUnauthorizedResponse($this->currentUser);
    }

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'zone')
      ->condition('field_uid', $data['id'])
      ->pager(1);
    
    $results = $query->execute();
    
    \Drupal::logger('yqb_api')->notice(sprintf('Receiving beacon notification for user %d in zone %d', $this->currentUser->id(), $data['id']));
    
    if(!empty($results)) {
      $zone = Node::load(current($results));
      $user = User::load($this->currentUser->id());
      $beaconsVisited = explode(',', $user->field_beacons_visited->value);
      $isAnon = (!empty($user->field_uuid->value));
      
      $data = [];
      $msg = '';

      if(!in_array($zone->field_uid->value, $beaconsVisited)  && !in_array(6, $beaconsVisited)){
        // Save beacon status into user profile
        $beaconsVisited[] = $zone->field_uid->value;
        sort($beaconsVisited);
        $beaconsVisited = ltrim(implode(',', $beaconsVisited), ',');
        $user->set('field_beacons_visited', $beaconsVisited);
        $user->set('field_beacons_updated', time());
        $user->save();
        
        switch ($zone->field_uid->value) {
          case 2:
            $query = \Drupal::entityQuery('node')
              ->condition('type', 'user_flight')
              ->condition('field_flight_date', date('Y-m-d', strtotime('-12 hours')), '>=')
              ->condition('field_flight_date', date('Y-m-d', strtotime('12 hours')), '<=')
              ->condition('field_user', $this->currentUser->id())
              ->pager(1);
            
            $results = $query->execute();
            
            if(empty($results)) {
              \Drupal::logger('yqb_api')->notice(sprintf('User %d arrived at the airport and doesn\'t have a flight, greet him', $this->currentUser->id()));
              
              if($isAnon){
                $msg = $this->t("Bonjour. Qu'est-ce qui vous amène à l'aéroport aujourd'hui?",
                  [],
                  ['langcode' => $this->language]
                );
              }else {
                $msg = $this->t("Bonjour @name. Qu'est-ce qui vous amène à l'aéroport aujourd'hui?",
                  ['@name' => $user->field_first_name->value],
                  ['langcode' => $this->language]
                );
              }
              
              $data['redirection'] = 'choose';
            }else{
              \Drupal::logger('yqb_api')->notice(sprintf('User %d arrived at the airport and has flight, sending wait time', $this->currentUser->id()));
              
              $rest = new WaitRestResource($this->configuration, $this->pluginId, $this->pluginDefinition, $this->serializerFormats, $this->logger, $this->currentUser);
              $minutes = $rest->getWaitTime();
              
              $flight = Node::load(current($results));
              $data['state'] = $flight->field_state->value;
              $data['redirection'] = 'update';
              
              // Set cookie for shown wait time
              setcookie(sprintf("show_wait_%d", $this->currentUser->id()), 1, time()+12*60*60); // 12 hours
              
              if($isAnon) {
                if ($minutes > 1) {
                  $msg = $this->t(
                    "Bonjour. Bienvenue à l'aéroport. Le temps d'attente à l'inspection est d'environ @time minutes.",
                    ['@time' => $minutes],
                    ['langcode' => $this->language]
                  );
                } else {
                  $msg = $this->t(
                    "Bonjour. Bienvenue à l'aéroport. Le temps d'attente à l'inspection est de moins de 5 minutes.",
                    [],
                    ['langcode' => $this->language]
                  );
                }
              }else {
                if ($minutes > 1) {
                  $msg = $this->t(
                    "Bonjour @name. Bienvenue à l'aéroport. Le temps d'attente à l'inspection est d'environ @time minutes.",
                    ['@name' => $user->field_first_name->value, '@time' => $minutes],
                    ['langcode' => $this->language]
                  );
                } else {
                  $msg = $this->t(
                    "Bonjour @name. Bienvenue à l'aéroport. Le temps d'attente à l'inspection est de moins de 5 minutes.",
                    ['@name' => $user->field_first_name->value],
                    ['langcode' => $this->language]
                  );
                }
              }
            }
          break;
          case 3:
  //          if(isset($_COOKIE[sprintf("show_wait_%d", $this->currentUser->id())])) unset($_COOKIE[sprintf("show_wait_%d", $this->currentUser->id())]);
            
            if(!isset($_COOKIE[sprintf("show_wait_%d", $this->currentUser->id())])) {
              $query = \Drupal::entityQuery('node')
                ->condition('type', 'user_flight')
                ->condition('field_flight_date', date('Y-m-d', strtotime('-12 hours')), '>=')
                ->condition('field_flight_date', date('Y-m-d', strtotime('+12 hours')), '<=')
                ->condition('field_user', $this->currentUser->id())
                ->pager(1);
              
              $results = $query->execute();
              
              if(!empty($results)) {
                $flight = Node::load(current($results));
                $data['state'] = $flight->field_state->value;
                $data['redirection'] = 'update';
                
                \Drupal::logger('yqb_api')->notice(sprintf('User %d entered inspection zone, show wait time', $this->currentUser->id()));
  
                $rest = new WaitRestResource($this->configuration, $this->pluginId, $this->pluginDefinition, $this->serializerFormats, $this->logger, $this->currentUser);
                $minutes = $rest->getWaitTime();
  
                if ($minutes > 1) {
                  $msg = $this->t(
                    "Le temps d'attente à l'inspection est d'environ @time minutes.",
                    ['@time' => $minutes],
                    ['langcode' => $this->language]
                  );
                } else {
                  $msg = $this->t(
                    "Le temps d'attente à l'inspection est de moins de 5 minutes.",
                    [],
                    ['langcode' => $this->language]
                  );
                }
              }
            }
          break;
          case 5:
            $query = \Drupal::entityQuery('node')
              ->condition('type', 'user_flight')
              ->condition('field_flight_type', 'departure')
              ->condition('field_state', 'travel')
              ->condition('field_flight_date', date('Y-m-d', strtotime('-12 hours')), '>=')
              ->condition('field_flight_date', date('Y-m-d', strtotime('+12 hours')), '<=')
              ->condition('field_user', $this->currentUser->id())
              ->pager(1);
            
            $results = $query->execute();
            
            if(!empty($results)) {
              \Drupal::logger('yqb_api')->notice(sprintf('User %d entered wait zone, show time remaining before flight', $this->currentUser->id()));
              
              $userFlight = Node::load(current($results));
              $data['state'] = $userFlight->field_state->value;
              $data['redirection'] = 'update';
              
              $flightDate = new \DateTime($userFlight->field_flight_date->value.' '.$userFlight->field_flight_time->value);
              $interval = $flightDate->diff(date_create('now'));
              
              if ( $v = $interval->h >= 1 ){
                $delay = $this->formatPlural($interval->h, '@nb heure', '@nb heures', ['@nb' => $interval->h], ['langcode' => $this->language] );
              }else {
                $delay = $this->formatPlural($interval->i, '@nb minute', '@nb minutes', ['@nb' => $interval->i], ['langcode' => $this->language]);
              }
              
              $msg = $this->t("Il reste @delay avant votre départ. Profitez de nos installations!",
                ['@delay' => $delay],
                ['langcode' => $this->language]
              );
            }
          break;
          case 6:
            $query = \Drupal::entityQuery('node')
              ->condition('type', 'user_flight')
              ->condition('field_flight_type', 'arrival')
              ->condition('field_state', 'travel')
              ->condition('field_flight_date', date('Y-m-d', strtotime('-12 hours')), '>=')
              ->condition('field_flight_date', date('Y-m-d', strtotime('12 hours')), '<=')
              ->condition('field_user', $this->currentUser->id())
              ->pager(1);
            
            $results = $query->execute();
            
            if(!empty($results)) {
              \Drupal::logger('yqb_api')->notice(sprintf('User %d has landed, show carousel name', $this->currentUser->id()));
              
              $userFlight = Node::load(current($results));
              $data['state'] = $userFlight->field_state->value;
              $data['redirection'] = 'update';
  
              if($isAnon){
                $msg = $this->t("Bienvenue à Québec! Si vous avez des bagages, rendez-vous au carrousel @carousel.",
                  ['@carousel' => $userFlight->field_carousel_name->value],
                  ['langcode' => $this->language]
                );
              }else {
                $msg = $this->t("Bienvenue à Québec @name! Si vous avez des bagages, rendez-vous au carrousel @carousel.",
                  ['@name' => $user->field_first_name->value, '@carousel' => $userFlight->field_carousel_name->value],
                  ['langcode' => $this->language]
                );
              }
            }
          break;
        }
      }else{
        if(in_array(6, $beaconsVisited)){
          \Drupal::logger('yqb_api')->notice('User is returning, don\'t notify');
        }else {
          \Drupal::logger('yqb_api')->notice('Beacon already crossed');
        }
      }
      
      // Send SMS
      if(!empty($msg)){
        $Reminders = new Reminders();
        $Reminders->sendPushNotifications($user, $msg, $data);
      }

      return new ResourceResponse(null);
    }else{
      return new ResourceResponse(['error' => $this->t("Zone not found", [], ['langcode' => $this->language])], 404);
    }
  }

}
