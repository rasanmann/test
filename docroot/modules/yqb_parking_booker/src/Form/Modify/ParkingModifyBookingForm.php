<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form\Modify;

use Drupal\bootstrap\Plugin\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\moneris\Connector\MonerisConnector;
use Drupal\yqb_parking_booker\Form\ParkingFormBase;
use Drupal\yqb_parking_booker\Form\ParkingResultsForm;
use Drupal\yqb_parking_booker\Form\ParkingSearchForm;
use Endroid\QrCode\QrCode;

class ParkingModifyBookingForm extends ParkingFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'parking_booker_modify_booking_form';
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

        $title = [
            '#type' => 'html_tag',
            '#tag' => 'h1',
            '#attributes' => ['class' => ['title-page']],
            '#value' => \Drupal::service('title_resolver')->getTitle(\Drupal::request(), \Drupal::routeMatch()->getRouteObject())
        ];

        $titleUpgrade = [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => $this->t("Modifier les options")
        ];

        $titleOptions = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t("Options supplémentaires d'arrivée le <strong>@arrival</strong> et départs le <strong>@departure</strong>", ['@arrival' => $this->store->get('arrival_date'), '@departure' => $this->store->get('departure_date')])
        ];

        $searchForm = ParkingSearchForm::create(\Drupal::getContainer());
        $searchFormState = new FormState();
        $searchFormState->set('coupon_input', false);
        $searchFormState->set('current_booking', true);
        $searchFormState->set('warning', true);
        $builtSearchForm = \Drupal::formBuilder()->buildForm($searchForm, $searchFormState);

        $builtSearchForm['title']['#value'] = $this->t("Modifier les dates de réservation");

        $builtSearchForm['container']['desktop']['col-3']['actions']['submit']['#value'] =
        $builtSearchForm['container']['desktop']['col-3']['actions']['submit']['#value'] = $this->t("Rechercher");

        $search = [
            '#type' => 'container',
            '#attributes' => ['class' => ['block-parking-booker']],
            'content' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['form', 'form-inverse']],
                'content' => $builtSearchForm
            ]
        ];

        $statusBar = $this->generateStatusBar([
            "Référence de réservation" => $currentBooking->reference,
            "Date d'arrivée" => date('Y-m-d H:i', strtotime($currentBooking->arrivalDate . ' ' . $currentBooking->arrivalTime)),
            "Date de sortie" => date('Y-m-d H:i', strtotime($currentBooking->departureDate . ' ' . $currentBooking->departureTime)),
        ], sprintf('yqb_parking_booker.%s.modify.results', \Drupal::languageManager()->getCurrentLanguage()->getId()));

        $form['actions']['#attributes']['class'][] = 'hidden';
        $actions = $form['actions'];
        unset($form['actions']);

        $form['title'] = $title;
        $form['status'] = $statusBar;
        $form['footer'] = $this->generateFooter();

        $form['order'] = $this->generateBookingSummary($currentBooking);
        $form['search'] = $search;
        
        

        if ($this->store->get('arrival_date') && $this->store->get('departure_date')) {
            $form['titles'] = [
                '#type' => 'container',
                '#attributes' => ['class' => ['row']],
                'row' => [
                    '#type' => 'container',
                    '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],
                    'hr' => [
                        '#type' => 'html_tag',
                        '#tag' => 'hr',
                    ],
                    'upgrade' => $titleUpgrade,
                    'options' => $titleOptions,
                ],
            ];

            $resultsForm = ParkingResultsForm::create(\Drupal::getContainer());
            $resultsFormState = new FormState();
            $builtResultsForm = \Drupal::formBuilder()->buildForm($resultsForm, $resultsFormState);

            unset($builtResultsForm['header']);
            unset($builtResultsForm['footer']);

            $form['booking-form'] = $builtResultsForm;
        }
        
        $params = ($this->store->get('webview')) ? ['webview' => 1] : [];
        
        $modifyLink = [
            '#title' => $this->t("Modifier mes informations"),
            '#type' => 'link',
            '#attributes' => ['class' => ['btn', 'btn-sm', 'btn-primary']],
            '#url' => Url::fromRoute(sprintf('yqb_parking_booker.%s.modify.details', \Drupal::languageManager()->getCurrentLanguage()->getId()), $params)
          ];
      
        $cancelLink = [
          '#title' => $this->t("Annuler ma réservation"),
          '#type' => 'link',
          '#attributes' => ['class' => ['btn', 'btn-sm', 'btn-danger']],
          '#url' => Url::fromRoute(sprintf('yqb_parking_booker.%s.modify.cancel', \Drupal::languageManager()->getCurrentLanguage()->getId()), $params)
        ];
    
        $form['actions'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['row', 'text-center', 'parking-modify-actions']],
          'row' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],
            'modifyInfoLink' => ($currentBooking->canAmend) ? $modifyLink : null,
            'modifyInfoLinkspace' => ['#markup' => '<br><br>'],
            'cancelLink' => ($currentBooking->canAmend) ? $cancelLink : null,
          ],
        ];

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