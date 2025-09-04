# Script para instalar XAMPP
Write-Host "Baixando e instalando XAMPP..." -ForegroundColor Green

# URL do XAMPP
$xamppUrl = "https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download"
$xamppInstaller = "$env:TEMP\xampp-installer.exe"

try {
    # Baixar XAMPP
    Write-Host "Baixando XAMPP..."
    Invoke-WebRequest -Uri $xamppUrl -OutFile $xamppInstaller -UseBasicParsing
    
    if (Test-Path $xamppInstaller) {
        Write-Host "Download concluído. Iniciando instalação..."
        
        # Instalar XAMPP silenciosamente
        Start-Process -FilePath $xamppInstaller -ArgumentList "--mode unattended --unattendedmodeui none --prefix C:\xampp" -Wait
        
        Write-Host "XAMPP instalado com sucesso!" -ForegroundColor Green
        
        # Limpar arquivo temporário
        Remove-Item $xamppInstaller -Force
        
        # Verificar instalação
        if (Test-Path "C:\xampp\xampp-control.exe") {
            Write-Host "Instalação verificada com sucesso!" -ForegroundColor Green
            
            # Iniciar serviços
            Write-Host "Iniciando serviços Apache e MySQL..."
            Start-Process -FilePath "C:\xampp\apache_start.bat" -WindowStyle Hidden
            Start-Process -FilePath "C:\xampp\mysql_start.bat" -WindowStyle Hidden
            
            Write-Host "Aguardando serviços iniciarem..."
            Start-Sleep -Seconds 10
            
            Write-Host "XAMPP instalado e serviços iniciados!" -ForegroundColor Green
            Write-Host "Você pode acessar o painel de controle em: C:\xampp\xampp-control.exe" -ForegroundColor Yellow
            Write-Host "phpMyAdmin disponível em: http://localhost/phpmyadmin" -ForegroundColor Yellow
        } else {
            Write-Host "Erro: Instalação não foi concluída corretamente." -ForegroundColor Red
        }
    } else {
        Write-Host "Erro: Falha no download do XAMPP." -ForegroundColor Red
    }
} catch {
    Write-Host "Erro durante a instalação: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Tentando método alternativo..." -ForegroundColor Yellow
    
    # Método alternativo usando Chocolatey se disponível
    if (Get-Command choco -ErrorAction SilentlyContinue) {
        Write-Host "Tentando instalar via Chocolatey..."
        choco install xampp -y
    } else {
        Write-Host "Por favor, baixe e instale o XAMPP manualmente de: https://www.apachefriends.org/download.html" -ForegroundColor Yellow
    }
}

Write-Host "Script concluído." -ForegroundColor Green