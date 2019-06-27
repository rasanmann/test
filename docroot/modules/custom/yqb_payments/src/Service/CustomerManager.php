<?php

namespace Drupal\yqb_payments\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\moneris\Receipt;

class CustomerManager
{
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

  public function __construct(PrivateTempStoreFactory $tempStoreFactory, MailManagerInterface $mailManager)
  {
    $this->tempStore = $tempStoreFactory->get('yqb_payments');
    $this->mailManager = $mailManager;
  }

  /**
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function reset()
  {
    foreach (static::$attributes as $attribute) {
      $this->tempStore->delete($attribute);
    }
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

  public function getReceipt()
  {
    return $this->get('receipt');
  }

  public function sendReceipt()
  {
    if (!$this->successful()) {
      return;
    }
  }
}
