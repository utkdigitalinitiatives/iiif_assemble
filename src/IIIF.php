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
        if ($mods != null) {
            $this->mods = $mods;
            $this->xpath = new XPath($mods);
            $this->simplexpath = new SimpleXPath($mods);
            $this->label = self::getLanguageArray($this->xpath->query('titleInfo[not(@type="alternative")][not(@lang)]'), 'value');
        }
        $this->object = $object;
        $this->type = self::determineTypeByModel($model);
        $this->url = Utility::getBaseUrl();

    }

    public function buildCollection ()
    {
        $id = $this->url . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]);

        $collection['@context'] = ['http://iiif.io/api/presentation/3/context.json'];
        $collection['id'] = $id;
        $collection['type'] = 'Collection';
        $summary = self::getLanguageArray($this->xpath->query('abstract[not(@lang)]'), 'value');
        if (is_array($summary->en) && $summary->en[0] != "") {
            $collection['summary'] = $summary;
        }
        $collection['viewingDirection'] = 'left-to-right';
        $collection['behavior'] = ['unordered'];
        $collection['partOf'] = self::getPartOf();
        $collectionMetadata = new MetadataProperty($this->xpath, $this->simplexpath);;
        $collection['metadata'] = $collectionMetadata->validated_primary_metadata;
        $collection['thumbnail'] = self::buildCollectionThumbnails();
        $collection['label'] = self::getLanguageArray($this->xpath->query('titleInfo[not(@type="alternative")]'), 'value');
        $collection['items'] = self::buildCollectionItems();
        $collection['provider'] = self::buildProvider();
        $collection['homepage'] = [ self::buildHomepage($this->pid, $collection['label']) ];

        return json_encode($collection);

    }

    public function buildMetadataCollection ()
    {
        $id = $this->url . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]);

        $collection['@context'] = ['http://iiif.io/api/presentation/3/context.json'];
        $collection['id'] = $id;
        $collection['type'] = 'Collection';
        $collection['viewingDirection'] = 'left-to-right';
        $collection['behavior'] = ['unordered'];
        $collection['thumbnail'] = self::buildCollectionThumbnails();
        $collection['label'] = (object)['none'=> [Utility::makeMetadataCollectionLabel($this->pid)]];
        $collection['items'] = self::buildCollectionItems();
        $collection['provider'] = self::buildProvider();
        $collection['homepage'] = self::buildMetadataCollectionHomePage();

        return json_encode($collection);

    }

    private function buildMetadataCollectionHomePage () {
        $id = "https://projectmirador.org/embed/?iiif-content=" . $this->url . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]);
        return [
            (object) [
                "id" => $id,
                "type" => "Text",
                "label" => (object) [
                    "en" => [
                        "View Collection in Mirador"
                    ]
                ],
                "format" => "text/html"
            ]
        ];
    }


    private function buildHomepage ($pid, $label_for_manifest) {
        if(strpos($pid, 'rfta%3A') === 0 || strpos($pid, 'rfta:') === 0 ) {
            $label = str_replace('Interview with ', '', $label_for_manifest->en[0]);
            $label = str_replace(' ', '-', $label);
            $label = str_replace('/', '-', $label);
            $label = strtolower(str_replace(',', '', $label));
            $slug = 'https://rfta.lib.utk.edu/interviews/object/' . $label;
        } else {
            $slug = $this->url . '/collections/islandora/object/' . $pid;
        }
        $homepage = (object) [
            'id' => $slug,
            'label' => $label_for_manifest,
            'type' => 'Text',
            'format' => 'text/html'
        ];
        return $homepage;
    }

    private function buildCollectionItems ($items = []) {
        foreach ($this->object as $item) {
            $items[] = (object) [
                'id' => $this->url . '/assemble/manifest/' . str_replace(':', '/', $item->pid),
                'type' => 'Manifest',
                'label' => (object) [
                    'none' => [$item->label]
                    ],
                'thumbnail' => self::useFedoraThumbnail($item->pid),
                'homepage' => [
                    self::buildHomepage($item->pid, (object) [
                        'en' => [$item->label]
                    ]),
                ]
            ];
        }

        return $items;

    }

    private function useFedoraThumbnail ($pid, $model="") {
        $items = [];
        $item = array();
        $item['id'] = $this->url . '/collections/islandora/object/' . $pid . '/datastream/TN/view';
        $item['height'] = 200;
        $item['width'] = 200;
        $item['type'] = 'Image';
        $item['format'] = 'image/jpeg';
        array_push($items, $item);
        return $items;
    }

    private function buildCollectionThumbnails ($items = []) {

        foreach ($this->object as $item) {
            $items[] = (object) [
                'id' => $this->url . '/iiif/2/collections~islandora~object~' . $item->pid . '~datastream~TN/full/full/0/default.jpg',
                'type' => 'Image',
                'format' => 'image/jpeg',
                'service' => [
                    (object) [
                        '@id' => $this->url . '/iiif/2/collections~islandora~object~' . $item->pid . '~datastream~TN',
                        '@type' => "http://iiif.io/api/image/2/context.json",
                        'profile' => 'http://iiif.io/api/image/2/level2.json'
                    ]
                ]
            ];
        }

        return $items;
    }

    public function buildManifest ()
    {
        $id = $this->url . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]);

        $manifest['@context'] = [
            "http://iiif.io/api/extension/navplace/context.json",
            'http://iiif.io/api/presentation/3/context.json'
        ];
        $manifest['id'] = $id;
        $manifest['type'] = 'Manifest';
        $manifest['label'] = self::getLanguageArray($this->xpath->query('titleInfo[not(@type="alternative")][not(@lang)]'), 'value');
        $summary = self::getLanguageArray($this->xpath->query('abstract[not(@lang)]'), 'value');
        if ($summary->en) {
            $manifest['summary'] = $summary;
        }
        $manifestMetadata = new MetadataProperty($this->xpath, $this->simplexpath);;
        $metadata = $manifestMetadata->validated_primary_metadata;
        if (count($metadata) > 0) {
            $manifest['metadata'] = $metadata;
        }
        $rights = self::buildRights();
        if ($rights) {
            $manifest['rights'] = $rights;
        }
        $requiredStatement = self::buildRequiredStatement();
        if ($requiredStatement->value->en) {
            $manifest['requiredStatement'] = $requiredStatement;
        }
        $navDate = new Navdate($this->xpath);
        if ($navDate->date !== null) {
            $manifest['navDate'] = $navDate->date;
        }
        $navPlace = new Navplace($this->xpath, $this->url);
        $coordinates = $navPlace->checkFornavPlace();
        if ($coordinates) {
            $manifest['navPlace'] = $navPlace->buildnavPlace();
        }
        $manifest['provider'] = self::buildProvider();
        $manifest['thumbnail'] = self::buildThumbnail(200, 200);
        $manifest['items'] = self::buildItems($id);
        $manifest['seeAlso'] = self::buildSeeAlso();
        $manifest['partOf'] = self::getPartOf();
        $manifest['homepage'] = [ self::buildHomepage($this->pid, $manifest['label']) ];

        if ($this->type === 'Book') {
            $manifest['behavior'] = ["paged"];
        }
        if ($this->type === 'Compound') {
            $manifest['behavior'] = ["individuals"];
        }
        if ($this->type === "Sound") {
            $manifest['accompanyingCanvas'] = self::buildAccompanyingCanvas($this->getIIIFImageURI('TN', $this->pid));
        }
        $presentation = self::buildStructures($manifest, $id);
        return json_encode($presentation);

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
        $rights_uri = $this->buildRights();
        $rights_data = new Rights($rights_uri);
        $complete_value = "";
        if ($rights_data->data) {
            if (isset($rights_data->data->badge)) {
                $rights_metadata = '<span><a href="' . str_replace('rdf', '', $rights_uri) . '"><img src="' . $rights_data->data->badge . '"/></a></span><br/>';
                $complete_value = $complete_value . $rights_metadata;
            }
            if (isset($rights_data->data->definition)) {
                $rights_usage = '<span><a href="' . $rights_uri . '">' . $rights_data->data->label . '</a>:  ' . $rights_data->data->definition . '</span><br/>';
                $complete_value = $complete_value . $rights_usage;
            }
            elseif (isset($rights_data->data->label)) {
                $cc_label = '<span><a href="' . $rights_data->data->uri . '"/>' . $rights_data->data->label . '</a></span><br/>';
                $complete_value = $complete_value . $cc_label;
                $complete_value = $complete_value . '<span>Requires:</span><br/>';
                $i = 1;
                foreach ($rights_data->data->requires as $value) {
                    $complete_value = $complete_value . '<small>' . $i . '. ' . $value . '</small><br/>';
                    $i += 1;
                }
                $complete_value = $complete_value . '<span>Permits:</span><br/>';
                $i = 1;
                foreach ($rights_data->data->permits as $value) {
                    $complete_value = $complete_value . '<small>' . $i . '. ' . $value . '</small><br/>';
                    $i += 1;
                }
            }
        }
        return (object) [
            'label' => self::getLanguageArray('Rights', 'label'),
            'value' => self::getLanguageArray([$complete_value], 'value')
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

    public function buildThumbnail ($width, $height, $pid="", $model="") {
        if ($pid == "") {
            $pid = $this->pid;
        }
        $items = array();
        $item = array();
        // Quick fix for Video Thumbnails
        $preferredSource = 'JP2';
        if ($this->type === "Sound" or $this->type === "Video") {
            $preferredSource = 'TN';
        }
        $iiifImage = self::getIIIFImageURI($preferredSource, $pid);
        $thumbnail_details = Request::get_thumbnail_details($iiifImage);

        if ($thumbnail_details['is_iiif']) :
            $thumbnail = new Thumbnail($pid, $this->type, $this->url);
            $item = $thumbnail->buildResponse()[0];
        else :
            $item['id'] = $this->url . '/collections/islandora/object/' . $pid . '/datastream/' . 'TN';
            $item['width'] = $width;
            $item['height'] = $height;
            $item['type'] = "Image";
            $item['format'] = "image/jpeg";
        endif;

        if ( $this->type === "Sound" or $this->type === "Video") {
            $item->duration = self::getBibframeDuration(self::findProxyDatastream());
        }
        array_push($items, $item);
        if ( $this->type === "Video" || $model === "info:fedora/islandora:sp_videoCModel") {
            $video = array();
            $video['id'] = $this->url . '/collections/islandora/object/' . $pid . '/datastream/MP4/#t=60,75';
            $video['type'] = 'Video';
            $video['format'] = 'video/mp4';
            $video['width'] = 1920;
            $video['height'] = 1080;
            $video['duration'] = 15;
            array_push($items, $video);
        }
        return $items;

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
        elseif (in_array($this->type, ['Compound'])) :
            $items = Request::getCompoundParts($this->pid, 'csv');

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

    private function buildAccompanyingCanvas ($uri) {
        $canvasId = str_replace('digital.lib.utk.edu/', 'digital.lib.utk.edu/notdereferenceable/', $uri) . '/canvas/accompanying';
        $title = 'Accompanying canvas for ' . $this->xpath->query('titleInfo[not(@type="alternative")]')[0];
        $canvas = (object) [
            "id" => $canvasId,
            "type" => "Canvas",
            "label" => self::getLanguageArray($title, 'label', 'none')
        ];
        $iiifImage = self::getIIIFImageURI('TN', $this->pid);

        if (Request::responseStatus($iiifImage)) :
            $responseImageBody = json_decode(Request::responseBody($iiifImage));
            $canvas->width = $responseImageBody->width;
            $canvas->height = $responseImageBody->height;
        endif;
        $canvas->items = [self::prepareAccompanyingPage($canvasId)];
        return $canvas;
    }

    private function paintAccompanyingImage($dsid) {
        $item = array();
        $datastream = $this->url . '/collections/islandora/object/' . $this->pid . '/datastream/';
        $iiifImage = self::getIIIFImageURI($dsid, $this->pid);
        $item = self::getItemBody($iiifImage, $datastream . 'TN');
        return $item;
        }

    public function buildCanvas ($index, $uri, $pid) {

        $canvasId = str_replace('digital.lib.utk.edu/', 'digital.lib.utk.edu/notdereferenceable/', $uri) . '/canvas/' . $index;
        $title = $this->xpath->query('titleInfo[not(@type="alternative")]')[0];
        $canvas = (object) [
                "id" => $canvasId,
                "type" => 'Canvas',
                "label" => self::getLanguageArray($title, 'label', 'none'),
        ];

        if (in_array($this->type, ['Sound','Video'])) :

            $canvas->height = 640;
            $canvas->width = 360;
            $canvas->duration = self::getBibframeDuration(self::findProxyDatastream());
            $canvas->thumbnail = self::buildThumbnail(200, 200);

        else :

            $iiifImage = self::getIIIFImageURI('JP2', $pid);

            if (Request::responseStatus($iiifImage)) :
                $responseImageBody = json_decode(Request::responseBody($iiifImage));
                $canvas->width = $responseImageBody->width;
                $canvas->height = $responseImageBody->height;
                $canvasThumbnail = new Thumbnail($pid, $this->type, $this->url);
                $canvas->thumbnail = $canvasThumbnail->buildResponse();
            else :
                $canvas->height = 640;
                $canvas->width = 360;
                $canvas->thumbnail = self::buildThumbnail(200, 200);
            endif;

        endif;

        $canvas->items = [self::preparePage($canvasId, $pid)];
        $annotations = self::prepareAnnotationPage($canvasId, $pid);
        if (count($annotations->items) > 0) {
            $canvas->annotations = [self::prepareAnnotationPage($canvasId, $pid)];
        }

        return $canvas;

    }

    private function buildOCR ($pid) {
        return (object) [
            "id" => $this->url . "/collections/islandora/object/" . $pid . '/datastream/HOCR',
            "type" => "Dataset",
            "label" => self::getLanguageArray("HOCR", 'label', 'none'),
            "format"=> "text/vnd.hocr+html",
            "profile"=> "http://kba.cloud/hocr-spec/1.2/"
        ];
    }

    public function buildCanvasWithPages ($index, $uri, $canvasData) {
        $canvasId = str_replace('digital.lib.utk.edu/', 'digital.lib.utk.edu/notdereferenceable/', $uri) . '/canvas/' . $index;
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
            $canvasThumbnail = new Thumbnail($data['pid'], $data['type'], $this->url);
            $canvas->thumbnail = $canvasThumbnail->buildResponse();
            $canvas->items[$key] = self::preparePage($canvasId, $data['pid'], $key, $canvasData);
            $annotations = self::prepareAnnotationPage($canvasId, $data['pid']);
            if (count($annotations->items) > 0) {
                $canvas->annotations = [$annotations];
            }
            if ($this->type === "Book") {
                $canvas->seeAlso = [self::buildOCR($data['pid'])];
            }
        }
        if ($this->type === "Compound") {
            $mods = Request::getDatastream('MODS', $data['pid']);
            $compound_metadata = new MetadataProperty($this->xpath, $this->simplexpath);
            $this->xpath = new XPath($mods['body']);
            $this->simplexpath = new SimpleXPath($mods['body']);
            $part_metadata = $compound_metadata->buildCanvas($this->xpath, $this->simplexpath);
            $part_rights = self::buildRights();
            $part_requiredstatement = self::buildRequiredStatement();
            $summary = self::getLanguageArray($this->xpath->query('abstract[not(@lang)]'), 'value');
            $part_duration = 0;
            if (array_key_exists('duration', $canvas->items[0]->items[0]->body)) {
                $part_duration = $canvas->items[0]->items[0]->body["duration"];
            }
            if (is_array($summary->en) && $summary->en[0] != "") {
                $canvas->summary = $summary;
            }
            if (count($part_metadata) > 0) {
                $canvas->metadata = $part_metadata;
            }
            if ($part_rights !== null) {
                $canvas->rights = $part_rights;
            }
            if ($part_requiredstatement->value->en !== null ) {
                $canvas->requiredStatement = $part_requiredstatement;
            }
            if ($part_duration > 0) {
                $canvas->duration = $part_duration;
            }
        }

        return $canvas;

    }

    private function buildTranscript($language_code, $page, $target, $pid="") {
        if($pid == "") {
            $pid = $this->pid;
        }
        $datastream = $this->url . '/collections/islandora/object/' . $pid . '/datastream/';
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
                "id" => $page . '/' . $pid . '/' . uniqid(),
                "type" => 'Annotation',
                "motivation" => "supplementing",
                "body" =>
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
                "target" => $target
        ];
    }

    private function getPartOf() {
        $all_collections = [];
        $collections = Request::getCollectionPidIsPartOf($this->pid, 'csv');
        $split_collections = explode("\n", $collections['body']);
        $split_collections = array_diff( $split_collections, ['"collection"', '', 'info:fedora/islandora:root'] );
        foreach ($split_collections as $collection) :
            $just_collection_pid = str_replace('info:fedora/', '', $collection);
            $new_collection = ( object ) [
                "id" => $this->url . '/assemble/collection/' . str_replace(':', '/', $just_collection_pid),
                "type" => "Collection"
            ];
            array_push($all_collections, $new_collection);
        endforeach;
        if (in_array($this->type, ['Sound', 'Video', 'Image'])) {
            $compound_objects = Request::getCompoundObjectPidIsPartOf($this->pid, 'csv');
            $split_compounds = explode("\n", $compound_objects['body']);
            $split_compounds = array_diff( $split_compounds, ['"compound"', ''] );
            foreach ($split_compounds as $compound) :
                $just_compound_pid = str_replace('info:fedora/', '', $compound);
                $new_collection = ( object ) [
                    "id" => $this->url . '/assemble/collection/' . str_replace(':', '/', $just_compound_pid),
                    "type" => "Manifest"
                ];
                array_push($all_collections, $new_collection);
            endforeach;
        }
        return $all_collections;

    }

    private function getTranscipts($pagenumber, $target, $pid) {
        $datastreams = $this::getDatastreamIds($pid);
        $transcripts = [];
        if (in_array('TRANSCRIPT', $datastreams)) :
            array_push($transcripts, $this::buildTranscript('en', $pagenumber, $target, $pid));
        endif;
        if (in_array('TRANSCRIPT-ES', $datastreams)) :
            array_push($transcripts, $this::buildTranscript('es', $pagenumber, $target, $pid));
        endif;
        return $transcripts;
    }

    public function prepareAccompanyingPage ($target) {

        $page = $target . '/page';
        $items = [
            (object) [
                "id" => $page . '/' . $this->pid . '/' . uniqid(),
                "type" => 'Annotation',
                "motivation" => "painting",
                "body" => self::paintAccompanyingImage('TN'),
                "target" => $target
            ]
        ];
        return (object) [
            "id" => $page . '/' . $this->pid,
            "type" => 'AnnotationPage',
            "items" => $items
        ];
    }

    public function preparePage ($target, $pid, $number = 1, $canvas_data=[]) {

        $page = $target . '/page';
        $items = [
            (object) [
                "id" => $page . '/' . $pid . '/' . uniqid(),
                "type" => 'Annotation',
                "motivation" => "painting",
                "body" => self::paintCanvas($pid, $canvas_data),
                "target" => $target
            ]
        ];
        $canvas = (object) [
            "id" => $page . '/' . $pid,
            "type" => 'AnnotationPage',
            "items" => $items
        ];
        return $canvas;
    }

    private function prepareAnnotationPage ($target, $pid, $number = 1) {
        $page = $target . '/page/annotation';
        $items = [];
        if (in_array($this->type, ['Sound', 'Video', 'Compound'])) :
            $transcripts = self::getTranscipts($page, $target, $pid);
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
            $body['service'] = [
                (object) [
                    '@id' => $response->{'@id'},
                    '@type' => $response->{'@context'},
                    'profile' => $response->profile[0],
                ]
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

    private function getDatastreamIds ($pid="") {
        if($pid == "") {
            $pid = $this->pid;
        }
        $dsids = Request::getDatastreams($pid, 'csv');
        $final_dsids = [];
        foreach (explode("\n", $dsids['body']) as &$dsid) :
            $potential_dsid = explode("/", $dsid);
            array_push($final_dsids, end($potential_dsid));
        endforeach;
        return $final_dsids;
    }

    public function paintCanvas ($pid, $data=[] ){

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
            $item['format'] = "audio/mp3";

        elseif ($this->type === 'Video') :
            $item['id'] = $datastream . 'MP4';
            $item['type'] = "Video";
            $item['width'] = 640;
            $item['height'] = 360;
            $item['duration'] = self::getBibframeDuration('MP4');
            $item['format'] = "video/mp4";

        elseif ($this->type === 'Compound') :
            $part_type = self::determineTypeByModel($data[0]['type']);
            if ($part_type === 'Image') :
                $iiifImage = self::getIIIFImageURI('JP2', $pid);
                $item = self::getItemBody($iiifImage, $datastream . 'OBJ');
            elseif ($part_type === 'Sound') :
                $item['id'] = $datastream . 'PROXY_MP3';
                $item['type'] = "Sound";
                $item['width'] = 640;
                $item['height'] = 360;
                $item['duration'] = self::getBibframeDuration('PROXY_MP3', $pid);
                $item['format'] = "audio/mpeg";
            elseif ($part_type == 'Video') :
                $item['id'] = $datastream . 'MP4';
                $item['type'] = "Video";
                $item['width'] = 640;
                $item['height'] = 360;
                $item['duration'] = self::getBibframeDuration('MP4', $pid);
                $item['format'] = "video/mp4";
            endif;
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

            if (in_array($partType, ['Interview Questions', 'Chapters', 'geographic', 'Preguntas de entrevista'])) :

                $label = $part->getElementsByTagNameNS('http://www.pbcore.org/PBCore/PBCoreNamespace.html', 'pbcoreTitle');
                $startTime = $part->getAttribute('startTime');
                $endTime = $part->getAttribute('endTime');
                $rangeNavPlace = (object) [];
                if ($partType == 'geographic'):
                    $partType = 'Places Mentioned';
                    $navPlace = new Navplace($this->xpath, $this->url);
                    $coordinates = $navPlace->checkFornavPlace();
                    if ($coordinates) {
                        $rangeNavPlace = $navPlace->buildNavPlaceRange($label[0]->textContent);
                    }
                endif;
                $range = Utility::sanitizeLabel($partType);
                $ranges[$range]['type'] = 'Range';
                $ranges[$range]['id'] = str_replace('digital.lib.utk.edu/', 'digital.lib.utk.edu/notdereferenceable/', $uri) . '/' . $range;
                $ranges[$range]['label'] = self::getLanguageArray($partType, 'label');
                if ($rangeNavPlace != (object)[]):
                    $ranges[$range]['items'][] = (object) [
                        'type' => 'Range',
                        'id' => str_replace('digital.lib.utk.edu/', 'digital.lib.utk.edu/notdereferenceable/', $uri) . '/' . $range . '/' . $index,
                        'label' => self::getLanguageArray($label[0]->textContent, 'label'),
                        'navPlace' => $rangeNavPlace,
                        'items' => [
                            (object) [
                                'type' => 'Canvas',
                                'id' => str_replace('digital.lib.utk.edu/', 'digital.lib.utk.edu/notdereferenceable/', $canvas) . '/0#t=' . $startTime . ',' . $endTime
                            ]
                        ]
                    ];
                else:
                    $ranges[$range]['items'][] = (object) [
                        'type' => 'Range',
                        'id' => str_replace('digital.lib.utk.edu/', 'digital.lib.utk.edu/notdereferenceable/', $uri) . '/' . $range . '/' . $index,
                        'label' => self::getLanguageArray($label[0]->textContent, 'label'),
                        'items' => [
                            (object) [
                                'type' => 'Canvas',
                                'id' => str_replace('digital.lib.utk.edu/', 'digital.lib.utk.edu/notdereferenceable/', $canvas) . '/0#t=' . $startTime . ',' . $endTime
                            ]
                        ]
                    ];
                endif;

            endif;

        endforeach;

        return array_values($ranges);

    }

    public function getLanguageArray ($string, $type, $language = 'en') {

        if ($type === 'label') :
            $string = [$string];
        endif;

        return (object) [
            $language => $string
        ];

    }

    private function getBibframeDuration($dsid, $pid="") {
        if ($pid == "") {
            $pid = $this->pid;
        }
        $durations = Request::getBibframeDuration($pid, $dsid, 'csv');
        $response = explode("\n", $durations['body'])[1];
        if ($response != "") {
            $duration = explode("\n", $durations['body'])[1];
            $split_duration = explode(":", $duration);
            $hours = intval($split_duration[0]) *  60 * 60;
            $minutes = intval($split_duration[1]) * 60;
            return $hours + $minutes + intval($split_duration[2]);
        }
        else {
            return 400;
        }
    }

    private static function determineTypeByModel ($islandoraModel) {

        $model = Utility::xmlToArray($islandoraModel);

        if (in_array('info:fedora/islandora:sp_basic_image', $model)) :
            $type = "Image";
        elseif (in_array('info:fedora/islandora:sp_large_image_cmodel', $model)) :
            $type = "Image";
        elseif (in_array('info:fedora/islandora:pageCModel', $model)) :
            $type = "Page";
        elseif (in_array('info:fedora/islandora:sp-audioCModel', $model)) :
            $type = "Sound";
        elseif (in_array('info:fedora/islandora:sp_videoCModel', $model)) :
            $type = "Video";
        elseif (in_array('info:fedora/islandora:bookCModel', $model)) :
            $type = "Book";
        elseif (in_array('info:fedora/islandora:compoundCModel', $model)) :
            $type = "Compound";
        else :
            $type = "Image";
        endif;

        return $type;

    }

}

?>
