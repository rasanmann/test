<?php

namespace Drupal\moneris;

use Drupal;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\moneris\Connector\MonerisConnector;
use MonerisUnified\mpgHttpsPostStatus;
use MonerisUnified\mpgRequest;
use MonerisUnified\mpgTransaction;

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
  public function purchase($token, $orderId, $amount, $email = NULL, $status = FALSE) {
    $config = Drupal::config('moneris.payment_settings');
    $testMode = ($config->get('moneris.environment') != 'live');

    $siteName = \Drupal::config('system.site')->get('name');
    $params = [
      'type' => 'res_purchase_cc',
      'data_key' => $token,
      'order_id' => strtoupper(substr($orderId, 0, 50)),
      'amount' => $amount,
      'cust_id' => $this->getUuid($email),
      'crypt_type' => 7,
      'dynamic_descriptor' => substr($siteName, 0, 20),
    ];

    $mpgTxn = new mpgTransaction($params);
    $mpgRequest = new mpgRequest($mpgTxn);
    $mpgRequest->setProcCountryCode("CA");
    $mpgRequest->setTestMode($testMode);

    $mpgHttpPost = new mpgHttpsPostStatus($config->get('moneris.store_id'), $config->get('moneris.api_key'), $status, $mpgRequest);
    $mpgResponse = $mpgHttpPost->getMpgResponse();

    $complete = $mpgResponse->getComplete();
    $timeout = $mpgResponse->getTimedOut();

    if ($complete == 'false' && $timeout == 'true' && !$status) {
      return $this->purchase($token, $orderId, $amount, $email, TRUE);
    }

    $responseCode = $mpgResponse->getResponseCode();
    if ($responseCode == 'null' || $responseCode >= 50 || $complete == 'false') {
      $this->logger->error(print_r($mpgResponse->getMpgResponseData(), TRUE));

      if ($responseCode != 'null') {
        $errorMessage = $this->t('An error occured: @code - @message', ['@code' => $responseCode, '@message' => $mpgResponse->getMessage()]);
      }
      else {
        $errorMessage = $this->t('An unknown error occured: @message.', ['@message' => $mpgResponse->getMessage()]);
      }

      $exception = new TransactionException($errorMessage);
      $exception->setMonerisResult($mpgResponse);
      throw $exception;
    }

    return $mpgResponse;
  }

  public function getUuid($email) {
    $email = empty($email) ? '_empty_' : trim($email);
    $key = 'customer_' . Crypt::hashBase64($email);
    $uuid = $this->stateService->get($key, $this->uuidService->generate());
    $this->stateService->set($key, $uuid);
    return $uuid;
  }
}
