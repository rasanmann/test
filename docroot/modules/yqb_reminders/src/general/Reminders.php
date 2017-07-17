<?php
/**
 * @file
 * Contains \Drupal\yqb_reminders\Global\Reminders
 */

namespace Drupal\yqb_reminders\general;

use ApnsPHP_Abstract;
use ApnsPHP_Message;
use ApnsPHP_Push;

use paragraph1\phpFCM\Client;
use paragraph1\phpFCM\Message;
use paragraph1\phpFCM\Recipient\Device;
use paragraph1\phpFCM\Notification;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\Entity;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

use Services_Twilio;
use Services_Twilio_RestException;
use Lookups_Services_Twilio;

class Reminders{

  public function __construct() {
    if (!defined('CURL_HTTP_VERSION_2_0')) {
      define('CURL_HTTP_VERSION_2_0', '3');
    }
  }

  /**
   * Save Reminder into db
   * @param $data
   * @param $user
   * @return bool
   */
  public function saveReminder($data, $user = []) {
    $config = \Drupal::config('twilio.settings');
    
    // Create reminder node
    $node = Node::create([
      'type' => 'reminder',
      'title' => sprintf('%s on %s for %s', $data['flight'], $data['flight_date'], $data['phone_number']),
      'langcode' => \Drupal::service('language_manager')->getCurrentLanguage()->getId(),
      'uid' => '1',
      'status' => 0,
      'field_phone_number' => $data['phone_number'],
      'field_flight' => trim($data['flight']),
      'field_flight_date' => trim($data['flight_date']),
      'field_flight_airline' => trim($data['flight_airline']),
      'field_flight_type' => trim($data['flight_type']),
    ]);

    $node->save();

    // Send message via Twilio
    $client = new Services_Twilio($config->get('twilio.sid'), $config->get('twilio.token'));
    
    try {
      if(!empty($user)){
        $user = User::load($user->id());
        
        $body = \Drupal::translation()->translate("@user vous a abonné aux SMS pour recevoir les mises à jour du vol @flight du @flight_date. Pour arrêter de recevoir les mises à jour, répondez « Fin ». Veuillez prendre note que des frais standard de messagerie texte et de transfert de données peuvent s'appliquer.", [
            '@user' => $user->field_first_name->value . ' ' . $user->field_last_name->value,
            '@flight' => $data['flight'],
            '@flight_date' => $data['flight_date'],
        ]);
      }else {
        $body = \Drupal::translation()->translate("Vous recevrez les mises à jour du vol @flight du @flight_date. Pour arrêter de recevoir les mises à jour, répondez « Fin ». Veuillez prendre note que des frais standard de messagerie texte et de transfert de données peuvent s'appliquer.", [
            '@flight' => $data['flight'],
            '@flight_date' => $data['flight_date'],
        ]);
      }
      
      $message = $client->account->messages->create([
          "From" => $config->get('twilio.number'),
          "To" => $data['phone_number'],
          "Body" => $body
      ]);

     return true;
    } catch (Services_Twilio_RestException $e) {
      return false;
    }
  }

  /**
   * Send Push notifications to correct OS
   * @param \Drupal\user\Entity\User $user
   * @param $msg
   * @param $data
   */
  public function sendPushNotifications($user, $msg, $data = []){
    $config = \Drupal::config('yqb_api.settings');
    
    /**
     * Send Android notification
     */
    if(!empty($user->field_push_android_token->value)){
      $client = new Client();
      $client->setApiKey($config->get('yqb_api.android_key'));
      $client->injectHttpClient(new \GuzzleHttp\Client());
      
      $message = new Message();
      $message->addRecipient(new Device($user->field_push_android_token->value));
      
      if(!empty($msg)) {
        $note = new Notification(null, $msg);
        $note->setSound('default');
        $message->setNotification($note);
      }
      
      if(!empty($data)) {
        $message->setData($data);
      }
      
      $this->log(\GuzzleHttp\json_encode($message), 'android');
      $response = $client->send($message);
      $this->log(sprintf("Response Code => %s", $response->getStatusCode()), 'android');
    }
  
    /**
     * Send iOS notification
     */
    
    if(!empty($user->field_push_ios_token->value)){
      $push = new ApnsPHP_Push(
        $config->get('yqb_api.ios_environment'),
        './tools/development/' . $config->get('yqb_api.ios_certificate'),
        ApnsPHP_Abstract::PROTOCOL_HTTP
      );
      
      $push->setRootCertificationAuthority('./tools/development/entrust_root_certification_authority.pem');
      $push->connect();
      
      /**
      * Prepare message
      */
      $message = new ApnsPHP_Message($user->field_push_ios_token->value);
      
      $message->setContentAvailable(true);
      $message->setCustomIdentifier("{$user->id()}-{$data['user_flight_id']}-update");
      $message->setTopic('com.aeroportdequebec.yqb');
      
      if(!empty($msg)) {
        $message->setSound();
        $message->setBadge(1);
        $message->setText($msg);
        
        // Add message in data
        $message->setCustomProperty('message', $msg);
      }else{
        $message->setBadge(0);
      }
      
      foreach($data as $k => $v){
        $message->setCustomProperty($k, $v);
      }
      

      /**
       * Send message
       */
      $push->add($message);
      $push->send();
      $push->disconnect();
      
      $this->log(\GuzzleHttp\json_encode($message->getPayload()), 'ios');
      
      // Examine the error message container
      $aErrorQueue = $push->getErrors();
      if (!empty($aErrorQueue)) {
        $this->log(\GuzzleHttp\json_encode($aErrorQueue), 'ios');
      }
    }
  }

