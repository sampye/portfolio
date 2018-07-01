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
    return view('index');
});

Route::get('/items', 'ItemController@getIndex');
Route::get('/countries', 'CountryController@getIndex');
Route::get('/vats', 'VatController@getIndex');
Route::get('/orders', 'OrderController@getIndex');
Route::get('/order/create', 'OrderController@getCreate');
Route::post('/order/create', 'OrderController@postCreate');
Route::get('/order/{order}', 'OrderController@getShow');