<?php

/**
 * @file
 * Contains \Drupal\advam\Form\AdvamForm.
 */

namespace Drupal\yqb_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'yqb_api_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('yqb_api.settings');

   
    $form['key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Android API key'),
        '#default_value' => $config->get('yqb_api.android_key'),
        '#description' => $this->t('Console Firebase > Gear > Paramètres du projet > Cloud Messaging : Jeton Firebase Cloud Messaging'),
    ];
    
    $form['env'] = [
      '#type' => 'select',
      '#title' => $this->t('iOS Environment'),
      '#options' => [
        '0' => $this->t('Production'),
        '1' => $this->t('Sandbox'),
      ],
      '#default_value' => $config->get('yqb_api.ios_environment'),
    ];
    
    $form['certificate'] = [
        '#type' => 'textfield',
        '#title' => $this->t('iOS Certificate name'),
        '#default_value' => $config->get('yqb_api.ios_certificate'),
        '#description' => $this->t('complete_filename.pem'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('yqb_api.settings');
    $config->set('yqb_api.android_key', $form_state->getValue('key'));
    $config->set('yqb_api.ios_environment', $form_state->getValue('env'));
    $config->set('yqb_api.ios_certificate', $form_state->getValue('certificate'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'yqb_api.settings',
    ];
  }
}