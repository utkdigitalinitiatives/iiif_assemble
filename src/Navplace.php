<?php


namespace Src;


class Navplace
{
    private $data;
    private $coordinates;
    private $geographic;
    private $url;
    private $underefenceable_uri;

    public function __construct($mods, $url)
    {

        $this->data = $mods;
        $this->url = $url;
        $this->coordinates = $mods->query('subject/cartographics/coordinates');
        $this->geographic = $mods->query('subject[@authority="geonames"]/geographic');
        $this->underefenceable_uri = str_replace('digital.lib', 'iiif.lib', $this->url);

    }

    public function checkFornavPlace() {
        return $this->data->query('subject[@authority="geonames"]/cartographics/coordinates');
    }

    private function initFeatureCollection() {
        return (object) [
            "id" => $this->underefenceable_uri  . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]) . "/featurecollection/1",
            "type" => "FeatureCollection",
            "features" => [],
        ];
    }

    private function buildFeature ($coordinate, $identifier) {
        $new_coordinates = explode(",", $coordinate);
        $longitude = $new_coordinates[1];
        $latitude = $new_coordinates[0];
        return (object) [
            "id" => $this->underefenceable_uri . str_replace('?update=1', '', $_SERVER["REQUEST_URI"]) . "/feature/" . $identifier,
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
        $navPlace = $this->initFeatureCollection();
        $i = 1;
        foreach ($this->coordinates as $coordinate) {
            $feature = $this->buildFeature($coordinate, $i);
            $i += 1;
            array_push($navPlace->features, $feature);
        }
        return $navPlace;
    }

}
