<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mensagens próprias da API
    |--------------------------------------------------------------------------
    |
    | A mensagem de 429 do ThrottleRequests é fixa no framework e não passa pelo
    | tradutor; ela é aplicada pela resposta customizada dos rate limiters, em
    | AppServiceProvider.
    |
    */

    'throttle' => 'Requisições em excesso. Tente novamente em :seconds segundos.',

];
