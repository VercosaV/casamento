# Convite de casamento (Victor & Kamilly)

Site em Laravel com convite, confirmação de presença (RSVP), lista de presentes com pagamento pelo Stripe e um painel para os noivos.
Login dos noivos por JWT guardado em cookie.

## Primeiros passos (em um computador novo)

```bash
git clone https://github.com/VercosaV/casamento.git && cd casamento
composer install
cp .env.example .env            # no Windows: copy .env.example .env
php artisan key:generate        # chave do Laravel (APP_KEY)
php artisan jwt:secret          # chave do login (JWT_SECRET). SEM ESTA, O LOGIN DÁ ERRO 500
```

Edite o `.env` com os dados do seu MySQL (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), crie o banco vazio e continue:

```bash
php artisan migrate
php artisan storage:link        # para as fotos enviadas pelo painel aparecerem
php artisan casamento:criar-noivo   # rode uma vez para cada noivo (pede nome, e-mail e senha)
php artisan casamento:verificar     # confere se ficou tudo certo
php artisan serve               # abre em http://localhost:8000
```

Depois de mexer no `.env`, rode `php artisan config:clear`.

Entre em `http://localhost:8000/login` com o e-mail e a senha que você cadastrou. O painel fica em `/painel`.

## Comandos do projeto

| Comando | Para quê |
|---|---|
| `php artisan casamento:criar-noivo` | cria o login de um noivo (rodar de novo com o mesmo e-mail troca a senha) |
| `php artisan casamento:verificar` | diagnóstico: chaves, banco, noivos, fotos, testes e Stripe |
| `php artisan casamento:apagar-dados --confirmo` | apaga os dados dos convidados depois do casamento (**exporte antes**) |
| `php artisan test` | roda os testes (usam SQLite em memória, não tocam no seu banco) |

## Problemas comuns

| O que aparece | Causa | Solução |
|---|---|---|
| Erro 500 no login, `SecretMissingException` | falta o `JWT_SECRET` no `.env` | `php artisan jwt:secret` e depois `php artisan config:clear`. Confira no `.env` que apareceu uma linha `JWT_SECRET=` com um texto longo |
| Erro 500 no login, `InvalidKeyProvided` | existe a linha `JWT_SECRET=` mas vazia | `php artisan jwt:secret --force` |
| "Credenciais inválidas" | e-mail ou senha errados, ou o noivo não foi criado | `php artisan casamento:criar-noivo` (refaz a senha) |
| `php artisan test`: `could not find driver` | extensão `pdo_sqlite` desligada | ative `extension=pdo_sqlite` no `php.ini` |
| Fotos enviadas não aparecem | falta o link do storage | `php artisan storage:link` |
| Pagamento não confirma sozinho | falta o webhook do Stripe | veja a seção Stripe |
| Qualquer coisa estranha | | `php artisan casamento:verificar` |

## Testes

`php artisan test` usa SQLite em memória (configurado no `phpunit.xml`). Se quiser rodar com MySQL, crie um banco **só para testes** (por exemplo `casamento_teste`): os testes apagam e recriam todas as tabelas dele. **Nunca aponte os testes para o seu banco de verdade.**

## Stripe (pagamento dos presentes)

Sem `STRIPE_SK` no `.env` o site funciona no modo "reservar presente", sem pagamento. Para ligar o pagamento em **modo de teste**:

1. Crie a conta em dashboard.stripe.com e copie a chave secreta de teste (`sk_test_...`) para `STRIPE_SK`.
2. Instale o Stripe CLI e rode `stripe listen --forward-to localhost:8000/stripe/webhook`. Copie o `whsec_...` que ele mostra para `STRIPE_WEBHOOK_SECRET`.
3. `php artisan config:clear` e faça um pagamento com o cartão de teste `4242 4242 4242 4242`.

Chaves ficam **só no `.env`** (ele não vai para o Git). Este repositório é público.

## Produção

No `.env` do servidor: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://seu-dominio`, `SESSION_SECURE_COOKIE=true`, chave `sk_live_...` e o webhook cadastrado no painel do Stripe para `https://seu-dominio/stripe/webhook`. Rode `php artisan casamento:verificar` no servidor antes de divulgar o convite.
