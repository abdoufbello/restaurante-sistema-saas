<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Log de Atividades com Multi-Tenancy
 */
class ActivityLogModel extends BaseMultiTenantModel
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false; // Logs não devem ser deletados
    protected $protectFields = true;
    
    protected $allowedFields = [
        'restaurant_id',
        'user_id',
        'customer_id',
        'session_id',
        'action',
        'entity_type',
        'entity_id',
        'entity_name',
        'description',
        'method',
        'url',
        'route',
        'controller',
        'function',
        'ip_address',
        'user_agent',
        'referer',
        'request_data',
        'response_data',
        'old_values',
        'new_values',
        'changes',
        'status_code',
        'execution_time',
        'memory_usage',
        'level',
        'category',
        'tags',
        'context',
        'metadata',
        'created_by',
        'created_at'
    ];
    
    // Timestamps
    protected $useTimestands = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null; // Logs não são atualizados
    protected $deletedField = null; // Logs não são deletados
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'action' => 'required|min_length[3]|max_length[100]',
        'entity_type' => 'permit_empty|max_length[50]',
        'description' => 'permit_empty|max_length[500]',
        'method' => 'permit_empty|in_list[GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD]',
        'url' => 'permit_empty|max_length[500]',
        'ip_address' => 'permit_empty|valid_ip',
        'level' => 'permit_empty|in_list[debug,info,notice,warning,error,critical,alert,emergency]',
        'category' => 'permit_empty|in_list[auth,crud,system,security,api,ui,report,backup,maintenance,integration]'
    ];
    
    protected $validationMessages = [
        'action' => [
            'required' => 'Ação é obrigatória',
            'min_length' => 'Ação deve ter pelo menos 3 caracteres',
            'max_length' => 'Ação deve ter no máximo 100 caracteres'
        ],
        'method' => [
            'in_list' => 'Método HTTP inválido'
        ],
        'ip_address' => [
            'valid_ip' => 'Endereço IP inválido'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'prepareLogData'];
    
    // Níveis de log
    protected $logLevels = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7
    ];
    
    // Categorias de atividade
    protected $activityCategories = [
        'auth' => 'Autenticação',
        'crud' => 'CRUD Operations',
        'system' => 'Sistema',
        'security' => 'Segurança',
        'api' => 'API',
        'ui' => 'Interface',
        'report' => 'Relatórios',
        'backup' => 'Backup',
        'maintenance' => 'Manutenção',
        'integration' => 'Integração'
    ];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['level'])) {
            $data['data']['level'] = 'info';
        }
        
        if (!isset($data['data']['category'])) {
            $data['data']['category'] = 'system';
        }
        
        if (!isset($data['data']['status_code'])) {
            $data['data']['status_code'] = 200;
        }
        
        // Captura informações da requisição se disponível
        if (!isset($data['data']['ip_address']) && function_exists('request')) {
            $request = request();
            $data['data']['ip_address'] = $request->getIPAddress();
            $data['data']['user_agent'] = $request->getUserAgent()->getAgentString();
            $data['data']['method'] = $request->getMethod();
            $data['data']['url'] = (string) $request->getUri();
        }
        
        // Captura session_id se disponível
        if (!isset($data['data']['session_id']) && session_status() === PHP_SESSION_ACTIVE) {
            $data['data']['session_id'] = session_id();
        }
        
        return $data;
    }
    
    /**
     * Prepara dados do log
     */
    protected function prepareLogData(array $data): array
    {
        // Serializa arrays/objetos para JSON
        $jsonFields = ['request_data', 'response_data', 'old_values', 'new_values', 'changes', 'context', 'metadata', 'tags'];
        
        foreach ($jsonFields as $field) {
            if (isset($data['data'][$field]) && !is_string($data['data'][$field])) {
                $data['data'][$field] = json_encode($data['data'][$field]);
            }
        }
        
        // Limita tamanho dos campos de texto
        $textFields = ['description' => 500, 'url' => 500, 'user_agent' => 500];
        
        foreach ($textFields as $field => $maxLength) {
            if (isset($data['data'][$field]) && strlen($data['data'][$field]) > $maxLength) {
                $data['data'][$field] = substr($data['data'][$field], 0, $maxLength - 3) . '...';
            }
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Registra atividade
     */
    public function logActivity(array $data): ?int
    {
        // Valida dados obrigatórios
        if (empty($data['action'])) {
            return null;
        }
        
        return $this->insert($data);
    }
    
    /**
     * Registra atividade de autenticação
     */
    public function logAuth(string $action, int $userId = null, array $context = []): ?int
    {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'category' => 'auth',
            'level' => in_array($action, ['login_failed', 'logout_forced']) ? 'warning' : 'info',
            'description' => $this->getAuthDescription($action),
            'context' => $context
        ];
        
        return $this->logActivity($data);
    }
    
    /**
     * Registra atividade CRUD
     */
    public function logCrud(string $action, string $entityType, int $entityId = null, string $entityName = null, array $oldValues = [], array $newValues = []): ?int
    {
        $changes = [];
        
        // Calcula mudanças se fornecidos valores antigos e novos
        if (!empty($oldValues) && !empty($newValues)) {
            foreach ($newValues as $key => $newValue) {
                $oldValue = $oldValues[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }
        }
        
        $data = [
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'category' => 'crud',
            'level' => $action === 'delete' ? 'warning' : 'info',
            'description' => $this->getCrudDescription($action, $entityType, $entityName),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changes' => $changes
        ];
        
        return $this->logActivity($data);
    }
    
    /**
     * Registra atividade de sistema
     */
    public function logSystem(string $action, string $description, string $level = 'info', array $context = []): ?int
    {
        $data = [
            'action' => $action,
            'category' => 'system',
            'level' => $level,
            'description' => $description,
            'context' => $context
        ];
        
        return $this->logActivity($data);
    }
    
    /**
     * Registra atividade de segurança
     */
    public function logSecurity(string $action, string $description, string $level = 'warning', array $context = []): ?int
    {
        $data = [
            'action' => $action,
            'category' => 'security',
            'level' => $level,
            'description' => $description,
            'context' => $context
        ];
        
        return $this->logActivity($data);
    }
    
    /**
     * Registra atividade de API
     */
    public function logApi(string $endpoint, string $method, int $statusCode, float $executionTime = null, array $requestData = [], array $responseData = []): ?int
    {
        $level = 'info';
        if ($statusCode >= 400 && $statusCode < 500) {
            $level = 'warning';
        } elseif ($statusCode >= 500) {
            $level = 'error';
        }
        
        $data = [
            'action' => 'api_request',
            'category' => 'api',
            'level' => $level,
            'description' => "API {$method} {$endpoint} - Status: {$statusCode}",
            'method' => $method,
            'url' => $endpoint,
            'status_code' => $statusCode,
            'execution_time' => $executionTime,
            'request_data' => $requestData,
            'response_data' => $responseData
        ];
        
        return $this->logActivity($data);
    }
    
    /**
     * Obtém logs por usuário
     */
    public function getLogsByUser(int $userId, array $filters = []): array
    {
        $builder = $this->where('user_id', $userId);
        
        return $this->applyFilters($builder, $filters)->findAll();
    }
    
    /**
     * Obtém logs por entidade
     */
    public function getLogsByEntity(string $entityType, int $entityId = null): array
    {
        $builder = $this->where('entity_type', $entityType);
        
        if ($entityId) {
            $builder->where('entity_id', $entityId);
        }
        
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }
    
    /**
     * Obtém logs por categoria
     */
    public function getLogsByCategory(string $category, array $filters = []): array
    {
        $builder = $this->where('category', $category);
        
        return $this->applyFilters($builder, $filters)->findAll();
    }
    
    /**
     * Obtém logs por nível
     */
    public function getLogsByLevel(string $level, array $filters = []): array
    {
        $builder = $this->where('level', $level);
        
        return $this->applyFilters($builder, $filters)->findAll();
    }
    
    /**
     * Obtém logs de erro
     */
    public function getErrorLogs(array $filters = []): array
    {
        $builder = $this->whereIn('level', ['error', 'critical', 'alert', 'emergency']);
        
        return $this->applyFilters($builder, $filters)->findAll();
    }
    
    /**
     * Obtém logs de segurança
     */
    public function getSecurityLogs(array $filters = []): array
    {
        $builder = $this->where('category', 'security');
        
        return $this->applyFilters($builder, $filters)->findAll();
    }
    
    /**
     * Busca avançada de logs
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        return $this->applyFilters($builder, $filters)->findAll();
    }
    
    /**
     * Aplica filtros ao builder
     */
    private function applyFilters($builder, array $filters = [])
    {
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('action', $filters['search'])
                             ->orLike('description', $filters['search'])
                             ->orLike('entity_name', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['user_id'])) {
            $builder = $builder->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['customer_id'])) {
            $builder = $builder->where('customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['action'])) {
            if (is_array($filters['action'])) {
                $builder = $builder->whereIn('action', $filters['action']);
            } else {
                $builder = $builder->where('action', $filters['action']);
            }
        }
        
        if (!empty($filters['entity_type'])) {
            if (is_array($filters['entity_type'])) {
                $builder = $builder->whereIn('entity_type', $filters['entity_type']);
            } else {
                $builder = $builder->where('entity_type', $filters['entity_type']);
            }
        }
        
        if (!empty($filters['category'])) {
            if (is_array($filters['category'])) {
                $builder = $builder->whereIn('category', $filters['category']);
            } else {
                $builder = $builder->where('category', $filters['category']);
            }
        }
        
        if (!empty($filters['level'])) {
            if (is_array($filters['level'])) {
                $builder = $builder->whereIn('level', $filters['level']);
            } else {
                $builder = $builder->where('level', $filters['level']);
            }
        }
        
        if (!empty($filters['method'])) {
            $builder = $builder->where('method', $filters['method']);
        }
        
        if (!empty($filters['status_code'])) {
            $builder = $builder->where('status_code', $filters['status_code']);
        }
        
        if (!empty($filters['ip_address'])) {
            $builder = $builder->where('ip_address', $filters['ip_address']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder = $builder->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder = $builder->where('created_at <=', $filters['date_to']);
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $limit = $filters['limit'] ?? 100;
        
        return $builder->orderBy($orderBy, $orderDir)->limit($limit);
    }
    
    /**
     * Obtém estatísticas dos logs
     */
    public function getLogStats(array $filters = []): array
    {
        $builder = $this;
        
        // Aplica filtros de data se fornecidos
        if (!empty($filters['date_from'])) {
            $builder = $builder->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder = $builder->where('created_at <=', $filters['date_to']);
        }
        
        $stats = [];
        
        // Total de logs
        $stats['total_logs'] = $builder->countAllResults(false);
        
        // Logs por categoria
        $categoryStats = $builder->select('category, COUNT(*) as count')
                               ->groupBy('category')
                               ->orderBy('count', 'DESC')
                               ->findAll();
        
        $stats['by_category'] = [];
        foreach ($categoryStats as $category) {
            $stats['by_category'][$category['category']] = $category['count'];
        }
        
        // Logs por nível
        $levelStats = $this->select('level, COUNT(*) as count')
                          ->groupBy('level')
                          ->orderBy('count', 'DESC')
                          ->findAll();
        
        $stats['by_level'] = [];
        foreach ($levelStats as $level) {
            $stats['by_level'][$level['level']] = $level['count'];
        }
        
        // Logs por ação
        $actionStats = $this->select('action, COUNT(*) as count')
                           ->groupBy('action')
                           ->orderBy('count', 'DESC')
                           ->limit(10)
                           ->findAll();
        
        $stats['top_actions'] = [];
        foreach ($actionStats as $action) {
            $stats['top_actions'][$action['action']] = $action['count'];
        }
        
        // Usuários mais ativos
        $userStats = $this->select('user_id, COUNT(*) as count')
                         ->where('user_id IS NOT NULL')
                         ->groupBy('user_id')
                         ->orderBy('count', 'DESC')
                         ->limit(10)
                         ->findAll();
        
        $stats['most_active_users'] = [];
        foreach ($userStats as $user) {
            $stats['most_active_users'][$user['user_id']] = $user['count'];
        }
        
        // Logs de hoje
        $stats['logs_today'] = $this->where('DATE(created_at)', date('Y-m-d'))->countAllResults();
        
        // Logs de erro hoje
        $stats['error_logs_today'] = $this->where('DATE(created_at)', date('Y-m-d'))
                                         ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
                                         ->countAllResults();
        
        return $stats;
    }
    
    /**
     * Obtém relatório de atividades por período
     */
    public function getActivityReport(string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        $dateFormat = match($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };
        
        $activities = $this->select("DATE_FORMAT(created_at, '{$dateFormat}') as period, category, level, COUNT(*) as count")
                          ->where('created_at >=', $startDate)
                          ->where('created_at <=', $endDate)
                          ->groupBy(['period', 'category', 'level'])
                          ->orderBy('period', 'ASC')
                          ->findAll();
        
        $report = [];
        
        foreach ($activities as $activity) {
            $period = $activity['period'];
            $category = $activity['category'];
            $level = $activity['level'];
            $count = $activity['count'];
            
            if (!isset($report[$period])) {
                $report[$period] = [
                    'total' => 0,
                    'by_category' => [],
                    'by_level' => []
                ];
            }
            
            $report[$period]['total'] += $count;
            $report[$period]['by_category'][$category] = ($report[$period]['by_category'][$category] ?? 0) + $count;
            $report[$period]['by_level'][$level] = ($report[$period]['by_level'][$level] ?? 0) + $count;
        }
        
        return $report;
    }
    
    /**
     * Limpa logs antigos
     */
    public function cleanOldLogs(int $daysOld = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        // Mantém logs de erro por mais tempo
        $errorLevels = ['error', 'critical', 'alert', 'emergency'];
        $errorCutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld * 2} days"));
        
        // Remove logs normais antigos
        $normalDeleted = $this->where('created_at <', $cutoffDate)
                             ->whereNotIn('level', $errorLevels)
                             ->delete();
        
        // Remove logs de erro muito antigos
        $errorDeleted = $this->where('created_at <', $errorCutoffDate)
                            ->whereIn('level', $errorLevels)
                            ->delete();
        
        return $normalDeleted + $errorDeleted;
    }
    
    /**
     * Exporta logs para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $logs = $this->advancedSearch($filters);
        
        $csv = "Data/Hora,Usuário,Ação,Entidade,Categoria,Nível,Descrição,IP,Método,URL,Status\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log['created_at'],
                $log['user_id'] ?? 'Sistema',
                str_replace(',', ';', $log['action']),
                $log['entity_type'] ?? '',
                $log['category'],
                $log['level'],
                str_replace(["\n", "\r", ','], [' ', ' ', ';'], $log['description'] ?? ''),
                $log['ip_address'] ?? '',
                $log['method'] ?? '',
                str_replace(',', ';', $log['url'] ?? ''),
                $log['status_code'] ?? ''
            );
        }
        
        return $csv;
    }
    
    /**
     * Obtém descrição para atividades de autenticação
     */
    private function getAuthDescription(string $action): string
    {
        $descriptions = [
            'login' => 'Usuário fez login no sistema',
            'login_failed' => 'Tentativa de login falhada',
            'logout' => 'Usuário fez logout do sistema',
            'logout_forced' => 'Usuário foi desconectado forçadamente',
            'password_changed' => 'Usuário alterou a senha',
            'password_reset' => 'Usuário solicitou reset de senha',
            'account_locked' => 'Conta do usuário foi bloqueada',
            'account_unlocked' => 'Conta do usuário foi desbloqueada'
        ];
        
        return $descriptions[$action] ?? "Atividade de autenticação: {$action}";
    }
    
    /**
     * Obtém descrição para atividades CRUD
     */
    private function getCrudDescription(string $action, string $entityType, string $entityName = null): string
    {
        $entityDisplay = $entityName ? "{$entityType} '{$entityName}'" : $entityType;
        
        $descriptions = [
            'create' => "Criou {$entityDisplay}",
            'read' => "Visualizou {$entityDisplay}",
            'update' => "Atualizou {$entityDisplay}",
            'delete' => "Excluiu {$entityDisplay}",
            'restore' => "Restaurou {$entityDisplay}"
        ];
        
        return $descriptions[$action] ?? "Operação {$action} em {$entityDisplay}";
    }
    
    /**
     * Obtém categorias disponíveis
     */
    public function getAvailableCategories(): array
    {
        return $this->activityCategories;
    }
    
    /**
     * Obtém níveis disponíveis
     */
    public function getAvailableLevels(): array
    {
        return array_keys($this->logLevels);
    }
}