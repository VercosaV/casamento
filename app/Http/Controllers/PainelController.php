<?php

namespace App\Http\Controllers;

use App\Models\Contribuicao;
use App\Models\Convidado;

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
            // "Escolhidas" = pagas + reservadas sem pagamento. Pendentes ainda não contam como presente dado.
            'cotas'       => (int) Contribuicao::whereIn('status', [Contribuicao::PAGA, Contribuicao::RESERVADA])->sum('quantidade_cotas'),
            'presentes'   => Contribuicao::whereIn('status', [Contribuicao::PAGA, Contribuicao::RESERVADA])->distinct()->count('presente_id'),
            // Dinheiro de verdade: só o que o Stripe confirmou. Guardado em centavos (inteiro).
            'recebido'    => Contribuicao::where('status', Contribuicao::PAGA)->sum('valor_centavos') / 100,
            'reservado'   => Contribuicao::where('status', Contribuicao::RESERVADA)->sum('valor_centavos') / 100,
            'aguardando'  => Contribuicao::ocupandoCota()->where('status', Contribuicao::PENDENTE)->count(),
        ];

        $recentes = Contribuicao::with(['presente', 'convidado'])
            ->whereIn('status', [Contribuicao::PAGA, Contribuicao::RESERVADA])
            ->latest()->take(5)->get();

        return view('painel.index', compact('stats', 'recentes'));
    }
}
