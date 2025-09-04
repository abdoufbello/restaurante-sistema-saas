# Guia de Deploy - Prato Rápido SaaS

## Arquitetura de Deploy Recomendada

### GitHub + Portainer + Traefik + Docker

**Por que esta combinação é ideal:**
- ✅ GitHub Actions para CI/CD automático
- ✅ Portainer para gerenciamento visual dos containers
- ✅ Traefik para proxy reverso e SSL automático
- ✅ Docker para containerização e isolamento
- ✅ Escalabilidade horizontal futura

## Estrutura de Subdomínios Necessários

### Subdomínios Principais (4 obrigatórios)
1. **app.seudominio.com** - Aplicação principal (dashboard, pedidos, etc.)
2. **api.seudominio.com** - API REST (CodeIgniter 4)
3. **admin.seudominio.com** - Painel administrativo
4. **portainer.seudominio.com** - Gerenciamento de containers

### Subdomínios Opcionais (2 recomendados)
5. **traefik.seudominio.com** - Dashboard do Traefik
6. **db.seudominio.com** - phpMyAdmin (opcional, para debug)

**Total: 4-6 subdomínios**

## Configuração Docker

### 1. Dockerfile para a Aplicação
```dockerfile
# Dockerfile
FROM php:8.2-apache

# Instalar extensões PHP necessárias
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

# Copiar código
COPY . /var/www/html/
COPY .htaccess /var/www/html/

# Permissões
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

EXPOSE 80
```

### 2. Docker Compose com Traefik
```yaml
# docker-compose.yml
version: '3.8'

services:
  # Traefik Proxy
  traefik:
    image: traefik:v3.0
    container_name: traefik
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - ./traefik:/etc/traefik
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.traefik.rule=Host(`traefik.seudominio.com`)"
      - "traefik.http.routers.traefik.tls.certresolver=letsencrypt"
    networks:
      - web

  # Aplicação Principal
  app:
    build: .
    container_name: prato-rapido-app
    restart: unless-stopped
    volumes:
      - ./public:/var/www/html/public
      - ./app:/var/www/html/app
      - ./uploads:/var/www/html/uploads
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.app.rule=Host(`app.seudominio.com`)"
      - "traefik.http.routers.app.tls.certresolver=letsencrypt"
      - "traefik.http.services.app.loadbalancer.server.port=80"
    networks:
      - web
      - internal
    depends_on:
      - mysql

  # API Backend
  api:
    build: .
    container_name: prato-rapido-api
    restart: unless-stopped
    volumes:
      - ./api:/var/www/html/api
      - ./app:/var/www/html/app
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.api.rule=Host(`api.seudominio.com`)"
      - "traefik.http.routers.api.tls.certresolver=letsencrypt"
    networks:
      - web
      - internal
    depends_on:
      - mysql

  # Banco de Dados
  mysql:
    image: mysql:8.0
    container_name: prato-rapido-db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: sua_senha_root
      MYSQL_DATABASE: prato_rapido
      MYSQL_USER: prato_user
      MYSQL_PASSWORD: sua_senha_user
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database:/docker-entrypoint-initdb.d
    networks:
      - internal

  # phpMyAdmin (opcional)
  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: prato-rapido-phpmyadmin
    restart: unless-stopped
    environment:
      PMA_HOST: mysql
      PMA_USER: root
      PMA_PASSWORD: sua_senha_root
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.phpmyadmin.rule=Host(`db.seudominio.com`)"
      - "traefik.http.routers.phpmyadmin.tls.certresolver=letsencrypt"
    networks:
      - web
      - internal
    depends_on:
      - mysql

volumes:
  mysql_data:

networks:
  web:
    external: true
  internal:
    external: false
```

### 3. Configuração do Traefik
```yaml
# traefik/traefik.yml
api:
  dashboard: true
  insecure: false

entryPoints:
  web:
    address: ":80"
    http:
      redirections:
        entrypoint:
          to: websecure
          scheme: https
  websecure:
    address: ":443"

providers:
  docker:
    endpoint: "unix:///var/run/docker.sock"
    exposedByDefault: false

certificatesResolvers:
  letsencrypt:
    acme:
      email: seu-email@dominio.com
      storage: /etc/traefik/acme.json
      httpChallenge:
        entryPoint: web
```

## GitHub Actions para CI/CD

### Workflow de Deploy Automático
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup SSH
      uses: webfactory/ssh-agent@v0.7.0
      with:
        ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}
    
    - name: Deploy to server
      run: |
        ssh -o StrictHostKeyChecking=no user@seu-servidor.com '
          cd /path/to/prato-rapido &&
          git pull origin main &&
          docker-compose down &&
          docker-compose build --no-cache &&
          docker-compose up -d
        '
```

## Vantagens desta Arquitetura

### 1. **GitHub Integration**
- ✅ Controle de versão profissional
- ✅ CI/CD automático
- ✅ Backup automático do código
- ✅ Colaboração em equipe
- ✅ Issues e project management

### 2. **Portainer Benefits**
- ✅ Interface visual para containers
- ✅ Logs em tempo real
- ✅ Monitoramento de recursos
- ✅ Deploy com um clique
- ✅ Rollback fácil

### 3. **Traefik + SSL**
- ✅ SSL automático (Let's Encrypt)
- ✅ Proxy reverso inteligente
- ✅ Load balancing
- ✅ Health checks
- ✅ Dashboard de monitoramento

## Correções e Melhorias que Posso Implementar

### 1. **Bugs Atuais Identificados**
- ✅ Gráfico de vendas (já corrigido)
- ✅ Links 404 (já corrigidos)
- 🔄 Validação de formulários
- 🔄 Tratamento de erros
- 🔄 Responsividade mobile

### 2. **Melhorias de Segurança**
- 🔄 Sanitização de inputs
- 🔄 CSRF protection
- 🔄 Rate limiting
- 🔄 SQL injection prevention
- 🔄 XSS protection

### 3. **Performance**
- 🔄 Cache de queries
- 🔄 Otimização de imagens
- 🔄 Minificação CSS/JS
- 🔄 CDN integration

### 4. **Features SaaS**
- 🔄 Multi-tenancy
- 🔄 Billing integration
- 🔄 User management
- 🔄 API rate limiting
- 🔄 Analytics dashboard

## Próximos Passos

1. **Preparar repositório GitHub**
2. **Configurar secrets no GitHub Actions**
3. **Criar subdomínios no DNS**
4. **Configurar Portainer no servidor**
5. **Deploy inicial**
6. **Testes e correções**
7. **Monitoramento e logs**

## Estimativa de Tempo

- **Setup inicial**: 2-4 horas
- **Correções de bugs**: 4-6 horas
- **Melhorias de segurança**: 6-8 horas
- **Features SaaS**: 10-15 horas

**Total: 22-33 horas de desenvolvimento**

---

**Conclusão**: Esta arquitetura oferece escalabilidade, segurança e facilidade de manutenção, sendo perfeita para um SaaS em crescimento.