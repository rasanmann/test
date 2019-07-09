<?php

namespace Drupal\yqb_alert\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

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

    $config = \Drupal::config('yqb_alert.settings');



    $form['french_alert'] = [
      '#type' => 'textarea',
      '#title' => $this->t('French Alert'),
      '#placeholder' => "Entrer le text de l'alerte ici",
      '#description' => 'description'
    ];

    $form['english_alert'] = [
      '#type' => 'textarea',
      '#title' => $this->t('English Alert'),
      '#placeholder' => "Enter the text for the alert",
      '#description' => 'description'
    ];

    $form['alert_is_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cocher pour afficher l\'alerte'),
      '#default_value' => $config->get('yqb_alert.alert_is_enabled'),
//      '#return_value' => 1
    ];


    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    ksm($config->get('yqb_alert.alert_is_enabled'));



    return $form;

  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = \Drupal::config('yqb_alert.settings');
//    $config->get('alert_is_enabled');
//    $config = \Drupal::service('yqb_alert.settings')->getEditable('yqb_alert.alert_is_enabled');




//    $config->set('yqb_alert.alert_is_enabled', $form_state->getValue('alert_is_enabled'));
//    $config->save();

//    drupal_set_message("allo");
  }


  protected function getEditableConfigNames() {
    return [
      'yqb_alert.settings',
    ];
  }


}
