<?php

namespace App\Http\Controllers;

use App\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DocumentController extends Controller {
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */
    public function ieee() {
        
    
        $search_string = null;
        $path = "ieee/";
        $files = scandir($path);

        foreach($files as $file) {

            // ignore when directory
            $dir = array_search($file, array(".", ".."));
            if ($dir === 0 || $dir === 1) continue;

            $search_string = null;

            // load file
            $documents = file($path . $file);
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

}
