<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Support\WebService;
use App\Http\Support\File;
use Config;
use Bibliophile\BibtexParse\ParseEntries;

class CapesController extends Controller {
    
    public function save_article_my_space(Request $request) {
        try {
            if (empty($request->input('query'))) {
                throw new \Exception("Query string not found.");
            }
            $query_string = urlencode($request->input('query'));
            echo "Page: 1 <br>";
            $time = time() . '000';
            $url = 'http://rnp-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/search.do?ct=facet&fctN=facet_lang&fctV=eng&rfnGrp=2&rfnGrpCounter=2&frbg=&rfnGrpCounter=1&indx=1&fn=search&mulIncFctN=facet_rtype&mulIncFctN=facet_rtype&dscnt=0&rfnIncGrp=1&rfnIncGrp=1&scp.scps=scope%3A(%22CAPES%22)%2CEbscoLocalCAPES%2Cprimo_central_multiple_fe&mode=Basic&vid=CAPES_V1&ct=facet&srt=rank&tab=default_tab&dum=true&fctIncV=newspaper_articles&fctIncV=articles&dstmp=' . $time . '&vl(freeText0)=' . $query_string;
            $html = $this->progress_capes($url);
            echo "Page: 2 <br>";
            $time = time() . '000';
            $url = 'http://rnp-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/search.do?ct=Next+Page&pag=nxt&indx=1&pageNumberComingFrom=1&frbg=&rfnGrpCounter=2&fn=search&indx=1&mulIncFctN=facet_rtype&mulIncFctN=facet_rtype&dscnt=0&scp.scps=scope%3A(%22CAPES%22)%2CEbscoLocalCAPES%2Cprimo_central_multiple_fe&rfnIncGrp=1&rfnIncGrp=1&vid=CAPES_V1&fctV=eng&mode=Basic&ct=facet&rfnGrp=2&tab=default_tab&srt=rank&fctN=facet_lang&dum=true&fctIncV=newspaper_articles&fctIncV=articles&dstmp=' . $time . '&vl(freeText0)=' . $query_string;
            $this->progress_capes($url);
            for($page=3;$page<=10;$page++) {
                echo "Page: " . $page . "<br>";
                $time = time() . '000';        
                $url = 'http://rnp-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/search.do?ct=Next+Page&pag=nxt&indx=' . ($page-2) . '1&pageNumberComingFrom=' . ($page-1) . '&frbg=&rfnGrpCounter=2&indx=' . ($page-2) . '1&fn=search&mulIncFctN=facet_rtype&mulIncFctN=facet_rtype&dscnt=0&scp.scps=scope%3A(%22CAPES%22)%2CEbscoLocalCAPES%2Cprimo_central_multiple_fe&rfnIncGrp=1&rfnIncGrp=1&fctV=eng&mode=Basic&vid=CAPES_V1&ct=Next%20Page&rfnGrp=2&srt=rank&tab=default_tab&fctN=facet_lang&dum=true&fctIncV=newspaper_articles&fctIncV=articles&dstmp=' . $time . '&vl(freeText0)=' . $query_string;
                $this->progress_capes($url);
            }

            $this->get_bibtex_from_my_space();

            return response("Successful", 200)
                  ->header('Content-Type', 'text/plain');

        } catch(\Exception $e) {
            // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status
            return response($e->getMessage(), 405)
                  ->header('Content-Type', 'text/plain');
        }
    }

    public function get_bibtex_from_my_space() {
        // if (empty($request->input('folder'))) {
        //     throw new \Exception("Query string not found.");
        // }
        // $idFolder = $request->input('folder');
        $cookie     = Config::get('constants.cookie_capes');
        $user_agent = Config::get('constants.user_agent');

        $idFolder = "1176590190";
        $total = 0;
        $url = 'https://rnp-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/basket.do?fn=display&vid=CAPES_V1&folderId=' . $idFolder;
        $parameters["referer"]  = "https://rnp-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/basket.do?vid=CAPES_V1&fn=display&fromLink=gotoeShelfUI&fromUserArea=true&fromPreferences=false&dscnt=0&dstmp=" . time() . '000' . "&fromLogin=true&fromLogin=true";
        $parameters["host"]     = "rnp-primo.hosted.exlibrisgroup.com";
        $html = WebService::loadURL($url, $cookie, $user_agent, array(), $parameters);        
        libxml_use_internal_errors(true) && libxml_clear_errors(); // for html5
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $dom->preserveWhiteSpace = true;
        $docs = array();
        
        foreach ($dom->getElementsByTagName('input') as $node) {        
            if ($node->getAttribute('name') == "docs") {
                $docs[] = $node->getAttribute('value');
            }
        }

        if (count($docs) > 0) {
            $fields = array(
                'encode' => 'UTF-8',
                'Button' => 'OK'
            );

            $fields_string = "";
            $bibtex        = "";
            foreach($docs as $key => $doc) {
                $fields_string .= 'docs='.$doc.'&'; 
                if (($key+1)%30 == 0) {
                    while (@ ob_end_flush()); // end all output buffers if any
                    $fields_string = rtrim($fields_string, '&');
                    echo $fields_string . "<br>";
                    $url = "http://rnp-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/PushToAction.do?pushToType=BibTeXPushTo&fromBasket=true&" . $fields_string;
                    $bibtex .= WebService::loadURL($url, $cookie, $user_agent, $fields, $parameters);
                    $fields_string = "";
                    $total += 30;
                    @ flush();
                }
            }

            if (!empty($fields_string)) {
                
                $url = "http://rnp-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/PushToAction.do?pushToType=BibTeXPushTo&fromBasket=true&" . $fields_string;
                $bibtex .= WebService::loadURL($url, $cookie, $user_agent, $fields, $parameters);
                $total += substr_count($fields_string, '&');
            }

            $name = "capes/" . strtolower(Config::get('constants.file')) . ".bib";
            file_put_contents($name, $bibtex);
            $retorno = "Successful, Total Records: " . $total;

            return response($retorno, 200)
                  ->header('Content-Type', 'text/plain');
        }
    }

    private function progress_capes($url) {
        // $cookie     = Webservice::getCookieFromSite($url);
        $cookie     = Config::get('constants.cookie_capes');
        $user_agent = Config::get('constants.user_agent');
        $dom = new \DOMDocument;
        $html = WebService::loadURL($url, $cookie, $user_agent);
        @$dom->loadHTML($html);
        $dom->preserveWhiteSpace = true;
        foreach ($dom->getElementsByTagName('a') as $node) {
            if ($node->hasAttribute( 'href' )) {
                while (@ ob_end_flush()); // end all output buffers if any
                if (strpos($node->getAttribute( 'href' ), 'basket.do') !== false) {
                    $urls = explode("?fn=", $node->getAttribute( 'href' ));
                    $url_action = "http://rnp-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/basket.do?fn=" . $urls[1];
                    Webservice::loadURL($url_action, $cookie, $user_agent);
                    echo ' <a href="' . $url . '">' . $url . '</a><br>';
                    @ flush();
                    sleep(2);
                }
            }
        }
        sleep(rand(3,5));
        return $html;
    }
}
