<?php

namespace App\Actions;

use App\Enums\StatusExpedicao;
use App\Models\Expedicao;
use App\Models\Usuario;
use App\Support\GeradorDeProtocolo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Registra uma expedição junto com a jornada, os participantes e os itens de
 * suprimento, em uma única transação.
 */
class CriarExpedicao
{
    /**
     * Quantas vezes tentar de novo quando duas requisições simultâneas
     * disputam o mesmo sequencial de protocolo.
     */
    protected const TENTATIVAS = 5;

    public function __construct(protected GeradorDeProtocolo $gerador) {}

    /**
     * @param  array{jornada: array<string, mixed>, participantes: list<array<string, mixed>>, itens_suprimento?: list<array<string, mixed>>}  $dados
     */
    public function handle(Usuario $reino, array $dados): Expedicao
    {
        for ($tentativa = 1; $tentativa <= self::TENTATIVAS; $tentativa++) {
            try {
                return $this->registrar($reino, $dados);
            } catch (QueryException $e) {
                // O índice único de `protocolo` é o árbitro final: se outra
                // requisição gravou o mesmo sequencial primeiro, geramos o
                // próximo e tentamos de novo.
                if ($tentativa === self::TENTATIVAS || ! $this->protocoloDuplicado($e)) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Não foi possível gerar um protocolo único para a expedição.');
    }

    /**
     * @param  array{jornada: array<string, mixed>, participantes: list<array<string, mixed>>, itens_suprimento?: list<array<string, mixed>>}  $dados
     */
    protected function registrar(Usuario $reino, array $dados): Expedicao
    {
        return DB::transaction(function () use ($reino, $dados): Expedicao {
            $expedicao = Expedicao::create([
                'protocolo' => $this->gerador->proximo(),
                'usuario_id' => $reino->id,
                'status' => StatusExpedicao::Pendente,
            ]);

            $expedicao->jornada()->create($dados['jornada']);
            $expedicao->participantes()->createMany($dados['participantes']);

            if ($dados['itens_suprimento'] ?? []) {
                $expedicao->itensSuprimento()->createMany($dados['itens_suprimento']);
            }

            return $expedicao;
        });
    }

    protected function protocoloDuplicado(QueryException $e): bool
    {
        return $e->getCode() === '23505' // PostgreSQL: unique_violation
            || str_contains($e->getMessage(), 'protocolo');
    }
}
