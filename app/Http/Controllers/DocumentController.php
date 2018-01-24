<?php

namespace App\Http\Controllers;

use App\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DocumentController extends Controller {
    
    /**
     * Load IEEE data
     *
     * @param  void
     * @return Response
     */
    public function ieee() {
        
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
                
                $document_new = new Document;
                $document_new->type             = "article";
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
                $document_new->pdf_link         = $data[15];
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
                
                $document_new = new Document;
                $document_new->type             = $type;
                $document_new->source_id        = trim($data[1]);
                $document_new->title            = $title;
                $document_new->title_slug       = $title_slug;
                $document_new->abstract         = null;
                $document_new->authors          = $authors;
                $document_new->year             = $data[18];
                $document_new->volume           = (empty(trim($data[14]))) ? null:trim($data[14]);
                $document_new->issue            = (empty(trim($data[15]))) ? null:trim($data[15]);
                $document_new->issn             = (empty(trim($data[19]))) ? null:trim($data[19]);
                $document_new->isbns            = (empty(trim($data[23]))) ? null:trim($data[23]);
                $document_new->doi              = (empty(trim($data[11]))) ? null:trim($data[11]); // https://doi.org/
                $document_new->pdf_link         = null;
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

    private function loadFiles($path) {
        $arrFiles = array();
        $files = scandir($path);
        foreach($files as $file) {
            // ignore when directory
            $dir = array_search($file, array(".", ".."));
            if ($dir === 0 || $dir === 1) continue;

            $arrFiles[] = $path . $file;
        }
        return $arrFiles;
    }
}
