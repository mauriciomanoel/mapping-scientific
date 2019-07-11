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

class PubMedController extends Controller {    
                                     
    public function import_bibtex() {

        $query = '("Internet of Things" OR "IoT" OR "iomt" OR "*health*") AND ("*elder*" OR "old people" OR "older person" OR "senior citizen" OR "aged people" OR "aged population" OR "aging population" OR "aging people") AND ("Smart City" OR "Smart Cities" OR "Smart health" OR "Smart home*")';
        $path_file = storage_path() . "/data_files/pubmed/";
        $files = File::load($path_file);

        Util::showMessage("Start Import bibtex file from PubMed");
        foreach($files as $file) 
        {
            Util::showMessage($file);

            $file = file_get_contents($file);
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
            
            $parser = new ParserCustom();             // Create a Parser
            $listener = new Listener();         // Create and configure a Listener
            $parser->addListener($listener);    // Attach the Listener to the Parser
            $parser->parseString($text);          // or parseFile('/path/to/file.bib')
            $entries = $listener->export();     // Get processed data from the Listener
            Util::showMessage("Import " . count($entries) . " documents");
 
            foreach($entries as $key => $article) {
                
                
                // Add new Parameter in variable article
                $article["search_string"]   = $query;
                $article["pdf_link"]        = $article["url"];
                $article["bibtex"]          = json_encode($article["_original"]); // save bibtex in json
                $article["source"]          = Config::get('constants.source_pubmed');
                $article["source_id"]       = (!empty($article["id"])) ? $article["id"] : null;
                $article["file_name"]       = basename($file);
                
                $duplicate = 0;
                $duplicate_id = null;
                // Search if article exists
                $title_slug = Slug::slug($article["title"], "-");
                $article["title_slug"] = $title_slug;
                $document = Document::where(
                    [
                        ['title_slug', '=', $title_slug],
                        ['file_name', '=', $file],
                        ['source', '=', Config::get('constants.source_pmc')],
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
        Util::showMessage("Finish Import bibtex file from PubMed");
    }

}