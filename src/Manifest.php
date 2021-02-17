<?php

namespace Src;

use Curl\Curl;

error_reporting(E_ALL);

class Manifest
{

    private $requestMethod;
    private $persistentIdentifier;

    public function __construct($requestMethod, $persistentIdentifier)
    {
        $this->requestMethod = $requestMethod;
        $this->persistentIdentifier = $persistentIdentifier;
    }

    public function processRequest()
    {

        switch ($this->requestMethod) {
            case 'GET':
                $response = $this->getManifest($this->persistentIdentifier);
                break;
            default:
                $response = $this->notFoundResponse();
                break;
        }

        header($response['status_code_header']);

        if ($response['body']) {
            echo $response['body'];
        }
    }

    private function getManifest($pid)
    {
        $fedora_url = "http://localhost:8080/fedora";
        $username = "fedoraAdmin";
        $password = "fedoraAdmin";

        $request = $fedora_url . '/objects/thing:1/datastreams/MODS/content?format=xml';

        $curl = new Curl();
        $curl->verbose();
        $curl->setBasicAuthentication('fedoraAdmin', 'fedoraAdmin');
        $curl->get($request);

        $result = $pid;

        if (!$result) {
            return $this->notFoundResponse();
        }

        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);

//        return $response;
    }

    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;
        return $response;
    }
}

?>
