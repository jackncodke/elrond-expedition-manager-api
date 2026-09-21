<?php

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
