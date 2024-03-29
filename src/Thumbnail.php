<?php

namespace Src;

class Thumbnail
{
    public $iiifImageUri;
    private $model;
    public $pid;
    public $thumbnailSource;
    public $url;
    public function __construct($pid, $model, $url)
    {
        $this->model = $model;
        $this->pid = $pid;
        $this->url = $url;
        $this->width = 200;
        $this->height = 200;
        $this->thumbnailSource = $this->getBestThumbnail();
        $this->iiifImageUri = $this->getIiifImageUri();
    }

    private function getBestThumbnail()
    {
        switch ($this->model) {
            case 'Image':
                $thumbnail_details = Request::get_thumbnail_details($this->getIiifImageUri('JP2'));
                if ($thumbnail_details['is_iiif']){
                    return 'JP2';
                }
                else {
                    return 'TN';
                }
            default:
                return 'TN';
        }
    }

    private function buildIdentifier()
    {
        if ($this->thumbnailSource == "TN") {
            return $this->url . '/iiif/2/collections~islandora~object~' . $this->pid . '~datastream~TN/full/full/0/default.jpg';
        }
        else {
            $sizes = $this->findIdealWidthAndHeight();
            $this->width = intval($sizes->width);
            $this->height = intval($sizes->height);
            return $this->url . '/iiif/2/collections~islandora~object~' . $this->pid . '~datastream~' . $this->thumbnailSource . '/full/' . $sizes->width . ',' . $sizes->height . '/0/default.jpg';
        }
    }

    private function buildService()
    {
        return [
            (object) [
                '@id' => $this->url . '/iiif/2/collections~islandora~object~' . $this->pid . '~datastream~' . $this->thumbnailSource,
                '@type' => "http://iiif.io/api/image/2/context.json",
                'profile' => 'http://iiif.io/api/image/2/level2.json'
            ]
        ];
    }

    public function buildResponse()
    {
        return [
            (object) [
                'id' => $this->buildIdentifier(),
                'width' => $this->width,
                'height' => $this->height,
                'type' => 'Image',
                'format' => 'image/jpeg',
                'service' => $this->buildService()
            ]
        ];
    }

    private function getIiifImageUri($dsid="")
    {
        if ($dsid == "") {
            $dsid = $this->thumbnailSource;
        }
        $uri = $this->url . '/iiif/2/';
        $uri .= 'collections~islandora~object~' . $this->pid;
        $uri .= '~datastream~' . $dsid;
        $uri .= '/info.json';
        return $uri;

    }

    private function findIdealWidthAndHeight()
    {
        $sizes = (object)[];
        $responseImageBody = json_decode(Request::responseBody($this->iiifImageUri));
        $sizes->width = $responseImageBody->sizes[2]->width;
        $sizes->height = $responseImageBody->sizes[2]->height;
        return $sizes;
    }
}