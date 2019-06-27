<?php

namespace Drupal\moneris;

use Moneris_Result;

class Receipt
{
  protected $values;

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

  public function getAmount()
  {
    return $this->get('TransAmount');
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
