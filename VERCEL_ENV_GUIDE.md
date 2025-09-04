# 🔧 Guia Completo: Configuração de Variáveis de Ambiente na Vercel

## 🚀 Como Configurar na Vercel

1. **Acesse seu projeto na Vercel**: https://vercel.com/dashboard
2. **Vá em Settings > Environment Variables**
3. **Adicione cada variável abaixo**
4. **Selecione os ambientes**: Production, Preview, Development

---

## ⚡ VARIÁVEIS ESSENCIAIS (Obrigatórias)

### 🌍 Ambiente
```
CI_ENVIRONMENT = production
```

### 🔗 URL Base
```
app_baseURL = https://seu-projeto.vercel.app
```
**📝 Nota**: Substitua pela URL real que a Vercel fornecer

### 🔒 Segurança (CRÍTICO)
```
encryption_key = [GERAR_CHAVE_32_CARACTERES]
jwt_secret = [GERAR_CHAVE_JWT_FORTE]
```

**🔑 Como gerar chaves seguras:**
```bash
# Encryption Key (32 caracteres)
node -e "console.log(require('crypto').randomBytes(16).toString('hex'))"

# JWT Secret (64+ caracteres)
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

---

## 🗄️ BANCO DE DADOS (Obrigatório)

### Opção A: Railway MySQL (RECOMENDADO)

**⚠️ ATENÇÃO**: PlanetScale removeu o plano gratuito em abril de 2024!

**Use Railway** (mais fácil): https://railway.app
- $5 de crédito gratuito por mês
- Suporte completo ao MySQL
- Interface muito simples

```
database_default_hostname = [HOST_RAILWAY]
database_default_database = railway
database_default_username = root
database_default_password = [PASSWORD_RAILWAY]
database_default_DBDriver = MySQLi
database_default_port = 3306
```

**📋 Passos para Railway:**
1. Criar conta gratuita: https://railway.app
2. New Project > Database > MySQL
3. Clique no banco criado > Connect
4. Copiar credenciais de conexão
5. Executar migrações localmente primeiro

### Opção B: PlanetScale (Pago)
```
database_default_hostname = [HOST_PLANETSCALE]
database_default_database = [NOME_DATABASE]
database_default_username = [USERNAME]
database_default_password = [PASSWORD]
database_default_DBDriver = MySQLi
database_default_port = 3306
database_default_encrypt = true
```

**💡 Outras alternativas gratuitas**: Veja o arquivo `ALTERNATIVAS_PLANETSCALE.md`

---

## 📧 EMAIL (Recomendado)

### Gmail (Mais fácil)
```
email_fromEmail = noreply@seu-dominio.com
email_fromName = Sistema Restaurante
email_SMTPHost = smtp.gmail.com
email_SMTPUser = [SEU_EMAIL_GMAIL]
email_SMTPPass = [SENHA_DE_APP_GMAIL]
email_SMTPPort = 587
email_SMTPCrypto = tls
```

**📋 Como configurar Gmail:**
1. Ativar autenticação de 2 fatores
2. Gerar senha de app: https://myaccount.google.com/apppasswords
3. Usar a senha de app (não sua senha normal)

---

## 📱 WHATSAPP BUSINESS (Opcional)

```
whatsapp_token = [TOKEN_WHATSAPP_BUSINESS]
whatsapp_phoneId = [PHONE_NUMBER_ID]
whatsapp_webhookToken = [WEBHOOK_VERIFY_TOKEN]
whatsapp_apiUrl = https://graph.facebook.com/v18.0/
```

**📋 Como obter:**
1. Meta for Developers: https://developers.facebook.com
2. Criar app WhatsApp Business
3. Configurar webhook

---

## 💳 PAGAMENTOS (Opcional)

### Mercado Pago
```
mercadopago_publicKey = [PUBLIC_KEY_MP]
mercadopago_accessToken = [ACCESS_TOKEN_MP]
mercadopago_webhookSecret = [WEBHOOK_SECRET_MP]
```

### Stripe
```
stripe_publicKey = [PUBLIC_KEY_STRIPE]
stripe_secretKey = [SECRET_KEY_STRIPE]
stripe_webhookSecret = [WEBHOOK_SECRET_STRIPE]
```

---

## 📊 ANALYTICS (Recomendado)

```
google_analyticsId = G-XXXXXXXXXX
facebook_pixelId = [FACEBOOK_PIXEL_ID]
hotjar_siteId = [HOTJAR_SITE_ID]
```

**📋 Como obter:**
- **Google Analytics**: https://analytics.google.com
- **Facebook Pixel**: https://business.facebook.com
- **Hotjar**: https://www.hotjar.com

---

## 🚚 DELIVERY (Opcional)

### iFood
```
ifood_clientId = [CLIENT_ID_IFOOD]
ifood_clientSecret = [CLIENT_SECRET_IFOOD]
ifood_merchantId = [MERCHANT_ID_IFOOD]
```

### Uber Eats
```
ubereats_clientId = [CLIENT_ID_UBER]
ubereats_clientSecret = [CLIENT_SECRET_UBER]
ubereats_storeId = [STORE_ID_UBER]
```

---

## ☁️ STORAGE (Opcional)

### AWS S3
```
aws_accessKey = [AWS_ACCESS_KEY]
aws_secretKey = [AWS_SECRET_KEY]
aws_region = us-east-1
aws_bucket = [NOME_BUCKET]
```

---

## 🔧 CONFIGURAÇÕES AVANÇADAS

```
# Cache
cache_handler = file
cache_storePath = writable/cache/

