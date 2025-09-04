# Script para instalar PHP no Windows
# Execute como Administrador

Write-Host "Instalando PHP..." -ForegroundColor Green

# Criar diretório para PHP
$phpDir = "C:\PHP"
if (!(Test-Path $phpDir)) {
    New-Item -ItemType Directory -Path $phpDir -Force
    Write-Host "Diretório C:\PHP criado" -ForegroundColor Yellow
}

# URL do PHP 8.3.24 (Thread Safe) - Link atualizado
$phpUrl = "https://windows.php.net/downloads/releases/php-8.3.24-Win32-vs16-x64.zip"
$zipFile = "$env:TEMP\php.zip"

try {
    # Baixar PHP
    Write-Host "Baixando PHP..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri $phpUrl -OutFile $zipFile -UseBasicParsing
    
    # Extrair PHP
    Write-Host "Extraindo PHP..." -ForegroundColor Yellow
    Expand-Archive -Path $zipFile -DestinationPath $phpDir -Force
    
    # Remover arquivo zip
    Remove-Item $zipFile -Force
    
    # Configurar php.ini
    $phpIniDev = "$phpDir\php.ini-development"
    $phpIni = "$phpDir\php.ini"
    
    if (Test-Path $phpIniDev) {
        Copy-Item $phpIniDev $phpIni -Force
        Write-Host "php.ini configurado" -ForegroundColor Yellow
        
        # Habilitar extensões necessárias
        $content = Get-Content $phpIni
        $content = $content -replace ';extension=mysqli', 'extension=mysqli'
        $content = $content -replace ';extension=pdo_mysql', 'extension=pdo_mysql'
        $content = $content -replace ';extension=mbstring', 'extension=mbstring'
        $content = $content -replace ';extension=openssl', 'extension=openssl'
        $content = $content -replace ';extension=curl', 'extension=curl'
        $content = $content -replace ';extension=fileinfo', 'extension=fileinfo'
        $content = $content -replace ';extension=gd', 'extension=gd'
        $content = $content -replace ';extension=intl', 'extension=intl'
        $content = $content -replace ';extension=zip', 'extension=zip'
        $content | Set-Content $phpIni
        
        Write-Host "Extensões PHP habilitadas" -ForegroundColor Yellow
    }
    
    # Adicionar PHP ao PATH do sistema
    $currentPath = [Environment]::GetEnvironmentVariable("PATH", "Machine")
    if ($currentPath -notlike "*$phpDir*") {
        $newPath = "$currentPath;$phpDir"
        [Environment]::SetEnvironmentVariable("PATH", $newPath, "Machine")
        Write-Host "PHP adicionado ao PATH do sistema" -ForegroundColor Yellow
    }
    
    # Atualizar PATH da sessão atual
    $env:PATH += ";$phpDir"
    
    Write-Host "PHP instalado com sucesso!" -ForegroundColor Green
    Write-Host "Versão instalada:" -ForegroundColor Cyan
    & "$phpDir\php.exe" -v
    
} catch {
    Write-Host "Erro durante a instalação: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host "\nPara usar o PHP, reinicie o terminal ou execute:" -ForegroundColor Cyan
Write-Host "refreshenv" -ForegroundColor Yellow
Write-Host "ou" -ForegroundColor Cyan
Write-Host "$env:PATH += ';C:\PHP'" -ForegroundColor Yellow