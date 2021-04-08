<?php

namespace Src;

use Curl\Curl;

class Request {

    private static function curlRequest ($request, $fedora = true) {

        $curl = new Curl();
        $curl->verbose();

        if ($fedora) :
            $curl->setBasicAuthentication($_ENV['FEDORA_USER'], $_ENV['FEDORA_PASS']);
        endif;

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

    public static function responseStatus ($uri, $status = 200) {

        $response = self::curlRequest($uri, false);

        if ($response['status'] === $status) {
            return true;
        } else {
            return false;
        }

    }

    public static function responseBody($uri) {

        $response = self::curlRequest($uri, false);
        return $response['body'];

    }

    public static function getObjectModels ($pid, $format = 'XML') {

        $request = $_ENV['FEDORA_URL'] . '/objects/' . $pid;

        if ($format) :
            $request .= '?format=' . $format;
        endif;

        return self::curlRequest($request);

    }

    public static function getDatastream ($dsid, $pid, $format = 'XML') {

        $request = $_ENV['FEDORA_URL'] . '/objects/' . $pid;
        $request .= '/datastreams/';
        $request .= $dsid;
        $request .= '/content';

        if ($format) :
            $request .= '?format=' . $format;
        endif;

        return self::curlRequest($request);

    }

}

?>
