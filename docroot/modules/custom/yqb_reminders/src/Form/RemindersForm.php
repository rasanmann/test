<?php
/**
 * @file
 * Contains \Drupal\yqb_flight_planner\Form\FlightPlannerForm.
 */

namespace Drupal\yqb_reminders\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity;

use Drupal\node\Entity\Node;
use Drupal\yqb_reminders\general\Reminders;
use Services_Twilio;
use Services_Twilio_RestException;
use Lookups_Services_Twilio;

class RemindersForm extends RemindersFormBase {
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
    $useFullWidth = ($form_state->get('full_width'));

      // Find airline with corresponding code
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'airline')
        ->condition('status', 1)
        ->sort('title')
    ;

    $nids = $query->execute();

    $nodes = Node::loadMultiple($nids);

    $airlines = [];

    foreach($nodes as $nodeId => $node) {
      if (!$node->get('field_hidden')->value) {
        $airlines[$node->get('field_icao')->value] = $node->getTitle();
      }
    }

    $autoCompleteData = $this->buildAutoCompleteData();

    $phoneNumber = [
        '#type' => 'textfield',
        '#title' => $this->t("Numéro de téléphone"),
        '#placeholder' => '418 555-5255',
        '#attributes' => [
            'pattern' => '\d*',
            'maxlength' => 20,
        ],
        '#required' => TRUE,
    ];

    $flightNumber = [
        '#type' => 'textfield',
        '#title' => $this->t("Numéro de vol"),
        '#placeholder' => '2185',
        '#attributes' => [
            'data-autocomplete' => json_encode($autoCompleteData),
            'class' => ['required','validNumber'],
            'maxlength' => 20,
        ],
        '#required' => TRUE,
    ];

    $flightAirline = [
        '#type' => 'select',
        '#title' => $this->t("Compagnie aérienne"),
        '#options' => $airlines,
        '#attributes' => [
            'class' => ['required'],
            'maxlength' => 20,
        ],
        '#required' => TRUE,
    ];

    $flightType = [
        '#type' => 'select',
        '#title' => $this->t("Type"),
        '#options' => [
            'departure' => $this->t("Départ"),
            'arrival' => $this->t("Arrivée"),
        ],
        '#attributes' => [
            'class' => ['required'],
            'maxlength' => 20,
        ],
        '#required' => TRUE,
    ];

    $flightDateDesktop = [
        '#type' => 'textfield',
        '#title' => $this->t("Date du vol"),
        '#placeholder' => date('Y-m-d'),
        '#attributes' => [
            'class' => ['required','validUpcomingDate'],
            'autocomplete' => 'off',
            'readonly' => 'true',
            'data-toggle' => 'datepicker',
            'maxlength' => 20,
        ],
        '#required' => TRUE,
    ];

    $flightDateMobile = [
        '#type' => 'date',
        '#title' => $this->t("Date du vol"),
        '#value' => date('Y-m-d'),
        '#placeholder' => date('Y-m-d'),
        '#attributes' => [
            'type' => 'date',
            'autocomplete' => 'off',
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
        '#attributes' => ['class' => ['col-normal', 'col-tel']],
        'phone_number' => $phoneNumber
      ],

      // Flight number
      'col-flight-number' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal', 'col-flight-number']],
        'flight_number' => $flightNumber
      ],

      // Flight date desktop
      'col-flight-date-desktop' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal', 'col-flight-date', 'is-desktop']],
        'flight_date' => $flightDateDesktop
      ],

      // Flight date mobile
      'col-flight-date-mobile' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal', 'col-flight-date', 'is-mobile']],
        'flight_date' => $flightDateMobile
      ],

      // Flight airline
        'col-flight-airline' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['col-normal', 'col-flight-airline']],
            'flight_airline' => $flightAirline
        ],

      // Flight type
        'col-flight-type' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['col-normal', 'col-flight-type']],
            'flight_type' => $flightType
        ],

      // Submit button
      'col-submit' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['btn-space']],
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
    parse_str(\Drupal::request()->getQueryString(), $params);

    $Reminders = new Reminders();
    
    // Format + validate phone number via Twilio API
    $data = [];
    $data['phone_number'] = $Reminders->formatPhoneNumber($form_state->getValue('phone_number'));
    $data['flight'] = $form_state->getValue('flight_number');
    $data['flight_date'] = $form_state->getValue('flight_date');
    $data['flight_airline'] = $form_state->getValue('flight_airline');
    $data['flight_type'] = $form_state->getValue('flight_type');
    
    if($data['phone_number'] !== false) {
      $route = sprintf('yqb_reminders.%s.confirmation', \Drupal::languageManager()->getCurrentLanguage()->getId());
      
      if($Reminders->saveReminder($data)){
        $form_state->setRedirect($route, array(), array('query' => array_merge($params, array('confirm' => 1))));
      }else{
        $form_state->setRedirect($route, array(), array('query' => array_merge($params, array('confirm' => 0))));
      }
    }else{
      drupal_set_message($this->t("Le numéro de téléphone entré n'est pas valide."), 'error');
    }
  }

  /**
   * @return array
   */
  protected function buildAutoCompleteData() {
    $data = [
      'arrival' => [],
      'departure' => [],
    ];

    foreach($data as $contentType => $item) {
      $query = \Drupal::entityQuery('node')
          ->condition('type', $contentType)
          ->condition('status', 1);

      $nids = $query->execute();

      $nodes = Node::loadMultiple($nids);

      foreach($nodes as $nodeId => $node) {
        $airlineIcao = null;

        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $airline */
        $airline = $node->get('field_airline');

        if ($airline->count() && $airline->first()->get('entity')->getTarget()) {
          /** @var \Drupal\node\Entity\Node $airlineNode */
          $airlineNode = $airline->first()->get('entity')->getTarget()->getValue();
          if ($airlineNode->hasField('field_icao')) {
            $airlineIcao = $airlineNode->get('field_icao')->value;
          }
        }

        if ($airlineIcao) {
          if ($airlineIcao === 'JZA' || $airlineIcao === 'QZ') {
            $airlineIcao = 'ACA';
          }

          $data[$contentType][] = [
            'flightNumber' => $node->get('field_flight_number')->value,
            'icao' => $airlineIcao
          ];
        }
      }
    }

    return $data;
  }
}

?>