<?php
// node data

$filename = '/absolute/path/to/image.jpg';
$title = 'A node created with services 3.x and REST server';
$body = 'Body lorem ipsum';
$tags = 'TEST_TAG, TEST_TAG2';
$type = 'test'; // node type
$vid = 2; // vid of vocabulary marked as "Tags"

$services_url = 'http://api.yqb.dev/endpoint';

/*
 * Server REST - user.login
 */

// REST Server URL for auth
$request_url = $services_url . '/user/login';

// User data
$user_data = array(
    'username' => 'admin',
    'password' => '7WXhn4LYyQbTP88q',
);

$user_data = http_build_query($user_data);

// cURL
$curl = curl_init($request_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json')); // Accept JSON response
curl_setopt($curl, CURLOPT_POST, 1); // Do a regular HTTP POST
curl_setopt($curl, CURLOPT_POSTFIELDS, $user_data); // Set POST data
curl_setopt($curl, CURLOPT_HEADER, FALSE);  // Ask to not return Header
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Check if login was successful
if ($http_code == 200) {
    // Convert json response as array
    $logged_user = json_decode($response);
}
else {
    // Get error msg
    $http_message = curl_error($curl);
    die('Auth error ' . $http_message);
}

// Define cookie session
$cookie_session = $logged_user->session_name . '=' . $logged_user->sessid;

/*
 * Server REST - file.create
 */

if(!file_exists($filename)) {
    die('File not exists');
}

if(!is_readable($filename)) {
    die('File not readable');
}

// file
$file = array(
    'filesize' => filesize($filename),
    'filename' => basename($filename),
    'file' => base64_encode(file_get_contents($filename)),
    'uid' => $logged_user->user->uid,
);

$file = http_build_query($file);

// REST Server URL for file upload
$request_url = $services_url . '/file';

// cURL
$curl = curl_init($request_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
curl_setopt($curl, CURLOPT_POST, 1); // Do a regular HTTP POST
curl_setopt($curl, CURLOPT_POSTFIELDS, $file); // Set POST data
curl_setopt($curl, CURLOPT_HEADER, FALSE);  // Ask to not return Header
curl_setopt($curl, CURLOPT_COOKIE, "$cookie_session"); // use the previously saved session
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Check if login was successful
if ($http_code == 200) {
    // Convert json response as array
    $file_data = json_decode($response);
}
else {
    // Get error msg
    $http_message = curl_error($curl);
    die('Sending file error' . $http_message . "\n");
}

// file id (nessesary for node)
$fid = $file_data->fid;

/*
 * Server REST - node.create
 */

// REST Server URL
$request_url = $services_url . '/node';


// Node data
$node_data = array(
    'title' => $title,
    'type' => $type,
    'body' => $body,
    'taxonomy[tags][' . $vid . ']' => $tags,
    'field_main_image[]' => array('fid' => $fid, 'list' => 1, 'data' => NULL),
);
$node_data = http_build_query($node_data);


// cURL
$curl = curl_init($request_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json')); // Accept JSON response
curl_setopt($curl, CURLOPT_POST, 1); // Do a regular HTTP POST
curl_setopt($curl, CURLOPT_POSTFIELDS, $node_data); // Set POST data
curl_setopt($curl, CURLOPT_HEADER, FALSE);  // Ask to not return Header
curl_setopt($curl, CURLOPT_COOKIE, "$cookie_session"); // use the previously saved session
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Check if login was successful
if ($http_code == 200) {
    // Convert json response as array
    $node = json_decode($response);
}
else {
    // Get error msg
    $http_message = curl_error($curl);
    die('Getting data error' . $http_message . "\n");
}

print_r($node);

