<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Limita tentativas de login por e-mail e por IP, para dificultar
     * ataques de força bruta sem punir usuários atrás de um mesmo NAT.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower($request->string('email')).'|'.$request->ip())
            ->response($this->respostaDeExcesso('auth.throttle')),
        );

        // A consulta por protocolo é pública e os protocolos são sequenciais,
        // logo adivinháveis. O limite não impede a enumeração, mas encarece.
        RateLimiter::for('consulta-publica', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->ip())
            ->response($this->respostaDeExcesso('api.throttle')),
        );
    }

    /**
     * A mensagem de 429 do ThrottleRequests é fixa no framework e não passa
     * pelo tradutor. Esta resposta customizada devolve a versão traduzida,
     * preservando os headers de rate limit.
     *
     * @return Closure(Request, array<string, mixed>): JsonResponse
     */
    protected function respostaDeExcesso(string $chave): Closure
    {
        return fn (Request $request, array $headers): JsonResponse => response()->json(
            ['message' => __($chave, ['seconds' => $headers['Retry-After'] ?? 60])],
            Response::HTTP_TOO_MANY_REQUESTS,
            $headers,
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Sem o envelope `data`: as respostas da API são objetos de topo, no
        // mesmo formato do login.
        JsonResource::withoutWrapping();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
