# Script de Instalação do Ambiente de Desenvolvimento
# Para o projeto Restaurant Kiosk - Totem K2 Mini Rex Sunmi

Write-Host "=== INSTALAÇÃO DO AMBIENTE DE DESENVOLVIMENTO ===" -ForegroundColor Green
Write-Host "Projeto: Restaurant Kiosk para Totem K2 Mini Rex Sunmi" -ForegroundColor Yellow
Write-Host ""

# Verificar se está executando como administrador
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "ERRO: Este script precisa ser executado como Administrador!" -ForegroundColor Red
    Write-Host "Clique com botão direito no PowerShell e selecione 'Executar como administrador'" -ForegroundColor Yellow
    pause
    exit 1
}

# Função para baixar arquivos
function Download-File {
    param(
        [string]$Url,
        [string]$OutputPath
    )
    
    try {
        Write-Host "Baixando: $Url" -ForegroundColor Cyan
        Invoke-WebRequest -Uri $Url -OutFile $OutputPath -UseBasicParsing
        Write-Host "Download concluído: $OutputPath" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "Erro no download: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Criar diretório temporário
$tempDir = "C:\temp\restaurant-kiosk-setup"
if (!(Test-Path $tempDir)) {
    New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
}

Write-Host "1. INSTALANDO CHOCOLATEY (Gerenciador de Pacotes)" -ForegroundColor Yellow

# Verificar se Chocolatey está instalado
if (!(Get-Command choco -ErrorAction SilentlyContinue)) {
    Write-Host "Instalando Chocolatey..." -ForegroundColor Cyan
    Set-ExecutionPolicy Bypass -Scope Process -Force
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
    
    # Atualizar PATH
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    
    Write-Host "Chocolatey instalado com sucesso!" -ForegroundColor Green
} else {
    Write-Host "Chocolatey já está instalado." -ForegroundColor Green
}

Write-Host ""
Write-Host "2. INSTALANDO XAMPP" -ForegroundColor Yellow

# Verificar se XAMPP está instalado
if (!(Test-Path "C:\xampp\xampp-control.exe")) {
    Write-Host "Baixando XAMPP 8.2.12..." -ForegroundColor Cyan
    $xamppUrl = "https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download"
    $xamppInstaller = "$tempDir\xampp-installer.exe"
    
    if (Download-File -Url $xamppUrl -OutputPath $xamppInstaller) {
        Write-Host "Executando instalador do XAMPP..." -ForegroundColor Cyan
        Write-Host "ATENÇÃO: Selecione Apache, MySQL, PHP e phpMyAdmin na instalação!" -ForegroundColor Yellow
        
        Start-Process -FilePath $xamppInstaller -ArgumentList "/S" -Wait
        
        if (Test-Path "C:\xampp\xampp-control.exe") {
            Write-Host "XAMPP instalado com sucesso!" -ForegroundColor Green
        } else {
            Write-Host "Erro na instalação do XAMPP. Instale manualmente." -ForegroundColor Red
        }
    }
} else {
    Write-Host "XAMPP já está instalado." -ForegroundColor Green
}

Write-Host ""
Write-Host "3. INSTALANDO COMPOSER" -ForegroundColor Yellow

# Instalar Composer via Chocolatey
if (!(Get-Command composer -ErrorAction SilentlyContinue)) {
    Write-Host "Instalando Composer..." -ForegroundColor Cyan
    choco install composer -y
    
    # Atualizar PATH
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    
    Write-Host "Composer instalado com sucesso!" -ForegroundColor Green
} else {
    Write-Host "Composer já está instalado." -ForegroundColor Green
}

Write-Host ""
Write-Host "4. INSTALANDO GIT" -ForegroundColor Yellow

# Instalar Git via Chocolatey
if (!(Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "Instalando Git..." -ForegroundColor Cyan
    choco install git -y
    
    # Atualizar PATH
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    
    Write-Host "Git instalado com sucesso!" -ForegroundColor Green
} else {
    Write-Host "Git já está instalado." -ForegroundColor Green
}

Write-Host ""
Write-Host "5. CONFIGURANDO XAMPP" -ForegroundColor Yellow

# Configurar PHP.ini
$phpIniPath = "C:\xampp\php\php.ini"
if (Test-Path $phpIniPath) {
    Write-Host "Configurando PHP.ini..." -ForegroundColor Cyan
    
    # Backup do arquivo original
    Copy-Item $phpIniPath "$phpIniPath.backup" -Force
    
    # Ler conteúdo atual
    $phpIniContent = Get-Content $phpIniPath
    
    # Configurações necessárias
    $configurations = @(
        @{search = ";upload_max_filesize = 2M"; replace = "upload_max_filesize = 10M"},
        @{search = ";post_max_size = 8M"; replace = "post_max_size = 10M"},
        @{search = ";max_execution_time = 30"; replace = "max_execution_time = 300"},
        @{search = ";memory_limit = 128M"; replace = "memory_limit = 256M"},
        @{search = ";extension=gd"; replace = "extension=gd"},
        @{search = ";extension=mysqli"; replace = "extension=mysqli"},
        @{search = ";extension=pdo_mysql"; replace = "extension=pdo_mysql"},
        @{search = ";extension=mbstring"; replace = "extension=mbstring"},
        @{search = ";extension=curl"; replace = "extension=curl"},
        @{search = ";extension=zip"; replace = "extension=zip"},
        @{search = ";extension=intl"; replace = "extension=intl"},
        @{search = ";date.timezone ="; replace = "date.timezone = America/Sao_Paulo"}
    )
    
    # Aplicar configurações
    foreach ($config in $configurations) {
        $phpIniContent = $phpIniContent -replace [regex]::Escape($config.search), $config.replace
    }
    
    # Salvar arquivo
    $phpIniContent | Set-Content $phpIniPath -Encoding UTF8
    
    Write-Host "PHP.ini configurado com sucesso!" -ForegroundColor Green
}

# Configurar Apache httpd.conf
$httpdConfPath = "C:\xampp\apache\conf\httpd.conf"
if (Test-Path $httpdConfPath) {
    Write-Host "Configurando Apache httpd.conf..." -ForegroundColor Cyan
    
    # Backup do arquivo original
    Copy-Item $httpdConfPath "$httpdConfPath.backup" -Force
    
    # Ler conteúdo atual
    $httpdContent = Get-Content $httpdConfPath
    
    # Habilitar mod_rewrite
    $httpdContent = $httpdContent -replace "#LoadModule rewrite_module modules/mod_rewrite.so", "LoadModule rewrite_module modules/mod_rewrite.so"
    
    # Configurar AllowOverride
    $httpdContent = $httpdContent -replace "AllowOverride None", "AllowOverride All"
    
    # Salvar arquivo
    $httpdContent | Set-Content $httpdConfPath -Encoding UTF8
    
    Write-Host "Apache configurado com sucesso!" -ForegroundColor Green
}

Write-Host ""
Write-Host "6. CRIANDO ESTRUTURA DO PROJETO" -ForegroundColor Yellow

# Criar estrutura de diretórios
$projectPath = "C:\xampp\htdocs\restaurant-kiosk"
if (!(Test-Path $projectPath)) {
    Write-Host "Criando estrutura de diretórios..." -ForegroundColor Cyan
    
    $directories = @(
        "$projectPath",
        "$projectPath\admin",
        "$projectPath\kiosk",
        "$projectPath\api",
        "$projectPath\uploads",
        "$projectPath\uploads\dishes",
        "$projectPath\database",
        "$projectPath\docs",
        "$projectPath\writable",
        "$projectPath\writable\cache",
        "$projectPath\writable\logs",
        "$projectPath\writable\session",
        "$projectPath\writable\uploads"
    )
    
    foreach ($dir in $directories) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    
    # Configurar permissões
    icacls "$projectPath\writable" /grant Everyone:(OI)(CI)F /T
    icacls "$projectPath\uploads" /grant Everyone:(OI)(CI)F /T
    
    Write-Host "Estrutura de diretórios criada com sucesso!" -ForegroundColor Green
}

Write-Host ""
Write-Host "7. INICIANDO SERVIÇOS XAMPP" -ForegroundColor Yellow

# Iniciar XAMPP Control Panel
if (Test-Path "C:\xampp\xampp-control.exe") {
    Write-Host "Iniciando XAMPP Control Panel..." -ForegroundColor Cyan
    Start-Process "C:\xampp\xampp-control.exe"
    
    Write-Host "XAMPP Control Panel iniciado!" -ForegroundColor Green
    Write-Host "IMPORTANTE: Inicie os serviços Apache e MySQL no painel de controle." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "8. CRIANDO ARQUIVO DE TESTE" -ForegroundColor Yellow

# Criar arquivo de teste PHP
$testPhpContent = @"
<?php
echo "<h1>Ambiente de Desenvolvimento - Restaurant Kiosk</h1>";
echo "<h2>Informações do Sistema:</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . `$_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Document Root:</strong> " . `$_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>Teste de Conexão com MySQL:</h2>";
try {
    `$pdo = new PDO('mysql:host=localhost', 'root', '');
    echo "<p style='color: green;'><strong>✓ Conexão com MySQL: OK</strong></p>";
} catch (PDOException `$e) {
    echo "<p style='color: red;'><strong>✗ Erro na conexão com MySQL:</strong> " . `$e->getMessage() . "</p>";
}

echo "<h2>Extensões PHP Necessárias:</h2>";
`$extensions = ['gd', 'mysqli', 'pdo_mysql', 'mbstring', 'curl', 'zip', 'intl'];
foreach (`$extensions as `$ext) {
    if (extension_loaded(`$ext)) {
        echo "<p style='color: green;'>✓ `$ext</p>";
    } else {
        echo "<p style='color: red;'>✗ `$ext (não carregada)</p>";
    }
}

