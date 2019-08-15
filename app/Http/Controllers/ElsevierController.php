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



 

class ElsevierController extends Controller {

    public static $parameter_query = array(
                                     "Internet_of_Things_OR_IoT_OR_Internet_of_Medical_Things_OR_iomt_OR_health_OR_AAL_OR_Ambient_Assisted_Living_AND_aged_population_OR_aging_population_OR_aging_people_OR_older_adult_AND_Smart_Cities_OR_Smart_City" => '("Internet of Things" OR "IoT" OR "Internet of Medical Things" OR "iomt" OR "*health*" OR "AAL" OR "Ambient Assisted Living") AND ("aged population" OR "aging population" OR "aging people" OR "older adult")  AND ("Smart Cities" OR "Smart City")',
                                     "Internet_of_Things_OR_IoT_OR_Internet_of_Medical_Things_OR_iomt_OR_health_OR_AAL_OR_Ambient_Assisted_Living_AND_elder_OR_old_people_OR_older_person_OR_senior_citizen_OR_aged_people_AND_Smart_Cities_OR_Smart_City" => '("Internet of Things" OR "IoT" OR "Internet of Medical Things" OR "iomt" OR "*health*" OR "AAL" OR "Ambient Assisted Living") AND ("*elder*" OR "old people" OR "older person" OR "senior citizen" OR "aged people") AND ("Smart Cities" OR "Smart City")'
                                    );

    public function import_bibtex() {
        
        $path_file = storage_path() . "/data_files/elsevier/json/";
        $files = File::load($path_file);
        Util::showMessage("Start Import bibtex file from Elsevier Sciencedirect");
        try 
        {
            foreach($files as $file) 
            {

                Util::showMessage($file);
                $text = file_get_contents($file);
                $articles = json_decode($text, true);
                Util::showMessage("Total articles: " . count($articles));


                /*$parser = new Parser();             // Create a Parser
                //$parser->addTransliteration(Bibtex::$transliteration); //  Attach the Transliteration special characters to the Parser
                $listener = new Listener();         // Create and configure a Listener                
                $parser->addListener($listener);    // Attach the Listener to the Parser
                $parser->parseFile($file);          // or parseFile('/path/to/file.bib')
                $entries = $listener->export();     // Get processed data from the Listener
*/
                foreach($articles as $key => $article) {
                    
                    // var_dump($article); exit;
                    $query = str_replace(array($path_file, ".json"), "", $file);
                    // Add new Parameter in variable article
                    $article["search_string"] = self::$parameter_query[$query];
                    $article["pdf_link"]        = !empty($article["link_pdf"]) ? $article["link_pdf"] : null;
                    $article["document_url"]    = !empty($article["url_article"]) ? $article["url_article"] : (isset($article["url"]) ? $article["url"] : null);                   
                    $article["bibtex"]          = json_encode($article); // save bibtex in json
                    $article["source"]          = Config::get('constants.source_elsevier_sciencedirect');
                    $article["source_id"]       = null;
                    $article["type"]            = "article";
                    $article["citation-key"]    = null;
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
                            ['source', '=', Config::get('constants.source_elsevier_sciencedirect')],
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

        Util::showMessage("Finish Import bibtex file from Elsevier Sciencedirect");
    }

    /**
     * Load Detail from Website ACM 
     *
     * @param  void
     * @return void
     */
    public function load_detail() {        
        Util::showMessage("Start Load detail from Elsevier ScienceDirect");

        $documents = Document::where(
            [
                ['source', '=', Config::get('constants.source_elsevier_sciencedirect')],
                ['duplicate', '=', '0'],
            ])
            ->whereNotNull('doi')
            ->whereNull('metrics')
            ->get();
        
        Util::showMessage("Total of Articles: " . count($documents));
        if (count($documents)) 
        {
            $cookie         = "";
            $user_agent     = Config::get('constants.user_agent');                    
            
            foreach($documents as $key => $document) {
                
                $doi = str_replace(array("https://doi.org/", "http://doi.org/"), "", $document->doi);
                $url = Config::get('constants.api_rest_plu_ms_elsevier') . $doi;
                Util::showMessage($url);                
                $json_metric = WebService::loadURL($url, $cookie, $user_agent);
                $metrics = json_decode($json_metric, true);                

                if (isset($metrics["error_code"])) {
                    Util::showMessage("Metric not fond: $url");
                    continue;
                }
                // var_dump($metrics); 
                $captures   =  @$metrics["statistics"]["Captures"];
                $citations  =  @$metrics["statistics"]["Citations"];                
                $download_count = null;
                $citation_count = null;

                // get Readers -> Downloads
                if (!empty($captures)) 
                {
                    foreach($captures as $capture) 
                    {
                        if ($capture["label"] == "Readers") 
                        {
                            $download_count += $capture["count"];
                        }
                    }
                }
                // Get Citation
                if (!empty($citations))
                {
                    foreach($citations as $citation)
                    {
                        if ($citation["label"] == "Citation Indexes" && $citation["source"] == "CrossRef")
                        {
                            $citation_count += $citation["count"];
                        }
                    }
                }
                
                $document->citation_count   = $citation_count;
                $document->download_count   = $download_count;
                $document->metrics          = $json_metric;
                $document->save();

                $rand = rand(2,4);
                Util::showMessage("$rand seconds pause for next step.");
                sleep($rand);
            }
        }
        Util::showMessage("Finish Load detail from Elsevier ScienceDirect");
    }  
}
