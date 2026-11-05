<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApenasNoivos
{
    // Roda DEPOIS de auth:api. "Estar logado" não basta: o usuário precisa
    // ter um registro na tabela noivos. Assim, qualquer outra conta que
    // exista na tabela users (teste, esquecida, criada por engano) não entra.
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = auth('api')->user();

        if (! $usuario || ! $usuario->noivo()->exists()) {
            abort(403, 'Área exclusiva dos noivos.');
        }

        return $next($request);
    }
}