# Logs
logger_threshold = 3

# CORS
cors_allowedOrigins = ["https://seu-dominio.vercel.app"]
cors_allowedMethods = ["GET","POST","PUT","DELETE","OPTIONS"]
cors_allowedHeaders = ["Content-Type","Authorization","X-Requested-With"]

# Rate Limiting
rateLimit_enabled = true
rateLimit_requests = 100
rateLimit_window = 3600

# Maintenance
maintenance_enabled = false
```

---

## ✅ CHECKLIST DE DEPLOY

### Antes do Deploy
- [ ] Banco de dados configurado
- [ ] Chaves de segurança geradas
- [ ] Email configurado
- [ ] Analytics configurado

### Após o Deploy
- [ ] Testar landing page
- [ ] Testar formulário de leads
- [ ] Verificar emails de notificação
- [ ] Testar todas as páginas

### Configurações Opcionais
- [ ] WhatsApp Business
- [ ] Pagamentos (Mercado Pago/Stripe)
- [ ] Integrações de delivery
- [ ] Storage na nuvem

---

## 🆘 TROUBLESHOOTING

### Erro de Banco de Dados
```
# Verificar se as credenciais estão corretas
# Verificar se o banco permite conexões externas
# Para PlanetScale: SSL deve estar habilitado
```

### Erro de Email
```
# Gmail: Verificar se a senha de app está correta
# Verificar se a autenticação de 2 fatores está ativa
# Testar as credenciais localmente primeiro
```

### Erro de Chaves
```
# Encryption key deve ter exatamente 32 caracteres
# JWT secret deve ser forte (64+ caracteres)
# Nunca usar chaves de desenvolvimento em produção
```

---

## 🎯 PRÓXIMOS PASSOS

1. **Configurar variáveis essenciais**
2. **Fazer deploy na Vercel**
3. **Testar todas as funcionalidades**
4. **Configurar domínio personalizado**
5. **Ativar analytics e monitoramento**

---

**💡 Dica**: Comece apenas com as variáveis essenciais. Adicione as opcionais conforme a necessidade!

**🔒 Segurança**: Nunca compartilhe suas chaves. Use sempre chaves diferentes para desenvolvimento e produção.

---

*Última atualização: Janeiro 2024*