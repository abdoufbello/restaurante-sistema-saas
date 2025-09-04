# Script para instalar XAMPP automaticamente
# Execute como Administrador

Write-Host "=== Instalador Automático do XAMPP ==="
Write-Host "Este script irá baixar e instalar o XAMPP para você."
Write-Host ""

# Verificar se está executando como administrador
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERRO: Este script precisa ser executado como Administrador!" -ForegroundColor Red
    Write-Host "Clique com o botão direito no PowerShell e selecione 'Executar como administrador'" -ForegroundColor Yellow
    Read-Host "Pressione Enter para sair"
    exit 1
}

# URLs e caminhos
$xamppUrl = "https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download"
$downloadPath = "$env:TEMP\xampp-installer.exe"
$installPath = "C:\xampp"

Write-Host "1. Baixando XAMPP..." -ForegroundColor Green
try {
    # Baixar XAMPP
    Invoke-WebRequest -Uri $xamppUrl -OutFile $downloadPath -UseBasicParsing
    Write-Host "   ✓ Download concluído!" -ForegroundColor Green
} catch {
    Write-Host "   ✗ Erro ao baixar XAMPP: $($_.Exception.Message)" -ForegroundColor Red
    Read-Host "Pressione Enter para sair"
    exit 1
}

Write-Host "2. Instalando XAMPP..." -ForegroundColor Green
try {
    # Instalar XAMPP silenciosamente
    Start-Process -FilePath $downloadPath -ArgumentList "--mode unattended --unattendedmodeui none --prefix $installPath" -Wait
    Write-Host "   ✓ XAMPP instalado com sucesso!" -ForegroundColor Green
} catch {
    Write-Host "   ✗ Erro na instalação: $($_.Exception.Message)" -ForegroundColor Red
    Read-Host "Pressione Enter para sair"
    exit 1
}

Write-Host "3. Configurando variáveis de ambiente..." -ForegroundColor Green
try {
    # Adicionar PHP ao PATH
    $phpPath = "$installPath\php"
    $currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
    
    if ($currentPath -notlike "*$phpPath*") {
        $newPath = "$currentPath;$phpPath"
        [Environment]::SetEnvironmentVariable("Path", $newPath, "Machine")
        Write-Host "   ✓ PHP adicionado ao PATH!" -ForegroundColor Green
    } else {
        Write-Host "   ✓ PHP já está no PATH!" -ForegroundColor Green
    }
} catch {
    Write-Host "   ✗ Erro ao configurar PATH: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "4. Limpando arquivos temporários..." -ForegroundColor Green
Remove-Item $downloadPath -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "=== INSTALAÇÃO CONCLUÍDA! ===" -ForegroundColor Green
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Yellow
Write-Host "1. Reinicie o PowerShell para carregar as novas variáveis de ambiente"
Write-Host "2. Execute: php --version (para verificar se o PHP está funcionando)"
Write-Host "3. Inicie o XAMPP Control Panel em: C:\xampp\xampp-control.exe"
Write-Host "4. Inicie os serviços Apache e MySQL no painel de controle"
Write-Host ""
Write-Host "Localização do XAMPP: C:\xampp" -ForegroundColor Cyan
Write-Host "Painel de Controle: C:\xampp\xampp-control.exe" -ForegroundColor Cyan
Write-Host ""
Read-Host "Pressione Enter para finalizar"