# Guia rapido: criar recurso CRUD

Este guia cria um CRUD simples de usuarios usando controller, validacao e repository.

## 1. Criar repository

Arquivo: `app/Repositories/UserRepository.php`.

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';
}
```

## 2. Criar controller

Arquivo: `app/Controllers/UserController.php`.

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use App\Support\HttpStatus;

final class UserController extends Controller
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function index(Request $request, array $params): Response
    {
        return $this->ok($this->users->findAll());
    }

    public function show(Request $request, array $params): Response
    {
        $user = $this->users->find($params['id']);

        if ($user === null) {
            $this->abort(HttpStatus::NOT_FOUND, 'Usuario nao encontrado.');
        }

        return $this->ok($user);
    }

    public function store(Request $request, array $params): Response
    {
        $data = $this->validate($request, [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email',
        ]);

        if ($this->users->exists('email', $data['email'])) {
            $this->abort(HttpStatus::CONFLICT, 'Email ja cadastrado.');
        }

        return $this->created($this->users->insert($data));
    }

    public function update(Request $request, array $params): Response
    {
        $data = $this->validate($request, [
            'name' => 'nullable|string|min:3|max:255',
            'email' => 'nullable|email',
        ]);

        $updated = $this->users->update($params['id'], $data);

        if ($updated === null) {
            $this->abort(HttpStatus::NOT_FOUND, 'Usuario nao encontrado.');
        }

        return $this->ok($updated);
    }

    public function destroy(Request $request, array $params): Response
    {
        if (!$this->users->delete($params['id'])) {
            $this->abort(HttpStatus::NOT_FOUND, 'Usuario nao encontrado.');
        }

        return $this->noContent();
    }
}
```

## 3. Registrar rotas

Arquivo: `routes/web.php`.

```php
use App\Controllers\UserController;

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);
```

## 4. Testar

Criar:

```bash
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Yuri","email":"yuri@example.com"}'
```

Listar:

```bash
curl http://localhost:8000/users
```

Buscar:

```bash
curl http://localhost:8000/users/1
```

Atualizar:

```bash
curl -X PUT http://localhost:8000/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Yuri Luiz"}'
```

Excluir:

```bash
curl -X DELETE http://localhost:8000/users/1
```

## 5. Onde evoluir

- Crie um service se regras como `email ja cadastrado` crescerem.
- Crie um DTO se o array validado for usado em mais de uma camada.
- Troque `DB_CONNECTION=sqlite` se quiser um arquivo de banco separado do JSON.
