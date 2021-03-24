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

        $expression = '//' . $expression;

        if($this->ns) {
            $this->xpath->registerNamespace('ns', $this->ns);
            $nodes = $this->xpath->query(str_replace('//', '//ns:', $expression));
        } else {
            $nodes = $this->xpath->query($expression);
        }

        $values = null;

        foreach ($nodes as $key => $node) :
            $values[$key] = $nodes[$key]->nodeValue;
        endforeach;

        return $values;

    }

}

?>
