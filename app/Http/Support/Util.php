<?php

namespace App\Http\Support;

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
}

?>