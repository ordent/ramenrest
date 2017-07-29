<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/users', '\Ordent\RamenRest\Controllers\UserController@getCollection');
Route::get('/users/{id}', '\Ordent\RamenRest\Controllers\UserController@getItem');
Route::post('/users', '\Ordent\RamenRest\Controllers\UserController@postItem');
Route::put('/users/{id}', '\Ordent\RamenRest\Controllers\UserController@putItem');
Route::delete('/users/{id}', '\Ordent\RamenRest\Controllers\UserController@deleteItem');