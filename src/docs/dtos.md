# DTOs

DTO significa Data Transfer Object. Use quando quiser transformar arrays de input em objetos com propriedades conhecidas.

## BaseDTO

Arquivo: `app/DTO/BaseDTO.php`.

Ele oferece:

- `fromArray(array $data): static`
- `toArray(): array`

## Criar um DTO

```php
<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class CreateUserDTO extends BaseDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $website = null,
    ) {
    }
}
```

## fromArray

```php
$dto = CreateUserDTO::fromArray([
    'name' => 'Yuri',
    'email' => 'yuri@example.com',
    'website' => 'https://example.com',
]);
```

Como funciona:

- Olha os parametros do construtor via Reflection.
- Para cada parametro, busca uma chave com o mesmo nome no array.
- Se a chave existir, usa o valor.
- Se nao existir e o parametro tiver valor padrao, usa o padrao.
- Se nao existir e nao tiver padrao, lanca `InvalidArgumentException`.

## toArray

```php
$data = $dto->toArray();
```

Como funciona:

- Le apenas propriedades publicas.
- Ignora propriedades nao inicializadas.
- Retorna array `nomeDaPropriedade => valor`.

## Uso com controller

```php
public function store(Request $request, array $params): Response
{
    $data = $this->validate($request, [
        'name' => 'required|string|min:3|max:255',
        'email' => 'required|email',
        'website' => 'nullable|url',
    ]);

    $dto = CreateUserDTO::fromArray($data);

    $created = db()->table('users')->insert($dto->toArray());

    return $this->created($created);
}
```

## Quando usar

Use DTO quando:

- o array passa por varias camadas;
- voce quer autocomplete nas propriedades;
- existe um contrato claro de entrada;
- o controller esta ficando cheio de arrays soltos.

Nao precisa usar DTO em exemplos muito pequenos. Um array validado ja pode ser suficiente.
