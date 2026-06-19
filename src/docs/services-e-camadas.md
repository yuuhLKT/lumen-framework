# Services e organizacao de camadas

A lumen nao tem uma classe `Service` obrigatoria. A ideia e usar services como uma convencao simples quando a regra de negocio comecar a deixar o controller grande.

## Quando criar um service

Crie um service quando uma acao envolver:

- mais de uma regra de negocio;
- validacoes que nao sao apenas formato de input;
- acesso a mais de um repository;
- decisoes que voce quer testar ou reutilizar;
- controller ficando dificil de ler.

Nao precisa criar service para uma rota muito pequena que apenas valida e salva dados.

## Exemplo

Arquivo: `app/Services/UserService.php`.

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\HttpException;
use App\Repositories\UserRepository;
use App\Support\HttpStatus;

final class UserService
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): array
    {
        if ($this->users->exists('email', $data['email'])) {
            throw new HttpException('Email ja cadastrado.', HttpStatus::CONFLICT);
        }

        return $this->users->insert($data);
    }
}
```

Controller usando o service:

```php
use App\Services\UserService;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $service = new UserService())
    {
    }

    public function store(Request $request, array $params): Response
    {
        $data = $this->validate($request, [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email',
        ]);

        return $this->created($this->service->create($data));
    }
}
```

## Responsabilidade de cada camada

- `Controller`: recebe request, chama validacao, chama service ou repository e retorna response.
- `Validator`: valida formato dos dados externos.
- `DTO`: organiza dados quando arrays ficarem confusos.
- `Service`: executa regra de negocio.
- `Repository`: acessa uma tabela.
- `Client`: encapsula comunicacao com APIs externas.
- `Database`: implementa persistencia em JSON, SQLite, MySQL ou PostgreSQL.

## Regra pratica

Comece simples:

```php
Controller -> db()->table()
```

Quando repetir acesso a tabela:

```php
Controller -> Repository
```

Quando aparecer regra de negocio:

```php
Controller -> Service -> Repository
```

Quando arrays ficarem pouco claros:

```php
Controller -> DTO -> Service -> Repository
```
