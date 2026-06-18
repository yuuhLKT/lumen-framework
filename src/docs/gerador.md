# Gerador de projetos

O arquivo `base.sh` cria um novo projeto copiando o conteudo de `src/` para uma pasta no mesmo nivel de `base`.

```bash
./base.sh
```

Ele pergunta:

- nome do novo projeto;
- se o projeto deve incluir Mini Auth.

## O que nao e copiado

O gerador sempre remove itens que pertencem ao desenvolvimento da propria base:

- `.env`;
- `AGENTS.md`;
- `tests/`;
- `vendor/`;
- `composer.lock`;
- caches de ferramentas;
- bancos SQLite locais em `storage/`;
- `coverage/`.

Os comandos de testes e qualidade continuam no projeto gerado. A ideia e que cada projeto crie seus proprios testes quando precisar.

## Projeto com Mini Auth

Ao responder `s`, `sim`, `y` ou `yes`, o projeto mantem:

- rotas `/auth/register`, `/auth/login`, `/auth/me` e `/auth/logout`;
- `AuthController`, `AuthService`, repositories de usuario/token e `AuthMiddleware`;
- `config/auth.php` e `DEV_BEARER_TOKEN`;
- migration inicial com `users` e `auth_tokens`;
- documentacao de autenticacao.

## Projeto sem Mini Auth

Ao deixar em branco ou responder qualquer outro valor, o gerador remove os arquivos especificos de Auth e deixa apenas a rota `/health` registrada.

O metodo `Router::auth()` continua existindo, mas lanca uma `LogicException` com mensagem clara. Isso evita erro silencioso quando alguem cola um exemplo com `->auth()` em um projeto criado sem Auth.

Tambem sao removidos:

- `DEV_BEARER_TOKEN` de `.env.example`, `.env.docker.example` e `.env`;
- variavel `DEV_BEARER_TOKEN` do `docker-compose.yml`;
- `autoload-dev` apontando para `tests/` no `composer.json`;
- documentacao e indice de Mini Auth.

## Depois de gerar

Entre no projeto criado e rode o fluxo normal:

```bash
make deps
make up
```

Ou, para rodar localmente:

```bash
make local
curl http://localhost:8000/health
```
