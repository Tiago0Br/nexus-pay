# 🗺️ Roadmap NexusPay

**Prazo Final:** 15/03 (Domingo)
**Stack:** Laravel (PHP) + MySQL + Docker

## 📍 Fase 1: Fundação e Infraestrutura (Terça/Quarta)

- [x] Inicializar projeto Laravel.
- [x] Criar `docker-compose.yml` (MySQL + Mocks).
- [X] Configurar variáveis de ambiente (`.env`) para conexão com o banco.
- [X] Criar Migrations (Eloquent) para as tabelas:
    - [X] `users` (email, password, etc).
    - [X] `gateways` (name, is_active, priority).
    - [X] `clients` (name, email).
    - [X] `products` (name, amount em centavos).
    - [X] `transactions` (relacionamentos, status, valor, etc).
- [X] Criar Seeders para popular o banco com produtos e os dois gateways iniciais.
- [X] Limpar boilerplate (rotas genéricas, views).

## 📍 Fase 2: Autenticação e Domínio (Quarta/Quinta)

- [X] Instalar e configurar o Laravel Sanctum.
- [X] Criar rota e controller de Login (`POST /login`).
- [X] Proteger rotas privadas com middleware de autenticação.
- [X] Criar rotas de gerenciamento de Gateways:
    - [X] Ativar/Desativar gateway.
    - [X] Alterar prioridade.
- [X] Implementar validações de dados nas entradas.

## 📍 Fase 3: O Core Multi-Gateway (Quinta/Sexta)

- [X] Criar interface padrão para os serviços de pagamento.
- [X] Implementar integração com Gateway 1 (Mock porta 3001 - Requer Login no mock).
- [X] Implementar integração com Gateway 2 (Mock porta 3002 - Headers fixos).
- [X] Criar classe orquestradora (Strategy/Factory) para gerenciar o fluxo:
    - [X] Buscar gateways ativos ordenados por prioridade.
    - [X] Tentar cobrar no primeiro.
    - [X] Fallback: Se falhar, tentar o próximo automaticamente.

## 📍 Fase 4: Regras de Negócio Nível 2 (Sexta)

- [X] Criar CRUD de Produtos.
- [X] Criar rota de Compra (`POST /transactions`):
    - [X] Receber `product_id`, `quantity` e dados do cartão.
    - [X] Calcular valor total no back-end (preço do banco \* quantidade).
    - [X] Chamar orquestrador de gateways.
    - [X] Salvar transação no banco com status correto e gateway utilizado.
- [X] Criar rota de Reembolso (`POST /transactions/:id/charge_back`).
- [X] Criar rotas de leitura adicionais:
    - [X] Listar todos os clientes.
    - [X] Listar detalhe do cliente e suas compras.
    - [X] Listar todas as compras gerais.
- [X] Criar CRUD de usuários
- [X] Criar rotas para gerenciamento dos Gateways

## 📍 Fase 5: Qualidade e Testes (Sábado)

- [X] Configurar ambiente de testes (Pest).
- [X] Escrever testes unitários/integração para o cálculo de valor da compra.
- [X] Escrever teste simulando sucesso no Gateway 1.
- [X] Escrever teste simulando falha no Gateway 1 (CVV inválido) e sucesso no Gateway 2.
- [X] Refinar respostas de erro da API (padronizar JSON de erro).

## 📍 Fase 6: Entrega (Domingo)

- [ ] Escrever `README.md` detalhado:
    - [ ] Passo a passo de instalação.
    - [ ] Como rodar o Docker.
    - [ ] Explicação da arquitetura Multi-Gateway.
- [ ] Revisar código (Clean Architecture, responsabilidades separadas).
- [ ] Realizar commit final.
- [ ] Enviar repositório do Github!
