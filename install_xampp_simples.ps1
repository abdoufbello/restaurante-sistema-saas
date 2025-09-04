# Script simplificado para baixar XAMPP (sem necessidade de admin)
# Este script apenas baixa o XAMPP, você precisará instalar manualmente

Write-Host "=== Download do XAMPP ==="
Write-Host "Este script ira baixar o XAMPP para voce instalar."
Write-Host ""

# URL e caminho de download
$xamppUrl = "https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download"
$downloadPath = "$env:USERPROFILE\Downloads\xampp-installer.exe"

Write-Host "Baixando XAMPP..." -ForegroundColor Green
Write-Host "Destino: $downloadPath" -ForegroundColor Cyan
Write-Host ""

try {
    # Mostrar progresso do download
    $webClient = New-Object System.Net.WebClient
    $webClient.DownloadFile($xamppUrl, $downloadPath)
    
    Write-Host "Download concluido com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Proximos passos:" -ForegroundColor Yellow
    Write-Host "1. Vá para a pasta Downloads: $env:USERPROFILE\Downloads"
    Write-Host "2. Clique com botão direito em 'xampp-installer.exe'"
    Write-Host "3. Selecione 'Executar como administrador'"
    Write-Host "4. Siga o assistente de instalacao (deixe tudo padrao)"
    Write-Host "5. Instale na pasta C:\xampp (padrao)"
    Write-Host ""
    Write-Host "Apos a instalacao:" -ForegroundColor Yellow
    Write-Host "1. Execute: C:\xampp\xampp-control.exe"
    Write-Host "2. Inicie Apache e MySQL"
    Write-Host "3. Adicione C:\xampp\php ao PATH do sistema"
    Write-Host ""
    
    # Perguntar se quer abrir a pasta Downloads
    $response = Read-Host "Deseja abrir a pasta Downloads agora? (s/n)"
    if ($response -eq 's' -or $response -eq 'S' -or $response -eq 'sim') {
        Start-Process "explorer.exe" -ArgumentList "$env:USERPROFILE\Downloads"
    }
    
} catch {
    Write-Host "Erro ao baixar XAMPP: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Alternativa: Baixe manualmente em:" -ForegroundColor Yellow
    Write-Host "https://www.apachefriends.org/download.html" -ForegroundColor Cyan
}

Write-Host ""
Read-Host "Pressione Enter para finalizar"