<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
// Route::get('/users', [UserController::class, 'fetchAllUsers']);
Route::post('/verify', [UserController::class, 'verifyOTP']);   // this will need password and the otp 
Route::post('/resendOTP', [UserController::class, 'resendOTP']); // this will need an email to resend the Otp .