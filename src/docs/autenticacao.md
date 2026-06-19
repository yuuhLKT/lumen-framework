# Mini Auth

A lumen possui um Mini Auth real com usuarios, senha com hash e tokens Bearer persistidos. Ele foi feito para estudos, APIs pequenas e desafios backend sem adicionar JWT, sessao ou framework.

## Como funciona

- Usuarios ficam na tabela `users`.
- Tokens ficam na tabela `auth_tokens`.
- Senhas sao salvas com `password_hash()`.
- O token retornado no login/cadastro e aleatorio, opaco e exibido uma unica vez.
- No banco fica apenas o hash SHA-256 do token.
- Rotas protegidas usam `Authorization: Bearer <token>`.
- `Request::user()` retorna o usuario autenticado, sem `password_hash`.

## Rotas prontas

```text
POST /auth/register
POST /auth/login
GET  /auth/me
POST /auth/logout
```

`/auth/me` e `/auth/logout` sao protegidas com `->auth()`.

## Cadastrar usuario

```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Yuri Luiz","email":"yuri@example.com","password":"password123"}'
```

Resposta:

```json
{
  "token_type": "Bearer",
  "access_token": "token-gerado",
  "user": {
    "id": 1,
    "name": "Yuri Luiz",
    "email": "yuri@example.com",
    "created_at": "2026-01-01T10:00:00-03:00",
    "updated_at": "2026-01-01T10:00:00-03:00"
  }
}
```

## Login

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"yuri@example.com","password":"password123"}'
```

Cada login gera um novo token.

## Buscar usuario autenticado

```bash
curl http://localhost:8000/auth/me \
  -H "Authorization: Bearer token-gerado"
```

Resposta:

```json
{
  "user": {
    "id": 1,
    "name": "Yuri Luiz",
    "email": "yuri@example.com",
    "created_at": "2026-01-01T10:00:00-03:00",
    "updated_at": "2026-01-01T10:00:00-03:00"
  }
}
```

## Logout

```bash
curl -X POST http://localhost:8000/auth/logout \
  -H "Authorization: Bearer token-gerado"
```

Resposta: HTTP `204 No Content`.

O token usado na requisicao e revogado e nao autentica mais.

## Proteger rotas

Use `->auth()` logo depois da rota:

```php
$router->get('/profile', [ProfileController::class, 'show'])->auth();
```

Dentro do controller:

```php
public function show(Request $request, array $params): Response
{
    return $this->ok([
        'user' => $request->user(),
    ]);
}
```

Rotas sem `->auth()` continuam publicas.

## Regras de validacao prontas

Cadastro:

```text
name: required|string|min:3|max:255
email: required|email
password: required|string|min:8|max:255
```

Login:

```text
email: required|email
password: required|string
```

## Erros comuns

Token ausente ou invalido:

```json
{
  "error": "Token de autenticacao invalido ou ausente."
}
```

Credenciais invalidas:

```json
{
  "error": "Credenciais invalidas."
}
```

Email duplicado:

```json
{
  "error": "Email ja cadastrado."
}
```

## Classes principais

- `App\Controllers\AuthController`: endpoints HTTP de auth.
- `App\Services\AuthService`: regra de cadastro, login, busca por token e logout.
- `App\Repositories\UserRepository`: acesso a usuarios.
- `App\Repositories\AuthTokenRepository`: acesso aos tokens.
- `App\Core\Auth`: integracao com `Router::auth()`.
- `App\Core\Request::user()`: usuario autenticado da requisicao.

## Token fixo opcional

A lumen ainda aceita um token fixo no `.env` para scripts internos ou testes muito simples:

```env
DEV_BEARER_TOKEN=dev-token
```

Esse token passa no `->auth()`, mas não representa um usuário. Portanto, `Request::user()` retorna `null` quando a autenticação veio de token fixo.

Para APIs com usuario autenticado, prefira sempre `/auth/register` ou `/auth/login`.

## Cuidados

- Use HTTPS em qualquer ambiente fora da maquina local.
- Nao salve tokens reais em README, prints ou commits.
- O token bruto so aparece na resposta de cadastro/login.
- O banco guarda apenas `token_hash`.
- Para projetos maiores, considere expiracao de tokens, refresh tokens, permissoes, roles e rate limit de login.
