<?php

/**
 * @file
 * Contains \Drupal\twilio\Form\TwilioForm.
 */

namespace Drupal\yqb_reminders\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class RemindersAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'reminders_admin_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('yqb_reminders.settings');

    $form['blocked_flights'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Blocked flights:'),
      '#placeholder' => $this->t('5696, 394, 1023'),
      '#default_value' => $config->get('yqb_reminders.blocked_flights'),
      '#description' => $this->t("Stop sending reminders to the following flight numbers. Split with commas."),
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
    $config = $this->config('yqb_reminders.settings');
    $config->set('yqb_reminders.blocked_flights', $form_state->getValue('blocked_flights'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'yqb_reminders.settings',
    ];
  }
}