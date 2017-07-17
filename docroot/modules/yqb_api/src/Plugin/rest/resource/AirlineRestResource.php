<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "airline_rest_resource",
 *   label = @Translation("Airline rest resource"),
 *   uri_paths = {
 *     "canonical" = "/v1/airlines"
 *   }
 * )
 */
class AirlineRestResource extends YQBResourceBase  {

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
   * Returns a list of airlines.
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
     * Fetch airlines
     */
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'airline')
      ->sort('title', 'ASC');
    
    $result = $query->execute();
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($result);

    /**
     * Remash data for pretty response
     */
    $flights = array();
    foreach($nodes as $node){
      $icao = $node->field_icao->value;
           
      $flights[] = [
        'id' => $node->nid->value,
        'title' => $node->title->value,
        'icao' =>  ($icao === 'JZA' || $icao === 'QZ') ? 'ACA' : $icao,
        'realIcao' => $icao,
      ];
    }
    
    return (new ResourceResponse($flights))->addCacheableDependency($build);
  }

}
