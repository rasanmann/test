<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingReviewForm.
 */

namespace Drupal\yqb_parking_booker\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\moneris\Connector\MonerisConnector;
use Drupal\moneris\Render\MonerisFrameRenderer;
use Drupal\node\Entity\Node;
use Endroid\QrCode\QrCode;
use DateTime;
use DateTimeZone;

class ParkingReviewForm extends ParkingFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parking_booker_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Initialization wasn't stored, user probably accessed this page directly, redirect to homepage
    if (!$this->store->get('initialization')) {
      drupal_set_message($this->t("Une erreur est survenue durant la confirmation de la réservation. Veuillez rééssayer."), 'error');
      return $this->redirect('page_manager.page_view_parking_booking_panels');
    }

    $form = parent::buildForm($form, $form_state);

    $form['header'] = $this->generateHeader();
    $form['footer'] = $this->generateFooter();

    $initialization = $this->store->get('initialization');

    $titlePayment = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t("Votre carte de crédit"),
    ];

    $titleConfirmation = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t("@first_name, il ne vous reste plus qu'à confirmer votre réservation", ['@first_name'=> $this->store->get('first_name')]),
    ];

    $dataKey = [
      '#type' => 'hidden',
      '#title' => $this->t('Data key'),
      '#required' => TRUE,
    ];

    $frameRenderer = new MonerisFrameRenderer([
      'pan_label' => $this->t("Num&eacute;ro de carte")->render(),
      'exp_label' => $this->t("Date d&rsquo;expiration (MMAA)")->render(),
      'cvd_label' => $this->t("CVD")->render(),
    ]);

    $terms = [
      '#type' => 'checkbox',
      '#title' => '',
      '#attributes' => ['data-toggle' => 'switch', 'data-on-text' => '&nbsp;', 'data-off-text' => '&nbsp;'],
      '#required' => true
    ];

    $termsModal = $this->generateModal('terms', $this->t('Conditions'), [
        'info' => [
            '#type' => 'html_tag',
            '#attributes' => ['style' => 'max-height:50vh; overflow:scroll;'],
            '#tag' => 'div',
            '#value' => $this->advam->getTermsAndConditions(reset($initialization->products)->termsAndConditionsId)->termsAndConditons
        ],
    ]);

    $termsLabel = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t("J’accepte <a href='#' data-toggle='modal' data-target='#terms'>les conditions</a> et par la présente je reconnais que mon droit d’annulation cessera d’être applicable dès mon entrée dans le stationnement si l’entrée est faite avant la période d’expiration de 14 jours."),
    ];

    $communications = [
      '#type' => 'checkbox',
      '#title' => '',
      '#attributes' => ['data-toggle' => 'switch', 'data-on-text' => '&nbsp;', 'data-off-text' => '&nbsp;'],
    ];

    $communicationsLabel = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t("J’accepte de recevoir les offres spéciales, nouvelles et informations de l’Aéroport international Jean-Lesage de Québec concernant les événements ou promotions, par la poste, par courriel, par message texte ou par SMS. Vous pouvez révoquer cette autorisation en tout temps en cliquant sur le lien indiqué dans les courriels ou en communiquant avec nous."),
    ];

    yqb_bills_attach_recaptcha($form['actions']);

    $form['actions']['submit']['#value'] = $this->t('Payer et réserver');

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
          '#attributes' => ['class' => ['col-md-6']],

          'title' => $titlePayment,
          'data_key' => $dataKey,
          'moneris_frame' => $frameRenderer->getRenderArray(),
        ],

        'col-2' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-md-6']],

          'title' => $titleConfirmation,

          'termsContainer' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['switch-wrapper']],
              'terms' => $terms,
              'terms_modal' => $termsModal,
              'terms_label' => $termsLabel,
          ],

          'communicationsContainer' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['switch-wrapper']],
              'receive_communication' => $communications,
              'receive_communication_label' => $communicationsLabel,
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
    // Store terms and receive communication
    $this->store->set('terms', $form_state->getValue('terms'));
    $this->store->set('receive_communication', $form_state->getValue('receive_communication'));

    $moneris = new MonerisConnector();

    // New transaction to make
    $transaction = [
        'data_key' => $form_state->getValue('data_key'),
        'order_id' => sprintf('parking_%s_%s', $this->store->get('initialization')->booking->reference, time()),
        'cust_id' => uniqid(), //(\Drupal::currentUser()->id()) ? 'web_' . \Drupal::currentUser()->id() : 'guest_' . uniqid(),
        'amount' => floatval($this->store->get('booking')->price)
    ];

    if ($this->store->get('current_booking')) {
      // We're changing an existing booking

      // Store in variable for easier access
      $currentBooking = $this->store->get('current_booking');
      $cancellation = $this->advam->getBookingCancellation($currentBooking->guid);
      $payment = reset($cancellation->booking->payments);

      // Refund original parking amount
      $refund = $moneris->refund($payment->transactionCode, $payment->paymentReference, $cancellation->cancellationFee->totalRefundAmount);

      if (!$refund->was_successful()) {
        $form_state->setErrorByName('submit', $this->t("Une erreur est survenue durant le remboursement de la réservation. Veuillez rééssayer."));

        return false;
      } else {
        // Refund confirmation
        $refundConfirmation =  $this->advam->confirmModificationRefund($currentBooking->guid, $cancellation->cancellationFee->totalRefundAmount);

        $this->store->set('refund', $cancellation->cancellationFee->totalRefundAmount);

        // Charge new amount from card
        $purchase = $moneris->purchase($transaction);

        if ($purchase->was_successful()) {
          // Modify existing booking
          $modification = $this->advam->modifyBooking($currentBooking->guid, [
              'productId' => $this->store->get('product_id'),
              'arrivalDate' => date('Y-m-d', strtotime($this->store->get('arrival_date'))),
              'arrivalTime' => date('H:i', strtotime($this->store->get('arrival_date'))),
              'departureDate' => date('Y-m-d', strtotime($this->store->get('departure_date'))),
              'departureTime' => date('H:i', strtotime($this->store->get('departure_date')))
          ]);

          if ($modification) {
            // Booking was modified, store guid
            $bookingGuid = $modification->booking->guid;
          } else {
            $form_state->setErrorByName('submit', $this->t("Une erreur est survenue durant le remboursement de la réservation. Veuillez rééssayer."));

            return false;
          }
        } else {
          $form_state->setErrorByName('submit', $this->t("Une erreur est survenue durant le remboursement de la réservation. Veuillez rééssayer."));

          return false;
        }
      }
    } else {
      // Just purchase it
      $purchase = $moneris->purchase($transaction);

      // Store guid
      $bookingGuid = $this->store->get('initialization')->booking->guid;
    }

    if ($purchase->was_successful()) {
      $orderId = (string)$purchase->response()->receipt->ReceiptId;
      $transactionRef = (string)$purchase->reference_number();
      $authCode = (string)$purchase->response()->receipt->AuthCode;
      $authMessage = (string)$purchase->response()->receipt->Message;
      $responseCode = (string)$purchase->response()->receipt->ResponseCode;
      $transactionCode = $purchase->transaction()->number();
      $cardType = (string)$purchase->response()->receipt->CardType;
      $cardExpiryDate = (string)$purchase->response()->receipt->ResolveData->expdate;
      $maskedPan = (string)$purchase->response()->receipt->ResolveData->masked_pan;
      $paymentValue = (float)$purchase->response()->receipt->TransAmount;

      $this->store->set('processed_amount', $paymentValue);

      // Invert exp date posiition
      $cardExpiryDate = substr($cardExpiryDate, 2) . substr($cardExpiryDate, 0, 2);

      // Success
      $this->store->set('transaction', $transaction);
      $this->store->set('reference_number', $transactionRef);

      $confirmationData = [
          'guid' => $bookingGuid,
          'email' => $this->store->get('email'),
          'firstName' => $this->store->get('first_name'),
          'lastName' => $this->store->get('last_name'),
          'addressLine1' => $this->store->get('address'),
          'town' => $this->store->get('city'),
          'postcode' => $this->store->get('postal_code'),
          'phone' => $this->store->get('phone'),
          'isReceiveMarketing' => empty($this->store->get('receive_communication')) ? false : true,

          "paymentProvider" => 'Moneris',
          "authCode" => $authCode,
          "authMessage" => $authMessage,
          "responseCode" => $responseCode,
          "transactionCode" => $transactionCode,
          "transactionRef" => $transactionRef,
          "paymentReference" => $orderId,
          "cardType" => $cardType,
          "cardNumber" => $maskedPan,
          "cardExpiryDate" => $cardExpiryDate,
          "paymentValue" => $paymentValue
      ];

      $confirmation = $this->advam->confirmBooking($confirmationData);


      if ($confirmation === false) {
        // Void transaction immediately
        $void = $moneris->void($purchase->transaction());

        // Log error
        $this->advam->logAndClean(json_encode([
            $purchase->transaction()->number(),
            $purchase->transaction()->order_id(),
            $void->response(),
            $void->response_message()
        ], JSON_PRETTY_PRINT), date('Y-m-d_H-i-s'), 'moneris', 30);

        $form_state->setError($form, $this->advam->errors());
        $form_state->setErrorByName('actions', $this->t("Une erreur est survenue durant la confirmation de la réservation. Veuillez rééssayer."));
      } else {
        // Booking was confirmed
        $this->store->set('confirmation', $confirmation);

        $arrivalDate = new DateTime($this->store->get('arrival_date'), new DateTimeZone("UTC"));
        $departureDate = new DateTime($this->store->get('departure_date'), new DateTimeZone("UTC"));

        if(isset($modification) && !empty($modification)){
          // Update parking_booking
          $this->updateBookingReservation($modification);
        }else {
          // Create parking booking
          $userId = ($this->store->get('user_id')) ? $this->store->get('user_id') : ((\Drupal::currentUser()) ? \Drupal::currentUser()->id() : null);
          $data = [
            'type' => 'parking_booking',
            'title' => 'Parking / ' . $confirmation->booking->reference,
            'field_user' => $userId,
            'field_arrival' => $arrivalDate->format("Y-m-d\TH:i:s"),
            'field_departure' => $departureDate->format("Y-m-d\TH:i:s"),
            'field_first_name' => $this->store->get('first_name'),
            'field_last_name' => $this->store->get('last_name'),
            'field_email' => $this->store->get('email'),
            'field_address' => $this->store->get('address'),
            'field_city' => $this->store->get('city'),
            'field_postal_code' => $this->store->get('postal_code'),
            'field_phone_number' => $this->store->get('phone'),
            'field_receive_communication' => empty($this->store->get('receive_communication')) ? 0 : 1,
            'field_advam_guid' => $confirmation->booking->guid,
            'field_advam_reference' => $confirmation->booking->reference,
            'field_moneris_reference' => $transactionRef
          ];

          if($this->store->get('user_flight')){
            $data['field_user_flight'] = $this->store->get('user_flight');
          }

          // Create parking_booking node
          $node = Node::create($data);
          $node->save();
        }
      }
    } else {
      // Log error
      $this->advam->logAndClean(json_encode([
          $purchase->response(),
          $purchase->response_message(),
          $purchase->error_code(),
          $purchase->error_message()
      ], JSON_PRETTY_PRINT), date('Y-m-d_H-i-s'), 'moneris', 30);

      // Set error
//      $form_state->setError($form, $purchase->response_message());
//      $form_state->setError($form, $purchase->error_code());
//      $form_state->setError($form, $purchase->error_message());
      $form_state->setErrorByName('submit', $this->t("Une erreur est survenue durant le paiement de la réservation. Veuillez rééssayer."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route = sprintf('yqb_parking_booker.%s.confirmation', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $this->parkingRedirect($form_state, $route);
  }
}

?>
