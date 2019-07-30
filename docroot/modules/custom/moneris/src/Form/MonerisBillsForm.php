<?php

/**
 * @file
 * Contains \Drupal\moneris\Form\MonerisForm.
 */

namespace Drupal\moneris\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MonerisBillsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'moneris_bills_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('moneris.payment_settings');

    // Page title field
    $form['store_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store ID:'),
      '#default_value' => $config->get('moneris.store_id'),
      '#description' => $this->t('Store ID.'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key:'),
      '#default_value' => $config->get('moneris.api_key'),
      '#description' => $this->t('API key.'),
    ];

    $form['api_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('API url:'),
        '#default_value' => $config->get('moneris.api_url'),
        '#description' => $this->t('Moneris API url.'),
    ];

    $form['profile_id'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Profile ID:'),
      '#default_value' => $config->get('moneris.profile_id'),
      '#description' => $this->t('Moneris profile ID for hosted tokenization. Enter domains on a seperate line using this pattern `url: profileId`'),
    ];

    $form['environment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment:'),
      '#default_value' => $config->get('moneris.environment'),
      '#description' => $this->t('Moneris environment.'),
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
    $config = $this->config('moneris.payment_settings');

    $config->set('moneris.store_id',    $form_state->getValue('store_id'));
    $config->set('moneris.api_key',     $form_state->getValue('api_key'));
    $config->set('moneris.api_url',  $form_state->getValue('api_url'));
    $config->set('moneris.profile_id',  $form_state->getValue('profile_id'));
    $config->set('moneris.environment',  $form_state->getValue('environment'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'moneris.payment_settings',
    ];
  }
}
