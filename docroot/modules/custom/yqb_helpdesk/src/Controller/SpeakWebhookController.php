<?php
/**
 * @file
 * Contains \Drupal\yqb_helpdesk\Controller\SpeakWebhookController.
 */

namespace Drupal\yqb_helpdesk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Services_Twilio;
use Services_Twilio_RestException;

/**
 * Provides route responses for the Reminders module.
 */
class SpeakWebhookController extends ControllerBase {
  
  private $helpdeskPhoneNumber;
  
  public function __construct() {
    $config = \Drupal::config('yqb_helpdesk.settings');
    $this->helpdeskPhoneNumber = $config->get('yqb_helpdesk.phone_number');
  }

  /**
   * Receives Twilio incoming Call and gather caller id
   */
  public function call(){
    header("content-type: text/xml");
    
    echo '<?xml version="1.0" encoding="UTF-8"?>
    <Response>
      <Gather action="'.Url::fromRoute('yqb_helpdesk.enqueue', [], ['absolute' => true])->toString().'" numDigits="8"> </Gather>
      <Dial>'.$this->helpdeskPhoneNumber.'</Dial>
    </Response>';
    
    exit;
  }

  /**
   * Fetch the user based on gathered numbers and enqueue user
   * Calls the agent with user data
   */
  public function enqueue(){
    // Get user by caller id
    $user = [
      'id' => 0,
      'logDate' => date('Y-m-d H:i:s'),
      'first_name' => '',
      'last_name' => '',
      'language' => 'fr',
      'langcode' => 'fr-CA'
    ];
    
    if(isset($_REQUEST['Digits'])){
      $query = \Drupal::entityQuery('user')->condition('field_caller_id', $_REQUEST['Digits'])->pager(1);
      $results = $query->execute();
      
      if(!empty($results)) {
        $dbUser = User::load(current($results));

        $user['id'] = $dbUser->id();
        $user['first_name'] = (!empty($dbUser->field_uuid->value)) ? '' : $dbUser->field_first_name->value;
        $user['last_name'] = (!empty($dbUser->field_uuid->value)) ? '' : $dbUser->field_last_name->value;
        if(!empty($dbUser->field_language->value)){
          $user['language'] = $dbUser->field_language->value;
          $user['langcode'] = $user['language'].'-CA';
        }
      }
    }
    
    // Log user
    $this->logAndClean(json_encode($user, JSON_PRETTY_PRINT), $user['id'].'_'.date('Y_m_d__H_i_s', strtotime($user['logDate'])).'_enqueue', 'call');
    \Drupal::logger('yqb_helpdesk')->notice(sprintf('Enqueue User %d (%s %s) on %s', $user['id'], $user['first_name'], $user['last_name'], $user['logDate']));
    
    // Prepare message
    $say = $this->t("Bonjour, un agent vous répondra sous peu.", 
      [],
      ['langcode' => $user['language']]
    );
    
    // Helpdesk queue
    $workflowSid = "WWf4281d21d79d13a36b351bb7548504dd";
    
    // Enqueue caller
    header("content-type: text/xml");
    echo '<?xml version="1.0" encoding="UTF-8"?>
    <Response>
        <Say language="'.$user['langcode'].'" voice="alice">'.$say.'</Say>
        
        <Enqueue workflowSid="'.$workflowSid.'">
          <Task>'.json_encode($user).'</Task>
        </Enqueue>
    </Response>';
    
    exit;
  }
  
  public function connect(){
    header("content-type: application/json");
    
    $params = json_decode($_REQUEST['TaskAttributes'], true);
    $params['ReservationSid'] = $_REQUEST['ReservationSid'];
    $params['IfMachine'] = 'Continue';
    
    echo json_encode([
      "instruction" =>  "call",
      "from" => "+18885128763",
      "url" =>  Url::fromRoute('yqb_helpdesk.callback', $params, ['absolute' => true])->toString()
    ]);
    
    exit;
  }

