<?php

namespace App\Http\Controllers;

use App\Models\Noivo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// Cada noivo edita o PRÓPRIO perfil (nome, e-mail de login e senha).
class PerfilController extends Controller
{
    public function edit()
    {
        $usuario = auth('api')->user();

        return view('perfil.edit', compact('usuario'));
    }

    public function update(Request $request)
    {
        /** @var User $usuario */
        $usuario = auth('api')->user();
        $noivo = $usuario->noivo;

        $dados = $request->validate([
            'nome'  => ['required', 'string', 'max:255'],
            // ignore(): a pessoa pode manter o e-mail que já tem
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
                Rule::unique('noivos', 'email')->ignore($noivo->id),
            ],
            // A senha só muda se a pessoa preencher os campos de senha
            'senha_atual' => ['nullable', 'required_with:senha', 'current_password:api'],
            'senha'       => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'senha_atual.current_password' => 'A senha atual não confere.',
            'senha_atual.required_with'    => 'Informe a senha atual para trocar a senha.',
            'senha.min'                    => 'A nova senha precisa ter ao menos 8 caracteres.',
            'senha.confirmed'              => 'A confirmação da nova senha não confere.',
        ]);

        DB::transaction(function () use ($usuario, $noivo, $dados) {
            $usuario->name = $dados['nome'];
            $usuario->email = $dados['email'];

            if (! empty($dados['senha'])) {
                $usuario->password = $dados['senha']; // o cast "hashed" do model faz o hash
            }

            $usuario->save();

            // O e-mail do login (users) e o do perfil (noivos) andam juntos.
            $noivo->update(['nome' => $dados['nome'], 'email' => $dados['email']]);
        });

        return redirect()->route('perfil.edit')->with('mensagem', 'Perfil atualizado!');
    }
}
