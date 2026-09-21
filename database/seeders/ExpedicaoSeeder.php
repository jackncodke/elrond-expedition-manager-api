<?php

namespace Database\Seeders;

use App\Enums\StatusExpedicao;
use App\Models\Expedicao;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Duas expedições completas — jornada, participantes e itens de suprimento —
 * uma ainda PENDENTE e outra já deliberada pelo Conselho.
 *
 * Os protocolos são fixos para que o README e a collection do Postman possam
 * referenciá-los. O gerador de protocolo continua a partir deles.
 */
class ExpedicaoSeeder extends Seeder
{
    public const PROTOCOLO_PENDENTE = 'ELR-2026-000001';

    public const PROTOCOLO_AUTORIZADA = 'ELR-2026-000002';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gondor = Usuario::where('email', 'gondor@reinos.elrond.test')->sole();
        $rohan = Usuario::where('email', 'rohan@reinos.elrond.test')->sole();
        $elrond = Usuario::where('email', 'elrond@conselho.elrond.test')->sole();

        $pendente = Expedicao::updateOrCreate(
            ['protocolo' => self::PROTOCOLO_PENDENTE],
            [
                'usuario_id' => $gondor->id,
                'status' => StatusExpedicao::Pendente,
                'motivo_rejeicao' => null,
                'decidido_por' => null,
                'decidido_em' => null,
            ],
        );

        $pendente->jornada()->updateOrCreate([], [
            'origem' => 'Minas Tirith',
            'destino' => 'Osgiliath',
            'data_partida_prevista' => '2026-11-03',
            'rota_descricao' => 'Descida pela estrada do Pelennor, travessia do Anduin ao amanhecer.',
        ]);

        $pendente->participantes()->delete();
        $pendente->participantes()->createMany([
            ['nome' => 'Faramir', 'raca' => 'Humano', 'papel' => 'Líder'],
            ['nome' => 'Beregond', 'raca' => 'Humano', 'papel' => 'Guarda'],
            ['nome' => 'Damrod', 'raca' => 'Humano', 'papel' => 'Batedor'],
        ]);

        $pendente->itensSuprimento()->delete();
        $pendente->itensSuprimento()->createMany([
            ['nome' => 'Arco de Ithilien', 'tipo' => 'arma', 'quantidade' => 12, 'observacoes' => 'Com aljavas completas.'],
            ['nome' => 'Lembas', 'tipo' => 'provisao', 'quantidade' => 40, 'observacoes' => null],
            ['nome' => 'Cavalo de Gondor', 'tipo' => 'montaria', 'quantidade' => 6, 'observacoes' => null],
        ]);

        $autorizada = Expedicao::updateOrCreate(
            ['protocolo' => self::PROTOCOLO_AUTORIZADA],
            [
                'usuario_id' => $rohan->id,
                'status' => StatusExpedicao::Autorizada,
                'motivo_rejeicao' => null,
                'decidido_por' => $elrond->id,
                'decidido_em' => now()->subDays(3),
            ],
        );

        $autorizada->jornada()->updateOrCreate([], [
            'origem' => 'Edoras',
            'destino' => 'Abismo de Helm',
            'data_partida_prevista' => '2026-10-12',
            'rota_descricao' => 'Marcha pela planície de Rohan, evitando os vaus do Isen.',
        ]);

        $autorizada->participantes()->delete();
        $autorizada->participantes()->createMany([
            ['nome' => 'Éomer', 'raca' => 'Humano', 'papel' => 'Líder'],
            ['nome' => 'Gimli', 'raca' => 'Anão', 'papel' => 'Guarda'],
            ['nome' => 'Legolas', 'raca' => 'Elfo', 'papel' => 'Batedor'],
            ['nome' => 'Meriadoc Brandybuck', 'raca' => 'Hobbit', 'papel' => 'Portador'],
        ]);

        $autorizada->itensSuprimento()->delete();
        $autorizada->itensSuprimento()->createMany([
            ['nome' => 'Lança de Rohan', 'tipo' => 'arma', 'quantidade' => 30, 'observacoes' => null],
            ['nome' => 'Provisões de estrada', 'tipo' => 'provisao', 'quantidade' => 80, 'observacoes' => 'Suficientes para quinze dias.'],
            ['nome' => 'Frasco de Galadriel', 'tipo' => 'artefato_magico', 'quantidade' => 1, 'observacoes' => 'Sob guarda do portador.'],
        ]);
    }
}
