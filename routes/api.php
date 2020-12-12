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

\Laravel\Passport\Passport::$ignoreCsrfToken = true;
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['prefix' => '/v1'], function () {
    Route::post('/user/login', 'WechatController@weappLogin');
    Route::get('/live/tags', 'Api\CommunityController@getTags');
    Route::get('/live/getCommunity', 'Api\CommunityController@getCommunityByLocation');
    Route::get('/live/getCommunityList', 'Api\CommunityController@getCommunityList');
    Route::get('/live/getContact','Api\CommunityController@getContact');

    Route::group(['middleware' => ['cors', 'multiauth:api']], function (){

             Route::any('/live/addContact', 'Api\CommunityController@addContact');
    });

});

