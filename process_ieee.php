<?php

    require_once("class/config.class.php");

    var_dump(ConnectionRDB::getInstance()); exit;
    $documents = file("ieeee_export2018.01.23-18.47.04.csv");
    foreach($documents as $document) {
        $data = explode("\",\"", $document);
        echo "<pre>"; var_dump($data); exit;
    }

?>