echo "<h2>Próximos Passos:</h2>";
echo "<ol>";
echo "<li>Verificar se Apache e MySQL estão rodando no XAMPP</li>";
echo "<li>Acessar phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
echo "<li>Criar banco de dados 'restaurant_kiosk'</li>";
echo "<li>Baixar e configurar OpenSourcePOS</li>";
echo "</ol>";
?>
"@

$testPhpContent | Set-Content "C:\xampp\htdocs\test_environment.php" -Encoding UTF8

Write-Host ""
Write-Host "=== INSTALAÇÃO CONCLUÍDA ===" -ForegroundColor Green
Write-Host ""
Write-Host "URLs de Teste:" -ForegroundColor Yellow
Write-Host "• Teste do Ambiente: http://localhost/test_environment.php" -ForegroundColor Cyan
Write-Host "• phpMyAdmin: http://localhost/phpmyadmin" -ForegroundColor Cyan
Write-Host "• Projeto: http://localhost/restaurant-kiosk" -ForegroundColor Cyan
Write-Host ""
Write-Host "Próximos Passos:" -ForegroundColor Yellow
Write-Host "1. Inicie Apache e MySQL no XAMPP Control Panel" -ForegroundColor White
Write-Host "2. Acesse http://localhost/test_environment.php para verificar" -ForegroundColor White
Write-Host "3. Execute o próximo script para configurar o OpenSourcePOS" -ForegroundColor White
Write-Host ""
Write-Host "Pressione qualquer tecla para continuar..." -ForegroundColor Gray
pause

# Limpar diretório temporário
Remove-Item $tempDir -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "Instalação finalizada!" -ForegroundColor Green