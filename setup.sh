#!/bin/bash

# Script de Setup - Prato Rápido SaaS
# Este script automatiza a configuração inicial do projeto

set -e

echo "🚀 Iniciando setup do Prato Rápido SaaS..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log colorido
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    log_error "Docker não está instalado. Por favor, instale o Docker primeiro."
    exit 1
fi

# Verificar se Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    log_error "Docker Compose não está instalado. Por favor, instale o Docker Compose primeiro."
    exit 1
fi

log_success "Docker e Docker Compose encontrados!"

# Criar rede externa do Traefik
log_info "Criando rede externa 'web' para o Traefik..."
docker network create web 2>/dev/null || log_warning "Rede 'web' já existe"

# Copiar arquivo de ambiente
if [ ! -f ".env" ]; then
    log_info "Copiando arquivo de configuração de ambiente..."
    cp .env.docker .env
    log_warning "IMPORTANTE: Configure as variáveis no arquivo .env antes de continuar!"
else
    log_info "Arquivo .env já existe"
fi

# Criar arquivo acme.json para certificados SSL
log_info "Criando arquivo para certificados SSL..."
mkdir -p docker/traefik
touch docker/traefik/acme.json
chmod 600 docker/traefik/acme.json

# Criar diretórios necessários
log_info "Criando diretórios necessários..."
mkdir -p uploads/dishes
mkdir -p writable/logs
mkdir -p writable/cache
mkdir -p writable/session
mkdir -p database/backups

# Definir permissões
log_info "Configurando permissões..."
chmod -R 755 public/
chmod -R 777 writable/
chmod -R 755 uploads/

# Função para solicitar configuração do domínio
configure_domain() {
    echo ""
    log_info "Configuração do Domínio"
    echo "Por favor, configure seu domínio no arquivo .env"
    echo "Exemplo: DOMAIN=meudominio.com"
    echo ""
    echo "Subdomínios que serão criados:"
    echo "  - app.meudominio.com (Aplicação principal)"
    echo "  - api.meudominio.com (API REST)"
    echo "  - admin.meudominio.com (Painel admin)"
    echo "  - portainer.meudominio.com (Gerenciamento)"
    echo "  - traefik.meudominio.com (Proxy dashboard)"
    echo "  - db.meudominio.com (phpMyAdmin)"
    echo ""
    read -p "Pressione Enter para continuar após configurar o domínio..."
}

# Função para configurar banco de dados
configure_database() {
    echo ""
    log_info "Configuração do Banco de Dados"
    echo "Configure as seguintes variáveis no arquivo .env:"
    echo "  - DB_NAME=prato_rapido"
    echo "  - DB_USER=prato_user"
    echo "  - DB_PASS=sua_senha_segura"
    echo "  - DB_ROOT_PASS=root_senha_segura"
    echo ""
}

# Função para configurar email
configure_email() {
    echo ""
    log_info "Configuração de Email"
    echo "Configure as seguintes variáveis no arquivo .env:"
    echo "  - email.fromEmail=noreply@seudominio.com"
    echo "  - email.SMTPHost=smtp.gmail.com"
    echo "  - email.SMTPUser=seu-email@gmail.com"
    echo "  - email.SMTPPass=sua-senha-app"
    echo ""
}

# Solicitar configurações
if [ "$1" != "--skip-config" ]; then
    configure_domain
    configure_database
    configure_email
fi

# Instalar dependências do Composer
if [ -f "composer.json" ]; then
    log_info "Instalando dependências do Composer..."
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader
    else
        log_warning "Composer não encontrado. As dependências serão instaladas no container."
    fi
fi

# Build das imagens Docker
log_info "Construindo imagens Docker..."
docker-compose build --no-cache

# Iniciar serviços
log_info "Iniciando serviços..."
docker-compose up -d

# Aguardar serviços ficarem prontos
log_info "Aguardando serviços ficarem prontos..."
sleep 30

# Executar migrações (se existirem)
if [ -d "app/Database/Migrations" ] && [ "$(ls -A app/Database/Migrations)" ]; then
    log_info "Executando migrações do banco de dados..."
    docker-compose exec -T app php spark migrate --all
fi

# Executar seeds (se existirem)
if [ -d "app/Database/Seeds" ] && [ "$(ls -A app/Database/Seeds)" ]; then
    log_info "Executando seeds do banco de dados..."
    docker-compose exec -T app php spark db:seed
fi

# Verificar status dos containers
log_info "Verificando status dos containers..."
docker-compose ps

# Mostrar informações finais
echo ""
log_success "🎉 Setup concluído com sucesso!"
echo ""
echo "📋 Informações importantes:"
echo "  🌐 Aplicação: https://app.$(grep DOMAIN .env | cut -d'=' -f2)"
echo "  🔧 Portainer: https://portainer.$(grep DOMAIN .env | cut -d'=' -f2)"
echo "  📊 Traefik: https://traefik.$(grep DOMAIN .env | cut -d'=' -f2)"
echo "  🗄️  phpMyAdmin: https://db.$(grep DOMAIN .env | cut -d'=' -f2)"
echo ""
echo "📝 Próximos passos:"
echo "  1. Configure os DNS dos subdomínios para apontar para seu servidor"
echo "  2. Aguarde a geração dos certificados SSL (pode levar alguns minutos)"
echo "  3. Acesse a aplicação e configure sua conta admin"
echo ""
echo "🔧 Comandos úteis:"
echo "  - Ver logs: docker-compose logs -f"
echo "  - Parar serviços: docker-compose down"
echo "  - Reiniciar: docker-compose restart"
echo "  - Backup DB: docker-compose exec mysql mysqldump -u root -p prato_rapido > backup.sql"
echo ""
log_success "Sistema Prato Rápido SaaS está rodando! 🚀"