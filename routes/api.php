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
});

Route::group(['middleware' => 'auth.user'], function () {
    // Route::get('vehicles', 'App\Http\Controllers\VehicleController@fetch');
    Route::post('vehicles', 'App\Http\Controllers\VehicleController@store')->name('user');
    // Route::put('vehicle/{id}', 'App\Http\Controllers\VehicleController@update');
    // Route::delete('vehicle/{id}', 'App\Http\Controllers\VehicleController@destroy');
    // Route::get('user-vehicles', 'App\Http\Controllers\VehicleController@userVehicles');
});