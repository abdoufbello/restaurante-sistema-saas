# 🚀 Guia Completo: Deploy na Vercel + Captação de Leads

## 📋 **PREPARAÇÃO DO PROJETO PARA VERCEL**

### 1. **Configurações Necessárias**

#### A. Criar arquivo `vercel.json`
```json
{
  "version": 2,
  "builds": [
    {
      "src": "index.php",
      "use": "@vercel/php"
    }
  ],
  "routes": [
    {
      "src": "/(.*)",
      "dest": "/index.php"
    }
  ],
  "env": {
    "CI_ENVIRONMENT": "production"
  }
}
```

#### B. Configurar variáveis de ambiente
Crie arquivo `.env.production`:
```env
# Database
DATABASE_URL=mysql://user:password@host:port/database
DB_HOST=seu-host-mysql
DB_USERNAME=seu-usuario
DB_PASSWORD=sua-senha
DB_DATABASE=nome-do-banco

# App
APP_URL=https://seu-dominio.vercel.app
CI_ENVIRONMENT=production
APP_TIMEZONE=America/Sao_Paulo

# Security
ENCRYPTION_KEY=sua-chave-de-32-caracteres
JWT_SECRET=sua-chave-jwt-secreta

# Email (para leads)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu-email@gmail.com
SMTP_PASS=sua-senha-app

# WhatsApp Business API
WHATSAPP_TOKEN=seu-token-whatsapp
WHATSAPP_PHONE_ID=seu-phone-id
```

### 2. **Banco de Dados na Nuvem**

#### Opções Recomendadas:
1. **PlanetScale** (MySQL compatível, gratuito)
2. **Railway** (PostgreSQL/MySQL, fácil setup)
3. **Supabase** (PostgreSQL, recursos extras)
4. **AWS RDS** (produção robusta)

#### Setup PlanetScale (Recomendado):
```bash
# 1. Criar conta em planetscale.com
# 2. Criar database
# 3. Obter connection string
# 4. Configurar no .env.production
```

### 3. **Adaptações do Código**

#### A. Configurar rotas para produção
Editar `app/Config/Routes.php`:
```php
// Adicionar no início
if (ENVIRONMENT === 'production') {
    $routes->get('/', 'Home::landing'); // Página de captação
    $routes->get('/demo', 'Home::demo'); // Demo do sistema
    $routes->post('/lead', 'Home::capturarLead'); // Captura de leads
}
```

#### B. Criar Controller para Landing Page
Criar `app/Controllers/Home.php`:
```php
<?php
namespace App\Controllers;

class Home extends BaseController
{
    public function landing()
    {
        return view('landing/index');
    }
    
    public function demo()
    {
        // Redirecionar para demo com dados fictícios
        return redirect()->to('/dashboard?demo=true');
    }
    
    public function capturarLead()
    {
        $data = $this->request->getPost();
        
        // Salvar lead no banco
        $leadModel = new \App\Models\Lead();
        $leadModel->save([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'telefone' => $data['telefone'],
            'restaurante' => $data['restaurante'],
            'cidade' => $data['cidade'],
            'interesse' => $data['interesse'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Enviar email de notificação
        $this->enviarNotificacaoLead($data);
        
        // Enviar WhatsApp automático
        $this->enviarWhatsAppLead($data);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Obrigado! Entraremos em contato em breve.'
        ]);
    }
}
```

---

## 🎯 **ESTRATÉGIA DE CAPTAÇÃO DE LEADS**

### 1. **Landing Page Otimizada**

#### Estrutura da Página:
```html
<!-- Hero Section -->
<section class="hero">
    <h1>Revolucione Seu Restaurante com IA</h1>
    <p>Sistema completo de gestão com WhatsApp automático e previsão de demanda</p>
    <button>TESTE GRÁTIS POR 30 DIAS</button>
</section>

<!-- Problemas que Resolve -->
<section class="problems">
    <h2>Pare de Perder Dinheiro com:</h2>
    <ul>
        <li>❌ Estoque parado ou em falta</li>
        <li>❌ Pedidos perdidos no WhatsApp</li>
        <li>❌ Relatórios manuais demorados</li>
        <li>❌ Funcionários sobrecarregados</li>
    </ul>
</section>

<!-- Solução -->
<section class="solution">
    <h2>Nossa Solução:</h2>
    <div class="features">
        <div>🤖 IA prevê demanda</div>
        <div>📱 WhatsApp automático</div>
        <div>📊 Relatórios em tempo real</div>
        <div>🔄 Integração delivery</div>
    </div>
</section>

<!-- Formulário de Lead -->
<section class="lead-form">
    <h2>Comece Hoje Mesmo - GRÁTIS</h2>
    <form id="leadForm">
        <input name="nome" placeholder="Seu nome" required>
        <input name="email" type="email" placeholder="Seu email" required>
        <input name="telefone" placeholder="WhatsApp" required>
        <input name="restaurante" placeholder="Nome do restaurante" required>
        <input name="cidade" placeholder="Cidade" required>
        <select name="interesse" required>
            <option value="">Maior interesse</option>
            <option value="estoque">Controle de Estoque</option>
            <option value="whatsapp">Automação WhatsApp</option>
            <option value="delivery">Integração Delivery</option>
            <option value="relatorios">Relatórios Avançados</option>
        </select>
        <button type="submit">QUERO TESTAR GRÁTIS</button>
    </form>
</section>

<!-- Prova Social -->
<section class="social-proof">
    <h2>Restaurantes que já usam:</h2>
    <div class="testimonials">
        <!-- Depoimentos fictícios iniciais -->
    </div>
</section>
```

