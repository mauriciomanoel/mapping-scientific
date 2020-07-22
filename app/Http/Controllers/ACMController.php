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
use App\Http\Support\Webservice;
use App\Http\Support\Util;
use App\Http\Support\CreateDocument;
use App\Http\Support\HTML;
use App\Http\Support\ParserCustom;
use RenanBr\BibTexParser\Listener;

class ACMController extends Controller {

    private static $query = null;

    public function import_bibtex() {

        
        $path_file = storage_path() . "/data_files/acm/bib/";
        $files = File::load($path_file);
        
        Util::showMessage("Start Import bibtex file from ACM");
        foreach($files as $file) {
            Util::showMessage($file);
            $parser = new ParserCustom();             // Create a Parser            
            $listener = new Listener();         // Create and configure a Listener
            $parser->addListener($listener);    // Attach the Listener to the Parser
            $parser->parseFile($file);          // or parseFile('/path/to/file.bib')
            $entries = $listener->export();     // Get processed data from the Listener
            
            Util::showMessage("Total articles: " . count($entries));

            foreach($entries as $key => $article) {
                // $query = str_replace(array($path_file, ".bib"), "", $file);
                
                $source_id = 0;
                // Add new Parameter in variable article
                $article["search_string"]   = self::$query;
                if (isset($article["acmid"])) {
                    $article["document_url"]    = Config::get('constants.pach_acm') . "citation.cfm?id=" . $article["acmid"];
                    $source_id                  = $article["acmid"];
                    $article["source_id"]       = $source_id;
                }
                $article["bibtex"]          = json_encode($article["_original"]); // save bibtex in json
                $article["source"]          = Config::get('constants.source_acm');
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
                        ['source', '=', Config::get('constants.source_acm')],
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
        Util::showMessage("Finish Import bibtex file from ACM");
        // self::load_detail();
    }

    public function import_json() {
        
        $path_file = storage_path() . "/data_files/acm/json/";
        $files = File::load($path_file);
        Util::showMessage("Start Import JSON file from ACM");
        try 
        {
            foreach($files as $file) 
            {

                Util::showMessage($file);
                $text = file_get_contents($file);
                $articles = json_decode($text, true);
                Util::showMessage("Total articles: " . count($articles));

                foreach($articles as $key => $article) {
                    
                    $source_id = 0;
                    // Add new Parameter in variable article
                    $article["search_string"]   = self::$query;
                    if (isset($article["acmid"])) {
                        $article["document_url"]    = Config::get('constants.pach_acm') . "citation.cfm?id=" . $article["acmid"];
                        $source_id                  = $article["acmid"];
                        $article["source_id"]       = $source_id;
                    }
             
                    $article["bibtex"]          = json_encode($article); // save bibtex in json
                    $article["source"]          = Config::get('constants.source_acm');
                    $article["file_name"]       = $file;
                    $article["citation-key"]    = null;

                    if (empty($article["abstract"]))  {
                        $article["abstract"] = null;
                    }

                    if (empty($article["pages"]))  {
                        $article["pages"] = null;
                    }
                    
                    $duplicate = 0;
                    $duplicate_id = null;
                    // Search if article exists
                    $title_slug = Slug::slug($article["title"], "-");
                    $article["title_slug"] = $title_slug;
                    $document = Document::where(
                        [
                            ['title_slug', '=', $title_slug],
                            ['file_name', '=', $file],
                            ['source', '=', Config::get('constants.source_acm')],
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

        Util::showMessage("Finish Import bibtex file from ACM");
    }

    /**
     * Load Detail from Website ACM 
     *
     * @param  void
     * @return void
     */
    public function load_detail() {
        Util::showMessage("Start Load detail from ACM");
        $documents = Document::where(
            [
                ['source', '=', Config::get('constants.source_acm')],
                ['duplicate', '=', '0'],
            ])
            ->whereNotNull('source_id')
            ->whereNull('metrics')
            ->get();

        foreach($documents as $document) {
            $url            = Config::get('constants.url_acm_abstract') . $document->source_id;
            Util::showMessage($url);
            $user_agent     = Config::get('constants.user_agent');
            $cookie         = WebService::getCookie($url);
            $abstract       = trim(strip_tags(WebService::loadURL($url, $cookie, $user_agent))); // load abstract 
            $html_article   = WebService::loadURL($document->document_url, $cookie, $user_agent);
            $metrics        = HTML::getFromClass($html_article, "small-text", "td");
            $metrics        = trim(strip_tags(str_replace("·", "", $metrics[0])));
            $data_metrics   = explode("\n", $metrics);
            $metric         = "";
            $citation_count = null;
            $download_count = null;
            
            foreach($data_metrics as $data_metric) {
                $data_metric = trim($data_metric);
                if (strpos($data_metric, "Citation Count")) {
                    $filter = filter_var($data_metric, FILTER_SANITIZE_NUMBER_INT);                    
                    if ($filter !== "") {
                        $citation_count = $filter;
                    }
                } else if (strpos($data_metric, "Downloads (cumulative)")) { 
                    $filter = filter_var($data_metric, FILTER_SANITIZE_NUMBER_INT);
                    if ($filter !== "") {
                        $download_count = $filter;
                    }
                }
            }
            
            $metric = str_replace("\u00a0", "", json_encode($data_metrics));
            $document->abstract         = $abstract;
            $document->citation_count   = $citation_count;
            $document->download_count   = $download_count;
            $document->metrics          = $metric;
            $document->save();
            
            $rand = rand(2,4);
            Util::showMessage("$rand seconds pause for next step.");
            sleep($rand);
        }
        Util::showMessage("Finish Load detail from ACM");
    }    
}
