<?php

namespace App\Http\Support;

class File {

    public static function load($path, $ignoreFiles = array()) {
        $arrFiles = array();
        $files = scandir($path);        

        foreach($ignoreFiles as $ignoreFile) {
            $ignoreExtension[] = $ignoreFile;
        }
             
        foreach($files as $file) {            
            // Ignoring Directories
            $ignore = array_search($file, array(".", "..", ".DS_Store"));
            if ($ignore !== false) continue;

            // Ignoring files
            $extension = pathinfo($file, PATHINFO_EXTENSION);            
            $ignore = array_search($extension, $ignoreFiles);
            if ($ignore !== false) continue;

            if ( is_file($path . $file) ) {
                $arrFiles[] = $path . $file;
            }
        }
        return $arrFiles;
    }
}

?>