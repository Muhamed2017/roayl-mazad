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

// get all Vehicles endpoint
Route::get('vehicles', 'App\Http\Controllers\VehicleController@getAllVehicles');
Route::get('vehicles/finder', 'App\Http\Controllers\VehicleController@finder');
Route::get('ads', 'App\Http\Controllers\VehicleController@getHomeAds');
Route::get('my-cars', 'App\Http\Controllers\VehicleController@getUserVehicles');
Route::get('featured', 'App\Http\Controllers\VehicleController@getFeaturedVehicles');




// admin apis

Route::group(['prefix' => 'admin'], function () {

    Route::post('signin', 'App\Http\Controllers\AuthController@login')->name('admin');
    Route::post('signup', 'App\Http\Controllers\AuthController@register')->name('admin');

    Route::group(['middleware' => 'auth_admin'], function () {
        Route::post('blockuser/{id}', 'App\Http\Controllers\AdminController@changeStateOfUser');
        Route::post('ads', 'App\Http\Controllers\AdminController@addAdvertisment');
        Route::post('featuring/{id}', 'App\Http\Controllers\AdminController@setAsFeatured');
    });
});
