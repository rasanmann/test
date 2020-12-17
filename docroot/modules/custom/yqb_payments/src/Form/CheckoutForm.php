<?php

namespace Drupal\yqb_payments\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\moneris\Gateway;
use Drupal\moneris\Receipt;
use Drupal\moneris\TransactionException;
use Drupal\yqb_payments\Service\CustomerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CheckoutForm extends FormBase {

  /** @var CustomerManager $customerManager */
  protected $customerManager;

  /** @var Gateway $monerisGateway */
  protected $monerisGateway;

  public function __construct(CustomerManager $customerManager, Gateway $gateway) {
    $this->customerManager = $customerManager;
    $this->monerisGateway = $gateway;
    $this->monerisGateway->switchSettings('moneris.payment_settings');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yqb_payments.customer_manager'),
      $container->get('moneris.gateway')
    );
  }

  public function getFormId() {
    return 'yqb_payments_checkout_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['moneris'] = [
      '#type' => 'moneris',
      '#default_value' => '',
      '#moneris_config' => 'moneris.payment_settings',
    ];

    $form['#attached'] = [
      'library' => ['yqb_payments/payment'],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['#weight'] = 200;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send payment'),
    ];

    $form['loading'] = [
      '#markup' => '<p class="payment-processing">' . $this->t('Processing payment, please wait...') . '</p>',
      '#weight' => 250,
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $monerisToken = $form_state->getValue('moneris');
    if (empty($monerisToken)) {
      $form_state->setErrorByName('moneris', $this->t("An error occurred and you must fill out again the credit card form."));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $receipt = $this->customerManager->getReceipt();

    \Drupal::logger('yqb_payments')->notice($this->customerManager->getReferenceNumber());
    if (!$receipt) {
      try {
        if (($monerisResult = $this->monerisGateway->purchase(
          $form_state->getValue('moneris'),
          $this->customerManager->getReferenceNumber(),
          $this->customerManager->get('amount'),
          $this->customerManager->get('email')
        ))) {
          $receipt = Receipt::create($monerisResult);
          $this->customerManager->setReceipt($receipt);
        }
      } catch (TransactionException $e) {
        $this->customerManager->clearReceipt();

        if (!empty($e->getErrors())) {
          foreach ($e->getErrors() as $error) {
            if (!preg_match('/^APPROVED/i', $error)) {
              Drupal::messenger()->addError($error);
            }
          }
        }
        Drupal::messenger()->addError($e->getMessage());

        return $form_state->setRedirect('yqb_payments.yqb_payment.checkout');
      }
    }

    $this->customerManager->createPaymentEntity();
    $this->customerManager->sendReceipt();
    return $form_state->setRedirect('yqb_payments.yqb_payment.success');
  }
}
