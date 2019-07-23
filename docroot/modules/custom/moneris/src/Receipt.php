<?php

namespace Drupal\moneris;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Drupal\Core\Datetime\DrupalDateTime;
use Moneris_Result;

class Receipt
{
  protected $values;

  const CARD_TYPES = [
    'M' => 'MasterCard',
    'V' => 'Visa',
    'AX' => 'American Express',
    'D' => 'Debit',
    'P' => 'Pin Debit',
    'unknown' => 'Unknown',
  ];

  public function __construct(array $values = [])
  {
    $this->values = $values;
  }

  public static function create($source)
  {
    switch (\get_class($source)) {
      case Moneris_Result::class:
        $receipt = new Receipt([
          'ReceiptId' => $source->response()->receipt->ReceiptId->__toString(),
          'ReferenceNum' => $source->response()->receipt->ReferenceNum->__toString(),
          'AuthCode' => $source->response()->receipt->AuthCode->__toString(),
          'TransTime' => $source->response()->receipt->TransTime->__toString(),
          'TransDate' => $source->response()->receipt->TransDate->__toString(),
          'TransAmount' => $source->response()->receipt->TransAmount->__toString(),
          'expdate' => $source->response()->receipt->ResolveData->expdate->__toString(),
          'masked_pan' => $source->response()->receipt->ResolveData->masked_pan->__toString(),
          'CardType' => $source->response()->receipt->CardType->__toString(),
        ]);
        break;
      default:
        $receipt = new Receipt();
    }

    return $receipt;
  }

  public function set($key, $value)
  {
    $this->values[$key] = $value;
    return $this;
  }

  public function get($key, $default = null)
  {
    return isset($this->values[$key]) ? $this->values[$key] : $default;
  }

  public function getReceiptId()
  {
    return $this->get('ReceiptId');
  }

  public function getReferenceNumber()
  {
    return $this->get('ReferenceNum');
  }

  public function getAuthorizationCode()
  {
    return $this->get('AuthCode');
  }

  public function getTransactionTime()
  {
    return $this->get('TransTime');
  }

  public function getTransactionDate()
  {
    return $this->get('TransDate');
  }

  public function getTransactionDateTime($timezone = 'GMT')
  {
    $date = new DrupalDateTime($this->getTransactionDate() . ' ' . $this->getTransactionTime(), 'America/Toronto');
    $date->setTimezone(new DateTimeZone($timezone));
    return $date->format('Y-m-d\TH:i:s');
  }

  public function getFormattedCardNumber()
  {
    $lastDigits = substr($this->getMaskedPan(), -4);
    return '**** **** **** ' . $lastDigits;
  }

  public function getAmount()
  {
    return $this->get('TransAmount');
  }

  public function getCardType() {
    $transactionValue = $this->get('CardType', 'unknown');

    return isset(static::CARD_TYPES[$transactionValue]) ?
      static::CARD_TYPES[$transactionValue] : 'Unknown';
  }

  public function getExpirationDate()
  {
    return $this->get('expdate');
  }

  public function getMaskedPan()
  {
    return $this->get('masked_pan');
  }
}
