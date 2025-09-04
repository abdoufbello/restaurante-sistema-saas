# Dockerfile para Prato Rápido SaaS
FROM php:8.2-apache

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configurar extensões GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Instalar extensões PHP necessárias
RUN docker-php-ext-install \
    mysqli \
    pdo \
    pdo_mysql \
    gd \
    zip

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto
COPY . /var/www/html/

# Instalar dependências do Composer
RUN composer install --no-dev --optimize-autoloader

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/ \
    && chmod -R 777 /var/www/html/writable/ \
    && chmod -R 777 /var/www/html/uploads/

# Criar diretórios necessários
RUN mkdir -p /var/www/html/writable/logs \
    && mkdir -p /var/www/html/writable/cache \
    && mkdir -p /var/www/html/writable/session

# Expor porta 80
EXPOSE 80

# Comando de inicialização
CMD ["apache2-foreground"]