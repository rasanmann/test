<?php
namespace Drupal\yqb_newsletters\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\webformSubmissionInterface;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "yqb_newsletters",
 *   label = @Translation("Newsletter subscription"),
 *   category = @Translation("Form Handler"),
 *   description = @Translation("Administers subscriptions in Mailchimp"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */

class yqb_newsletters extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  // @TODO : 
  // Get Tags dynamically in form
  const MAILCHIMP_API_KEY = 'f75ab6d01bdb8708fb2b15c269bc8328-us11'; // see https://mailchimp.com/help/about-api-keys
  const LIST_ID = 'fbcc0156a1'; // see https://3by400.com/get-support/3by400-knowledgebase?view=kb&kbartid=6
  const SERVER_LOCATION = 'us11'; // the string after the '-' in your MAILCHIMP_API_KEY f.e. us4

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    $values = $webform_submission->getData();
    $email = strtolower($values['email']);
    $first_name = $values['first_name'];
    $last_name = $values['last_name'];

    // The data to send to the API
    $postData = array(
      "email_address" => "$email",
      "status" => "subscribed",
      "merge_fields" => array(
        "FNAME" => "$first_name",
        "LNAME" => "$last_name"
      ),
      "tags" => array('Air Canada')
    );

    // Setup cURL
    // To get the correct dataserver, see the url of your mailchimp back-end, mine is https://us20.admin.mailchimp.com/account/api/
    $ch = curl_init('https://'.self::SERVER_LOCATION.'.api.mailchimp.com/3.0/lists/'.self::LIST_ID.'/members/');
    curl_setopt_array($ch, array(
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Authorization: apikey '.self::MAILCHIMP_API_KEY,
        'Content-Type: application/json'
      ),
      CURLOPT_POSTFIELDS => json_encode($postData)
    ));

    // Send the request
    $response = curl_exec($ch);
    $readable_response = json_decode($response);
    if(!$readable_response) {
      \Drupal::logger('Mailchimp_subscriber')->error($readable_response->title.': '.$readable_response->detail .'. Raw values:'.print_r($values));
      \Drupal::messenger()->addError(t('Un problème est survenu. Veuillez contacter votre webmaster.'));
    }
    if($readable_response->status == 403) {
      \Drupal::logger('Mailchimp_subscriber')->error($readable_response->title.': '.$readable_response->detail .'. Raw values:'.print_r($values));
      \Drupal::messenger()->addError(t('Un problème est survenu. Veuillez contacter votre webmaster.'));
    }
    if($readable_response->status == 'subscribed') {
      \Drupal::messenger()->addStatus(t('Vous êtes maintenant inscrit avec succès.'));
    }
    if($readable_response->status == 400) {
      if($readable_response->title == 'Member Exists') {
        \Drupal::messenger()->addWarning(t('Vous êtes déjà inscrit à cette liste de diffusion.'));
      }
    }

    return true;
  }
}
