<?php

namespace Src;

use DOMDocument;
use DOMXPath;

class IIIF {

    private $pid;
    private $xpath;
    private $type;
    private $url;

    public function __construct($pid, $mods, $object)
    {

        $model = simplexml_load_string($object['body'])->objModels->model;

        $this->pid = $pid;
        $this->mods = $mods;
        $this->xpath = new XPath($mods);
        $this->type = self::determineTypeByModel($model);

        $this->url = Utility::getBaseUrl();

    }

    public function buildPresentation ()
    {
        $id = $this->url . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]);

        $manifest['@context'] = ['https://iiif.io/api/presentation/3/context.json'];
        $manifest['id'] = $id;
        $manifest['type'] = 'Manifest';
        $manifest['label'] = self::getLanguageArray($this->xpath->query('titleInfo[not(@type="Alternative")]'), 'value');
        $manifest['summary'] = self::getLanguageArray($this->xpath->query('abstract'), 'value');
        $manifest['metadata'] = self::buildMetadata();
        $manifest['rights'] = self::buildRights();
        $manifest['requiredStatement'] = self::buildRequiredStatement();
        $manifest['provider'] = self::buildProvider();
        $manifest['thumbnail'] = self::buildThumbnail(200, 200);
        $manifest['items'] = self::buildItems($id);

        $presentation = self::buildStructures($manifest, $id);

        return json_encode($presentation);

    }

    public function buildMetadata () {

        $metadata = array(
            'Alternative Title' => $this->xpath->query('titleInfo[@type="alternative"]'),
            'Table of Contents' => $this->xpath->query('tableOfContents'),
            'Role Term' => null,
            'Publisher' => null,
            'Date' => $this->xpath->query('dateCreated'),
            'Publication Date' => null,
            'Form' => null,
            'Extent' => $this->xpath->query('physicalDescription/extent'),
            'Topic' => null,
            'Coverage' => null,
            'Time Period' => null,
            'Publication Identifier' => $this->xpath->query('identifier')
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

        return $sets;

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

    public function buildRequiredStatement () {

        return (object) [
            'label' => self::getLanguageArray('Attribution', 'label'),
            'value' => self::getLanguageArray(['University of Tennessee, Knoxville. Libraries'], 'value')
        ];

    }

    public function buildProvider () {

        return [
            (object) [
                "id" => 'https://www.lib.utk.edu/about/',
                "type" => 'Agent',
                "label" => self::getLanguageArray('University of Tennessee, Knoxville. Libraries', 'label'),
                "homepage" => [
                    (object) [
                        "id" => 'https://www.lib.utk.edu/',
                        "type" => 'Text',
                        "label" => self::getLanguageArray('University of Tennessee Libraries Homepage', 'label'),
                        "format" =>  'text/html'
                    ]
                ],
                "logo" => [
                    (object) [
                        "id" => 'https://utkdigitalinitiatives.github.io/iiif-level-0/ut_libraries_centered/full/full/0/default.jpg',
                        "type" => 'Image',
                        "format" =>  'image/jpeg',
                        "width" => 200,
                        "height" => 200
                    ]
                ]
            ]
        ];

    }

    public function buildThumbnail ($width, $height) {

        $item = array();

        $dsid = self::getThumbnailDatastream();
        $iiifImage = self::getIIIFImageURI($dsid);

        if (Request::responseStatus($iiifImage)) :
            $item['id'] = $iiifImage;
        else :
            $item['id'] = $this->url . '/collections/islandora/object/' . $this->pid . '/datastream/' . $dsid;
        endif;

        $item['type'] = "Image";
        $item['format'] = "image/jpeg";
        $item['width'] = $width;
        $item['height'] = $height;

        return [
            $item
        ];

    }

    public function getThumbnailDatastream () {

        if ($this->type === 'Sound') :
            $id = 'TN';
        elseif ($this->type === 'Video') :
            $id = 'TN';
        else :
            $id = 'OBJ';
        endif;

        return $id;

    }

    public function getIIIFImageURI ($dsid) {

        $uri = $this->url . '/iiif/2/';
        $uri .= 'collections~islandora~object~' . $this->pid;
        $uri .= '~datastream~' . $dsid;
        $uri .= '/info.json';

        return $uri;

    }

    public function buildItems ($uri) {

        $canvasId = $uri . '/canvas';

        $canvas = [
            (object) [
                "id" => $canvasId,
                "type" => 'Canvas'
            ]
        ];

        if (in_array($this->type, ['Sound','Video'])) :
            $canvas[0]->height = 640;
            $canvas[0]->width = 360;
            $canvas[0]->duration = self::getDuration();

        else :

            $iiifImage = self::getIIIFImageURI('OBJ');

            if (Request::responseStatus($iiifImage)) :
                $responseImageBody = json_decode(Request::responseBody($iiifImage));
                $canvas[0]->width = $responseImageBody->width;
                $canvas[0]->height = $responseImageBody->height;
            else :
                $canvas[0]->height = 640;
                $canvas[0]->width = 360;
            endif;

        endif;

        $canvas[0]->items = [self::preparePage($canvasId)];

        return $canvas;

    }

    public function preparePage ($target) {

        $page = $target . '/page';

        return (object) [
            "id" => $page,
            "type" => 'AnnotationPage',
            "items" => [
                (object) [
                    "@context" => 'https://iiif.io/api/presentation/3/context.json',
                    "id" => $page . '/annotation',
                    "type" => 'Annotation',
                    "motivation" => "painting",
                    "body" => [self::paintCanvas()],
                    "target" => $target
                ]
            ]
        ];

    }

    public function getItemBody ($primary, $fallback) {

        if (Request::responseStatus($primary)) :
            $response = json_decode(Request::responseBody($primary));
            $body['id'] = $response->{'@id'} . '/full/full/0/default.jpg';
            $body['type'] = "Image";
            $body['width'] = $response->width;
            $body['height'] = $response->height;;
            $body['format'] = "image/jpeg";
            $body['service'] = (object) [
                '@id' => $response->{'@id'},
                '@type' => $response->{'@context'},
                'profile' => $response->profile[0],
            ];
        else :
            $body['id'] = $fallback;
            $body['type'] = "Image";
            $body['width'] = 1000;
            $body['height'] = 1000;
            $body['format'] = "image/jpeg";
        endif;

        return $body;

    }

    public function paintCanvas () {

        $item = array();

        $datastream = $this->url . '/collections/islandora/object/' . $this->pid . '/datastream/OBJ';

        if ($this->type === 'Image') :
            $iiifImage = self::getIIIFImageURI('OBJ');
            $item = self::getItemBody($iiifImage, $datastream);

        elseif ($this->type === 'Sound') :
            $item['id'] = $datastream;
            $item['type'] = "Sound";
            $item['width'] = 640;
            $item['height'] = 360;
            $item['duration'] = self::getDuration();
            $item['format'] = "audio/mpeg";

        elseif ($this->type === 'Video') :
            $item['id'] = $datastream;
            $item['type'] = "Video";
            $item['width'] = 640;
            $item['height'] = 360;
            $item['duration'] = self::getDuration();
            $item['format'] = "video/mp4";

        else :
            $item['id'] = null;
            $item['type'] = null;
            $item['format'] = null;

        endif;

        return $item;
    }

    public function buildStructures ($manifest, $uri) {

        if (is_array($this->xpath->query('extension'))) {

            $doc = new DOMDocument;
            $doc->loadXML($this->mods);
            $pbcore = $doc->getElementsByTagNameNS('http://www.pbcore.org/PBCore/PBCoreNamespace.html', 'pbcorePart');

            if (is_object($pbcore)) :

                $id = $uri . '/range';

                $manifest['structures'] = self::buildRange($pbcore, $id, $uri . '/canvas');

            endif;

        } else {

        }

        return $manifest;

    }

    public function buildRange ($parts, $uri, $canvas) {

        $ranges = [];
        $unique = [];

        foreach ($parts as $index => $part) :

            $partType = $part->getAttribute('partType');

            if ($partType === 'iiif') :

                $label = $part->getElementsByTagNameNS('http://www.pbcore.org/PBCore/PBCoreNamespace.html', 'pbcoreTitle');
                $startTime = $part->getAttribute('startTime');
                $endTime = $part->getAttribute('endTime');

                $partTypeAnnotation = $part->getAttribute('partTypeAnnotation');
                $range = Utility::sanitizeLabel($partTypeAnnotation);

                $unique[$index] = $range;
                $set = array_unique($unique);

                foreach($set as $key => $value) :
                    if ($value === $range) :
                        $rangeKey = $key;
                    endif;
                endforeach;

                $ranges[$rangeKey]['type'] = 'Range';
                $ranges[$rangeKey]['id'] = $uri . '/' . $range;
                $ranges[$rangeKey]['label'] = self::getLanguageArray($partTypeAnnotation, 'label');
                $ranges[$rangeKey]['items'][] = (object) [
                    'type' => 'Range',
                    'id' => $uri . '/' . $range . '/' . $index,
                    'label' => self::getLanguageArray($label[0]->textContent, 'label'),
                    'items' => [
                        (object) [
                            'type' => 'Canvas',
                            'id' => $canvas . '#t=' . $startTime . ',' . $endTime
                        ]
                    ]
                ];

            endif;

        endforeach;

        return array_values($ranges);

    }

    public function getLabelValuePair ($label, $value) {

        if ($value !== null) {
            return (object) [
                'label' => self::getLanguageArray($label, 'label'),
                'value' => self::getLanguageArray($value, 'value')
            ];
        } else {
            return null;

        }

    }

    public function getLanguageArray ($string, $type, $language = 'en') {

        if ($type === 'label') :
            $string = [$string];
        endif;

        return (object) [
            $language => $string
        ];

    }

    private static function getDuration () {
        return 500;
    }

    private static function determineTypeByModel ($islandoraModel) {

        $model = Utility::xmlToArray($islandoraModel);

        if (in_array('info:fedora/islandora:sp_basic_image', $model)) :
            $type = "Image";
        elseif (in_array('info:fedora/islandora:sp_large_image_cmodel', $model)) :
            $type = "Image";
        elseif (in_array('info:fedora/islandora:pageCModel', $model)) :
            $type = "Image";
        elseif (in_array('info:fedora/islandora:sp-audioCModel', $model)) :
            $type = "Sound";
        elseif (in_array('info:fedora/islandora:sp_videoCModel', $model)) :
            $type = "Video";
        else :
            $type = "Image";
        endif;

        return $type;

    }

}

?>
