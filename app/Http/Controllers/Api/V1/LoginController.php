<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Autentica um Reino ou um Membro do Conselho e emite um token do Sanctum.
     *
     * O token expira conforme o tipo do usuário (ver config/conselho.php) e
     * revoga os tokens emitidos antes dele para o mesmo usuário.
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

        // Cada login abre uma sessão única: os tokens anteriores são revogados
        // para que um token esquecido em outro cliente não siga válido.
        $usuario->tokens()->delete();

        return response()->json([
            'token' => $usuario->createToken('api', ['*'], $usuario->tipo->expiracaoDoToken())->plainTextToken,
            'tipo' => $usuario->tipo->value,
            'usuario' => new UsuarioResource($usuario),
        ]);
    }
}
