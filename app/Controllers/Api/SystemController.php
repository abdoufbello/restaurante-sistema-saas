<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

class SystemController extends BaseApiController
{
    protected $requiredPermissions = [
        'info' => 'system.read',
        'health' => null, // Público para monitoramento
        'logs' => 'system.logs',
        'cache' => 'system.cache',
        'database' => 'system.database',
        'maintenance' => 'system.maintenance'
    ];

    /**
     * Informações do sistema
     */
    public function info()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('system.read');
            
            $info = [
                'application' => [
                    'name' => 'Restaurant Management API',
                    'version' => '1.0.0',
                    'environment' => ENVIRONMENT,
                    'timezone' => date_default_timezone_get(),
                    'locale' => \Config\Services::request()->getLocale()
                ],
                'server' => [
                    'php_version' => PHP_VERSION,
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'operating_system' => PHP_OS,
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size')
                ],
                'database' => [
                    'driver' => \Config\Database::connect()->getDatabase(),
                    'version' => $this->getDatabaseVersion()
                ],
                'extensions' => [
                    'gd' => extension_loaded('gd'),
                    'curl' => extension_loaded('curl'),
                    'json' => extension_loaded('json'),
                    'mbstring' => extension_loaded('mbstring'),
                    'openssl' => extension_loaded('openssl'),
                    'pdo' => extension_loaded('pdo'),
                    'zip' => extension_loaded('zip')
                ],
                'performance' => [
                    'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                    'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
                    'execution_time' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms'
                ]
            ];
            
