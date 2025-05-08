<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AgreementController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', function () {
    return view('index');
});
Route::get('/about', function () {
    return view('about');
});
Route::get('/blog-details', function () {
    return view('blog-details');
});
Route::get('/blog', function () {
    return view('blog');
});
Route::get('/contact', function () {
    return view('contact');
});
Route::get('/portfolio-details', function () {
    return view('portfolio-details');
});
Route::get('/portfolio', function () {
    return view('portfolio');
});
Route::get('/service-details', function () {
    return view('service-details');
});
Route::get('/services', function () {
    return view('services');
});
Route::get('/starter-page', function () {
    return view('starter-page');
});
Route::get('/team', function () {
    return view('team');
});

Route::get("/sendotp",[UserController::class,"sendOtpTest"]);
Route::get('/getAllAgreements', [AgreementController::class, 'getAllAgreements']);
Route::get('/getAgreements', [AgreementController::class, 'getAgreements']);