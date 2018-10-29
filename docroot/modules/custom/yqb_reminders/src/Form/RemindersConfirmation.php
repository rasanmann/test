<?php
/**
 * @file
 * Contains \Drupal\yqb_flight_planner\Form\FlightPlannerForm.
 */

namespace Drupal\yqb_reminders\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Services_Twilio;
use Services_Twilio_AccessToken;
use Services_Twilio_RestException;

class RemindersConfirmation extends RemindersFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reminders_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'form';
    $form['#attributes']['class'][] = 'form-outline';

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2'
    ];
    
    if($_GET['confirm'] == '1'){
      $value = $this->t("Merci de vous être inscrit à l'alerte SMS.");
    }else{
      $config = $this->config('twilio.settings');
      $number = $config->get('twilio.number');
            
      if(preg_match( '/^\+\d(\d{3})(\d{3})(\d{4})$/', $number, $matches)) {
        $number = $matches[1] . '-' .$matches[2] . '-' . $matches[3];
      }
      
      $value = $this->t("Une erreur est survenue. Veuillez vous assurer que vos alertes ne sont pas désactivées en textant « Start » au @number.", ['@number' => $number]);
    }
    
    $form['title']['#value'] = $value;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}

?>