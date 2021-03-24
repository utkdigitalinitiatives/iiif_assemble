<?php

namespace Src;

class IIIF {

    private $xpath;
    private $model;
    private $id;

    public function __construct($mods, $model)
    {

        $this->xpath = new XPath($mods->asXml());
        $this->model = $model;
        $this->id = 'https//:' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

    }

    public function buildManifest ()
    {

        $manifest['@context'] = 'https://iiif.io/api/presentation/3/context.json';
        $manifest['id'] = $this->id;
        $manifest['type'] = 'Manifest';
        $manifest['label'] = self::getLanguageArray($this->xpath->query('titleInfo[not(@*)]'));
        $manifest['summary'] = self::getLanguageArray($this->xpath->query('abstract'));
        $manifest['metadata'] = self::buildMetadata();

        return json_encode($manifest);

    }

    public function buildMetadata () {

        $alternativeTitle = self::getLabelValuePair(
            'Alternative Title',
            $this->xpath->query('titleInfo[@type="alternative"]')
        );

        $identifier = self::getLabelValuePair(
            'Publication Identifier',
            $this->xpath->query('identifier')
        );

        $tableOfContents = self::getLabelValuePair(
            'Table of Contents',
            $this->xpath->query('tableOfContents')
        );

        $date = self::getLabelValuePair(
            'Date',
            $this->xpath->query('dateCreated')
        );

        return (object) [
            $alternativeTitle,
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
