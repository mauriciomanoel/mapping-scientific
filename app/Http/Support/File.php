<?php

namespace App\Http\Support;

class File {

    public static function load($path) {
        $arrFiles = array();
        $files = scandir($path);
        foreach($files as $file) {
            // ignore when directory
            $dir = array_search($file, array(".", ".."));
            if ($dir === 0 || $dir === 1) continue;
            if ( is_file($path . $file) ) {
                $arrFiles[] = $path . $file;
            }
        }
        return $arrFiles;
    }


}

?>