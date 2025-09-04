# Script para remover o serviço Windows do Prato Rápido
# Execute como Administrador

Write-Host "========================================" -ForegroundColor Red
Write-Host "  Removedor de Serviço - Prato Rápido" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Red
Write-Host ""

# Verificar se está executando como administrador
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "ERRO: Este script deve ser executado como Administrador" -ForegroundColor Red
    Write-Host "Clique com o botão direito no PowerShell e selecione 'Executar como administrador'" -ForegroundColor Yellow
    pause
    exit 1
}

# Definir variáveis
$serviceName = "PratoRapidoServer"
$serviceDisplayName = "Prato Rápido - Servidor PHP"
$projectPath = $PSScriptRoot
$wrapperPath = Join-Path $projectPath "service_wrapper.bat"

Write-Host "Removendo serviço Windows..." -ForegroundColor Yellow
Write-Host "Nome do serviço: $serviceName" -ForegroundColor Cyan
Write-Host ""

# Verificar se o serviço existe
$existingService = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
if (-not $existingService) {
    Write-Host "Serviço '$serviceName' não encontrado." -ForegroundColor Yellow
    Write-Host "Nada para remover." -ForegroundColor Green
} else {
    Write-Host "Parando serviço..." -ForegroundColor Yellow
    Stop-Service -Name $serviceName -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
    
    Write-Host "Removendo serviço..." -ForegroundColor Yellow
    $removeResult = sc.exe delete $serviceName
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Serviço removido com sucesso!" -ForegroundColor Green
    } else {
        Write-Host "ERRO: Falha ao remover o serviço" -ForegroundColor Red
        Write-Host "Código de erro: $LASTEXITCODE" -ForegroundColor Red
    }
}

# Remover arquivo wrapper se existir
if (Test-Path $wrapperPath) {
    Write-Host "Removendo arquivo wrapper..." -ForegroundColor Yellow
    Remove-Item $wrapperPath -Force
    Write-Host "Arquivo wrapper removido." -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  REMOÇÃO CONCLUÍDA!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

Write-Host "Pressione qualquer tecla para continuar..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")