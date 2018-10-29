<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Facebook\Facebook;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "user_logout_rest_resource",
 *   label = @Translation("User logout rest resource"),
 *   serialization_class = "Drupal\yqb_api\Normalizer\JsonDenormalizer",
 *   uri_paths = {
 *     "canonical" = "/v1/users/logout",
 *     "https://www.drupal.org/link-relations/create" = "/v1/users/logout"
 *   }
 * )
 */
class UserLogoutRestResource extends YQBResourceBase  {

  /**
   * A current user instance.
   *
   * @var \Drupal\user\UserAuth
   */
  protected $auth;

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
      \Drupal\user\UserAuth $auth) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->auth = $auth;
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
        $container->get('user.auth')
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {
    $userId = \Drupal::currentUser()->id();
    
    // Remove tokens on logout
    if($userId){
      $user = User::load($userId);  
      $user->set('field_push_ios_token', '');
      $user->set('field_push_android_token', '');
      $user->save();
    }
    
    user_logout();
    return new ResourceResponse($data);
  }
}