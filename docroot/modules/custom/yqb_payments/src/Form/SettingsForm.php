<?php

namespace Drupal\yqb_payments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase
{
  public function getFormId()
  {
    return 'yqb_payments_settings_form';
  }

  protected function getEditableConfigNames()
  {
    return [
      'yqb_payments.settings'
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('yqb_payments.settings');
    $form = parent::buildForm($form, $form_state);

    $form['recipients'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipients'),
      '#default_value' => $config->get('recipients'),
      '#description' => $this->t('Emails separated by commas.'),
    ];

    $form['minimum_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum amount'),
      '#description' => 'The minimum amount help preventing fraud.',
      '#default_value' => $config->get('minimum_amount'),
      '#step' => 0.01,
      '#min' => 0
    ];

    $form['form_is_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide and disable pay bills form'),
      '#default_value' => $config->get('form_is_enabled'),
      '#return_value' => 1
    ];

    $form['recaptcha_sitekey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('reCAPTCHA Site Key'),
      '#default_value' => $config->get('recaptcha_sitekey'),
      '#required' => TRUE
    ];

    $form['recaptcha_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('reCAPTCHA Secret Key'),
      '#default_value' => $config->get('recaptcha_secret'),
      '#required' => TRUE
    ];

    $form['payment_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment Page'),
      '#description' => $this->t('Provide the relative URL without the host / domain name.'),
      '#default_value' => $config->get('payment_page'),
      '#required' => TRUE
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitForm($form, $form_state);
    $this->config('yqb_payments.settings')
      ->set('recipients', $form_state->getValue('recipients'))
      ->set('minimum_amount', $form_state->getValue('minimum_amount'))
      ->set('form_is_enabled', $form_state->getValue('form_is_enabled'))
      ->set('recaptcha_sitekey', $form_state->getValue('recaptcha_sitekey'))
      ->set('recaptcha_secret', $form_state->getValue('recaptcha_secret'))
      ->set('payment_page', $form_state->getValue('payment_page'))
      ->save();
  }
}
