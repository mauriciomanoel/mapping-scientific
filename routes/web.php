<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('import-ieee-bibtex', 'IEEEController@import_bibtex');
Route::get('import-acm-bibtex', 'ACMController@import_bibtex');
Route::get('import-elsevier', 'DocumentController@elsevier');
Route::get('import-google-scholar', 'DocumentController@google_scholar');
Route::get('capes-save-article-my-space', 'CapesController@save_article_my_space');
Route::get('capes-get-bibtex-from-my-space', 'CapesController@get_bibtex_from_my_space');




