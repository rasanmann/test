<?php
/**
 * @file
 * Contains \Drupal\yqb_bills\Form\FlightPlannerForm.
 */

namespace Drupal\yqb_bills\Form;

use Drupal\Core\Form\FormStateInterface;

class BillsPaymentForm extends BillsFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bills_payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $information = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t("Aéroport de Québec inc. (AQi), gestionnaire de l'aéroport international Jean-Lesage de Québec, met à votre disposition un service gratuit de paiement en ligne qui vous permet d'acquitter vos factures en tout temps et en toute sécurité (maximum de 5000$) par Visa, MasterCard ou American Express. Pour utiliser le service, il vous suffit de compléter les champs suivants&nbsp;:"),
    ];

    $lastName = [
      '#type' => 'textfield',
      '#title' => $this->t('Nom'),
      '#required' => TRUE,
    ];

    $firstName = [
      '#type' => 'textfield',
      '#title' => $this->t('Prénom'),
      '#required' => TRUE,
    ];

    $companyName = [
      '#type' => 'textfield',
      '#title' => $this->t('Nom de votre entreprise'),
      '#required' => TRUE,
    ];

    $invoiceNumber = [
      '#type' => 'textfield',
      '#title' => $this->t('Numéro de facture'),
      '#required' => TRUE,
    ];

    $clientId = [
      '#type' => 'textfield',
      '#title' => $this->t('Numéro de client'),
      '#required' => TRUE,
    ];

    $amount = [
      '#type' => 'number',
      '#title' => $this->t('Montant à débourser'),
      '#placeholder' => $this->t('50,00'),
      '#step' => 0.01,
      '#min' => 0.01,
      '#required' => TRUE,
    ];

    $fields = [
      'information' => $information,
      'first_name' => $firstName,
      'last_name' => $lastName,
      'company_name' => $companyName,
      'invoice_number' => $invoiceNumber,
      'client_id' => $clientId,
      'amount' => $amount,
    ];

    $fields = array_map(function($fieldName, $field) {
      return [
          '#type' => 'container',
          '#attributes' => ['class' => ['row']],

          'col' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['col-sm-4']],
            $fieldName => $field
          ],
      ];
    }, array_keys($fields), $fields);

    // Add fields to form
    $form = array_merge($fields, $form);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#attributes' => ['class' => ['btn', 'btn-default']],
      '#type' => 'submit',
      '#value' => $this->t('Continuer'),
      '#button_type' => 'default',
      '#weight' => 10,
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
    foreach($form_state->getValues() as $key => $value) {
      $this->store->set($key, $value);
    }

    $route = sprintf('yqb_bills.%s.review', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $form_state->setRedirect($route);
  }
}

?>