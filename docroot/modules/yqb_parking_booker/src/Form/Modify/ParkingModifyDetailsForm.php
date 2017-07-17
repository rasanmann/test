<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form\Modify;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\moneris\Connector\MonerisConnector;
use Drupal\node\Entity\Node;
use Drupal\yqb_parking_booker\Form\ParkingFormBase;
use Endroid\QrCode\QrCode;

class ParkingModifyDetailsForm extends ParkingFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'parking_booker_modify_details_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Results weren't stored, user probably accessed this page directly, redirect to homepage
        if (!$this->store->get('current_booking')) {
            drupal_set_message($this->t("Aucune réservation correspondant aux informations fournies."), 'error');
            return $this->redirect(sprintf('yqb_parking_booker.%s.modify.index', \Drupal::languageManager()->getCurrentLanguage()->getId()));
        }

        $form = parent::buildForm($form, $form_state);

        $currentBooking = $this->store->get('current_booking');

        // Make sure we have to most up to date info
        $currentBooking = $this->advam->getBooking($currentBooking->guid)->booking;

        $form['title'] = [
            '#type' => 'html_tag',
            '#tag' => 'h1',
            '#attributes' => ['class' => ['title-page']],
            '#value' => \Drupal::service('title_resolver')->getTitle(\Drupal::request(), \Drupal::routeMatch()->getRouteObject())
        ];

        $form['status'] = $this->generateStatusBar([
            "Référence de réservation" => $currentBooking->reference,
            "Date d'arrivée" => date('Y-m-d H:i', strtotime($currentBooking->arrivalDate . ' ' . $currentBooking->arrivalTime)),
            "Date de sortie" => date('Y-m-d H:i', strtotime($currentBooking->departureDate . ' ' . $currentBooking->departureTime)),
        ], sprintf('yqb_parking_booker.%s.modify.results', \Drupal::languageManager()->getCurrentLanguage()->getId()));

        $email = [
            '#type' => 'email',
            '#title' => $this->t("Courriel"),
            '#default_value' => $currentBooking->email,
            '#required' => true,
        ];

        $firstName = [
            '#type' => 'textfield',
            '#title' => $this->t("Prénom"),
            '#default_value' => $currentBooking->firstName,
            '#required' => true,
        ];

        $lastName = [
            '#type' => 'textfield',
            '#title' => $this->t("Nom"),
            '#default_value' => $currentBooking->lastName,
            '#required' => true,
        ];

        $postalCode = [
            '#type' => 'textfield',
            '#title' => $this->t("Code postal"),
            '#default_value' => $currentBooking->postcode,
            '#required' => true,
        ];

        $city = [
            '#type' => 'textfield',
            '#title' => $this->t("Ville"),
            '#default_value' => $currentBooking->town,
            '#required' => true,
        ];

        $address = [
            '#type' => 'textfield',
            '#title' => $this->t("Adresse"),
            '#default_value' => $currentBooking->addressLine1,
            '#required' => true,
        ];

        $phoneNumber = [
            '#type' => 'textfield',
            '#title' => $this->t("Téléphone"),
            '#default_value' => $currentBooking->phone,
            '#required' => true,
        ];

        $actions = $form['actions'];
        unset($form['actions']);

        $container = [
            '#type' => 'container',
            '#cache' => ['max-age' => 0],
            '#attributes' => ['class' => ['row', 'parking-content']],

            'row' => [
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
                            '#attributes' => ['class' => ['col-sm-6']],
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
        $booking = $this->store->get('current_booking');

        $result = $this->advam->updateBooking($booking->guid, [
            'firstName' => $form_state->getValue('first_name'),
            'lastName' => $form_state->getValue('last_name'),
            'email' => $form_state->getValue('email'),
            'phone' => $form_state->getValue('phone_number'),
            'addressLine1' => $form_state->getValue('address'),
            'town' => $form_state->getValue('city'),
            'postcode' => $form_state->getValue('postal_code'),
        ]);
      
        // Update parking_booking
        $nodes = \Drupal::entityQuery('node')
            ->condition('type', 'parking_booking')
            ->condition('field_advam_guid', $booking->guid)
            ->pager(1)
            ->execute();
        
        if(!empty($nodes)) {
          $booking = Node::load(current($nodes));

          $booking->set('field_first_name', $form_state->getValue('first_name'));
          $booking->set('field_last_name', $form_state->getValue('last_name'));
          $booking->set('field_email', $form_state->getValue('email'));
          $booking->set('field_address', $form_state->getValue('address'));
          $booking->set('field_city', $form_state->getValue('city'));
          $booking->set('field_postal_code', $form_state->getValue('postal_code'));
          $booking->set('field_phone_number', $form_state->getValue('phone_number'));
          $booking->save();
        }
      
        if (!$result) {
            $form_state->setError($form, $this->t("Une erreur est survenue durant les modifications. Veuillez rééssayer."));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      drupal_set_message($this->t("Les modifications ont été apportées avec succès."));

      $route = sprintf('yqb_parking_booker.%s.modify.results', \Drupal::languageManager()->getCurrentLanguage()->getId());

      $this->parkingRedirect($form_state, $route);
    }
}

?>