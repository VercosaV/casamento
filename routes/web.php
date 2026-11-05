<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConvidadoController;
use App\Http\Controllers\NoivoController;
use App\Http\Controllers\PresenteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\ContribuicaoController;
use App\Http\Controllers\PainelController;


// ---------- Público (qualquer visitante, sem login) ----------

Route::view('/privacidade', 'privacidade')->name('privacidade');

Route::view('/', 'home')->name('home');

// RSVP com nomes próprios (rsvp.*) para não colidir com convidados.* do painel
Route::get('/confirmar-presenca', [ConvidadoController::class, 'create'])->name('rsvp.create');
Route::post('/confirmar-presenca', [RsvpController::class, 'store'])
    ->middleware('throttle:rsvp')->name('rsvp.store');


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ---------- Área dos noivos (protegida por JWT) ----------

Route::middleware(['jwt.cookie', 'auth:api'])->group(function () {
    // Noivos: só editar. As duas contas vêm do seeder.
    Route::resource('noivos', NoivoController::class)->only(['edit', 'update']);

    // Convidados: CRUD completo
    Route::get('/convidados/exportar', [ConvidadoController::class, 'exportar'])->name('convidados.exportar');


    Route::resource('convidados', ConvidadoController::class);

    Route::get('/presentes-admin', [PresenteController::class, 'admin'])->name('presentes.admin');
    Route::get('/presentes/create', [PresenteController::class, 'create'])->name('presentes.create');
    Route::post('/presentes', [PresenteController::class, 'store'])->name('presentes.store');
    Route::get('/presentes/{presente}/edit', [PresenteController::class, 'edit'])->name('presentes.edit');
    Route::put('/presentes/{presente}', [PresenteController::class, 'update'])->name('presentes.update');
    Route::delete('/presentes/{presente}', [PresenteController::class, 'destroy'])->name('presentes.destroy');
    Route::get('/painel', [PainelController::class, 'index'])->name('painel');
    Route::get('/contribuicoes', [ContribuicaoController::class, 'index'])->name('contribuicoes.index');

});

// ---------- Público de novo: lista de presentes (por último, de propósito) ----------
// O Laravel casa as rotas na ordem. Se /presentes/{presente} viesse antes de
// /presentes/create, a palavra "create" seria tratada como o id de um presente.
Route::get('/presentes', [PresenteController::class, 'index'])->name('presentes.index');
Route::post('/presentes/{presente}/contribuir', [ContribuicaoController::class, 'store'])
    ->middleware('throttle:rsvp')->name('presentes.contribuir');

