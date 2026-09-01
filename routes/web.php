<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConvidadoController;
use App\Http\Controllers\NoivoController;
use App\Http\Controllers\PresenteController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('convidados', ConvidadoController::class);

Route::resource('noivos', NoivoController::class);

Route::resource('presentes', PresenteController::class);