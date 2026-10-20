<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    return view('landingPage');
});

Route::get('/kampus', function () {
    return view('HalamanKampus.profilKampus');
})->name('kampus');

Route::get('/fakultas', function () {
    return view('HalamanKampus.fakultas');
})->name('fakultas');

Route::get('/prodi', function () {
    return view('HalamanKampus.prodi');
})->name('prodi');

