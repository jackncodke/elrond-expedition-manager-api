<?php

namespace Database\Seeders;

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Dois Reinos solicitantes e um Membro do Conselho. A senha de todos é
 * `password`, documentada no README — vale apenas para desenvolvimento.
 */
class UsuarioSeeder extends Seeder
{
    public const SENHA_PADRAO = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuarios = [
            [
                'tipo' => TipoUsuario::Reino,
                'nome' => 'Reino de Gondor',
                'codigo' => 'GONDOR',
                'email' => 'gondor@reinos.elrond.test',
            ],
            [
                'tipo' => TipoUsuario::Reino,
                'nome' => 'Reino de Rohan',
                'codigo' => 'ROHAN',
                'email' => 'rohan@reinos.elrond.test',
            ],
            [
                'tipo' => TipoUsuario::Conselho,
                'nome' => 'Elrond',
                'codigo' => null,
                'email' => 'elrond@conselho.elrond.test',
            ],
        ];

        foreach ($usuarios as $usuario) {
            Usuario::updateOrCreate(
                ['email' => $usuario['email']],
                [...$usuario, 'password' => self::SENHA_PADRAO, 'ativo' => true],
            );
        }
    }
}
