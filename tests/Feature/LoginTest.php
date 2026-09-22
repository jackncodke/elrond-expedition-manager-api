<?php

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

it('autentica um usuario REINO e devolve token e tipo', function () {
    $reino = Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $resposta = $this->postJson(route('v1.login'), [
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

    $this->postJson(route('v1.login'), [
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

    $this->postJson(route('v1.login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-errada',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('recusa um email que nao existe', function () {
    $this->postJson(route('v1.login'), [
        'email' => 'ninguem@elrond.test',
        'password' => 'seja-la-o-que-for',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('recusa um usuario inativo sem revelar que ele existe', function () {
    Usuario::factory()->reino()->inativo()->create([
        'email' => 'isengard@elrond.test',
        'password' => 'senha-valida',
    ]);

    $inativo = $this->postJson(route('v1.login'), [
        'email' => 'isengard@elrond.test',
        'password' => 'senha-valida',
    ]);

    $inexistente = $this->postJson(route('v1.login'), [
        'email' => 'ninguem@elrond.test',
        'password' => 'senha-valida',
    ]);

    $inativo->assertUnprocessable();
    expect($inativo->json('errors.email'))->toBe($inexistente->json('errors.email'));
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('exige email e password', function () {
    $this->postJson(route('v1.login'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('recusa um email mal formado', function () {
    $this->postJson(route('v1.login'), ['email' => 'nao-e-email', 'password' => 'x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('emite um token que autentica nas rotas protegidas pelo tipo', function () {
    Usuario::factory()->conselho()->create([
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ]);

    $token = $this->postJson(route('v1.login'), [
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

    $tentativa = fn () => $this->postJson(route('v1.login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-errada',
    ]);

    foreach (range(1, 5) as $ignored) {
        $tentativa()->assertUnprocessable();
    }

    $excedida = $tentativa();

    $excedida->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
        ->assertHeader('Retry-After');

    expect($excedida->json('message'))->toContain('Tentativas de login em excesso');
});

it('nunca devolve o password do usuario', function () {
    Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $resposta = $this->postJson(route('v1.login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    expect($resposta->json('usuario'))->not->toHaveKey('password')
        ->and($resposta->content())->not->toContain('senha-do-reino');
});

it('emite um token de REINO que expira em 8 horas', function () {
    Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $this->freezeTime();

    $this->postJson(route('v1.login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ])->assertOk();

    expect(PersonalAccessToken::sole()->expires_at->format('Y-m-d H:i:s'))
        ->toBe(Date::now()->addMinutes(480)->format('Y-m-d H:i:s'));
});

it('emite um token de CONSELHO que expira em 2 horas', function () {
    Usuario::factory()->conselho()->create([
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ]);

    $this->freezeTime();

    $this->postJson(route('v1.login'), [
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ])->assertOk();

    expect(PersonalAccessToken::sole()->expires_at->format('Y-m-d H:i:s'))
        ->toBe(Date::now()->addMinutes(120)->format('Y-m-d H:i:s'));
});

it('usa os minutos de expiracao configurados para cada tipo', function () {
    config()->set('conselho.autenticacao.expiracao_em_minutos', [
        TipoUsuario::Reino->value => 15,
        TipoUsuario::Conselho->value => 5,
    ]);

    expect(TipoUsuario::Reino->minutosDeExpiracaoDoToken())->toBe(15)
        ->and(TipoUsuario::Conselho->minutosDeExpiracaoDoToken())->toBe(5);
});

it('recusa com 401 um token expirado', function () {
    Usuario::factory()->conselho()->create([
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ]);

    $token = $this->postJson(route('v1.login'), [
        'email' => 'elrond@elrond.test',
        'password' => 'senha-do-conselho',
    ])->json('token');

    Route::middleware(['auth:sanctum', 'conselho'])
        ->get('/_teste/conselho', fn (): array => ['ok' => true]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/_teste/conselho')
        ->assertOk();

    // Um minuto depois do prazo de 2h do Conselho.
    $this->travelTo(Date::now()->addMinutes(121));
    reiniciaAutenticacao();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/_teste/conselho')
        ->assertUnauthorized();
});

it('revoga o token anterior a cada novo login', function () {
    Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $credenciais = ['email' => 'gondor@elrond.test', 'password' => 'senha-do-reino'];

    $antigo = $this->postJson(route('v1.login'), $credenciais)->json('token');
    $novo = $this->postJson(route('v1.login'), $credenciais)->json('token');

    Route::middleware(['auth:sanctum', 'reino'])
        ->get('/_teste/reino', fn (): array => ['ok' => true]);

    $this->withHeader('Authorization', "Bearer {$antigo}")
        ->getJson('/_teste/reino')
        ->assertUnauthorized();

    $this->withHeader('Authorization', "Bearer {$novo}")
        ->getJson('/_teste/reino')
        ->assertOk();

    $this->assertDatabaseCount('personal_access_tokens', 1);
});

it('nao revoga os tokens de outros usuarios ao fazer login', function () {
    $outro = Usuario::factory()->conselho()->create();
    $outro->createToken('api');

    Usuario::factory()->reino()->create([
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ]);

    $this->postJson(route('v1.login'), [
        'email' => 'gondor@elrond.test',
        'password' => 'senha-do-reino',
    ])->assertOk();

    $this->assertDatabaseCount('personal_access_tokens', 2);
    expect($outro->tokens()->count())->toBe(1);
});
