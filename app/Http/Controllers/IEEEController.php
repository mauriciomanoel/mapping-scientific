<?php

namespace App\Http\Controllers;

use App\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Support\Slug;
use Config;
use Bibliophile\BibtexParse\ParseEntries;

class IEEEController extends Controller {
    

    public function import_bibtex() {

        $parse = new ParseEntries();
        $parse->expandMacro = FALSE;
        $parse->removeDelimit = true;
        $parse->fieldExtract = true;
        $parse->openBib("data_files/ieee/Internet_of_Medical_Things_OR_Internet_of_healthcare_things_OR_Internet_of_M-health_Things.bib");
        $parse->extractEntries();

        $articles   = $parse->returnArrays();
        $bibtex     = $parse->bibtexInArray();
        
        foreach($articles as $key => $article) {
            //   var_dump($article, $bibtex[$key]); exit;
            $search_string  = "Internet_of_Medical_Things_OR_Internet_of_healthcare_things_OR_Internet_of_M-health_Things";
            $type = $article["bibtexEntryType"];
            $authors = $article["author"];
            $title = $article["title"];
            $title_slug = Slug::slug($title, "-");
            $document = Document::where('title_slug', $title_slug)->first();
            $duplicate = 0;
            $duplicate_id = null;                
            if (!empty($document)) {
                $duplicate = 1;
                $duplicate_id = $document->id;
            }                    
            $source_id  = $article["bibtexCitation"];
            $published_in    = isset($article["booktitle"]) ? $article["booktitle"] : "";
            if (empty($published_in)) {
                $published_in    = isset($article["journal"]) ? $article["journal"] : "";
            }
            $pdf_link   = Config::get('constants.pach_ieee') . "xpl/abstractSimilar.jsp?arnumber=" . $source_id;
            $abstract   = $article["abstract"];
            $year       = $article["year"];
            $volume     = $article["volume"];
            $issue      = ""; // avaliar a necessidade
            $issn       = $article["issn"];
            $isbns      = ""; // avaliar a necessidade
            $doi            = $article["doi"]; //https://doi.org/
            $keywords       = $article["keywords"];        
            $numpages       = ""; // Avaliar a necessidade;
            $pages          = $article["pages"];
            $publisher      = ""; // Avaliar a necessidade;

            $document_new = new Document;
            $document_new->type             = $type;
            $document_new->bibtex_citation  = $source_id;
            $document_new->title            = $title;
            $document_new->title_slug       = $title_slug;
            $document_new->abstract         = $abstract;
            $document_new->authors          = $authors;
            $document_new->year             = $year;
            $document_new->volume           = $volume;
            $document_new->issue            = $issue;
            $document_new->issn             = $issn;
            $document_new->isbns            = $isbns;
            $document_new->doi              = $doi;            
            $document_new->document_url     = Config::get('constants.pach_ieee') . "document/" . $source_id;
            $document_new->pdf_link         = $pdf_link;
            $document_new->keywords         = $keywords;
            $document_new->published_in     = $published_in;
            $document_new->numpages         = $numpages;
            $document_new->pages            = $pages;
            $document_new->publisher        = $publisher;
            $document_new->source           = "ieeexplore";
            $document_new->search_string    = $search_string;
            $document_new->duplicate        = $duplicate;
            $document_new->duplicate_id     = $duplicate_id;
            $document_new->bibtex           = $bibtex[$key];
            $document_new->save();

        }
        // echo "<pre>"; var_dump($parse->bibtexInArray(), $parse->returnArrays()); exit;
        
    }

