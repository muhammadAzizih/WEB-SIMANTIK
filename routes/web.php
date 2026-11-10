<?php
use App\Http\Controllers\KampusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    return view('landingPage');
});

Route::get('/kampus', [KampusController::class, 'showKampus'
])->name('kampus');

Route::get('/fakultas', [KampusController::class, 'showFakultas'
])->name('fakultas');

Route::get('/prodi', [KampusController::class, 'showProdi'
])->name('prodi');

// Route::get('/kampus', function () {
//     return view('HalamanKampus.profilKampus');
// })->name('kampus');

// Route::get('/fakultas', function () {
//     return view('HalamanKampus.fakultas');
// })->name('fakultas');

// Route::get('/prodi', function () {
//     return view('HalamanKampus.prodi');
// })->name('prodi');

Route::prefix('api')->group(function () {
    Route::get('/kampus', [KampusController::class, 'index']);
    Route::get('/kampus/peta', [KampusController::class, 'peta']);
    Route::get('/filter-options', [KampusController::class, 'options']);
    Route::get('/prodi', [KampusController::class, 'prodi']);
    Route::get('/kampus/suggestions', [KampusController::class, 'suggestions']);
});