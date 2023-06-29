<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');

Route::group(['middleware' => 'role:admin|user'], function () {
    Route::get('/me', 'AuthController@me');
    Route::post('/logout', 'AuthController@logout');
    Route::post('/create/order', 'OrderController@create');
    Route::get('/orders', 'OrderController@index');
    Route::get('/order/{id}', 'OrderController@show');

})->middleware('permission:user');

Route::group(['middleware' => 'role:admin'], function () {
    Route::post('update/order/{id}', 'OrderController@update');
})->middleware('permission:all');

Route::get('/bikes', 'BikeController@index');
Route::get('/bike/{id}', 'BikeController@show');

Route::any('/unauth', function () {
    return response()->json([
        'error' => [
            'warning' => 'Unauthorized'
        ],
        'code'  => 400
    ]);
});

