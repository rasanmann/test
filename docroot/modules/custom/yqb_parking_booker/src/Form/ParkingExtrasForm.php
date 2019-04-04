<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingPaymentForm.
 */

namespace Drupal\yqb_parking_booker\Form;

use Drupal\Core\Form\FormStateInterface;

class ParkingExtrasForm extends ParkingFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parking_booker_extras_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Booking weren't stored, user probably accessed this page directly, redirect to homepage
    if (!$this->store->get('booking')) {
      drupal_set_message($this->t("Une erreur est survenue durant la confirmation de la réservation. Veuillez rééssayer."), 'error');
      return $this->redirect('page_manager.page_view_parking_booking_panels');
    }

    $form = parent::buildForm($form, $form_state);

    $form['header'] = $this->generateHeader();
    $form['footer'] = $this->generateFooter();

    $container = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row', 'parking-content']],

      'row' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],
      ]
    ];

    $extras = $this->advam->getExtras();

    $form['results'] = [
      '#type' => 'container',
      '#cache' => ['max-age' => 0],
      '#attributes' => ['class' => ['table-results', 'table-results-extra']]
    ];

    $extraAvailabilities = $this->advam->getExtraAvailability([
      'arrivalDate' => date('Y-m-d', strtotime($this->store->get('arrival_date'))),
      'arrivalTime' => date('H:i', strtotime($this->store->get('arrival_date'))),
      'departureDate' => date('Y-m-d', strtotime($this->store->get('departure_date'))),
      'departureTime' => date('H:i', strtotime($this->store->get('departure_date'))),
    ]);

    if ($extras && count($extras)) {
      foreach ($extras as $key => $extra) {
        if (!$extra->active) {
          continue;
        }

        $extraAvailable = false;

        if ($extraAvailabilities && count($extraAvailabilities->extras)) {
          foreach ($extraAvailabilities->extras as $extraAvailability) {
            if ($extraAvailability->id === $extra->id) {
              $extraAvailable = true;
              break;
            }
          }
        }

        $informationModal = $this->generateModal('information-' . $extra->id, $this->t('Information supplémentaire'), [
          'info' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $extra->longDescription
          ],
        ]);

        $information = [
          '#type' => 'container',
          '#attributes' => ['class' => ['form-group']],
          'informationModal' => $informationModal,
          'information' => [
            '#type' => 'html_tag',
            '#tag' => 'a',
            '#value' => $this->t('<span class="icon icon-info"></span><span></span>Information supplémentaire'),
            '#attributes' => [
              'href' => '#',
              'data-toggle' => 'modal',
              'data-target' => '#information-' . $extra->id,
            ]
          ],
        ];

        $inputs = [
          '#type' => 'container',
          '#attributes' => ['class' => ['row']],
        ];

        if ($extraAvailable === true) {
          $extrasData = [];
          foreach ($extra->offerLines as $offerLine) {
            if ($offerLine->active) {
              $inputs['quantity[' . $offerLine->id . ']'] = [
                '#type' => 'container',
                '#attributes' => ['class' => ['col-sm-' . round(12 / count($extra->offerLines))]],
                'offer-quantity-' . $extra->id . '-' . $offerLine->id => [
                  '#type' => 'select',
                  '#attributes' => ['name' => 'offer-quantity-' . $extra->id . '-' . $offerLine->id, 'class' => ['input-sm']],
                  '#required' => $offerLine->mandatory,
                  '#title' => $offerLine->description,
                  '#prefix' => '',
                  '#suffix' => $this->t('@price chaque', ['@price' => $this->moneyFormat($offerLine->pricePerUnit)]),
                  '#options' => range(0, $offerLine->maxQuantity)
                ]
              ];

              // Save it for validation
              $extrasData[$extra->id . '-' . $offerLine->id] = [
                'extra_id' => $extra->id,
                'offerline_id' => $offerLine->id,
                'description' => $offerLine->description,
                'mandatory' => $offerLine->mandatory,
                'price' => $this->moneyFormat($offerLine->pricePerUnit),
                'price_raw' => $offerLine->pricePerUnit,
                'max' => $offerLine->maxQuantity,
              ];
            }
          }

          $this->store->set('extrasData', $extrasData);

        } else {
          $inputs['na'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['col-sm-12']],
            '#markup' => $this->t('Non disponible pour les dates et heures sélectionnées.'),
          ];
        }

        $form['results']['parking-' . $extra->id] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['wrap-row', 'parking-' . $extra->id]],

          'name' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['parking-name', 'parking-name-' . $extra->id],
              'style' => 'background-image:url("' . $extra->logo . '"); background-size:cover; height:170px;',
            ],
          ],

          'info' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['parking-info']],
            '#markup' => $extra->description
          ],

          'localisation' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['parking-localisation']],
            'wrapper' => $information
          ],

          'dropdown' => $inputs,
        ];
      }
    }

    $form['actions']['submit']['#value'] = $this->t('Procédez au paiement');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $values = $_POST;

    $extras = [];
    $extrasForValidation = [];
    $extrasDetails = $this->store->get('extrasData');

    foreach ($values as $key => $value) {
      if (preg_match('/offer-quantity-(\d{1,})-(\d{1,})/', $key, $matches)) {
        $offerId = intval(array_pop($matches));
        $extraId = intval(array_pop($matches));
        $quantity = intval($value);

        if ($quantity) {
          if (!isset($extras[$extraId])) {
            $extras[$extraId] = [];
          }

          $extras[$extraId][] = [
            $offerId => $quantity
          ];

          $extrasForValidation[] = $extraId.'-'.$offerId;
        }
      }
    }

    // Validation for adult ticket requirement on non adult ticket
    $adultId = 0;
    if(!empty($extrasDetails) && is_array($extrasDetails)) {
      foreach ($extrasDetails as $k => $detail) {
        if (preg_match('/adult/i', $detail['description'])) {
          $adultId = $k;
          break;
        }
      }
    }

    if($adultId !== 0 && !empty($extrasForValidation) && !in_array($adultId, $extrasForValidation)){
      $form_state->setErrorByName('submit', $this->t('The passenger must be at least 18 years of age to enter without a parent or legal guardian.'));
    }else {
      foreach ($extras as $extraId => $offerLines) {
        $data = [
          'extraId' => $extraId,
          'offerLines' => [],
          'arrivalDate' => date('Y-m-d', strtotime($this->store->get('arrival_date'))),
          'arrivalTime' => date('H:i', strtotime($this->store->get('arrival_date'))),
          'departureDate' => date('Y-m-d', strtotime($this->store->get('departure_date'))),
          'departureTime' => date('H:i', strtotime($this->store->get('departure_date')))
        ];

        foreach ($offerLines as $offerLine) {
          $data['offerLines'][] = [
            'extraOfferLineId' => key($offerLine),
            'quantity' => current($offerLine),
          ];
        }

        $extraResult = $this->advam->addExtra($this->store->get('booking')->guid, $data);

        if (!$extraResult) {
          drupal_set_message($this->t("Une erreur est survenue durant l'ajout du produit à la commande."), 'error');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route = sprintf('yqb_parking_booker.%s.payment', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $this->parkingRedirect($form_state, $route);
  }
}

?>
