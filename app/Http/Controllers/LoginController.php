<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Autentica um Reino ou um Membro do Conselho e emite um token do Sanctum.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::where('email', $request->string('email'))->first();

        // Um usuário inativo falha como credencial inválida, de propósito: a
        // resposta não deve permitir descobrir quais e-mails existem.
        if (! $usuario instanceof Usuario || ! $usuario->ativo || ! Hash::check($request->string('password')->value(), $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return response()->json([
            'token' => $usuario->createToken('api')->plainTextToken,
            'tipo' => $usuario->tipo->value,
            'usuario' => new UsuarioResource($usuario),
        ]);
    }
}
