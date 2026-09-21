<?php

namespace App\Enums;

enum StatusExpedicao: string
{
    case Pendente = 'PENDENTE';
    case Autorizada = 'AUTORIZADA';
    case Rejeitada = 'REJEITADA';

    /**
     * Uma expedição finalizada já foi deliberada pelo Conselho e não aceita
     * uma nova decisão.
     */
    public function finalizada(): bool
    {
        return $this !== self::Pendente;
    }
}
