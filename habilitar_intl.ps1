# Script para habilitar extensão intl no XAMPP
Write-Host "Habilitando extensão intl no PHP do XAMPP..." -ForegroundColor Green

try {
    # Caminho do arquivo php.ini
    $phpIniPath = "C:\xampp\php\php.ini"
    
    if (Test-Path $phpIniPath) {
        Write-Host "Arquivo php.ini encontrado: $phpIniPath" -ForegroundColor Yellow
        
        # Ler conteúdo do arquivo
        $content = Get-Content $phpIniPath
        
        # Verificar se a extensão já está habilitada
        $intlEnabled = $content | Where-Object { $_ -match "^extension=intl" }
        
        if ($intlEnabled) {
            Write-Host "Extensão intl já está habilitada!" -ForegroundColor Green
        } else {
            # Procurar pela linha comentada
            $intlCommented = $content | Where-Object { $_ -match "^;extension=intl" }
            
            if ($intlCommented) {
                Write-Host "Descomentando extensão intl..." -ForegroundColor Yellow
                $content = $content -replace "^;extension=intl", "extension=intl"
            } else {
                Write-Host "Adicionando extensão intl..." -ForegroundColor Yellow
                # Encontrar seção de extensões e adicionar
                $extensionIndex = -1
                for ($i = 0; $i -lt $content.Length; $i++) {
                    if ($content[$i] -match "^extension=") {
                        $extensionIndex = $i
                        break
                    }
                }
                
                if ($extensionIndex -ge 0) {
                    $newContent = @()
                    $newContent += $content[0..($extensionIndex-1)]
                    $newContent += "extension=intl"
                    $newContent += $content[$extensionIndex..($content.Length-1)]
                    $content = $newContent
                } else {
                    $content += "extension=intl"
                }
            }
            
            # Salvar arquivo
            $content | Set-Content $phpIniPath -Encoding UTF8
            Write-Host "Extensão intl habilitada com sucesso!" -ForegroundColor Green
        }
        
        Write-Host ""
        Write-Host "IMPORTANTE: Reinicie o Apache no XAMPP Control Panel para aplicar as alterações." -ForegroundColor Red
        Write-Host ""
        
        # Verificar se o Apache está rodando
        $apacheProcess = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
        if ($apacheProcess) {
            Write-Host "Apache está rodando. Você precisa reiniciá-lo no XAMPP Control Panel." -ForegroundColor Yellow
        }
        
    } else {
        Write-Host "Arquivo php.ini não encontrado em: $phpIniPath" -ForegroundColor Red
        Write-Host "Verifique se o XAMPP está instalado corretamente." -ForegroundColor Red
    }
    
} catch {
    Write-Host "Erro ao habilitar extensão intl: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Tente executar como administrador." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Pressione qualquer tecla para continuar..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")