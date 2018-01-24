<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DocumentController extends Controller {
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */
    public function ieee()
    {
        // return "Ola mundo...";

        $documents = file("ieeee_export2018.01.23-18.47.04.csv");
        foreach($documents as $document) {
            $data = explode("\",\"", $document);
            echo "<pre>"; var_dump($data); exit;
        }

        exit;
        $result = array("status"=>"ok", "result"=>array()); 
        return response('Hello World', 200)
                  ->header('Content-Type', 'text/plain');
    }
}

?>
