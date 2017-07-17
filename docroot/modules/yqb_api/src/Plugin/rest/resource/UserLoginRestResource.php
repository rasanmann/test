<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Facebook\Facebook;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "user_login_rest_resource",
 *   label = @Translation("User login rest resource"),
 *   serialization_class = "Drupal\yqb_api\Normalizer\JsonDenormalizer",
 *   uri_paths = {
 *     "canonical" = "/v1/users/login",
 *     "https://www.drupal.org/link-relations/create" = "/v1/users/login"
 *   }
 * )
 */
class UserLoginRestResource extends YQBResourceBase  {

  /**
   * A current user instance.
   *
   * @var \Drupal\user\UserAuth
   */
  protected $auth;

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
    AccountProxyInterface $current_user,
    \Drupal\user\UserAuth $auth) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->auth = $auth;
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
      $container->get('current_user'),
      $container->get('user.auth')
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {
    switch(true) {
      // Anonymous login
      case array_key_exists('uuid', $data):
        return $this->anonymous($data);
        break;

      // Facebook login
      case array_key_exists('facebook_token', $data):
        return $this->facebook($data);
        break;

      // Google login
      case array_key_exists('google_token', $data):
        return $this->google($data);
        break;

      // Email login
      default:
        return $this->email($data);
    }
  }

  /**
   * Attaches an account to the current user
   * @param $uid
   * @param $rest
   * @param $data
   * @return mixed
   */
  private function setAccount($uid, $rest, $data = []) {
    $account = User::load($uid);

    // Attach account to current user
    \Drupal::currentUser()->setAccount($account);
    \Drupal::logger('user')->notice('Session opened for %name.', array('%name' => $account->getUsername()));

    // Create session and cookie
    user_login_finalize($account);
    
    $patchData = ['language' => $this->language];
    
    if(isset($data['push_android_token'])) $patchData['push_android_token'] = $data['push_android_token'];
    if(isset($data['push_ios_token'])) $patchData['push_ios_token'] = $data['push_ios_token'];
    
    // Patch language
    $rest->patch($account->id(), $patchData);

    return new ResourceResponse([
      'id' => $account->id(),
      'first_name' => $account->get('field_first_name')->get(0)->value,
      'last_name' => $account->get('field_last_name')->get(0)->value,
      'email' => $account->getEmail(),
      'caller_id' => $account->get('field_caller_id')->get(0)->value,
      'newsletter' => (bool) $account->get('field_newsletter')->get(0)->value,
      'type' => (!empty($account->field_facebook_user_id->value)) ? 'facebook' : ((!empty($account->field_google_user_id->value)) ? 'google' : ((!empty($account->field_uuid->value)) ? 'uuid' : 
        'email'))
    ]);
  }

  /**
   * Login by email
   * @param $email
   * @return ResourceResponse
   */
  private function email($data) {
    $email = $data['email'];
    $password = $data['password'];
    
    // Load user by email
    $user = user_load_by_mail($email);
    
    if($user !== false) {
      // Get user UID
      $uid = $this->auth->authenticate($user->getAccountName(), $password);
      
      if($uid) {
        $rest = new UserRestResource($this->configuration, $this->pluginId, $this->pluginDefinition, $this->serializerFormats, $this->logger);

        // Return account info
        return $this->setAccount($uid, $rest, $data);
      }else{
        return new ResourceResponse(["error" => $this->t("The email or password is incorrect", [], ['langcode' => $this->language])], 403);
      }
    }else{
      return new ResourceResponse(["error" => $this->t("User not found", [], ['langcode' => $this->language])], 404);
    }
  }

  /**
   * Login with Facebook
   * @param $data
   * @return ResourceResponse
   */
  private function anonymous($data) {
    $uuid = $data['uuid'];
    
    $query = \Drupal::entityQuery('user')->condition('field_uuid', $uuid);

    $result = $query->execute();
    
    // UserRest Resource
    $rest = new UserRestResource($this->configuration, $this->pluginId, $this->pluginDefinition, $this->serializerFormats, $this->logger);

    if (empty($result)) {
      // This is a new user
      return $rest->post($data);
    } else {
      // Return account info
      return $this->setAccount(current($result), $rest, $data);
    }
  }

  /**
   * Login with Facebook
   * @param $data
   * @return ResourceResponse
   */
  private function facebook($data) {
    $token = $data['facebook_token'];
    
    // Validate if token is valid with Facebook
    $fb = new Facebook($this->facebookConfig);

    $fb->setDefaultAccessToken($token);

    try {
      // Returns a `Facebook\FacebookResponse` object
      $response = $fb->get('/me?fields=id');
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      $response = new ResourceResponse(['error' => $this->t('Graph returned an error: @msg', ['@msg' => $e->getMessage()], ['langcode' => $this->language])], 400);
      return $response;
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      $response = new ResourceResponse(['error' => $this->t('Facebook SDK returned an error: @msg', ['@msg' => $e->getMessage()], ['langcode' => $this->language])], 400);
      return $response;
    }

    $user = $response->getGraphUser();

    $query = \Drupal::entityQuery('user')->condition('field_facebook_user_id', $user->getId());

    $result = $query->execute();

    // UserRest Resource
    $rest = new UserRestResource($this->configuration, $this->pluginId, $this->pluginDefinition, $this->serializerFormats, $this->logger);
    
    if (empty($result)) {
      // This is a new user
      return $rest->post($data);
    } else {
      // Return account info
      return $this->setAccount(current($result), $rest, $data);
    }
  }

  /**
   * Login with Google
   * @param $data
   * @return ResourceResponse
   */
  private function google($data) {
    $token = $data['google_token'];
    
    $payload = file_get_contents('https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);

    if (!$payload) {
      $response = new ResourceResponse(['error' => $this->t('Google API error', [], ['langcode' => $this->language])], 400);
      return $response;
    }

    $profile = json_decode($payload);

    if (!$payload) {
      $response = new ResourceResponse(['error' => $this->t('Google API JSON decode error', [], ['langcode' => $this->language])], 400);
      return $response;
    }

    $query = \Drupal::entityQuery('user')->condition('field_google_user_id', $profile->sub);

    $result = $query->execute();

    // UserRest Resource
    $rest = new UserRestResource($this->configuration, $this->pluginId, $this->pluginDefinition, $this->serializerFormats, $this->logger);
    
    if (empty($result)) {
      // This is a new user
      return $rest->post($data);
    } else {
      // Return account info
      return $this->setAccount(current($result), $rest, $data);
    }
  }
}
