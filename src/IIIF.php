<?php

namespace Src;

class IIIF {

    private $pid;
    private $xpath;
    private $model;
    private $url;

    public function __construct($pid, $mods, $model)
    {

        $this->pid = $pid;
        $this->xpath = new XPath($mods->asXml());
        $this->model = $model;

        $this->url = Utility::getBaseUrl();

    }

    public function buildPresentation ()
    {

        $manifest['@context'] = 'https://iiif.io/api/presentation/3/context.json';
        $manifest['id'] = $this->url . $_SERVER["REQUEST_URI"];
        $manifest['type'] = 'Manifest';
        $manifest['label'] = self::getLanguageArray($this->xpath->query('titleInfo[not(@*)]'));
        $manifest['summary'] = self::getLanguageArray($this->xpath->query('abstract'));
        $manifest['metadata'] = self::buildMetadata();
        $manifest['rights'] = self::buildRights();
        $manifest['provider'] = self::buildProvider();
        $manifest['thumbnail'] = self::buildThumbnail('TN', array(200, 200));

        return json_encode($manifest);

    }

    public function buildMetadata () {

        $alternativeTitle = $this->xpath->query('titleInfo[@type="alternative"]');
        $identifier = $this->xpath->query('identifier');
        $tableOfContents = $this->xpath->query('tableOfContents');
        $date = $this->xpath->query('dateCreated');

        $metadata = array(
            'Alternative Title' => $alternativeTitle,
            'Publication Identifier' => $identifier,
            'Table of Contents' => $tableOfContents,
            'Date' => $date
        );

        return self::validateMetadata($metadata);

    }

    public function validateMetadata ($array) {

        $sets = array();

        foreach ($array as $label => $value) :
            if ($value !== null) :
                $sets[] = self::getLabelValuePair(
                    $label,
                    $value
                );
            endif;
        endforeach;

        return (object) $sets;

    }

    public function buildRights () {

        $accessCondition = $this->xpath->queryElement('accessCondition');

        foreach ($accessCondition as $node) :
            foreach ($node->attributes as $attribute) :
                if ($attribute->nodeName === 'xlink:href')
                    $rights = $attribute->nodeValue;
            endforeach;
        endforeach;

        if (isset($rights)) :
            return $rights;
        else :
            return null;
        endif;

    }

    public function buildProvider () {

        return null;

    }

    public function buildThumbnail ($dsid, $size) {

        $uri = $this->url . '/iiif/2/';
        $uri .= 'collections~islandora~object~' . $this->pid;
        $uri .= '~datastream~' . $dsid;
        $uri .= '~view/full/!' . $size[0] . ',' . $size[1];
        $uri .= '/1/default.jpg';

        $format = "image/jpeg";

        return [
            (object) [
                'id' => $uri,
                'type' => "Image",
                'format' => $format,
                'width' => $size[0],
                'height' => $size[1]
            ]
        ];

    }

    public function getLabelValuePair ($label, $value) {

        if ($value !== null) {
            return (object) [
                'label' => self::getLanguageArray($label),
                'value' => self::getLanguageArray($value)
            ];
        } else {
            return null;
        }

    }

    public function getLanguageArray ($string, $language = 'en') {

        return (object) [
            $language => $string
        ];

    }

}

?>
