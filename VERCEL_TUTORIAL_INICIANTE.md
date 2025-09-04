# 🚀 TUTORIAL VERCEL PARA INICIANTES

## 📱 PASSO A PASSO VISUAL - ONDE CLICAR E O QUE FAZER

---

## 🎯 PARTE 1: ACESSAR A VERCEL

### 1️⃣ **Abrir o Site da Vercel**
- Abra seu navegador
- Digite: `https://vercel.com`
- Clique em **"Sign Up"** (se não tem conta) ou **"Log In"** (se já tem)

### 2️⃣ **Fazer Login**
- Escolha **"Continue with GitHub"** (mais fácil)
- Faça login com sua conta do GitHub
- Autorize a Vercel a acessar seus repositórios

---

## 🎯 PARTE 2: IMPORTAR SEU PROJETO

### 3️⃣ **Criar Novo Projeto**
- Na tela inicial da Vercel, clique no botão **"New Project"**
- Você verá uma lista dos seus repositórios do GitHub

### 4️⃣ **Selecionar Repositório**
- Procure por: **"restaurante-sistema-saas"**
- Clique no botão **"Import"** ao lado dele

### 5️⃣ **Configurar Deploy**
- **Project Name**: Deixe como está ou mude para algo como "meu-restaurante-sistema"
- **Framework Preset**: Selecione **"Other"**
- **Root Directory**: Deixe **"./"** (ponto barra)
- **Build Command**: Deixe vazio
- **Output Directory**: Deixe **"public"**
- **Install Command**: Deixe vazio

### 6️⃣ **NÃO CLIQUE EM DEPLOY AINDA!**
- Primeiro precisamos configurar as variáveis de ambiente
- Clique em **"Environment Variables"** (deve estar visível na tela)

---

## 🎯 PARTE 3: CONFIGURAR VARIÁVEIS DE AMBIENTE

### 7️⃣ **Adicionar Variáveis Uma por Uma**

Para cada variável abaixo, faça:
1. No campo **"Name"** (Nome): Digite o nome da variável
2. No campo **"Value"** (Valor): Digite o valor da variável
3. Clique em **"Add"** (Adicionar)

---

## 🔥 **VARIÁVEIS OBRIGATÓRIAS** (Adicione estas primeiro)

### ✅ **Variável 1:**
- **Name**: `CI_ENVIRONMENT`
- **Value**: `production`
- Clique **"Add"**

### ✅ **Variável 2:**
- **Name**: `app_baseURL`
- **Value**: `https://seu-projeto.vercel.app` 
- ⚠️ **IMPORTANTE**: Depois do deploy, volte aqui e substitua pela URL real que a Vercel der
- Clique **"Add"

### ✅ **Variável 3:**
- **Name**: `encryption_key`
- **Value**: `6583f600e2c53bc93fb9b51e55674634`
- Clique **"Add"

### ✅ **Variável 4:**
- **Name**: `jwt_secret`
- **Value**: `52d7db95a4be1c60ecfa64b06806f702f777496554d221d2d209e9885ef65e8b`
- Clique **"Add"

### ✅ **Variável 5:**
- **Name**: `jwt_expire`
- **Value**: `3600`
- Clique **"Add"

---

## 🗄️ **BANCO DE DADOS** (Você precisa criar primeiro)

### 🎯 **ANTES DE CONTINUAR - CRIAR BANCO NO RAILWAY:**

### ⚠️ **IMPORTANTE**: PlanetScale Removeu Plano Gratuito!

O PlanetScale removeu seu plano gratuito em abril de 2024. Agora vamos usar **Railway** que é:
- ✅ **$5 gratuitos por mês** (suficiente para projetos pequenos)
- ✅ **MySQL nativo** (sem migração necessária)
- ✅ **Interface muito simples**
- ✅ **Performance excelente**

1. **Abra nova aba**: `https://railway.app`
2. **Clique**: "Start a New Project" e faça cadastro com GitHub
3. **Clique**: "New Project" → "Database" → "MySQL"
4. **Aguarde** uns 2 minutos para criar
5. **Clique no banco criado** → aba "Connect"
6. **Copie** as informações que aparecerem:
   - Host
   - Username 
   - Password
   - Database name

### ✅ **Agora volte para a Vercel e adicione:**

### ✅ **Variável 6:**
- **Name**: `database_default_hostname`
- **Value**: `[COLE O HOST DO RAILWAY AQUI]`
- Clique **"Add"**

### ✅ **Variável 7:**
- **Name**: `database_default_database`
- **Value**: `railway` (geralmente é sempre "railway")
- Clique **"Add"**

### ✅ **Variável 8:**
- **Name**: `database_default_username`
- **Value**: `root` (geralmente é sempre "root")
- Clique **"Add"**

### ✅ **Variável 9:**
- **Name**: `database_default_password`
- **Value**: `[COLE A PASSWORD DO RAILWAY AQUI]`
- Clique **"Add"**

