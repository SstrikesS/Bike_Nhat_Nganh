<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');
Route::get('/bikes', 'BikeController@index');
Route::get('/bike/{id}', 'BikeController@show');

Route::group(['middleware' => ['auth:sanctum', 'role:admin|user']], function () {
    Route::get('/me', 'AuthController@me');
    Route::post('/logout', 'AuthController@logout');
    Route::post('/create/order', 'OrderController@create');
    Route::get('/orders', 'OrderController@index');
    Route::get('/order/{id}', 'OrderController@show');
    Route::post('/update/order/{id}', 'OrderController@update');
})->middleware('permission:user');

Route::group(['middleware' => ['auth:sanctum','role:admin']], function () {
    
    Route::post('/update/bike/{id}', 'BikeController@edit');
    Route::post('/create/bike', 'BikeController@create');
    Route::get('/delete/bike/{id}', 'BikeController@destroy');

})->middleware('permission:all');



Route::any('/unauth', function () {
    return response()->json([
        'error' => [
            'warning' => 'Unauthorized'
        ],
        'code'  => 400
    ]);
});
