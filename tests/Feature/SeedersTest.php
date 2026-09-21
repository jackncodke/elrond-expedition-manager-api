<?php

use App\Enums\StatusExpedicao;
use App\Models\Expedicao;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ExpedicaoSeeder;
use Database\Seeders\UsuarioSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('cria dois reinos e um membro do conselho', function () {
    expect(Usuario::reinos()->count())->toBe(2)
        ->and(Usuario::conselho()->count())->toBe(1);

    $reinos = Usuario::reinos()->get();

    expect($reinos->pluck('codigo')->all())->toEqualCanonicalizing(['GONDOR', 'ROHAN'])
        ->and($reinos->every(fn (Usuario $reino): bool => $reino->codigo !== null))->toBeTrue()
        ->and(Usuario::conselho()->sole()->codigo)->toBeNull();
});

it('permite login com a senha padrao dos seeders', function () {
    $this->postJson(route('login'), [
        'email' => 'gondor@reinos.elrond.test',
        'password' => UsuarioSeeder::SENHA_PADRAO,
    ])->assertOk();

    $this->postJson(route('login'), [
        'email' => 'elrond@conselho.elrond.test',
        'password' => UsuarioSeeder::SENHA_PADRAO,
    ])->assertOk();
});

it('cria uma expedicao pendente completa', function () {
    $expedicao = Expedicao::where('protocolo', ExpedicaoSeeder::PROTOCOLO_PENDENTE)->sole();

    expect($expedicao->status)->toBe(StatusExpedicao::Pendente)
        ->and($expedicao->decidido_por)->toBeNull()
        ->and($expedicao->decidido_em)->toBeNull()
        ->and($expedicao->motivo_rejeicao)->toBeNull()
        ->and($expedicao->reino->codigo)->toBe('GONDOR')
        ->and($expedicao->jornada->origem)->toBe('Minas Tirith')
        ->and($expedicao->participantes)->toHaveCount(3)
        ->and($expedicao->itensSuprimento)->toHaveCount(3);
});

it('cria uma expedicao ja decidida pelo conselho', function () {
    $expedicao = Expedicao::where('protocolo', ExpedicaoSeeder::PROTOCOLO_AUTORIZADA)->sole();

    expect($expedicao->status)->toBe(StatusExpedicao::Autorizada)
        ->and($expedicao->finalizada())->toBeTrue()
        ->and($expedicao->decidido_em)->not->toBeNull()
        ->and($expedicao->decididoPor->isConselho())->toBeTrue()
        ->and($expedicao->reino->codigo)->toBe('ROHAN')
        ->and($expedicao->jornada->destino)->toBe('Abismo de Helm')
        ->and($expedicao->participantes)->toHaveCount(4)
        ->and($expedicao->itensSuprimento)->toHaveCount(3);
});

it('so atribui expedicoes a usuarios do tipo REINO', function () {
    Expedicao::with('reino')->get()->each(
        fn (Expedicao $expedicao) => expect($expedicao->reino->isReino())->toBeTrue(),
    );
});

it('pode rodar duas vezes sem duplicar registros', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Usuario::count())->toBe(3)
        ->and(Expedicao::count())->toBe(2);

    $this->assertDatabaseCount('jornadas', 2);
    $this->assertDatabaseCount('participantes', 7);
    $this->assertDatabaseCount('itens_suprimento', 6);
});
