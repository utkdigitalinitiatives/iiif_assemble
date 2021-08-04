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
            $response['body'] = $curl->rawResponse;
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

    public static function getObjects($pid, $format = 'XML', $isMemberOf = false) {

        if ($isMemberOf) :
            $request = $_ENV['FEDORA_URL'] . '/objects';
            $request .= '?query=pid%7E' . explode('%3A', $pid)[1] . '*&pid=true';
            $request .= '&resultFormat=' . $format;
        else :
            $request = $_ENV['FEDORA_URL'] . '/objects/' . $pid;
            $request .= '?format=' . $format;
        endif;

        return self::curlRequest($request);

    }

    public static function getBookPages($pid, $format = 'csv') {

        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&format=' . $format .'&query=';

        $query = "PREFIX fedora-model: <info:fedora/fedora-system:def/model#> PREFIX fedora-rels-ext: ";
        $query .= "<info:fedora/fedora-system:def/relations-external#> PREFIX isl-rels-ext: ";
        $query .= "<http://islandora.ca/ontology/relsext#> SELECT \$page \$numbers \$title FROM <#ri> WHERE {{ \$page ";
        $query .= "fedora-rels-ext:isMemberOf <info:fedora/" . $pid ."> ; isl-rels-ext:isPageNumber \$numbers ; <http://purl.org/dc/elements/1.1/title> \$title . }}";

        $request .= self::escapeQuery($query);

        return self::curlRequest($request);

    }

    public static function getCollectionItems($pid, $format = 'csv') {

        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&format=' . $format .'&query=';

        $query = "PREFIX fedora-model: <info:fedora/fedora-system:def/model#> PREFIX fedora-rels-ext: ";
        $query .= "<info:fedora/fedora-system:def/relations-external#> PREFIX isl-rels-ext: ";
        $query .= "<http://islandora.ca/ontology/relsext#> SELECT \$item FROM <#ri> WHERE {{ \$item ";
        $query .= "fedora-rels-ext:isMemberOfCollection <info:fedora/" . $pid ."> .}}";

        $request .= self::escapeQuery($query);

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

    public static function escapeQuery ($query) {

        $searchReplace = array(
            '*' => '%2A',
            ' ' => '%20',
            '<' => '%3C',
            ':' => '%3A',
            '>' => '%3E',
            '#' => '%23',
            '\n' => '',
            '?' => '%3F',
            '{' => '%7B',
            '}' => '%7D',
            '/' => '%2F'
        );

        return str_replace(
            array_keys($searchReplace),
            array_values($searchReplace),
            $query
        );

    }

}

?>
