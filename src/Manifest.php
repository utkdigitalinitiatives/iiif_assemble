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
        $this->persistentIdentifier = $persistentIdentifier;

    }

    public function processRequest()
    {

        switch ($this->requestMethod) {
            case 'GET':
                $response = self::theManifest();
                break;
            default:
                $response = self::noFoundResponse();
                break;
        }

        print $response;

    }

    private function theManifest()
    {
        if (self::manifestAvailable()) {
            $manifest = self::getManifest();
        } else {
            $manifest = self::buildManifest();
        }

        return $manifest;

    }

    private function buildManifest()
    {

        $persistentIdentifier = implode('%3A', $this->persistentIdentifier);
        $object = Request::getObjects($persistentIdentifier);

        if ($object['status'] === 200) :
            $mods = Request::getDatastream('MODS', $persistentIdentifier);
        else :
            return json_encode($object);
        endif;

        if ($mods['status'] === 200) :
            $iiif = new IIIF($persistentIdentifier, $mods['body'], $object);
            $presentation = $iiif->buildPresentation();
            self::cacheManifest($presentation);
            return $presentation;
        else :
            return json_encode($mods);
        endif;

    }

    private function manifestAvailable ()
    {

        $namespace = self::getNamespacePath();
        $filename = self::getManifestPath($namespace) . '/manifest.json';
        $expires = 15552000;

        if (isset($_GET['update']) && $_GET['update'] === '1') {
            return false;
        } else if (file_exists($filename)) {
            if (time() < filemtime($filename) + $expires) :
                return true;
            else :
                return false;
            endif;
        } else {
            return false;
        }

    }

    private function getManifest ()
    {

        $namespace = self::getNamespacePath();
        $filename = self::getManifestPath($namespace) . '/manifest.json';

        return file_get_contents($filename);

    }

    private function cacheManifest ($manifest)
    {

        $namespace = self::getNamespacePath();
        if (!is_dir($namespace)) {
            mkdir($namespace);
        }

        $id = self::getManifestPath($namespace);
        if (!is_dir($id)) {
            mkdir($id);
        }

        $path = $id . '/manifest.json';

        file_put_contents($path, $manifest);

        return true;

    }

    private function noFoundResponse()
    {

        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;

        return $response;

    }

    private function getNamespacePath ()
    {

        return '../cache/' . $this->persistentIdentifier[0];

    }


    private function getManifestPath ($container)
    {

        return $container . '/' . $this->persistentIdentifier[1];

    }

}

?>
