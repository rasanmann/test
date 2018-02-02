<?php
/**
 * @file
 * Contains \Drupal\yukon_forms\Form\NewsletterForm.
 */

namespace Drupal\yqb_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
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

    $form['body']['target_id'] = [
        '#type' => 'hidden'
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
    if(empty($form_state->getValue('target_id'))){
      $form_state->setError($form, t('Une erreur est survenue durant la soumission du formulaire.'));
    }
        
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save user
    $connection = \Drupal::database();
    $connection->query("
        INSERT INTO yqb_download_queries 
        ( 
          file_id, 
          first_name, 
          last_name, 
          email, 
          company, 
          date
        )
        VALUES (
          :file_id, 
          :first_name, 
          :last_name, 
          :email, 
          :company, 
          :date
        )
    ", [
        ':file_id' => $form_state->getValue('target_id'),
        ':first_name' => $form_state->getValue('first_name'),
        ':last_name' => $form_state->getValue('last_name'),
        ':email' => $form_state->getValue('email'),
        ':company' => $form_state->getValue('company'),
        ':date' => date('Y-m-d H:i:s'),
    ]);
    
    // Prepare file
    $file = File::load($form_state->getValue('target_id'));
    $path = \Drupal::service('file_system')->realpath($file->getFileUri());
    
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . $file->getSize());
    header("Content-Transfer-Encoding: Binary"); 
    header("Content-disposition: attachment; filename=\"" . $file->getFilename() . "\"");
    readfile($path);
    
    die();
  }
}