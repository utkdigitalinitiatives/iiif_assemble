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

        if ($values != null) {
            foreach ($values as $key => $value) :
                if (!in_array($key, $attributeValues)) :
                    unset($values[$key]);
                endif;
            endforeach;

            return array_values($values);
        } else {
            return null;
        }


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

    public function __construct($xml)
    {

        $this->mods = $xml;
        $this->doc = new SimpleXMLElement($xml);
        $this->doc->registerXPathNamespace("mods", "http://www.loc.gov/mods/v3");
    }

    public function get_values($expression) {
        $return_value = array();
        $matches = $this->doc->xpath($expression);
        foreach ($matches as $value) {
            array_push($return_value, (string)$value);
        }
        return $return_value;
    }

    private function get_role_terms() {
        $return_value = array();
        $matches = $this->doc->xpath('mods:name/mods:role/mods:roleTerm');
        foreach ($matches as $value) {
            if (in_array((string)$value, $return_value)==false) {
                array_push($return_value, (string)$value);
            }
        }
        return $return_value;
    }

    public function get_names() {
        $names = array();
        $roleterms = $this->get_role_terms();
        foreach ($roleterms as $role) {
            $current = (string)$role;
            $names[$current]  = $this->get_values("mods:name[mods:role[mods:roleTerm[text()='{$current}']]]/mods:namePart");
        }
        return $names;
    }
}

?>
