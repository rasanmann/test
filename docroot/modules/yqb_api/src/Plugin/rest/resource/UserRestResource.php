<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Facebook\Facebook;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "user_rest_resource",
 *   label = @Translation("User rest resource"),
 *   serialization_class = "Drupal\yqb_api\Normalizer\JsonDenormalizer",
 *   uri_paths = {
 *     "canonical" = "/v1/users/{user}",
 *     "https://www.drupal.org/link-relations/create" = "/v1/users"
 *   }
 * )
 */
class UserRestResource extends YQBResourceBase  {

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
   * Responds to GET requests.
   *
   * Returns the current user
   *
   * @param $id
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   */
  public function get($id = null) {
    if($id) {
      $user = User::load($id);
      
      // Empty fields for guest user
      if((!empty($user->get('field_uuid')->get(0)->value))){
        $userData = [
          'first_name' => '',
          'last_name' => '',
          'email' => '',
          'newsletter' => false,
          'type' => 'uuid'
        ];
      }else{
        $userData = [
          'first_name' => $user->get('field_first_name')->get(0)->value,
          'last_name' => $user->get('field_last_name')->get(0)->value,
          'email' => $user->getEmail(),
          'newsletter' => $user->get('field_newsletter')->get(0)->value,
          'type' => (!empty($user->field_facebook_user_id->value)) ? 'facebook' : ((!empty($user->field_google_user_id->value)) ? 'google' : 'email')
        ];
      }

      // Return account result
      $response = new ResourceResponse([
          'id' => $user->id(),
          'first_name' => $userData['first_name'],
          'last_name' => $userData['last_name'],
          'email' => $userData['email'],
          'caller_id' => $user->get('field_caller_id')->get(0)->value,
          'newsletter' => (bool) $userData['newsletter'],
          'type' => $userData['type']
      ]);
      
      $response->addCacheableDependency($this->currentUser);
      return $response;
    }else{
      $response = new ResourceResponse(['error' => $this->t('Missing parameter : Id', [], ['langcode' => $this->language])], 400);
      $response->addCacheableDependency($this->currentUser);
      return $response;
    }
  }

  /**
   * Responds to PATCH requests.
   *
   * Update a user
   */
  public function patch($id, $data) {  
    if($id) {
      $user = User::load($id);
      
      if(\Drupal::currentUser()->id() == $user->id()) {
        
        foreach ($data as $key => $value) {
          if ($key === 'password') {
            $user->setPassword($value);
          } else if ($key === 'email') {
            // Check if email is already used
            $query = \Drupal::entityQuery('user')
              ->condition('mail', $data['email'])
              ->condition('uid', $user->id(), '!=');
            
            $count = $query->count()->execute();
            
            if($count){
              $response = new ResourceResponse(['error' => strip_tags($this->t('The email address %mail is already taken.', ['%mail' => $data['email']], ['langcode' => $this->language]))], 400);
              $response->addCacheableDependency($this->currentUser);
              return $response;
            }  
                      
            $user->setEmail($value);
          } else if ($key === 'first_name') {
            $user->set('field_first_name', $value);
          } else if ($key === 'last_name') {
            $user->set('field_last_name', $value);
          } else if ($key === 'push_ios_token') {
            $user->set('field_push_ios_token', $value);
          } else if ($key === 'push_android_token') {
            $user->set('field_push_android_token', $value);
          } else if ($key === 'language') {
            $user->set('field_language', $value);
          } else if ($key === 'newsletter') {
            $user->set('field_newsletter', (int) $value);
          }
        }

        // Upgrade anonymous account to email account
        if (isset($data['email']) && !empty($user->field_uuid->value)) {
          module_load_include('module', 'email_registration', 'email_registration');
          
          // Reset his password if new account from uuid but no password entered
          if(!isset($data['password'])) _user_mail_notify('password_reset', $user);
          
          // Create his username
          $new_name = preg_replace('/@.*$/', '', $data['email']);
          $new_name = email_registration_cleanup_username($new_name);
      
          // Ensure whatever name we have is unique.
          $user->setUsername(email_registration_unique_username($new_name));
          
          // Remove his uuid
          $user->set('field_uuid', null);
        }

        if ($user->save()) {
          return new ResourceResponse([
            'id' => $user->id(),
            'first_name' => $user->get('field_first_name')->get(0)->value,
            'last_name' => $user->get('field_last_name')->get(0)->value,
            'email' => $user->getEmail(),
            'caller_id' => $user->get('field_caller_id')->get(0)->value,
            'newsletter' => (bool)$user->get('field_newsletter')->get(0)->value,
            'type' => (!empty($user->field_facebook_user_id->value)) ? 'facebook' : ((!empty($user->field_google_user_id->value)) ? 'google' : ((!empty($user->field_uuid->value)) ? 'uuid' : 'email'))
          ]);
        } else {
          $response = new ResourceResponse(['error' => $this->t('An error occurred during the account updating process.', [], ['langcode' => $this->language])], 400);
          $response->addCacheableDependency($this->currentUser);
          return $response;
        }
      }else{
        $response = new ResourceResponse(['error' => $this->t('You cannot update someone else’s account.', [], ['langcode' => $this->language])], 403);
        $response->addCacheableDependency($this->currentUser);
        return $response;
      }

    }else{
      $response = new ResourceResponse(['error' => $this->t('Missing parameter : Id', [], ['langcode' => $this->language])], 400);
      $response->addCacheableDependency($this->currentUser);
      return $response;
    }
  }

