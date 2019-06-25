<?php

namespace Drupal\yqb_payments\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yqb_payments\Service\Recaptcha;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentForm extends FormBase
{
  protected $recaptchaService;

  protected $config;

  public function __construct(Recaptcha $recaptchaService, ConfigFactory $configFactory)
  {
    $this->recaptchaService = $recaptchaService;
    $this->config = $configFactory->get('yqb_payments.settings');
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('yqb_payments.recaptcha'),
      $container->get('config.factory')
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
      '#title' => $this->t('First name')
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Last name')
    ];

    $form['email'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => $this->t('Email')
    ];

    $form['business_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Business name')
    ];

    $form['bill_no'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Bill number')
    ];

    $form['customer_no'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Customer number')
    ];

    $form['amount'] = [
      '#type' => 'number',
      '#required' => TRUE,
      '#title' => $this->t('Amount to pay'),
      '#step' => 0.01,
      '#min' => $this->config->get('minimum_amount'), //@todo use the configuration for the minimum amount.
      '#size' => 8
    ];

    $form['notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I would like to receive invoices and statements by email.')
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
      $form_state->setErrorByName('recaptcha', 'bob');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {

  }
}
