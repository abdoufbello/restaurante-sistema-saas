<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306', 'root', '');
    echo "MySQL conectado com sucesso!\n";
    
    // Tentar criar o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ospos_saas");
    echo "Banco de dados 'ospos_saas' criado/verificado com sucesso!\n";
    
} catch(Exception $e) {
    echo "Erro MySQL: " . $e->getMessage() . "\n";
    echo "\nVerifique se o MySQL está rodando no XAMPP Control Panel.\n";
}
?>