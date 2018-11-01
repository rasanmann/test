<?php
/**
 * @file
 * Contains \Drupal\yqb_flight_planner\Form\FlightPlannerForm.
 */

namespace Drupal\yqb_flight_planner\Form;

require_once DRUPAL_ROOT . '/lib/wego/src/WegoAPI.php';

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Wego\WegoAPI;

class FlightPlannerForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flight_planner_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Find airports
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'airport')
      ->condition('status', 1)
    ;

    $nids = array_keys($query->execute());

    $db = \Drupal::database();

    $query = $db->select('node_field_data','t');
    $query->addJoin('INNER', 'node__field_iata', 'i', 't.nid = %alias.entity_id');
    $query->addJoin('INNER', 'node__field_city', 'c', 't.nid = %alias.entity_id');

    $query->fields('t', ['title']);
    $query->fields('i', ['field_iata_value']);
    $query->fields('c', ['field_city_value']);

    $query->condition('nid', $nids, 'IN');

    $query->orderBy('field_city_value');

    $nodes = $query->execute();

    $airports = [];

    foreach ($nodes as $node) {
      $airports[$node->field_iata_value] = sprintf('%s, %s (%s)', $node->field_city_value, $node->title, $node->field_iata_value);
    }

    $form['row-1'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row']],

      'col-1-1' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-sm-6']],

        'origin' => [
          '#type' => 'select',
          '#title' => $this->t('Origin'),
          '#options' => $airports,
          '#required' => TRUE,
        ]
      ],

      'col-1-2' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-sm-3']],

        'from' => [
          '#type' => 'date',
          '#title' => $this->t('From'),
          '#value' => date('Y-m-d', strtotime('+1 day')),
          '#required' => TRUE,
        ]
      ],

      'col-1-3' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-sm-3']],

        'to' => [
          '#type' => 'date',
          '#title' => $this->t('To'),
          '#value' => date('Y-m-d', strtotime('+8 day')),
          '#required' => TRUE,
        ]
      ],
    ];

    $form['row-2'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row']],

      'col-2-1' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-sm-6']],

        'destination' => [
          '#type' => 'select',
          '#title' => $this->t('Destination'),
          '#options' => $airports,
          '#required' => TRUE,
        ]
      ],

      'col-2-2' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-sm-3']],

        'passengers' => [
          '#type' => 'select',
          '#title' => $this->t('Passengers'),
          '#options' => [1, 2, 3],
          '#required' => TRUE,
        ]
      ],

      'col-2-3' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-sm-3']],

        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
        ]
      ],
    ];

    $storage = &$form_state->getStorage();

    if (isset($storage['results'])) {
      $form['results'] = [
        '#type' => 'table',
        '#header' => [
          'Price',
          'Segments',
          'Airlines',
          'Provider',
          '',
        ],
        '#empty' => $this->t('Aucun résultat.')
      ];

      foreach($storage['results'] as $id => $result) {
        $form['results'][$id]['price'] = [
          '#plain_text' => $result['price'],
        ];

        $form['results'][$id]['segments'] = [
          '#markup' => $result['segments'],
        ];

        $form['results'][$id]['airlines'] = [
          '#markup' => $result['airlines'],
        ];

        $form['results'][$id]['provider'] = [
          '#markup' => $result['provider'],
        ];

        $form['results'][$id]['link'] = [
          '#type' => 'link',
          '#title' => $this->t('Book now'),
          '#url' => Url::fromUri($result['link']),
          '#attributes' => ['target' => '_blank', 'class' => ['btn', 'btn-info']]
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate video URL.
//    if (!UrlHelper::isValid($form_state->getValue('video'), TRUE)) {
//      $form_state->setErrorByName('video', $this->t("The video url '%url' is invalid.", array('%url' => $form_state->getValue('video'))));
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = &$form_state->getStorage();
    $storage['results'] = [];

    $wego = new WegoAPI();

    $options = $wego->getFares($form_state->getValue('origin'), $form_state->getValue('destination'), $form_state->getValue('from'));

    setlocale(LC_MONETARY, 'en_US');

    foreach($options as $option) {
      $storage['results'][] = [
        'price' => money_format('%(#10n', $option['price']),
        'segments' => implode(' &rarr; ', $option['segments']),
        'airlines' => implode(', ', $option['airlines']),
        'provider' => $option['provider'],
        'link' => $option['link'],
      ];
    }

    $form_state->setStorage($storage);

    $form_state->setRebuild();
  }
}

?>