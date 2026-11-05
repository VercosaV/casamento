<?php

namespace App\Http\Controllers;

use App\Models\Contribuicao;
use App\Models\Convidado;
use Illuminate\Support\Facades\DB;

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
            'cotas'       => (int) Contribuicao::sum('quantidade_cotas'),
            'presentes'   => Contribuicao::distinct()->count('presente_id'),
            'valor'       => (float) DB::table('contribuicoes')
                ->join('presentes', 'presentes.id', '=', 'contribuicoes.presente_id')
                ->sum(DB::raw('contribuicoes.quantidade_cotas * (presentes.valor / presentes.cotas_total)')),
        ];

        $recentes = Contribuicao::with(['presente', 'convidado'])->latest()->take(5)->get();

        return view('painel.index', compact('stats', 'recentes'));
    }
}
