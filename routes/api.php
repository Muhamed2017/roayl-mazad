<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'auth'], function () {
    Route::post('signin', 'App\Http\Controllers\AuthController@login')->name('user');
    Route::post('signup', 'App\Http\Controllers\AuthController@register')->name('user');
    // Route::post('resetpassword', 'Api\Site\RegisterController@resetPassword')->name('user');
    // Route::post('verify', 'Api\Site\RegisterController@verify')->name('user');

});
