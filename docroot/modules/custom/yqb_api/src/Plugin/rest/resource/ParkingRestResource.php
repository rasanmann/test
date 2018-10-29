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
 *   id = "parking_rest_resource",
 *   label = @Translation("Parking rest resource"),
 *   uri_paths = {
 *     "canonical" = "/v1/parkings"
 *   }
 * )
 */
class ParkingRestResource extends YQBResourceBase  {

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
   * Returns a list of airports
   */
  public function get() {
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      return $this->getUnauthorizedResponse();
    }
  
    /**
     * Fetch user flights
     */
    $now = date('Y-m-d');
          
    $query = \Drupal::entityQuery('node');
    
    $andDeparture= $query->andConditionGroup()
      ->condition('field_arrival', $now, '<=')
      ->condition('field_departure', $now, '>');
    
    $orGroup = $query->orConditionGroup()
      ->condition('field_arrival', $now, '>=')
      ->condition($andDeparture);
    
    $results = $query->condition('type', 'parking_booking')
      ->condition('field_user', $this->currentUser->id())
      ->condition($orGroup)
      ->sort('field_arrival', 'ASC')
      ->pager(1)
      ->execute();
    
    if(!empty($results)) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $parkings = $node_storage->loadMultiple($results);
      
      $response = ['id' => current($parkings)->id()];
    }else{
      $response = ['id' => null];
    }
    
    $response =  new ResourceResponse($response);
    $response->addCacheableDependency($this->currentUser);
    return $response;
  }

  public function post(){
    return new ResourceResponse([
      'error' => $this->t("Unauthorized", [], ['langcode' => $this->language]) 
    ], 403); 
  }
}
