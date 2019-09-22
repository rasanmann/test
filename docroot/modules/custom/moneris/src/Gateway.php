<?php

namespace Drupal\moneris;

use Drupal;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\moneris\Connector\MonerisConnector;
use SimpleXMLElement;

class Gateway {

  use StringTranslationTrait;

  protected $monerisGateway;

  protected $stateService;

  protected $uuidService;

  protected $logger;

  public function __construct(State $stateService, Php $uuidService, LoggerChannelFactoryInterface $loggerFactory) {
    $this->monerisGateway = new MonerisConnector();
    $this->stateService = $stateService;
    $this->uuidService = $uuidService;
    $this->logger = $loggerFactory->get('moneris');
  }

  public function switchSettings($configuration) {
    $config = Drupal::config($configuration);
    $this->monerisGateway->overrideApi(
      $config->get('moneris.api_key'),
      $config->get('moneris.store_id'),
      $config->get('moneris.api_url'),
      $config->get('moneris.environment')
    );
  }

  /**
   * @throws TransactionException
   */
  public function purchase($token, $orderId, $amount, $email = NULL) {
    $params = [
      'data_key' => $token,
      'order_id' => substr($orderId, 0, 50),
      'amount' => $amount,
      'cust_id' => $this->getUuid($email),
    ];
    $monerisResult = $this->monerisGateway->purchase($params);
    $receiptId = $monerisResult->transaction()->response()->receipt->ReceiptId->__toString();

    if (!$monerisResult->was_successful() || $receiptId == 'null' || $monerisResult->failed_avs() || $monerisResult->failed_cvd()) {
      $this->monerisGateway->void($monerisResult->transaction());
      $errors = $monerisResult->errors();
      $errorDetails = [
        'errors' => $errors,
        'error_code' => $monerisResult->error_code(),
        'error_message' => $monerisResult->error_message(),
        'response' => $monerisResult->response()->__toString(),
      ];
      if (isset($monerisResult->transaction()->response()->receipt) && $monerisResult->transaction()->response()->receipt instanceof SimpleXMLElement) {
        $receiptMessage = $monerisResult->transaction()->response()->receipt->Message->__toString();
        $errorDetails['receipt'] = [
          'ResponseCode' => $monerisResult->transaction()->response()->receipt->ResponseCode->__toString(),
          'ISO' => $monerisResult->transaction()->response()->receipt->ISO->__toString(),
          'AuthCode' => $monerisResult->transaction()->response()->receipt->AuthCode->__toString(),
          'Message' => $receiptMessage,
        ];
        if (!empty($receiptMessage)) {
          $errors[] = $monerisResult->transaction()->response()->receipt->Message->__toString();
        }
      }
      if ($monerisResult->failed_avs()) {
        $errors[] = $this->t('The AVS verification failed.');
      }
      if ($monerisResult->failed_cvd()) {
        $errors[] = $this->t('The CVD verification failed.');
      }
      $this->logger->error('<pre>' . print_r($errorDetails, TRUE) . '</pre>');
      $exception = new TransactionException($this->t('We are unable to process the payment at the moment.'));
      $exception->setErrors($errors);
      $exception->setMonerisResult($monerisResult);
      throw $exception;
    }
    else {
      return $monerisResult;
    }
  }

  public function getUuid($email) {
    $email = empty($email) ? '_empty_' : trim($email);
    $key = 'customer_' . Crypt::hashBase64($email);
    $uuid = $this->stateService->get($key, $this->uuidService->generate());
    $this->stateService->set($key, $uuid);
    return $uuid;
  }
}
