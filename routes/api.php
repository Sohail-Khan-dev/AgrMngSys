<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [UserController::class, 'register']); // this will limit user request upto 5 in a minute
Route::post('/login', [UserController::class, 'login']);
Route::get('/users', [UserController::class, 'fetchAllUsers']);
Route::post('/verify', [UserController::class, 'verifyOTP']);