  /**
   * Returns the status message to send via push depending on status and state of flight
   * @param $status
   * @param $state
   * @param $userFlight
   * @param $language
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getStatusMessage($status, $state, $userFlight, $language){
    switch($status){
      case 8: // (Arrived)
        if($state == 'travel') {
          $msg = '';
        }else{
          $msg = \Drupal::translation()->translate("Le vol @flightNumber est arrivé à Québec.", ['@flightNumber' => $userFlight->field_flight_number->value], ['langcode' => $language]);
        }
      break;
      case 10: // (Departed)
        if($state == 'travel') {
          $msg = '';
        }else{
          $msg = \Drupal::translation()->translate("Le vol @flightNumber est décollé.", ['@flightNumber' => $userFlight->field_flight_number->value], ['langcode' => $language]);
        }
      break;
      case 11: // (Delayed)
        if($state == 'travel') {
          $msg = \Drupal::translation()->translate("Votre vol est retardé. L'heure de départ est maintenant à @time. Contactez votre compagnie aérienne pour plus d'informations.",
            ['@time' => $userFlight->field_flight_time->value], 
            ['langcode' => $language]
          );
        }else{
          $msg = \Drupal::translation()->translate("Le vol @flightNumber est retardé. L'heure de départ est maintenant à @time.", 
            ['@flightNumber' => $userFlight->field_flight_number->value, '@time' => $userFlight->field_flight_time->value],
            ['langcode' => $language]
          );
        }
      break;
      case 12: // (Canceled)
        if($state == 'travel') {
          $msg = \Drupal::translation()->translate("Votre vol est annulé. Contactez votre compagnie aérienne pour avoir plus d'informations.", [], ['langcode' => $language]);
        }else{
          $msg = \Drupal::translation()->translate("Le vol @flightNumber est annulé. Contactez la compagnie aérienne pour avoir plus d'informations.", 
            ['@flightNumber' => $userFlight->field_flight_number->value], 
            ['langcode' => $language]
          );
        }
      break;
      case 13: // (Early)
        if($state == 'travel') {
          $msg = \Drupal::translation()->translate("Votre vol est devancé. L'heure de départ est maintenant à @time. Contactez votre compagnie aérienne pour plus d'informations.", 
            ['@time' => $userFlight->field_flight_time->value], 
            ['langcode' => $language]
          );
        }else{
          $msg = \Drupal::translation()->translate("Le vol @flightNumber est devancé. L'heure de départ est maintenant à @time.", 
            ['@flightNumber' => $userFlight->field_flight_number->value, '@time' => $userFlight->field_flight_time->value],
            ['langcode' => $language]
          );
        }
      break;
      // Gate has changed
      case 44: // (Gate changed)
        if($state == 'travel') {
          $msg = \Drupal::translation()->translate("Changement: Votre numéro de porte est maintenant @gate", ['@gate' => $userFlight->field_gate->value], ['langcode' => $language]);
        }else{
          $msg = '';
        }
      break;
      
      case 9:
      default: // (On time)
        if($state == 'travel') {
          $msg = \Drupal::translation()->translate("Bonne nouvelle! Votre vol est à l'heure.", [], ['langcode' => $language]);
        }else{
          $msg = \Drupal::translation()->translate("Le vol @flightNumber est à l'heure.", ['@flightNumber' => $userFlight->field_flight_number->value], ['langcode' => $language]);
        }
      break;
    }
    
    return $msg;
  }

  /**
   * Format and validate number via Twilio Lookup Services
   * @param $phoneNumber
   * @return mixed
   */
  public function formatPhoneNumber($phoneNumber){
    $config = \Drupal::config('twilio.settings');
    
    $phoneNumber = preg_replace('/[^0-9\.]+/','', $phoneNumber);
    
    if(!empty($phoneNumber)) {
      try {
        $client = new Lookups_Services_Twilio($config->get('twilio.sid'), $config->get('twilio.token'));
        $number = $client->phone_numbers->get($phoneNumber);
        $phoneNumber = $number->phone_number;
      } catch (Services_Twilio_RestException $e) {
        $phoneNumber = false;
      }
    }
      
    return $phoneNumber;
  }
  
  private function log($data, $filename){
      if (!file_put_contents(sprintf('public://api/%s.log', $filename), date('Y-m-d H:i:s').' - '.$data."\r\n", FILE_APPEND)) {
        echo "Couldn't log file" . PHP_EOL;
      }
    }
}

?>