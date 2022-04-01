<?php

use Illuminate\Support\Facades\Route;

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
    $client = \Elasticsearch\ClientBuilder::create()
        ->setHosts(config('elasticquent.config.hosts'))
        ->build();

//    \App\Models\Person::reindex();

    $params = [
        "index" => "data",
        "type" => "persons",
        "body" => [
            "query" => [
                "query_string" => [
                    "query" => "*il*"
                ]
            ]
        ]
    ];

    $docs = $client->search($params);
    dd($docs['hits']['hits']);

    return view('welcome');
});
