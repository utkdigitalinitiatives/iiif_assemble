<?php

namespace Src;

error_reporting(E_ALL);

class Manifest
{

    private $requestMethod;
    private $persistentIdentifier;

    public function __construct($requestMethod, $persistentIdentifier)
    {

        $this->requestMethod = $requestMethod;
        $this->persistentIdentifier = implode('%3A', $persistentIdentifier);

    }

    public function processRequest()
    {

        switch ($this->requestMethod) {
            case 'GET':
                $response = self::getManifest();
                break;
            default:
                $response = self::noFoundResponse();
                break;
        }

        print $response;

    }

    private function getManifest()
    {

        $contentModel = Request::getObjectModels($this->persistentIdentifier);

        if ($contentModel['status'] === 200) :
            $mods = Request::getDatastream('MODS', $this->persistentIdentifier);
        else :
            return json_encode($contentModel);
        endif;

        if ($mods['status'] === 200) :
            $iiif = new IIIF($this->persistentIdentifier, $mods['body'], $contentModel);
            return $iiif->buildManifest();
        else :
            return json_encode($mods);
        endif;

    }

    private function noFoundResponse()
    {

        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;

        return $response;

    }

}

?>
