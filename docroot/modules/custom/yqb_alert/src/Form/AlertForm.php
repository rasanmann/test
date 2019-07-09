<?php

namespace Drupal\yqb_alert\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AlertForm extends FormBase {

  /**
   * @return string
   */
  public function getFormId(){
    return 'alert_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|void
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // TODO: Implement buildForm() method.

    $form['french_alert'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First number'),
    ];

    $form['english_alert'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second number'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
    ];

    return $form;

  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    drupal_set_message("it works!");
  }


}
