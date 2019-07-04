<?php

namespace Drupal\yqb_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\yqb_payments\Service\CustomerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

class PaymentController extends ControllerBase
{
  protected $customerManager;

  protected $config;

  public function __construct(
    CustomerManager $customerManager,
    ConfigFactory $configFactory)
  {
    $this->customerManager = $customerManager;
    $this->config = $configFactory->get('yqb_payments.settings');
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('yqb_payments.customer_manager'),
      $container->get('config.factory')
    );
  }

  public function checkout()
  {
    if (!$this->customerManager->canCheckout()) {
      $url = Url::fromUserInput($this->config->get('payment_page'));
      return $this->redirect($url->getRouteName(), $url->getRouteParameters());
    }

    return [
      '#theme' => 'yqb_payments_checkout',
      '#customer' => $this->customerManager->all()
    ];
  }

  public function success()
  {
    if (!$this->customerManager->successful()) {
      return $this->redirect('yqb_payments.yqb_payment.checkout');
    }

    $output = [
      '#theme' => 'yqb_payments_success',
      '#customer' => $this->customerManager->all(),
      '#receipt' => $this->customerManager->getReceipt(),
      '#entity' => $this->customerManager->getEntity()
    ];

    $this->customerManager->reset();

    return $output;
  }
}
