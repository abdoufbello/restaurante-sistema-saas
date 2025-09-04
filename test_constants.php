<?php

// Teste para verificar se as constantes estão sendo definidas corretamente
echo "Testando constantes do CodeIgniter...\n\n";

// Definir constantes de caminho necessárias
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', ROOTPATH . 'vendor/codeigniter4/framework/system/');
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);
define('WRITEPATH', ROOTPATH . 'writable' . DIRECTORY_SEPARATOR);

// Definir o ambiente
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

echo "Ambiente: " . ENVIRONMENT . "\n";

// Carregar as constantes manualmente
require_once __DIR__ . '/app/Config/Constants.php';

// Carregar o arquivo de boot do ambiente
$bootFile = __DIR__ . '/app/Config/Boot/' . ENVIRONMENT . '.php';
if (file_exists($bootFile)) {
    echo "Carregando arquivo de boot: $bootFile\n";
    require_once $bootFile;
} else {
    echo "ERRO: Arquivo de boot não encontrado: $bootFile\n";
}

// Verificar se as constantes foram definidas
echo "\nVerificando constantes:\n";
echo "CI_DEBUG definida: " . (defined('CI_DEBUG') ? 'SIM' : 'NÃO') . "\n";
if (defined('CI_DEBUG')) {
    echo "Valor de CI_DEBUG: " . (CI_DEBUG ? 'true' : 'false') . "\n";
}

echo "SHOW_DEBUG_BACKTRACE definida: " . (defined('SHOW_DEBUG_BACKTRACE') ? 'SIM' : 'NÃO') . "\n";

// Verificar extensões necessárias
echo "\nVerificando extensões:\n";
echo "Extensão intl: " . (extension_loaded('intl') ? 'HABILITADA' : 'DESABILITADA') . "\n";
echo "Extensão mbstring: " . (extension_loaded('mbstring') ? 'HABILITADA' : 'DESABILITADA') . "\n";

// Testar a classe Locale
echo "\nTestando classe Locale:\n";
if (class_exists('Locale')) {
    echo "Classe Locale: DISPONÍVEL\n";
    try {
        $locale = Locale::getDefault();
        echo "Locale padrão: $locale\n";
    } catch (Exception $e) {
        echo "Erro ao obter locale padrão: " . $e->getMessage() . "\n";
    }
} else {
    echo "Classe Locale: NÃO DISPONÍVEL\n";
    echo "ERRO: A classe Locale não está disponível. Verifique se a extensão intl está habilitada.\n";
}

echo "\nTeste concluído.\n";