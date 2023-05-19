<?php

namespace Src;

class Thumbnail
{
    private $model;
    public $pid;
    public $thumbnailSource;
    public $url;
    public function __construct($pid, $model, $url)
    {
        $this->model = $model;
        $this->pid = $pid;
        $this->url = $url;
        $this->thumbnailSouce = $this->getBestThumbnail();
    }

    private function getBestThumbnail()
    {
        switch ($this->model) {
            case 'Image':
                return 'JP2';
                break;
            default:
                return 'TN';
        }
    }

    private function buildService()
    {
        return [
            (object) [
                '@id' => $this->url . '/iiif/2/collections~islandora~object~' . $this->pid . '~datastream~' . this->thumbnailSource . '/info.json',
                '@type' => "http://iiif.io/api/image/2/context.json",
                'profile' => 'http://iiif.io/api/image/2/level2.json'
            ]
        ];
    }
}