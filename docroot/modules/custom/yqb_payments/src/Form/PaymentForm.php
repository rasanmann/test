<?php

namespace Drupal\yqb_payments\Form;

use Drupal;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yqb_payments\Service\CustomerManager;
use Drupal\yqb_payments\Service\Recaptcha;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class PaymentForm extends FormBase
{


  protected $recaptchaService;

  protected $config;

  protected $customerManager;

  public function __construct(
    Recaptcha $recaptchaService,
    ConfigFactory $configFactory,
    CustomerManager $customerManager
  )
  {
    $this->recaptchaService = $recaptchaService;
    $this->config = $configFactory->get('yqb_payments.settings');
    $this->customerManager = $customerManager;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('yqb_payments.recaptcha'),
      $container->get('config.factory'),
      $container->get('yqb_payments.customer_manager')
    );
  }

  public function getFormId()
  {
    return 'yqb_payments_payment_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['first_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('First name'),
      '#default_value' => $this->customerManager->get('first_name'),
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Last name'),
      '#default_value' => $this->customerManager->get('last_name'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => $this->t('Email'),
      '#default_value' => $this->customerManager->get('email'),
    ];

    $form['business_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Business name'),
      '#default_value' => $this->customerManager->get('business_name'),
    ];

    $form['bill_no'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Bill number'),
      '#default_value' => $this->customerManager->get('bill_no'),
    ];

    $form['customer_no'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Customer number'),
      '#default_value' => $this->customerManager->get('customer_no'),
    ];

    $form['amount'] = [
      '#type' => 'number',
      '#required' => TRUE,
      '#title' => $this->t('Amount to pay'),
      '#step' => 0.01,
      '#min' => $this->config->get('minimum_amount'), //@todo use the configuration for the minimum amount.
      '#size' => 8,
      '#default_value' => $this->customerManager->get('amount'),
    ];

    $form['notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I would like to receive invoices and statements by email.'),
      '#default_value' => $this->customerManager->get('notifications'),
    ];

    $this->recaptchaService->attach($form);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Proceed to checkout'),
      '#attributes' => [
        'class' => ['recaptcha-submit']
      ]
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);
    if (!$this->recaptchaService->validate($form_state)) {
      $form_state->setErrorByName('recaptcha', $this->t("Something went wrong, we were unable to validate the CAPTCHA. Please try again."));
    }

    if ($form_state->hasAnyErrors()) {
      $this->customerManager->reset();
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->customerManager->set('first_name', $form_state->getValue('first_name'))
      ->set('last_name', $form_state->getValue('last_name'))
      ->set('email', $form_state->getValue('email'))
      ->set('business_name', $form_state->getValue('business_name'))
      ->set('bill_no', $form_state->getValue('bill_no'))
      ->set('customer_no', $form_state->getValue('customer_no'))
      ->set('amount', $form_state->getValue('amount'))
      ->set('notifications', $form_state->getValue('notifications'))
      ->save();

    $form_state->setRedirect('yqb_payments.yqb_payment.checkout', [], [
      'query' => Drupal::destination()->getAsArray()
    ]);
  }
}
