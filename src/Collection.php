<?php

namespace Src;

error_reporting(E_ALL);

class Collection
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
                $response = self::theCollection();
                break;
            default:
                $response = self::noFoundResponse();
                break;
        }

        print $response;

    }

    private function theCollection()
    {

        if (self::collectionAvailable()) {
            $collection = self::getCollection();
        } else {
            $collection = self::newCollection();
        }

        return $collection;

    }

    private function newCollection ()
    {

        $persistentIdentifier = implode('%3A', $this->persistentIdentifier);
        $object = Request::getObjects($persistentIdentifier);

        if ($object['status'] === 200) :
            $model = simplexml_load_string($object['body'])->objModels->model;
            if (self::isCollection($model)) :
                $items = Request::getCollectionItems($persistentIdentifier, 'csv');
                $mods = Request::getDatastream('MODS', $persistentIdentifier);
                $collection = Utility::orderCollection($items['body']);
                $iiif = new IIIF($persistentIdentifier, $mods['body'], $collection, "info:fedora/islandora:collectionCModel");
                $iiifCollection = $iiif->buildCollection();
                self::cacheCollection($iiifCollection);
                return $iiifCollection;
            else :
                $object['body'] = 'Object ' . str_replace('%3A', ':', $persistentIdentifier) . ' is not of object model islandora:collectionCModel.';
                return json_encode($object);
            endif;
        else :
            return json_encode($items);
        endif;

    }

    private static function isCollection ($islandoraModel) {

        $model = Utility::xmlToArray($islandoraModel);

        if (in_array('info:fedora/islandora:collectionCModel', $model)) :
            return true;
        else:
            return false;
        endif;

    }

    private function collectionAvailable ()
    {

        $namespace = self::getNamespacePath();
        $filename = self::getCollectionPath($namespace) . '/collection.json';
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

    private function getCollection ()
    {

        $namespace = self::getNamespacePath();
        $filename = self::getCollectionPath($namespace) . '/collection.json';

        return file_get_contents($filename);

    }

    private function cacheCollection($manifest)
    {

        $namespace = self::getNamespacePath();
        if (!is_dir($namespace)) {
            mkdir($namespace);
        }

        $id = self::getCollectionPath($namespace);
        if (!is_dir($id)) {
            mkdir($id);
        }

        $path = $id . '/collection.json';

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


    private function getCollectionPath ($container)
    {

        return $container . '/' . $this->persistentIdentifier[1];

    }

}

?>
