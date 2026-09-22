<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class DocumentacaoController extends Controller
{
    /**
     * Versão fixa do Swagger UI no unpkg, com os hashes de integridade dos
     * arquivos correspondentes.
     *
     * A versão é fixa de propósito: `swagger-ui-dist` sem versão resolve para
     * a última publicada, e uma mudança no CDN passaria a rodar aqui sem
     * ninguém decidir por isso. Ao atualizar, recalcule os hashes com
     * `curl -sL <url> | openssl dgst -sha384 -binary | openssl base64 -A`.
     */
    private const SWAGGER_UI_VERSAO = '5.33.0';

    private const SWAGGER_UI_CSS_SRI = 'sha384-Ov4/wv3j2bmct8cDc5X4ngJZohVPzEmc6uDPH8WeljUxO5vtoykvMEfbu9Vh6RaW';

    private const SWAGGER_UI_JS_SRI = 'sha384-YDALVcy8kj8yltLBVi1vBiBAUqdxvus673gM8XKwiy6aDUJFXivF/KCufekjYbVf';

    /**
     * Serve o Swagger UI apontado para a especificação em `/docs/openapi.json`.
     *
     * É a única resposta HTML da API — o projeto não tem camada de frontend,
     * então o documento é montado aqui mesmo, sem view nem bundler.
     */
    public function __invoke(): Response
    {
        $substituicoes = [
            '{{versao}}' => self::SWAGGER_UI_VERSAO,
            '{{sri_css}}' => self::SWAGGER_UI_CSS_SRI,
            '{{sri_js}}' => self::SWAGGER_UI_JS_SRI,
            // json_encode entrega a URL já como literal de string do JavaScript,
            // com as aspas e os escapes no lugar.
            '{{url_da_especificacao}}' => json_encode(
                route('docs.openapi'),
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
        ];

        return response(
            strtr($this->documento(), $substituicoes),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=utf-8'],
        );
    }

    /**
     * O documento é um nowdoc: nada dentro dele é interpolado pelo PHP, então
     * o JavaScript pode usar `$` à vontade. Os valores entram por `strtr`.
     */
    private function documento(): string
    {
        return <<<'HTML'
            <!DOCTYPE html>
            <html lang="pt-BR">
            <head>
              <meta charset="utf-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <title>API do Conselho de Elrond — Documentação</title>
              <link rel="icon" href="/favicon.svg" type="image/svg+xml">
              <link
                rel="stylesheet"
                href="https://unpkg.com/swagger-ui-dist@{{versao}}/swagger-ui.css"
                integrity="{{sri_css}}"
                crossorigin="anonymous">
              <style>
                body { margin: 0; background: #fafafa; }
                .swagger-ui .topbar { display: none; }
              </style>
            </head>
            <body>
              <div id="swagger-ui"></div>
              <script
                src="https://unpkg.com/swagger-ui-dist@{{versao}}/swagger-ui-bundle.js"
                integrity="{{sri_js}}"
                crossorigin="anonymous"></script>
              <script>
                window.onload = function () {
                  window.ui = SwaggerUIBundle({
                    url: {{url_da_especificacao}},
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    layout: 'BaseLayout',
                    presets: [SwaggerUIBundle.presets.apis],
                    plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                    tryItOutEnabled: true
                  });
                };
              </script>
            </body>
            </html>
            HTML;
    }
}
