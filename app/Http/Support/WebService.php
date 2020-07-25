<?php

namespace App\Http\Support;

class WebService {

    public static function loadURL($url, $cookie, $user_agent, $fields=array(), $parameters=array()) 
    {        
        $ch 		= curl_init($url);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);  
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        if (empty($fields) && count($fields) ==0) {
            curl_setopt( $ch, CURLOPT_HTTPGET, 1 );
        } else {
            $fields_string = "";
            foreach($fields as $key => $value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string, '&');
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        }
        $header[0] = "Accept: application/json,text/plain,text/xml,application/xml,application/xhtml+xml,";
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Accept-Charset: UTF-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3";
        $header[] = "Upgrade-Insecure-Requests: 1";
        $header[] = "Pragma: ";
        // $header[] = "Cookie: " . $cookie;
        if (!empty($parameters["host"])) {
            $header[] = "Host: " . $parameters["host"];
        }
        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        if (!empty($parameters["referer"])) {
            curl_setopt( $ch, CURLOPT_REFERER, $parameters["referer"]);
        }
        curl_setopt( $ch, CURLOPT_ENCODING, "gzip, deflate, br");
        curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent);
        $output 	= curl_exec($ch);
        curl_close( $ch );
        var_dump($output);
        return $output;
    }

    public static function getCookie($url) 
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt( $ch, CURLOPT_HTTPGET, 1 ); 
        // get headers too with this line
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);
        // get cookie
        // multi-cookie variant contributed by @Combuster in comments
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        $cookie = "";
        foreach($cookies as $key => $value) {
            $cookie .= $key . "=" . $value . ", ";
        }
        $cookie = rtrim($cookie, ", ");
        return $cookie;
    }

}
