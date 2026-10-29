<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConvidadoController;
use App\Http\Controllers\NoivoController;
use App\Http\Controllers\PresenteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RsvpController;

// ---------- Público (qualquer visitante, sem login) ----------

Route::view('/', 'home')->name('home');

Route::get('/confirmar-presenca', [RsvpController::class, 'create'])->name('rsvp.create');
Route::post('/confirmar-presenca', [RsvpController::class, 'store'])->name('rsvp.store');

// RSVP com nomes próprios (rsvp.*) para não colidir com convidados.* do painel
Route::get('/confirmar-presenca', [ConvidadoController::class, 'create'])->name('rsvp.create');
Route::post('/confirmar-presenca', [ConvidadoController::class, 'store'])->name('rsvp.store');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------- Área dos noivos (protegida por JWT) ----------

Route::middleware(['jwt.cookie', 'auth:api'])->group(function () {
    // Noivos: só editar. As duas contas vêm do seeder.
    Route::resource('noivos', NoivoController::class)->only(['edit', 'update']);

    Route::resource('convidados', ConvidadoController::class);

    Route::get('/presentes-admin', [PresenteController::class, 'admin'])->name('presentes.admin');
    Route::get('/presentes/create', [PresenteController::class, 'create'])->name('presentes.create');
    Route::post('/presentes', [PresenteController::class, 'store'])->name('presentes.store');
    Route::get('/presentes/{presente}/edit', [PresenteController::class, 'edit'])->name('presentes.edit');
    Route::put('/presentes/{presente}', [PresenteController::class, 'update'])->name('presentes.update');
    Route::delete('/presentes/{presente}', [PresenteController::class, 'destroy'])->name('presentes.destroy');
});

// ---------- Público de novo: lista de presentes (por último, de propósito) ----------
// O Laravel casa as rotas na ordem. Se /presentes/{presente} viesse antes de
// /presentes/create, a palavra "create" seria tratada como o id de um presente.

Route::get('/presentes', [PresenteController::class, 'index'])->name('presentes.index');
Route::get('/presentes/{presente}', [PresenteController::class, 'show'])->name('presentes.show');
Route::post('/presentes/{presente}/reservar', [PresenteController::class, 'reservar'])->name('presentes.reservar');