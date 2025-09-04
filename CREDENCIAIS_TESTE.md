# Credenciais de Teste - Sistema SaaS Restaurante

## 🏪 Restaurantes Disponíveis

### 1. Pizzaria Bella Napoli
- **CNPJ**: `12.345.678/0001-90`
- **Plano**: Professional
- **Funcionários**:
  - **Admin**: `admin_pizza` / `123456`
  - **Pizzaiolo**: `pizzaiolo1` / `pizza123`
  - **Gerente**: `gerente1` / `gerente123`

### 2. Burger House Premium
- **CNPJ**: `98.765.432/0001-10`
- **Plano**: Starter
- **Funcionários**:
  - **Admin**: `admin_burger` / `123456`
  - **Atendente**: `atendente1` / `atend123`
  - **Inativo**: `funcionario_inativo` / `123456` (não deve conseguir logar)

### 3. Sushi Zen
- **CNPJ**: `11.222.333/0001-44`
- **Plano**: Trial
- **Funcionários**:
  - **Admin**: `admin_sushi` / `123456`
  - **Sushiman**: `sushiman1` / `sushiman123`

### 4. Café Gourmet Express
- **CNPJ**: `44.555.666/0001-77`
- **Plano**: Enterprise
- **Funcionários**:
  - **Admin**: `admin_cafe` / `123456`
  - **Barista**: `barista1` / `barista123`

### 5. Taco Loco Mexican Food
- **CNPJ**: `77.888.999/0001-33`
- **Plano**: Professional
- **Funcionários**:
  - **Admin**: `admin_taco` / `123456`
  - **Cozinheiro**: `cozinheiro1` / `cozinha123`

## 🧪 Cenários de Teste

### Testes de Autenticação
1. ✅ Login com credenciais válidas
2. ❌ Login com CNPJ inválido
3. ❌ Login com usuário inválido
4. ❌ Login com senha inválida
5. ❌ Login com funcionário inativo
6. ✅ Logout e redirecionamento

### Testes de Funcionalidades
1. **Dashboard**: Visualização de estatísticas
2. **Gestão de Pratos**: CRUD completo
3. **Categorias**: Criação e edição
4. **Pedidos**: Interface de pedidos online
5. **Planos**: Visualização e upgrade
6. **Relatórios**: Analytics e gráficos
7. **Notificações**: Alertas do sistema

### Testes de Planos SaaS
1. **Trial**: Limitações básicas
2. **Starter**: Funcionalidades intermediárias
3. **Professional**: Recursos avançados
4. **Enterprise**: Acesso completo

## 🔗 URLs de Teste

- **Login**: `http://localhost:8080/simple_auth.php`
- **Registro**: `http://localhost:8080/register.php`
- **Dashboard**: `http://localhost:8080/dashboard.php`
- **Pedidos Online**: `http://localhost:8080/kiosk.php`
- **Planos**: `http://localhost:8080/plans.php`
- **Relatórios**: `http://localhost:8080/reports.php`
- **Notificações**: `http://localhost:8080/notifications.php`

## 📝 Notas Importantes

- Todos os admins usam senha `123456` para facilitar testes
- Funcionários inativos não conseguem fazer login
- Cada restaurante tem planos diferentes para testar limitações
- Sistema funciona sem totem físico (conceito SaaS puro)
- Interface de pedidos pode ser usada como app web ou em totem