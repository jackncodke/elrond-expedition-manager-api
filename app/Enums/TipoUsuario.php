<?php

namespace App\Enums;

/**
 * Perfil de um usuário da API. A tabela `usuarios` é única e este campo é o
 * que distingue um Reino solicitante de um Membro do Conselho.
 */
enum TipoUsuario: string
{
    case Reino = 'REINO';
    case Conselho = 'CONSELHO';
}
