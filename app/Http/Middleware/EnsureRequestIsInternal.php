<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defesa em profundidade para as rotas internas: a requisição precisa vir de um
 * IP na allowlist E apresentar o token de serviço correto.
 *
 * As duas camadas devolvem a mesma mensagem genérica de 403, de propósito: quem
 * chama de fora não deve conseguir descobrir qual das duas barrou a requisição.
 */
class EnsureRequestIsInternal
{
    public const HEADER_TOKEN = 'X-Internal-Service-Token';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $this->origemConfiavel($request) && $this->tokenDeServicoValido($request),
            Response::HTTP_FORBIDDEN,
            'Requisição não autorizada.',
        );

        return $next($request);
    }

    /**
     * Confere o IP de origem contra a allowlist, que aceita IPv4, IPv6 e CIDR.
     */
    protected function origemConfiavel(Request $request): bool
    {
        /** @var list<string> $permitidos */
        $permitidos = config('conselho.interno.ips_permitidos', []);
        $ip = $request->ip();

        if ($permitidos === [] || $ip === null) {
            return false;
        }

        return IpUtils::checkIp($ip, $permitidos);
    }

    /**
     * Compara o header com o token configurado em tempo constante.
     */
    protected function tokenDeServicoValido(Request $request): bool
    {
        $esperado = config('conselho.interno.token_de_servico');
        $recebido = $request->header(self::HEADER_TOKEN);

        if (! is_string($esperado) || $esperado === '' || ! is_string($recebido)) {
            return false;
        }

        return hash_equals($esperado, $recebido);
    }
}
