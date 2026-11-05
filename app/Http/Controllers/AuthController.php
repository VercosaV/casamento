<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Sem JWT_SECRET o pacote não consegue assinar o token e estoura uma exceção feia.
        // Conferimos ANTES e damos uma mensagem que diz o que fazer.
        if (blank(config('jwt.secret'))) {
            Log::critical('JWT_SECRET não configurado. Rode: php artisan jwt:secret');

            return back()
                ->withErrors(['email' => config('app.debug')
                    ? 'JWT_SECRET não configurado. Rode no terminal: php artisan jwt:secret (depois: php artisan config:clear)'
                    : 'Não foi possível entrar agora. Tente novamente mais tarde.'])
                ->onlyInput('email');
        }

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return back()
                ->withErrors(['email' => 'Credenciais inválidas.'])
                ->onlyInput('email');
        }

        return redirect()
    ->route('painel')
    ->withCookie(cookie('access_token', $token, Auth::guard('api')->factory()->getTTL()));
    }

    public function logout(Request $request)
    {
        Auth::guard('api')->logout();

        return redirect()
            ->route('login')
            ->withCookie(cookie()->forget('access_token'));
    }
}