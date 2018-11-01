<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Endroid\QrCode\QrCode;

class ParkingResultsForm extends ParkingFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'parking_booker_results_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Results weren't stored, user probably accessed this page directly, redirect to homepage
        if (!$this->store->get('arrival_date') || !$this->store->get('departure_date')) {
            drupal_set_message($this->t("Une erreur est survenue durant la confirmation de la réservation. Veuillez rééssayer."), 'error');
            return $this->redirect('page_manager.page_view_parking_booking_panels');
        }

        $form = parent::buildForm($form, $form_state);
        $form['#attributes']['class'][] = 'parking-booker-form-responsive';

        // Keep actions in DOM but hide them, weird bug
        $form['actions']['#attributes']['class'][] = 'hidden';

        $form['header'] = $this->generateHeader();
        $form['footer'] = $this->generateFooter();

        $form = array_merge($form, $this->generateProductResults($this->advam->getProducts(), $this->store->get('arrival_date'), $this->store->get('departure_date'), $this->store->get('promo_code')));

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
//    $productId = $form_state->getTriggeringElement()['#attributes']['data-product-id'];
        $productId = $_POST['product_id'];

        $result = $this->advam->createBooking([
            'promotionCode' => $this->store->get('promo_code'),
            'parking' => [
                'productId' => $productId,
                'arrivalDate' => date('Y-m-d', strtotime($this->store->get('arrival_date'))),
                'arrivalTime' => date('H:i', strtotime($this->store->get('arrival_date'))),
                'departureDate' => date('Y-m-d', strtotime($this->store->get('departure_date'))),
                'departureTime' => date('H:i', strtotime($this->store->get('departure_date')))
            ],
        ]);

        if (!$result) {
            $form_state->setError($form, $this->advam->errors());
            $form_state->setError($form, $this->t("Le stationnement n'est pas disponible pour les dates sélectionnées."));
        } else {
            $this->store->set('product_id', $productId);
            $this->store->set('booking', $result->booking);

            // Promo code was entered but is not valid
            if ($this->store->get('promo_code') && !$this->store->get('booking')->validPromotionCode) {
                $this->store->delete('promo_code');
                drupal_set_message("Le code promotionnel n'est pas valide.", 'error');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Default route, go to extra
        $route = sprintf('yqb_parking_booker.%s.extras', \Drupal::languageManager()->getCurrentLanguage()->getId());

        if ($this->store->get('current_booking')) {
            // Go straight to payment if we have an active current booking
            $route = sprintf('yqb_parking_booker.%s.payment', \Drupal::languageManager()->getCurrentLanguage()->getId());

            // TODO : test with extras in booking
            if ($this->store->get('current_booking')->price === $this->store->get('booking')->price) {
                // No need to refund anything, just do the modification

                // Modify existing booking
                $modification = $this->advam->modifyBooking($this->store->get('current_booking')->guid, [
                    'productId' => $this->store->get('product_id'),
                    'arrivalDate' => date('Y-m-d', strtotime($this->store->get('arrival_date'))),
                    'arrivalTime' => date('H:i', strtotime($this->store->get('arrival_date'))),
                    'departureDate' => date('Y-m-d', strtotime($this->store->get('departure_date'))),
                    'departureTime' => date('H:i', strtotime($this->store->get('departure_date'))),
                    'forceModify' => true
                ]);

                if ($modification) {
                    // Booking was moi
                    $this->store->set('modification', $modification);

                    $confirmationData = [
                        'guid' => $modification->booking->guid,
                        'email' => $this->store->get('current_booking')->email,
                        'firstName' => $this->store->get('current_booking')->firstName,
                        'lastName' => $this->store->get('current_booking')->lastName,
                        'addressLine1' => $this->store->get('current_booking')->addressLine1,
                        'town' => $this->store->get('current_booking')->town,
                        'postcode' => $this->store->get('current_booking')->postcode,
                        'isReceiveMarketing' => $this->store->get('current_booking')->isReceiveMarketing,

                        "paymentProvider" => $this->store->get('current_booking')->payments[0]->paymentProvider,
                        "authCode" => $this->store->get('current_booking')->payments[0]->authCode,
                        "authMessage" => $this->store->get('current_booking')->payments[0]->authMessage,
                        "responseCode" => $this->store->get('current_booking')->payments[0]->responseCode,
                        "transactionCode" => $this->store->get('current_booking')->payments[0]->transactionCode,
                        "transactionRef" => $this->store->get('current_booking')->payments[0]->transactionRef,
                        "paymentReference" => $this->store->get('current_booking')->payments[0]->paymentReference,
                        "cardType" => $this->store->get('current_booking')->payments[0]->cardType,
                        "cardNumber" => $this->store->get('current_booking')->payments[0]->maskedPan,
                        "cardExpiryDate" => $this->store->get('current_booking')->payments[0]->cardExpiryDate,
                        "paymentValue" => $this->store->get('current_booking')->payments[0]->amount,
                    ];

                    $confirmation = $this->advam->confirmBooking($confirmationData);

                    if ($confirmation) {
                      $this->store->set('confirmation', $confirmation);
                      $this->store->set('processed_amount', 0);

                      // Update parking_booking
                      $this->updateBookingReservation($modification);

                      $route = sprintf('yqb_parking_booker.%s.confirmation', \Drupal::languageManager()->getCurrentLanguage()->getId());
                    } else {
                        drupal_set_message($this->t("Une erreur est survenue durant la modification de la réservation. Veuillez rééssayer."), 'error');
                    }

                } else {
                    drupal_set_message($this->t("Une erreur est survenue durant la modification de la réservation. Veuillez rééssayer."), 'error');
                }
            }
        }

        $this->parkingRedirect($form_state, $route);
    }
}

?>
