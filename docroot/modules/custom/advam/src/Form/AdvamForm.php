<?php

/**
 * @file
 * Contains \Drupal\advam\Form\AdvamForm.
 */

namespace Drupal\advam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AdvamForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'advam_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('advam.settings');

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL:'),
      '#default_value' => $config->get('advam.api_url'),
      '#description' => $this->t('Advam API URL.'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key:'),
      '#default_value' => $config->get('advam.api_key'),
      '#description' => $this->t('Advam API key.'),
    ];

    $form['airport_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Airport code:'),
      '#default_value' => $config->get('advam.airport_code'),
      '#description' => $this->t('Advam airport code.'),
    ];

    $form['use_legacy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use legacy system:'),
      '#default_value' => $config->get('advam.use_legacy'),
      '#description' => $this->t('Check if you want to use legacy booking system (reservation.aeroportdequebec.com).'),
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
    $config = $this->config('advam.settings');
    $config->set('advam.api_url', $form_state->getValue('api_url'));
    $config->set('advam.api_key', $form_state->getValue('api_key'));
    $config->set('advam.airport_code', $form_state->getValue('airport_code'));
    $config->set('advam.use_legacy', $form_state->getValue('use_legacy'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'advam.settings',
    ];
  }
}