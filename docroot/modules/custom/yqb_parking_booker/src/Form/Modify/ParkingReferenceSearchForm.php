<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form\Modify;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\yqb_parking_booker\Form\ParkingFormBase;

class ParkingReferenceSearchForm extends ParkingFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parking_booker_modify_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->deleteStore();

    if($this->getRequest()->query->get('webview')){
      $this->store->set('webview', true);
    }

    $form = parent::buildForm($form, $form_state);

    if($this->getRequest()->query->get('user_id')){
      $current_user = User::load($this->getRequest()->query->get('user_id'));

      if(!empty($current_user)) {
        $this->store->set('user_id', $this->getRequest()->query->get('user_id'));
      }
    }else{
      $current_user = \Drupal::currentUser();
    }

    $bookings = [];
    if (!empty($current_user) && $current_user->id()) {
      $now = date('Y-m-d');

      $query = \Drupal::entityQuery('node');

      $andDeparture= $query->andConditionGroup()
        ->condition('field_arrival', $now, '<=')
        ->condition('field_departure', $now, '>');

      $orGroup = $query->orConditionGroup()
        ->condition('field_arrival', $now, '>=')
        ->condition($andDeparture);

      $results = $query->condition('type', 'parking_booking')
      ->condition('field_user', $current_user->id())
      ->condition($orGroup)
      ->execute();

      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $bookings = $node_storage->loadMultiple($results);
    }

    if (!empty($bookings)) {
      $container = [
        '#type' => 'container',
        'row' => [
          'results' => [
            '#type' => 'container',
            '#theme' => 'table',
            '#responsive' => false,
            '#cache' => ['max-age' => 0],
            '#attributes' => ['class' => ['table', 'table-normal']],
            '#header' => [
              $this->t('Référence de réservation'),
              $this->t('Date d\'arrivé'),
              $this->t('Date de sortie'),
              ''
            ],
            '#rows' => [],
            '#empty' => $this->t('Vous n\'avez aucune réservation de stationnement active')
          ],
          'bottom' => []
        ]
      ];

      $i = 0;
      foreach($bookings as $booking){
        $container['row']['results']['#rows'][] = [
          'class' => '',
          'data' => [
            'reference' => [
                'class' => '',
                'data' => [
                    '#plain_text' => $booking->field_advam_reference->value
                ]
            ],
            'arrival' => [
                'class' => 'c-align',
                'data' => [
                    '#plain_text' => \Drupal::service('date.formatter')->format(strtotime($booking->field_arrival->value), 'custom', 'Y-m-d H:i:s')
                ]
            ],
            'departure' => [
                'class' => 'c-align',
                'data' => [
                    '#plain_text' => \Drupal::service('date.formatter')->format(strtotime($booking->field_departure->value), 'custom', 'Y-m-d H:i:s')
                ]
            ],
            'action' => [
              'class' => 'actions',
              'data' => [
                '#type' => 'container',
                'booking_reference' => [
                  '#type' => 'radio',
                  '#attributes' => ['name' => 'booking_reference', 'checked' => ($i == 0)],
                  '#required' => TRUE,
                  '#value' => $booking->field_advam_reference->value,
                  '#return_value' => $booking->field_advam_reference->value,
                ]
              ]
            ],
          ],
        ];

        $i++;
      }

      // Format submit zone
      $form['actions']['submit']['#value'] = $this->t('Voir la réservation');
      $form['actions']['submit']['#attributes'] = ['class' => ['btn', 'btn-primary']];

      $actions = $form['actions'];
      unset($form['actions']);

      $bottom = [
          '#type' => 'container',
          '#attributes' => ['class' => ['row', 'text-right']],
          'row' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],
              'email' => [
                '#type' => 'hidden',
                '#value' => $current_user->getEmail()
              ],
              'actions' => $actions,
          ],
      ];

      $container['row']['bottom'] = $bottom;
    } else {
      $description = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t(" Entrez votre adresse e-mail et numéro de réservation et sélectionnez recherche pour trouver votre réservation "),
      ];

      $email = [
        '#type' => 'email',
        '#title' => $this->t("Courriel"),
        '#required' => TRUE,
      ];

      $bookingReference = [
        '#type' => 'textfield',
        '#title' => $this->t("Référence de réservation"),
        '#required' => TRUE,
      ];

      $form['actions']['submit']['#value'] = $this->t('Rechercher');

      $actions = $form['actions'];
      unset($form['actions']);

      $container = [
        '#type' => 'container',
        '#cache' => ['max-age' => 0],
        '#attributes' => ['class' => ['row']],

        'row' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],

          'col-1' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['col-sm-6']],
            'description' => $description,
            'email' => $email,
            'booking_reference' => $bookingReference,
            'actions' => $actions,
          ],

          'col-2' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['col-sm-6']],
          ]
        ]
      ];
    }

    $form['container'] = $container;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $bookingReference = (isset($_POST['booking_reference']) && empty($form_state->getValue('booking_reference'))) ? $_POST['booking_reference'] : $form_state->getValue('booking_reference');

    $result = $this->advam->searchBooking($form_state->getValue('email'), $bookingReference);

    if ($result) {
      $this->store->set('current_booking', reset($result->bookings));
    } else {
      $form_state->setError($form, $this->t("Aucune référence de réservation trouvée."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route = sprintf('yqb_parking_booker.%s.modify.results', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $this->parkingRedirect($form_state, $route);
  }
}

?>
