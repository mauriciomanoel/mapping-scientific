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
Route::get('load-detail-ieee', 'IEEEController@load_detail');
Route::get('download-pdf-ieee', 'IEEEController@download_pdf');

Route::get('import-acm-bibtex', 'ACMController@import_bibtex');
Route::get('import-acm-json', 'ACMController@import_json');
Route::get('load-detail-acm', 'ACMController@load_detail');

// Route::get('import-elsevier-bibtex', 'ElsevierController@import_bibtex');
Route::get('import-elsevier-json', 'ElsevierController@import_json');
Route::get('load-detail-elsevier', 'ElsevierController@load_detail');


Route::get('import-springer-bibtex', 'SpringerController@import_bibtex');
Route::get('import-springer-json', 'SpringerController@import_json');
Route::get('load-detail-springer', 'SpringerController@load_detail');

Route::get('import-catalogo-capes-bibtex', 'CatalogoCapesController@import_bibtex_to_database');

Route::get('import-pmc-bibtex', 'PMCController@import_bibtex');
Route::get('import-pmc-json', 'PMCController@import_json');


Route::get('import-pubmed-bibtex', 'PubMedController@import_bibtex');
Route::get('import-pubmed-json', 'PubMedController@import_json');

Route::get('import-scopus-bibtex', 'ScopusController@import_bibtex');
Route::get('import-scopus-json', 'ScopusController@import_json');

Route::get('import-engineering-village-json', 'EngineeringVillageController@import_json');





