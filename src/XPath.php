<?php

namespace Src;

use DOMDocument;
use DOMXPath;
use SimpleXMLElement;

class XPath
{

    private $domxpath;
    private $ns;

    public function __construct($xml)
    {

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($xml);

        $this->domxpath = new DOMXPath($doc);
        $this->ns = $doc->documentElement->namespaceURI;

    }

    public function query ($expression, $global = false)
    {

        if ($this->ns) {
            $this->domxpath->registerNamespace('ns', $this->ns);
            $nodes = $this->domxpath->query('ns:' . str_replace('/', '/ns:', $expression));
        } else {
            $nodes = $this->domxpath->query($expression);
        }

        $values = null;

        foreach ($nodes as $key => $node) :
            $values[$key] = $nodes[$key]->nodeValue;
        endforeach;

        return $values;

    }

    public function queryFilterByAttribute ($expression, $global = false, $attribute, $attributeValues)
    {

        if ($this->ns) {
            $this->domxpath->registerNamespace('ns', $this->ns);
            $nodes = $this->domxpath->query('ns:' . $expression);
        } else {
            $nodes = $this->domxpath->query($expression);
        }

        $values = null;

        foreach ($nodes as $key => $node) :
            $value = $nodes[$key]->getAttribute($attribute);
            $values[$value] = $nodes[$key]->nodeValue;
        endforeach;

        foreach ($values as $key => $value) :
            if (!in_array($key, $attributeValues)) :
                unset($values[$key]);
            endif;
        endforeach;

        return array_values($values);

    }

    public function queryElement ($expression)
    {

        if ($this->ns) {
            $this->domxpath->registerNamespace('ns', $this->ns);
            $nodes = $this->domxpath->query('ns:' . $expression);
        } else {
            $nodes = $this->domxpath->query($expression);
        }

        return $nodes;

    }

    public function differentQuery ($expression) {

        return $expression;

    }

}

class SimpleXPath
{
    private $domxpath;
    private $ns;

    public function __construct($xml)
    {

        $this->mods = $xml;
        $this->doc = new SimpleXMLElement($xml);
        $this->doc->registerXPathNamespace("mods", "http://www.loc.gov/mods/v3");
    }

    public function get_interviewees() {
        return $this->doc->xpath('//mods:name[mods:role[mods:roleTerm[@valueURI="http://id.loc.gov/vocabulary/relators/ive"]]]/mods:namePart');
    }
}

?>
