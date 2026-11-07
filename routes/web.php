<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarrinhoController;
use App\Http\Controllers\ConvidadoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\PedidoAdminController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PresenteController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\SecaoController;
use Illuminate\Support\Facades\Route;

// ---------- Público (qualquer visitante, sem login) ----------

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/privacidade', 'privacidade')->name('privacidade');

// RSVP com nomes próprios (rsvp.*) para não colidir com convidados.* do painel
Route::get('/confirmar-presenca', [RsvpController::class, 'create'])->name('rsvp.create');
Route::post('/confirmar-presenca', [RsvpController::class, 'store'])
    ->middleware('throttle:rsvp')->name('rsvp.store');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------- Área dos noivos (JWT + ser noivo) ----------
// jwt.cookie  -> lê o token do cookie
// auth:api    -> confere se o token é válido (senão manda para /login)
// noivo       -> confere se o usuário é mesmo um noivo (senão 403)

Route::middleware(['jwt.cookie', 'auth:api', 'noivo'])->group(function () {
    Route::get('/painel', [PainelController::class, 'index'])->name('painel');

    // Perfil de cada noivo (nome, e-mail de login, senha)
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

    // Página do convite: seções editáveis
    Route::get('/secoes', [SecaoController::class, 'index'])->name('secoes.index');
    Route::get('/secoes/create', [SecaoController::class, 'create'])->name('secoes.create');
    Route::post('/secoes', [SecaoController::class, 'store'])->name('secoes.store');
    Route::get('/secoes/{secao}/edit', [SecaoController::class, 'edit'])->name('secoes.edit');
    Route::put('/secoes/{secao}', [SecaoController::class, 'update'])->name('secoes.update');
    Route::delete('/secoes/{secao}', [SecaoController::class, 'destroy'])->name('secoes.destroy');
    Route::post('/secoes/{secao}/visibilidade', [SecaoController::class, 'alternarVisibilidade'])->name('secoes.visibilidade');
    Route::post('/secoes/{secao}/mover', [SecaoController::class, 'mover'])->name('secoes.mover');
    Route::post('/secoes/{secao}/fotos', [SecaoController::class, 'enviarFotos'])->name('secoes.fotos.store');
    Route::delete('/fotos/{foto}', [SecaoController::class, 'excluirFoto'])->name('fotos.destroy');

    // Convidados (a rota /exportar vem ANTES do resource, senão "exportar" viraria um {id})
    Route::get('/convidados/exportar', [ConvidadoController::class, 'exportar'])->name('convidados.exportar');
    Route::resource('convidados', ConvidadoController::class);

    // Presentes (admin)
    Route::get('/presentes-admin', [PresenteController::class, 'admin'])->name('presentes.admin');
    Route::get('/presentes/create', [PresenteController::class, 'create'])->name('presentes.create');
    Route::post('/presentes', [PresenteController::class, 'store'])->name('presentes.store');
    Route::get('/presentes/{presente}/edit', [PresenteController::class, 'edit'])->name('presentes.edit');
    Route::put('/presentes/{presente}', [PresenteController::class, 'update'])->name('presentes.update');
    Route::delete('/presentes/{presente}', [PresenteController::class, 'destroy'])->name('presentes.destroy');

    // Pedidos dos convidados (Quem presenteou) e o que é manual: confirmar Pix, cancelar, marcar como recebido
    Route::get('/pedidos', [PedidoAdminController::class, 'index'])->name('pedidos.index');
    Route::post('/pedidos/{pedido}/confirmar', [PedidoAdminController::class, 'confirmar'])->name('pedidos.confirmar');
    Route::post('/pedidos/{pedido}/cancelar', [PedidoAdminController::class, 'cancelar'])->name('pedidos.cancelar');
    Route::post('/pedidos/{pedido}/entregar', [PedidoAdminController::class, 'entregar'])->name('pedidos.entregar');
});

// ---------- Público de novo: lista de presentes (por último, de propósito) ----------
// O Laravel casa as rotas na ordem. Se /presentes/{presente} viesse antes de
// /presentes/create, a palavra "create" seria tratada como o id de um presente.
Route::get('/presentes', [PresenteController::class, 'index'])->name('presentes.index');

// "Minha lista": o convidado junta presentes e depois escolhe UMA forma de enviar todos
Route::get('/carrinho', [CarrinhoController::class, 'mostrar'])->name('carrinho.mostrar');
Route::post('/carrinho/adicionar/{presente}', [CarrinhoController::class, 'adicionar'])
    ->middleware('throttle:rsvp')->name('carrinho.adicionar');
Route::delete('/carrinho/{presente}', [CarrinhoController::class, 'remover'])->name('carrinho.remover');

Route::post('/pedido', [PedidoController::class, 'store'])->middleware('throttle:rsvp')->name('pedido.store');
// Duas portas para a mesma página: a do convidado (Pix, comprar) e a de retorno do Stripe.
Route::get('/pedido/{token}', [PedidoController::class, 'mostrar'])->name('pedido.mostrar');

// ---------- Pagamento (Stripe) ----------
// Retorno do Checkout. O {token} é um UUID aleatório: identifica a compra sem expor o id.
Route::get('/pagamento/sucesso/{token}', [PedidoController::class, 'mostrar'])->name('pagamento.sucesso');
Route::get('/pagamento/cancelado/{token}', [PagamentoController::class, 'cancelado'])->name('pagamento.cancelado');

// Quem chama é o servidor do Stripe (sem navegador, sem cookie, sem CSRF).
// A segurança aqui é a ASSINATURA do aviso, conferida no controller. Veja bootstrap/app.php.
Route::post('/stripe/webhook', [PagamentoController::class, 'webhook'])->name('stripe.webhook');
