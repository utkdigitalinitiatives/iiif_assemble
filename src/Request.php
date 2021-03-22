<?php

namespace Src;

use Curl\Curl;

class Request{

    public static function getDatastream ($dsid, $pid) {

        // add this to a .env
        $fedora_url = $_ENV['FEDORA_URL'];
        $username = $_ENV['FEDORA_USER'];
        $password = $_ENV['FEDORA_PASS'];

        // build this dynamically based on dsid and pid
        $object = implode('%3A', $pid);

        // build the request
        $request = $fedora_url . '/objects/';
        $request .= $object;
        $request .= '/datastreams/';
        $request .= $dsid;
        $request .= '/content?format=xml';

        $curl = new Curl();
        $curl->verbose();
        $curl->setBasicAuthentication($username, $password);
        $curl->get($request);

        if ($curl->error) {
            $response = $curl->errorCode . ': ' . $curl->errorMessage;
        } else {
            $response = $curl->response;
        }

        $curl->close();

        return $response;

    }

}

?>
