<?php

namespace Drupal\yqb_payments\Service;

use Drupal;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\moneris\Gateway;
use Drupal\moneris\Receipt;
use Drupal\yqb_payments\Entity\YqbPaymentEntity;

class CustomerManager
{
  use StringTranslationTrait;

  static public $attributes = [
    'first_name',
    'last_name',
    'email',
    'business_name',
    'bill_no',
    'customer_no',
    'amount',
    'notifications',
  ];

  protected $tempStore;

  protected $mailManager;

  protected $monerisGateway;

  protected $languageManager;

  protected $config;

  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    MailManagerInterface $mailManager,
    Gateway $gateway,
    LanguageManager $languageManager,
    ConfigFactory $configFactory)
  {
    $this->tempStore = $tempStoreFactory->get('yqb_payments');
    $this->mailManager = $mailManager;
    $this->monerisGateway = $gateway;
    $this->languageManager = $languageManager;
    $this->config = $configFactory->get('yqb_payments.settings');
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function reset()
  {
    foreach (static::$attributes as $attribute) {
      $this->tempStore->delete($attribute);
    }
    $this->tempStore->delete('entity');
    $this->tempStore->delete('receipt');
  }

  public function canCheckout()
  {
    return !empty($this->get('first_name')) &&
      !empty($this->get('last_name')) &&
      !empty($this->get('email')) &&
      !empty($this->get('business_name')) &&
      !empty($this->get('bill_no')) &&
      !empty($this->get('customer_no')) &&
      !empty($this->get('amount'));
  }

  public function successful()
  {
    $receipt = $this->get('receipt');
    return $this->canCheckout() &&
      !empty($receipt) &&
      $receipt instanceof Receipt;
  }

  public function getReferenceNumber()
  {
    return preg_replace('/\s+/', '', sprintf('drupal_%s_%s_%s', $this->get('bill_no'), $this->get('customer_no'), time()));
  }

  public function get($key, $default = '')
  {
    $value = $this->tempStore->get($key);
    return is_null($value) ? $default : $value;
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function set($key, $value)
  {
    $this->tempStore->set($key, $value);
    return $this;
  }

  public function all()
  {
    $values = [];
    foreach (static::$attributes as $attribute) {
      $values[$attribute] = $this->get($attribute);
    }
    return $values;
  }

  public function setReceipt(Receipt $receipt)
  {
    $this->set('receipt', $receipt);
  }

  /**
   * @return Receipt
   */
  public function getReceipt()
  {
    return $this->get('receipt');
  }

  public function sendReceipt()
  {
    if ($this->successful()) {
      $recipients = trim($this->config->get('recipients'));
      if ($this->get('notifications') == 1) {
        $recipients .= ',' . $this->get('email');
      }

      $this->mailManager->mail(
        'yqb_bills',
        'pay_bill',
        $recipients,
        $this->languageManager->getCurrentLanguage()->getId(),
        $this->getReceiptEmailParams()
      );
    }
  }

  protected function getReceiptEmailParams()
  {
    $segments = [
      $this->t('<p>Hello,</p>'),
      $this->t('<p>An invoice has been paid.</p>'),
      $this->t('<h2>Informations</h2>')
    ];

    $segments[] = $this->t('<p>Reference Number: @reference_num</p>', ['@reference_num' => $this->getReceipt()->getReferenceNumber()]);
    $segments[] = $this->t('<p>First name: @first_name</p>', ['@first_name' => $this->get('first_name')]);
    $segments[] = $this->t('<p>Last name: @last_name</p>', ['@last_name' => $this->get('last_name')]);
    $segments[] = $this->t('<p>Email: @email</p>', ['@email' => $this->get('email')]);
    $segments[] = $this->t('<p>Business Name: @business_name</p>', ['@business_name' => $this->get('business_name')]);
    $segments[] = $this->t('<p>Bill No: @bill_no</p>', ['@bill_no' => $this->get('bill_no')]);
    $segments[] = $this->t('<p>Customer No: @customer_no</p>', ['@customer_no' => $this->get('customer_no')]);
    $segments[] = $this->t('<p>Amount: @amount</p>', ['@amount' => $this->get('amount')]);
    $segments[] = $this->t('<p>I would like to receive invoices and statements by email: @notifications</p>', ['@notifications' => $this->get('notifications')]);
    $segments[] = $this->t('<p>Customer ID: @customer_id</p>', ['@customer_id' => $this->monerisGateway->getUuid($this->get('email'))]);
    $segments[] = $this->t('<p>Authorization code: @auth_code</p>', ['@auth_code' => $this->getReceipt()->getAuthorizationCode()]);
    $segments[] = $this->t('<p>Card Number: @card_num</p>', ['@card_num' => $this->getReceipt()->getFormattedCardNumber()]);
    $segments[] = $this->t('<p>Date and time: @date_time</p>', ['@date_time' => $this->getReceipt()->getTransactionDateTime()]);

    $params = [
      'reference' => $this->getReceipt()->getReferenceNumber(),
      'body' => implode('', $segments)
    ];

    return $params;
  }

  public function createPaymentEntity()
  {
    if ($this->successful()) {
      $payment = YqbPaymentEntity::create([
        'reference_num' => $this->getReceipt()->getReferenceNumber(),
        'field_first_name' => $this->get('first_name'),
        'field_last_name' => $this->get('last_name'),
        'field_email' => $this->get('email'),
        'field_business_name' => $this->get('business_name'),
        'field_bill_no' => $this->get('bill_no'),
        'field_customer_no' => $this->get('customer_no'),
        'field_amount' => $this->get('amount'),
        'field_notifications' => $this->get('notifications'),
        'field_customer_id' => $this->monerisGateway->getUuid($this->get('email')),
        'field_auth_code' => $this->getReceipt()->getAuthorizationCode(),
        'field_card_num' => $this->getReceipt()->getFormattedCardNumber(),
        'field_date_time' => $this->getReceipt()->getTransactionDateTime(),
      ]);
      $payment->save();
      $this->set('entity', $payment);
    }
  }

  public function getEntity()
  {
    return $this->get('entity', false);
  }

  public function billNoIsUnique($billNo)
  {
    $resultFound = Drupal::entityQuery('yqb_payment')
      ->condition('field_bill_no', $billNo)
      ->count()
      ->execute();

    return (int)$resultFound === 0;
  }
}
