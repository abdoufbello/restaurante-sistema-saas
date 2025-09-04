<?php

// Script para corrigir o problema do Logger do CodeIgniter
echo "Corrigindo problema do Logger...\n";

try {
    $loggerFile = __DIR__ . '/vendor/codeigniter4/framework/system/Log/Logger.php';
    
    if (!file_exists($loggerFile)) {
        echo "ERRO: Arquivo Logger.php não encontrado: $loggerFile\n";
        exit(1);
    }
    
    // Ler o conteúdo do arquivo
    $content = file_get_contents($loggerFile);
    
    // Verificar se já foi corrigido
    if (strpos($content, 'bool $debug = null') !== false) {
        echo "Logger já foi corrigido anteriormente.\n";
        exit(0);
    }
    
    // Fazer backup
    $backupFile = $loggerFile . '.backup';
    if (!file_exists($backupFile)) {
        copy($loggerFile, $backupFile);
        echo "Backup criado: $backupFile\n";
    }
    
    // Corrigir o problema
    $search = 'public function __construct($config, bool $debug = CI_DEBUG)';
    $replace = 'public function __construct($config, bool $debug = null)';
    
    $newContent = str_replace($search, $replace, $content);
    
    if ($newContent === $content) {
        echo "AVISO: Padrão não encontrado para correção.\n";
        echo "Procurando por: $search\n";
        
        // Tentar uma busca mais flexível
        if (preg_match('/public function __construct\(\$config,\s*bool\s*\$debug\s*=\s*CI_DEBUG\)/', $content)) {
            $newContent = preg_replace(
                '/public function __construct\(\$config,\s*bool\s*\$debug\s*=\s*CI_DEBUG\)/',
                'public function __construct($config, bool $debug = null)',
                $content
            );
        }
    }
    
    // Também precisamos corrigir a lógica dentro do construtor
    $debugLogic = 'if ($debug === null) {\n            $debug = defined(\'CI_DEBUG\') ? CI_DEBUG : false;\n        }';
    
    // Procurar onde inserir a lógica
    $constructorStart = strpos($newContent, 'public function __construct($config, bool $debug = null)');
    if ($constructorStart !== false) {
        $openBrace = strpos($newContent, '{', $constructorStart);
        if ($openBrace !== false) {
            $insertPos = $openBrace + 1;
            $newContent = substr($newContent, 0, $insertPos) . "\n        " . $debugLogic . "\n" . substr($newContent, $insertPos);
        }
    }
    
    if ($newContent !== $content) {
        file_put_contents($loggerFile, $newContent);
        echo "Logger corrigido com sucesso!\n";
        echo "Alterações feitas:\n";
        echo "1. Parâmetro \$debug alterado para valor padrão null\n";
        echo "2. Lógica adicionada para verificar CI_DEBUG em tempo de execução\n";
    } else {
        echo "Nenhuma alteração foi necessária.\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nCorreção concluída. Tente executar o servidor novamente.\n";