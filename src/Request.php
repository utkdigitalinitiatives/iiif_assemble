<?php

namespace Src;

use Curl\Curl;

class Request {

    private static function fedoraRequest ($request) {

        $curl = new Curl();
        $curl->verbose();
        $curl->setBasicAuthentication($_ENV['FEDORA_USER'], $_ENV['FEDORA_PASS']);
        $curl->get($request);

        $response['request'] = $request;
        $response['status'] = $curl->httpStatusCode;

        if ($curl->error) {
            $response['body'] = $curl->errorCode . ': ' . $curl->errorMessage;
        } else {
            $response['body'] = $curl->response;
        }

        usleep(50000);

        return $response;

    }

    public static function getObjectModels ($pid, $format = 'XML') {

        $request = $_ENV['FEDORA_URL'] . '/objects/' . $pid;

        if ($format) :
            $request .= '?format=' . $format;
        endif;

        return self::fedoraRequest($request);

    }

    public static function getDatastream ($dsid, $pid, $format = 'XML') {

        $request = $_ENV['FEDORA_URL'] . '/objects/' . $pid;
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
