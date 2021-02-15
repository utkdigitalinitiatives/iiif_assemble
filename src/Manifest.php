<?php

namespace Src;

use Src\IIIF;
use Src\FedoraObject;

class Manifest {

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

//        $object = New FedoraObject($pid);
//        $result = New IIIF($object);

        $result = $pid;

        if (!$result) {
            return $this->notFoundResponse();
        }

        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);

        return $response;
    }

    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;
        return $response;
    }
}

?>
