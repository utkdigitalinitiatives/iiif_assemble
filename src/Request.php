<?php

namespace Src;

use Curl\Curl;

class Request {

    private static function fedoraRequest ($request) {

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

    public static function getObjectModels ($pid, $format = 'XML') {

        $request = $_ENV['FEDORA_URL'] . '/objects/';
        $request .= implode('%3A', $pid);

        if ($format) :
            $request .= '?format=' . $format;
        endif;

        $response = self::fedoraRequest($request);

        return $response->objModels;Re

    }

    public static function getDatastream ($dsid, $pid, $format = 'XML') {

        $request = $_ENV['FEDORA_URL'] . '/objects/';
        $request .= implode('%3A', $pid);
        $request .= '/datastreams/';
        $request .= $dsid;
        $request .= '/content';

        if ($format) :
            $request .= '?format=' . $format;
        endif;

        return self::fedoraRequest($request);

    }

}

?>
