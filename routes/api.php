<?php

use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatbotController;

Route::post('/chatbot', [ChatbotController::class, 'reply']);

Route::get('/ping', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/search', [SearchController::class, 'search']);