# Carteira Financeira

Carteira digital com depósito, transferência, reversão e extrato. Possui idempotência nas operações e envio de email quando o saldo chega a zero.

## Funcionalidades

- Autenticação e perfil com Laravel Breeze
- Depósito e transferência entre usuários
- Reversão de transação
- Extrato paginado com saldo atual
- Idempotência via `idempotency_key`
- Email quando o saldo chega a zero

## Requisitos

- Docker e Docker Compose

## Como rodar

1) Copie o arquivo de ambiente

```bash
cp .env.example .env
```

2) Suba os containers

```bash
docker compose up -d --build
```

3) Gere a chave

```bash
docker compose exec app php artisan key:generate
```

4) Rode as migrations

```bash
docker compose exec app php artisan migrate
```

5) Rode as seeders (usuários de teste)

```bash
docker compose exec app php artisan db:seed
```

6) Build dos assets (opcional para dev, recomendado para primeira execução)

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

Aplicação: http://localhost:8000

## Email sandbox (Mailpit)

O projeto usa um sandbox de email local. Verifique se as variáveis abaixo estão no `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

Interface do Mailpit: http://localhost:8025

## Seeder (usuários de teste)

O UserSeeder cria 3 usuários com a mesma senha para facilitar o login inicial.

Senha: `12345678`

| Usuário | Email |
| --- | --- |
| Teste Usuario 1 | teste1@example.com |
| Teste Usuario 2 | teste2@example.com |
| Teste Usuario 3 | teste3@example.com |

## Observer de saldo zero

Quando o saldo de uma carteira muda e chega exatamente a `0.00`, o observer envia um email
para o usuário da carteira usando o Mailpit. Ele não dispara se o saldo já estava em zero.

## Validações

As entradas são validadas por `FormRequest` em `app/Http/Requests`:

- Depósito/saque: `amount` no formato decimal (aceita vírgula) e `idempotency_key` opcional
- Transferência: `to_user_id` ou `to_email`, `amount` e `idempotency_key` opcional
- Reversão: `transaction_id` e `idempotency_key` opcional

## Docker

### Dockerfile

- Base `php:8.3-fpm-alpine` para rodar o PHP-FPM leve
- Instala apenas dependências do sistema necessárias (zip/libzip e oniguruma)
- Habilita extensões PHP usadas pelo projeto (pdo_mysql, mbstring, bcmath, zip)
- Copia o Composer do container oficial para instalar dependências PHP
- Copia o código da aplicação para a imagem e roda `composer install`
- Cria o usuário `laravel` e ajusta permissões do `/var/www`
- Define o usuário padrão do container como `laravel`

### docker-compose.yml

- `app`: container PHP-FPM da aplicacao, com bind mount do codigo e volume para `vendor`
- `nginx`: servidor web que expõe a aplicação na porta 8000
- `mysql`: banco de dados MySQL 8.0 com volume persistente
- `mailpit`: sandbox de email para visualizar mensagens
- `volumes`: `dbdata` persiste o MySQL, `vendor` evita sobrescrever as deps do Composer
- `network`: rede bridge `finance` para comunicação entre os containers

## Testes

```bash
docker compose exec app php artisan test
```

### O que está coberto

- Feature: controllers de carteira e transações, fluxo de perfil, observer de saldo zero
- Feature: serviços de transação e carteira (depósito, transferência, reversão)
- Unit: normalizador de valores - Amount
