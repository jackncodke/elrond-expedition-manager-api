<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    /**
     * Revoga o token usado nesta requisição, encerrando apenas esta sessão.
     * Os demais tokens do usuário, se houver, continuam válidos.
     */
    public function __invoke(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
