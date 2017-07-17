<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class ParkingSearchForm extends ParkingFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parking_booker_search_form';
  }

  protected function getDesktopForm($showCouponInput = false) {
    $desktop = [
      // Row
      '#type' => 'container',
      '#attributes' => ['class' => ['form-block-container', 'is-desktop']],

      'col-1' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal','col-full-responsive']],
        'arrival_date' => [
          '#type' => 'textfield',
          '#title' => $this->t("Date d'entrée"),
          '#placeholder' => date('Y-m-d H:00', strtotime('+1 day')),
          '#attributes' => [
            'autocomplete' => 'off',
            'readonly' => 'true',
            'data-toggle' => 'datetimepicker-parking',
            'class' => ['datetimepicker-start'],
            'maxlength' => 20,
          ],
          '#required' => TRUE,
        ]
      ],

      'col-2' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal']],
        'departure_date' => [
          '#type' => 'textfield',
          '#title' => $this->t("Date de sortie"),
          '#placeholder' => date('Y-m-d H:00', strtotime('+8 day')),
          '#attributes' => [
            'autocomplete' => 'off',
            'readonly' => 'true',
            'data-toggle' => 'datetimepicker-parking',
            'class' => ['datetimepicker-end'],
            'maxlength' => 20,
          ],
          '#required' => TRUE,
        ]
      ],

      'col-3' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['btn-space']],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Réserver'),
            '#attributes' => ['class' => ['btn-default']],
            '#button_type' => 'default',
            '#weight' => 10,
          ]
        ]
      ],
    ];

    if ($showCouponInput) {
      $desktop['col-2-1'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-full', 'col-promo']],
          'promo_code' => [
              '#type' => 'textfield',
              '#title' => $this->t("Code promotionnel"),
          ]
      ];
    }else{
      $desktop['col-3']['#attributes']['class'][] = 'is-static';
    }

    ksort($desktop);

    return $desktop;
  }

  public function getMobileForm($showCouponInput = false) {
    $mobile = [
      // Row
      '#type' => 'container',
      '#attributes' => ['class' => ['form-block-container', 'is-mobile']],

      'col-1' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal','col-full-responsive']],
        'arrival_date' => [
          '#type' => 'date',
          '#title' => $this->t("Date d'entrée"),
          '#placeholder' => date('Y-m-d', strtotime('+1 day')),
          '#attributes' => [
            'autocomplete' => 'off',
            'type' => 'date',
            'min' => date('Y-m-d', strtotime('+1 day')),
          ],
          '#required' => TRUE,
        ]
      ],

      'col-2' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal','col-full-responsive']],
        'arrival_time' => [
          '#type' => 'date',
          '#title' => $this->t("Heure d'entrée"),
          '#placeholder' => date('H:00'),
          '#attributes' => [
            'autocomplete' => 'off',
            'type' => 'time',
            'step' => 1800,
          ],
          //'#required' => TRUE,
        ]
      ],

      'col-3' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal','col-full-responsive']],
        'departure_date' => [
          '#type' => 'date',
          '#title' => $this->t("Date de sortie"),
          '#placeholder' => date('Y-m-d', strtotime('+8 day')),
          '#attributes' => [
            'autocomplete' => 'off',
            'type' => 'date',
            'min' => date('Y-m-d', strtotime('+1 day')),
          ],
          '#required' => TRUE,
        ]
      ],

      'col-4' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-normal','col-full-responsive']],
        'departure_time' => [
          '#type' => 'date',
          '#title' => $this->t("Heure de sortie"),
          '#placeholder' => date('H:00'),
          '#attributes' => [
            'autocomplete' => 'off',
            'type' => 'time',
            'step' => 1800,
          ],
          //'#required' => TRUE,
        ]
      ],


      'col-5' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['btn-space']],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Réserver'),
            '#attributes' => ['class' => ['btn-default']],
            '#button_type' => 'default',
            '#weight' => 10,
          ]
        ]
      ],
    ];

    if ($showCouponInput) {
      $mobile['col-4-1'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-full', 'col-promo']],
          'promo_code' => [
              '#type' => 'textfield',
              '#title' => $this->t("Code promotionnel"),
          ]
      ];
    }else{
      $mobile['col-5']['#attributes']['class'][] = 'is-static';
    }

    ksort($mobile);

    return $mobile;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if($this->getRequest()->query->get('webview')){
      $this->store->set('webview', true);
    }
    
    if($this->getRequest()->query->get('user_flight')){
      $this->store->set('user_flight', $this->getRequest()->query->get('user_flight'));
    }
    
    if($this->getRequest()->query->get('user_id')){
      if(!empty(User::load($this->getRequest()->query->get('user_id')))) {
        $this->store->set('user_id', $this->getRequest()->query->get('user_id'));
      }
    }
    
    $form = parent::buildForm($form, $form_state);

    $form['#attributes']['class'][] = 'form';
    $form['#attributes']['target'] = ($this->advam->isLegacy) ? '_blank' : null;

    $desktop = $this->getDesktopForm($form_state->get('coupon_input'));

    $mobile = $this->getMobileForm($form_state->get('coupon_input'));

    if ($form_state->get('warning')) {
      $form['title'] = [
          '#type' => 'html_tag',
          '#attributes' => ['class' => ['block-title', 'title-content']],
          '#tag' => 'h3',
          '#value' => $this->t("La réservation doit être effectuée minimum 24 h à l'avance"),
      ];
    }

    $form['container'] = [
      '#type' => 'container',
      '#cache' => ['max-age' => 0],
      '#attributes' => ['class' => []],
      'desktop' => $desktop,
      'mobile' => $mobile
    ];

    unset($form['actions']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('arrival_time') && $form_state->getValue('departure_time')) {
      $arrival = strtotime($form_state->getValue('arrival_date') . ' ' . $form_state->getValue('arrival_time'));
      $departure = strtotime($form_state->getValue('departure_date') . ' ' . $form_state->getValue('departure_time'));
    } else {
      $arrival = strtotime(date('Y-m-d H:i:s', strtotime($form_state->getValue('arrival_date'))));
      $departure = strtotime(date('Y-m-d H:i:s', strtotime($form_state->getValue('departure_date'))));
    }

    if ($arrival >= $departure) {
      $form_state->setError($form, $this->t("Les dates sélectionnées ne sont pas valides."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Start over
    $this->deleteStore(($form_state->get('current_booking')) ? ['current_booking', 'user_id', 'webview'] : null);

    if ($form_state->getValue('arrival_time') && $form_state->getValue('departure_time')) {
      $this->store->set('arrival_date', $form_state->getValue('arrival_date') . ' ' . $form_state->getValue('arrival_time'));
      $this->store->set('departure_date', $form_state->getValue('departure_date') . ' ' . $form_state->getValue('departure_time'));
    } else {
      $this->store->set('arrival_date', date('Y-m-d H:i:s', strtotime($form_state->getValue('arrival_date'))));
      $this->store->set('departure_date', date('Y-m-d H:i:s', strtotime($form_state->getValue('departure_date'))));
    }

    if ($form_state->getValue('promo_code')) {
      $this->store->set('promo_code', $form_state->getValue('promo_code'));
    }

    if ($this->advam->isLegacy) {
      $url = sprintf('http://reservation.aeroportdequebec.com/book.aspx?languageCode=%d', \Drupal::languageManager()->getCurrentLanguage()->getId());

      $url = 'http://reservation.aeroportdequebec.com/%s/searchresults.aspx?ad=%s&at=%s&dd=%s&dt=%s&pc=';

      header('Location: ' . sprintf($url,
              \Drupal::languageManager()->getCurrentLanguage()->getId(),
              date('d/m/Y', strtotime($this->store->get('arrival_date'))),
              date('H:i', strtotime($this->store->get('arrival_date'))),
              date('d/m/Y', strtotime($this->store->get('departure_date'))),
              date('H:i', strtotime($this->store->get('departure_date')))
      ));

      exit();
    }

    // Decide where to go from here
    if ($this->store->get('current_booking')) {
      // Currently modifying a booking,go back there
      $route = sprintf('yqb_parking_booker.%s.modify.booking', \Drupal::languageManager()->getCurrentLanguage()->getId());
    } else  {
      // New booking, go to results page
      $route = sprintf('yqb_parking_booker.%s.results', \Drupal::languageManager()->getCurrentLanguage()->getId());
    }
    
    $this->parkingRedirect($form_state, $route);
  }
}

?>