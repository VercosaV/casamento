<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConvidadoController;
use App\Http\Controllers\NoivoController;
use App\Http\Controllers\PresenteController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['jwt.cookie', 'auth:api'])->group(function () {
    Route::resource('convidados', ConvidadoController::class);
    Route::resource('noivos', NoivoController::class);
    Route::resource('presentes', PresenteController::class);
});