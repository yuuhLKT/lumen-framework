# Configuracao, .env e helpers

> A secao de autenticacao abaixo so se aplica se o projeto foi gerado com Mini Auth.

## Bootstrap

`bootstrap/app.php` faz quatro coisas:

- registra o autoload do namespace `App\`;
- carrega `utils/helpers.php`;
- carrega `.env` se existir;
- define o timezone usando `config/config.php`.

## .env

O arquivo `.env` deve ficar na raiz de `src`.

Exemplo:

```env
APP_TIMEZONE=America/Sao_Paulo
APP_DEBUG=false
DEV_BEARER_TOKEN=dev-token
DB_CONNECTION=json
DB_JSON_PATH=storage/database.json
DB_SQLITE_PATH=storage/database.sqlite
DB_MYSQL_HOST=127.0.0.1
DB_MYSQL_PORT=3306
DB_MYSQL_DATABASE=lumen
DB_MYSQL_USERNAME=root
DB_MYSQL_PASSWORD=
DB_MYSQL_CHARSET=utf8mb4
DB_PGSQL_HOST=127.0.0.1
DB_PGSQL_PORT=5432
DB_PGSQL_DATABASE=lumen
DB_PGSQL_USERNAME=postgres
DB_PGSQL_PASSWORD=
```

Existe um exemplo em `.env.example`.

O `.env` tem prioridade sobre variaveis ja exportadas no sistema. Se uma chave existir nos dois lugares, o valor do `.env` fica em `$_ENV`, `$_SERVER` e `getenv()`.

## Configuracoes gerais

Arquivo: `config/config.php`.

```php
return [
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
];
```

Por padrao, usa `America/Sao_Paulo`.
`APP_DEBUG=true` faz erros internos retornarem `trace` na resposta JSON.

## Configuracoes de banco

Arquivo: `config/datalumen.php`.

Variaveis gerais:

- `DB_CONNECTION`: `json`, `sqlite`, `mysql`, `pgsql` ou `postgres`.
- `DB_JSON_PATH`: caminho do arquivo JSON.
- `DB_SQLITE_PATH`: caminho do arquivo SQLite.

Caminhos relativos sao resolvidos a partir da raiz de `src`.

Variaveis MySQL:

- `DB_MYSQL_HOST`
- `DB_MYSQL_PORT`
- `DB_MYSQL_DATABASE`
- `DB_MYSQL_USERNAME`
- `DB_MYSQL_PASSWORD`
- `DB_MYSQL_CHARSET`

Variaveis PostgreSQL:

- `DB_PGSQL_HOST`
- `DB_PGSQL_PORT`
- `DB_PGSQL_DATABASE`
- `DB_PGSQL_USERNAME`
- `DB_PGSQL_PASSWORD`

## Configuracoes de autenticacao

Arquivo: `config/auth.php`.

Variaveis:

- `DEV_BEARER_TOKEN`: token único para desenvolvimento ou desafio simples.

Exemplo:

```env
DEV_BEARER_TOKEN=dev-token
```

O token não vazio é aceito nas rotas protegidas com `->auth()`.

## Helpers disponiveis

### dd

Dumpa valores em `<pre>` e encerra a execucao. Use apenas para debug local.

```php
dd($request->input());
```

### env

Busca uma variavel em `$_ENV`, `$_SERVER` ou `getenv()`.

```php
$connection = env('DB_CONNECTION', 'json');
```

### load_env_file

Carrega um arquivo `.env` simples com linhas `CHAVE=valor`.

```php
load_env_file(base_path('.env'));
```

Regras:

- ignora linhas vazias;
- ignora linhas iniciadas por `#`;
- exige `=`;
- remove aspas simples ou duplas ao redor do valor;
- sobrescreve variaveis ja existentes quando a mesma chave estiver no `.env`.

### base_path

Retorna a raiz de `src` ou um caminho dentro dela.

```php
base_path();
base_path('storage/database.json');
```

### path_from_base

Converte caminho relativo para caminho dentro da raiz de `src` e preserva caminhos absolutos.

```php
path_from_base('storage/database.json');
path_from_base('C:\\temp\\database.json');
```

### db

Retorna uma conexao de banco.

```php
db()->table('users');
db('sqlite')->table('users');
db('mysql')->table('users');
db('pgsql')->table('users');
```
