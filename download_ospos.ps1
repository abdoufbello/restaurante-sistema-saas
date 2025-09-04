# Script simples para baixar OpenSourcePOS
Write-Host "Baixando OpenSourcePOS..." -ForegroundColor Green

# Verificar Git
if (!(Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "Git não encontrado. Instalando via winget..." -ForegroundColor Yellow
    winget install --id Git.Git -e --source winget
}

# Baixar OpenSourcePOS
git clone https://github.com/opensourcepos/opensourcepos.git temp_ospos

# Copiar arquivos essenciais
if (Test-Path "temp_ospos") {
    Write-Host "Copiando arquivos..." -ForegroundColor Yellow
    
    if (Test-Path "temp_ospos\application") {
        Copy-Item "temp_ospos\application" "application" -Recurse -Force
        Write-Host "Copiado: application" -ForegroundColor Green
    }
    
    if (Test-Path "temp_ospos\system") {
        Copy-Item "temp_ospos\system" "system" -Recurse -Force
        Write-Host "Copiado: system" -ForegroundColor Green
    }
    
    # Limpar
    Remove-Item "temp_ospos" -Recurse -Force
    Write-Host "Download concluído!" -ForegroundColor Green
} else {
    Write-Host "Erro no download" -ForegroundColor Red
}

Write-Host "Pressione Enter para continuar..."
Read-Host