<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;

/**
 * Autentica o usuário pelo endpoint de login e devolve o token em texto puro.
 */
function tokenDeLogin(Usuario $usuario, string $password): string
{
    return test()->postJson(route('v1.login'), [
        'email' => $usuario->email,
        'password' => $password,
    ])->json('token');
}

it('revoga o token atual e devolve 204', function () {
    $reino = Usuario::factory()->reino()->create(['password' => 'senha-do-reino']);
    $token = tokenDeLogin($reino, 'senha-do-reino');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.logout'))
        ->assertNoContent();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('invalida o token usado no logout para as requisicoes seguintes', function () {
    $conselho = Usuario::factory()->conselho()->create(['password' => 'senha-do-conselho']);
    $token = tokenDeLogin($conselho, 'senha-do-conselho');

    Route::middleware(['auth:sanctum', 'conselho'])
        ->get('/_teste/conselho', fn (): array => ['ok' => true]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.logout'))
        ->assertNoContent();

    reiniciaAutenticacao();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/_teste/conselho')
        ->assertUnauthorized();
});

it('aceita tanto REINO quanto CONSELHO', function (string $estado, string $password) {
    $usuario = Usuario::factory()->{$estado}()->create(['password' => $password]);

    $this->withHeader('Authorization', 'Bearer '.tokenDeLogin($usuario, $password))
        ->postJson(route('v1.logout'))
        ->assertNoContent();
})->with([
    'reino' => ['reino', 'senha-do-reino'],
    'conselho' => ['conselho', 'senha-do-conselho'],
]);

it('recusa o logout sem autenticacao', function () {
    $this->postJson(route('v1.logout'))->assertUnauthorized();
});

it('recusa o logout com um token invalido', function () {
    $this->withHeader('Authorization', 'Bearer token-que-nunca-existiu')
        ->postJson(route('v1.logout'))
        ->assertUnauthorized();
});

it('recusa o logout com um token ja expirado', function () {
    $conselho = Usuario::factory()->conselho()->create(['password' => 'senha-do-conselho']);
    $token = tokenDeLogin($conselho, 'senha-do-conselho');

    $this->travelTo(Date::now()->addMinutes(121));

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.logout'))
        ->assertUnauthorized();
});

it('revoga apenas o token da requisicao, nao os demais do usuario', function () {
    $reino = Usuario::factory()->reino()->create(['password' => 'senha-do-reino']);
    $token = tokenDeLogin($reino, 'senha-do-reino');

    // Um segundo token emitido fora do login (o login revogaria o primeiro).
    $reino->createToken('api-secundario');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('v1.logout'))
        ->assertNoContent();

    expect($reino->tokens()->count())->toBe(1);
});
