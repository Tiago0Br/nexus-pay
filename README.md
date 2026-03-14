# NexusPay

API REST para gerenciamento de pagamentos multi-gateway, construída com Laravel 12. O sistema realiza cobranças através de múltiplos gateways de pagamento com fallback automático: caso o gateway de maior prioridade falhe, a cobrança é tentada automaticamente no próximo da fila.

---

## Requisitos

- PHP 8.4+
- Composer
- Docker e Docker Compose
- Node.js e npm

---

## Instalação e Execução

### 1. Clone o repositório e acesse a pasta

```bash
git clone <url-do-repositorio>
cd nexus-pay
```

### 2. Suba os serviços de infraestrutura

O Docker Compose provê o banco de dados MySQL e os mocks dos dois gateways de pagamento.

```bash
docker-compose up -d
```

Serviços iniciados:
- **MySQL 8.0** na porta `3306` (banco: `nexus_pay`, usuário: `betalent_user`)
- **Mock Gateway 1** na porta `3001` (requer autenticação via token)
- **Mock Gateway 2** na porta `3002` (autenticação via headers fixos)

### 3. Configure o ambiente

```bash
cp .env.example .env
```

Edite o `.env` e preencha as variáveis dos gateways com os valores dos mocks:

```env
GATEWAY1_BASE_URL=http://localhost:3001
GATEWAY1_AUTH_TOKEN=

GATEWAY2_BASE_URL=http://localhost:3002
GATEWAY2_AUTH_TOKEN=
GATEWAY2_AUTH_SECRET=
```

### 4. Instale as dependências e inicialize o projeto

```bash
composer install
php artisan key:generate
php artisan migrate --seed
```

O seed cria:
- Usuário padrão: `dev@betalent.tech` / `password123`
- Gateway 1 (prioridade 1) e Gateway 2 (prioridade 2), ambos ativos
- 1 produto: *Teclado Mecânico Keychron* — R$ 450,00
- 1 cliente de teste

### 5. Inicie o servidor

```bash
composer dev
# ou
php artisan serve
```

A API estará disponível em `http://localhost:8000`.

---

## Autenticação

A API usa **Laravel Sanctum** com tokens Bearer. O token é obtido via `POST /login` e deve ser enviado no header de todas as rotas protegidas:

```
Authorization: Bearer {token}
```

As únicas rotas públicas são `POST /login` e `POST /transactions`.

---

## Rotas da API

### Autenticação

#### `POST /login`
Autentica o usuário e retorna o token de acesso.

**Body:**
```json
{
  "email": "dev@betalent.tech",
  "password": "password123"
}
```

**Resposta (200):**
```json
{
  "token": "1|abc123..."
}
```

---

### Transações

#### `POST /transactions` *(pública)*
Realiza uma cobrança. O valor total é calculado no servidor (`preço do produto × quantidade`). O cliente é criado automaticamente caso não exista.

**Body:**
```json
{
  "client_name": "João Silva",
  "client_email": "joao@email.com",
  "product_id": 1,
  "quantity": 2,
  "cardNumber": "4111111111111111",
  "cvv": "123"
}
```

**Resposta (201):**
```json
{
  "message": "Pagamento aprovado com sucesso!",
  "transaction_id": 1,
  "gateway_used": 1,
  "status": "SUCCESS"
}
```

**Resposta de falha (402):** retornada quando todos os gateways recusam a cobrança.

---

#### `GET /transactions` 🔒
Lista todas as transações.

#### `GET /transactions/{id}` 🔒
Retorna os detalhes de uma transação, incluindo cliente e produtos.

#### `PATCH /transactions/{id}/charge_back` 🔒
Processa o reembolso de uma transação. Retorna erro `400` caso a transação já tenha sido reembolsada anteriormente.

**Resposta (200):**
```json
{
  "message": "Reembolso realizado com sucesso!",
  "transaction_id": 1,
  "status": "CHARGED_BACK"
}
```

---

### Gateways

#### `GET /gateways` 🔒
Lista todos os gateways cadastrados com seus status e prioridades.

#### `PATCH /gateways/{id}/toggle-active` 🔒
Ativa ou desativa um gateway.

#### `PATCH /gateways/{id}/priority` 🔒
Atualiza a prioridade de um gateway. Gateways com prioridade menor são acionados primeiro.

**Body:**
```json
{
  "priority": 1
}
```

---

### Produtos

#### `GET /products` 🔒
Lista todos os produtos.

#### `GET /products/{id}` 🔒
Retorna um produto específico.

#### `POST /products` 🔒
Cria um novo produto. O `amount` é em centavos (ex: `45000` = R$ 450,00).

**Body:**
```json
{
  "name": "Teclado Mecânico",
  "amount": 45000
}
```

#### `PUT /products/{id}` 🔒
Atualiza um produto.

#### `DELETE /products/{id}` 🔒
Remove um produto.

---

### Clientes

#### `GET /clients` 🔒
Lista todos os clientes.

#### `GET /clients/{id}` 🔒
Retorna um cliente e suas transações.

---

### Usuários

#### `GET /users` 🔒
Lista todos os usuários.

#### `GET /users/{id}` 🔒
Retorna um usuário específico.

#### `POST /users` 🔒
Cria um novo usuário.

**Body:**
```json
{
  "name": "Novo Usuário",
  "email": "novo@email.com",
  "password": "senha123",
  "role": "ADMIN"
}
```

#### `PUT /users/{id}` 🔒
Atualiza um usuário.

#### `DELETE /users/{id}` 🔒
Remove um usuário.

---

## Arquitetura Multi-Gateway

O núcleo do sistema é o `PaymentOrchestrator`, que coordena o fluxo de pagamento:

1. Busca todos os gateways **ativos**, ordenados por **prioridade**
2. Tenta processar a cobrança no primeiro gateway
3. Se falhar (qualquer exceção), tenta o próximo automaticamente
4. Se todos falharem, lança exceção e a transação **não é salva**
5. Em caso de sucesso, persiste a transação dentro de uma **transaction de banco de dados**

Cada gateway implementa a interface `PaymentGatewayInterface`, garantindo contratos uniformes de `charge()` e `refund()`. Os dois gateways disponíveis têm mecanismos de autenticação diferentes:

| | Gateway 1 | Gateway 2 |
|---|---|---|
| Autenticação | Token Bearer obtido via login no mock | Headers fixos (`authorizationToken` + `authorizationSecret`) |
| Token cacheado | Sim (1 hora) | Não se aplica |

---

## Testes

Os testes usam **Pest PHP** com banco SQLite em memória e `Http::fake()` para simular os gateways externos.

```bash
# Rodar todos os testes
composer test

# Rodar um arquivo específico
php artisan test tests/Feature/TransactionFeatureTest.php

# Rodar um teste específico pelo nome
php artisan test --filter="nome do teste"
```

---

## Possíveis Melhorias

- **Suporte a múltiplos produtos por transação** — atualmente cada transação aceita apenas um `product_id`.
- **Registro dinâmico de gateways** — novos gateways exigem alteração no código do `PaymentOrchestrator`. Isso poderia ser resolvido com um driver registry ou injeção via container.
- **Paginação nas listagens** — rotas como `GET /transactions` e `GET /clients` retornam todos os registros sem paginação.
- **Controle de permissões por role** — o campo `role` existe no modelo `User`, mas não há middleware de autorização diferenciando permissões por perfil.
- **Webhook de notificação** — notificar sistemas externos sobre mudanças de status de transações.
- **Retry com backoff** — implementar tentativas com espera entre falhas de gateway antes de acionar o fallback.
