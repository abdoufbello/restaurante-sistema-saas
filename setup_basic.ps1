# Setup Básico do Ambiente de Desenvolvimento (Sem Privilégios Admin)
# Para o projeto Restaurant Kiosk - Totem K2 Mini Rex Sunmi

Write-Host "=== SETUP BÁSICO DO AMBIENTE DE DESENVOLVIMENTO ===" -ForegroundColor Green
Write-Host "Projeto: Restaurant Kiosk para Totem K2 Mini Rex Sunmi" -ForegroundColor Yellow
Write-Host ""

# Verificar se estamos no diretório correto
$currentDir = Get-Location
Write-Host "Diretório atual: $currentDir" -ForegroundColor Cyan

# Criar estrutura básica do projeto
Write-Host "1. CRIANDO ESTRUTURA DO PROJETO" -ForegroundColor Yellow

$projectDirs = @(
    "admin",
    "kiosk",
    "api",
    "uploads",
    "uploads\dishes",
    "database",
    "docs",
    "writable",
    "writable\cache",
    "writable\logs",
    "writable\session",
    "writable\uploads",
    "public",
    "app",
    "app\Controllers",
    "app\Models",
    "app\Views"
)

foreach ($dir in $projectDirs) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "✓ Criado: $dir" -ForegroundColor Green
    } else {
        Write-Host "✓ Já existe: $dir" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "2. VERIFICANDO SISTEMA" -ForegroundColor Yellow

# Verificar se PHP está disponível
if (Get-Command php -ErrorAction SilentlyContinue) {
    $phpVersion = php -v
    Write-Host "✓ PHP encontrado" -ForegroundColor Green
} else {
    Write-Host "✗ PHP não encontrado" -ForegroundColor Red
}

# Verificar se Composer está disponível
if (Get-Command composer -ErrorAction SilentlyContinue) {
    Write-Host "✓ Composer encontrado" -ForegroundColor Green
} else {
    Write-Host "✗ Composer não encontrado" -ForegroundColor Red
}

# Verificar se Git está disponível
if (Get-Command git -ErrorAction SilentlyContinue) {
    Write-Host "✓ Git encontrado" -ForegroundColor Green
} else {
    Write-Host "✗ Git não encontrado" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== SETUP BÁSICO CONCLUÍDO ===" -ForegroundColor Green
Write-Host ""
Write-Host "Estrutura de diretórios criada com sucesso!" -ForegroundColor Yellow
Write-Host ""
Write-Host "Próximos Passos OBRIGATÓRIOS:" -ForegroundColor Yellow
Write-Host "1. Instalar XAMPP (como administrador)" -ForegroundColor White
Write-Host "2. Instalar Composer (como administrador)" -ForegroundColor White
Write-Host "3. Configurar arquivos do projeto" -ForegroundColor White
Write-Host "4. Configurar banco de dados MySQL" -ForegroundColor White
Write-Host ""
Write-Host "Links de Download:" -ForegroundColor Yellow
Write-Host "• XAMPP: https://www.apachefriends.org/download.html" -ForegroundColor Cyan
Write-Host "• Composer: https://getcomposer.org/download/" -ForegroundColor Cyan
Write-Host "• Git: https://git-scm.com/download/win" -ForegroundColor Cyan
Write-Host ""
Write-Host "Pressione qualquer tecla para continuar..." -ForegroundColor Gray
pause