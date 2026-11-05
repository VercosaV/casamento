<?php

namespace App\Http\Controllers;

use App\Models\Contribuicao;
use App\Models\Convidado;
use App\Models\Presente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContribuicaoController extends Controller
{
    // Painel dos noivos: rota protegida
    public function index()
    {
        $contribuicoes = Contribuicao::with(['presente', 'convidado'])->latest()->paginate(20);

        return view('contribuicoes.index', compact('contribuicoes'));
    }

    public function store(Request $request, Presente $presente)
    {
        $convidadoId = session('convidado_id');

        if ($convidadoId && ! Convidado::whereKey($convidadoId)->exists()) {
            session()->forget('convidado_id');
            $convidadoId = null;
        }

        $dados = $request->validate([
            'quantidade_cotas' => ['required', 'integer', 'min:1', 'max:'.$presente->cotas_total],
            'mensagem' => ['nullable', 'string', 'max:400'],
            'nome' => [Rule::requiredIf($convidadoId === null), 'nullable', 'string', 'max:255'],
            'email' => [Rule::requiredIf($convidadoId === null), 'nullable', 'email', 'max:255'],
        ]);

        if ($convidadoId === null) {
            $convidado = Convidado::firstOrCreate(
                ['email' => $dados['email']],
                ['nome' => $dados['nome']]
            );
            $convidadoId = $convidado->id;
            session(['convidado_id' => $convidadoId]);
        }

        $restantes = DB::transaction(function () use ($presente, $dados, $convidadoId) {
            // Trava a linha do presente até o fim da transação
            $travado = Presente::whereKey($presente->id)->lockForUpdate()->firstOrFail();

            $reservadas = (int) $travado->contribuicoes()->sum('quantidade_cotas');
            $restantes = $travado->cotas_total - $reservadas;

            if ($dados['quantidade_cotas'] > $restantes) {
                return $restantes; // não cabe: devolve quantas ainda cabem
            }

            $travado->contribuicoes()->create([
                'convidado_id' => $convidadoId,
                'quantidade_cotas' => $dados['quantidade_cotas'],
                'mensagem' => $dados['mensagem'] ?? null,
            ]);

            return null; // deu certo
        });

        if ($restantes !== null) {
            $texto = $restantes === 0
                ? 'Esse presente acabou de ser totalmente escolhido por outras pessoas.'
                : "Restam apenas {$restantes} cota(s) desse presente. Escolha uma quantidade menor.";

            return redirect()->route('presentes.index')->with('erro', $texto);
        }

        return redirect()->route('presentes.index')
            ->with('mensagem', 'Muito obrigado! Sua escolha foi registrada.');
    }
}
