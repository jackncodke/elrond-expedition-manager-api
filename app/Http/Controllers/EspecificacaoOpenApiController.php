<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EspecificacaoOpenApiController extends Controller
{
    /**
     * Serve `docs/openapi.json`, que fica fora de `public/` e por isso não é
     * alcançável direto pelo servidor web.
     *
     * A especificação é escrita à mão e versionada no repositório: não há
     * geração em tempo de requisição, só a leitura do arquivo.
     *
     * @throws NotFoundHttpException
     */
    public function __invoke(): Response
    {
        $caminho = (string) config('conselho.documentacao.especificacao');

        abort_unless(
            is_file($caminho),
            Response::HTTP_NOT_FOUND,
            'Especificação OpenAPI não encontrada.',
        );

        return response(
            (string) file_get_contents($caminho),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }
}
