<?php
namespace Drupal\data_exchange_layer\Connector;

/**
 * Class DataExchangeLayerConnector
 * @package Drupal\data_exchange_layer\Connector
 */
class DataExchangeLayerConnector {
  public $email = null;
  public $apiKey = null;
  public $authURL = null;
  public $apiURL = null;

  public $token = null;

  public function __construct($debug = false) {
    $config = \Drupal::config('data_exchange_layer.settings');

    $this->authURL =   $config->get('data_exchange_layer.auth_url');
    $this->apiURL =   $config->get('data_exchange_layer.api_url');
    $this->email =  $config->get('data_exchange_layer.email');
    $this->apiKey =  $config->get('data_exchange_layer.api_key');
  }

  /**
   * Prepares a cURL request
   * @param $url
   * @return resource
   */
  private function prepareRequest($url) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    return $ch;
  }

  /**
   * Analyzes the cURL response
   * @param $ch
   * @return mixed
   */
  private function analyzeResponse($ch) {
    $response = json_decode(curl_exec($ch));
    $info = curl_getinfo($ch);

    if ($response && substr($info['http_code'], 0, 1) === '2') {
      return $response;
    } else {
      return false;
    }
  }

  /**
   * Get access token to make calls to the API
   * @return bool
   */
  public function getToken() {
    $ch = $this->prepareRequest(sprintf($this->authURL . '/login'));

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf('email=%s', $this->email));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'WWW-Authenticate: ' . $this->apiKey,
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
    ]);

    $response = $this->analyzeResponse($ch);

    if ($response) return $response->access_token;

    return false;
  }

  /**
   * Gets list of departures at YQB.
   * @param null $from
   * @param null $to
   * @return bool|mixed
   */
  public function getDepartures($from = null, $to = null) {
    $token = $this->getToken();

    if (!$token) return false;

    $from = (!$from) ? date('Y-m-d') : $from;
    $to = (!$to) ? date('Y-m-d', strtotime('+2 day')) : $to;

    $ch = $this->prepareRequest(sprintf($this->apiURL . '/flights/departures?from=%s&to=%s', $from, $to));

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: ' . $token,
      'Accept: application/json',
    ]);

    $response = $this->analyzeResponse($ch);

    if ($response) return $response;

    return false;
  }

  /**
   * Gets list of arrivals at YQB.
   * @param null $from
   * @param null $to
   * @return bool|mixed
   */
  public function getArrivals($from = null, $to = null) {
    $token = $this->getToken();

    if (!$token) return false;

    $from = (!$from) ? date('Y-m-d') : $from;
    $to = (!$to) ? date('Y-m-d', strtotime('+2 day')) : $to;

    $ch = $this->prepareRequest(sprintf($this->apiURL . '/flights/arrivals?from=%s&to=%s', $from, $to));

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: ' . $token,
      'Accept: application/json',
    ]);

    $response = $this->analyzeResponse($ch);

    if ($response) return $response;

    return false;
  }

  /**
   * Gets current wait times at YQB airport.
   * @return bool|mixed
   */
  public function getWaitTime() {
    $token = $this->getToken();

    if (!$token) return false;

    $ch = $this->prepareRequest(sprintf($this->apiURL . '/bpss/statistics/metrics'));

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: ' . $token,
      'Accept: application/json',
    ]);

    $response = $this->analyzeResponse($ch);

    if ($response) return $response;

    return false;
  }
}
