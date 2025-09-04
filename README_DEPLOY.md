# 🚀 Deploy do Sistema de Restaurante na Vercel

## 📋 Checklist Pré-Deploy

### ✅ Arquivos Criados
- [x] `vercel.json` - Configurações da Vercel
- [x] `.env.production` - Variáveis de ambiente para produção
- [x] `app/Models/Lead.php` - Model para captura de leads
- [x] `app/Database/Migrations/2024-01-01-000001_CreateLeadsTable.php` - Tabela de leads
- [x] `app/Controllers/Home.php` - Controller atualizado com landing page
- [x] `app/Views/landing/index.php` - Landing page principal
- [x] `app/Views/landing/obrigado.php` - Página de agradecimento
- [x] `app/Views/landing/demo.php` - Página de demonstração
- [x] `app/Config/Locale.php` - Configurações de idioma

## 🎯 Passos para Deploy

### 1. Preparar Repositório GitHub

```bash
# Se ainda não tem Git inicializado
git init
git add .
git commit -m "Preparação para deploy na Vercel"

# Criar repositório no GitHub e conectar
git remote add origin https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git
git branch -M main
git push -u origin main
```

### 2. Configurar Banco de Dados (PlanetScale)

1. **Criar conta no PlanetScale**: https://planetscale.com
2. **Criar novo banco**: `restaurante-sistema`
3. **Obter credenciais de conexão**:
   - Host
   - Username
   - Password
   - Database name
4. **Executar migrações**:
   ```bash
   # Localmente, com as credenciais do PlanetScale
   php spark migrate
   ```

### 3. Deploy na Vercel

#### Opção A: Via Dashboard Vercel
1. Acesse https://vercel.com
2. Clique em "New Project"
3. Conecte seu repositório GitHub
4. Configure as variáveis de ambiente (ver seção abaixo)
5. Deploy!

#### Opção B: Via Vercel CLI
```bash
# Instalar Vercel CLI
npm i -g vercel

# Login na Vercel
vercel login

# Deploy
vercel

# Para deploy de produção
vercel --prod
```

### 4. Configurar Variáveis de Ambiente na Vercel

No dashboard da Vercel, vá em **Settings > Environment Variables** e adicione:

```
# Ambiente
CI_ENVIRONMENT=production

# Base URL (será fornecida pela Vercel)
app.baseURL=https://seu-projeto.vercel.app

# Banco de Dados PlanetScale
database.default.hostname=SEU_HOST_PLANETSCALE
database.default.database=SEU_DATABASE_NAME
database.default.username=SEU_USERNAME
database.default.password=SUA_PASSWORD
database.default.DBDriver=MySQLi
database.default.port=3306

# Chaves de Segurança (gerar novas)
encryption.key=SUA_CHAVE_32_CARACTERES
jwt.secret=SUA_CHAVE_JWT_SECRETA

# Email (configurar com seu provedor)
email.SMTPHost=smtp.gmail.com
email.SMTPUser=seu-email@gmail.com
email.SMTPPass=sua-senha-app
email.SMTPPort=587

# WhatsApp Business API
whatsapp.token=SEU_TOKEN_WHATSAPP
whatsapp.phone_number_id=SEU_PHONE_ID
whatsapp.webhook_verify_token=SEU_WEBHOOK_TOKEN

# Analytics
GA_TRACKING_ID=G-XXXXXXXXXX
FB_PIXEL_ID=XXXXXXXXXX
HOTJAR_ID=XXXXXXX
```

## 🚀 Próximos Passos Imediatos

### 1. Testar Localmente
- ✅ Servidor rodando em http://localhost:8080
- ✅ Landing page funcionando
- ✅ Formulário de captura de leads
- ✅ Páginas de demo e agradecimento

### 2. Preparar para Produção
1. **Configurar banco de dados na nuvem**
2. **Fazer deploy na Vercel**
3. **Configurar domínio personalizado**
4. **Testar todas as funcionalidades**

### 3. Estratégia de Marketing
- **Google Ads**: Palavras-chave "sistema restaurante"
- **Facebook Ads**: Público donos de restaurantes
- **SEO**: Otimização para buscas locais
- **Content Marketing**: Blog posts e vídeos

## 💰 Modelo de Negócio

### Planos de Assinatura

**🥉 Básico - R$ 97/mês**
- Até 100 pedidos/mês
- Controle básico de estoque
- WhatsApp manual
- Relatórios básicos

**🥈 Profissional - R$ 197/mês**
- Até 500 pedidos/mês
- IA de previsão de demanda
- WhatsApp automático
- Relatórios avançados

**🥇 Premium - R$ 397/mês**
- Pedidos ilimitados
- Todas as funcionalidades IA
- Multi-restaurantes
- Integrações ilimitadas

### Estratégia de Preços
- **Trial gratuito**: 30 dias
- **Desconto anual**: 20%
- **Garantia**: 30 dias

## 📊 Metas do Primeiro Mês

- **100 leads qualificados**
- **30 demos agendadas**
- **10 clientes pagantes**
- **R$ 1.500 MRR**

---

**🎯 Sistema pronto para receber os primeiros leads de restaurantes!**

*Última atualização: Janeiro 2024*