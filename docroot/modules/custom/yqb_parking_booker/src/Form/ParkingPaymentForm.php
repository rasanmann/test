<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingPaymentForm.
 */

namespace Drupal\yqb_parking_booker\Form;

use Drupal\Core\Form\FormStateInterface;

class ParkingPaymentForm extends ParkingFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parking_booker_payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['header'] = $this->generateHeader();
    $form['footer'] = $this->generateFooter();
    
    $userId = ($this->store->get('user_id')) ? $this->store->get('user_id') : \Drupal::currentUser()->id();
    
    $currentUser = \Drupal\user\Entity\User::load($userId);
    
    $isAnon = ($currentUser->get('field_uuid') && !empty($currentUser->get('field_uuid')->value));
    
    // Set default user information if user is connected or not
    $userInformation = [
      'firstName' =>    ($currentUser->get('field_first_name') && !$isAnon) ? $currentUser->get('field_first_name')->value : null,
      'lastName' =>     ($currentUser->get('field_last_name') && !$isAnon) ? $currentUser->get('field_last_name')->value : null,
      'email' =>        ($currentUser->getEmail() && !$isAnon) ? $currentUser->getEmail() : null,
      'address' =>      ($currentUser->get('field_address') && !$isAnon) ? $currentUser->get('field_address')->value : null,
      'city' =>         ($currentUser->get('field_city') && !$isAnon) ? $currentUser->get('field_city')->value : null,
      'postalCode' =>   ($currentUser->get('field_postal_code') && !$isAnon) ? $currentUser->get('field_postal_code')->value : null,
      'phoneNumber' =>  ($currentUser->get('field_phone_number') && !$isAnon) ? $currentUser->get('field_phone_number')->value : null,
    ];

    // If we're changing an existing booking, use this information instead
    if ($this->store->get('current_booking')) {
      $userInformation['firstName'] = $this->store->get('current_booking')->firstName;
      $userInformation['lastName'] = $this->store->get('current_booking')->lastName;
      $userInformation['email'] = $this->store->get('current_booking')->email;
      $userInformation['address'] = $this->store->get('current_booking')->addressLine1;
      $userInformation['city'] = $this->store->get('current_booking')->town;
      $userInformation['postalCode'] = $this->store->get('current_booking')->postcode;
      $userInformation['phoneNumber'] = $this->store->get('current_booking')->phone;
    }

    $titleInfo = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t("Informations personnelles"),
    ];

    $firstName = [
      '#type' => 'textfield',
      '#title' => $this->t("Prénom"),
      '#placeholder' => $this->t('Ex : Michel'),
      '#default_value' => $userInformation['firstName'],
      '#required' => TRUE,
    ];

    $lastName = [
      '#type' => 'textfield',
      '#title' => $this->t("Nom de famille"),
      '#placeholder' => $this->t('Ex : Roy'),
      '#default_value' => $userInformation['lastName'],
      '#required' => TRUE,
    ];

    $email = [
      '#type' => 'email',
      '#title' => $this->t("Courriel"),
      '#placeholder' => $this->t('Ex : monnom@moncourriel.com'),
      '#default_value' => $userInformation['email'],
      '#required' => TRUE,
    ];

    $address = [
      '#type' => 'textfield',
      '#title' => $this->t("Adresse"),
      '#placeholder' => $this->t('Ex : 500, rue Principale'),
      '#default_value' => $userInformation['address'],
      '#required' => TRUE,
    ];

    $city = [
      '#type' => 'textfield',
      '#title' => $this->t("Ville"),
      '#placeholder' => $this->t('Ex : Québec'),
      '#default_value' => $userInformation['city'],
      '#required' => TRUE,
    ];

    $postalCode = [
      '#type' => 'textfield',
      '#title' => $this->t("Code postal"),
      '#placeholder' => $this->t('Ex : G2G 2T9'),
      '#default_value' => $userInformation['postalCode'],
      '#required' => TRUE,
    ];

    $phoneNumber = [
      '#type' => 'textfield',
      '#title' => $this->t("Téléphone"),
      '#placeholder' => $this->t('Ex : 418 640-3300'),
      '#default_value' => $userInformation['phoneNumber'],
      '#required' => TRUE,
    ];

    $form['actions']['submit']['#value'] = $this->t('Réviser et confirmer');

    $actions = $form['actions'];
    unset($form['actions']);

    $container = [
      '#type' => 'container',
      '#cache' => ['max-age' => 0],
      '#attributes' => ['class' => ['row', 'parking-content']],

      'row-1' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],
        'col-1' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-sm-6']],
          'title' => $titleInfo,
        ],
      ],

      'row-2' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],

        'col-1' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-sm-6']],

            'first_name' => $firstName,
            'last_name' => $lastName,
          'email' => $email,
        ],
        'col-2' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-sm-6']], 
            'phone_number' => $phoneNumber,
            'address' => $address,
          'container-1' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['row', 'form-group-collapse']],
              'col-1-1' => [
                  '#type' => 'container',
                  '#attributes' => ['class' => ['col-sm-6']],
                  'city' => $city,
              ],

              'col-1-2' => [
                  '#type' => 'container',
                  '#attributes' => ['class' => ['col-sm-6', 'with-margin']],
                  'postal_code' => $postalCode,
              ],
          ],

          'actions' => $actions,
        ]
      ]
    ];
    
    $form['container'] = $container;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $initialization = $this->advam->initializeBookingConfirmation($this->store->get('booking')->guid);

    if ($initialization === false) {
      $form_state->setErrorByName('submit', $this->t('An error occured during the booking initialization. Please try again.'));
    } else {
      $this->store->set('initialization', $initialization);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('first_name', $form_state->getValue('first_name'));
    $this->store->set('last_name', $form_state->getValue('last_name'));
    $this->store->set('email', $form_state->getValue('email'));
    $this->store->set('address', $form_state->getValue('address'));
    $this->store->set('city', $form_state->getValue('city'));
    $this->store->set('postal_code', $form_state->getValue('postal_code'));
    $this->store->set('phone', $form_state->getValue('phone_number'));
    
    $route = sprintf('yqb_parking_booker.%s.review', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $this->parkingRedirect($form_state, $route);
  }
}

?>