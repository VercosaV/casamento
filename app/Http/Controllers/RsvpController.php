<?php

namespace App\Http\Controllers;

use App\Http\Requests\RsvpRequest;
use App\Models\Convidado;

class RsvpController extends Controller
{
    // Formulário PÚBLICO de presença. (Antes a rota apontava para o
    // ConvidadoController, que é o formulário dos noivos.)
    public function create()
    {
        return view('rsvp.create');
    }

    public function store(RsvpRequest $request)
    {
        // validated() SEM argumentos devolve só os campos que passaram
        // pelas regras do RsvpRequest. As regras e mensagens ficam lá.
        $dados = $request->validated();

        $vai = $request->boolean('confirma_presenca');

        $convidado = Convidado::updateOrCreate(
            ['email' => $dados['email']],
            [
                'nome'              => $dados['nome'],
                'telefone'          => $dados['telefone'] ?? null,
                'confirma_presenca' => $vai,
                'adultos'           => $vai ? ($dados['adultos'] ?? 1) : 0,
                'criancas'          => $vai ? ($dados['criancas'] ?? 0) : 0,
                'observacoes'       => $dados['observacoes'] ?? null,
            ]
        );

        session(['convidado_id' => $convidado->id]);

        if (! $vai) {
            return redirect()
                ->route('home')
                ->with('mensagem', 'Que pena que você não poderá ir. Obrigado por avisar!');
        }

        return redirect()
            ->route('presentes.index')
            ->with('mensagem', 'Presença confirmada! Obrigado, '.$convidado->nome.'.');
    }
}
