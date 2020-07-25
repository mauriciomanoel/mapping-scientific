<?php

namespace App\Http\Support;

class HTML {

    public static function getFromClass($html, $classname, $element="*") {
        $dom = self::getDOM($html);
        $finder = new \DomXPath($dom);
        $nodes = $finder->query("//" . $element . "[contains(@class, '$classname')]");
        $values = array();
        var_dump($nodes); exit;
        foreach($nodes as $node) {
            $values[] = $dom->saveHTML($node);
        }
        
        return $values;
    }
    public static function getAttributeFromClass($html, $classname, $attibutename) {
        $dom = self::getDOM($html);
        $finder = new \DomXPath($dom);
        $nodes = $finder->query("//*[contains(@class, '$classname')]");
        $values = array();
        
       
        foreach($nodes as $node) {
            $values[] = trim($node->getAttribute($attibutename));
        }
        
        return $values;
    }
    private static function getDOM($value) 
    {
        libxml_use_internal_errors(true) && libxml_clear_errors(); // for html5
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8'));
        $dom->preserveWhiteSpace = true;
        
        return $dom;
    }
    public static function getURL($html) {
        preg_match_all('/href="([^"]+)"/', $html, $arr, PREG_PATTERN_ORDER);
        if (!empty($arr[1])) {
            return $arr[1][0];
        }
        return "";
    }


}

?>