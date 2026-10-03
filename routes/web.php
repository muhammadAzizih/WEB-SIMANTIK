<?php

use Illuminate\Support\Facades\Route;

Route::get('/kampus', function () {
    return view('HalamanKampus.profilKampus');
});
