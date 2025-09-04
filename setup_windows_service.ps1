# Script para configurar serviço Windows para Prato Rápido
# Execute como Administrador

Write-Host "========================================" -ForegroundColor Green
Write-Host "  Configurador de Serviço - Prato Rápido" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
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
$serviceDescription = "Servidor PHP para o sistema Prato Rápido"
$projectPath = $PSScriptRoot
$batchFile = Join-Path $projectPath "start_server.bat"

Write-Host "Configurando serviço Windows..." -ForegroundColor Yellow
Write-Host "Nome do serviço: $serviceName" -ForegroundColor Cyan
Write-Host "Caminho do projeto: $projectPath" -ForegroundColor Cyan
Write-Host ""

# Verificar se o arquivo batch existe
if (-not (Test-Path $batchFile)) {
    Write-Host "ERRO: Arquivo start_server.bat não encontrado em: $batchFile" -ForegroundColor Red
    pause
    exit 1
}

# Verificar se o serviço já existe
$existingService = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
if ($existingService) {
    Write-Host "Serviço já existe. Removendo serviço anterior..." -ForegroundColor Yellow
    Stop-Service -Name $serviceName -Force -ErrorAction SilentlyContinue
    sc.exe delete $serviceName
    Start-Sleep -Seconds 2
}

# Criar wrapper script para o serviço
$wrapperScript = @"
@echo off
cd /d "$projectPath\public"
php -S localhost:8080
"@

$wrapperPath = Join-Path $projectPath "service_wrapper.bat"
$wrapperScript | Out-File -FilePath $wrapperPath -Encoding ASCII

Write-Host "Criando serviço Windows..." -ForegroundColor Yellow

# Criar o serviço usando sc.exe
$createResult = sc.exe create $serviceName binPath= "\"$wrapperPath\"" DisplayName= "$serviceDisplayName" start= auto

if ($LASTEXITCODE -eq 0) {
    Write-Host "Serviço criado com sucesso!" -ForegroundColor Green
    
    # Configurar descrição do serviço
    sc.exe description $serviceName "$serviceDescription"
    
    # Configurar ações de falha
    sc.exe failure $serviceName reset= 86400 actions= restart/5000/restart/5000/restart/5000
    
    Write-Host "Iniciando serviço..." -ForegroundColor Yellow
    Start-Service -Name $serviceName
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  CONFIGURAÇÃO CONCLUÍDA COM SUCESSO!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "O serviço '$serviceDisplayName' foi criado e iniciado." -ForegroundColor Cyan
    Write-Host "O servidor estará disponível em: http://localhost:8080" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Para gerenciar o serviço:" -ForegroundColor Yellow
    Write-Host "- Parar: Stop-Service -Name $serviceName" -ForegroundColor White
    Write-Host "- Iniciar: Start-Service -Name $serviceName" -ForegroundColor White
    Write-Host "- Status: Get-Service -Name $serviceName" -ForegroundColor White
    Write-Host "- Remover: sc.exe delete $serviceName" -ForegroundColor White
    Write-Host ""
    
} else {
    Write-Host "ERRO: Falha ao criar o serviço" -ForegroundColor Red
    Write-Host "Código de erro: $LASTEXITCODE" -ForegroundColor Red
}

Write-Host "Pressione qualquer tecla para continuar..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")