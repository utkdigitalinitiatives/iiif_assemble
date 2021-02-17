<?php

require "../run.php";

use Src\Manifest;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

// the namespace must be an string
if (isset($uri[3])) {
    $namespace = (string) $uri[3];
}

// the persistentIdentifier PID must be an int
if (isset($uri[4])) {
    $id = (int) $uri[4];
}

if (is_string($namespace) && is_int($id)) {

    $requestMethod = $_SERVER["REQUEST_METHOD"];

    $controller = new Manifest($requestMethod, array($namespace, $id));
    $controller->processRequest();

} else {

    header("HTTP/1.1 404 Not Found");
    exit();

}
