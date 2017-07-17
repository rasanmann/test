<?php

$hostName = (isset($argv[1])) ? $argv[1] : 'yqb.dev';
$flightNumber = (isset($argv[2])) ? $argv[2] : 8080;
$today = date('Y-m-d');
$time = '20:00:00';

// create a new cURL resource
$ch = curl_init();

// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, "https://{$hostName}/webhooks/reminders");
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json"]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{
     "eventKind": "UPDATED",
     "flight": {
         "@type": ".ArrivalFlight",
         "flightUniqueId": "ARR_130043",
         "airline": {
             "iataCode": "AC",
             "icaoCode": "ACA",
             "name": "Air Canada"
         },
         "flightNumber": "' . $flightNumber . '",
         "gate": "29",
         "isInternal": false,
         "codeShares": "UA8256",
         "kind": "DOMESTIC",
         "origin": [
             {
                 "iataCode": "YYZ",
                 "icaoCode": null,
                 "airportCityName": "Toronto",
                 "airportName": "Toronto Pearson International Airport"
             }
         ],
         "arrivalStatus": "On Time",
         "carousel": {
             "startTime": "2016-11-22T10:04:00",
             "endTime": "2016-11-22T10:24:00",
             "name": "B"
         },
         "eta": "' . $today . 'T' . $time .'.000",
         "sta": "' . $today . 'T' . $time .'.000",
         "ata": "' . $today . 'T' . $time .'.000",
         "firstBag": "2016-11-22T10:04:06-05:00",
         "lastBag": null
     },
     "isMajor": true,
     "eventType": "FLIGHT"
 }
');
curl_setopt($ch, CURLOPT_HEADER, 0);

// grab URL and pass it to the browser
$data = curl_exec($ch);

var_dump($data);

curl_setopt($ch, CURLOPT_POSTFIELDS, '{
    "eventKind": "UPDATED",
    "flight": {
        "@type": ".DepartureFlight",
      "flightUniqueId": "DEP_131813",
      "airline": {
        "iataCode": "AC",
        "icaoCode": "ACA",
        "name": "Air Canada"
      },
      "flightNumber": "' . $flightNumber . '",
      "gate": "25",
      "isInternal": false,
      "codeShares": null,
      "kind": "DOMESTIC",
      "destination": [
        {
          "iataCode": "YYZ",
          "icaoCode": null,
          "airportCityName": "Toronto",
          "airportName": "Toronto Pearson International Airport"
        }
      ],
      "departureStatus": "On Time",
      "etd": "' . $today . 'T' . $time .'.000",
      "std": "' . $today . 'T' . $time .'.000",
      "atd": "' . $today . 'T' . $time .'.000",
      "checkInCounters": [
        {
          "startTime": "2017-02-25T03:30:00",
          "endTime": "2017-02-25T20:00:00",
          "name": "100"
        },
        {
          "startTime": "2017-02-25T03:30:00",
          "endTime": "2017-02-25T20:00:00",
          "name": "101"
        },
        {
          "startTime": "2017-02-25T03:30:00",
          "endTime": "2017-02-25T20:00:00",
          "name": "102"
        },
        {
          "startTime": "2017-02-25T03:30:00",
          "endTime": "2017-02-25T05:00:00",
          "name": "103"
        },
        {
          "startTime": "2017-02-25T03:30:00",
          "endTime": "2017-02-25T05:00:00",
          "name": "104"
        }
      ]
    },
    "isMajor": true,
    "eventType": "FLIGHT"
}
');

// grab URL and pass it to the browser
$data = curl_exec($ch);

var_dump($data);

// close cURL resource, and free up system resources
curl_close($ch);