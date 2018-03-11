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
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\ParserException;

class IEEEController extends Controller {
    private static $parameter_query = array("healthcare_IoT_OR_health_IoT_OR_healthIoT" => '("healthcare IoT" OR "health IoT" OR "healthIoT")',
                                     "Internet_of_Medical_Things_OR_Internet_of_healthcare_things_OR_Internet_of_M-health_Things" => '("Internet of Medical Things" OR "Internet of healthcare things" OR "Internet of M-health Things")',
                                     "Internet_of_Things_AND_Health" => '("Internet of Things" AND *Health*)',
                                     "Internet_of_Things_AND_Healthcare" => '("Internet of Things" AND *Healthcare*)',
                                     "Internet_of_Things_AND_Medical" => '("Internet of Things" AND Medical)',
                                     "Medical_IoT_OR_IoT_Medical" => '("Medical IoT" OR "IoT Medical")');

    public function import_bibtex() {
        
        $path_file = "data_files/ieee/";
        $files = File::load($path_file);
        Util::showMessage("Start Import bibtex file from IEEE");
        foreach($files as $file) {
            Util::showMessage($file);
            $parser = new Parser();             // Create a Parser
            $parser->addTransliteration(Bibtex::$transliteration); //  Attach the Transliteration special characters to the Parser
            $listener = new Listener();         // Create and configure a Listener
            $parser->addListener($listener);    // Attach the Listener to the Parser
            $parser->parseFile($file);          // or parseFile('/path/to/file.bib')
            $entries = $listener->export();     // Get processed data from the Listener

            foreach($entries as $key => $article) {  
                $query = str_replace(array($path_file, ".bib"), "", $file);
                
                // Add new Parameter in variable article
                $article["search_string"] = self::$parameter_query[$query];
                $article["pdf_link"]        = Config::get('constants.pach_ieee') . "xpl/abstractSimilar.jsp?arnumber=" . $article["citation-key"];
                $article["document_url"]    = Config::get('constants.pach_ieee') . "document/" . $article["citation-key"];
                $article["bibtex"]          = json_encode($article["_original"]); // save bibtex in json
                $article["source"]          = Config::get('constants.source_ieee');
                $article["source_id"]       = $article["citation-key"];
                $article["file_name"]       = $file;
                
                $duplicate = 0;
                $duplicate_id = null;
                // Search if article exists
                $title_slug = Slug::slug($article["title"], "-");
                $article["title_slug"] = $title_slug;
                $document = Document::where(
                    [
                        ['title_slug', '=', $title_slug],
                        ['file_name', '=', $file],
                        ['source', '=', Config::get('constants.source_ieee')],
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
        Util::showMessage("Finish Import bibtex file from IEEE");
    }

    /**
     * Load Detail from Website ACM 
     *
     * @param  void
     * @return void
     */
    public function load_detail() {        
        Util::showMessage("Start Load detail from IEEE");

        $documents = Document::where(
            [
                ['source', '=', Config::get('constants.source_ieee')],
                ['duplicate', '=', '0'],
            ])
            ->whereNotNull('source_id')
            ->whereNull('metrics')
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
}
