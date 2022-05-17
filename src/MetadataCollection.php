<?php

namespace Src;

error_reporting(E_ALL);

class MetadataCollection
{
    private $requestMethod;
    private $persistentIdentifier;

    public function __construct($requestMethod, $field, $metadata_value)
    {

        $this->requestMethod = $requestMethod;
        $this->field = $field;
        $this->metadata_value = $metadata_value;
        $this->persistentIdentifier = urlencode($field . "/" . $this->metadata_value);

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

        $collection = self::newCollection();

        return $collection;

    }

    private function newCollection ()
    {
        $items = Request::getMetadataObjects($this::lookupField(), $this->metadata_value);
        $collection = Utility::orderCollection($items['body']);
        $persistentIdentifier = urlencode($this->field . "/" . $this->metadata_value);
        $iiif = new IIIF($persistentIdentifier, null, $collection, "info:fedora/islandora:collectionCModel");
        $iiifCollection = $iiif->buildMetadataCollection();
        self::cacheCollection($iiifCollection);
        return $iiifCollection;

    }

    private function lookupField (){
        return "http://purl.org/dc/elements/1.1/" . $this->field;

    }

    private function getNamespacePath ()
    {

        return '../cache/' . $this->persistentIdentifier[0];

    }

    private function getCollectionPath ($container)
    {

        return $container . '/' . $this->persistentIdentifier[1];

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
}

?>
