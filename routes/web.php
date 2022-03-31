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
        ->build();


    $params = [
        'index' => 'data',
//        'type' => 'persons',
        'body' => [
            'query' => [
                'match' => [
                    'first_name' => 'Viva'
                ]
            ]
        ]

    ];


    $response = $client->bulk([
        'body' => [
            'index' => [
                '_index'    => 'data',
                '_type'     => '_doc'
            ],
            [
                'id'            => 1,
//                'name'          => $movie->name,
//                'year'          => $movie->year,
//                'description'   => $movie->description,
//                'rating'        => $movie->rating,
//                'actors'        => implode(',', $movie->movie_actors->pluck('name')->toArray())
            ]
        ]
    ]);
    dd($response);


    return view('welcome');
});
