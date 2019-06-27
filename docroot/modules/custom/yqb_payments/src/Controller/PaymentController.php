<?php

namespace Drupal\yqb_payments\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\yqb_payments\Service\CustomerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentController extends ControllerBase
{
  protected $customerManager;

  public function __construct(CustomerManager $customerManager)
  {
    $this->customerManager = $customerManager;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('yqb_payments.customer_manager')
    );
  }

  public function checkout()
  {
    if (!$this->customerManager->canCheckout()) {
      // @todo redirect to paiement page with error
    }

    return [
      '#theme' => 'yqb_payments_checkout',
      '#customer' => $this->customerManager->all()
    ];
  }

  public function success()
  {
    if (!$this->customerManager->successful()) {
      //@todo
    }

    return [
      '#theme' => 'yqb_payments_success',
      '#customer' => $this->customerManager->all(),
      '#receipt' => $this->customerManager->getReceipt()
    ];
  }
}
