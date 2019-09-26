<?php
namespace Drupal\advam\Connector;

/**
 * Class AdvamConnector
 * @package Drupal\advam\Connector
 */
class AdvamConnector {
  public $apiURL = null;
  public $apiKey = null;
  public $airportCode = null;
  public $languageCode = null;

  public $token = null;

  public $lastRequest = null;
  public $lastRequestInfo = null;

  private $ch = null;

  public function __construct($debug = true) {
    $config = \Drupal::config('advam.settings');

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $this->languageCode = ($language === 'fr') ? 'fr' : 'en';

    $this->apiURL =   $config->get('advam.api_url');
    $this->apiKey =  $config->get('advam.api_key');
    $this->airportCode =  $config->get('advam.airport_code');

    $this->isLegacy =  $config->get('advam.use_legacy');

    // cURL initialization
    $this->ch = curl_init();

    curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Accept: application/json',
    ]);
  }

  /**
   * Search for Extras by Airport
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Extras/Extras_Get
   * @return mixed
   */
  public function getExtras() {
    list($response, $info) = $this->sendRequest('/extras');

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Get Details for car parks
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Products/Products_Get_0
   * @param $extraId
   * @return mixed
   */
  public function getCarParks() {
    list($response, $info) = $this->sendRequest('/carparks');

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Get Details for a Specific Extra
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Products/Products_Get_0
   * @param $extraId
   * @return mixed
   */
  public function getCarPark($carParkId) {
    list($response, $info) = $this->sendRequest(sprintf('/carparks/%d', $carParkId));

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Get Details for a Specific Extra
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Products/Products_Get_0
   * @param $extraId
   * @return mixed
   */
  public function getExtra($extraId) {
    list($response, $info) = $this->sendRequest(sprintf('/extras/%d', $extraId));

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Add Extra Individual Unconfirmed Booking Item
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_PostExtra
   * @param $guid
   * @param $extra
   * @return mixed
   * @return mixed
   */
  public function addExtra($guid, $extra) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/items/extras', $guid), 'POST', null, [
      'extra' => $extra
    ]);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Search for Products by Airport/Carpark
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Products/Products_Get
   * @return mixed
   */
  public function getProducts() {
    list($response, $info) = $this->sendRequest('/products');

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Get Details for a Specific Product
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Products/Products_Get_0
   * @param $productId
   * @return mixed
   */
  public function getProduct($productId) {
    list($response, $info) = $this->sendRequest(sprintf('/products/%d', $productId));

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Search for Booking By Email and Reference
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_SearchBooking
   * @return mixed
   */
  public function getBooking($guid) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s', $guid));

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Update a Booking
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_PutBooking
   * @param $guid
   * @param array $data
   * @return bool
   */
  public function updateBooking($guid, $data = []) {
    $payload = [
        'booking' => $data
    ];

    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s', $guid), 'PUT', null, $payload);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Search for Booking By Email and Reference
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_SearchBooking
   * @return mixed
   */
  public function searchBooking($email, $reference) {
    list($response, $info) = $this->sendRequest('/bookings', 'GET', null, [
      'email' => $email,
      'reference' => $reference,
    ]);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Get Confirmation Email Content For Booking
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_GetConfirmationEmail
   * @param $guid
   * @return mixed
   */
  public function getBookingConfirmation($guid) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/confirmation', $guid), 'GET');

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Initially Reserve Booking With Specific Items Collection
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_PostBooking
   * @param $data
   * @return mixed
   */
  public function createBooking($data) {
    $data = array_merge([
        'airportCode' => $this->airportCode
    ], $data);

    list($response, $info) = $this->sendRequest('/bookings', 'POST', null, $data);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Initialize confirmation
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_PostInitializeConfirmation
   * @param $guid
   * @return mixed
   */
  public function initializeBookingConfirmation($guid) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/confirmation/initialize', $guid), 'POST');

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Confirm Booking
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_PostConfirmation
   * @param $data
   * @return mixed
   */
  public function confirmBooking($data) {
    $payload = [
      "booking" => [
        "title" => null,
        "firstName" => $data['firstName'],
        "lastName" => $data['lastName'],
        "phone" => $data['phone'],
        "mobilePhone" => null,
        "email" => $data['email'],
        "addressLine1" => $data['addressLine1'],
        "addressLine2" => null,
        "addressLine3" => null,
        "addressLine4" => null,
        "postcode" => $data['postcode'],
        "town" => $data['town'],
        "county" => null,
        "countryId" => 0,
        "vehicleRegistrationNumber" => null,
        "vehicleMake" => null,
        "vehicleModel" => null,
        "vehicleColour" => null,
        "remarks" => null,
        "paymentReference" => null,
        "thirdPartyReference" => null,
        "carrier" => null,
        "destination" => null,
        "reasonForTravel" => 0,
        "isReceiveMarketing" => $data['isReceiveMarketing'],
        "emailOptIn" => false,
        "privacyOptIn" => true,
        "membershipNumber" => null,
        "inboundFlight" => null,
        "outboundFlight" => null,
        "accessIdentifier" => null,
        "accessMethod" => 0,
        "awardSchemeId" => null,
        "awardMemberNumber" => null,
        "customFieldValues" => null,
      ],
      "payment" => [
        "paymentProvider" => $data['paymentProvider'],
        "authCode" => $data['authCode'],
        "authMessage" => $data['authMessage'],
        "responseCode" => $data['responseCode'],
        "transactionCode" => $data['transactionCode'],
        "transactionRef" => $data['transactionRef'],
        "paymentReference" => $data['paymentReference'],
        "cardType" => $data['cardType'],
        "cardNumber" => $data['cardNumber'],
        "cardExpiryDate" => $data['cardExpiryDate'],
        "paymentValue" => $data['paymentValue'],
        "paymentType" => null,
        "merchantAccount" => null,
        "entryCardNumbers" => null,
      ]
    ];

    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/confirmation', $data['guid']), 'POST', null, $payload);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Initially Reserve Booking With Specific Items Collection
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_PostBooking
   * @param $data
   * @return mixed
   */
  public function getParkingAvailability($data) {
    $data = array_merge([
      'airportCode' => $this->airportCode,
    ], $data);

    list($response, $info) = $this->sendRequest('/services/parkingavailability', 'GET', null, $data);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Initially Reserve Booking With Specific Items Collection
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings/Bookings_PostBooking
   * @param $data
   * @return mixed
   */
    public function getExtraAvailability($data) {
    $data = array_merge([
      'airportCode' => $this->airportCode,
    ], $data);

    list($response, $info) = $this->sendRequest('/services/extrasavailability', 'GET', null, $data);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  public function getTermsAndConditions($termsAndConditionsId) {
    list($response, $info) = $this->sendRequest(sprintf('/termsandconditions/%s', $termsAndConditionsId), 'GET');

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }

  }

  public function getBookingCancellation($guid) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/cancellation', $guid));

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Modify a booking
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings//Bookings_PostCancellation
   * @param $guid
   * @return mixed
   */
  public function modifyBooking($guid, $data) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/modification', $guid), 'POST', null, $data);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Cancel a booking
   * More : http://yqbv5-api.preprod.inventiveit.net/swagger/ui/index#!/Bookings//Bookings_PostCancellation
   * @param $guid
   * @return mixed
   */
  public function cancelBooking($guid) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/cancellation', $guid), 'POST');

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Update booking fees
   * More : http://api-altitude.aeroportdequebec.com/swagger/ui/index#!/Bookings/Bookings_PostUpdateBookingFees
   * @param $guid
   * @return mixed
   */
  public function confirmCancellationRefund($guid) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/cancellation/refundconfirmation', $guid), 'POST');

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /**
   * Update booking fees
   * More : http://api-altitude.aeroportdequebec.com/swagger/ui/index#!/Bookings/Bookings_PostUpdateBookingFees
   * @param $guid
   * @return mixed
   */
  public function confirmModificationRefund($guid, $amount) {
    list($response, $info) = $this->sendRequest(sprintf('/bookings/%s/modification/refund', $guid), 'POST', null, [
        'refunded' => true,
        'amount' => $amount
    ]);

    if ($info['http_code'] !== 200) {
      return false;
    } else {
      return $response;
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Utilities
  |--------------------------------------------------------------------------
  */

  /**
   * Sends cURL request to API
   * @param $url
   * @param string $method
   * @param array $params
   * @param array $data
   * @return array
   */
  protected function sendRequest($url, $method = 'GET', $params = [], $data = []) {
    $this->lastRequest = null;
    $this->lastRequestInfo = null;

//    curl_reset($this->ch);

    $url = trim($this->apiURL, '/') . '/' . $this->languageCode . $url . '?api_key=' . $this->apiKey;

    if ($method !== 'GET') {
      $dataString = json_encode($data);

      curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($this->ch, CURLOPT_POSTFIELDS, $dataString);

      $headers[] = 'Content-Length: ' . strlen($dataString);
    } else if (!empty($data)) {
//      curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, null);
//      curl_setopt($this->ch, CURLOPT_POSTFIELDS, null);

      $url .= '&' . http_build_query($data);
    }

    curl_setopt($this->ch, CURLOPT_URL, $url);

    $response = json_decode(curl_exec($this->ch));
    $info = curl_getinfo($this->ch);

    $this->lastRequest = $response;
    $this->lastRequestInfo = $info;

    // Log data
    $this->logAndClean(json_encode([$data, $info, $response], JSON_PRETTY_PRINT), date('Y-m-d_H-i-s'), '', 30);

    return [
        $response,
        $info
    ];
  }

  public function errors() {
    return $this->lastRequest->title;
  }

  /**
   * @param $data
   * @param $logDate
   * @param $folder
   * @param int $limit
   */
  public function logAndClean($data, $logDate, $folder, $limit = 50){
    // Log outgoing events and add message to log
    if (!is_dir('public://advam/'.$folder)) {
      mkdir('public://advam/'.$folder);
    }

    if(!empty($folder)) $folder .= '/';

    // Get all log files and order by most recent
    $realPublicPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $files = glob($realPublicPath . '/advam/'.$folder.'*.log');

    $this->cleanLogFiles($files, 10);

    file_put_contents(sprintf('public://advam/'.$folder.'%s_%s.log', $logDate, microtime()), $data);
  }

  /**
   * @param $files
   * @param int $limit
   */
  protected function cleanLogFiles($files, $limit = 10){
    usort($files, function ($a, $b) {
      return filemtime($a) < filemtime($b);
    });

    // Clean up, keep most recent files
    if (count($files) > $limit) {
      $deletes = array_slice($files, $limit - 1);
      foreach ($deletes as $delete) {
        @unlink($delete);
      }
    }
  }
}
