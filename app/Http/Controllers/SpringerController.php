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

class SpringerController extends Controller {

    private static $parameter_query = array(
                                     "healthcare_IoT_OR_health_IoT_OR_healthIoT_Article" => '("healthcare IoT" OR "health IoT" OR "healthIoT")',
                                     "healthcare_IoT_OR_health_IoT_OR_healthIoT_ConferencePaper" => '("healthcare IoT" OR "health IoT" OR "healthIoT")',
                                     "Internet_of_Medical_Things_OR_Internet_of_healthcare_things_OR_Internet_of_M_health_Things_Article" => '("Internet of Medical Things" OR "Internet of healthcare things" OR "Internet of M-health Things")',
                                     "Internet_of_Medical_Things_OR_Internet_of_healthcare_things_OR_Internet_of_M_health_Things_ConferencePaper" => '("Internet of Medical Things" OR "Internet of healthcare things" OR "Internet of M-health Things")',                               
                                     "Internet_of_Things_OR_Internet_of_Things_AND_Health_Article" => '(("Internet of Things" OR "Internet-of-Things") AND "*Health*")',
                                     "Internet_of_Things_OR_Internet_of_Things_AND_Health_ConferencePaper" => '(("Internet of Things" OR "Internet-of-Things") AND "*Health*")',                                    
                                     "Internet_of_Things_and_Healthcare_Article" => '("Internet of Things" AND *Healthcare*)',
                                     "Internet_of_Things_and_Healthcare_ConferencePaper" => '("Internet of Things" AND *Healthcare*)',
                                     "Internet_of_Things_OR_Internet_of_Things_AND_Medical_Article" => '("Internet of Things" AND Medical)',
                                     "Internet_of_Things_OR_Internet_of_Things_AND_Medical_ConferencePaper" => '("Internet of Things" AND Medical)',
                                     "Medical_IoT_OR_IoT_Medical_Article" => '("Medical IoT" OR "IoT Medical")',
                                     "Medical_IoT_OR_IoT_Medical_ConferencePaper" => '("Medical IoT" OR "IoT Medical")',
                                     "manually_added" => null
                                    );

    public function import_bibtex() {
        
        $path_file = "data_files/springer/";
        $files = File::load($path_file);
        Util::showMessage("Start Import bibtex file from Springer");
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
                ['duplicate', '=', '0'],
            ])
            ->whereNotNull('document_url')
            ->whereNull('metrics')
            ->get();
        
        Util::showMessage("Total of Articles: " . count($documents));
        if (!empty($documents)) 
        {
                       
            foreach($documents as $key => $document) {
                
                $url = $document->document_url;
                // $url = "https://link.springer.com/article/10.1007/s10796-014-9492-7";
                // $url = "https://link.springer.com/article/10.1007/s12160-017-9903-3";
                Util::showMessage($url);                
                $info_article = self::get_info($url);
                if (!empty($info_article)) {                    
                    $document->citation_count   = (!empty(@$info_article["Citations"])) ? $info_article["Citations"] : null;
                    $document->download_count   = (!empty(@$info_article["Downloads"])) ? $info_article["Downloads"] : null;
                    $document->keywords         = (!empty(@$info_article["Keywords"])) ? $info_article["Keywords"] : null;
                    unset($info_article["Keywords"]);
                    $document->metrics          = json_encode($info_article);
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
        if (!empty($html_keywords)) 
        {
            $values = Util::getHTMLFromClass($html_keywords[0], "Keyword", "span");
            $values = array_map("strip_tags", $values);
            //Clean Special Characters
            foreach($values as $key => $str) {
                $clean = iconv('ISO8859-1', 'ASCII//TRANSLIT', $str);
                $clean = str_replace("^A ", "", $clean);
                $values[$key] = $clean;
            }
            $keyword = "";
            if (!empty($values)) {
                $keyword = implode(";", $values);
            }
            $info['Keywords'] = $keyword;
        }
        

        return $info;
    }
    
}
