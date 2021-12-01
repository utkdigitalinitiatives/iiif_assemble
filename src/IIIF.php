<?php

namespace Src;

use DOMDocument;
use DOMXPath;

class IIIF {

    private $pid;
    private $xpath;
    private $object;
    private $type;
    private $url;

    public function __construct($pid, $mods, $object, $model = null)
    {

        if (!$model) {
            $model = simplexml_load_string($object['body'])->objModels->model;
        }

        $this->pid = $pid;
        $this->mods = $mods;
        $this->object = $object;
        $this->xpath = new XPath($mods);
        $this->simplexpath = new SimpleXPath($mods);
        $this->type = self::determineTypeByModel($model);

        $this->url = Utility::getBaseUrl();

    }

    public function buildCollection ()
    {
        $id = $this->url . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]);

        $collection['@context'] = ['https://iiif.io/api/presentation/3/context.json'];
        $collection['id'] = $id;
        $collection['type'] = 'Collection';
        $collection['label'] = self::getLanguageArray($this->xpath->query('titleInfo[not(@type="alternative")]'), 'value');
        $collection['items'] = self::buildCollectionItems();

        return json_encode($collection);

    }

    private function buildCollectionItems ($items = []) {

        foreach ($this->object as $item) {
            $items[] = (object) [
                'id' => $this->url . '/assemble/manifest/' . str_replace(':', '/', $item),
                'type' => 'Manifest',
                'label' => (object) [
                    'none' => [$item]
                    ]
            ];
        }

        return $items;

    }

    public function buildManifest ()
    {
        $id = $this->url . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]);

        $manifest['@context'] = ['https://iiif.io/api/presentation/3/context.json'];
        $manifest['id'] = $id;
        $manifest['type'] = 'Manifest';
        $manifest['label'] = self::getLanguageArray($this->xpath->query('titleInfo[not(@type="alternative")][not(@lang)]'), 'value');
        $manifest['summary'] = self::getLanguageArray($this->xpath->query('abstract[not(@lang)]'), 'value');
        $manifest['metadata'] = self::buildMetadata();
        $manifest['rights'] = self::buildRights();
        $manifest['requiredStatement'] = self::buildRequiredStatement();
        $manifest['provider'] = self::buildProvider();
        $manifest['thumbnail'] = self::buildThumbnail(200, 200);
        $manifest['items'] = self::buildItems($id);
        $manifest['seeAlso'] = self::buildSeeAlso();

        if ($this->type === 'Book') {
            $manifest['behavior'] = ["paged"];
        }

        $presentation = self::buildStructures($manifest, $id);

        return json_encode($presentation);

    }

    public function buildMetadata () {

        $metadata = array(
            'Alternative Title' => $this->xpath->query('titleInfo[@type="alternative"]'),
            'Table of Contents' => $this->xpath->query('tableOfContents'),
            'Publisher' => $this->xpath->query('originInfo/publisher'),
            'Date' => $this->xpath->query('originInfo/dateCreated|originInfo/dateOther'),
            'Publication Date' => $this->xpath->query('originInfo/dateIssued'),
            'Format' => $this->xpath->query('physicalDescription/form[not(@type="material")]'),
            'Extent' => $this->xpath->query('physicalDescription/extent'),
            'Subject' => $this->xpath->query('subject[not(@displayLabel="Narrator Class")]/topic'),
            'Narrator Role' => $this->xpath->query('subject[@displayLabel="Narrator Class"]/topic'),
            'Place' => $this->browse_sanitize($this->xpath->query('subject/geographic')),
            'Time Period' => $this->xpath->query('subject/temporal'),
            'Description' => $this->xpath->query('abstract[not(@lang)]'),
            'Descripción' => $this->xpath->query('abstract[@lang="spa"]'),
            'Título' => $this->xpath->query('titleInfo[@lang="spa"]/title'),
            'Publication Identifier' => $this->xpath->queryFilterByAttribute('identifier', false, 'type', ['issn','isbn']),
            'Browse' => $this->browse_sanitize($this->xpath->query('note[@displayLabel="Browse"]')),
            'Language' => $this->xpath->query('language/languageTerm')
        );
        $metadata_with_names = $this->add_names_to_metadata($metadata);
        return self::validateMetadata($metadata_with_names);

    }

    private function browse_sanitize($value) {
        $sanitize = array(
            'Medical Personnel & First Responders' => 'Medical Personnel and First Responders',
            'Educators and Public or Government officials or employees' => 'Public or Government Employees',
            'Meterologists & Environmentalists' => 'Meterologists and Environmentalists',
            'Disaster Response & Recovery' => 'Disaster Response and Recovery',
            'Arrowmont School of Arts & Crafts' => 'Arrowmont School of Arts and Crafts'
            );
        $finals = array();
        foreach ($value as $thing) {
            if (array_key_exists($thing, $sanitize)) {
                array_push($finals, $sanitize[$thing]);
            }
            else {
                array_push($finals, $thing);
            }
        }
        return $finals;
    }

    private function add_names_to_metadata($current_metadata) {
        $names = $this->simplexpath->get_names();
        foreach ($names as $k => $v) {
            $current_metadata[$k] = $v;
        }
        return $current_metadata;
    }

    public function validateMetadata ($array) {

        $sets = array();
        $spanish_labels = array('spa_sample_1', 'spa_sample_2');

        foreach ($array as $label => $value) :
            if ($value !== null and empty($value) !== true) :
                if (in_array($label, $spanish_labels)) :
                    $lang = 'es';
                else :
                    $lang = 'en';
                endif;
                $sets[] = self::getLabelValuePair(
                    $label,
                    $value,
                    $lang
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

        $providing_institution = $this->xpath->query('recordInfo/recordContentSource');
        return (object) [
            'label' => self::getLanguageArray('Provided by', 'label'),
            'value' => self::getLanguageArray($providing_institution, 'value')
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

    private function buildSeeAlso () {

        return [
            (object) [
                "id" => $this->url . '/collections/islandora/object/' . $this->pid . '/datastream/MODS' ,
                "type" => "Dataset",
                "label" =>
                    (object) [
                        "en" => [ "Bibliographic Description in MODS" ]
                    ]
                ,
                "format" => "application/xml",
                "profile" => "http://www.loc.gov/standards/mods/v3/mods-3-5.xsd"
                ]
        ];
    }

    public function buildThumbnail ($width, $height) {

        $item = array();

        $dsid = self::getThumbnailDatastream();
        $iiifImage = self::getIIIFImageURI($dsid, $this->pid);
        $thumbnail_details = Request::get_thumbnail_details($iiifImage);

        if ($thumbnail_details['is_iiif']) :
            $item['id'] = $thumbnail_details['thumbnail_uri'];
            $item['width'] = $thumbnail_details['width'];
            $item['height'] = $thumbnail_details['height'];
        else :
            $item['id'] = $this->url . '/collections/islandora/object/' . $this->pid . '/datastream/' . $dsid;
            $item['width'] = $width;
            $item['height'] = $height;
        endif;

        if ( $this->type === "Sound" or $this->type === "Video") {
            $item['duration'] = self::getBibframeDuration(self::findProxyDatastream());
        }

        $item['type'] = "Image";
        $item['format'] = "image/jpeg";

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
            $id = 'JP2';
        endif;

        return $id;

    }

    public function getIIIFImageURI ($dsid, $pid) {

        $uri = $this->url . '/iiif/2/';
        $uri .= 'collections~islandora~object~' . $pid;
        $uri .= '~datastream~' . $dsid;
        $uri .= '/info.json';

        return $uri;

    }

    public function buildItems ($uri) {

        if (in_array($this->type, ['Book'])) :

            $items = Request::getBookPages($this->pid, 'csv');

            if ($items['status'] === 200) {

                $canvases = Utility::orderCanvases($items['body']);
                $items = [];

                foreach ($canvases as $key => $canvas) {
                    $items[$key] = $this->buildCanvasWithPages($key, $uri, $canvas);
                }

                return $items;

            } else {

                return null;

            }

        else:

            return [$this->buildCanvas(0, $uri, $this->pid)];

        endif;

    }

    private function findProxyDatastream () {
        if ($this->type == 'Sound'):
            return 'PROXY_MP3';
        else:
            return 'MP4';
        endif;
    }

    public function buildCanvas ($index, $uri, $pid) {

        $canvasId = $uri . '/canvas/' . $index;
        $title = $this->xpath->query('titleInfo[not(@type="alternative")]')[0];
        $canvas = (object) [
                "id" => $canvasId,
                "type" => 'Canvas',
                "label" => self::getLanguageArray($title, 'label', 'none')
            ];

        if (in_array($this->type, ['Sound','Video'])) :

            $canvas->height = 640;
            $canvas->width = 360;
            $canvas->duration = self::getBibframeDuration(self::findProxyDatastream());

        else :

            $iiifImage = self::getIIIFImageURI('JP2', $pid);

            if (Request::responseStatus($iiifImage)) :
                $responseImageBody = json_decode(Request::responseBody($iiifImage));
                $canvas->width = $responseImageBody->width;
                $canvas->height = $responseImageBody->height;
            else :
                $canvas->height = 640;
                $canvas->width = 360;
            endif;

        endif;

        $canvas->items = [self::preparePage($canvasId, $pid)];

        return $canvas;

    }

    public function buildCanvasWithPages ($index, $uri, $canvasData) {
        $canvasId = $uri . '/canvas/' . $index;
        $canvas = (object) [
            "id" => $canvasId,
            "type" => 'Canvas',
            "label" => self::getLanguageArray($canvasData[0]['title'], 'label', 'none')
        ];

        foreach ($canvasData as $key => $data) {
            $iiifImage = self::getIIIFImageURI('JP2', $data['pid']);

            if (Request::responseStatus($iiifImage)) :
                $responseImageBody = json_decode(Request::responseBody($iiifImage));
                $canvas->width = $responseImageBody->width;
                $canvas->height = $responseImageBody->height;
            else :
                $canvas->height = 640;
                $canvas->width = 360;
            endif;

            $canvas->items[$key] = self::preparePage($canvasId, $data['pid'], $key);
        }

        return $canvas;

    }

    private function buildTranscript($language_code, $page, $target) {
        $datastream = $this->url . '/collections/islandora/object/' . $this->pid . '/datastream/';
        if ($language_code == "es") :
            $transcript_datastream = "TRANSCRIPT-ES";
            $transcript_label = "Subtítulos en español";
            $transcript_language = "es";
        else :
            $transcript_datastream = "TRANSCRIPT";
            $transcript_label = "Captions in English";
            $transcript_language = "en";
        endif;
        return (object) [
                "id" => $page . '/' . $this->pid . '/' . uniqid(),
                "type" => 'Annotation',
                "motivation" => "supplementing",
                "body" => [
                    (object) [
                        "id" => $datastream . $transcript_datastream,
                        "type" => "Text",
                        "format" => "text/vtt",
                        "label" =>
                            (object) [
                                $transcript_language => [
                                    $transcript_label
                                ]
                            ],
                        "language"=> $transcript_language
                        ],
                    ],
                "target" => $target
        ];
    }

    private function getTranscipts($pagenumber, $target) {
        $datastreams = $this::getDatastreamIds();
        $transcripts = [];
        if (in_array('TRANSCRIPT', $datastreams)) :
            array_push($transcripts, $this::buildTranscript('en', $pagenumber, $target));
        endif;
        if (in_array('TRANSCRIPT-ES', $datastreams)) :
            array_push($transcripts, $this::buildTranscript('es', $pagenumber, $target));
        endif;
        return $transcripts;
    }

    public function preparePage ($target, $pid, $number = 1) {

        $page = $target . '/page';
        $items = [
            (object) [
                "id" => $page . '/' . $pid . '/' . uniqid(),
                "type" => 'Annotation',
                "motivation" => "painting",
                "body" => [self::paintCanvas($pid)],
                "target" => $target
            ]
        ];
        if (in_array($this->type, ['Sound', 'Video'])) :
            $transcripts = self::getTranscipts($page, $target);
            foreach ($transcripts as &$transcript) :
                array_push($items, $transcript);
            endforeach;
        endif;
        return (object) [
            "id" => $page . '/' . $pid,
            "type" => 'AnnotationPage',
            "items" => $items
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

    private function getDatastreamIds () {
        $dsids = Request::getDatastreams($this->pid, 'csv');
        $final_dsids = [];
        foreach (explode("\n", $dsids['body']) as &$dsid) :
            $potential_dsid = explode("/", $dsid);
            array_push($final_dsids, end($potential_dsid));
        endforeach;
        return $final_dsids;
    }

    public function paintCanvas ($pid) {

        $item = array();

        $datastream = $this->url . '/collections/islandora/object/' . $pid . '/datastream/';

        if (in_array($this->type, ['Image', 'Book'])) :
            $iiifImage = self::getIIIFImageURI('JP2', $pid);
            $item = self::getItemBody($iiifImage, $datastream . 'OBJ');

        elseif ($this->type === 'Sound') :
            $item['id'] = $datastream . 'PROXY_MP3';
            $item['type'] = "Sound";
            $item['width'] = 640;
            $item['height'] = 360;
            $item['duration'] = self::getBibframeDuration('PROXY_MP3');
            $item['format'] = "audio/mpeg";

        elseif ($this->type === 'Video') :
            $item['id'] = $datastream . 'MP4';
            $item['type'] = "Video";
            $item['width'] = 640;
            $item['height'] = 360;
            $item['duration'] = self::getBibframeDuration('MP4');
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
                $manifest['structures'] = self::buildRange($pbcore, $uri . '/range', $uri . '/canvas');
            endif;

        }

        return $manifest;

    }

    public function buildRange ($parts, $uri, $canvas) {

        $ranges = [];

        foreach ($parts as $index => $part) :

            $partType = $part->getAttribute('partType');

            if (in_array($partType, ['Interview Questions', 'Chapters', 'geographic'])) :

                $label = $part->getElementsByTagNameNS('http://www.pbcore.org/PBCore/PBCoreNamespace.html', 'pbcoreTitle');
                $startTime = $part->getAttribute('startTime');
                $endTime = $part->getAttribute('endTime');
                if ($partType == 'geographic'):
                    $partType = 'Places Mentioned';
                endif;
                $range = Utility::sanitizeLabel($partType);

                $ranges[$range]['type'] = 'Range';
                $ranges[$range]['id'] = $uri . '/' . $range;
                $ranges[$range]['label'] = self::getLanguageArray($partType, 'label');
                $ranges[$range]['items'][] = (object) [
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

    public function getLabelValuePair ($label, $value, $language="en") {

        if ($value !== null) {
            return (object) [
                'label' => self::getLanguageArray($label, 'label', $language),
                'value' => self::getLanguageArray($value, 'value', $language)
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

    private function getBibframeDuration($dsid) {
        $durations = Request::getBibframeDuration($this->pid, $dsid, 'csv');
        $duration = explode("\n", $durations['body'])[1];
        $split_duration = explode(":", $duration);
        $hours = intval($split_duration[0]) *  60 * 60;
        $minutes = intval($split_duration[1]) * 60;
        return $hours + $minutes + intval($split_duration[2]);
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
        elseif (in_array('info:fedora/islandora:bookCModel', $model)) :
            $type = "Book";
        else :
            $type = "Image";
        endif;

        return $type;

    }

}

?>
