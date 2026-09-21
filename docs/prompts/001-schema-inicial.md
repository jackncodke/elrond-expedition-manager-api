# 001 — Schema Inicial e Especificação da API do Conselho de Elrond

> Prompt de especificação inicial usado para gerar a estrutura de dados e os
> endpoints principais da API. Mantido aqui para histórico de decisões de
> arquitetura. Regras permanentes derivadas deste documento estão resumidas
> em `CLAUDE.md`.

## Contexto

API REST do Conselho de Elrond para digitalizar e padronizar o processo de
registro, análise e autorização de expedições, substituindo o fluxo por
cartas. Reinos registram solicitações de expedição de forma estruturada e
acompanham o status por meio de um protocolo único. Membros do Conselho
deliberam (autorizam ou rejeitam) via um endpoint interno.

O projeto Laravel já foi criado com Laravel Boost em
`elrond-expedition-manager`.

---

## Remoção da camada de frontend

Este projeto é uma API pura (API-only). Remover/limpar tudo relacionado a
frontend:

- Deletar `resources/views/*` (exceto o estritamente necessário, como views
  de erro)
- Remover a rota padrão `/` (welcome) de `routes/web.php`
- Remover Vite, `package.json`, `vite.config.js`, `resources/js`,
  `resources/css`
- Ajustar `bootstrap/app.php` para não carregar rotas web desnecessárias
- Garantir que `routes/api.php` seja o único ponto de entrada de rotas
- Configurar o `.env` com `APP_URL` apropriado

---

## Banco de dados

Configurar o `.env` para usar PostgreSQL:

```
DB_CONNECTION=pgsql
DB_DATABASE=elrond_expedition_manager_db
DB_USERNAME=elrond_user
DB_PASSWORD=elrond_password
DB_HOST=127.0.0.1
DB_PORT=5432
```

## Stack

- Sanctum para autenticação (um único guard, uma única tabela de usuários)
- PostgreSQL
- Migrations, Models, Form Requests, Resources, Controllers, Policies e
  Middlewares separados
- Testes automatizados (Pest ou PHPUnit, conforme padrão do Boost)
- SQLite in-memory para o ambiente de testes (`phpunit.xml`)

---

## Schema explícito das tabelas

Criar as migrations **exatamente** com esta estrutura. Não adicionar campos
além dos listados sem antes perguntar. `snake_case`, timestamps padrão em
todas as tabelas, FKs com `onDelete` definido.

### tabela: `usuarios`

| campo      | tipo               | regras                                                                 |
|------------|--------------------|-------------------------------------------------------------------------|
| id         | bigint, PK         | auto increment                                                           |
| tipo       | string(20)         | not null — enum de valores: `REINO` \| `CONSELHO`                       |
| nome       | string(120)        | not null                                                                 |
| codigo     | string(20)         | nullable, unique — obrigatório se `tipo = REINO`; nulo se `CONSELHO`     |
| email      | string             | not null, unique                                                         |
| password   | string             | not null (hash, usado com Sanctum)                                      |
| ativo      | boolean            | not null, default `true`                                                |
| timestamps | —                  | `created_at`, `updated_at`                                              |

- índice em `(tipo)`
- Regra `codigo` obrigatório/nulo conforme `tipo`: validada em Form Request
  (não como CHECK constraint no banco)

### tabela: `expedicoes`

| campo            | tipo         | regras                                                                                  |
|------------------|--------------|--------------------------------------------------------------------------------------------|
| id               | bigint, PK   | auto increment                                                                              |
| protocolo        | string(30)   | not null, unique — ex: `ELR-2026-000123`                                                    |
| usuario_id       | bigint, FK   | -> `usuarios.id`, not null, `onDelete restrict` — o Reino solicitante (`tipo = REINO`)       |
| status           | string(20)   | not null, default `PENDENTE` — `PENDENTE` \| `AUTORIZADA` \| `REJEITADA`                     |
| motivo_rejeicao  | text         | nullable                                                                                     |
| decidido_por     | bigint, FK   | -> `usuarios.id`, nullable, `onDelete set null` — o Membro do Conselho (`tipo = CONSELHO`)   |
| decidido_em      | timestamp    | nullable                                                                                     |
| timestamps       | —            | `created_at`, `updated_at`                                                                   |

- índice em `(protocolo)` e em `(status)`

### tabela: `jornadas`

