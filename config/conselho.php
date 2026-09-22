<?php

use App\Enums\TipoUsuario;

return [

    /*
    |--------------------------------------------------------------------------
    | Requisições internas
    |--------------------------------------------------------------------------
    |
    | Defesa em profundidade do endpoint de decisão do Conselho. A allowlist
    | aceita IPv4, IPv6 e notação CIDR; o token de serviço é comparado com
    | hash_equals contra o header X-Internal-Service-Token.
    |
    */

    'interno' => [
        'ips_permitidos' => array_values(array_filter(
            array_map('trim', explode(',', (string) env('INTERNAL_ALLOWED_IPS', ''))),
        )),

        'token_de_servico' => env('INTERNAL_SERVICE_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiração dos tokens de acesso
    |--------------------------------------------------------------------------
    |
    | Validade, em minutos, do token do Sanctum emitido no login, por tipo de
    | usuário. O Conselho tem validade menor porque decide expedições: é o
    | perfil cujo token mais custa caro se vazar.
    |
    */

    'autenticacao' => [
        'expiracao_em_minutos' => [
            TipoUsuario::Reino->value => (int) env('SANCTUM_TOKEN_EXPIRATION_REINO', 480),
            TipoUsuario::Conselho->value => (int) env('SANCTUM_TOKEN_EXPIRATION_CONSELHO', 120),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentação
    |--------------------------------------------------------------------------
    |
    | Caminho da especificação OpenAPI servida por `GET /docs/openapi.json`. O
    | arquivo é escrito à mão e versionado; fica fora de `public/` para não ser
    | exposto pelo servidor web sem passar pela rota.
    |
    */

    'documentacao' => [
        'especificacao' => base_path('docs/openapi.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Protocolo das expedições
    |--------------------------------------------------------------------------
    |
    | Compõe o identificador público de uma expedição, no formato
    | PREFIXO-ANO-SEQUENCIAL (ex.: ELR-2026-000123).
    |
    */

    'protocolo' => [
        'prefixo' => env('EXPEDICAO_PROTOCOLO_PREFIXO', 'ELR'),
    ],

];
