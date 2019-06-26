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
      $routeName = '<front>';
      $routeParameters = [];
      $destination = Url::fromUserInput(Drupal::destination()->get());
      if ($destination->isRouted() && $destination->getRouteName() != Drupal::routeMatch()->getRouteName()) {
        $routeName = $destination->getRouteName();
        $routeParameters = $destination->getRouteParameters();
      }
      $this->customerManager->reset();
      Drupal::messenger()->addWarning($this->t("You must fill out the Pay Bills form before proceeding to checkout."));
      return $this->redirect($routeName, $routeParameters);
    }

    return [
      '#theme' => 'yqb_payments_checkout',
      '#customer' => $this->customerManager->all()
    ];
  }
}
