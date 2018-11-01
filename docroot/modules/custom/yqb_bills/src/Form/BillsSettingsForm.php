<?php

/**
 * @file
 * Contains \Drupal\yqb_bills\Form\BillsSettingsForm.
 */

namespace Drupal\yqb_bills\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class BillsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'bills_settings_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('yqb_bills.settings');

    // Page title field
    $form['recipients'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipients:'),
      '#default_value' => $config->get('yqb_bills.recipients'),
      '#description' => $this->t('Emails separated by commas.'),
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
    $config = $this->config('yqb_bills.settings');

    $config->set('yqb_bills.recipients', $form_state->getValue('recipients'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
        'yqb_bills.settings',
    ];
  }
}