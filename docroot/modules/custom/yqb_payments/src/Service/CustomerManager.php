<?php

namespace Drupal\yqb_payments\Service;

use Symfony\Component\HttpFoundation\Session\Session;

class CustomerManager
{
  const SESSION_KEY = 'yqb_payments.payment_form.values';

  protected $session;

  protected $values = [];

  public function __construct(Session $session)
  {
    $this->session = $session;
    $this->values = $this->session->get(static::SESSION_KEY, []);
  }

  public function save()
  {
    $this->session->set(static::SESSION_KEY, $this->values);
  }

  public function reset()
  {
    $this->values = [];
    $this->session->remove(static::SESSION_KEY);
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

  public function get($key, $default = '')
  {
    return isset($this->values[$key]) ? $this->values[$key] : $default;
  }

  public function set($key, $value)
  {
    $this->values[$key] = $value;

    return $this;
  }

  public function all()
  {
    return $this->values;
  }
}
