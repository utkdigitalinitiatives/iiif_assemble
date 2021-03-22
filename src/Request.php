<?php

namespace Src;

use Curl\Curl;

class Request{

    private static function processRequest ($request) {

        $curl = new Curl();
        $curl->verbose();
        $curl->setBasicAuthentication($_ENV['FEDORA_USER'], $_ENV['FEDORA_PASS']);
        $curl->get($request);

        if ($curl->error) {
            $response = $curl->errorCode . ': ' . $curl->errorMessage;
        } else {
            $response = $curl->response;
        }

        $curl->close();

        return $response;

    }

    public static function getDatastream ($dsid, $pid, $format = null) {

        $object = implode('%3A', $pid);

        $request = $_ENV['FEDORA_URL'] . '/objects/';
        $request .= $object;
        $request .= '/datastreams/';
        $request .= $dsid;
        $request .= '/content';

        if ($format) :
            $request .= '?format=' . $format;
        endif;

        return self::processRequest($request);

    }

}

?>
