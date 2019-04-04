<?php

namespace Drupal\yqb_parking_booker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Settings extends ConfigFormBase {

  public function getFormId() {
    return 'yqb_parking_booker_settings';
  }

  public function getEditableConfigNames() {
    return [
      'yqb_parking_booker.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('yqb_parking_booker.settings');

    $form['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable parking booker form'),
      '#default_value' => $config->get('disabled'),
    ];

    $form['disabled_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disabled message'),
      '#default_value' => $config->get('disabled_text'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('disabled')) {
      $disabledText = $form_state->getValue('disabled_text');
      if (empty($disabledText)) {
        $form_state->setError($form['disabled_text'], $this->t('The "Disabled Text" field is required if the parking booker is disabled.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('yqb_parking_booker.settings')
         ->set('disabled', $form_state->getValue('disabled'))
         ->set('disabled_text', $form_state->getValue('disabled_text'))
         ->save();
  }
}
