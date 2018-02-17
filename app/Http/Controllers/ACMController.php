<?php

namespace App\Http\Controllers;

set_time_limit(0);

use App\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Support\Slug;
use App\Http\Support\File;
use App\Http\Support\Webservice;
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

        self::load_detail();

        $path_file = "data_files/acm/";
        $files = File::load($path_file);
        
        foreach($files as $file) {
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
                
                // Add new Parameter in variable article
                $article["search_string"]   = self::$parameter_query[$query];
                if (isset($article["acmid"])) {
                    $article["document_url"]    = Config::get('constants.pach_acm') . "citation.cfm?id=" . $article["acmid"];
                    $article["source_id"]       = $article["acmid"];
                }
                $article["bibtex"]          = $bibtex[$key];
                $article["source"]          = Config::get('constants.source_acm');
                
                $duplicate = 0;
                $duplicate_id = null;
                // Search if article exists
                $title_slug = Slug::slug($article["title"], "-");
                $article["title_slug"] = $title_slug;
                $document = Document::where('title_slug', $title_slug)->first();
                if (!empty($document)) {
                    $duplicate      = 1;
                    $duplicate_id   = $document->id;
                }
                // Create new Document
                $document_new = CreateDocument::process($article);
                $document_new->duplicate        = $duplicate;
                $document_new->duplicate_id     = $duplicate_id;
                $document_new->save();
                
            }
        }
    }

    /**
     * Load ACM data
     *
     * @param  void
     * @return Response
     */
    public function load_detail() {

        $documents = Document::where(
            [
                ['source', '=', Config::get('constants.source_acm')],
                ['duplicate', '=', '0'],
            ])
            ->whereNotNull('source_id')
            ->get();

        foreach($documents as $document) {
            $url            = Config::get('constants.url_acm_abstract') . $document->source_id;
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
            $document->metrics          = ltrim($metric, "");
            $document->save();
            
            sleep(rand(2,4));
        }
    }    
}
