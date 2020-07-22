<?php

namespace App\Http\Controllers;

set_time_limit(0);

use App\Document;
use App\Bibtex;
use Config;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Support\Slug;
use App\Http\Support\File;
use App\Http\Support\Util;
use App\Http\Support\Webservice;
use App\Http\Support\CreateDocument;
use App\Http\Support\ParserCustom;
use RenanBr\BibTexParser\Listener;
use Carbon\Carbon;

class PMCController extends Controller {    
                                     
    public function import_bibtex() {

        $query = '("Internet of Things" OR "IoT" OR "iomt" OR "*health*") AND ("*elder*" OR "old people" OR "older person" OR "senior citizen" OR "aged people" OR "aged population" OR "aging population" OR "aging people") AND ("Smart City" OR "Smart Cities" OR "Smart health" OR "Smart home*")';
        $path_file = storage_path() . "/data_files/pmc/bib/";
        $files = File::load($path_file);

        Util::showMessage("Start Import bibtex file from PMC");
        foreach($files as $file) 
        {
            Util::showMessage($file);
           
            $parser = new ParserCustom();             // Create a Parser
            $listener = new Listener();         // Create and configure a Listener
            $parser->addListener($listener);    // Attach the Listener to the Parser
            $parser->parseFile($file);          // or parseFile('/path/to/file.bib')
            $entries = $listener->export();     // Get processed data from the Listener
            Util::showMessage("Import " . count($entries) . " documents");
 
            foreach($entries as $key => $article) {  
                
                // Add new Parameter in variable article
                $article["search_string"]   = $query;
                $article["pdf_link"]        = $article["url"];
                $article["bibtex"]          = json_encode($article["_original"]); // save bibtex in json
                $article["doi"]             = (!empty($article["doi"])) ? $article["doi"] : "";
                $article["source"]          = Config::get('constants.source_pmc');
                $article["source_id"]       = $article["id"];
                $article["file_name"]       = basename($file);
                
                $duplicate = 0;
                $duplicate_id = null;
                // Search if article exists
                $title_slug = Slug::slug($article["title"], "-");
                $article["title_slug"] = $title_slug;
                $document = Document::where(
                    [
                        ['title_slug', '=', $title_slug],
                        ['file_name', '=', $file],
                        ['source', '=', Config::get('constants.source_pmc')],
                    ])
                    ->first();
                if (empty($document)) {
                    // Create new Document
                    $document_new = CreateDocument::process($article);

                    // Find if exists article with title slug
                    $document = Document::where('title_slug', $title_slug)->first();                
                    if (!empty($document)) {
                        $duplicate      = 1;
                        $duplicate_id   = $document->id;
                    }
                    $document_new->duplicate        = $duplicate;
                    $document_new->duplicate_id     = $duplicate_id;
                    $document_new->save();

                } else {
                    Util::showMessage("Article already exists: " . $article["title"]  . " - " . $file);
                    Util::showMessage("");
                }
                
            }
        }
        Util::showMessage("Finish Import bibtex file from PMC");
    }

