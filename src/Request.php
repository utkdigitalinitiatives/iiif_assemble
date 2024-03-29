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

    public static function get_thumbnail_details ($uri) {
        $response = self::curlRequest($uri, false);
        $decoded_response = json_decode($response['body'], true);
        $details = array(
            'is_iiif'=>false
        );
        if ($response['status'] === 200) {
            $details['is_iiif'] = true;
            $details['width'] = $decoded_response['width'];
            $details['height'] = $decoded_response['height'];
            $details['thumbnail_uri'] = str_replace('info.json','full/max/0/default.jpg', $uri);
            $details['service'] = [
                (object) [
                    '@id' => str_replace('/info.json','', $uri),
                    '@type' => 	'http://iiif.io/api/image/2/context.json',
                    '@profile' => 'http://iiif.io/api/image/2/level2.json'
                ]
            ];
        }
        return $details;

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
        $query .= "fedora-rels-ext:isMemberOf <info:fedora/" . $pid ."> ; isl-rels-ext:isPageNumber \$numbers ;";
        $query .= "fedora-model:label \$title . }}";

        $request .= self::escapeQuery($query);

        return self::curlRequest($request);

    }

    public static function getCompoundParts($pid, $format = 'csv') {
        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&format=' . $format .'&query=';
        $escaped_pid = str_replace('%3A', "%5F", $pid);
        $query = "PREFIX fedora-model: <info:fedora/fedora-system:def/model#> PREFIX fedora-rels-ext: ";
        $query .= "<info:fedora/fedora-system:def/relations-external#> PREFIX isl-rels-ext: ";
        $query .= "<http://islandora.ca/ontology/relsext#> SELECT \$part \$numbers \$title \$model FROM <#ri> WHERE {{ \$part ";
        $query .= "fedora-rels-ext:isConstituentOf <info:fedora/" . $pid ."> ; isl-rels-ext:isSequenceNumberOf" . $escaped_pid . " \$numbers ;";
        $query .= "fedora-model:label \$title ; fedora-model:hasModel \$model  . }}";

        $request .= self::escapeQuery($query);

        return self::curlRequest($request);
    }

    public static function getCollectionItems($pid, $format = 'csv') {

        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&format=' . $format .'&query=';

        $query = "PREFIX fedora-model: <info:fedora/fedora-system:def/model#> PREFIX fedora-rels-ext: ";
        $query .= "<info:fedora/fedora-system:def/relations-external#> PREFIX isl-rels-ext: ";
        $query .= "<http://islandora.ca/ontology/relsext#> SELECT \$item \$label FROM <#ri> WHERE {{ \$item ";
        $query .= "fedora-rels-ext:isMemberOfCollection <info:fedora/" . $pid ."> ; <info:fedora/fedora-system:def/model#label> \$label . }} ";

        $request .= self::escapeQuery($query);

        return self::curlRequest($request);

    }

    public static function getCollectionPidIsPartOf($pid, $format = 'csv') {

        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&format=' . $format .'&query=';

        $query = "PREFIX fedora-rels-ext: <info:fedora/fedora-system:def/relations-external#>";
        $query .= "SELECT \$collection FROM <#ri> WHERE {{ <info:fedora/" . $pid ."> fedora-rels-ext:isMemberOfCollection \$collection . }}";
        $request .= self::escapeQuery($query);

        return self::curlRequest($request);
    }

    public static function getCompoundObjectPidIsPartOf($pid, $format = 'csv') {

        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&format=' . $format .'&query=';

        $query = "PREFIX fedora-rels-ext: <info:fedora/fedora-system:def/relations-external#>";
        $query .= "SELECT \$compound FROM <#ri> WHERE {{ <info:fedora/" . $pid ."> fedora-rels-ext:isConstituentOf \$compound . }}";
        $request .= self::escapeQuery($query);

        return self::curlRequest($request);
    }
  
    public static function getBibframeDuration($pid, $dsid, $format = 'csv') {

        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&format=' . $format .'&query=';

        $query = "PREFIX bibframe: <http://id.loc.gov/ontologies/bibframe/#>";
        $query .= "SELECT \$duration FROM <#ri> WHERE {{ <info:fedora/" . $pid ."/" . $dsid . "> bibframe:duration ?duration . }}";

        $request .= self::escapeQuery($query);

        return self::curlRequest($request);

    }


    public static function getDatastreams($pid, $format = 'csv'){
        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&format=' . $format .'&query=';
        $query = "PREFIX fedora: <info:fedora/fedora-system:def/view#> SELECT \$o FROM <#ri> WHERE {{ ";
        $query .= "<info:fedora/" . $pid ."> fedora:disseminates \$o . }}";

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

    public static function getTextTranscript($dsid, $pid) {
        $transcript = Request::getDatastream($dsid, $pid);
        $canvasAnnotations = (object)[];
        if ($transcript['status'] == 200) {
            $new_transcript = explode('Page ', $transcript['body']);
            foreach ($new_transcript as $part) {
                if ($part !== ""){
                    $canvasNumb = (int)explode("\n", $part)[0] - 1;
                    $text =preg_split("/\d+\n/", $part, -1, PREG_SPLIT_NO_EMPTY);
                    $annotationText = trim($text[0]);
                    $canvasAnnotations->$canvasNumb = $annotationText;
                }
            }
        }
        return $canvasAnnotations;
    }

    public static function getMetadataObjects ($field, $value, $format = 'csv') {
        $request = $_ENV['FEDORA_URL'] . '/risearch?type=tuples&lang=sparql&limit=100&format=' . $format .'&query=';
        $query = "SELECT \$pid \$label FROM <#ri> WHERE {{ \$pid <" . $field . "> '"  . $value . "' ; <info:fedora/fedora-system:def/model#label> \$label . }}";
        $request .= self::escapeQuery($query);

        return self::curlRequest($request);
    }

    public static function getCreativeCommons($uri) {
        $request = "http://api.creativecommons.org/rest/1.5/details?license-uri=" . $uri;

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