| campo                  | tipo        | regras                                                    |
|------------------------|-------------|------------------------------------------------------------|
| id                     | bigint, PK  | auto increment                                              |
| expedicao_id           | bigint, FK  | -> `expedicoes.id`, not null, `onDelete cascade`, unique (1:1) |
| origem                 | string(120) | not null                                                    |
| destino                | string(120) | not null                                                    |
| data_partida_prevista  | date        | not null                                                    |
| rota_descricao         | text        | nullable                                                    |
| timestamps             | —           | `created_at`, `updated_at`                                  |

### tabela: `participantes`

| campo         | tipo        | regras                                             |
|---------------|-------------|-------------------------------------------------------|
| id            | bigint, PK  | auto increment                                         |
| expedicao_id  | bigint, FK  | -> `expedicoes.id`, not null, `onDelete cascade`       |
| nome          | string(120) | not null                                               |
| raca          | string(60)  | not null — ex: "Humano", "Elfo", "Anão", "Hobbit"      |
| papel         | string(60)  | not null — ex: "Líder", "Batedor", "Guarda"            |
| timestamps    | —           | `created_at`, `updated_at`                             |

### tabela: `itens_suprimento`

| campo         | tipo        | regras                                                              |
|---------------|-------------|------------------------------------------------------------------------|
| id            | bigint, PK  | auto increment                                                          |
| expedicao_id  | bigint, FK  | -> `expedicoes.id`, not null, `onDelete cascade`                        |
| nome          | string(120) | not null                                                                |
| tipo          | string(40)  | not null — ex: "arma", "provisao", "artefato_magico", "montaria"        |
| quantidade    | integer     | not null, default `1`                                                  |
| observacoes   | text        | nullable                                                                |
| timestamps    | —           | `created_at`, `updated_at`                                              |

---

## Models e relacionamentos

- `Usuario`: scopes `scopeReinos()` (where `tipo = REINO`) e
  `scopeConselho()` (where `tipo = CONSELHO`). Métodos auxiliares
  `isReino()` e `isConselho()`.
- `Usuario` `hasMany` `Expedicao` (via `usuario_id`, relação
  `expedicoesSolicitadas`)
- `Usuario` `hasMany` `Expedicao` (via `decidido_por`, relação
  `expedicoesDecididas`)
- `Expedicao` `belongsTo` `Usuario` (relação `reino`, FK `usuario_id`),
  `belongsTo` `Usuario` (relação `decididoPor`, FK `decidido_por`),
  `hasOne` `Jornada`, `hasMany` `Participante`, `hasMany` `ItemSuprimento`
- `Jornada` `belongsTo` `Expedicao`
- `Participante` `belongsTo` `Expedicao`
- `ItemSuprimento` `belongsTo` `Expedicao`

---

## Autenticação e controle de acesso por tipo

- Endpoint único de login (`POST /api/login`) recebendo email/password,
  retornando token Sanctum + tipo do usuário autenticado
- Middleware `EnsureUserIsReino` (bloqueia se `usuarios.tipo != 'REINO'`)
- Middleware `EnsureUserIsConselho` (bloqueia se `usuarios.tipo != 'CONSELHO'`)
- Aplicar o middleware correto em cada rota conforme o perfil exigido
- Ao criar expedição, usar o usuário autenticado (`auth()->id()`) como
  `usuario_id` — nunca aceitar esse campo no body da requisição

**Checkpoint:** após gerar as migrations, rodar `php artisan migrate:status`
(sem migrar ainda) e mostrar o conteúdo das migrations antes de aplicar,
para revisão dos campos.

---

## Requisito crítico: distinção interno vs. externo

O endpoint de decisão do conselho é uma requisição **interna** e deve ser
protegido por **duas camadas combinadas** (defesa em profundidade), além de
Sanctum + `EnsureUserIsConselho`:

1. **Camada de rede** — Middleware `EnsureRequestIsInternal` valida se o IP
   de origem está em uma allowlist configurável via `.env`
   (`INTERNAL_ALLOWED_IPS=127.0.0.1,10.0.0.0/8`), suportando notação CIDR.
   Fora da lista → `403` imediato.
2. **Camada de token de serviço** — Header obrigatório
   `X-Internal-Service-Token`, comparado (via `hash_equals`) contra
   `INTERNAL_SERVICE_TOKEN` definido no `.env`. Ausente ou inválido → `403`.

