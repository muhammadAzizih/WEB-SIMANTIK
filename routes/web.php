<?php

use Illuminate\Support\Facades\Route;

Route::get('/kampus', function () {
    return view('HalamanKampus.profilKampus');
});

Route::get('/fakultas', function () {
    return view('HalamanKampus.fakultas');
});

Route::get('/prodi', function () {
    return view('HalamanKampus.prodi');
});

Route::get('/', function() {
    return view('landingPage');
});