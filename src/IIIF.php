<?php

namespace Src;

class IIIF {

    private $mods;
    private $model;
    private $id;

    public function __construct($mods, $model)
    {

        $this->mods = $mods;
        $this->model = $model;
        $this->id = 'https//:' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

    }

    public function buildManifest () {

//        print_r ($this->mods);

        $manifest['@context'] = 'https://iiif.io/api/presentation/3/context.json';
        $manifest['id'] = $this->id;
        $manifest['type'] = 'Manifest';
        $manifest['label'] = self::getLanguageArray($this->mods->titleInfo->title);
        $manifest['summary'] = self::getLanguageArray($this->mods->abstract);
        $manifest['metadata'] = self::buildMetadata();

        return json_encode($manifest);

    }

    public function buildMetadata () {

        $identifier = self::getLabelValuePair(
            'Publication Identifier',
            $this->mods->identifier
        );

        $tableOfContents = self::getLabelValuePair(
            'Table of Contents',
            $this->mods->tableOfContents
        );

        $date = self::getLabelValuePair(
            'Date',
            $this->mods->originInfo->dateCreated
        );

        return (object) [
            $identifier,
            $tableOfContents,
            $date
        ];

    }

    public function getLabelValuePair ($label, $value) {

        return (object) [
            'label' => self::getLanguageArray($label),
            'value' => self::getLanguageArray($value)
        ];

    }

    public function getLanguageArray ($string, $language = 'en') {

        return (object) [
            $language => $string
        ];

    }

}

?>
