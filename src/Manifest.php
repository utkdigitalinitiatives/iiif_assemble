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

        $mods = $this->getDatastream($pid, 'MODS');

        print json_encode($mods);

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

    private function getDatastream($pid, $datastream)
    {

        // add this to a .env
        $fedora_url = $_ENV['FEDORA_URL'];
        $username = $_ENV['FEDORA_USER'];
        $password = $_ENV['FEDORA_PASS'];

        // build this dynamically based on dsid and pid
        $object = implode('%3A', $pid);

        // build the request
        $request = $fedora_url . '/objects/';
        $request .= $object;
        $request .= '/datastreams/';
        $request .= $datastream;
        $request .= '/content?format=xml';

        $curl = new Curl();
        $curl->verbose();
        $curl->setBasicAuthentication($username, $password);
        $curl->get($request);

        if ($curl->error) {
            $response = $curl->errorCode . ': ' . $curl->errorMessage;
        } else {
            $response = $curl->response;
        }

        $curl->close();

        return $response;
    }
}

?>
