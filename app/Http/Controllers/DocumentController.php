<?php

namespace App\Http\Controllers;

use App\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Support\WebService;
use App\Http\Support\File;
use Config;
use Bibliophile\BibtexParse\ParseEntries;

class DocumentController extends Controller {
    /**
     * Load ACM data
     *
     * @param  void
     * @return Response
     */
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
}
