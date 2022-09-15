<?php


namespace Src;


class Navplace
{
    private $data;
    private $coordinates;
    private $geographic;
    private $url;

    public function __construct($mods, $url)
    {

        $this->data = $mods;
        $this->url = $url;
        $this->coordinates = $mods->query('subject/cartographics/coordinates');
        $this->geographic = $mods->query('subject[@authority="geonames"]/geographic');

    }

    public function checkFornavPlace() {
        return $this->data->query('subject/cartographics/coordinates');
    }

    private function initNavPlace() {
        return (object) [
            "id" => str_replace('digital.lib', 'iiif.lib', $this->url) . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]) . "/featurecollection/1",
            "type" => "FeatureCollection",
            "features" => [],
        ];
    }

    private function buildFeature ($coordinate, $identifier) {
        $new_coordinates = explode(",", $coordinate);
        $longitude = $new_coordinates[1];
        $latitude = $new_coordinates[0];
        return (object) [
            "id" => str_replace('digital.lib', 'iiif.lib', $this->url) . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]) . "/feature/" . $identifier,
            "type" => "Feature",
            "properties" => (object) [
                "label" => (object) [
                    "en" => [
                        $this->geographic[$identifier - 1]
                    ]
                ],
                "manifest" => $this->url . str_replace('?update=1', '', $_SERVER["REQUEST_URI"])
            ],
            "geometry" => (object) [
                "type" => "Point",
                "coordinates" => [
                    floatval($longitude),
                    floatval($latitude)
                ]
            ]
        ];
    }

    public function buildnavPlace() {
        $navPlace = $this->initNavPlace();
        $i = 1;
        foreach ($this->coordinates as $coordinate) {
            $feature = $this->buildFeature($coordinate, $i);
            $i += 1;
            array_push($navPlace->features, $feature);
        }
        return $navPlace;
    }

}