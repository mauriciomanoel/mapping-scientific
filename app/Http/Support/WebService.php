<?php

namespace App\Http\Support;

class WebService
{
    public static function loadUrl($url, $encoded="")
    {
        $ch 		= curl_init($url);
        curl_setopt( $ch, CURLOPT_POSTFIELDS,  $encoded );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);  
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_HTTPGET, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Mobile Safari/537.36');
        $output 	= curl_exec($ch);
        curl_close( $ch );
        return $output;
    }
}
