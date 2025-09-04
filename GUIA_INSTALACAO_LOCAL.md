# Guia de Instalação - Ambiente de Desenvolvimento Local

## Pré-requisitos

### 1. XAMPP (Recomendado para Windows)

**Download e Instalação:**
1. Baixe o XAMPP 8.1+ em: https://www.apachefriends.org/download.html
2. Execute o instalador como administrador
3. Selecione os componentes:
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP (8.1+)
   - ✅ phpMyAdmin
   - ❌ Mercury (não necessário)
   - ❌ Tomcat (não necessário)

**Configuração Inicial:**
1. Inicie o XAMPP Control Panel
2. Inicie os serviços Apache e MySQL
3. Teste acessando: http://localhost
4. Acesse phpMyAdmin: http://localhost/phpmyadmin

### 2. Composer (Gerenciador de Dependências PHP)

**Instalação:**
1. Baixe em: https://getcomposer.org/download/
2. Execute o instalador
3. Verifique a instalação:
   ```bash
   composer --version
   ```

### 3. Git (Controle de Versão)

**Instalação:**
1. Baixe em: https://git-scm.com/download/win
2. Execute o instalador com configurações padrão
3. Verifique a instalação:
   ```bash
   git --version
   ```

## Configuração do Projeto

### 1. Estrutura de Diretórios

Crie a seguinte estrutura no diretório `htdocs` do XAMPP:

```
C:\xampp\htdocs\restaurant-kiosk\
├── admin/          # Painel administrativo (OpenSourcePOS customizado)
├── kiosk/          # Interface do cliente para o totem
├── api/            # API REST para comunicação
├── uploads/        # Imagens dos pratos
├── database/       # Scripts SQL
└── docs/           # Documentação
```

### 2. Configuração do Banco de Dados

**Criar Banco de Dados:**
1. Acesse phpMyAdmin: http://localhost/phpmyadmin
2. Clique em "Novo" para criar um banco
3. Nome do banco: `restaurant_kiosk`
4. Collation: `utf8mb4_unicode_ci`

**Configuração de Usuário:**
```sql
CREATE USER 'restaurant_user'@'localhost' IDENTIFIED BY 'restaurant_pass123';
GRANT ALL PRIVILEGES ON restaurant_kiosk.* TO 'restaurant_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configuração do PHP

**Editar php.ini:**
Localização: `C:\xampp\php\php.ini`

```ini
; Aumentar limites para upload de imagens
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M

; Habilitar extensões necessárias
extension=gd
extension=mysqli
extension=pdo_mysql
extension=mbstring
extension=curl
extension=zip
extension=intl

; Configurações de timezone
date.timezone = America/Sao_Paulo
```

**Reiniciar Apache após alterações**

### 4. Configuração do Apache

**Habilitar mod_rewrite:**
1. Edite: `C:\xampp\apache\conf\httpd.conf`
2. Descomente a linha:
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Encontre a seção `<Directory "C:/xampp/htdocs">` e altere:
   ```
   AllowOverride All
   ```

## Instalação do OpenSourcePOS

### 1. Download do Código

```bash
cd C:\xampp\htdocs
git clone https://github.com/opensourcepos/opensourcepos.git restaurant-kiosk
cd restaurant-kiosk
```

### 2. Instalação de Dependências

```bash
composer install
```

### 3. Configuração do CodeIgniter

**Arquivo .env:**
Copie `.env.example` para `.env` e configure:

```env
# Database
database.default.hostname = localhost
database.default.database = restaurant_kiosk
database.default.username = restaurant_user
database.default.password = restaurant_pass123
database.default.DBDriver = MySQLi

# App
app.baseURL = 'http://localhost/restaurant-kiosk/'
app.forceGlobalSecureRequests = false

# Encryption
encryption.key = [gerar chave com: php spark key:generate]

# Session
session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.savePath = WRITEPATH . 'session'
```

### 4. Configuração de Permissões

**Windows (via Command Prompt como Admin):**
```cmd
icacls "C:\xampp\htdocs\restaurant-kiosk\writable" /grant Everyone:(OI)(CI)F
icacls "C:\xampp\htdocs\restaurant-kiosk\public\uploads" /grant Everyone:(OI)(CI)F
```

### 5. Importar Banco de Dados

1. Acesse phpMyAdmin
2. Selecione o banco `restaurant_kiosk`
3. Importe o arquivo SQL do OpenSourcePOS
4. Execute scripts de customização para restaurantes

## Estrutura de URLs

### Desenvolvimento Local
- **Admin Panel**: http://localhost/restaurant-kiosk/admin
- **Kiosk Interface**: http://localhost/restaurant-kiosk/kiosk
- **API**: http://localhost/restaurant-kiosk/api
- **phpMyAdmin**: http://localhost/phpmyadmin

## Testes de Funcionamento

### 1. Teste do PHP
Crie arquivo `test.php` em `htdocs`:
```php
<?php
phpinfo();
?>
```
Acesse: http://localhost/test.php

### 2. Teste do Banco
Crie arquivo `test_db.php`:
```php
<?php
$conn = new mysqli('localhost', 'restaurant_user', 'restaurant_pass123', 'restaurant_kiosk');
if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}
echo 'Conexão com banco OK!';
$conn->close();
?>
```

### 3. Teste do CodeIgniter
Acesse: http://localhost/restaurant-kiosk
Deve exibir a página inicial do CodeIgniter

## Troubleshooting

### Problemas Comuns

**1. Erro 403 Forbidden**
- Verificar permissões de pasta
- Verificar configuração do Apache

**2. Erro de Conexão com Banco**
- Verificar se MySQL está rodando
- Verificar credenciais no .env
- Verificar se o banco foi criado

**3. Erro 500 Internal Server Error**
- Verificar logs do Apache: `C:\xampp\apache\logs\error.log`
- Verificar configuração do php.ini
- Verificar permissões da pasta writable

**4. Upload de Imagens não Funciona**
- Verificar permissões da pasta uploads
- Verificar configurações de upload no php.ini
- Verificar tamanho máximo de arquivo

### Logs Importantes
- Apache Error Log: `C:\xampp\apache\logs\error.log`
- Apache Access Log: `C:\xampp\apache\logs\access.log`
- MySQL Error Log: `C:\xampp\mysql\data\mysql_error.log`
- PHP Error Log: Configurar no php.ini

## Próximos Passos

1. ✅ Ambiente configurado
2. ⏳ Customizar OpenSourcePOS para restaurantes
3. ⏳ Implementar autenticação com CNPJ
4. ⏳ Desenvolver interface do kiosk
5. ⏳ Integrar métodos de pagamento

## Comandos Úteis

```bash
# Gerar chave de criptografia
php spark key:generate

# Executar migrações
php spark migrate

# Limpar cache
php spark cache:clear

# Servidor de desenvolvimento
php spark serve
```

## Backup e Versionamento

### Git
```bash
# Inicializar repositório
git init
git add .
git commit -m "Initial commit"

# Criar branch de desenvolvimento
git checkout -b development
```

### Backup do Banco
```bash
# Exportar banco
mysqldump -u restaurant_user -p restaurant_kiosk > backup.sql

# Importar banco
mysql -u restaurant_user -p restaurant_kiosk < backup.sql
```