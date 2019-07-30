<?php

namespace Drupal\moneris;

use Exception;
use Moneris_Result;
use Throwable;

class TransactionException extends Exception
{
  protected $errors = [];

  protected $monerisResult;

  public function __construct($message = "", $code = 0, Throwable $previous = null)
  {
    parent::__construct($message, $code, $previous);
    return $this;
  }

  public function setErrors(array $errors)
  {
    $this->errors = $errors;
  }

  public function setMonerisResult(Moneris_Result $result)
  {
    $this->monerisResult = $result;
  }

  public function getMonerisResult()
  {
    return $this->monerisResult;
  }

  public function getErrors()
  {
    return $this->errors;
  }
}
