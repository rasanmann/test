<?php

namespace Drupal\yqb_payments\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Online Payment edit forms.
 *
 * @ingroup yqb_payments
 */
class YqbPaymentEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\yqb_payments\Entity\YqbPaymentEntity */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Online Payment.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Online Payment.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.yqb_payment.canonical', ['yqb_payment' => $entity->id()]);
  }

}
