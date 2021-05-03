<?php

namespace Src;

error_reporting(E_ALL);

class Collection
{

    private $requestMethod;
    private $persistentIdentifier;

    public function __construct($requestMethod, $persistentIdentifier)
    {

        $this->requestMethod = $requestMethod;
        $this->persistentIdentifier = $persistentIdentifier;

    }

    public function processRequest()
    {

        switch ($this->requestMethod) {
            case 'GET':
                $response = self::theCollection();
                break;
            default:
                $response = self::noFoundResponse();
                break;
        }

        print $response;

    }

    private function theCollection()
    {

        $persistentIdentifier = implode('%3A', $this->persistentIdentifier);
        $object = Request::getObjects($persistentIdentifier);

        if ($object['status'] === 200) :
            $model = simplexml_load_string($object['body'])->objModels->model;
            if (self::isCollection($model)) :
                $object = Request::getObjects($persistentIdentifier, 'XML', true);
                return json_encode($object);
            else :
                $object['body'] = 'Object ' . str_replace('%3A', ':', $persistentIdentifier) . ' is not of object model islandora:collectionCModel.';
                return json_encode($object);
            endif;
        else :
            return json_encode($object);
        endif;

    }

    private static function isCollection ($islandoraModel) {

        $model = Utility::xmlToArray($islandoraModel);

        if (in_array('info:fedora/islandora:collectionCModel', $model)) :
            return true;
        else:
            return false;
        endif;

    }

}

?>
