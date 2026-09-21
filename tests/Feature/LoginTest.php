<?php

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

it('autentica um usuario REINO e devolve token e tipo', function () {
    $reino = Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $resposta = $this->postJson(route('login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $resposta->assertOk()
        ->assertJsonPath('tipo', TipoUsuario::Reino->value)
        ->assertJsonPath('usuario.id', $reino->id)
        ->assertJsonPath('usuario.codigo', $reino->codigo)
        ->assertJsonStructure(['token', 'tipo', 'usuario' => ['id', 'tipo', 'nome', 'codigo', 'email', 'ativo']]);

    expect($resposta->json('token'))->toBeString()->not->toBeEmpty();
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

it('autentica um usuario CONSELHO e devolve token e tipo', function () {
    Usuario::factory()->conselho()->create([
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ]);

    $this->postJson(route('login'), [
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ])
        ->assertOk()
        ->assertJsonPath('tipo', TipoUsuario::Conselho->value)
        ->assertJsonPath('usuario.codigo', null);
});

it('recusa credenciais invalidas', function () {
    Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $this->postJson(route('login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-errada',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('recusa um email que nao existe', function () {
    $this->postJson(route('login'), [
        'email' => 'ninguem@elrond.test',
        'password' => 'seja-la-o-que-for',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('recusa um usuario inativo sem revelar que ele existe', function () {
    Usuario::factory()->reino()->inativo()->create([
        'email' => 'isengard@elrond.test',
        'password' => 'senha-valida',
    ]);

    $inativo = $this->postJson(route('login'), [
        'email' => 'isengard@elrond.test',
        'password' => 'senha-valida',
    ]);

    $inexistente = $this->postJson(route('login'), [
        'email' => 'ninguem@elrond.test',
        'password' => 'senha-valida',
    ]);

    $inativo->assertUnprocessable();
    expect($inativo->json('errors.email'))->toBe($inexistente->json('errors.email'));
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('exige email e password', function () {
    $this->postJson(route('login'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('recusa um email mal formado', function () {
    $this->postJson(route('login'), ['email' => 'nao-e-email', 'password' => 'x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('emite um token que autentica nas rotas protegidas pelo tipo', function () {
    Usuario::factory()->conselho()->create([
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ]);

    $token = $this->postJson(route('login'), [
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ])->json('token');

    Route::middleware(['auth:sanctum', 'conselho'])
        ->get('/_teste/conselho', fn (): array => ['ok' => true]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/_teste/conselho')
        ->assertOk();
});

it('limita tentativas de login repetidas', function () {
    Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $tentativa = fn () => $this->postJson(route('login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-errada',
    ]);

    foreach (range(1, 5) as $ignored) {
        $tentativa()->assertUnprocessable();
    }

    $tentativa()->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
});

it('nunca devolve o password do usuario', function () {
    Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $resposta = $this->postJson(route('login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    expect($resposta->json('usuario'))->not->toHaveKey('password')
        ->and($resposta->content())->not->toContain('senha-do-reino');
});
