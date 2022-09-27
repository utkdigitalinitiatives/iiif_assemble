<?php


namespace Src;


class Navplace
{
    private $data;
    private $coordinates;
    private $geographic;
    private $url;
    private $undereferenceable_uri;

    public function __construct($mods, $url)
    {

        $this->data = $mods;
        $this->url = $url;
        $this->coordinates = $mods->query('subject[@authority="geonames"]/cartographics/coordinates');
        $this->geographic = $mods->query('subject[@authority="geonames"]/geographic');
        $this->title = $mods->query('titleInfo/title')[0];
        $this->undereferenceable_uri = str_replace('digital.lib.utk.edu', 'digital.lib.utk.edu/notdereferenceable', $this->url);

    }

    public function checkFornavPlace() {
        return $this->data->query('subject[@authority="geonames"]/cartographics/coordinates');
    }

    private function initFeatureCollection($identifier="") {
        return (object) [
            "id" => str_replace('?update=1', '', $this->undereferenceable_uri ) . "/featurecollection/" . $identifier . "/1",
            "type" => "FeatureCollection",
            "features" => [],
        ];
    }

    private function buildFeature ($coordinate, $identifier) {
        $new_coordinates = explode(",", $coordinate);
        $longitude = $new_coordinates[1];
        $latitude = $new_coordinates[0];
        return (object) [
            "id" => str_replace('?update=1', '', $this->undereferenceable_uri ) . "/feature/" . $identifier,
            "type" => "Feature",
            "properties" => (object) [
                "label" => (object) [
                    "en" => [
                        $this->title . " -- " . $this->geographic[$identifier - 1]
                    ]
                ],
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

    public function buildNavPlaceRange($label) {
        $current_coordinates = ($this->coordinates[array_search($label, $this->geographic)]);
        $featureCollection = $this->initFeatureCollection(str_replace(" ", "", $label));
        $feature = $this->buildRangeFeature($current_coordinates, trim($label, " ") . "/1", $label);
        array_push($featureCollection->features, $feature);
        return $featureCollection;
    }

    private function buildRangeFeature ($coordinate, $identifier, $label) {
        $new_coordinates = explode(",", $coordinate);
        $longitude = $new_coordinates[1];
        $latitude = $new_coordinates[0];
        return (object) [
            "id" => str_replace('?update=1', '', $this->undereferenceable_uri ) . "/feature/" . str_replace(" ", "", $identifier),
            "type" => "Feature",
            "properties" => (object) [
                "label" => (object) [
                    "en" => [
                        $label . " discussed in " . $this->title,
                    ]
                ],
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

}
