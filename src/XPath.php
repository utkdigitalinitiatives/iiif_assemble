<?php

namespace Src;

use DOMDocument;
use DOMXPath;

class XPath
{

    private $domxpath;
    private $ns;

    public function __construct($xml)
    {

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $this->domxpath = new DOMXPath($doc);
        $this->ns = $doc->documentElement->namespaceURI;

    }

    public function globalQuery ($expression)
    {

        $expression = '//' . $expression;

        if ($this->ns) {
            $this->domxpath->registerNamespace('ns', $this->ns);
            $nodes = $this->domxpath->query(str_replace('//', '//ns:', $expression));
        } else {
            $nodes = $this->domxpath->query($expression);
        }

        $values = null;

        foreach ($nodes as $key => $node) :
            $values[$key] = $nodes[$key]->nodeValue;
        endforeach;

        return $values;

    }



    public function differentQuery ($expression) {

        return $expression;

    }

}

?>
