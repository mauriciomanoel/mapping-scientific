<?php

namespace App\Http\Support;

use App\Document;

class CreateDocument {

    public static function process($article) {

        $search_string  = $article["search_string"];
        $type           = $article["bibtexEntryType"];

        $author = null;
        if (isset($article["author"])) {
            $author = str_replace(array("{", "}", "\"", "\'", "~", "\\", "`"), '', $article["author"]);
        }
        
        $authors            = $author;
        $title              = $article["title"];
        $title_slug         = $article["title_slug"];        
        $bibtex_citation    = $article["bibtexCitation"];
        $published_in       = (isset($article["series"]) ? $article["series"] . ": " : null) . isset($article["booktitle"]) ? $article["booktitle"] : null;
        if (empty($published_in)) {
            $published_in    = isset($article["journal"]) ? $article["journal"] : null;
        }
        $pdf_link           = isset($article["pdf_link"]) ? $article["pdf_link"] : null;
        $abstract           = isset($article["abstract"]) ? $article["abstract"] : null;
        $year               = isset($article["year"]) ? $article["year"] : null;
        $volume             = isset($article["volume"]) ? $article["volume"] : null; // // Avaliar a necessidade;
        $issue              = ""; // avaliar a necessidade
        $issn               = isset($article["issn"]) ? $article["issn"] : null;
        $isbns              = ""; // avaliar a necessidade
        $doi                = (isset($article["doi"]) && !empty($article["doi"])) ? $article["doi"] : null; //https://doi.org/
        $keywords           = isset($article["keywords"]) ? $article["keywords"] : null;
        $pages              = isset($article["pages"]) ? $article["pages"] : null;
        $publisher          = ""; // Avaliar a necessidade;
        $document_url       = isset($article["document_url"]) ? $article["document_url"] : null;
        $bibtex             = $article["bibtex"];
        $source             = $article["source"];
        $source_id          = isset($article["source_id"]) ? $article["source_id"] : null;
        

        $document_new = new Document;
        $document_new->type             = $type;
        $document_new->bibtex_citation  = $bibtex_citation;
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
        $document_new->document_url     = $document_url;
        $document_new->pdf_link         = $pdf_link;
        $document_new->keywords         = $keywords;
        $document_new->published_in     = $published_in;
        $document_new->pages            = $pages;
        $document_new->source           = $source;
        $document_new->source_id        = $source_id;
        $document_new->search_string    = $search_string;
        $document_new->bibtex           = $bibtex;

        return $document_new;
        
    }
}

?>