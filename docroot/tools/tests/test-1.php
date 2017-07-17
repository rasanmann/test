<?php

/**
 * Create a token for non-safe REST calls.
 **/
function mymodule_get_csrf_header()
{
    $curl_get = curl_init();
    curl_setopt_array($curl_get, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'http://api.yqb.dev/services/session/token',
    ));
    $csrf_token = curl_exec($curl_get);
    curl_close($curl_get);
    return 'X-CSRF-Token: ' . $csrf_token;
}

$token = mymodule_get_csrf_header();

echo $token . PHP_EOL;

/*
 * Server REST - user.login
 */

// REST Server URL
$request_url = 'http://api.yqb.dev/endpoint/user/login';

// User data
$user_data = array(
    'username' => 'admin',
    'password' => '7WXhn4LYyQbTP88q',
);
//$user_data = http_build_query($user_data);

// cURL
$curl = curl_init($request_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-type: application/json', $token)); // Accept JSON response
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST'); // Do a regular HTTP POST
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($user_data)); // Set POST data
curl_setopt($curl, CURLOPT_HEADER, FALSE);  // Ask to not return Header
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
curl_setopt($curl, CURLOPT_VERBOSE, true);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Check if login was successful
if ($http_code == 200) {
    echo 'LOGGED IN' . PHP_EOL;

    var_dump($response);

    // Convert json response as array
    $logged_user = json_decode($response);
} else {
    echo 'NOT LOGGED IN' . PHP_EOL;

    // Get error msg
    $http_message = curl_error($curl);
    die($http_message);
}


/*
 * Server REST - node.create
 */

// REST Server URL
$request_url = 'http://api.yqb.dev/endpoint/node';

// Node data
$node_data = array(
    'title' => 'A node created with services 3.x and REST server',
    'type' => 'page',
    'body[und][0][value]' => 'Body',
);
//$node_data = http_build_query($node_data);

// Define cookie session
$cookie_session = $logged_user->name . '=' . $logged_user->id;

echo $cookie_session . PHP_EOL;

// cURL
$curl = curl_init($request_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-type: application/json', $token)); // Accept JSON response
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST'); // Do a regular HTTP POST
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($node_data)); // Set POST data
curl_setopt($curl, CURLOPT_HEADER, FALSE);  // Ask to not return Header
curl_setopt($curl, CURLOPT_COOKIE, "$cookie_session"); // use the previously saved session
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
curl_setopt($curl, CURLOPT_VERBOSE, true);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Check if login was successful
if ($http_code == 200) {
    echo 'CREATED' . PHP_EOL;

    // Convert json response as array
    $node = json_decode($response);
} else {
    echo 'NOT CREATED' . PHP_EOL;

    // Get error msg
    $http_message = curl_error($curl);
    var_dump($http_code);
    var_dump($response);
    die($http_message);
}

print_r($node);
