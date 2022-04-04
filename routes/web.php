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

Route::get('/', function (\Illuminate\Http\Request $request) {
    $client = \Elasticsearch\ClientBuilder::create()
        ->setHosts(config('elasticquent.config.hosts'))
        ->build();

    $aa = new \App\Models\Person();
    $aa->addAllToIndex();
    $aa->reindex();

//    $aa->query()
//        ->create([
//            'first_name' => 'test',
//            'last_name' => 'test',
//            'phone' => 123,
//            'address' => 'test'
//        ]);
//    $aa->whereLastName('test')->first()->delete();
//    $aa->whereFirstName('test')->update([
//        'first_name' => 'aaaa'
//    ]);

    //dd($request->all());
//    if ($request->input('search')) {
//        $params = [
//            "index" => "data",
//            "type" => "persons",
//            "body" => [
//                "query" => [
//                    "query_string" => [
//                        "query" => "*". $request->input('search') ."*"
//                    ]
//                ]
//            ]
//        ];
//
//        $docs = $client->search($params);
//
//        return response()->json([
//            'data' => $docs
//        ]);
//    }
//    dd($docs['hits']['hits']);


    return view('welcome');
});
