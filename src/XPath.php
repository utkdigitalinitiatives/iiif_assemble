<?php

namespace Src;

use DOMDocument;
use DOMXPath;

class XPath
{

    private $xpath;
    private $ns;

    public function __construct($xml)
    {

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $this->xpath = new DOMXPath($doc);
        $this->ns = $doc->documentElement->namespaceURI;

    }

    public function query ($expression) {

        if($this->ns) {
            $this->xpath->registerNamespace("ns", $this->ns);
            $nodes = $this->xpath->query(str_replace('//', '//ns:', $expression));
        } else {
            $nodes = $this->xpath->query($expression);
        }

//        print_r ($nodes);

        return $nodes[0]->nodeValue;

    }

}