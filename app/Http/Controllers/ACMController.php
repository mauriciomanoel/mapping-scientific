<?php

namespace App\Http\Controllers;

set_time_limit(0);

use App\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Support\Slug;
use App\Http\Support\File;
use App\Http\Support\Webservice;
use App\Http\Support\Util;
use App\Http\Support\CreateDocument;
use App\Http\Support\HTML;
use Config;
use Bibliophile\BibtexParse\ParseEntries;

class ACMController extends Controller {
    private static $parameter_query = array("healthcare_IoT_OR_health_IoT_OR_healthIoT" => '("healthcare IoT" OR "health IoT" OR "healthIoT")',
                                     "Internet_of_Medical_Things_OR_Internet_of_healthcare_things_OR_Internet_of_M-health_Things" => '("Internet of Medical Things" OR "Internet of healthcare things" OR "Internet of M-health Things")',
                                     "Internet_of_Things_AND_Health" => '("Internet of Things" AND *Health*)',
                                     "Internet_of_Things_AND_Healthcare" => '("Internet of Things" AND *Healthcare*)',
                                     "Internet_of_Things_AND_Medical" => '("Internet of Things" AND Medical)',
                                     "Medical_IoT_OR_IoT_Medical" => '("Medical IoT" OR "IoT Medical")');

    public function import_bibtex() {

        $path_file = "data_files/acm/";
        $files = File::load($path_file);
        Util::showMessage("Start Import bibtex file from ACM");
        foreach($files as $file) {
            Util::showMessage($file);
            $parse = new ParseEntries();
            $parse->expandMacro = FALSE;
            $parse->removeDelimit = true;
            $parse->fieldExtract = true;
            $parse->openBib($file);
            $parse->extractEntries();

            $articles   = $parse->returnArrays();
            $bibtex     = $parse->bibtexInArray();
            
            foreach($articles as $key => $article) {
                $query = str_replace(array($path_file, ".bib"), "", $file);
                
                $source_id = 0;
                // Add new Parameter in variable article
                $article["search_string"]   = self::$parameter_query[$query];
                if (isset($article["acmid"])) {
                    $article["document_url"]    = Config::get('constants.pach_acm') . "citation.cfm?id=" . $article["acmid"];
                    $source_id                  = $article["acmid"];
                    $article["source_id"]       = $source_id;
                }
                $article["bibtex"]          = $bibtex[$key];
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
        self::load_detail();
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

                $metric .= $data_metric . " |";
            }
            $metric = rtrim($metric, " |");
            
            $document->abstract         = $abstract;
            $document->citation_count   = $citation_count;
            $document->download_count   = $download_count;
            $document->metrics          = ltrim($metric, " ");
            $document->save();
            
            $rand = rand(2,4);
            Util::showMessage("$rand seconds pause for next step.");
            sleep($rand);
        }
        Util::showMessage("Finish Load detail from ACM");
    }    
}
