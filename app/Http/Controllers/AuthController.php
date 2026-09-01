<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return back()
                ->withErrors(['email' => 'Credenciais inválidas.'])
                ->onlyInput('email');
        }

        return redirect()
            ->route('noivos.index')
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