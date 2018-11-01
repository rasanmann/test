<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form\Modify;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\yqb_parking_booker\Form\ParkingFormBase;
use Endroid\QrCode\QrCode;

class ParkingReferenceResultsForm extends ParkingFormBase {
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
    // Check if parking id is submited via url
    if($this->getRequest()->query->get('parking_id') && $this->getRequest()->query->get('user_id')) {
      // Cleanup session
      $this->deleteStore(['webview']);
      
      if($this->getRequest()->query->get('webview')) $this->store->set('webview', true);
      $this->store->set('user_id', $this->getRequest()->query->get('user_id'));
      
      $query = \Drupal::entityQuery('node')
         ->condition('type', 'parking_booking')
         ->condition('nid', $this->getRequest()->query->get('parking_id'))
         ->condition('field_user', $this->getRequest()->query->get('user_id'))
         ->pager(1);
      
      $results = $query->execute();
      
      if(!empty($results)) {
        $parking = Node::load(current($results));
        
        $result = $this->advam->getBooking($parking->field_advam_guid->value);
        $this->store->set('current_booking', $result->booking);
      }
      
      $noBackBtn = true;
    }
    
    // Results weren't stored, user probably accessed this page directly, redirect to homepage
    if (!$this->store->get('current_booking')) {
      drupal_set_message($this->t("Aucune réservation correspondant aux informations fournies."), 'error');
      
      // Make sure redirects is in good context
      $params = [];
      if($this->store->get('webview')) $params['webview'] = 1;
      if($this->store->get('user_id')) $params['user_id'] = $this->store->get('user_id');
      
      return $this->redirect(sprintf('yqb_parking_booker.%s.modify.index', \Drupal::languageManager()->getCurrentLanguage()->getId()),  $params);
    }
    
    $confirmation = $this->advam->getBookingConfirmation($this->store->get('current_booking')->guid);

    $form = parent::buildForm($form, $form_state);

    $currentBooking = $this->store->get('current_booking');
    
    $backUrl = sprintf('yqb_parking_booker.%s.modify.index', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $form['title'] = [
      "#type" => 'container',
      '#attributes' => ['class' => ['parking-booker-title']],
      'row' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#attributes' => ['class' => ['title-page']],
        '#value' => \Drupal::service('title_resolver')->getTitle(\Drupal::request(), \Drupal::routeMatch()->getRouteObject()),
      ],
      'back' => [
          '#attributes' => ['class' => ['btn-back']],
          '#type' => 'link',
          '#url' => \Drupal\Core\Url::fromRoute($backUrl),
          '#title' => $this->t('<span class="icon icon-left-arrow" data-grunticon-embed></span>')
      ]
    ];
    
    if(isset($noBackBtn)){
      $form['title']['#attributes']['class'][] = 'no-back-btn';
    }

    $order = $this->generateBookingView($currentBooking, $confirmation);
    
    $params = ($this->store->get('webview')) ? ['webview' => 1] : [];

    $modifyBookingLink = [
      '#title' => $this->t("Modifier ma réservation"),
      '#type' => 'link',
      '#attributes' => ['class' => ['btn', 'btn-sm', 'btn-info', 'btn-inverse']],
      '#url' => Url::fromRoute(sprintf('yqb_parking_booker.%s.modify.booking', \Drupal::languageManager()->getCurrentLanguage()->getId()), $params)
    ];

    $actions = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row', 'text-center']],
      'row' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],
        'modifyBookingLink' => ($currentBooking->canAmend) ? $modifyBookingLink : null,
      ],
    ];

    unset($form['actions']);

    $form['receipt'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row', 'parking-confirmation-content']],
      'order' => $order,
      'actions' => $actions
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
    $route = sprintf('yqb_parking_booker.%s.extras', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $this->parkingRedirect($form_state, $route);
  }
}

?>