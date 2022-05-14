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

        if (self::collectionAvailable()) {
            $collection = self::getCollection();
        } else {
            $collection = self::newCollection();
        }

        return $collection;

    }

    private function newCollection ()
    {
        $items = Request::getMetadataObjects($this::lookupField(), $this->metadata_value);
        $collection = Utility::orderCollection($items['body']);
        $persistentIdentifier = urlencode(field . "/" . $this->metadata_value);
        $iiif = new IIIF($persistentIdentifier, null, $collection, "info:fedora/islandora:collectionCModel");
        $iiifCollection = $iiif->buildCollection();
        self::cacheCollection($iiifCollection);
        return $iiifCollection;

    }

    private function lookupField (){
        if (in_array('contributor', $this->field)) :
            $field = "http://purl.org/dc/elements/1.1/contributor";
        elseif (in_array('subject', $this->field)) :
            $field = "http://purl.org/dc/elements/1.1/subject";
        elseif (in_array('type', $this->field)) :
            $field = "http://purl.org/dc/elements/1.1/type";
        else :
            $field = "http://purl.org/dc/elements/1.1/type";
        endif;

        return $field;

    }
}

?>
