# Script para adicionar PHP ao PATH após instalar XAMPP
# Execute este script APÓS instalar o XAMPP

Write-Host "=== Configurador do PHP PATH ==="
Write-Host "Este script adiciona o PHP do XAMPP ao PATH do sistema."
Write-Host ""

# Verificar se o XAMPP foi instalado
$xamppPath = "C:\xampp"
$phpPath = "C:\xampp\php"

if (-not (Test-Path $xamppPath)) {
    Write-Host "ERRO: XAMPP nao encontrado em C:\xampp" -ForegroundColor Red
    Write-Host "Certifique-se de que o XAMPP foi instalado corretamente." -ForegroundColor Yellow
    Read-Host "Pressione Enter para sair"
    exit 1
}

if (-not (Test-Path $phpPath)) {
    Write-Host "ERRO: PHP nao encontrado em C:\xampp\php" -ForegroundColor Red
    Write-Host "Verifique se o XAMPP foi instalado completamente." -ForegroundColor Yellow
    Read-Host "Pressione Enter para sair"
    exit 1
}

Write-Host "XAMPP encontrado! Configurando PATH..." -ForegroundColor Green

try {
    # Obter PATH atual do usuário
    $currentUserPath = [Environment]::GetEnvironmentVariable("Path", "User")
    
    if ($currentUserPath -like "*$phpPath*") {
        Write-Host "PHP ja esta no PATH do usuario!" -ForegroundColor Green
    } else {
        # Adicionar PHP ao PATH do usuário
        if ($currentUserPath) {
            $newUserPath = "$currentUserPath;$phpPath"
        } else {
            $newUserPath = $phpPath
        }
        
        [Environment]::SetEnvironmentVariable("Path", $newUserPath, "User")
        Write-Host "PHP adicionado ao PATH do usuario com sucesso!" -ForegroundColor Green
    }
    
    # Atualizar PATH na sessão atual
    $env:Path += ";$phpPath"
    
    Write-Host ""
    Write-Host "Testando PHP..." -ForegroundColor Yellow
    
    # Testar se o PHP funciona
    $phpVersion = & "$phpPath\php.exe" --version 2>$null
    
    if ($phpVersion) {
        Write-Host "PHP funcionando corretamente!" -ForegroundColor Green
        Write-Host "Versao: $($phpVersion.Split([Environment]::NewLine)[0])" -ForegroundColor Cyan
    } else {
        Write-Host "Aviso: PHP pode nao estar funcionando corretamente." -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "Erro ao configurar PATH: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== CONFIGURACAO CONCLUIDA! ===" -ForegroundColor Green
Write-Host ""
Write-Host "Proximos passos:" -ForegroundColor Yellow
Write-Host "1. Feche e reabra o PowerShell"
Write-Host "2. Execute: php --version"
Write-Host "3. Inicie o XAMPP Control Panel: C:\xampp\xampp-control.exe"
Write-Host "4. Inicie Apache e MySQL no painel"
Write-Host "5. Execute as migracoes: php spark migrate"
Write-Host ""
Read-Host "Pressione Enter para finalizar"