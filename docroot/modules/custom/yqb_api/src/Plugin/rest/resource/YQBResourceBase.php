<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Route;

class YQBResourceBase extends ResourceBase {

  protected $language;
  protected $defaultDestinationImage = 'themes/yqb/img/maps/default-destination.jpg';

  protected $yqb = [
    "id" => null,
    "iata" => "YQB",
    "city" => "Québec",
    "name" => "Aéroport international Jean-Lesage",
    "image" => null,
  ];

  protected $facebookConfig = [
      'app_id' => '1246543422032305',
      'app_secret' => 'aae299538a544794e44618af358a8cdf',
      'default_graph_version' => 'v2.7',
  ];

  protected $googleConfig = [
      'client_id' => '741806413961-akc5n7omdv4oqdjveeeen56jei5gtnvv.apps.googleusercontent.com',
      'client_secret' => 'go9SP2raZoSPVqImHVUejJPW'
  ];

  protected $states = [
    'travel', 'companion'
  ];

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
    $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    // Set language
    $language = \Drupal::request()->headers->get('accept-language');
    $this->language = (!empty($language)) ? $language : 'fr';
  }

  /**
   * Setups the base route for all HTTP methods.
   *
   * @param string $canonical_path
   *   The canonical path for the resource.
   * @param string $method
   *   The HTTP method to be used for the route.
   *
   * @return \Symfony\Component\Routing\Route
   *   The created base route.
   */
  protected function getBaseRoute($canonical_path, $method) {
    $lower_method = strtolower($method);

    $route = new Route($canonical_path, array(
        '_controller' => 'Drupal\yqb_api\RequestHandler::handle',
      // Pass the resource plugin ID along as default property.
        '_plugin' => $this->pluginId,
    ), array(
        '_permission' => "restful $lower_method $this->pluginId",
    ),
        array(),
        '',
        array(),
        // The HTTP method is a requirement for this route.
        array($method)
    );
    return $route;
  }

  protected function validateUserFlight($data, $user){
    $rest = new UserFlightRestResource($this->configuration, $this->pluginId, $this->pluginDefinition, $this->serializerFormats, $this->logger, $user);
    $currentUserFlights = $rest->fetchUserFlights($data['state']);

    foreach($currentUserFlights as $flight){
      if($flight['number'] == $data['number'] && $flight['type'] == $data['type'] && $flight['date'] == $data['date'] && $flight['time'] == $data['time']){
        return false;
      }
    }

    return true;
  }

  /**
   * Receives user_flight data and save the node
   * Used from /users POST and /users/flights POST
   * @param $data
   * @return array|mixed
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   * @return int
   */
  protected function createUserFlight($data, $user){
    $userFlight = [
      'type' => 'user_flight',
      'title' => sprintf('%s on %s for %s', $data['number'], $data['date'], $user->id()),
      'langcode' => $this->language,
      'field_flight_type' => $data['type'],
      'field_flight_date' => $data['date'],
      'field_flight_time' => $data['time'],
      'field_gate' => $data['gate'],
      'field_flight_number' => $data['number'],
      'field_flight_airline' => $data['airline']['realIcao'],
      'field_airline' => $data['airline']['id'],
      'field_destination_airport' => $data['destination']['id'],
      'field_origin_airport' => $data['origin']['id'],
      'field_user' => $user->id(),
      'field_status' => $data['status']['id'],
      'field_state' => $data['state'],
      'field_archived' => 0,
      'field_completed' => 0,
      'field_carousel_name' => ($data['type'] == 'arrival') ? $data['carousel'] : null,
    ];

    // Add departure flight to arrival
    if(isset($data['departure'])){
      $userFlight['field_user_flight'] = $data['departure'];
    }

    $node = Node::create($userFlight);

    if($node->save()){
      $userFlight['id'] = $node->id();

      return $userFlight;
    }else{
      return false;
    }
  }

  /**
   * Update a user flight
   * @param $data
   * @param $user
   * @param \Drupal\node\Entity\Node $currentFlight
   * @return bool
   */
  protected function updateUserFlight($data, $user, $currentFlight){
    $currentFlight->setTitle(sprintf('%s on %s for %s', $data['number'], $data['date'], $user->id()));
    $currentFlight->set('field_flight_type', $data['type']);
    $currentFlight->set('field_flight_date', $data['date']);
    $currentFlight->set('field_flight_time', $data['time']);
    $currentFlight->set('field_gate', $data['gate']);
    $currentFlight->set('field_flight_number', $data['number']);
    $currentFlight->set('field_flight_airline', $data['airline']['realIcao']);
    $currentFlight->set('field_airline', $data['airline']['id']);
    $currentFlight->set('field_destination_airport', $data['destination']['id']);
    $currentFlight->set('field_origin_airport', $data['origin']['id']);
    $currentFlight->set('field_status', $data['status']['id']);

    return $currentFlight->save();
  }

  /**
   * Default error message for unauthorized response
   * @param null $dependency
   * @return ResourceResponse
   */
  protected function getUnauthorizedResponse($dependency = null){
    $response = new ResourceResponse([
      'error' => $this->t("Unauthorized", [], ['langcode' => $this->language])
    ], 403);

    if(!empty($dependency)) {
      $response->addCacheableDependency($dependency);
    }

    return $response;
  }

  /**
   * Returns the message to display in app flight status
   * @param $status
   * @param $userFlight
   * @param $language
   * @param $raw
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getListStatusMessage($status, $userFlight, $language, $raw = false) {
    if($raw){
      $raw = $userFlight;
      $userFlight = new \stdClass();
      $userFlight->field_flight_type->value = $raw['type'];
      $userFlight->field_flight_time->value = $raw['time'];
      $userFlight->field_gate->value = $raw['gate'];
    }
    switch ($status) {
      case 8: // (Arrived)
        $msg = $this->t("Arrivé", [], ['langcode' => $language]);
      break;
      case 10: // (Departed)
        $msg = $this->t("Ce vol a décollé", [], ['langcode' => $language]);
      break;
      case 11: // (Delayed)
        if ($userFlight->field_flight_type->value == 'departure') {
          $msg = $this->t("Délai : départ repoussé à @time", ['@time' => $userFlight->field_flight_time->value], ['langcode' => $language]);
        } else {
          $msg = $this->t("Délai : arrivée repoussée à @time", ['@time' => $userFlight->field_flight_time->value], ['langcode' => $language]);
        }
      break;
      case 12: // (Canceled)
        $msg = $this->t("Vol annulé. Contactez votre compagnie aérienne.", [], ['langcode' => $language]);
      break;
      case 13: // (Early)
        if ($userFlight->field_flight_type->value == 'departure') {
          $msg = $this->t("En avance : Nouveau départ à @time", ['@time' => $userFlight->field_flight_time->value], ['langcode' => $language]);
        } else {
          $msg = $this->t("En avance : Arrivée devancée à @time", ['@time' => $userFlight->field_flight_time->value], ['langcode' => $language]);
        }
      break;
      // Gate has changed
      case 44: // (Gate changed)
        $msg = $this->t("Changement : Votre numéro de porte est maintenant @gate", ['@gate' => $userFlight->field_gate->value], ['langcode' => $language]);
      break;

      case 9:
      default: // (On time)
        $msg = $this->t("A l'heure : tout va bien", [], ['langcode' => $language]);
      break;
    }

    return $msg;
  }

  /**
   * Log data into files and trigger cleaning process
   * @param $data
   * @param $filename
   * @param $folder
   * @param int $limit
   */
  protected function cacheAndClean($data, $filename, $folder, $limit = 50){
    // Log outgoing events and add message to log
    if (!is_dir('public://api')) {
      mkdir('public://api');
    }

    if (!is_dir('public://api/'.$folder)) {
      mkdir('public://api/'.$folder);
    }

    if(!empty($folder)) $folder .= '/';

    // Get all log files and order by most recent
    $realPublicPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $files = glob($realPublicPath . '/api/'.$folder.'*.json');

    $this->cleanLogFiles($files, $limit);

    file_put_contents(sprintf('public://api/'.$folder.'%s.json', $filename), json_encode($data));
  }

  /**
   * Read cached json flight
   * @param $filename
   * @param $folder
   * @return bool|mixed
   */
  protected function readCache($filename, $folder){
    $realPublicPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");

    if(!empty($folder)) $folder .= '/';

    if(is_file($realPublicPath . '/api/'.$folder.$filename.'.json')){
      return json_decode(file_get_contents($realPublicPath . '/api/'.$folder.$filename.'.json'), true);
    }else{
      return false;
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
