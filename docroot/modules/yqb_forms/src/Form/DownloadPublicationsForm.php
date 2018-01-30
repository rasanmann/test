<?php
/**
 * @file
 * Contains \Drupal\yukon_forms\Form\NewsletterForm.
 */

namespace Drupal\yqb_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Contribute form.
 */
class DownloadPublicationsForm extends FormBase {
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yqb_forms_download_publication';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currentUser = User::load(\Drupal::currentUser()->id());

    $form['body'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['modal-body']]
    ];

    $form['footer'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['modal-footer']]
    ];

    $form['body']['first_name'] = [
        '#type' => 'textfield',
        '#title' => t('Prénom'),
        '#required' => TRUE,
        '#default_value' => ($currentUser->isAuthenticated()) ? $currentUser->field_first_name->value : ''
    ];

    $form['body']['last_name'] = [
        '#type' => 'textfield',
        '#title' => t('Nom'),
        '#required' => TRUE,
        '#default_value' => ($currentUser->isAuthenticated()) ? $currentUser->field_last_name->value : ''
    ];

    $form['body']['email'] = [
        '#type' => 'textfield',
        '#title' => t('Courriel'),
        '#required' => TRUE,
        '#default_value' => ($currentUser->isAuthenticated()) ? $currentUser->getEmail(): ''
    ];

    $form['body']['company'] = [
        '#type' => 'textfield',
        '#title' => t('Compagnie'),
        '#required' => TRUE,
    ];

    $form['footer']['submit'] = [
        '#type' => 'submit',
        '#attributes' => ['class' => ['btn-primary']],
        '#value' => t('Télécharger'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Send email
    //    $type = $form_state->getValue('type');
    //    $globalConfig = \Drupal::config('yukon_settings.email_settings')->get('global');
    //    
    //    if(isset($globalConfig[$langcode]['contact_us'][$type])){
    //      $to = $globalConfig[$langcode]['contact_us'][$type];
    //      
    //      $message = sprintf("<p>From: %s (%s)</p>", $form_state->getValue('name'), $form_state->getValue('email'));
    //      
    //      $params = [
    //        'subject' => sprintf("Contact Us - %s", $this->inquiryTypes[$type]),
    //        'message' => $message . nl2br($form_state->getValue('comment')),
    //        'from' => sprintf("%s <%s>", $form_state->getValue('name'), $form_state->getValue('email'))
    //      ];
    //      
    //      $mailManager = \Drupal::service('plugin.manager.mail');
    //      $mailManager->mail( 'yukon_settings', 'contact-us', $to, $langcode, $params, NULL, true);
    //      
    //      drupal_set_message(t('contact-us-success'));
    //    }else{
    //      \Drupal::logger('contact-us')->error("Error sending email to " . $type);
    //      $form_state->setError($form, t('contact-us-error'));
    //    }
    //    
    //    $form_state->setRedirect('entity.node.canonical', ['node' => 70]);
  }
}