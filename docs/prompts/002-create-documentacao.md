Gere a documentação completa da API do Conselho de Elrond, em dois artefatos:

## 1. README.md (na raiz do projeto)

Deve conter, nesta ordem:
- Título e descrição curta do projeto
- Stack utilizada (Laravel, PostgreSQL, Sanctum)
- Pré-requisitos (PHP, Composer, PostgreSQL)
- Passo a passo de instalação local (clone, composer install, .env, 
  migrate, seed, serve)
- Explicação do modelo de autenticação: um único endpoint de login, 
  diferença entre usuário tipo REINO e tipo CONSELHO
- Explicação da proteção do endpoint de decisão (IP allowlist + 
  X-Internal-Service-Token + Sanctum tipo CONSELHO), incluindo como 
  configurar INTERNAL_ALLOWED_IPS e INTERNAL_SERVICE_TOKEN no .env
- Tabela com todos os endpoints: método, rota, autenticação exigida, 
  descrição resumida
- Para cada endpoint: exemplo de requisição via curl (headers, body) e 
  exemplo de resposta JSON (sucesso e principais erros: 401, 403, 404, 
  409, 422)
- Seção de "Como rodar os testes" (comando artisan/pest)
- Seção "Como importar a collection do Postman"

## 2. Especificação OpenAPI 3.0 (docs/openapi.json)

Gere um arquivo openapi.json (OpenAPI 3.0.3) válido e completo, cobrindo 
todos os endpoints já implementados:
- POST /api/login
- POST /api/expedicoes
- GET /api/expedicoes/{protocolo}
- PATCH /api/expedicoes/{protocolo}/decisao
- GET /api/expedicoes/{protocolo}/status

Para cada endpoint, incluir:
- summary e description
- parâmetros de path (ex: protocolo)
- requestBody com schema completo (baseado nos Form Requests já implementados)
- todas as respostas possíveis com status code e schema (200/201, 401, 403, 
  404, 409, 422), usando os schemas dos API Resources como referência
- securitySchemes: bearerAuth (Sanctum) e, para o endpoint de decisão, 
  documentar também o header X-Internal-Service-Token como um 
  apiKey security scheme adicional
- Definir schemas reutilizáveis em components/schemas para: Usuario, 
  Expedicao, Jornada, Participante, ItemSuprimento, ErroValidacao, ErroGenerico

Não use nenhum pacote de geração automática (ex: l5-swagger) — escreva o 
JSON manualmente com base no código real dos controllers, Form Requests e 
Resources, para garantir que reflita o comportamento exato implementado, 
não anotações desatualizadas.

Ao terminar, valide o JSON gerado (sintaxe correta) e informe se algum 
endpoint tem comportamento não coberto pela documentação atual.

---

## 3. Swagger UI servido pela própria API

Crie uma rota GET /api/documentation (fora do grupo de rotas autenticadas)
que retorna uma view HTML simples carregando o Swagger UI via CDN
(https://unpkg.com/swagger-ui-dist), configurada para ler o arquivo
docs/openapi.json exposto em uma rota pública /docs/openapi.json.
