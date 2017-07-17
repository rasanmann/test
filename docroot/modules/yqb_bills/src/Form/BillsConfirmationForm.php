<?php
/**
 * @file
 * Contains \Drupal\yqb_bills\Form\FlightPlannerForm.
 */

namespace Drupal\yqb_bills\Form;

use Drupal\Core\Form\FormStateInterface;

class BillsConfirmationForm extends BillsFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bills_confirmation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $this->sendAdminEmail();

    $reference_number = $this->store->get('reference_number');

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Merci'),
    ];

    $form['confirmation'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Votre numéro de référence : :reference_number:', [':reference_number:' => $reference_number]),
    ];

    $form['message'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Votre paiement a bien été reçu.'),
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