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
use Illuminate\Support\Facades\Storage;

use Config;

class CatalogoCapesController extends Controller {
    

    public static $parameter_query = array(
        "internet-of-things-or-iot-and-health" => '("Internet of Things" OR "IoT") AND "*health*"',
        "iinternet-of-medical-things-or-iomt" => '"Internet of Medical Things" OR "iomt"',
        "aal-or-ambient-assisted-living" => '"AAL" OR "Ambient Assisted Living"'
       );

    public function import_bibtex_to_database() {
        
        $path_file = storage_path() . "/data_files/catalogo_teses_dissertacoes/bib/";
        $files = File::load($path_file);
        
        Util::showMessage("Start Import bibtex file from Catalogo Teses");
        try 
        {
            foreach($files as $file) 
            {
                Util::showMessage($file);
                $parser = new Parser();             // Create a Parser
                $listener = new Listener();         // Create and configure a Listener                
                $parser->addListener($listener);    // Attach the Listener to the Parser
                $parser->parseFile($file);          // or parseFile('/path/to/file.bib')
                $entries = $listener->export();     // Get processed data from the Listener

                $search_string = '("Smart City" OR "Smart Cities" OR "Smart health" OR "Smart home*")';
                foreach($entries as $key => $article) {
                    
                    if (empty($article["abstract"]) && empty($article["note"])) {
                        Util::showMessage("Article Discarded without Abstract and Note: " . $article["title"]  . " - " . $file);
                        continue;
                    }
                           
                    if (empty($article["abstract"])) {
                        $article["abstract"] = $article["note"];
                    }

                    // Add new Parameter in variable article
                    $article["search_string"]   = $search_string;
                    $article["pdf_link"]        = !empty($article["link_pdf"]) ? $article["link_pdf"] : null;
                    $article["document_url"]    = $article["url"];
                    $article["bibtex"]          = json_encode($article["_original"]); // save bibtex in json
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
                            ['source', '=', $article["source"]],
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
                        Util::showMessage("Article " . $article["title"]  . " successfully registered" . $file);

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

        Util::showMessage("Finish Import bibtex file from Catalogo Teses");
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