  /**
   * Prompts the agent with client info and asks him to answer the call
   */
  public function callback(){
    $this->logAndClean(json_encode($_GET, JSON_PRETTY_PRINT), $_GET['id'].'_'.date('Y_m_d__H_i_s', strtotime($_GET['logDate'])).'_callback', 'call');
    \Drupal::logger('yqb_helpdesk')->notice(sprintf('Agent receives User %d (%s %s) on %s', $_GET['id'], $_GET['first_name'], $_GET['last_name'], $_GET['logDate']));
    
    if($_GET['id'] != 0){
      $lang = ($_GET['language'] == 'fr') ? 'français' : 'anglais';
      if(!empty($_GET['first_name'])) {
        $message = "Vous avez un appel de l'utilisateur {$_GET['first_name']} {$_GET['last_name']}. Son numéro est ".implode(' ', str_split($_GET['id']))." et son application est en {$lang}.";
      }else{
         $message = "Vous avez un appel de l'utilisateur ".implode(' ', str_split($_GET['id']))." et son application est en {$lang}."; 
      }
    }else{
      $message = "Vous avez un appel d'un utilisateur inconnu.";
    }

    header("content-type: text/xml");
    echo '<?xml version="1.0" encoding="UTF-8"?>
    <Response>
      <Gather action="'.Url::fromRoute('yqb_helpdesk.gather', ['ReservationSid' => $_GET['ReservationSid']], ['absolute' => true])->toString().'" numDigits="1">
        <Say language="fr-CA" voice="alice">'.$message.'</Say>
        <Say language="fr-CA" voice="alice">Pour accepter l\'appel, appuyez sur le 1</Say>
        <Say language="fr-CA" voice="alice">Pour rejeter l\'appel, appuyez sur une autre touche.</Say>
      </Gather>
      <Say language="fr-CA" voice="alice">Désolé, je n\'ai pas reçu votre réponse.</Say>
      <Redirect>'.htmlspecialchars(Url::fromRoute('yqb_helpdesk.callback', $_GET, ['absolute' => true])->toString()).'</Redirect>
    </Response>';
    
    exit;
  }
  
  
  
  /**
   * Check if agent pushed right button and connect to client
   */
  public function gather(){
    $user_pushed = (int) $_REQUEST['Digits'];
    
    if ($user_pushed == 1) {
      $action = '<Say language="fr-CA" voice="alice">Connexion au client</Say>
        <Dial>
             <Queue reservationSid="'.$_GET['ReservationSid'].'" postWorkActivitySid="WAce608701c8be2b5f02c3c9ff12d12161"/>
        </Dial>';
    }else {
      $action = "<Hangup />";
    }
    
    // Dial agent
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <Response>
        {$action}
    </Response>";
    
    exit;
  }
  
  
  /************************************************************************************************************************* */
  
  
  
  /**
   * Log data into files and trigger cleaning process
   * @param $data
   * @param $filename
   * @param $folder
   * @param int $limit
   */
  private function logAndClean($data, $filename, $folder, $limit = 50){
    // Log outgoing events and add message to log
    if (!is_dir('public://webhooks/'.$folder)) {
      mkdir('public://webhooks/'.$folder);
    }
    
    if(!empty($folder)) $folder .= '/';
    
    // Get all log files and order by most recent
    $realPublicPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $files = glob($realPublicPath . '/webhooks/'.$folder.'*.log');
    
    $this->cleanLogFiles($files, $limit);
    
    if (!file_put_contents(sprintf('public://webhooks/'.$folder.'%s.log', $filename), $data)) {
      echo "Couldn't log file" . PHP_EOL;
    }
  }

  /**
   * Clear oldest files 
   * @param $files
   * @param int $limit
   */
  private function cleanLogFiles($files, $limit = 10){
    usort($files, create_function('$a, $b', 'return filemtime($a) < filemtime($b);'));
      
    // Clean up, keep most recent files
    if (count($files) > $limit) {
        $deletes = array_slice($files, $limit - 1);
        foreach ($deletes as $delete) {
            @unlink($delete);
        }
    }
  }
}