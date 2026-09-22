Implemente expiração de tokens Sanctum, logout e revogação de tokens antigos 
na API do Conselho de Elrond.

## 1. Expiração diferenciada por tipo de usuário

No endpoint de login (POST /api/login), ao criar o token, defina o tempo de 
expiração conforme o tipo do usuário:
- tipo = REINO: expira em 8 horas
- tipo = CONSELHO: expira em 2 horas (perfil mais sensível, decide expedições)

Verifique a versão do laravel/sanctum instalada (composer.json) e confirme 
se createToken() suporta o parâmetro de expiração diretamente. Se a versão 
não suportar, implemente a expiração manualmente: grave um campo expires_at 
na tabela personal_access_tokens (crie migration se necessário) e adicione 
um middleware ou override no guard do Sanctum que valide esse campo e recuse 
(401) tokens vencidos.

Documente os dois tempos de expiração no .env.example como variáveis 
configuráveis:
SANCTUM_TOKEN_EXPIRATION_REINO=480
SANCTUM_TOKEN_EXPIRATION_CONSELHO=120
(valores em minutos)

## 2. Revogação de tokens antigos no login

Antes de criar um novo token no login, revogue todos os tokens ativos 
anteriores daquele usuário (via $usuario->tokens()->delete()), garantindo 
que cada login gere uma sessão única e não acumule tokens esquecidos.

## 3. Endpoint de logout

Crie POST /api/logout (autenticado via Sanctum, qualquer tipo de usuário):
- Revoga apenas o token atual da requisição 
  ($request->user()->currentAccessToken()->delete())
- Retorna 204 (No Content) em caso de sucesso

## 4. Testes automatizados (Feature tests)

- Login como REINO gera token com expiração de 8h
- Login como CONSELHO gera token com expiração de 2h
- Requisição com token expirado retorna 401
- Novo login revoga token anterior do mesmo usuário (token antigo passa a 
  retornar 401)
- Logout revoga o token atual (requisição subsequente com o mesmo token 
  retorna 401)
- Logout sem autenticação retorna 401

## 5. Atualizar documentação

- Atualize o README.md: seção de autenticação explicando os tempos de 
  expiração por tipo, o comportamento de revogação no login, e o novo 
  endpoint de logout
- Atualize docs/openapi.json: adicione POST /api/logout com sua resposta 
  204, e documente no securitySchemes ou na description do bearerAuth os 
  tempos de expiração diferenciados
- Atualize a collection do Postman: adicione a requisição de logout, e um 
  teste/script que capture o token retornado no login automaticamente na 
  variável de ambiente (se ainda não fizer isso)

Rode os testes ao final e me avise se algo do fluxo atual de autenticação 
quebrou com essa mudança (ex: testes antigos que assumiam token sem expiração).
