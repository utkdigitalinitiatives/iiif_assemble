<?php

require "../run.php";

use Src\MetadataCollection;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

// the metadata_field must be one of: contributor, subject
if (isset($uri[3])) {
    $metadata_field = $uri[3];
}

// the metdata_value must be a string
if (isset($uri[4])) {
    $metadata_value = $uri[4];
}

if (isset($metadata_field) && isset($metadata_value)) {

    $requestMethod = $_SERVER["REQUEST_METHOD"];

    $controller = new MetadataCollection($requestMethod, $metadata_field, $metadata_value);
    $controller->processRequest();

} else {

    header("HTTP/1.1 404 Not Found");
    exit();

}
