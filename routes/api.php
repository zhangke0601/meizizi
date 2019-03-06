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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/cartoon', 'Api\CartoonController@index')->name('api.cartoon.index');
Route::get('/relation', 'Api\CartoonController@relation')->name('api.cartoon.relation');
Route::get('/last-month', 'Api\CartoonController@lastMonth')->name('api.cartoon.lastMonth');
Route::get('/detail', 'Api\CartoonController@detail')->name('api.cartoon.detail');
Route::get('/rate', 'Api\CartoonController@rate')->name('api.cartoon.rate');
Route::get('/sync-cp', 'Api\CartoonController@syncCp')->name('api.cartoon.syncCp');
