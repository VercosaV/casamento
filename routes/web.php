<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConvidadoController;
use App\Http\Controllers\NoivoController;
use App\Http\Controllers\PresenteController;

// ---------- Público (qualquer visitante, sem login) ----

Route::get('/', function () {
    return view('welcome');
});

Route::get('/confirmar-presenca', [ConvidadoController::class, 'create'])->name('convidados.create');
Route::post('/confirmar-presenca', [ConvidadoController::class, 'store'])->name('convidados.store');


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------- Área dos noivos (protegida por JWT) -

Route::middleware(['jwt.cookie', 'auth:api'])->group(function () {
    Route::resource('convidados', ConvidadoController::class);
    Route::resource('noivos', NoivoController::class);
    Route::get('/presentes-admin', [PresenteController::class, 'admin'])->name('presentes.admin');
    Route::get('/presentes/create', [PresenteController::class, 'create'])->name('presentes.create');
    Route::post('/presentes', [PresenteController::class, 'store'])->name('presentes.store');
    Route::get('/presentes/{presente}/edit', [PresenteController::class, 'edit'])->name('presentes.edit');
    Route::put('/presentes/{presente}', [PresenteController::class, 'update'])->name('presentes.update');
    Route::delete('/presentes/{presente}', [PresenteController::class, 'destroy'])->name('presentes.destroy');
});


// ---------- Público de novo: lista de presentes (declaradas por último, de propósito) ----------

Route::get('/presentes', [PresenteController::class, 'index'])->name('presentes.index');
Route::get('/presentes/{presente}', [PresenteController::class, 'show'])->name('presentes.show');
Route::post('/presentes/{presente}/reservar', [PresenteController::class, 'reservar'])->name('presentes.reservar');


