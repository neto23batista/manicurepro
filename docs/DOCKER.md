# Docker / Laravel Sail (desenvolvimento local)

Stack opcional via [Laravel Sail](https://laravel.com/docs/sail): app PHP 8.3, MySQL 8.4, Redis e Mailpit.

O fluxo **sem Docker** (`composer install` + MySQL local + `php artisan serve`) continua válido. `.env.example` e `phpunit.xml` não foram alterados para Sail.

## Pré-requisitos

- Docker Desktop (no Windows: com **WSL2**)
- `composer install` (precisa de `vendor/laravel/sail` para o build da imagem)

## Configurar `.env` só quando usar Sail

Ajuste (ou copie de um `.env` local) estes hosts para os **nomes dos serviços** Compose:

```env
APP_URL=http://localhost
APP_PORT=80

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=manicurepro
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

Para voltar ao fluxo nativo, use de novo `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1` e `MAIL_MAILER=log` (como em `.env.example`).

Mailpit UI: http://localhost:8025

## Comandos

```bash
# Subir (WSL / Linux / macOS)
./vendor/bin/sail up -d

# App
./vendor/bin/sail artisan key:generate   # se APP_KEY vazia
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev

# Parar
./vendor/bin/sail down
```

Acesse http://localhost (porta `APP_PORT`, padrão 80). Vite: porta `5173`.

Alternativa sem o wrapper Sail: `docker compose up -d` (mesmas variáveis de ambiente no `.env`).

## Notas

- Imagem da app: `./vendor/laravel/sail/runtimes/8.3` — rode `composer install` antes do primeiro `up`.
- Volumes `sail-mysql` / `sail-redis` persistem dados entre restarts.
- Testes Pest/PHPUnit seguem em SQLite `:memory:` (`phpunit.xml`); não dependem do MySQL do Compose.
- Mailpit é opcional no sentido de uso: o serviço sobe com o stack; sem ele, mantenha `MAIL_MAILER=log`.
