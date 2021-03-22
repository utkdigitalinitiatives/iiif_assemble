<?php

namespace Src;

class IIIF {

    private $xml;
    private $model;
    private $id;

    public function __construct($xml, $model)
    {
        $this->xml = $xml;
        $this->model = $model;
        $this->id = 'https//:' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

    }

    public function buildManifest () {

        $manifest = self::initManifest();

        return json_encode($manifest);

    }

    public function initManifest () {

        $document['@context'] = 'http://iiif.io/api/presentation/3/context.json';
        $document['id'] = $this->id;
        $document['type'] = 'Manifest';

        return $document;

    }

    public static function buildLabel ($mods) {

    }

}

?>