### ✅ **Variável 10:**
- **Name**: `database_default_DBDriver`
- **Value**: `MySQLi`
- Clique **"Add"

### ✅ **Variável 11:**
- **Name**: `database_default_port`
- **Value**: `3306`
- Clique **"Add"

### ✅ **Variável 12:**
- **Name**: `database_default_encrypt`
- **Value**: `true`
- Clique **"Add"

---

## 📧 **EMAIL** (Recomendado - Configure com Gmail)

### 🎯 **ANTES DE CONTINUAR - CONFIGURAR GMAIL:**

1. **Vá para**: `https://myaccount.google.com/security`
2. **Ative**: "Verificação em duas etapas" (se não estiver ativa)
3. **Vá para**: `https://myaccount.google.com/apppasswords`
4. **Selecione**: "Mail" e "Windows Computer"
5. **Clique**: "Generate"
6. **Copie** a senha de 16 caracteres que aparecer

### ✅ **Agora volte para a Vercel e adicione:**

### ✅ **Variável 13:**
- **Name**: `email_fromEmail`
- **Value**: `noreply@seu-dominio.com` (pode deixar assim mesmo)
- Clique **"Add"

### ✅ **Variável 14:**
- **Name**: `email_fromName`
- **Value**: `Sistema Restaurante`
- Clique **"Add"

### ✅ **Variável 15:**
- **Name**: `email_SMTPHost`
- **Value**: `smtp.gmail.com`
- Clique **"Add"**

### ✅ **Variável 16:**
- **Name**: `email_SMTPUser`
- **Value**: `[SEU_EMAIL@gmail.com]` (substitua pelo seu email)
- Clique **"Add"**

### ✅ **Variável 17:**
- **Name**: `email_SMTPPass`
- **Value**: `[COLE A SENHA DE APP DO GMAIL AQUI]`
- Clique **"Add"**

### ✅ **Variável 18:**
- **Name**: `email_SMTPPort`
- **Value**: `587`
- Clique **"Add"**

### ✅ **Variável 19:**
- **Name**: `email_SMTPCrypto`
- **Value**: `tls`
- Clique **"Add"**

---

## 🎯 PARTE 4: FAZER O DEPLOY

### 8️⃣ **Finalmente - Deploy!**
- Depois de adicionar todas as variáveis acima
- Clique no botão grande **"Deploy"**
- Aguarde uns 2-3 minutos

### 9️⃣ **Sucesso!**
- Quando aparecer "Congratulations!"
- Clique em **"Visit"** para ver seu site
- Copie a URL que aparece (algo como `https://meu-projeto-abc123.vercel.app`)

### 🔟 **Atualizar URL Base**
- Volte para **Settings** → **Environment Variables**
- Encontre a variável `app_baseURL`
- Clique no ícone de **lápis** (editar)
- Substitua pela URL real do seu site
- Clique **"Save"**

---

## 🎯 PARTE 5: TESTAR TUDO

### ✅ **Checklist de Testes:**
- [ ] Site abre sem erros
- [ ] Landing page aparece bonita
- [ ] Formulário de contato funciona
- [ ] Página de demo abre
- [ ] Página de agradecimento funciona

---

## 🆘 **SE ALGO DEU ERRADO**

### 🔍 **Ver Erros:**
1. Na Vercel, vá em **"Functions"** → **"View Function Logs"**
2. Ou vá em **"Deployments"** → clique no deploy → **"View Function Logs"**

### 🔧 **Problemas Comuns:**

**❌ Erro de Banco:**
- Verifique se copiou corretamente as credenciais do Railway
- Verifique se o banco está ativo no Railway
- Verifique se não ultrapassou o limite de $5 gratuitos

**❌ Erro de Email:**
- Verifique se a senha de app do Gmail está correta
- Verifique se a verificação em duas etapas está ativa

**❌ Site não abre:**
- Aguarde uns 5 minutos (às vezes demora)
- Verifique se a URL base está correta

---

## 🎉 **PRONTO! SEU SISTEMA ESTÁ NO AR!**

### 📊 **Próximos Passos:**
1. **Teste tudo** - Preencha o formulário, veja se recebe email
2. **Domínio próprio** - Configure um domínio personalizado na Vercel
3. **Analytics** - Adicione Google Analytics depois
4. **Marketing** - Comece a divulgar para restaurantes!

### 💰 **Potencial de Receita:**
- **Plano Básico**: R$ 97/mês
- **Plano Pro**: R$ 197/mês  
- **Plano Premium**: R$ 397/mês

**🎯 Meta**: 10 clientes no primeiro mês = R$ 1.500+ de receita!

---

## 📞 **PRECISA DE AJUDA?**

Se algo não funcionou:
1. **Releia** este tutorial passo a passo
2. **Verifique** se todas as variáveis foram adicionadas corretamente
3. **Aguarde** uns 5-10 minutos após o deploy
4. **Teste** em uma aba anônima do navegador

**🚀 Seu sistema de captação de leads está pronto para gerar receita!**