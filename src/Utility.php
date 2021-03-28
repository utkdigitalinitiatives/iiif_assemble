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

}

?>
