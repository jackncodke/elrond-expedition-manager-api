<?php

use App\Models\Usuario;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | A API é stateless e autentica exclusivamente por token do Sanctum, então
    | o guard padrão é o `sanctum` e não existe guard de sessão.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'sanctum'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | O guard `sanctum` é registrado pelo próprio SanctumServiceProvider; a
    | entrada abaixo apenas fixa o provider utilizado por ele.
    |
    */

    'guards' => [
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Tabela única `usuarios`, com o campo `tipo` distinguindo REINO de
    | CONSELHO. Não há provider separado por perfil.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', Usuario::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
