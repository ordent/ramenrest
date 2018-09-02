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

// adding routes for default files entities
Route::get('/api/files', '\Ordent\RamenRest\Controllers\FilesController@getCollection');
Route::get('/api/files/{id}', '\Ordent\RamenRest\Controllers\FilesController@getItem');
Route::post('/api/files', '\Ordent\RamenRest\Controllers\FilesController@postItem');
Route::post('/api/files/{id}', '\Ordent\RamenRest\Controllers\FilesController@putItem');
Route::put('/api/files/{id}', '\Ordent\RamenRest\Controllers\FilesController@putItem');
Route::delete('/api/files/{id}', '\Ordent\RamenRest\Controllers\FilesController@deleteItem');
Route::post('/api/files/{id}/delete', '\Ordent\RamenRest\Controllers\FilesController@deleteItem');

Route::post('/api/uploads', '\Ordent\RamenRest\Controllers\FilesController@modellessUpload');