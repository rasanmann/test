<?php

namespace Drupal\moneris;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\moneris\Connector\MonerisConnector;
use Moneris_Result;

class Gateway
{
  use StringTranslationTrait;

  protected $monerisGateway;

  protected $stateService;

  protected $uuidService;

  protected $logger;

  public function __construct(State $stateService, Php $uuidService, LoggerChannelFactoryInterface $loggerFactory)
  {
    $this->monerisGateway = new MonerisConnector();
    $this->stateService = $stateService;
    $this->uuidService = $uuidService;
    $this->logger = $loggerFactory->get('moneris');
  }

  /**
   * @throws TransactionException
   */
  public function purchase($token, $orderId, $amount, $email = null)
  {
    $monerisResult = $this->monerisGateway->purchase([
      'data_key' => $token,
      'order_id' => substr($orderId, 0, 50),
      'amount' => $amount,
      'cust_id' => $this->getUuid($email)
    ]);

    if (!$monerisResult->was_successful()) {
      $errorDetails = [
        'errors' => $monerisResult->errors(),
        'error_code' => $monerisResult->error_code(),
        'error_message' => $monerisResult->error_message(),
        'response' => $monerisResult->response()->__toString()
      ];
      $this->logger->error('<pre>' . print_r($errorDetails, true) . '</pre>');
      $exception = new TransactionException($this->t('We are unable to process the payment at the moment.'));
      $exception->setErrors($monerisResult->errors());
      $exception->setMonerisResult($monerisResult);
      throw $exception;
    }

    return $monerisResult;
  }

  protected function getUuid($email)
  {
    $email = empty($email) ? '_empty_' : trim($email);
    $key = 'customer_' . Crypt::hashBase64($email);
    $uuid = $this->stateService->get($key, $this->uuidService->generate());
    $this->stateService->set($key, $uuid);
    return $uuid;
  }
}
