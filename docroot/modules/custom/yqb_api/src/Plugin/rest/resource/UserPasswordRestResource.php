<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "user_password_rest_resource",
 *   label = @Translation("User password rest resource"),
 *   serialization_class = "Drupal\yqb_api\Normalizer\JsonDenormalizer",
 *   uri_paths = {
 *     "canonical" = "/v1/users/password",
 *     "https://www.drupal.org/link-relations/create" = "/v1/users/password"
 *   }
 * )
 */
class UserPasswordRestResource extends YQBResourceBase  {

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
    LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
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
      $container->get('logger.factory')->get('yqb_api')
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {
    // Load user by email
    $user = user_load_by_mail($data['email']);
    
    // No cache
    $build = array(
      '#cache' => array(
        'max-age' => 0
      ),
    );

    if (!$user) {
      $response = new ResourceResponse(['error' => $this->t('User not found', [], ['langcode' => $this->language])], 404);
      $response->addCacheableDependency($user);
    } else {
      $mail = _user_mail_notify('password_reset', $user);

      if (empty($mail)) {
        $response = new ResourceResponse(['error' => $this->t('Error sending the email', [], ['langcode' => $this->language])], 400);
        $response->addCacheableDependency($build);
      } else {
        $response = new ResourceResponse(null);
        $response->addCacheableDependency($build);
      }
    }
    return $response;
  }
}
