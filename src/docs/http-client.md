# HTTP client

A base possui um client HTTP simples para chamadas externas sem depender de bibliotecas.

## Uso

```php
use App\Http\NativeHttpClient;

$client = new NativeHttpClient();

$response = $client->get('https://example.com/api');

if ($response->successful()) {
    $data = $response->json();
}
```

POST JSON:

```php
$response = $client->post('https://example.com/api', [
    'name' => 'Base',
]);
```

## Testes

Use `FakeHttpClient` para testar services que dependem de API externa:

```php
use App\Http\FakeHttpClient;
use App\Http\HttpResponse;

$client = new FakeHttpClient(new HttpResponse(200, '{"ok":true}'));

$response = $client->post('https://example.com/api', ['id' => 1]);

$requests = $client->requests();
```

## Observacoes

- O client usa `file_get_contents` com `stream_context_create`.
- O timeout padrao e 5 segundos.
- Para projetos maiores, voce pode trocar a implementacao mantendo a interface `HttpClient`.