    /**
     * Load IEEE data
     *
     * @param  void
     * @return Response
     */
    public function ieee_csv() {
        
        $path = "ieee/";
        
        $files = $this->loadFiles($path);
        foreach($files as $file) {

            $search_string = null;

            // load file
            $documents = file($file);
            foreach($documents as $key => $document) {

                if (empty($search_string)) {
                    $search_string = trim($document);
                }
                if ($key < 2) continue;

                $data = explode("\",\"", $document);
                $authors = trim($data[1]);
                $title = trim($data[0]);
                if (empty($authors)) continue;            
                if (strpos($title, "\"") === 0) {
                    $title = substr($title, 1);
                }
                $title_slug = self::slug($title, "-");
                $document = Document::where('title_slug', $title_slug)->first();

                $duplicate = 0;
                $duplicate_id = null;                
                if (!empty($document)) {
                    $duplicate = 1;
                    $duplicate_id = $document->id;
                }
                
                $type = "article";
                if (strpos($title, "\"") === 0) {
                    $title = substr($title, 1);
                }
                
                $sources = preg_split('/[^0-9]/', $data[15], -1, PREG_SPLIT_NO_EMPTY); // filter number only
                $source_id = (!empty($sources)) ? $sources[0]:null;
                $pdf_link = $data[15];

                if (strpos($data[15], "pdfType=chapter") !== false) {
                    $pdf_link = Config::get('constants.pach_ieee') . "xpl/abstractSimilar.jsp?arnumber=" . $source_id;
                    $type = "ebook";
                }

                $document_new = new Document;
                $document_new->type             = $type;
                $document_new->source_id        = $source_id;
                $document_new->title            = $title;
                $document_new->title_slug       = $title_slug;
                $document_new->abstract         = $data[10];
                $document_new->authors          = $authors;
                $document_new->year             = $data[5];
                $document_new->volume           = (empty(trim($data[6]))) ? null:trim($data[6]);
                $document_new->issue            = (empty(trim($data[7]))) ? null:trim($data[7]);
                $document_new->issn             = (empty(trim($data[11]))) ? null:trim($data[11]);
                $document_new->isbns            = (empty(trim($data[12]))) ? null:trim($data[12]);
                $document_new->doi              = (empty(trim($data[13]))) ? null:trim($data[13]); // https://doi.org/
                $document_new->pdf_link         = $pdf_link;
                $document_new->keywords         = (empty(trim($data[16]))) ? null:trim($data[16]);
                $document_new->published_in     = $data[3];
                $document_new->numpages         = $data[8] . "-" . $data[9];
                $document_new->pages            = null;
                $document_new->publisher        = $data[29];
                $document_new->source           = "ieeexplore";
                $document_new->search_string    = $search_string;
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
    public function acm() {

        $path = "acm/";
        $files = $this->loadFiles($path);
        foreach($files as $file) {

            $search_string = null;

            // load file
            $documents = file($file);
            foreach($documents as $key => $document) {

                if (empty($search_string)) {
                    $search_string = trim($document);
                }
                if ($key < 2) continue;

                $data = explode("\",\"", $document);                
                $authors = trim($data[2]);
                $title = trim($data[6]);
                $type = trim($data[0]);
                if (empty($authors)) continue;            
                if (strpos($title, "\"") === 0) {
                    $title = substr($title, 1);
                }
                if (strpos($type, "\"") === 0) {
                    $type = substr($type, 1);
                }
                $title_slug = self::slug($title, "-");
                $document = Document::where('title_slug', $title_slug)->first();

                $duplicate = 0;
                $duplicate_id = null;                
                if (!empty($document)) {
                    $duplicate = 1;
                    $duplicate_id = $document->id;
                }

                $source_id                      = trim($data[1]);
                $abstract                       = "";
                $abstract                       = trim(strip_tags(WebService::loadUrl(Config::get('constants.URL_ACM_ABSTRACT') . $source_id))); // load abstract 
                
                $document_new = new Document;
                $document_new->type             = $type;
                $document_new->source_id        = $source_id;
                $document_new->title            = $title;
                $document_new->title_slug       = $title_slug;
                $document_new->abstract         = (empty(trim($abstract))) ? null:trim($abstract);
                $document_new->authors          = $authors;
                $document_new->year             = $data[18];
                $document_new->volume           = (empty(trim($data[14]))) ? null:trim($data[14]);
                $document_new->issue            = (empty(trim($data[15]))) ? null:trim($data[15]);
                $document_new->issn             = (empty(trim($data[19]))) ? null:trim($data[19]);
                $document_new->isbns            = (empty(trim($data[23]))) ? null:trim($data[23]);
                $document_new->doi              = (empty(trim($data[11]))) ? null:trim($data[11]); // https://doi.org/
                $document_new->pdf_link         = null;
                $document_new->document_url     = Config::get('constants.URL_ACM_CITATION') . $source_id;
                $document_new->keywords         = (empty(trim($data[10]))) ? null:trim($data[10]);
                $document_new->published_in     = $data[20] . " - " . $data[21];
                $document_new->numpages         = $data[9];
                $document_new->pages            = $data[7];
                $document_new->publisher        = $data[25];
                $document_new->source           = "acm";
                $document_new->search_string    = $search_string;
                $document_new->duplicate        = $duplicate;
                $document_new->duplicate_id     = $duplicate_id;
                $document_new->save();
            }            
        }
    }

    public function elsevier() {

        $parse = new ParseEntries();
        $parse->expandMacro = FALSE;
        $parse->removeDelimit = TRUE;
        $parse->fieldExtract = TRUE;
        // $parse->openBib("elsevier/science-Health.bib");
        $parse->openBib("periodicos-capes/periodicos_capes_Internet_of_Things_and_health.bib");
        $parse->extractEntries();
        $parse->closeBib();

        echo "<pre>"; var_dump($parse->returnArrays()); exit;
        
    }

    public function google_scholar() {

        $parse = new ParseEntries();
        $parse->expandMacro = FALSE;
        $parse->removeDelimit = TRUE;
        $parse->fieldExtract = TRUE;
        // $parse->openBib("elsevier/science-Health.bib");
        $parse->openBib("google-scholar/google_scholar_health_IoT.bib");
        $parse->extractEntries();
        $parse->closeBib();

        echo "<pre>"; var_dump($parse->returnArrays()); exit;
        
    }

    public function capes_save_article_in_my_space() {
        var_dump(App::cookie_capes); 
    }
}
