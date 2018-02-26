<?php

namespace App\Http\Support;

class File {

    public static function load($path) {
        $arrFiles = array();
        $files = scandir($path);
        foreach($files as $file) {
            // ignore when directory
            $ignore = array_search($file, array(".", "..", ".DS_Store"));
            if ($ignore !== false) continue;
            if ( is_file($path . $file) ) {
                $arrFiles[] = $path . $file;
            }
        }
        return $arrFiles;
    }
}

?>