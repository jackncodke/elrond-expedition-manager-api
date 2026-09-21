# API do Conselho de Elrond

API REST para registro, análise e autorização de expedições. Reinos registram
solicitações de forma estruturada e acompanham o andamento por um protocolo
único; Membros do Conselho deliberam por um endpoint interno.

Projeto **API-only**: não há frontend, Blade ou Vite. `routes/api.php` é o
único ponto de entrada de rotas.

- Laravel 13 · PHP 8.4 · PostgreSQL 15
- Autenticação por token com Laravel Sanctum
- Testes com Pest (SQLite in-memory)

A especificação que originou o projeto está em
[`docs/prompts/001-schema-inicial.md`](docs/prompts/001-schema-inicial.md).

---

## Sumário

- [Como rodar](#como-rodar)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Modelo de acesso](#modelo-de-acesso)
- [Dados de exemplo](#dados-de-exemplo)
- [Endpoints](#endpoints)
  - [1. Login](#1-login)
  - [2. Criar expedição](#2-criar-expedição)
  - [3. Consultar expedição](#3-consultar-expedição)
  - [4. Decisão do Conselho (interno)](#4-decisão-do-conselho-interno)
  - [5. Consultar status](#5-consultar-status)
- [Diferenças de acesso por tipo](#diferenças-de-acesso-por-tipo)
- [Collection do Postman](#collection-do-postman)
- [Respostas de erro](#respostas-de-erro)
- [Testes e qualidade](#testes-e-qualidade)
- [Estrutura](#estrutura)

---

## Como rodar

Pré-requisitos: PHP 8.4 com as extensões `pdo_pgsql` e `pdo_sqlite` (esta
última só para rodar os testes), Composer e um PostgreSQL acessível.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Crie o banco e o usuário no PostgreSQL:

```sql
CREATE USER elrond_user WITH PASSWORD 'elrond_password';
CREATE DATABASE elrond_expedition_manager_db OWNER elrond_user;
```

Ajuste `DB_*` no `.env`, gere um token de serviço e rode as migrations:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # cole em INTERNAL_SERVICE_TOKEN

php artisan migrate:status   # confira o que está pendente antes
php artisan migrate
php artisan db:seed          # opcional: dados de exemplo
php artisan serve
```

A API sobe em `http://localhost:8000`. Os exemplos abaixo usam essa URL.

---

## Variáveis de ambiente

Além das variáveis padrão do Laravel, o projeto usa:

| Variável | Descrição |
|---|---|
| `DB_CONNECTION` | `pgsql`. Os testes sobrescrevem para `sqlite` in-memory via `phpunit.xml`. |
| `INTERNAL_ALLOWED_IPS` | Allowlist de origem do endpoint interno, separada por vírgula. Aceita IPv4, IPv6 e notação CIDR — ex.: `127.0.0.1,::1,10.0.0.0/8`. |
| `INTERNAL_SERVICE_TOKEN` | Token de serviço do endpoint interno, comparado com `hash_equals` contra o header `X-Internal-Service-Token`. |
| `EXPEDICAO_PROTOCOLO_PREFIXO` | Prefixo dos protocolos gerados. Padrão `ELR`, compondo `ELR-2026-000123`. |

Lidas em `config/conselho.php` — nunca por `env()` fora de `config/`, para que
`php artisan config:cache` continue funcionando.

> **Allowlist vazia ou token não configurado bloqueiam o endpoint interno.**
> A falha é sempre fechada: sem configuração válida, ninguém decide nada.

---

## Modelo de acesso

Existe **uma única tabela `usuarios`**, com o campo `tipo` distinguindo os dois
perfis. Não há tabelas nem guards separados.

| `tipo` | Quem é | `codigo` |
|---|---|---|
| `REINO` | Reino solicitante | Obrigatório e único |
| `CONSELHO` | Membro do Conselho | Sempre nulo |

O login é o mesmo para os dois; o token do Sanctum devolvido carrega o usuário,
e os middlewares `reino` e `conselho` restringem cada rota ao perfil correto.

---

## Dados de exemplo

Depois de `php artisan db:seed`:

| Usuário | Tipo | Código | E-mail | Senha |
|---|---|---|---|---|
| Reino de Gondor | `REINO` | `GONDOR` | `gondor@reinos.elrond.test` | `password` |
| Reino de Rohan | `REINO` | `ROHAN` | `rohan@reinos.elrond.test` | `password` |
| Elrond | `CONSELHO` | — | `elrond@conselho.elrond.test` | `password` |

| Protocolo | Reino | Status |
|---|---|---|
| `ELR-2026-000001` | Gondor | `PENDENTE` |
| `ELR-2026-000002` | Rohan | `AUTORIZADA` |

A senha `password` vale apenas para desenvolvimento.

---

## Endpoints

### 1. Login

`POST /api/login` — autentica qualquer usuário e devolve um token do Sanctum
junto do seu tipo. Limitado a 5 tentativas por minuto por e-mail + IP.

**Como um Reino:**

```bash
curl -s -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "gondor@reinos.elrond.test",
    "password": "password"
  }'
```

```json
{
  "token": "3|Xk9fL2mQ...",
  "tipo": "REINO",
  "usuario": {
    "id": 1,
    "tipo": "REINO",
    "nome": "Reino de Gondor",
    "codigo": "GONDOR",
    "email": "gondor@reinos.elrond.test",
    "ativo": true
  }
}
```

**Como um Membro do Conselho** — mesma rota, só muda a credencial:

```bash
curl -s -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "elrond@conselho.elrond.test",
    "password": "password"
  }'
```

```json
{
  "token": "4|Pz7hR4nW...",
  "tipo": "CONSELHO",
  "usuario": {
    "id": 3,
    "tipo": "CONSELHO",
    "nome": "Elrond",
    "codigo": null,
    "email": "elrond@conselho.elrond.test",
    "ativo": true
  }
}
```

Para reaproveitar o token nos próximos comandos:

```bash
TOKEN_REINO=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"gondor@reinos.elrond.test","password":"password"}' \
  | python -c "import sys,json; print(json.load(sys.stdin)['token'])")
```

Credenciais inválidas, e-mail inexistente ou conta inativa devolvem o **mesmo**
`422`, para não permitir descobrir quais contas existem:

```json
{
  "message": "As credenciais informadas são inválidas.",
  "errors": { "email": ["As credenciais informadas são inválidas."] }
}
```

---

### 2. Criar expedição

`POST /api/expedicoes` — **exige token de um usuário `REINO`**. Cria a
expedição com jornada, participantes e itens de suprimento numa única
requisição, gera o protocolo e devolve `201`.

```bash
curl -s -X POST http://localhost:8000/api/expedicoes \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_REINO" \
  -d '{
    "jornada": {
      "origem": "Minas Tirith",
      "destino": "Osgiliath",
      "data_partida_prevista": "2026-11-03",
      "rota_descricao": "Travessia do Anduin ao amanhecer."
    },
    "participantes": [
      { "nome": "Faramir",  "raca": "Humano", "papel": "Líder" },
      { "nome": "Beregond", "raca": "Humano", "papel": "Guarda" }
    ],
    "itens_suprimento": [
      { "nome": "Arco de Ithilien", "tipo": "arma",     "quantidade": 12, "observacoes": "Com aljavas." },
      { "nome": "Lembas",           "tipo": "provisao", "quantidade": 40, "observacoes": null }
    ]
  }'
```

```json
{
  "protocolo": "ELR-2026-000003",
  "status": "PENDENTE",
  "motivo_rejeicao": null,
  "reino": { "id": 1, "tipo": "REINO", "nome": "Reino de Gondor", "codigo": "GONDOR" },
  "jornada": {
    "origem": "Minas Tirith",
    "destino": "Osgiliath",
    "data_partida_prevista": "2026-11-03",
    "rota_descricao": "Travessia do Anduin ao amanhecer."
  },
  "participantes": [
    { "nome": "Faramir",  "raca": "Humano", "papel": "Líder" },
    { "nome": "Beregond", "raca": "Humano", "papel": "Guarda" }
  ],
  "itens_suprimento": [
    { "nome": "Arco de Ithilien", "tipo": "arma",     "quantidade": 12, "observacoes": "Com aljavas." },
    { "nome": "Lembas",           "tipo": "provisao", "quantidade": 40, "observacoes": null }
  ],
  "decidido_por": null,
  "decidido_em": null,
  "criado_em": "2026-09-21T20:25:48+00:00",
  "atualizado_em": "2026-09-21T20:25:48+00:00"
}
```

**Regras de validação**

| Campo | Regra |
|---|---|
| `jornada.origem`, `jornada.destino` | Obrigatórios, até 120 caracteres |
| `jornada.data_partida_prevista` | Obrigatório, data válida, de hoje em diante |
| `jornada.rota_descricao` | Opcional |
| `participantes` | Ao menos um |
| `participantes.*.nome` | Obrigatório, até 120 caracteres |
| `participantes.*.raca`, `participantes.*.papel` | Obrigatórios, até 60 caracteres |
| `itens_suprimento` | Obrigatório estar presente; pode vir vazio (`[]`) |
| `itens_suprimento.*.quantidade` | Inteiro, mínimo 1 |

O Reino solicitante vem sempre do token. Enviar `usuario_id`, `protocolo` ou
`status` no corpo resulta em `422` — a API recusa explicitamente em vez de
ignorar em silêncio:

```bash
curl -s -X POST http://localhost:8000/api/expedicoes \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_REINO" \
  -d '{"usuario_id": 2, "jornada": {}, "participantes": [], "itens_suprimento": []}'
```

```json
{
  "message": "O Reino solicitante é determinado pelo token autenticado e não pode ser enviado. (and 5 more errors)",
  "errors": {
    "usuario_id": ["O Reino solicitante é determinado pelo token autenticado e não pode ser enviado."],
    "jornada": ["O campo jornada é obrigatório."],
    "jornada.origem": ["O campo origem é obrigatório."],
    "jornada.destino": ["O campo destino é obrigatório."],
    "jornada.data_partida_prevista": ["O campo data de partida prevista é obrigatório."],
    "participantes": ["A expedição precisa de ao menos um participante."]
  }
}
```

Uma data de partida no passado:

```json
{
  "message": "A data de partida prevista não pode estar no passado.",
  "errors": {
    "jornada.data_partida_prevista": ["A data de partida prevista não pode estar no passado."]
  }
}
```

---

### 3. Consultar expedição

`GET /api/expedicoes/{protocolo}` — **consulta pública**, sem autenticação.
Devolve os dados completos, o status, a última atualização e o motivo da
rejeição quando houver.

```bash
curl -s http://localhost:8000/api/expedicoes/ELR-2026-000002 \
  -H "Accept: application/json"
```

```json
{
  "protocolo": "ELR-2026-000002",
  "status": "AUTORIZADA",
  "motivo_rejeicao": null,
  "reino": { "id": 2, "tipo": "REINO", "nome": "Reino de Rohan", "codigo": "ROHAN" },
  "jornada": {
    "origem": "Edoras",
    "destino": "Abismo de Helm",
    "data_partida_prevista": "2026-10-12",
    "rota_descricao": "Marcha pela planície de Rohan, evitando os vaus do Isen."
  },
  "participantes": [
    { "nome": "Éomer",   "raca": "Humano", "papel": "Líder" },
    { "nome": "Gimli",   "raca": "Anão",   "papel": "Guarda" },
    { "nome": "Legolas", "raca": "Elfo",   "papel": "Batedor" }
  ],
  "itens_suprimento": [
    { "nome": "Lança de Rohan", "tipo": "arma", "quantidade": 30, "observacoes": null }
  ],
  "decidido_por": { "id": 3, "tipo": "CONSELHO", "nome": "Elrond", "codigo": null },
  "decidido_em": "2026-09-18T14:02:11+00:00",
  "criado_em": "2026-09-15T09:30:00+00:00",
  "atualizado_em": "2026-09-18T14:02:11+00:00"
}
```

Por ser um endpoint público, `reino` e `decidido_por` trazem apenas
identificação — `email` e `ativo` ficam de fora.

Protocolo inexistente devolve `404`:

```json
{ "message": "Nenhuma expedição encontrada para o protocolo informado." }
```

---

### 4. Decisão do Conselho (interno)

`PATCH /api/expedicoes/{protocolo}/decisao` — **requisição interna**. Exige as
três camadas ao mesmo tempo:

1. **Rede** — IP de origem dentro de `INTERNAL_ALLOWED_IPS`
2. **Token de serviço** — header `X-Internal-Service-Token` válido
3. **Usuário** — token Sanctum de um usuário `tipo = CONSELHO`

Qualquer falha nas camadas 1 ou 2 devolve o mesmo `403` genérico, sem indicar
qual delas barrou.

**Autorizar:**

```bash
TOKEN_CONSELHO=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"elrond@conselho.elrond.test","password":"password"}' \
  | python -c "import sys,json; print(json.load(sys.stdin)['token'])")

curl -s -X PATCH http://localhost:8000/api/expedicoes/ELR-2026-000001/decisao \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_CONSELHO" \
  -H "X-Internal-Service-Token: $INTERNAL_SERVICE_TOKEN" \
  -d '{ "decisao": "AUTORIZADA" }'
```

**Rejeitar** — `motivo` é obrigatório:

```bash
curl -s -X PATCH http://localhost:8000/api/expedicoes/ELR-2026-000001/decisao \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_CONSELHO" \
  -H "X-Internal-Service-Token: $INTERNAL_SERVICE_TOKEN" \
  -d '{
    "decisao": "REJEITADA",
    "motivo": "A rota atravessa território sob domínio inimigo."
  }'
```

```json
{
  "protocolo": "ELR-2026-000001",
  "status": "REJEITADA",
  "motivo_rejeicao": "A rota atravessa território sob domínio inimigo.",
  "decidido_por": { "id": 3, "tipo": "CONSELHO", "nome": "Elrond", "codigo": null },
  "decidido_em": "2026-09-21T20:41:07+00:00"
}
```

> Um `motivo` enviado junto de uma decisão `AUTORIZADA` é descartado: uma
> expedição autorizada nunca fica com `motivo_rejeicao` preenchido.

#### Simulando as falhas

**Requisição externa** (IP fora da allowlist). Para testar localmente,
remova `127.0.0.1` de `INTERNAL_ALLOWED_IPS`, ou chame a API por trás de um
proxy. O resultado é `403` mesmo com token de usuário válido:

```bash
curl -s -X PATCH http://localhost:8000/api/expedicoes/ELR-2026-000001/decisao \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_CONSELHO" \
  -H "X-Internal-Service-Token: $INTERNAL_SERVICE_TOKEN" \
  -d '{"decisao":"AUTORIZADA"}'
```

```json
{ "message": "Requisição não autorizada." }
```

**Sem o token de serviço** — mesma resposta:

```bash
curl -s -X PATCH http://localhost:8000/api/expedicoes/ELR-2026-000001/decisao \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_CONSELHO" \
  -d '{"decisao":"AUTORIZADA"}'
```

```json
{ "message": "Requisição não autorizada." }
```

**Com token de um Reino** — passa as duas primeiras camadas e é barrado na
terceira:

```bash
curl -s -X PATCH http://localhost:8000/api/expedicoes/ELR-2026-000001/decisao \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_REINO" \
  -H "X-Internal-Service-Token: $INTERNAL_SERVICE_TOKEN" \
  -d '{"decisao":"AUTORIZADA"}'
```

```json
{ "message": "Acesso restrito a membros do Conselho." }
```

**Expedição já decidida** devolve `409`:

```json
{
  "message": "A expedição ELR-2026-000002 já foi AUTORIZADA e não aceita uma nova decisão.",
  "protocolo": "ELR-2026-000002",
  "status": "AUTORIZADA"
}
```

**Rejeição sem motivo** devolve `422`:

```json
{
  "message": "O motivo é obrigatório para rejeitar uma expedição.",
  "errors": { "motivo": ["O motivo é obrigatório para rejeitar uma expedição."] }
}
```

---

### 5. Consultar status

`GET /api/expedicoes/{protocolo}/status` — **consulta pública**. Devolve apenas
o andamento, sem carregar jornada, participantes nem itens. É o endpoint certo
para polling.

```bash
curl -s http://localhost:8000/api/expedicoes/ELR-2026-000001/status \
  -H "Accept: application/json"
```

```json
{
  "protocolo": "ELR-2026-000001",
  "status": "PENDENTE",
  "finalizada": false,
  "motivo_rejeicao": null,
  "decidido_em": null,
  "criado_em": "2026-09-15T09:30:00+00:00",
  "atualizado_em": "2026-09-15T09:30:00+00:00"
}
```

Depois de uma rejeição:

```json
{
  "protocolo": "ELR-2026-000001",
  "status": "REJEITADA",
  "finalizada": true,
  "motivo_rejeicao": "A rota atravessa território sob domínio inimigo.",
  "decidido_em": "2026-09-21T20:41:07+00:00",
  "criado_em": "2026-09-15T09:30:00+00:00",
  "atualizado_em": "2026-09-21T20:41:07+00:00"
}
```

`finalizada` indica se a deliberação já ocorreu — útil para saber quando parar
o polling.

---

## Diferenças de acesso por tipo

| Endpoint | Público | `REINO` | `CONSELHO` | Interno |
|---|:---:|:---:|:---:|:---:|
| `POST /api/login` | ✅ | ✅ | ✅ | — |
| `POST /api/expedicoes` | ❌ `401` | ✅ | ❌ `403` | — |
| `GET /api/expedicoes/{protocolo}` | ✅ | ✅ | ✅ | — |
| `PATCH /api/expedicoes/{protocolo}/decisao` | ❌ `403` | ❌ `403` | ✅ | obrigatório |
| `GET /api/expedicoes/{protocolo}/status` | ✅ | ✅ | ✅ | — |

Um Reino não decide sobre nenhuma expedição, nem sobre as suas. Um Membro do
Conselho não registra expedições.

---

## Collection do Postman

Em [`docs/postman/`](docs/postman/):

- `conselho-de-elrond.postman_collection.json`
- `conselho-de-elrond.postman_environment.json`

Importe os dois, selecione o environment **Conselho de Elrond — Local** e
preencha `internal_service_token` com o valor de `INTERNAL_SERVICE_TOKEN` do
seu `.env`. Rode `php artisan db:seed` antes.

O fluxo se encadeia sozinho: **Login › Reino** e **Login › Conselho** guardam
os tokens em `token_reino` e `token_conselho`; **Criar expedição** guarda o
protocolo gerado em `protocolo`, que as requisições seguintes usam.

| Pasta | Conteúdo |
|---|---|
| Login | Reino e Conselho, pela mesma rota |
| Expedições | Criar, consultar e consultar status |
| Decisão do Conselho (interno) | Autorizar e rejeitar, já com o header `X-Internal-Service-Token` |
| Falhas esperadas | Uma requisição por código de erro: `401`, `403` (três variantes), `404`, `409` e `422` (quatro variantes) |

Todas as requisições têm testes que verificam o status e o formato da
resposta, então a collection roda inteira no Collection Runner.

Um script de pré-requisição calcula `data_partida` (daqui a 45 dias) e
`data_partida_passada` (ontem) a cada execução, para os exemplos não
envelhecerem contra a regra de data futura.

---

## Respostas de erro

Todas as respostas são JSON, inclusive as de erro.

| Código | Quando |
|---|---|
| `401` | Sem token, ou token inválido, numa rota autenticada |
| `403` | Tipo de usuário errado para a rota, ou requisição interna barrada por IP ou token de serviço |
| `404` | Protocolo inexistente |
| `409` | Decisão sobre uma expedição já autorizada ou já rejeitada |
| `422` | Falha de validação |
| `429` | Rate limit excedido (5/min no login, 60/min nas consultas públicas) |

Formato de validação (`422`):

```json
{
  "message": "A primeira mensagem de erro. (and 2 more errors)",
  "errors": {
    "campo": ["Mensagem para o campo."]
  }
}
```

Demais erros trazem apenas `message` — com exceção do `409`, que inclui também
`protocolo` e `status` para o chamador saber qual decisão já valia.

As mensagens saem em português, inclusive as genéricas do validador: as
traduções estão em `lang/pt_BR/`, com os nomes legíveis dos campos em
`validation.attributes`. A mensagem de `429` é fixa no framework e não passa
pelo tradutor, então vem da resposta customizada dos rate limiters, em
`AppServiceProvider`. Há um teste que falha se alguma mensagem em inglês
escapar.

> Os exemplos deste README mostram o formato de produção. Com `APP_DEBUG=true`
> o Laravel anexa `exception`, `file`, `line` e `trace` às respostas de erro.
> Mantenha `APP_DEBUG=false` em produção.

---

## Testes e qualidade

Os testes rodam sobre SQLite in-memory, configurado em `phpunit.xml` — não
tocam o banco de desenvolvimento.

```bash
php artisan test              # suíte completa
php artisan test --compact
vendor/bin/pest --filter=login

vendor/bin/pint               # formatação
vendor/bin/phpstan analyse --memory-limit=1G   # análise estática, nível 7

composer ci:check             # tudo junto, como no CI
```

A suíte cobre, entre outros casos: login dos dois tipos e credenciais
inválidas; criação de expedição por um Reino e bloqueio de um Membro do
Conselho; tentativa de forjar o Reino solicitante; consulta por protocolo
existente e inexistente; as três camadas do endpoint interno barrando
isoladamente; decisão por um Reino; autorização e rejeição bem-sucedidas;
rejeição sem motivo; nova decisão sobre expedição já finalizada; e o status nos
três estados.

---

## Estrutura

```
app/
  Actions/           CriarExpedicao, DecidirExpedicao
  Enums/             TipoUsuario, StatusExpedicao
  Exceptions/        ExpedicaoJaFinalizada (renderiza 409)
  Http/
    Controllers/     Login, Expedicao, DecisaoExpedicao, StatusExpedicao
    Middleware/      EnsureUserIsReino, EnsureUserIsConselho, EnsureRequestIsInternal
    Requests/        Login, StoreExpedicao, DecidirExpedicao
    Resources/       Expedicao, StatusExpedicao, Usuario, UsuarioPublico, Jornada, ...
  Models/            Usuario, Expedicao, Jornada, Participante, ItemSuprimento
  Support/           GeradorDeProtocolo
config/conselho.php  allowlist de IPs, token de serviço, prefixo do protocolo
routes/api.php       único arquivo de rotas
```

Detalhes que valem saber:

- **Geração de protocolo.** `ELR-ANO-SEQUENCIAL`, reiniciando a cada ano. A
  unicidade é garantida pelo índice único da coluna; em caso de colisão entre
  requisições simultâneas, `CriarExpedicao` gera o próximo e tenta de novo.
- **Ordem dos middlewares.** O Laravel reordena middlewares pela lista de
  prioridade, então a ordem declarada na rota não basta. `bootstrap/app.php`
  força `EnsureRequestIsInternal` a rodar antes da autenticação — do contrário
  uma requisição externa com token inválido receberia `401` e uma com token
  válido `403`, revelando a validade do token a quem está fora da rede.
- **Decisão concorrente.** `DecidirExpedicao` relê a expedição com
  `lockForUpdate()` dentro da transação antes de checar o status, para que duas
  decisões simultâneas não passem as duas.
