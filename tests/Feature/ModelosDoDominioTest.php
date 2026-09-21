<?php

use App\Enums\StatusExpedicao;
use App\Enums\TipoUsuario;
use App\Models\Expedicao;
use App\Models\ItemSuprimento;
use App\Models\Jornada;
use App\Models\Participante;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

it('grava um usuario do tipo REINO com codigo e um do tipo CONSELHO sem codigo', function () {
    $reino = Usuario::factory()->reino()->create();
    $conselho = Usuario::factory()->conselho()->create();

    expect($reino->tipo)->toBe(TipoUsuario::Reino)
        ->and($reino->codigo)->not->toBeNull()
        ->and($reino->isReino())->toBeTrue()
        ->and($reino->isConselho())->toBeFalse()
        ->and($conselho->tipo)->toBe(TipoUsuario::Conselho)
        ->and($conselho->codigo)->toBeNull()
        ->and($conselho->isConselho())->toBeTrue()
        ->and($conselho->isReino())->toBeFalse();
});

it('usa a tabela unica usuarios para os dois tipos', function () {
    Usuario::factory()->reino()->create();
    Usuario::factory()->conselho()->create();

    expect((new Usuario)->getTable())->toBe('usuarios');
    $this->assertDatabaseCount('usuarios', 2);
});

it('filtra usuarios pelos scopes reinos e conselho', function () {
    Usuario::factory()->reino()->count(2)->create();
    Usuario::factory()->conselho()->create();

    expect(Usuario::reinos()->count())->toBe(2)
        ->and(Usuario::conselho()->count())->toBe(1);
});

it('nunca expoe o password ao serializar um usuario', function () {
    $usuario = Usuario::factory()->reino()->create();

    expect($usuario->toArray())->not->toHaveKey('password');
});

it('guarda o password com hash', function () {
    $usuario = Usuario::factory()->reino()->create(['password' => 'senha-secreta']);

    expect($usuario->password)->not->toBe('senha-secreta')
        ->and(Hash::check('senha-secreta', $usuario->password))->toBeTrue();
});

it('liga a expedicao ao reino solicitante e ao membro do conselho que decidiu', function () {
    $reino = Usuario::factory()->reino()->create();
    $conselheiro = Usuario::factory()->conselho()->create();

    $expedicao = Expedicao::factory()
        ->for($reino, 'reino')
        ->autorizada($conselheiro)
        ->create();

    expect($expedicao->reino->is($reino))->toBeTrue()
        ->and($expedicao->decididoPor->is($conselheiro))->toBeTrue()
        ->and($reino->expedicoesSolicitadas)->toHaveCount(1)
        ->and($conselheiro->expedicoesDecididas)->toHaveCount(1);
});

it('expoe jornada, participantes e itens de suprimento de uma expedicao', function () {
    $expedicao = Expedicao::factory()->completa(participantes: 3, itens: 2)->create();

    expect($expedicao->jornada)->toBeInstanceOf(Jornada::class)
        ->and($expedicao->participantes)->toHaveCount(3)
        ->and($expedicao->participantes->first())->toBeInstanceOf(Participante::class)
        ->and($expedicao->itensSuprimento)->toHaveCount(2)
        ->and($expedicao->itensSuprimento->first())->toBeInstanceOf(ItemSuprimento::class);
});

it('usa os nomes de tabela do dominio em portugues', function () {
    expect((new Expedicao)->getTable())->toBe('expedicoes')
        ->and((new Jornada)->getTable())->toBe('jornadas')
        ->and((new Participante)->getTable())->toBe('participantes')
        ->and((new ItemSuprimento)->getTable())->toBe('itens_suprimento');
});

it('nasce pendente e converte status e datas', function () {
    $expedicao = Expedicao::factory()->create();

    expect($expedicao->status)->toBe(StatusExpedicao::Pendente)
        ->and($expedicao->finalizada())->toBeFalse()
        ->and($expedicao->decidido_em)->toBeNull();

    $rejeitada = Expedicao::factory()->rejeitada('Rota sob domínio inimigo.')->create();

    expect($rejeitada->status)->toBe(StatusExpedicao::Rejeitada)
        ->and($rejeitada->finalizada())->toBeTrue()
        ->and($rejeitada->motivo_rejeicao)->toBe('Rota sob domínio inimigo.')
        ->and($rejeitada->decidido_em)->toBeInstanceOf(CarbonImmutable::class);
});

it('converte a data de partida da jornada e a quantidade do item', function () {
    $jornada = Jornada::factory()->create(['data_partida_prevista' => '2026-03-25']);
    $item = ItemSuprimento::factory()->create(['quantidade' => 12]);

    expect($jornada->data_partida_prevista)->toBeInstanceOf(CarbonImmutable::class)
        ->and($jornada->data_partida_prevista->toDateString())->toBe('2026-03-25')
        ->and($item->quantidade)->toBe(12);
});

it('apaga jornada, participantes e itens junto com a expedicao', function () {
    $expedicao = Expedicao::factory()->completa(participantes: 2, itens: 2)->create();

    $expedicao->delete();

    $this->assertDatabaseCount('jornadas', 0);
    $this->assertDatabaseCount('participantes', 0);
    $this->assertDatabaseCount('itens_suprimento', 0);
});

it('impede apagar um reino que ainda tem expedicao registrada', function () {
    $reino = Usuario::factory()->reino()->create();
    Expedicao::factory()->for($reino, 'reino')->create();

    expect(fn () => $reino->delete())->toThrow(QueryException::class);
});

it('mantem a expedicao e zera decidido_por quando o membro do conselho e removido', function () {
    $conselheiro = Usuario::factory()->conselho()->create();
    $expedicao = Expedicao::factory()->autorizada($conselheiro)->create();

    $conselheiro->delete();

    expect($expedicao->fresh()->decidido_por)->toBeNull();
});
