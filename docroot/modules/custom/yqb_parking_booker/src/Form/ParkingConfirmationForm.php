<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form;

use Drupal\Core\Form\FormStateInterface;
use Endroid\QrCode\QrCode;
use DateTime;
use DateTimeZone;

class ParkingConfirmationForm extends ParkingFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parking_booker_confirmation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->store->delete('multistep_data');
    $this->store->delete('current_booking');
    $this->store->delete('initialization');
    $this->store->delete('transaction_processed');
    $this->store->delete('confirmation_booking_data');
    $this->store->delete('purchase_data');
    $this->store->delete('booking_guid');
    $this->store->delete('reference_number');
    $this->store->delete('process_started');

    // Confirmation wasn't stored, user probably accessed this page directly, redirect to homepage
    if (!$this->store->get('confirmation')) {
      drupal_set_message($this->t("Une erreur est survenue durant la confirmation de la réservation. Veuillez rééssayer."), 'error');
      return $this->redirect('page_manager.page_view_parking_booking_panels');
    }

    $form = parent::buildForm($form, $form_state);

    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

    unset($form['actions']);

    $form['header'] = $this->generateHeader();

    $titleConfirmation = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t("Merci de votre commande @first_name", ['@first_name'=> $this->store->get('confirmation')->booking->firstName]),
    ];

    $emailConfirmation = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t("Nous vous acheminons un courriel de confirmation sous peu à l'adresse @email.", ['@email'=> $this->store->get('confirmation')->booking->email]),
    ];

    $userTimeZone = ($currentUser->getTimeZone()) ? $currentUser->getTimeZone() : 'America/Toronto';

    $tz = new DateTimeZone($userTimeZone);
    $confirmationDate = new DateTime($this->store->get('confirmation')->booking->bookingDate, new DateTimeZone('UTC'));
    $confirmationDate->setTimezone($tz);

    // Message when there is a refund
    $refundMessage = ($this->store->get('refund')) ? $paymentConfirmation = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t("Le montant de @amount de votre première réservation vous a été remboursé.", [
            '@amount'=> $this->moneyFormat($this->store->get('refund'))
        ]),
    ] : null;

    $paymentConfirmation = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t("Un paiement de @amount a été appliqué sur votre carte le @date à @time.", [
            '@amount'=> $this->moneyFormat($this->store->get('processed_amount')),
            '@date'=> $confirmationDate->format('Y-m-d'),
            // TODO Convert time to user timezone
            '@time'=> $confirmationDate->format('H:i'),
        ]),
    ];

    $modificationSummary = property_exists($this->store->get('confirmation'), 'modificationSummary') ? $this->store->get('confirmation')->modificationSummary : null;

    $details = $this->generateBookingDetailsConfirmation($this->store->get('confirmation')->booking);
    $order = $this->generateBookingOrderConfirmation($this->store->get('confirmation')->booking, $modificationSummary);

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
          'title' => $titleConfirmation,
          'email' => $emailConfirmation,
          'refund' => $refundMessage,
          'payment' => $paymentConfirmation,
        ]
      ],

      'row-2' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],

        'col-1' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-sm-6']],
          'order' => $order,
        ],

        'col-2' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-sm-6']],
          'details' => $details,
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

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}

?>