    public function import_json() {

        $query = null;
        $path_file = storage_path() . "/data_files/pmc/json/";
        $files = File::load($path_file);
        Util::showMessage("Start Import bibtex file from Springer");
        try 
        {
            foreach($files as $file) 
            {

                Util::showMessage($file);
                $text = file_get_contents($file);
                $articles = json_decode($text, true);
                Util::showMessage("Total articles: " . count($articles));
 
                foreach($articles as $key => $article) {  
                    // Add new Parameter in variable article
                    $article["search_string"]   = $query;
                    $article["pdf_link"]        = (!empty($article["url"])) ? $article["url"] : null;
                    $article["bibtex"]          = json_encode($article); // save bibtex in json
                    $article["doi"]             = (!empty($article["doi"])) ? $article["doi"] : "";
                    $article["source"]          = Config::get('constants.source_pmc');
                    $article["source_id"]       = (!empty($article["id"])) ? $article["id"] : null;
                    $article["citation-key"]    = null;
                    $article["file_name"]       = $file;
                    $article["author"]          = (!empty($article["author"])) ? utf8_encode($article["author"]) : null;
                    
                    
                    $duplicate = 0;
                    $duplicate_id = null;
                    // Search if article exists
                    $title_slug = Slug::slug($article["title"], "-");
                    $article["title_slug"] = $title_slug;
                    $document = Document::where(
                        [
                            ['title_slug', '=', $title_slug],
                            ['file_name', '=', $file],
                            ['source', '=', Config::get('constants.source_pmc')],
                        ])
                        ->first();
                    if (empty($document)) {
                        // Create new Document
                        $document_new = CreateDocument::process($article);

                        // Find if exists article with title slug
                        $document = Document::where('title_slug', $title_slug)->first();                
                        if (!empty($document)) {
                            $duplicate      = 1;
                            $duplicate_id   = $document->id;
                        }
                        $document_new->duplicate        = $duplicate;
                        $document_new->duplicate_id     = $duplicate_id;
                        $document_new->save();

                    } else {
                        Util::showMessage("Article already exists: " . $article["title"]  . " - " . $file);
                        Util::showMessage("");
                    }
                    
                }
            }
        } catch(ParserException $ex)  
        {
            Util::showMessage("ParserException: " . $ex->getMessage());
        } catch(\Exception $ex)  
        {
            Util::showMessage("Exception: " . $ex->getMessage());
        }
        Util::showMessage("Finish Import bibtex file from PMC");
    }

    /**
     * Load Detail from Website ACM 
     *
     * @param  void
     * @return void
     */
    public function load_detail(Request $request) {        
        Util::showMessage("Start Load detail from IEEE");

        $last_day_update = ($request->input('last_day_update')) ? (int) $request->input('last_day_update') : 0;
        $documents = Document::where(
            [
                ['source', '=', Config::get('constants.source_ieee')],
                ['duplicate', '=', '0'],
            ])
            ->whereNotNull('source_id')
            ->where('updated_at', '<', Carbon::now()->subDays($last_day_update))
            ->get();
        
        Util::showMessage("Total of Articles: " . count($documents));        
        if (count( $documents )) 
        {
            $url = Config::get('constants.api_rest_ieee') . "document/". $documents[0]->source_id . "/metrics";
            $cookie         = WebService::getCookie($url);
            $user_agent     = Config::get('constants.user_agent');
                    
            foreach($documents as $key => $document) {
                $url = Config::get('constants.api_rest_ieee') . "document/". $document->source_id . "/metrics";
                Util::showMessage($url);
                @$parameters["referer"] = $url;
                $html_metric = WebService::loadURL($url, $cookie, $user_agent, array(), $parameters);            
                $metrics = json_decode($html_metric, true);        
                if (!empty($metrics) && is_array($metrics)) {

                    $url = Config::get('constants.api_rest_ieee') . "document/". $document->source_id . "/citations?count=30";
                    @$parameters["referer"] = $url;
                    $html_citaticon_metric      = WebService::loadURL($url, $cookie, $user_agent, array(), $parameters);            
                    $citations                  = json_decode($html_citaticon_metric, true);
                    $document->citation_count   = @$citations["nonIeeeCitationCount"] + @$citations["ieeeCitationCount"] + @$citations["patentCitationCount"];
                    $document->download_count   = @$metrics["metrics"]["totalDownloads"];
                    $document->metrics          = $html_metric . " | " . $html_citaticon_metric;
                    $document->save();
                }

                $rand = rand(2,4);
                Util::showMessage("$rand seconds pause for next step.");
                sleep($rand);
            }
        }
        Util::showMessage("Finish Load detail from IEEE");
    }  

