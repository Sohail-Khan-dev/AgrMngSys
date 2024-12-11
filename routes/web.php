<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/index.html', function () {
    return view('index');
});
Route::get('/about.html', function () {
    return view('about');
});
Route::get('/blog-details.html', function () {
    return view('blog-details');
});
Route::get('/blog.html', function () {
    return view('blog');
});
Route::get('/contact.html', function () {
    return view('contact');
});
Route::get('/portfolio-details.html', function () {
    return view('portfolio-details');
});
Route::get('/service-details.html', function () {
    return view('service-details');
});
Route::get('/starter-page.html', function () {
    return view('starter-page');
});
Route::get('/team.html', function () {
    return view('team');
});

