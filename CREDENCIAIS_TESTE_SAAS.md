# 🔐 Credenciais de Teste - Prato Rápido SaaS

## 📋 Visão Geral

Este documento contém todas as credenciais de teste organizadas por plano do SaaS. Todos os usuários utilizam a senha padrão `123456` para facilitar os testes.

---

## 🆓 PLANO TRIAL (Gratuito - 30 dias)

### 🏪 Restaurante: [TESTE] Lanchonete Trial
- **CNPJ:** `10.000.000/0001-01`
- **ID:** 7
- **Plano:** Trial
- **Expira em:** 28/02/2025
- **Limites:**
  - 1 totem ativo
  - 50 pedidos por mês
  - 10 pratos no cardápio

### 👥 Usuários Disponíveis:

#### Admin Principal
- **Usuário:** `teste_trial`
- **Senha:** `123456`
- **Nome:** Admin Trial
- **Role:** admin
- **Email:** teste.trial@pratorapido.com

#### Funcionário
- **Usuário:** `funcionario_trial`
- **Senha:** `123456`
- **Nome:** Funcionário Trial
- **Role:** cashier
- **Email:** funcionario.trial@pratorapido.com

---

## 🚀 PLANO STARTER (R$ 99/mês)

### 🏪 Restaurante: [TESTE] Restaurante Starter
- **CNPJ:** `20.000.000/0001-02`
- **ID:** 8
- **Plano:** Starter
- **Expira em:** 16/03/2025
- **Limites:**
  - 2 totems ativos
  - 200 pedidos por mês
  - 50 pratos no cardápio

### 👥 Usuários Disponíveis:

#### Admin Principal
- **Usuário:** `teste_starter`
- **Senha:** `123456`
- **Nome:** Admin Starter
- **Role:** admin
- **Email:** teste.starter@pratorapido.com

#### Funcionário Caixa
- **Usuário:** `funcionario_starter`
- **Senha:** `123456`
- **Nome:** Funcionário Starter
- **Role:** cashier
- **Email:** funcionario.starter@pratorapido.com

#### Gerente
- **Usuário:** `gerente_starter`
- **Senha:** `123456`
- **Nome:** Gerente Starter
- **Role:** manager
- **Email:** gerente.starter@pratorapido.com

---

## 💼 PLANO PROFESSIONAL (R$ 199/mês)

### 🏪 Restaurante: [TESTE] Bistrô Professional
- **CNPJ:** `30.000.000/0001-03`
- **ID:** 9
- **Plano:** Professional
- **Expira em:** 16/04/2025
- **Limites:**
  - 5 totems ativos
  - 1000 pedidos por mês
  - 200 pratos no cardápio

### 👥 Usuários Disponíveis:

#### Admin Principal
- **Usuário:** `teste_professional`
- **Senha:** `123456`
- **Nome:** Admin Professional
- **Role:** admin
- **Email:** teste.professional@pratorapido.com

#### Funcionário Caixa
- **Usuário:** `funcionario_professional`
- **Senha:** `123456`
- **Nome:** Funcionário Professional
- **Role:** cashier
- **Email:** funcionario.professional@pratorapido.com

#### Gerente
- **Usuário:** `gerente_professional`
- **Senha:** `123456`
- **Nome:** Gerente Professional
- **Role:** manager
- **Email:** gerente.professional@pratorapido.com

#### Cozinheiro
- **Usuário:** `cozinha_professional`
- **Senha:** `123456`
- **Nome:** Cozinheiro Professional
- **Role:** kitchen
- **Email:** cozinha.professional@pratorapido.com

---

## 🏢 PLANO ENTERPRISE (R$ 399/mês)

### 🏪 Restaurante: [TESTE] Rede Enterprise
- **CNPJ:** `40.000.000/0001-04`
- **ID:** 10
- **Plano:** Enterprise
- **Expira em:** 16/05/2025
- **Limites:**
  - Totems ilimitados
  - Pedidos ilimitados
  - Pratos ilimitados

### 👥 Usuários Disponíveis:

#### Admin Principal
- **Usuário:** `teste_enterprise`
- **Senha:** `123456`
- **Nome:** Admin Enterprise
- **Role:** admin
- **Email:** teste.enterprise@pratorapido.com

