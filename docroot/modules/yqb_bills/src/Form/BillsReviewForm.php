<?php
/**
 * @file
 * Contains \Drupal\yqb_bills\Form\FlightPlannerForm.
 */

namespace Drupal\yqb_bills\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\moneris\Render\MonerisFrameRenderer;
use Drupal\moneris\Connector\MonerisConnector;

class BillsReviewForm extends BillsFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bills_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $titlePayment = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t("Votre carte de crédit"),
    ];

    $titleConfirmation = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t("Vos informations"),
    ];

    $review = [
      '#type' => 'table',
      '#attributes' => ['class' => ['table', 'table-normal']],
      '#empty' => $this->t('Aucun résultat.'),
      '#weight' => 1,
    ];

    $fields = [
      'first_name' => $this->t('Prénom'),
      'last_name' => $this->t('Nom'),
      'company_name' => $this->t('Nom de votre entreprise'),
      'invoice_number' => $this->t('Numéro de facture'),
      'client_id' => $this->t('Numéro de client'),
      'amount' => $this->t('Montant à débourser'),
    ];

    foreach ($fields as $key => $value) {
      $review[] = [
          $key => [
          '#plain_text' => $value,
        ],
          $key . '_value' => [
          '#plain_text' => $this->store->get($key),
        ]
      ];
    }

    $dataKey = [
      '#type' => 'hidden',
      '#title' => $this->t('Data key'),
      '#required' => TRUE,
    ];

    $frameRenderer = new MonerisFrameRenderer([
      'pan_label' => $this->t("Num&eacute;ro de la carte")->render(),
      'exp_label' => $this->t("Date d&rsquo;expiration (MMAA)")->render(),
      'cvd_label' => $this->t("CVD")->render(),
    ]);

    $fields = [
      'data_key' => $dataKey,
      'moneris_frame' => $frameRenderer->getRenderArray(),
    ];

    $fields = array_map(function($fieldName, $field) {
      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['row']],
        '#weight' => 5,

        'col' => [
          '#type' => 'container',
          $fieldName => $field
        ],
      ];
    }, array_keys($fields), $fields);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#attributes' => ['class' => ['btn', 'btn-default']],
      '#type' => 'submit',
      '#value' => $this->t('Payer maintenant'),
      '#button_type' => 'default',
      '#weight' => 10,
    ];

    $actions = $form['actions'];
    unset($form['actions']);

    $container = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row']],
      'col-1' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['col-sm-6']],
        'title' => $titleConfirmation,
        'review' => $review
      ],
      'col-2' => array_merge([
        '#type' => 'container',
        '#attributes' => ['class' => ['col-sm-6']],
        'title' => $titlePayment,
        'actions' => $actions
      ], $fields)
    ];

    $form['container'] = $container;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $connector = new MonerisConnector();

    $transaction = [
      'data_key' => $form_state->getValue('data_key'),
      'order_id' => 'bill_' . $this->store->get('invoice_number') . '_' . uniqid(),
      'cust_id' => $this->store->get('client_id'),
      'amount' => floatval($this->store->get('amount'))
    ];

    $purchase = $connector->purchase($transaction);

    if ($purchase->was_successful()) {
      // Success
      $this->store->set('transaction', $transaction);
      $this->store->set('transaction_response', json_encode($purchase->response()));
      $this->store->set('transaction_response_message', json_encode($purchase->response_message()));
      $this->store->set('reference_number', (string)$purchase->reference_number());
    } else {
      // Failed
      $form_state->setError($form, $purchase->response_message());
      $form_state->setError($form, $purchase->error_code());
      $form_state->setError($form, $purchase->error_message());
      $form_state->setErrorByName('submit', $this->t("Une erreur est survenue durant le paiement de la facture. Veuillez rééssayer."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route = sprintf('yqb_bills.%s.confirmation', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $form_state->setRedirect($route);
  }
}

?>