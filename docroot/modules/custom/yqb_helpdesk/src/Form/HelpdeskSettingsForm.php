<?php

/**
 * @file
 * Contains \Drupal\advam\Form\AdvamForm.
 */

namespace Drupal\yqb_helpdesk\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class HelpdeskSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'yqb_helpdesk_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('yqb_helpdesk.settings');

   
    $form['phone_number'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Helpdesk phone number'),
        '#default_value' => $config->get('yqb_helpdesk.phone_number'),
        '#description' => $this->t('The phone number that will be called when someone asks for help'),
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
    $config = $this->config('yqb_helpdesk.settings');
    $config->set('yqb_helpdesk.phone_number', $form_state->getValue('phone_number'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'yqb_helpdesk.settings',
    ];
  }
}