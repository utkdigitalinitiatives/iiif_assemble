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
        return "Other Items of "  . $metadata_value[0] . " " . $metadata_value[1];
    }

}

?>
