<?php

namespace App\Http\Controllers;

use App\Models\Noivo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoivoController extends Controller
{
    // Home pública do casal
    public function home()
    {
        // AuthController::login, no return:
        return redirect()
            ->route('convidados.index')
            ->withCookie(cookie('access_token', $token, Auth::guard('api')->factory()->getTTL()));
    }

    public function edit(Noivo $noivo)
    {
        return view('noivos.edit', compact('noivo'));
    }

    public function update(Request $request, Noivo $noivo)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            // ignore(): o próprio noivo pode manter o e-mail que já tem
            'email' => ['required', 'email', 'max:255', Rule::unique('noivos', 'email')->ignore($noivo->id)],
        ]);

        $noivo->update($dados);

        return redirect()->route('noivos.index')->with('mensagem', 'Noivo alterado!');
    }
}
