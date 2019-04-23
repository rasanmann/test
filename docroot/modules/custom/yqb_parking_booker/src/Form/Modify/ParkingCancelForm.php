<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form\Modify;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\moneris\Connector\MonerisConnector;
use Drupal\yqb_parking_booker\Form\ParkingFormBase;
use Endroid\QrCode\QrCode;

class ParkingCancelForm extends ParkingFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'parking_booker_cancel_form';
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
        $form['#attributes']['class'][] = 'form-tables';

        $currentBooking = $this->store->get('current_booking');

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

        $cancellation = $this->advam->getBookingCancellation($currentBooking->guid);

        $this->store->set('cancellation', $cancellation);

        $titleOrder = [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => $this->t("Détails de la commande")
        ];

        $order = [
            '#type' => 'container',
            '#theme' => 'table',
            '#responsive' => false,
            '#cache' => ['max-age' => 0],
            '#attributes' => ['class' => ['table', 'table-normal']],
            '#header' => [
                $this->t('Produit'),
                $this->t('Prix'),
                $this->t("Frais d'annulation"),
            ],
            '#rows' => [],
            '#empty' => $this->t('Aucun résultat.')
        ];

        foreach($cancellation->cancellationItems as $category => $cancellationItems) {
            if (!count($cancellationItems)) {
                continue;
            }

            foreach($cancellationItems as $cancellationItem) {
                $info = ['#type' => 'container'];
                $price = $cancellationItem->amount;
                $fee = $cancellationItem->cancellationFees;
                $name = [
                    '#type' => 'html_tag',
                    '#tag' => 'p',
                    '#weight' => -1,
                    '#value' => $this->t($category)
                ];

                if (isset($cancellationItem->productId) && $cancellationItem->productId) {
                    $product = $this->advam->getProduct($cancellationItem->productId);
                    $name['#value'] = '<strong>'. $product->name . '</strong>';
                } else if (isset($cancellationItem->extraId) && $cancellationItem->extraId) {
                    $extra = $this->advam->getExtra($cancellationItem->extraId);
                    $name['#value'] = '<strong>'. $extra->name . '</strong>';

                    if ($cancellationItem->offerLines) {
                        foreach($cancellationItem->offerLines as $offerLine) {
                            $quantity = 1;

                            foreach($currentBooking->items->extras as $bookingExtra) {
                                if ($bookingExtra->extraId === $cancellationItem->extraId) {
                                    foreach($bookingExtra->offerLines as $bookingExtraOfferLine) {
                                        if ($offerLine->offerLineId === $bookingExtraOfferLine->extraOfferLineId) {
                                            $quantity = $bookingExtraOfferLine->quantity;
                                            break;
                                        }
                                    }
                                }
                            }

                            foreach($extra->offerLines as $extraOfferLine) {
                                if ($offerLine->offerLineId === $extraOfferLine->id) {
                                    $info[] = [
                                        '#type' => 'html_tag',
                                        '#tag' => 'p',
                                        '#value' => $quantity . ' x ' . strip_tags($extraOfferLine->description)
                                    ];
                                    break;
                                }
                            }
                        }
                    }
                }

                $info[] = $name;

                if (!$cancellationItem->canCancel) {
                    $info[] = [
                        '#type' => 'html_tag',
                        '#tag' => 'p',
                        '#attributes' => ['class' => ['text-warning']],
                        '#value' => '<span class="glyphicon glyphicon-warning-sign"></span> ' . $this->t("Les Termes et Conditions qui régissent ces éléments font qu'ils ne sont pas remboursables.")
                    ];
                }

                $row = $this->generateRow($info, $price, $fee);

                $order['#rows'][] = $row;
            }
        }

        $titleSummary = [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => $this->t("Détails de l'annulation")
        ];

        $feeInformation = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t("L'annulation de la réservation implique un frais de service de @fee.", ['@fee' => $this->moneyFormat($cancellation->cancellationFee->totalCancellationFee)])
        ];

        $totalInformation = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t("Le montant de @amount sera remboursé sur la carte utilisée pour faire le paiement initial.", ['@amount' => $this->moneyFormat($cancellation->cancellationFee->totalRefundAmount)])
        ];

        $summary = [
            '#type' => 'container',
            '#theme' => 'table',
            '#responsive' => false,
            '#cache' => ['max-age' => 0],
            '#attributes' => ['class' => ['table', 'table-normal']],
            '#header' => [
                $this->t('Détail'),
                $this->t('Montant'),
            ],
            '#rows' => [],
            '#empty' => $this->t('Aucun résultat.')
        ];

        $summary['#rows'][] = [
            ['data' => ['#markup' => '<strong>' . $this->t("Coût des éléments annulés") . '</strong>']],
            ['data' => ['#markup' => $this->moneyFormat($cancellation->cancellationFee->totalCancellableAmount)]],
        ];

        $summary['#rows'][] = [
            ['data' => ['#markup' => '<strong>' . $this->t("Frais total d'annulation") . '</strong>']],
            ['data' => ['#markup' => $this->moneyFormat($cancellation->cancellationFee->totalCancellationFee)]],
        ];

        $summary['#rows'][] = [
            ['data' => ['#markup' => '<strong>' . $this->t("Montant total du remboursement") . '</strong>']],
            ['data' => ['#markup' => $this->moneyFormat($cancellation->cancellationFee->totalRefundAmount)]],
        ];

        $form['actions']['submit']['#value'] = $this->t('Annuler maintenant');
        $form['actions']['submit']['#attributes']['class'][] = 'btn-danger';

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
                    'title' => $titleOrder,
                    'order' => $order
                ],
                'col-2' => [
                    '#type' => 'container',
                    '#attributes' => ['class' => ['col-sm-6']],
                    'title' => $titleSummary,
                    'summary' => $summary,
                    'fee' => $feeInformation,
                    'total' => $totalInformation,
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
        $cancellation = $this->store->get('cancellation');
        $booking = $cancellation->booking;
        $payment = reset($booking->payments);

        $moneris = new MonerisConnector();

        // Refund the money first
        $result = $moneris->refund($payment->transactionCode, $payment->paymentReference, $cancellation->cancellationFee->totalRefundAmount);

        // Check if refund was successful
        if (!$result->was_successful()) {
            $form_state->setErrorByName('submit', $this->t("Une erreur est survenue durant le remboursement de la réservation. Veuillez rééssayer."));
        } else {
            // If refund was successful, cancel the booking
            $result = $this->advam->cancelBooking($booking->guid);

            // Delete node
            $toDelete = \Drupal::entityQuery('node')
                ->condition('type', 'parking_booking')
                ->condition('field_advam_reference', $cancellation->booking->reference)
                ->pager(1)
                ->execute();

            if(!empty($toDelete)) {
              $node_storage = \Drupal::entityTypeManager()->getStorage('node');
              $nodes = $node_storage->loadMultiple($toDelete);

              if (!empty($nodes)) {
                $node_storage->delete($nodes);
              }
            }

            if (!$result) {
                drupal_set_message($this->t("Une erreur est survenue durant l'annulation de la réservation, mais le remboursement a été effectué."), 'warning');
            } else {
                // If cancellation was successful, confirm the refund was successful
                $result = $this->advam->confirmCancellationRefund($booking->guid);

                if (!$result) {
                    drupal_set_message($this->t("Une erreur est survenue durant la confirmation de remboursement de la réservation, mais le remboursement a été effectué."), 'warning');
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      $route = sprintf('yqb_parking_booker.%s.modify.cancel_confirmation', \Drupal::languageManager()->getCurrentLanguage()->getId());

      $this->parkingRedirect($form_state, $route);
    }

    protected function generateRow($info, $price, $fee) {
        return [
            'class' => '',
            'data' => [
                'info' => [
                    'class' => '',
                    'data' => [$info]
                ],
                'price' => [
                    'class' => '',
                    'data' => [
                        '#markup' => $this->moneyFormat($price)
                    ]
                ],
                'fee' => [
                    'class' => '',
                    'data' => [
                        '#markup' => $this->moneyFormat($fee)
                    ]
                ],
            ],
        ];
    }
}

?>