#### Funcionário Caixa
- **Usuário:** `funcionario_enterprise`
- **Senha:** `123456`
- **Nome:** Funcionário Enterprise
- **Role:** cashier
- **Email:** funcionario.enterprise@pratorapido.com

#### Gerente
- **Usuário:** `gerente_enterprise`
- **Senha:** `123456`
- **Nome:** Gerente Enterprise
- **Role:** manager
- **Email:** gerente.enterprise@pratorapido.com

#### Cozinheiro
- **Usuário:** `cozinha_enterprise`
- **Senha:** `123456`
- **Nome:** Cozinheiro Enterprise
- **Role:** kitchen
- **Email:** cozinha.enterprise@pratorapido.com

#### Supervisor
- **Usuário:** `supervisor_enterprise`
- **Senha:** `123456`
- **Nome:** Supervisor Enterprise
- **Role:** manager
- **Email:** supervisor.enterprise@pratorapido.com

---

## 🔗 Como Fazer Login

### Método 1: Login Completo
1. Acesse: `http://localhost:8000/login.html`
2. **CNPJ:** Use o CNPJ do restaurante desejado
3. **Usuário:** Use um dos usuários listados acima
4. **Senha:** `123456` (para todos)

### Método 2: Acesso Direto ao Dashboard
1. Acesse: `http://localhost:8000/dashboard.html`
2. Sistema já configurado para demonstração

---

## 📊 Funcionalidades por Plano

### 🆓 Trial
- ✅ Dashboard básico
- ✅ Gestão de pratos (limitado a 10)
- ✅ Pedidos básicos (limitado a 50/mês)
- ✅ 1 totem
- ❌ Relatórios avançados
- ❌ Integrações

### 🚀 Starter
- ✅ Dashboard completo
- ✅ Gestão de pratos (limitado a 50)
- ✅ Pedidos (limitado a 200/mês)
- ✅ 2 totems
- ✅ Relatórios básicos
- ✅ Integração com delivery
- ❌ API personalizada

### 💼 Professional
- ✅ Dashboard avançado
- ✅ Gestão completa de pratos (limitado a 200)
- ✅ Pedidos (limitado a 1000/mês)
- ✅ 5 totems
- ✅ Relatórios completos
- ✅ Todas as integrações
- ✅ API personalizada
- ✅ White label básico

### 🏢 Enterprise
- ✅ Todas as funcionalidades
- ✅ Sem limitações
- ✅ Totems ilimitados
- ✅ Pedidos ilimitados
- ✅ Pratos ilimitados
- ✅ Suporte dedicado
- ✅ White label completo
- ✅ Treinamento personalizado

---

## 🧪 Cenários de Teste Sugeridos

### 1. Teste de Limitações por Plano
- Tente criar mais pratos do que o limite permite
- Verifique se os totems respeitam as limitações
- Teste a contagem de pedidos mensais

### 2. Teste de Roles/Permissões
- **Admin:** Acesso total ao sistema
- **Manager:** Gestão operacional, sem configurações críticas
- **Cashier:** Apenas pedidos e caixa
- **Kitchen:** Apenas visualização de pedidos para preparo

### 3. Teste de Upgrade/Downgrade
- Acesse a página de planos: `http://localhost:8000/plans.html`
- Teste mudança entre planos
- Verifique se as limitações são aplicadas corretamente

### 4. Teste Multi-tenant
- Faça login em restaurantes diferentes
- Verifique isolamento de dados
- Confirme que cada restaurante vê apenas seus dados

---

## 📝 Notas Importantes

- ⚠️ **Todos os usuários usam senha `123456`** para facilitar testes
- 🔒 **Dados isolados por restaurante** (multi-tenancy)
- 📅 **Datas de expiração configuradas** para demonstrar renovações
- 🏷️ **Restaurantes marcados com [TESTE]** para fácil identificação
- 🔄 **Sistema funciona offline** (dados em JSON para demonstração)

---

## 🚀 URLs Principais

- **Login:** http://localhost:8000/login.html
- **Dashboard:** http://localhost:8000/dashboard.html
- **Pedidos:** http://localhost:8000/orders.html
- **Cardápio:** http://localhost:8000/dishes.html
- **Planos:** http://localhost:8000/plans.html
- **Relatórios:** http://localhost:8000/reports.html
- **Totem:** http://localhost:8000/kiosk.html

---

*Documento criado em: 16/01/2025*
*Versão: 1.0*
*Sistema: Prato Rápido SaaS*