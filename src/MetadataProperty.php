<?php

namespace Src;

class MetadataProperty
{
    public function __construct($mods, $simpleMods)
    {

        $this->mods = $mods;
        $this->simpleMods = $simpleMods;
        $this->primary_metadata = self::buildManifest();
        $this->validated_primary_metadata = self::validateMetadata($this->primary_metadata);
        
    }

    private function add_names_to_metadata($current_metadata) {
        $names = $this->simpleMods->get_names();
        foreach ($names as $k => $v) {
            $current_metadata[$k] = $v;
        }
        return $current_metadata;
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
        if($value) {
            foreach ($value as $thing) {
                if (array_key_exists($thing, $sanitize)) {
                    array_push($finals, $sanitize[$thing]);
                }
                else {
                    array_push($finals, $thing);
                }
            }
        }
        return $finals;
    }

    public function buildManifest()
    {
        $date = $this->mods->query('originInfo/dateCreated[not(@encoding)]');
        if ($date == "") {
            $date = $this->mods->query('originInfo/dateCreated[@encoding]');
        }
        $related_resources = $this->mods->query('relatedItem[@type="references"]/location/url');
        $final_resources = Utility::addAnchorsToReferences($related_resources);
        $metadata = array(
            'Alternative Title' => $this->mods->query('titleInfo[@type="alternative"]'),
            'Table of Contents' => $this->mods->query('tableOfContents'),
            'Publisher' => $this->mods->query('originInfo/publisher'),
            'Date' => $date,
            'Publication Date' => $this->mods->query('originInfo/dateIssued[not(@encoding)]'),
            'Format' => $this->mods->query('physicalDescription/form[not(@type="material")]'),
            'Extent' => $this->mods->query('physicalDescription/extent'),
            'Subject' => $this->mods->query('subject[not(@displayLabel="Narrator Class")]/topic'),
            'Narrator Role' => $this->mods->query('subject[@displayLabel="Narrator Class"]/topic'),
            'Place' => $this->browse_sanitize($this->mods->query('subject/geographic')),
            'Time Period' => $this->mods->query('subject/temporal'),
            'Description' => $this->mods->query('abstract[not(@lang)]'),
            'Descripción' => $this->mods->query('abstract[@lang="spa"]'),
            'Título' => $this->mods->query('titleInfo[@lang="spa"]/title'),
            'Publication Identifier' => $this->mods->queryFilterByAttribute('identifier', false, 'type', ['issn','isbn']),
            'Browse' => $this->browse_sanitize($this->mods->query('note[@displayLabel="Browse"]')),
            'Language' => $this->mods->query('language/languageTerm'),
            'Provided by' => $this->mods->query('recordInfo/recordContentSource'),
            'Related Resource' => $final_resources,
        );
        return $this->add_names_to_metadata($metadata);
    }

    public function buildCanvas($canvasMODS, $canvasSimpleMODS)
    {
        $date = $canvasMODS->query('originInfo/dateCreated[not(@encoding)]');
        if ($date == "") {
            $date = $canvasMODS->query('originInfo/dateCreated[@encoding]');
        }
        $related_resources = $canvasMODS->query('relatedItem[@type="references"]/location/url');
        $final_resources = Utility::addAnchorsToReferences($related_resources);
        $metadata = array(
            'Canvas Label' => $canvasMODS->query('titleInfo/title'),
            'Alternative Title' => $canvasMODS->query('titleInfo[@type="alternative"]'),
            'Table of Contents' => $canvasMODS->query('tableOfContents'),
            'Publisher' => $canvasMODS->query('originInfo/publisher'),
            'Date' => $date,
            'Publication Date' => $canvasMODS->query('originInfo/dateIssued[not(@encoding)]'),
            'Format' => $canvasMODS->query('physicalDescription/form[not(@type="material")]'),
            'Extent' => $canvasMODS->query('physicalDescription/extent'),
            'Subject' => $canvasMODS->query('subject[not(@displayLabel="Narrator Class")]/topic'),
            'Narrator Role' => $canvasMODS->query('subject[@displayLabel="Narrator Class"]/topic'),
            'Place' => $this->browse_sanitize($canvasMODS->query('subject/geographic')),
            'Time Period' => $canvasMODS->query('subject/temporal'),
            'Description' => $canvasMODS->query('abstract[not(@lang)]'),
            'Descripción' => $canvasMODS->query('abstract[@lang="spa"]'),
            'Título' => $canvasMODS->query('titleInfo[@lang="spa"]/title'),
            'Publication Identifier' => $canvasMODS->queryFilterByAttribute('identifier', false, 'type', ['issn','isbn']),
            'Browse' => $this->browse_sanitize($canvasMODS->query('note[@displayLabel="Browse"]')),
            'Language' => $canvasMODS->query('language/languageTerm'),
            'Provided by' => $canvasMODS->query('recordInfo/recordContentSource'),
            'Related Resource' => $final_resources,
        );
        $metadata_with_names = $this->add_names_to_metadata($metadata);
        $unique = self::compare($metadata_with_names);
        $validated_metadata = self::validateMetadata($unique);
        return $validated_metadata;
    }

    private function compare($canvas_data) {
        $new_data = array();
        foreach ($canvas_data as $key => $value) {
            if (array_key_exists($key, $this->primary_metadata)) {
                if ($this->primary_metadata[$key] !== null and $canvas_data[$key] !== null) {
                    foreach($value as $piece) {
                        if (!in_array($piece, $this->primary_metadata[$key])) {
                            $new_data[$key] = array($piece);
                        }
                    }
                }
            }
            else {
                $new_data[$key] = $value;
            }
        }
        return $new_data;
    }

    private function getLabelValuePair ($label, $value, $language="en") {

        if ($value !== null) {
            return (object) [
                'label' => self::getLanguageArray($label, 'label', $language),
                'value' => self::getLanguageArray($value, 'value', $language)
            ];
        } else {
            return null;

        }

    }

    private function getLanguageArray ($string, $type, $language = 'en') {

        if ($type === 'label') :
            $string = [$string];
        endif;

        return (object) [
            $language => $string
        ];

    }

    private function validateMetadata ($array) {

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

}