    /**
     * Load Detail from Website ACM 
     *
     * @param  void
     * @return void
     */
    public function download_pdf() {        
        Util::showMessage("Start Download PDF from IEEE");

        $documents = Document::where(
            [
                ['source', '=', Config::get('constants.source_ieee')],
                ['duplicate', '=', '0'],
                ['id', '=', '49'],
                
            ])
            ->whereNotNull('source_id')
            ->get();

            $url = "http://ieeexplore.ieee.org/ielx7/6287639/7042252/0711/3786.pdf";
        $cookie = WebService::getCookie($url);
        // var_dump($cookie); exit;
        $cookie = 'ERIGHTS=EfgeiRRZH3a6XoPU2uhoCWzUhgCgNVGB*kRyxxlihZqWkRjMTVON79yQx3Dx3D-18x2dRGUx2Ffh1S1m4eA24DqhpckAx3Dx3D0MlrsuFRlqP14Q2xx08osDgx3Dx3D-ICiWWFk1V0sztTx2FaouVejAx3Dx3D-HkZjWsU0yPIC0RD8x2BnFD6Ax3Dx3D; WLSESSION=220357260.20480.0000; TS01b943bb=012f350623945856cf346e18aeefc121bf27434cd19388bf0fb601dc862acbc044f33263e63708e845414802b7f2667e8426b5c69274286795375bcc1640017c56d29ff3d41417a5068ee48335ec9f14421090fce0; JSESSIONID=I-Ipr5ouVzrUZSvFJym4pMCNQR0obh2mOW2vyDtqoQ_IfM_xFSkC!220562568; ipList=150.161.49.96; TS011813a0=012f350623dc4f7e5c37d8d788c4a5fd3811ae58ce9388bf0fb601dc862acbc044f33263e6a1098ab14e7c9291890cb536551c7a872c1fc3ed154a1eac580c70847d1e9569530e07a61630874b3a467b4282e45996; TS01f64340=012f350623f6732b6b01794c130fe71a0c3cfd08819388bf0fb601dc862acbc044f33263e6a1098ab14e7c9291890cb536551c7a873ae20f8ba9d9c4cd88b4345298a39827773eb14b311191aa344befbf62e65952; fp=dbebb983b3b0d7fc7208b36fa5d6ad61; unicaID=WWmxxrVPPeb-awWzRH9; AMCVS_8E929CC25A1FB2B30A495C97%40AdobeOrg=1; s_cc=true; AMCV_8E929CC25A1FB2B30A495C97%40AdobeOrg=1687686476%7CMCIDTS%7C17606%7CMCMID%7C11647193990017840513624781554021676655%7CMCAAMLH-1521722601%7C4%7CMCAAMB-1521722601%7CRKhpRz8krg2tLO6pguXWp5olkAcUniQYPHaMWWgdJ3xzPWQmdj0y%7CMCOPTOUT-1521125001s%7CNONE%7CMCAID%7CNONE%7CMCSYNCSOP%7C411-17613%7CvVersion%7C3.0.0; TS01d430e1=012f350623f02fbf098d364010025c15aba27b81039388bf0fb601dc862acbc044f33263e6a1098ab14e7c9291890cb536551c7a879b287890065131d2cd7e89dc4d6d6ceb97b5673c8e8ebddc93e5358bee0eb2be3755f09060f710e8dfced97454fb0c65947e7ab59a5160cf08c789c2b93b748b; seqId=95583; visitstart=09:44; utag_main=v_id:016229afaba00003062dedbe50d905073003a06b0086e$_sn:1$_ss:0$_st:1521119683866$ses_id:1521117801379%3Bexp-session$_pn:4%3Bexp-session$vapi_domain:ieee.org';
        $user_agent     = Config::get('constants.user_agent');

        foreach($documents as $document) {
            
            $url = "http://ieeexplore.ieee.org/ielx7/6287639/7042252/0711/3786.pdf";

            $pdf_file = WebService::loadURL($url, $cookie, $user_agent);

            
            // $pdf = file_get_contents($url_pdf);
            var_dump($pdf_file);
            // file_put_contents("c:\\temp\\07113786.pdf", $pdf);
                        
        }

    }

}