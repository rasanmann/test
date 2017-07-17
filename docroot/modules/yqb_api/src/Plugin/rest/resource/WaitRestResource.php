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
 *   id = "wait_rest_resource",
 *   label = @Translation("Wait time rest resource"),
 *   uri_paths = {
 *     "canonical" = "/v1/wait"
 *   }
 * )
 */
class WaitRestResource extends YQBResourceBase  {

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
    
    $build = array(
      '#cache' => array(
        'max-age' => 0
      ),
    );
    
    $minutes = $this->getWaitTime();
    $time = ['wait_time' => ($minutes > 1) ? sprintf('%d min', $minutes) : '< 5 min'];
    
    $response =  new ResourceResponse($time);
    $response->addCacheableDependency($build);
    
    return $response;
  }

  public function getWaitTime(){
    $wait_config = \Drupal::configFactory()->getEditable('yqb_wait_times.wait');
    
    return round(floatval($wait_config->get('expectedWaitTime')) / 60);
  }
}
