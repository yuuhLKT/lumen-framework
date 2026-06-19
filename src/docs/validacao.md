# Validacao

O validador fica em `App\Validation\Validator` e tambem pode ser usado pelo atalho `$this->validate()` do controller lumen.

## Uso no controller

```php
$data = $this->validate($request, [
    'name' => 'required|string|min:3|max:255',
    'email' => 'required|email',
    'website' => 'nullable|url',
]);
```

O retorno contem apenas os campos validados pelas regras.

## Uso direto

```php
use App\Validation\Validator;

$data = Validator::validate($request->input(), [
    'name' => ['required', 'string', 'min:3'],
    'email' => 'required|email',
]);
```

As regras podem ser string separada por `|` ou array de strings.

## Regras disponiveis

```text
required
nullable
string
integer
numeric
boolean
array
email
url
min:N
max:N
in:a,b,c
```

## Comportamento das regras

- `required`: campo precisa existir e nao pode ser `null`, string vazia ou array vazio.
- `nullable`: se o campo nao for obrigatorio e estiver vazio, o valor validado sera `null`.
- `string`: exige `is_string()`.
- `integer`: usa `FILTER_VALIDATE_INT`.
- `numeric`: usa `is_numeric()`.
- `boolean`: aceita `true`, `false`, `0`, `1`, `'0'` e `'1'`.
- `array`: exige `is_array()`.
- `email`: usa `FILTER_VALIDATE_EMAIL`.
- `url`: usa `FILTER_VALIDATE_URL`.
- `min:N`: para strings, exige pelo menos `N` caracteres.
- `max:N`: para strings, exige no maximo `N` caracteres.
- `in:a,b,c`: compara o valor como string contra a lista permitida.

## Erro de validacao

Se falhar, o validador lanca `ValidationException`. O `ErrorHandler` transforma isso em JSON com HTTP `422`.

Exemplo:

```json
{
  "error": "Dados invalidos.",
  "errors": {
    "email": ["O campo deve ser um email valido."]
  }
}
```

## Exemplo completo

```php
public function store(Request $request, array $params): Response
{
    $data = $this->validate($request, [
        'name' => 'required|string|min:3|max:255',
        'email' => 'required|email',
        'role' => 'nullable|in:admin,user',
    ]);

    $created = db()->table('users')->insert($data);

    return $this->created($created);
}
```
