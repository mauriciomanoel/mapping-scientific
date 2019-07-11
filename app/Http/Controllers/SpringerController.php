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
use RenanBr\BibTexParser\Processor\LatexToUnicodeProcessor;

class SpringerController extends Controller {

    public function import_bibtex() {
       
        $arrSearch = array();
        $arrReplace = array();
        foreach (Bibtex::$transliteration as $key => $value) {
            $arrSearch[] = $key;
            $arrReplace[] = $value;
        }

        $query = '("Internet of Things" OR "IoT" OR "iomt" OR "*health*") AND ("*elder*" OR "old people" OR "older person" OR "senior citizen" OR "aged people" OR "aged population" OR "aging population" OR "aging people") AND ("Smart City" OR "Smart Cities" OR "Smart health" OR "Smart home*")';
        $path_file = storage_path() . "/data_files/springer/json/";
        $files = File::load($path_file);

        Util::showMessage("Start Import bibtex file from Springer");
        try 
        {
            
            foreach($files as $file) 
            {                
                Util::showMessage($file);                

                $file = file_get_contents($file);
                $articles = json_decode($file, true);

                echo "<pre>"; var_dump($articles); 
                echo json_last_error(); //Returns 4 - Syntax error;
                exit;

                $text = preg_replace(array_keys(Bibtex::$transliteration), array_values(Bibtex::$transliteration), $file);
                $values = explode("\n", $text);
                
                $text = "";
                foreach($values as $key => $value) {
                    //var_dump(substr($value,0,1)); exit;
                    if (substr($value,0,1) == "@") {
                        $value = str_replace(array(" ", ".", "/", "-", "_"), "", $value);                        
                        $values[$key] = $value;
                    }                    
                }
                $text = implode("\n", $values);              
                //echo "<pre>"; var_dump($text); exit;
                           
            
                Util::showMessage("Start Parser");
                $parser = new Parser();             // Create a Parser          
                $listener = new Listener();         // Create and configure a Listener
                //$listener->addProcessor(new LatexToUnicodeProcessor());
                $parser->addListener($listener);    // Attach the Listener to the Parser
                $parser->parseString($text);          // or parseFile('/path/to/file.bib')
                $entries = $listener->export();     // Get processed data from the Listener
                Util::showMessage("Total articles: " . count($entries));
                foreach($entries as $key => $article) {

                    if (empty(@$article["title"])) {
                        Util::showMessage("Ignore article without Title. citation-key: " . $article["citation-key"]);
                        continue;
                    }
                    $query = str_replace(array($path_file, ".bib"), "", $file);
                    
                    // Add new Parameter in variable article
                    $article["search_string"]   = $query;
                    $article["pdf_link"]        = !empty($article["link_pdf"]) ? $article["link_pdf"] : null;
                    $article["document_url"]    = !empty($article["url_article"]) ? $article["url_article"] : (isset($article["url"]) ? $article["url"] : null);
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
                        /*
                        $document_new->duplicate        = $duplicate;
                        $document_new->duplicate_id     = $duplicate_id;
                        $document_new->save();
                        */

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

        Util::showMessage("Finish Import bibtex file from Springer");
    }

    /**
     * Load Detail from Website Springer 
     *
     * @param  void
     * @return void
     */
    public function load_detail() {
        Util::showMessage("Start Load detail from Springer");

        $documents = Document::where(
            [
                ['source', '=', Config::get('constants.source_springer')],
                ['duplicate', '=', '0']
                ,
            ])
            ->whereNotNull('document_url')
            ->whereNull('metrics')
            ->get();
        
        Util::showMessage("Total of Articles: " . count($documents));
        if (count($documents)) 
        {
                       
            foreach($documents as $key => $document) {
                
                $url = $document->document_url;
                Util::showMessage($url);                
                $info_article = self::get_info($url);
                if (!empty($info_article)) {                    
                    $document->citation_count   = (!empty(@$info_article["Citations"])) ? $info_article["Citations"] : null;
                    $document->download_count   = (!empty(@$info_article["Downloads"])) ? $info_article["Downloads"] : null;
                    $document->keywords         = (!empty(@$info_article["Keywords"])) ? $info_article["Keywords"] : null;
                    unset($info_article["Keywords"]);
                    $document->metrics          = (!empty(@$info_article)) ? json_encode($info_article) : null;
                    $document->save();
                }

                $rand = rand(2,4);
                Util::showMessage("$rand seconds pause for next step.");
                sleep($rand);
            }
        }
        Util::showMessage("Finish Load detail from Springer");
    }


    /**
     * Load Metrics from Website Springer 
     *
     * @param  void
     * @return void
     */
    public function get_info($url) {
        $info         = array();
        $cookie         = "";
        $user_agent     = Config::get('constants.user_agent');
        $html_article   = WebService::loadURL($url, $cookie, $user_agent);
        $html_metrics   = Util::getHTMLFromClass($html_article, "article-metrics__item");    
        $html_keywords  = Util::getHTMLFromClass($html_article, "KeywordGroup");
        // dataLayer[0]['Keywords']
        if (!empty($html_metrics))
        {
            foreach($html_metrics as $html_metric) {
                $values = Util::getHTMLFromClass($html_metric, "metric", "span");
                $values = array_map("strip_tags", $values);
                if (!empty($values)) 
                {
                    $value  = $values[0];
                    $key    = $values[1];
                    if ($key == "Downloads") {
                        if (strpos($value, 'k') !== false) {
                            $value = str_replace("k", "", $value);
                            $value *= 1000;
                        }
                    }
                    $info[$key] = $value;
                }
            }
        }
        preg_match("/Keywords':'.*',/", $html_article, $output);
        if (!empty($output)) 
        {
            $output     = $output[0];
            $keyword    = str_replace(array("Keywords':'","',"), "", $output);            
            $info['Keywords'] = $keyword;
        }
        
        return $info;
    }
    
}