### 2. **Funil de Conversão**

#### Jornada do Lead:
1. **Visitante** → Landing Page
2. **Interessado** → Preenche formulário
3. **Lead Qualificado** → Recebe demo personalizada
4. **Trial** → 30 dias grátis
5. **Cliente** → Assinatura paga

#### Automação de Follow-up:
```php
// Sequência automática de emails
Day 0: "Bem-vindo! Aqui está seu acesso"
Day 1: "Como configurar em 5 minutos"
Day 3: "Dica: Conecte seu WhatsApp"
Day 7: "Relatório: Economia da primeira semana"
Day 14: "Webinar: Funcionalidades avançadas"
Day 21: "Oferta especial: 50% desconto"
Day 28: "Últimos dias do trial"
Day 30: "Não perca seus dados - Assine agora"
```

### 3. **Canais de Aquisição**

#### A. **Google Ads**
```
Palavras-chave:
- "sistema para restaurante"
- "controle estoque restaurante"
- "automação whatsapp delivery"
- "software gestão restaurante"
- "pdv restaurante"
```

#### B. **Facebook/Instagram Ads**
```
Audiências:
- Donos de restaurantes
- Gerentes de food service
- Interessados em delivery
- Seguidores de concorrentes
```

#### C. **Content Marketing**
```
Blog posts:
- "Como reduzir desperdício em 50%"
- "WhatsApp Business para restaurantes"
- "Relatórios que todo dono deveria ver"
- "Integração com iFood e Uber Eats"
```

---

## 🚀 **PASSOS PARA DEPLOY**

### 1. **Preparar Repositório**
```bash
# 1. Criar repositório no GitHub
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/seu-usuario/restaurant-system
git push -u origin main
```

### 2. **Deploy na Vercel**
```bash
# 1. Instalar Vercel CLI
npm i -g vercel

# 2. Login na Vercel
vercel login

# 3. Deploy
vercel --prod
```

### 3. **Configurar Domínio**
```bash
# Na Vercel Dashboard:
# 1. Settings → Domains
# 2. Adicionar domínio personalizado
# 3. Configurar DNS
```

### 4. **Configurar Variáveis de Ambiente**
```bash
# Na Vercel Dashboard:
# 1. Settings → Environment Variables
# 2. Adicionar todas as variáveis do .env.production
```

---

## 📈 **MÉTRICAS E OTIMIZAÇÃO**

### 1. **KPIs Importantes**
- **Taxa de Conversão**: Visitantes → Leads
- **Custo por Lead (CPL)**
- **Taxa Trial → Pagante**
- **Lifetime Value (LTV)**
- **Churn Rate**

### 2. **Ferramentas de Analytics**
```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_TRACKING_ID"></script>

<!-- Facebook Pixel -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', 'YOUR_PIXEL_ID');
  fbq('track', 'PageView');
</script>

<!-- Hotjar -->
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:YOUR_HJID,hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>
```

---

## 💰 **MODELO DE NEGÓCIO**

### 1. **Planos de Assinatura**
```
🥉 BÁSICO - R$ 97/mês
- Até 100 pedidos/mês
- WhatsApp básico
- Relatórios essenciais

🥈 PROFISSIONAL - R$ 197/mês
- Pedidos ilimitados
- IA de previsão
- Integrações delivery
- Suporte prioritário

🥇 ENTERPRISE - R$ 397/mês
- Multi-lojas
- API personalizada
- Consultoria mensal
- White-label
```

### 2. **Estratégia de Preços**
- **Trial gratuito**: 30 dias
- **Desconto anual**: 20% off
- **Onboarding**: Grátis nos primeiros 100 clientes
- **Migração**: Grátis de outros sistemas

---

## 🎯 **PRÓXIMOS PASSOS**

### Semana 1-2: **Setup Técnico**
- [ ] Configurar banco de dados na nuvem
- [ ] Deploy na Vercel
- [ ] Configurar domínio personalizado
- [ ] Testar todas as funcionalidades

### Semana 3-4: **Landing Page**
- [ ] Criar landing page otimizada
- [ ] Implementar formulário de leads
- [ ] Configurar analytics
- [ ] Testes A/B do formulário

### Semana 5-6: **Marketing**
- [ ] Configurar Google Ads
- [ ] Criar campanhas Facebook
- [ ] Produzir conteúdo para blog
- [ ] Configurar email marketing

### Semana 7-8: **Otimização**
- [ ] Analisar métricas
- [ ] Otimizar conversões
- [ ] Melhorar onboarding
- [ ] Escalar campanhas

---

## 🚨 **CHECKLIST FINAL**

### Antes do Lançamento:
- [ ] ✅ Sistema funcionando 100%
- [ ] ✅ Banco de dados configurado
- [ ] ✅ SSL/HTTPS ativo
- [ ] ✅ Formulário de leads testado
- [ ] ✅ Emails automáticos funcionando
- [ ] ✅ WhatsApp API conectada
- [ ] ✅ Analytics configurado
- [ ] ✅ Backup automático ativo
- [ ] ✅ Monitoramento de uptime
- [ ] ✅ Política de privacidade/LGPD

### Pós-Lançamento:
- [ ] 📊 Monitorar métricas diariamente
- [ ] 📞 Responder leads em até 1h
- [ ] 🔧 Corrigir bugs rapidamente
- [ ] 📈 Otimizar campanhas semanalmente
- [ ] 💬 Coletar feedback dos usuários

---

**🎯 Meta Inicial: 100 leads qualificados em 30 dias**  
**💰 Objetivo: 10 clientes pagantes no primeiro mês**  
**🚀 Visão: Ser referência em automação para restaurantes**