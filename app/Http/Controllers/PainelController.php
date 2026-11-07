<?php

namespace App\Http\Controllers;

use App\Models\Contribuicao;
use App\Models\Convidado;
use App\Models\Pedido;

class PainelController extends Controller
{
    public function index()
    {
        $adultos  = (int) Convidado::where('confirma_presenca', true)->sum('adultos');
        $criancas = (int) Convidado::where('confirma_presenca', true)->sum('criancas');

        $stats = [
            'convidados'  => Convidado::count(),
            'confirmados' => Convidado::where('confirma_presenca', true)->count(),
            'recusados'   => Convidado::where('confirma_presenca', false)->count(),
            'pendentes'   => Convidado::whereNull('confirma_presenca')->count(),
            'adultos'     => $adultos,
            'criancas'    => $criancas,
            'pessoas'     => $adultos + $criancas,
            // "Escolhidos" = pagos + reservados/entregues ("comprar"). Pendentes ainda não contam como presente dado.
            'cotas'       => (int) Contribuicao::whereIn('status', Contribuicao::ESCOLHIDOS)->sum('quantidade_cotas'),
            'presentes'   => Contribuicao::whereIn('status', Contribuicao::ESCOLHIDOS)->distinct()->count('presente_id'),
            // Dinheiro de verdade: só o que foi CONFIRMADO (Stripe, ou Pix conferido por vocês). Em centavos.
            'recebido'    => Contribuicao::where('status', Contribuicao::PAGA)->sum('valor_centavos') / 100,
            // Stripe ou Pix esperando o dinheiro, ainda dentro do prazo.
            'aguardando'  => Pedido::where('status', Pedido::PENDENTE)->where('expira_em', '>', now())->count(),
            // "Comprar o presente": reservados que ainda não chegaram.
            'para_receber' => Pedido::where('forma', Pedido::FORMA_COMPRAR)->where('status', Pedido::RESERVADA)->count(),
        ];

        $recentes = Contribuicao::with(['presente', 'convidado', 'pedido'])
            ->whereIn('status', Contribuicao::ESCOLHIDOS)
            ->latest()->take(5)->get();

        return view('painel.index', compact('stats', 'recentes'));
    }
}
