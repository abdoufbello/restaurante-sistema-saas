<?php
/**
 * Restaurant Kiosk System
 * Sistema de Kiosk para Restaurantes - Totem Sunmi K2 Mini Rex
 * 
 * @author Restaurant Kiosk Team
 * @version 1.0.0
 */

// Verificar se o ambiente está configurado
$envExists = file_exists('.env');
$composerExists = file_exists('vendor/autoload.php');
$configExists = file_exists('composer.json');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Kiosk System - Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.8;
            font-size: 1.1em;
        }
        .content {
            padding: 30px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .status-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #ddd;
        }
        .status-card.success {
            border-left-color: #28a745;
        }
        .status-card.warning {
            border-left-color: #ffc107;
        }
        .status-card.error {
            border-left-color: #dc3545;
        }
        .status-icon {
            font-size: 1.5em;
            margin-right: 10px;
        }
        .success .status-icon {
            color: #28a745;
        }
        .warning .status-icon {
            color: #ffc107;
        }
        .error .status-icon {
            color: #dc3545;
        }
        .steps {
            background: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .steps h3 {
            margin-top: 0;
            color: #495057;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin: 10px 0;
            line-height: 1.6;
        }
        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .link-card {
            background: #007bff;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: background 0.3s;
        }
        .link-card:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
        }
        .specs {
            background: #f1f3f4;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .specs h3 {
            margin-top: 0;
            color: #495057;
        }
        .spec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .spec-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #007bff;
        }
        .spec-item strong {
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ Restaurant Kiosk System</h1>
            <p>Sistema de Autoatendimento para Totem Sunmi K2 Mini Rex</p>
        </div>
        
        <div class="content">
            <h2>📊 Status do Sistema</h2>
            
            <div class="status-grid">
                <div class="status-card <?php echo $configExists ? 'success' : 'error'; ?>">
                    <span class="status-icon"><?php echo $configExists ? '✅' : '❌'; ?></span>
                    <strong>Configuração do Projeto</strong><br>
                    <?php echo $configExists ? 'Arquivo composer.json encontrado' : 'Arquivo composer.json não encontrado'; ?>
                </div>
                
                <div class="status-card <?php echo $envExists ? 'success' : 'warning'; ?>">
                    <span class="status-icon"><?php echo $envExists ? '✅' : '⚠️'; ?></span>
                    <strong>Configuração do Ambiente</strong><br>
                    <?php echo $envExists ? 'Arquivo .env configurado' : 'Copie .env.example para .env'; ?>
                </div>
                
                <div class="status-card <?php echo $composerExists ? 'success' : 'error'; ?>">
                    <span class="status-icon"><?php echo $composerExists ? '✅' : '❌'; ?></span>
                    <strong>Dependências</strong><br>
                    <?php echo $composerExists ? 'Dependências instaladas' : 'Execute: composer install'; ?>
                </div>
                
                <div class="status-card <?php echo extension_loaded('mysqli') ? 'success' : 'error'; ?>">
                    <span class="status-icon"><?php echo extension_loaded('mysqli') ? '✅' : '❌'; ?></span>
                    <strong>MySQL</strong><br>
                    <?php echo extension_loaded('mysqli') ? 'Extensão MySQL disponível' : 'Extensão MySQL não encontrada'; ?>
                </div>
            </div>
            
            <div class="steps">
                <h3>🚀 Próximos Passos para Instalação</h3>
                <ol>
                    <li><strong>Instalar XAMPP:</strong> Baixe e instale o XAMPP 8.1+ com Apache, MySQL e PHP</li>
                    <li><strong>Instalar Composer:</strong> Baixe e instale o Composer para gerenciar dependências PHP</li>
                    <li><strong>Configurar Ambiente:</strong> Copie .env.example para .env e configure as variáveis</li>
                    <li><strong>Instalar Dependências:</strong> Execute <code>composer install</code> no terminal</li>
                    <li><strong>Configurar Banco:</strong> Crie o banco 'restaurant_kiosk' no MySQL</li>
                    <li><strong>Executar Migrações:</strong> Execute <code>php spark migrate</code> para criar as tabelas</li>
                </ol>
            </div>
            
            <h3>🔗 Links Úteis</h3>
            <div class="links">
                <a href="https://www.apachefriends.org/download.html" target="_blank" class="link-card">
                    📦 Download XAMPP
                </a>
                <a href="https://getcomposer.org/download/" target="_blank" class="link-card">
                    🎼 Download Composer
                </a>
                <a href="http://localhost/phpmyadmin" target="_blank" class="link-card">
                    🗄️ phpMyAdmin
                </a>
                <a href="https://codeigniter.com/user_guide/" target="_blank" class="link-card">
                    📚 CodeIgniter Docs
                </a>
            </div>
            
            <div class="specs">
                <h3>📱 Especificações do Totem Sunmi K2 Mini Rex</h3>
                <div class="spec-grid">
                    <div class="spec-item">
                        <strong>Tela:</strong><br>
                        15.6" HD Touch Screen
                    </div>
                    <div class="spec-item">
                        <strong>Sistema:</strong><br>
                        Android 7.1 (Sunmi OS)
                    </div>
                    <div class="spec-item">
                        <strong>Processador:</strong><br>
                        Qualcomm Snapdragon Octa-core
                    </div>
                    <div class="spec-item">
                        <strong>Memória:</strong><br>
                        4GB RAM + 16GB Storage
                    </div>
                    <div class="spec-item">
                        <strong>Impressora:</strong><br>
                        Térmica 58mm/80mm integrada
                    </div>
                    <div class="spec-item">
                        <strong>Scanner:</strong><br>
                        1D/2D Barcode Reader
                    </div>
                    <div class="spec-item">
                        <strong>Câmera:</strong><br>
                        3D Structured Light
                    </div>
                    <div class="spec-item">
                        <strong>Conectividade:</strong><br>
                        Wi-Fi, Bluetooth, USB
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <p><strong>Informações do Sistema:</strong></p>
                <p>PHP Version: <?php echo phpversion(); ?> | 
                Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?> | 
                Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></p>
                <p>Horário: <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>