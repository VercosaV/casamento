<?php

namespace App\Http\Controllers;

use App\Models\Convidado;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
    public function create()
    {
        return view('rsvp.create');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'              => ['required', 'string', 'max:255'],
            'confirma_presenca' => ['required', 'boolean'],
            'adultos'           => ['nullable', 'integer', 'min:1', 'max:10'],
            'criancas'          => ['nullable', 'integer', 'min:0', 'max:10'],
            'email'             => ['required', 'email', 'max:255'],
            'telefone'          => ['nullable', 'string', 'max:20'],
            'observacoes'       => ['nullable', 'string', 'max:500'],
            'consentimento'     => ['accepted'],
        ], [
            'nome.required'          => 'Informe seu nome completo.',
            'email.required'         => 'Informe seu e-mail.',
            'email.email'            => 'Esse e-mail não parece válido.',
            'consentimento.accepted' => 'Para continuar, aceite o uso dos seus dados.',
        ]);

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
            ->with('mensagem', 'Presença confirmada! Obrigado, ' . $convidado->nome . '.');
    }
}