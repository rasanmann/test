<?php
if (!isset($_SERVER['PHP_AUTH_USER']) || (isset($_SERVER['PHP_AUTH_USER']) && !($_SERVER['PHP_AUTH_USER'] === 'yqb-tools' && $_SERVER['PHP_AUTH_PW'] === '8So-;C1DZ&W}L&z'))) {
    header('WWW-Authenticate: Basic realm="YQB"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
} else {
    $json = file_get_contents('YQB.json.postman_collection');

    $data = str_replace('api.yqb.dev', 'api-yqb.bureau300.com', $json);

    header('Content-type: application/json');
    echo $data;
}
