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

class ScopusController extends Controller {    
                                     
    public function import_json() {

        $query = null;
        $path_file = storage_path() . "/data_files/scopus/json/";
        $files = File::load($path_file);
        Util::showMessage("Start Import bibtex file from Scopus");
        try 
        {
            foreach($files as $file) 
            {

                Util::showMessage($file);
                $text = file_get_contents($file);
                $articles = json_decode($text, true);
                Util::showMessage("Total articles: " . count($articles));
                foreach($articles as $key => $article) {

                    if (empty(@$article["title"])) {
                        Util::showMessage("Ignore article without Title. citation-key: " . $article["id"]);
                        continue;
                    }
                    
                    // Add new Parameter in variable article
                    $article["search_string"]   = $query;
                    $article["pdf_link"]        = !empty($article["link_pdf"]) ? $article["link_pdf"] : null;
                    $article["document_url"]    = !empty($article["url_article"]) ? $article["url_article"] : (isset($article["url"]) ? $article["url"] : null);                   
                    $article["bibtex"]          = json_encode($article); // save bibtex in json
                    $article["source"]          = Config::get('constants.source_scopus');
                    $article["source_id"]       = (!empty($article["id"])) ? $article["id"] : null;
                    $article["keywords"]        = (!empty($article["keywords"])) ? $article["keywords"] : null;
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
                            ['source', '=', Config::get('constants.source_scopus')],
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

        Util::showMessage("Finish Import bibtex file from Scopus");
    }

}