<?php

namespace App\Http\Support;
setlocale(LC_ALL, 'en_US.UTF8');
class Util {

    public static function showMessage($message) {
        while (@ ob_end_flush()); // end all output buffers if any
            echo $message . "<br>";
        @ flush();
        // ob_start();
        //     echo $message . "\r\n";
        //     $log = ob_get_contents();
        //     file_put_contents(FILE_LOG, $log, FILE_APPEND);
        // @ob_end_clean();  
    }

    public static function getDOM($value) 
    {
        @libxml_use_internal_errors(true) && @libxml_clear_errors(); // for html5
        $dom = new \DOMDocument('1.0', 'UTF-8');
        if ($value != false) {
            $dom->loadHTML(mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8'));
            $dom->preserveWhiteSpace = true;
        }
        return $dom;
    }

    public static function getHTMLFromClass($html, $classname, $element="*") {
        $dom = self::getDOM($html);
        $finder = new \DomXPath($dom);
        $nodes = $finder->query("//" . $element . "[contains(@class, '$classname')]");
        $values = array();
        
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
}

?>