O endpoint exige: IP confiável + token de serviço válido + Sanctum token de
usuário `tipo = CONSELHO`. As falhas retornam `403` genérico, sem indicar
qual camada falhou. Nos testes automatizados, simular interno/externo
manipulando o IP remoto e os headers da requisição de teste.

---

## Endpoints obrigatórios

### 1. `POST /api/login`
Autentica qualquer usuário (Reino ou Conselho). Retorna token Sanctum + tipo.

### 2. `POST /api/expedicoes`
- Autenticado, exige `EnsureUserIsReino`
- Cria expedição (jornada + participantes + itens em uma única requisição)
- Valida via Form Request
- Gera protocolo único
- Retorna protocolo com status `201`

### 3. `GET /api/expedicoes/{protocolo}`
- Consulta pública
- Retorna dados completos + status + última atualização + motivo de
  rejeição (se houver)
- `404` se protocolo não existir

### 4. `PATCH /api/expedicoes/{protocolo}/decisao`
- Endpoint interno + `EnsureUserIsConselho`
- Body: `decisao` (`AUTORIZADA` \| `REJEITADA`), `motivo` (obrigatório se
  `REJEITADA`)
- Bloqueia alteração se expedição já finalizada (`409`)
- Registra `decidido_por` e `decidido_em`

### 5. `GET /api/expedicoes/{protocolo}/status`
- Retorna status atual, timestamps relevantes e motivo de rejeição, quando
  houver

---

## Requisitos técnicos

- Form Requests para toda validação (nunca validar dentro do controller),
  incluindo a regra condicional `codigo` obrigatório/nulo conforme `tipo`
  no cadastro de usuário
- API Resources para formatar respostas JSON (nunca expor `password`)
- Respostas de erro JSON padronizadas (`404`, `422`, `401`, `403`, `409`)
- `README.md` com exemplos de `curl` para cada endpoint, incluindo login,
  simulação de requisição interna, e diferenças de acesso por tipo
- `.env.example` com todas as variáveis documentadas (placeholders, nunca
  valores reais)
- Seeder com:
  - 2 usuários `tipo = REINO`
  - 1 usuário `tipo = CONSELHO`
  - 2 expedições completas de exemplo (uma `PENDENTE`, uma já decidida)
- Collection do Postman (`.json`) com todos os endpoints, variáveis de
  ambiente (`base_url`, tokens, protocolo) e bodies de exemplo, incluindo
  fluxo de login para cada tipo

---

## Testes automatizados (Feature tests)

- Login: sucesso para `REINO` e para `CONSELHO`; falha com credenciais
  inválidas
- Criar expedição: sucesso (usuário `REINO`); bloqueado para usuário
  `CONSELHO`; falhas de validação
- Consultar por protocolo: existente e inexistente (`404`)
- Decisão bloqueada por IP não confiável → `403`
- Decisão bloqueada por token de serviço ausente/inválido → `403`
- Decisão bloqueada para usuário `tipo = REINO` tentando decidir → `403`
- Autorizar com sucesso (todas as camadas válidas, usuário `CONSELHO`)
- Rejeitar sem motivo → `422`
- Rejeitar com motivo → sucesso
- Tentar decidir expedição já finalizada → `409`
- Consultar status: pendente, autorizada e rejeitada

---

## Ordem de execução

Seguir uma etapa por vez, pausando para revisão onde indicado:

1. Remover camada de frontend
2. Configurar `.env` (Postgres) e `phpunit.xml` (SQLite in-memory para testes)
3. Criar migrations conforme schema acima — **parar e mostrar o conteúdo
   das migrations antes de rodar `migrate`**
4. *(após aprovação)* Rodar migrations e criar `Model Usuario` + `Expedicao`
   + demais, com relacionamentos e scopes
5. Criar middlewares `EnsureUserIsReino`, `EnsureUserIsConselho`,
   `EnsureRequestIsInternal`, e configurar `.env.example`
6. Criar endpoint de login
7. Criar seeders
8. Endpoint de criação de expedição + Form Request + geração de protocolo
   + testes
9. Endpoint de consulta por protocolo + testes
10. Endpoint de decisão do conselho (3 camadas + tipo `CONSELHO`) + testes
11. Endpoint de status + testes
12. README com exemplos de uso
13. Gerar collection do Postman
