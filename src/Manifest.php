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

        if ($response['body']) {
            echo $response['body'];
        }
    }

    private function getManifest($pid)
    {
        // add this to a .env
        $fedora_url = "http://localhost:8080/fedora";
        $username = "fedoraAdmin";
        $password = "fedoraAdmin";

        // build this dynamically based on dsid and pid
        $object = implode('%3A', $pid);
        $request = $fedora_url . '/objects/' . $object . '/datastreams/MODS/content?format=xml';

        // note, this above requires a pid of thing:1 to exist

        // make this part of class/method
        $curl = new Curl();
        $curl->verbose();
        $curl->setBasicAuthentication($username, $password);
        $curl->get($request);

        if ($curl->error) {
            echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
        } else {
            $data = $curl->response;
            print json_encode($data);
        }

//        $result = $pid;
//
//        if (!$result) {
//            return $this->notFoundResponse();
//        }

//        $response['status_code_header'] = 'HTTP/1.1 200 OK';
//        $response['body'] = json_encode($result);

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
