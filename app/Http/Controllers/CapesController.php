<?php

namespace App\Http\Controllers;

set_time_limit(0);

use App\Document;
use App\Bibtex;
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

use Config;

class CapesController extends Controller {

    private static $parameter_query = array(
                                     "healthcare_IoT_OR_health_IoT_OR_healthIoT" => '("healthcare IoT" OR "health IoT" OR "healthIoT")',
                                     "Internet_of_Medical_Things_OR_Internet_of_healthcare_things_OR_Internet_of_M-health_Things" => '("Internet of Medical Things" OR "Internet of healthcare things" OR "Internet of M-health Things")',
                                     "Internet_of_Things_AND_Health" => '("Internet of Things" AND *Health*)',
                                     "Internet_of_Things_AND_Healthcare" => '("Internet of Things" AND *Healthcare*)',
                                     "Internet_of_Things_AND_Medical" => '("Internet of Things" AND Medical)',
                                     "Medical_IoT_OR_IoT_Medical" => '("Medical IoT" OR "IoT Medical")',
                                     "manually_added" => null
                                    );

    public function import_bibtex() {

        $path_file = "data_files/periodicos-capes/";
        $files = File::load($path_file);
        Util::showMessage("Start Import bibtex file from Periodicos Capes");
        try 
        {
            foreach($files as $file) 
            {
                Util::showMessage($file);
                $parser = new Parser();             // Create a Parser
                $parser->addTransliteration(Bibtex::$transliteration); //  Attach the Transliteration special characters to the Parser
                $listener = new Listener();         // Create and configure a Listener
                $parser->addListener($listener);    // Attach the Listener to the Parser
                $parser->parseFile($file);          // or parseFile('/path/to/file.bib')
                $entries = $listener->export();     // Get processed data from the Listener

                foreach($entries as $key => $article) {

                    if (empty(@$article["title"])) {
                        Util::showMessage("Ignore article without Title. citation-key: " . $article["citation-key"]);
                        continue;
                    }
                    $query = str_replace(array($path_file, ".bib"), "", $file);
                    
                    // Add new Parameter in variable article
                    $article["search_string"] = self::$parameter_query[$query];
                    $article["pdf_link"]        = !empty($article["link_pdf"]) ? $article["link_pdf"] : null;
                    $article["document_url"]    = !empty($article["url"]) ? $article["url"] : null;
                    $article["bibtex"]          = json_encode($article["_original"]); // save bibtex in json
                    $article["source"]          = Config::get('constants.source_springer');
                    $article["source_id"]       = null;
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
                            ['source', '=', Config::get('constants.source_springer')],
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

        Util::showMessage("Finish Import bibtex file from Periodicos Capes");
    }

    /**
     * Load Detail from Website ACM 
     *
     * @param  void
     * @return void
     */
    public function load_detail() {        
        Util::showMessage("Start Load detail from Periodicos Capes");

        // $documents = Document::where(
        //     [
        //         ['source', '=', Config::get('constants.source_elsevier_sciencedirect')],
        //         ['duplicate', '=', '0'],
        //     ])
        //     ->whereNotNull('doi')
        //     ->whereNull('metrics')
        //     ->get();
        
        // Util::showMessage("Total of Articles: " . count($documents));
        // if (!empty($documents)) 
        // {
        //     $cookie         = "";
        //     $user_agent     = Config::get('constants.user_agent');                    
            
        //     foreach($documents as $key => $document) {
                
        //         $doi = str_replace(array("https://doi.org/", "http://doi.org/"), "", $document->doi);
        //         $url = Config::get('constants.api_rest_plu_ms_elsevier') . $doi;
        //         Util::showMessage($url);                
        //         $json_metric = WebService::loadURL($url, $cookie, $user_agent);
        //         $metrics = json_decode($json_metric, true);                

        //         if (isset($metrics["error_code"])) {
        //             Util::showMessage("Metric not fond: $url");
        //             continue;
        //         }
        //         // var_dump($metrics); 
        //         $captures   =  @$metrics["statistics"]["Captures"];
        //         $citations  =  @$metrics["statistics"]["Citations"];                
        //         $download_count = null;
        //         $citation_count = null;

        //         // get Readers -> Downloads
        //         if (!empty($captures)) 
        //         {
        //             foreach($captures as $capture) 
        //             {
        //                 if ($capture["label"] == "Readers") 
        //                 {
        //                     $download_count += $capture["count"];
        //                 }
        //             }
        //         }
        //         // Get Citation
        //         if (!empty($citations))
        //         {
        //             foreach($citations as $citation)
        //             {
        //                 if ($citation["label"] == "Citation Indexes" && $citation["source"] == "CrossRef")
        //                 {
        //                     $citation_count += $citation["count"];
        //                 }
        //             }
        //         }
                
        //         $document->citation_count   = $citation_count;
        //         $document->download_count   = $download_count;
        //         $document->metrics          = $json_metric;
        //         $document->save();

        //         $rand = rand(2,4);
        //         Util::showMessage("$rand seconds pause for next step.");
        //         sleep($rand);
        //     }
        // }
        Util::showMessage("Finish Load detail from Elsevier ScienceDirect");
    }  
}
