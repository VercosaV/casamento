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


/*
Route::get('/debug-jwt', function (\Illuminate\Http\Request $request) {
    try {
        $user = auth('api')->user();
        return response()->json([
            'ok' => true,
            'cookie_presente' => (bool) $request->cookie('access_token'),
            'header_authorization' => $request->header('Authorization'),
            'usuario_autenticado' => $user,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'erro' => $e->getMessage(),
            'cookie_presente' => (bool) $request->cookie('access_token'),
            'header_authorization' => $request->header('Authorization'),
        ]);
    }
})->middleware('jwt.cookie');
*/