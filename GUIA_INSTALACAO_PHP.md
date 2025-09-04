# 📋 Guia de Instalação do PHP para Iniciantes

## 🎯 Opção 1: Instalação Automática (RECOMENDADA)

### Passo a Passo:

1. **Abra o PowerShell como Administrador:**
   - Pressione `Windows + X`
   - Clique em "Windows PowerShell (Admin)" ou "Terminal (Admin)"
   - Se aparecer uma janela de confirmação, clique em "Sim"

2. **Execute o script automático:**
   ```powershell
   cd "C:\Users\faiso\Downloads\CodeIgniter"
   .\install_xampp_auto.ps1
   ```

3. **Aguarde a instalação** (pode demorar alguns minutos)

4. **Reinicie o PowerShell** após a instalação

5. **Teste se funcionou:**
   ```powershell
   php --version
   ```

---

## 🛠️ Opção 2: Instalação Manual

### Se preferir instalar manualmente:

#### 📥 1. Baixar o XAMPP

1. Acesse: https://www.apachefriends.org/download.html
2. Clique em "Download" na versão do Windows (PHP 8.2.x)
3. Salve o arquivo (aproximadamente 150MB)

#### 💿 2. Instalar o XAMPP

1. **Execute o arquivo baixado** como Administrador:
   - Clique com botão direito no arquivo
   - Selecione "Executar como administrador"

2. **Durante a instalação:**
   - Clique "Next" em todas as telas
   - **IMPORTANTE:** Mantenha a pasta de instalação como `C:\xampp`
   - Desmarque "Learn more about Bitnami" se aparecer

3. **Aguarde a instalação** (5-10 minutos)

#### ⚙️ 3. Configurar o PATH (Importante!)

1. **Abra as Configurações do Sistema:**
   - Pressione `Windows + R`
   - Digite: `sysdm.cpl`
   - Pressione Enter

2. **Adicione o PHP ao PATH:**
   - Clique na aba "Avançado"
   - Clique em "Variáveis de Ambiente"
   - Na seção "Variáveis do sistema", encontre "Path"
   - Clique em "Path" e depois "Editar"
   - Clique "Novo" e adicione: `C:\xampp\php`
   - Clique "OK" em todas as janelas

#### 🚀 4. Iniciar os Serviços

1. **Abra o XAMPP Control Panel:**
   - Vá para `C:\xampp`
   - Execute `xampp-control.exe`

2. **Inicie os serviços:**
   - Clique "Start" ao lado de "Apache"
   - Clique "Start" ao lado de "MySQL"
   - Os botões devem ficar verdes

#### ✅ 5. Testar a Instalação

1. **Abra um novo PowerShell:**
   - Pressione `Windows + R`
   - Digite: `powershell`
   - Pressione Enter

2. **Teste o PHP:**
   ```powershell
   php --version
   ```
   
   Deve aparecer algo como:
   ```
   PHP 8.2.12 (cli) (built: Oct 26 2023 14:25:33) (ZTS Visual C++ 2019 x64)
   ```

3. **Teste o acesso ao localhost:**
   - Abra seu navegador
   - Acesse: http://localhost
   - Deve aparecer a página de boas-vindas do XAMPP

---

## 🔧 Próximos Passos Após a Instalação

### 1. Executar as Migrações do Banco

```powershell
# Navegue até a pasta do projeto
cd "C:\Users\faiso\Downloads\CodeIgniter"

# Execute as migrações
php spark migrate
```

### 2. Verificar se as Tabelas foram Criadas

1. Acesse: http://localhost/phpmyadmin
2. Clique no banco de dados do seu projeto
3. Verifique se as tabelas `lgpd_consents` e `lgpd_consent_logs` foram criadas

---

## 🆘 Problemas Comuns

### ❌ "php não é reconhecido como comando"
**Solução:** O PATH não foi configurado corretamente
- Reinicie o PowerShell
- Verifique se adicionou `C:\xampp\php` ao PATH
- Reinicie o computador se necessário

### ❌ "Porta 80 já está em uso"
**Solução:** Outro programa está usando a porta 80
- Feche o Skype (usa porta 80)
- Ou configure o Apache para usar outra porta (8080)

### ❌ "MySQL não inicia"
**Solução:** Porta 3306 pode estar ocupada
- Verifique se não há outro MySQL rodando
- Reinicie o computador

---

## 📞 Precisa de Ajuda?

Se tiver problemas:
1. Tire um print da tela do erro
2. Me informe qual passo não funcionou
3. Posso ajudar a resolver!

**Boa sorte! 🚀**