            return $this->successResponse($info, 'Informações do sistema carregadas');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao carregar informações: ' . $e->getMessage());
        }
    }
    
    /**
     * Health check do sistema
     */
    public function health()
    {
        try {
            $checks = [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'memory' => $this->checkMemory(),
                'dependencies' => $this->checkDependencies()
            ];
            
            $overallStatus = 'healthy';
            $issues = [];
            
            foreach ($checks as $check => $result) {
                if ($result['status'] === 'error') {
                    $overallStatus = 'unhealthy';
                    $issues[] = $check . ': ' . $result['message'];
                } elseif ($result['status'] === 'warning' && $overallStatus === 'healthy') {
                    $overallStatus = 'degraded';
                }
            }
            
            $health = [
                'status' => $overallStatus,
                'timestamp' => date('Y-m-d H:i:s'),
                'checks' => $checks,
                'issues' => $issues,
                'uptime' => $this->getUptime()
            ];
            
            $httpStatus = $overallStatus === 'healthy' ? 200 : ($overallStatus === 'degraded' ? 200 : 503);
            
            return $this->response
                ->setStatusCode($httpStatus)
                ->setJSON([
                    'success' => $overallStatus === 'healthy',
                    'data' => $health,
                    'message' => 'Health check executado'
                ]);
            
        } catch (\Exception $e) {
            return $this->response
                ->setStatusCode(503)
                ->setJSON([
                    'success' => false,
                    'data' => [
                        'status' => 'unhealthy',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'error' => $e->getMessage()
                    ],
                    'message' => 'Erro no health check'
                ]);
        }
    }
    
    /**
     * Logs do sistema
     */
    public function logs()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('system.logs');
            
            $level = $this->request->getGet('level'); // error, warning, info, debug
            $lines = min((int) ($this->request->getGet('lines') ?? 100), 1000);
            $search = $this->request->getGet('search');
            
            $logPath = WRITEPATH . 'logs/';
            $logFiles = glob($logPath . 'log-*.log');
            
            if (empty($logFiles)) {
                return $this->successResponse([
                    'logs' => [],
                    'total' => 0
                ], 'Nenhum log encontrado');
            }
            
            // Ordena por data (mais recente primeiro)
            usort($logFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $logs = [];
            $totalLines = 0;
            
            foreach ($logFiles as $logFile) {
                if ($totalLines >= $lines) break;
                
                $fileLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $fileLines = array_reverse($fileLines); // Mais recentes primeiro
                
                foreach ($fileLines as $line) {
                    if ($totalLines >= $lines) break;
                    
                    $parsedLog = $this->parseLogLine($line);
                    
                    if (!$parsedLog) continue;
                    
                    // Filtro por nível
                    if ($level && strtolower($parsedLog['level']) !== strtolower($level)) {
                        continue;
                    }
                    
                    // Filtro por busca
                    if ($search && stripos($parsedLog['message'], $search) === false) {
                        continue;
                    }
                    
                    $logs[] = $parsedLog;
                    $totalLines++;
                }
            }
            
            return $this->successResponse([
                'logs' => $logs,
                'total' => count($logs),
                'available_levels' => ['error', 'warning', 'info', 'debug']
            ], 'Logs carregados com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao carregar logs: ' . $e->getMessage());
        }
    }
    
    /**
     * Gerenciamento de cache
     */
    public function cache()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('system.cache');
            
            $action = $this->request->getGet('action'); // clear, info
            
            $cache = \Config\Services::cache();
            
            switch ($action) {
                case 'clear':
                    $cache->clean();
                    
                    $this->logActivity('cache_clear', []);
                    
                    return $this->successResponse(null, 'Cache limpo com sucesso');
                    
                case 'info':
                default:
                    $info = [
                        'handler' => get_class($cache),
                        'is_supported' => $cache->isSupported(),
                        'stats' => $this->getCacheStats()
                    ];
                    
                    return $this->successResponse($info, 'Informações do cache carregadas');
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro no gerenciamento de cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Informações do banco de dados
     */
    public function database()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('system.database');
            
            $db = \Config\Database::connect();
            
            // Informações básicas
            $info = [
                'connection' => [
                    'driver' => $db->getDatabase(),
                    'hostname' => $db->hostname ?? 'localhost',
                    'database' => $db->getDatabase(),
                    'version' => $this->getDatabaseVersion()
                ],
                'tables' => $this->getTableInfo($db),
                'performance' => $this->getDatabasePerformance($db)
            ];
            
            return $this->successResponse($info, 'Informações do banco carregadas');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao carregar informações do banco: ' . $e->getMessage());
        }
    }
    
    /**
     * Modo de manutenção
     */
    public function maintenance()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('system.maintenance');
            
            $action = $this->request->getPost('action'); // enable, disable, status
            $message = $this->request->getPost('message') ?? 'Sistema em manutenção';
            
            $maintenanceFile = WRITEPATH . 'maintenance.json';
            
            switch ($action) {
                case 'enable':
                    $maintenanceData = [
                        'enabled' => true,
                        'message' => $message,
                        'enabled_at' => date('Y-m-d H:i:s'),
                        'enabled_by' => $this->getCurrentUser()['id']
                    ];
                    
                    file_put_contents($maintenanceFile, json_encode($maintenanceData, JSON_PRETTY_PRINT));
                    
                    $this->logActivity('maintenance_enable', ['message' => $message]);
                    
                    return $this->successResponse($maintenanceData, 'Modo de manutenção ativado');
                    
                case 'disable':
                    if (file_exists($maintenanceFile)) {
                        unlink($maintenanceFile);
                    }
                    
                    $this->logActivity('maintenance_disable', []);
                    
                    return $this->successResponse([
                        'enabled' => false,
                        'disabled_at' => date('Y-m-d H:i:s')
                    ], 'Modo de manutenção desativado');
                    
                case 'status':
                default:
                    $status = [
                        'enabled' => file_exists($maintenanceFile),
                        'data' => null
                    ];
                    
                    if ($status['enabled']) {
                        $status['data'] = json_decode(file_get_contents($maintenanceFile), true);
                    }
                    
                    return $this->successResponse($status, 'Status de manutenção carregado');
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro no gerenciamento de manutenção: ' . $e->getMessage());
        }
    }
    
    // Métodos auxiliares privados
    
    private function checkDatabase()
    {
        try {
            $db = \Config\Database::connect();
            $result = $db->query('SELECT 1')->getResult();
            
            return [
                'status' => 'ok',
                'message' => 'Conexão com banco de dados OK',
                'response_time' => $this->measureResponseTime(function() use ($db) {
                    $db->query('SELECT 1');
                })
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erro na conexão com banco: ' . $e->getMessage()
            ];
        }
    }
    
    private function checkCache()
    {
        try {
            $cache = \Config\Services::cache();
            
            if (!$cache->isSupported()) {
                return [
                    'status' => 'warning',
                    'message' => 'Cache não suportado'
                ];
            }
            
            // Teste de escrita/leitura
            $testKey = 'health_check_' . time();
            $testValue = 'test';
            
            $cache->save($testKey, $testValue, 60);
            $retrieved = $cache->get($testKey);
            $cache->delete($testKey);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache funcionando corretamente'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Erro na operação de cache'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erro no cache: ' . $e->getMessage()
            ];
        }
    }
    
    private function checkStorage()
    {
        try {
            $writePath = WRITEPATH;
            $freeSpace = disk_free_space($writePath);
            $totalSpace = disk_total_space($writePath);
            
            $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
            
            $status = 'ok';
            $message = 'Armazenamento OK';
            
            if ($usagePercent > 90) {
                $status = 'error';
                $message = 'Armazenamento crítico: ' . round($usagePercent, 1) . '% usado';
            } elseif ($usagePercent > 80) {
                $status = 'warning';
                $message = 'Armazenamento alto: ' . round($usagePercent, 1) . '% usado';
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'free_space' => $this->formatBytes($freeSpace),
                'total_space' => $this->formatBytes($totalSpace),
                'usage_percent' => round($usagePercent, 1)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erro ao verificar armazenamento: ' . $e->getMessage()
            ];
        }
    }
    
    private function checkMemory()
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseBytes(ini_get('memory_limit'));
            
            $usagePercent = ($memoryUsage / $memoryLimit) * 100;
            
            $status = 'ok';
            $message = 'Uso de memória OK';
            
            if ($usagePercent > 90) {
                $status = 'error';
                $message = 'Uso de memória crítico: ' . round($usagePercent, 1) . '%';
            } elseif ($usagePercent > 80) {
                $status = 'warning';
                $message = 'Uso de memória alto: ' . round($usagePercent, 1) . '%';
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'current_usage' => $this->formatBytes($memoryUsage),
                'memory_limit' => $this->formatBytes($memoryLimit),
                'usage_percent' => round($usagePercent, 1)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erro ao verificar memória: ' . $e->getMessage()
            ];
        }
    }
    
    private function checkDependencies()
    {
        $required = ['gd', 'curl', 'json', 'mbstring', 'openssl', 'pdo'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (empty($missing)) {
            return [
                'status' => 'ok',
                'message' => 'Todas as dependências estão instaladas'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Extensões faltando: ' . implode(', ', $missing),
                'missing' => $missing
            ];
        }
    }
    
    private function getDatabaseVersion()
    {
        try {
            $db = \Config\Database::connect();
            return $db->getVersion();
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
    
    private function getUptime()
    {
        // Simples implementação baseada no arquivo de log mais antigo
        $logPath = WRITEPATH . 'logs/';
        $logFiles = glob($logPath . 'log-*.log');
        
        if (empty($logFiles)) {
            return 'Unknown';
        }
        
        $oldestFile = min(array_map('filemtime', $logFiles));
        $uptime = time() - $oldestFile;
        
        return $this->formatDuration($uptime);
    }
    
    private function parseLogLine($line)
    {
        // Formato: LEVEL - YYYY-MM-DD HH:MM:SS --> MESSAGE
        if (preg_match('/^(\w+)\s+-\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+-->\s+(.+)$/', $line, $matches)) {
            return [
                'level' => strtolower($matches[1]),
                'timestamp' => $matches[2],
                'message' => $matches[3]
            ];
        }
        
        return null;
    }
    
    private function getCacheStats()
    {
        // Implementação básica - pode ser expandida conforme o driver de cache
        return [
            'hits' => 0,
            'misses' => 0,
            'size' => 'Unknown'
        ];
    }
    
    private function getTableInfo($db)
    {
        try {
            $tables = $db->listTables();
            $tableInfo = [];
            
            foreach ($tables as $table) {
                $count = $db->table($table)->countAllResults();
                $tableInfo[] = [
                    'name' => $table,
                    'rows' => $count
                ];
            }
            
            return $tableInfo;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function getDatabasePerformance($db)
    {
        try {
            $start = microtime(true);
            $db->query('SELECT 1');
            $queryTime = (microtime(true) - $start) * 1000;
            
            return [
                'query_time' => round($queryTime, 2) . 'ms',
                'connection_status' => 'Connected'
            ];
        } catch (\Exception $e) {
            return [
                'query_time' => 'Error',
                'connection_status' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    private function measureResponseTime($callback)
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2) . 'ms';
    }
    
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function parseBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
    
    private function formatDuration($seconds)
    {
        $units = [
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1
        ];
        
        $result = [];
        
        foreach ($units as $name => $divisor) {
            $quot = intval($seconds / $divisor);
            if ($quot) {
                $result[] = $quot . ' ' . $name . ($quot > 1 ? 's' : '');
                $seconds -= $quot * $divisor;
            }
        }
        
        return implode(', ', array_slice($result, 0, 2)) ?: '0 seconds';
    }
}