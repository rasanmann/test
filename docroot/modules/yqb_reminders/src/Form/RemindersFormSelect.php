<?php
/**
 * @file
 * Contains \Drupal\yqb_flight_planner\Form\FlightPlannerForm.
 */

namespace Drupal\yqb_reminders\Form;

use Drupal\Core\Form\FormStateInterface;

use Drupal\node\Entity\Node;
use Drupal\yqb_reminders\general\Reminders;

class RemindersFormSelect extends RemindersFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reminders_form_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $useFullWidth = ($form_state->get('full_width'));
    
    $userId = (isset($_GET['user_id'])) ? $_GET['user_id'] : ((\Drupal::currentUser()) ? \Drupal::currentUser()->id() : null);

      // Find user flights
    $query = \Drupal::entityQuery('node')
          ->condition('type', 'user_flight')
          ->sort('field_flight_date', 'ASC')
          ->sort('field_flight_time', 'ASC')
          ->condition('field_user', $userId)
          ->condition('field_state', 'travel')
          ->condition('field_archived', false);

    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);

    $userFlights = [];
    foreach($nodes as $userFlight){
      if($userFlight->field_flight_type->value == 'departure'){
        $destinationNode = $userFlight->field_destination_airport->first()->get('entity')->getTarget()->getValue();
        
        $title = $this->t("Québec > @destination - @date", [
          '@destination' => $destinationNode->field_city->value,
          '@date' => $userFlight->field_flight_date->value
        ]);
      }else{
        $originNode = $userFlight->field_origin_airport->first()->get('entity')->getTarget()->getValue();
        
        $title = $this->t("@origin > Québec - @date", [
          '@origin' => $originNode->field_city->value,
          '@date' => $userFlight->field_flight_date->value
        ]);
      }
      
      $userFlights[$userFlight->nid->value] = $title;
    }

    $phoneNumber = [
        '#type' => 'tel',
        '#title' => $this->t("Numéro de téléphone"),
        '#placeholder' => '418 555-5255',
        '#attributes' => [
            'maxlength' => 20,
            'type' => 'telephone'
        ],
        '#required' => TRUE,
    ];

    $userFlights = [
        '#type' => 'select',
        '#title' => $this->t("Vol"),
        '#options' => $userFlights,
        '#attributes' => [
            'class' => ['required'],
            'maxlength' => 20,
        ],
        '#required' => TRUE,
    ];

    $form = [
      // Row
      '#type' => 'container',
      '#attributes' => ['class' => ['form-block-container', ($useFullWidth) ? 'form-block-container-full' : null]],

      // Phone number
      'col-phone' => [
        '#type' => 'container',
        'phone_number' => $phoneNumber
      ],

      // Flight number
      'col-flight-number' => [
        '#type' => 'container',
        'user_flight' => $userFlights
      ],

      // Submit button
      'col-submit' => [
        '#type' => 'container',
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Envoyer'),
            '#attributes' => ['class' => ['btn-default']],
            '#button_type' => 'default',
            '#weight' => 10,
          ]
        ]
      ],
    ];

    $this->deleteStore();

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
    $Reminders = new Reminders();
    
    // Fetch user flight
    $userFlight = Node::load($form_state->getValue('user_flight'));
    
    // Format + validate phone number via Twilio API
    $airlineNode = $userFlight->field_airline->first()->get('entity')->getTarget()->getValue();
    
    $data = [];
    $data['phone_number'] = $Reminders->formatPhoneNumber($form_state->getValue('phone_number'));
    $data['flight'] = $userFlight->field_flight_number->value;
    $data['flight_date'] = $userFlight->field_flight_date->value;
    $data['flight_airline'] = ($airlineNode->field_icao->value === 'JZA' || $airlineNode->field_icao->value === 'QZ') ? 'ACA' : $airlineNode->field_icao->value;
    $data['flight_type'] = $userFlight->field_flight_type->value;
    
    if($data['phone_number'] !== false) {
      $route = sprintf('yqb_reminders.%s.confirmation', \Drupal::languageManager()->getCurrentLanguage()->getId());
      
      if($Reminders->saveReminder($data)){
        $form_state->setRedirect($route, array(), array('query' => array('confirm' => 1, 'webview' => 1)));
      }else{
        $form_state->setRedirect($route, array(), array('query' => array('confirm' => 0, 'webview' => 1)));
      }
    }else{
      drupal_set_message($this->t("Le numéro de téléphone entré n'est pas valide."), 'error');
    }
  }
}

?>