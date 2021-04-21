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
        $this->model = $model['body']->objModels->model;

        $this->url = Utility::getBaseUrl();

    }

    public function buildPresentation ()
    {
        $id = $this->url . $_SERVER["REQUEST_URI"];

        $manifest['@context'] = ['https://iiif.io/api/presentation/3/context.json'];
        $manifest['id'] = $id;
        $manifest['type'] = 'Manifest';
        $manifest['label'] = self::getLanguageArray($this->xpath->query('titleInfo[not(@type="Alternative")]'), 'value');
        $manifest['summary'] = self::getLanguageArray($this->xpath->query('abstract'), 'value');
        $manifest['metadata'] = self::buildMetadata();
        $manifest['rights'] = self::buildRights();
        $manifest['provider'] = self::buildProvider();
        $manifest['thumbnail'] = self::buildThumbnail(200, 200);
        $manifest['items'] = self::buildItems($id);

        return json_encode($manifest);

    }

    public function buildMetadata () {

        $alternativeTitle = $this->xpath->query('titleInfo[@type="alternative"]');
        $identifier = $this->xpath->query('identifier');
        $tableOfContents = $this->xpath->query('tableOfContents');
        $date = $this->xpath->query('dateCreated');
        $extent = $this->xpath->query('physicalDescription/extent');

        $metadata = array(
            'Alternative Title' => $alternativeTitle,
            'Publication Identifier' => $identifier,
            'Table of Contents' => $tableOfContents,
            'Date' => $date,
            'Extent' => $extent
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
                        "service" => [
                            (object) [
                                '@id' => 'https://utkdigitalinitiatives.github.io/iiif-level-0/ut_libraries_centered/info.json',
                                '@context' => ['http://iiif.io/api/image/2/context.json'],
                                'profile' => 'http://iiif.io/api/image/2/level0.json',
                            ]
                        ]
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

        return $item;

    }

    public function getThumbnailDatastream () {

        $model = Utility::xmlToArray($this->model);

        if (in_array('info:fedora/islandora:sp-audioCModel', $model)) :
            $id = 'TN';
        elseif (in_array('info:fedora/islandora:sp_videoCModel', $model)) :
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

        $canvas = $uri . '/canvas';

        return [
            (object) [
                "id" => $canvas,
                "type" => 'Canvas',
                "height" => 1000,
                "width" => 1000,
//                "duration" => 500,
                "items" => [self::preparePage($canvas)]
            ]
        ];

    }

    public function preparePage ($target) {

        $page = $target . '/page';

        return (object) [
            "id" => $page,
            "type" => 'AnnotationPage',
            "items" => [
                (object) [
                    "id" => $page . '/annotation',
                    "type" => 'Annotation',
                    "motivation" => "painting",
                    "body" =>
                        (object) [
                            self::paintCanvas(),
                        ],
                    "target" => $target
                ]
            ]
        ];

    }

    public function getItemBody ($primary, $fallback) {

        if (Request::responseStatus($primary)) :
            $response = Request::responseBody($primary);
            $body['id'] = $response->{'@id'} . '/full/full/0/default.jpg';
            $body['type'] = "Image";
            $body['width'] = "1000";
            $body['height'] = "1000";
            $body['format'] = "image/jpeg";
            $body['service'] = (object) [
                '@id' => $response->{'@id'},
                '@context' => $response->{'@context'},
                'profile' => $response->profile,
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

    public function determinePaintingDetails ($model) {

        $item = array();

        $datastream = $this->url . '/collections/islandora/object/' . $this->pid . '/datastream/OBJ';

        if (in_array('info:fedora/islandora:sp_basic_image', $model)) :
            $iiifImage = self::getIIIFImageURI('OBJ');
            $item['id'] = self::getItemBody($iiifImage, $datastream);
            $item['type'] = "Image";
            $item['format'] = "image/jpeg";

        elseif (in_array('info:fedora/islandora:sp_large_image_cmodel', $model)) :
            $iiifImage = self::getIIIFImageURI('OBJ');
            $item = self::getItemBody($iiifImage, $datastream);

        elseif (in_array('info:fedora/islandora:pageCModel', $model)) :
            $iiifImage = self::getIIIFImageURI('OBJ');
            $item = self::getItemBody($iiifImage, $datastream);

        elseif (in_array('info:fedora/islandora:sp-audioCModel', $model)) :
            $item['id'] = $datastream;
            $item['type'] = "Sound";
            $item['width'] = 1000;
            $item['height'] = 1000;
            $item['duration'] = 500;
            $item['format'] = "audio/mpeg";

        elseif (in_array('info:fedora/islandora:sp_videoCModel', $model)) :
            $item['id'] = $datastream;
            $item['type'] = "Video";
            $item['width'] = 1000;
            $item['height'] = 1000;
            $item['duration'] = 500;
            $item['format'] = "video/mp4";

        else :
            $item['id'] = null;
            $item['type'] = null;
            $item['format'] = null;

        endif;

        return $item;

    }

    public function paintCanvas () {

        $model = Utility::xmlToArray($this->model);
        $item = self::determinePaintingDetails($model);

        return $item;
    }

    public function buildStructures () {

        return null;

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

}

?>
