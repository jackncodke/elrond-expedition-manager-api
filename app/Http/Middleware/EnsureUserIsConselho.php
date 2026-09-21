<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe a rota a usuários `tipo = CONSELHO`.
 */
class EnsureUserIsConselho
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_unless($usuario instanceof Usuario && $usuario->isConselho(), Response::HTTP_FORBIDDEN, 'Acesso restrito a membros do Conselho.');

        return $next($request);
    }
}
