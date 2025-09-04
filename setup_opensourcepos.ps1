# Script para Download e Configuração do OpenSourcePOS
# Adaptado para Restaurant Kiosk - Totem Sunmi K2 Mini Rex

Write-Host "=== CONFIGURAÇÃO DO OPENSOURCEPOS ===" -ForegroundColor Green
Write-Host "Adaptando para Restaurant Kiosk - Totem Sunmi K2 Mini Rex" -ForegroundColor Yellow
Write-Host ""

# Verificar se Git está disponível
$gitCommand = Get-Command git -ErrorAction SilentlyContinue
if (-not $gitCommand) {
    Write-Host "ERRO: Git não está instalado!" -ForegroundColor Red
    Write-Host "Instale o Git primeiro: https://git-scm.com/download/win" -ForegroundColor Yellow
    pause
    exit 1
}

Write-Host "1. BAIXANDO OPENSOURCEPOS" -ForegroundColor Yellow

# Criar diretório temporário para download
$tempDir = "temp_opensourcepos"
if (Test-Path $tempDir) {
    Remove-Item $tempDir -Recurse -Force
}

Write-Host "Clonando repositório do OpenSourcePOS..." -ForegroundColor Cyan
git clone https://github.com/opensourcepos/opensourcepos.git $tempDir

if (-not (Test-Path $tempDir)) {
    Write-Host "ERRO: Falha ao baixar OpenSourcePOS" -ForegroundColor Red
    pause
    exit 1
}

Write-Host "✓ OpenSourcePOS baixado com sucesso!" -ForegroundColor Green

Write-Host ""
Write-Host "2. COPIANDO ARQUIVOS ESSENCIAIS" -ForegroundColor Yellow

# Copiar arquivos essenciais
if (Test-Path "$tempDir\application") {
    Copy-Item "$tempDir\application" "application" -Recurse -Force
    Write-Host "✓ Copiado: application/" -ForegroundColor Green
}

if (Test-Path "$tempDir\system") {
    Copy-Item "$tempDir\system" "system" -Recurse -Force
    Write-Host "✓ Copiado: system/" -ForegroundColor Green
}

if (Test-Path "$tempDir\public") {
    Copy-Item "$tempDir\public" "public_ospos" -Recurse -Force
    Write-Host "✓ Copiado: public_ospos/" -ForegroundColor Green
}

if (Test-Path "$tempDir\composer.json") {
    Copy-Item "$tempDir\composer.json" "composer_ospos.json" -Force
    Write-Host "✓ Copiado: composer_ospos.json" -ForegroundColor Green
}

if (Test-Path "$tempDir\database") {
    Copy-Item "$tempDir\database" "database_ospos" -Recurse -Force
    Write-Host "✓ Copiado: database_ospos/" -ForegroundColor Green
}

Write-Host ""
Write-Host "3. CRIANDO ESTRUTURA CUSTOMIZADA" -ForegroundColor Yellow

# Criar diretórios customizados
$customDirs = @(
    "app\Config",
    "app\Controllers\Admin",
    "app\Controllers\Kiosk",
    "app\Controllers\Api",
    "app\Models\Restaurant",
    "app\Views\admin",
    "app\Views\kiosk",
    "app\Views\templates",
    "public\assets",
    "public\assets\css",
    "public\assets\js",
    "public\assets\images",
    "database\migrations",
    "database\seeds"
)

foreach ($dir in $customDirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "✓ Criado: $dir" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "4. LIMPANDO ARQUIVOS TEMPORÁRIOS" -ForegroundColor Yellow

if (Test-Path $tempDir) {
    Remove-Item $tempDir -Recurse -Force
    Write-Host "✓ Arquivos temporários removidos" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== CONFIGURAÇÃO CONCLUÍDA ===" -ForegroundColor Green
Write-Host ""
Write-Host "Arquivos do OpenSourcePOS integrados com sucesso!" -ForegroundColor Yellow
Write-Host ""
Write-Host "Próximos Passos:" -ForegroundColor Yellow
Write-Host "1. Instalar dependências: composer install" -ForegroundColor White
Write-Host "2. Copiar .env.example para .env" -ForegroundColor White
Write-Host "3. Configurar banco de dados no .env" -ForegroundColor White
Write-Host "4. Executar migrações" -ForegroundColor White
Write-Host "5. Testar o sistema" -ForegroundColor White
Write-Host ""
Write-Host "Pressione qualquer tecla para continuar..." -ForegroundColor Gray
pause