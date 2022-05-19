<?php

namespace Src;

class Utility {

    public static function getBaseUrl () {

        if (isset($_SERVER['HTTPS'])) :
            $protocol = "https://";
        else :
            $protocol = "http://";
        endif;

        return $protocol . $_SERVER["HTTP_HOST"];

    }

    public static function xmlToArray ( $xmlObject, $out = array () )
    {
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;

        return $out;
    }

    public static function sanitizeLabel ($string)
    {

        $output = mb_strtolower($string);
        $output = str_replace(' ', '_', $output);

        return $output;

    }

    public static function setTimestamp ()
    {

        $date = date('Y-m-d\TH:i:sP');

        return (object) [
            'ISO8601' => $date,
            'unix' => strtotime($date)
            ];

    }

    public static function orderCanvases ($csv)
    {

        $result = str_getcsv($csv, "\n");
        unset($result[0]);

        $index = [];

        foreach ($result as $string) {
            if (strpos($string, "info:fedora/fedora-system:FedoraObject-3.0") !== true) {
                $item = explode(',', $string);
                $pageNumber = $item[1];
                $index[$pageNumber]['pid'] = str_replace('info:fedora/', '', $item[0]);
                $index[$pageNumber]['title'] = $item[2];
                $index[$pageNumber]['type'] = $item[3];
            }
        }

        ksort($index);
        $page = 0;
        $canvas = 0;
        $sequence = [];

        foreach ($index as $key => $object) {
            $sequence[$canvas][] = $object;
            $page++;
            $canvas++;
        }

        return $sequence;

    }

    public static function orderCollection ($csv)
    {

        $result = str_getcsv($csv, "\n");
        unset($result[0]);

        $index = [];

        foreach ($result as $string) {
            $item = explode(',', $string);
            if(str_starts_with($item[1], '"')) {
                $split = explode('"', $string);
                $label = str_replace('"', '', $split[1]);
            } else {
                $label = $item[1];
            }
            $index[] = (object) [
                'pid' => str_replace('info:fedora/', '', $item[0]),
                'label' => $label,
            ];
        }

        return $index;

    }

    public static function makeMetadataCollectionLabel ($identifier) {
        $metadata_value = explode('%2F', $identifier);
        $new_value = $metadata_value[0];
        if ($metadata_value[0] === "contributor") {
            $new_value = "Associated with";
        }
        elseif ($metadata_value[0] === "coverage") {
            $new_value = "near";
        }
        elseif ($metadata_value[0] === "creator") {
            $new_value = "Created by";
        }
        elseif ($metadata_value[0] === "date") {
            $new_value = "Associated with";
        }
        elseif ($metadata_value[0] === "description") {
            $new_value = "with Description";
        }
        elseif ($metadata_value[0] === "format") {
            $new_value = "with Format";
        }
        elseif ($metadata_value[0] === "identifier") {
            $new_value = "Identified by";
        }
        elseif ($metadata_value[0] === "language") {
            $new_value = "Written in";
        }
        elseif ($metadata_value[0] === "publisher") {
            $new_value = "Published by";
        }
        elseif ($metadata_value[0] === "relation") {
            $new_value = "Related to";
        }
        elseif ($metadata_value[0] === "rights") {
            $new_value = "with Rights Statement";
        }
        elseif ($metadata_value[0] === "source") {
            $new_value = "from";
        }
        elseif ($metadata_value[0] === "subject") {
            $new_value = "about";
        }
        elseif ($metadata_value[0] === "title") {
            $new_value = "with Label";
        }
        elseif ($metadata_value[0] === "type") {
            $new_value = "of Type";
        }
        return "Items "  . $new_value . " \"" . str_replace("%20", " ", urldecode($metadata_value[1])) . "\"";

    public static function addAnchorsToReferences($references) {
        $results = [];
        foreach ($references as $reference){
            $new_reference = '<a href="' . $reference . '">' . $reference . '</a>';
            array_push($results, $new_reference);
        }
        return $results;
    }

}

?>