  /**
   * Responds to POST requests.
   *
   * Creates a user
   */
  public function post($data) {
    $user = [];

    switch(true) {
      // Anonymous
      case array_key_exists('uuid', $data):
        // Set data
        $user = [
            'pass' => uniqid(null, true),
            'mail' => strtolower($data['uuid']) . '@yqb.ca',
            'status' => 1,
            'field_first_name' => $data['uuid'],
            'field_last_name' => $data['uuid'],
            'field_uuid' => $data['uuid']
        ];
      break;

      // Facebook
      case array_key_exists('facebook_token', $data):
        $fb = new Facebook($this->facebookConfig);

        $fb->setDefaultAccessToken($data['facebook_token']);

        try {
          $response = $fb->get('/me?fields=id,first_name,last_name,email');
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
          $response = new ResourceResponse(['error' => $this->t('Graph returned an error: @msg', ['@msg' => $e->getMessage()], ['langcode' => $this->language])], 400);
          return $response;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
          $response = new ResourceResponse(['error' => $this->t('Facebook SDK returned an error: @msg', ['@msg' => $e->getMessage()], ['langcode' => $this->language])], 400);
          return $response;
        }

        // Get user info
        $graphUser = $response->getGraphUser();

        // Set data
        $user = [
            'pass' => uniqid(null, true),
            'mail' => $graphUser->getEmail(),
            'status' => 1,
            'field_first_name' => $graphUser->getFirstName(),
            'field_last_name' => $graphUser->getLastName(),
            'field_facebook_user_id' => $graphUser->getId()
        ];

        // TODO : make sure facebook user ID is unique
      break;

      // Google
      case array_key_exists('google_token', $data):
        $payload = file_get_contents('https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $data['google_token']);

        if (!$payload) {
          $response = new ResourceResponse(['error' => $this->t('Google API error', [], ['langcode' => $this->language])], 400);
          return $response;
        }

        $profile = json_decode($payload);

        if (!$payload) {
          $response = new ResourceResponse(['error' => $this->t('Google API JSON decode error', [], ['langcode' => $this->language])], 400);
          return $response;
        }

        $user = [
            'pass' => uniqid(null, true),
            'mail' => $profile->email,
            'status' => 1,
            'field_first_name' => $profile->given_name,
            'field_last_name' => $profile->family_name,
            'field_google_user_id' => $profile->sub
        ];

        // TODO : make sure google user ID is unique
      break;

      // Email
      default:
        $user = [
            'pass' => $data['password'],
            'mail' => $data['email'],
            'status' => 1,
            'field_first_name' => $data['first_name'],
            'field_last_name' => $data['last_name'],
            'field_newsletter' => (int) $data['newsletter'],
        ];
      break;
    }
    
    // Add language
    $user['field_language'] = $this->language;
    
    // Add push fields if they are present
    if(array_key_exists('push_ios_token', $data)) $user['field_push_ios_token'] = $data['push_ios_token'];
    if(array_key_exists('push_android_token', $data)) $user['field_push_android_token'] = $data['push_android_token'];
    
    // Generate random unique caller_id
    while(true){
      $callerId = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
      
      $query = \Drupal::entityQuery('user')->condition('field_caller_id', $callerId);
      $count = $query->count()->execute();
      
      if(!$count){
        $user['field_caller_id'] = $callerId;
        break;
      }
    }

    $account = User::create($user);

    // Other modules may implement hook_email_registration_name($edit, $account)
    // to generate a username (return a string to be used as the username, NULL
    // to have email_registration generate it).
    $names = \Drupal::moduleHandler()->invokeAll('email_registration_name', [$account]);

    // Remove any empty entries.
    $names = array_filter($names);

    module_load_include('module', 'email_registration', 'email_registration');

    if (empty($names)) {
      // Strip off everything after the @ sign.
      $new_name = preg_replace('/@.*$/', '', $account->getEmail());
      // Clean up the username.
      $new_name = email_registration_cleanup_username($new_name);
    } else {
      // One would expect a single implementation of the hook, but if there
      // are multiples out there use the last one.
      $new_name = array_pop($names);
    }

    // Ensure whatever name we have is unique.
    $account->setUsername(email_registration_unique_username($new_name));

    $violations = $account->validate();

    if ($violations->count()) {
      $errors = [];

      foreach($violations as $violation) {
        $errors[] = strip_tags($violation->getMessage());
      }

      $response = new ResourceResponse(['error' => implode(',', $errors)], 400);
      return $response;
    } else if (!$account->save()) {
      $response = new ResourceResponse(['error' => $this->t('Unknown error', [], ['langcode' => $this->language])], 400);
      return $response;
      
    } else {
      // Send cookie to user
      user_login_finalize($account);

      // Return account result
      return new ResourceResponse([
        'id' => $account->id(),
        'first_name' => $account->get('field_first_name')->get(0)->value,
        'last_name' => $account->get('field_last_name')->get(0)->value,
        'email' => $account->getEmail(),
        'caller_id' => $account->field_caller_id->value,
        'newsletter' => (bool) $account->get('field_newsletter')->get(0)->value,
        'type' => (!empty($account->field_facebook_user_id->value)) ? 'facebook' : ((!empty($account->field_google_user_id->value)) ? 'google' : ((!empty($account->field_uuid->value)) ? 'uuid' : 
              'email'))
      ]);
    }
  }
}