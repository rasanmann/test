<?php

/**
 * @file
 * Contains \Drupal\data_exchange_layer\Form\DataExchangeLayerForm.
 */

namespace Drupal\data_exchange_layer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DataExchangeLayerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'data_exchange_layer_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('data_exchange_layer.settings');

    $form['auth_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auth URL:'),
      '#default_value' => $config->get('data_exchange_layer.auth_url'),
      '#description' => $this->t('DataExchangeLayer auth URL.'),
    ];

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL:'),
      '#default_value' => $config->get('data_exchange_layer.api_url'),
      '#description' => $this->t('DataExchangeLayer API URL.'),
    ];

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail:'),
      '#default_value' => $config->get('data_exchange_layer.email'),
      '#description' => $this->t('DataExchangeLayer e-mail.'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key:'),
      '#default_value' => $config->get('data_exchange_layer.api_key'),
      '#description' => $this->t('DataExchangeLayer API key.'),
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
    $config = $this->config('data_exchange_layer.settings');
    $config->set('data_exchange_layer.auth_url', $form_state->getValue('auth_url'));
    $config->set('data_exchange_layer.api_url', $form_state->getValue('api_url'));
    $config->set('data_exchange_layer.email', $form_state->getValue('email'));
    $config->set('data_exchange_layer.api_key', $form_state->getValue('api_key'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'data_exchange_layer.settings',
    ];
  }
}