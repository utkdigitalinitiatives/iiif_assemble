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

    public static function orderCanvases ($string)
    {

        $pages = str_getcsv($string, "\n");
        unset($pages[0]);

        $index = [];

        foreach ($pages as $page) {
            $item = explode(',', $page);
            $pageNumber = $item[1];
            $index[$pageNumber] = str_replace('info:fedora/', '', $item[0]);
        }

        sort($index);

        return $index;

    }


}

?>
