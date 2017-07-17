<?php
namespace Drupal\moneris\Connector;

use Moneris;

/**
 * Class MonerisConnector
 * @package Drupal\moneris\Connector
 */
class MonerisConnector {
  public $apiKey = null;
  public $storeId = null;

  private $api = null;

  public function __construct() {
    $config = \Drupal::config('moneris.settings');

    $this->apiKey =  $config->get('moneris.api_key');
    $this->storeId =  $config->get('moneris.store_id');
    $this->apiUrl =  $config->get('moneris.api_url');
    $this->environment =  $config->get('moneris.environment');

    $this->api = Moneris::create([
      'api_key' => $this->apiKey,
      'store_id' => $this->storeId,
      'environment' => $this->environment
    ]);
  }

  /**
   * @param $params
   * @return \Moneris_Result
   */
  public function verify($params) {
    $verification = $this->api->verify($params);

    return $verification;
  }

  /**
   * @param $params
   * @return \Moneris_Result
   */
  public function preauth($params) {
    $preauthorization = $this->api->preauth($params);

    return $preauthorization;
  }

  /**
   * @param $params
   * @return \Moneris_Result
   */
  public function purchase($params) {
    $purchase = $this->api->purchase($params);

    return $purchase;
  }

  /**
   * @param $transaction_number
   * @param null $order_id
   * @param null $amount
   * @return \Moneris_Result
   */
  public function refund($transaction_number, $order_id = null, $amount = null) {
    $refund = $this->api->refund($transaction_number, $order_id, $amount);

    return $refund;
  }

  /**
   * @param $transaction_number
   * @param null $order_id
   * @return \Moneris_Result
   */
  public function void($transaction_number, $order_id = null) {
    $void = $this->api->void($transaction_number, $order_id);

    return $void;
  }
}
