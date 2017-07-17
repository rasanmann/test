<?php

/**
 * @file
 * Contains \Drupal\twilio\Form\TwilioForm.
 */

namespace Drupal\twilio\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TwilioForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'twilio_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('twilio.settings');

    // Page title field
    $form['sid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SID:'),
      '#default_value' => $config->get('twilio.sid'),
      '#description' => $this->t('Twilio SID.'),
    ];
    
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token:'),
      '#default_value' => $config->get('twilio.token'),
      '#description' => $this->t('Twilio token.'),
    ];

    $form['number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number:'),
      '#default_value' => $config->get('twilio.number'),
      '#description' => $this->t('Twilio phone number.'),
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
    $config = $this->config('twilio.settings');
    $config->set('twilio.sid', $form_state->getValue('sid'));
    $config->set('twilio.token', $form_state->getValue('token'));
    $config->set('twilio.number', $form_state->getValue('number'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'twilio.settings',
    ];
  }
}