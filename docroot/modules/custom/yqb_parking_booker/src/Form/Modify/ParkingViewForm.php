<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form\Modify;

use Drupal\Core\Form\FormStateInterface;
use Endroid\QrCode\QrCode;
use DateTime;
use DateTimeZone;
use Drupal\yqb_parking_booker\Form\ParkingFormBase;

class ParkingViewForm extends ParkingFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parking_booker_view_form';
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
    unset($form['actions']);

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

    $titleConfirmation = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t("Merci de votre commande @first_name", ['@first_name'=> $this->store->get('first_name')]),
    ];

    $emailConfirmation = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t("Nous vous acheminons un courriel de confirmation sous peu à l'adresse @email.", ['@email'=> $this->store->get('email')]),
    ];

    $details = $this->generateBookingDetailsConfirmation($currentBooking);
    $order = $this->generateBookingOrderConfirmation($currentBooking);

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