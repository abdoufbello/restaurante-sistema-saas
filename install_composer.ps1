# Script para instalar Composer manualmente
Write-Host "Instalando Composer..."

try {
    # Baixar o instalador do Composer
    $composerSetup = "$env:TEMP\composer-setup.php"
    $composerInstaller = "https://getcomposer.org/installer"
    
    Write-Host "Baixando instalador do Composer..."
    Invoke-WebRequest -Uri $composerInstaller -OutFile $composerSetup
    
    # Criar diretório para o Composer
    $composerDir = "C:\Composer"
    if (!(Test-Path $composerDir)) {
        New-Item -ItemType Directory -Path $composerDir -Force
    }
    
    # Instalar o Composer
    Write-Host "Instalando Composer..."
    Set-Location $composerDir
    php $composerSetup --install-dir=$composerDir --filename=composer
    
    # Criar arquivo batch para facilitar o uso
    $batchContent = @"
@echo off
php "C:\Composer\composer" %*
"@
    $batchContent | Out-File -FilePath "C:\Composer\composer.bat" -Encoding ASCII
    
    # Adicionar ao PATH da sessão atual
    $env:PATH += ";C:\Composer"
    
    Write-Host "Composer instalado com sucesso!"
    
    # Testar instalação
    & "C:\Composer\composer.bat" --version
    
} catch {
    Write-Host "Erro durante a instalação: $($_.Exception.Message)"
    exit 1
}