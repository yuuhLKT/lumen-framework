# Autenticacao Bearer Token

A base possui autenticacao simples por Bearer Token para proteger rotas sem adicionar framework, banco de usuarios ou JWT.

## Configurar token

No `.env`:

```env
AUTH_TOKEN=dev-token
```

Tambem e possivel aceitar varios tokens:

```env
AUTH_TOKENS=mobile-token,admin-token,external-token
```

As duas variaveis podem ser usadas juntas. Tokens vazios sao ignorados.

## Proteger uma rota

Use `->auth()` logo depois da rota:

```php
$router->get('/me', fn () => ['user' => 'admin'])->auth();
```

Com controller:

```php
use App\Controllers\ProductController;

$router->post('/products', [ProductController::class, 'store'])->auth();
$router->put('/products/{id}', [ProductController::class, 'update'])->auth();
$router->delete('/products/{id}', [ProductController::class, 'destroy'])->auth();
```

Rotas sem `->auth()` continuam publicas.

## Fazer requisicao autenticada

Envie o header `Authorization` no formato `Bearer token`:

```bash
curl http://localhost:8000/me -H "Authorization: Bearer dev-token"
```

Exemplo com JSON:

```bash
curl -X POST http://localhost:8000/products \
  -H "Authorization: Bearer dev-token" \
  -H "Content-Type: application/json" \
  -d '{"name":"Notebook"}'
```

## Resposta quando falhar

Token ausente ou invalido retorna HTTP `401`:

```json
{
  "error": "Token de autenticacao invalido ou ausente."
}
```

## Ler token manualmente

Normalmente voce nao precisa fazer isso, porque `->auth()` ja valida antes do controller. Se precisar, use:

```php
$token = $request->bearerToken();
```

Ou leia o header diretamente:

```php
$authorization = $request->header('Authorization');
```

## Como funciona internamente

- `Request::capture()` normaliza os headers da requisicao.
- `Request::bearerToken()` extrai o token do header `Authorization`.
- `Router::auth()` marca a ultima rota registrada como protegida.
- `Router::dispatch()` chama `Auth::check()` antes do handler de rotas protegidas.
- `Auth::check()` compara o token recebido com os tokens configurados em `config/auth.php` usando `hash_equals()`.

## Exemplo completo

`.env`:

```env
AUTH_TOKEN=dev-token
```

`routes/web.php`:

```php
$router->get('/public', fn () => ['public' => true]);
$router->get('/private', fn () => ['private' => true])->auth();
```

Teste publico:

```bash
curl http://localhost:8000/public
```

Teste protegido sem token:

```bash
curl http://localhost:8000/private
```

Teste protegido com token:

```bash
curl http://localhost:8000/private -H "Authorization: Bearer dev-token"
```

## Cuidados

- Bearer Token simples e bom para estudos, scripts internos e desafios pequenos.
- Em projeto real, use HTTPS sempre.
- Nao coloque token real em README, prints ou commits.
- Troque o token se ele for exposto.
