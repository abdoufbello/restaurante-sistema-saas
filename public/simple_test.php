<?php
// Teste simples das funcionalidades básicas
session_start();

// Verificar se os arquivos JSON existem
$dataDir = '../writable/data/';
$files = [
    'restaurants.json',
    'users.json',
    'categories.json',
    'dishes.json',
    'orders.json'
];

echo "<h1>Teste do Sistema SAAS</h1>";
echo "<h2>Verificação de Arquivos de Dados:</h2>";

foreach ($files as $file) {
    $path = $dataDir . $file;
    if (file_exists($path)) {
        $data = json_decode(file_get_contents($path), true);
        echo "<p>✅ {$file}: " . count($data) . " registros</p>";
    } else {
        echo "<p>❌ {$file}: Arquivo não encontrado</p>";
    }
}

echo "<h2>Links para Teste:</h2>";
echo "<ul>";
echo "<li><a href='dashboard.php'>Dashboard Principal</a></li>";
echo "<li><a href='kiosk.php'>Kiosk (Requer Login)</a></li>";
echo "<li><a href='kiosk_public.php?restaurant_id=1'>Kiosk Público</a></li>";
echo "<li><a href='categories.php'>Gerenciar Categorias</a></li>";
echo "<li><a href='dishes.php'>Gerenciar Pratos</a></li>";
echo "<li><a href='orders.php'>Gerenciar Pedidos</a></li>";
echo "</ul>";

echo "<h2>Informações do Sistema:</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
?>