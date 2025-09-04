<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Auditoria com Multi-Tenancy
 */
class AuditLogModel extends BaseMultiTenantModel
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false; // Logs de auditoria não devem ser excluídos
    protected $protectFields = true;
    
    protected $allowedFields = [
        'restaurant_id',
        'user_id',
        'event_type',
        'table_name',
        'record_id',
        'action',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'session_id',
        'request_id',
        'severity',
        'category',
        'module',
        'description',
        'metadata',
        'tags',
        'created_by'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null; // Logs de auditoria não são atualizados
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'event_type' => 'required|in_list[create,update,delete,login,logout,access,permission,system,security,api,export,import,backup,restore]',
        'action' => 'required|min_length[3]|max_length[100]',
        'severity' => 'permit_empty|in_list[low,medium,high,critical]',
        'category' => 'permit_empty|in_list[authentication,authorization,data,system,security,api,user,admin,financial,operational]'
    ];
    
    protected $validationMessages = [
        'event_type' => [
            'required' => 'Tipo de evento é obrigatório',
            'in_list' => 'Tipo de evento inválido'
        ],
        'action' => [
            'required' => 'Ação é obrigatória',
            'min_length' => 'Ação deve ter pelo menos 3 caracteres',
            'max_length' => 'Ação deve ter no máximo 100 caracteres'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'prepareAuditData'];
    
    // Tipos de evento
    protected $eventTypes = [
        'create' => 'Criação',
        'update' => 'Atualização',
        'delete' => 'Exclusão',
        'login' => 'Login',
        'logout' => 'Logout',
        'access' => 'Acesso',
        'permission' => 'Permissão',
        'system' => 'Sistema',
        'security' => 'Segurança',
        'api' => 'API',
        'export' => 'Exportação',
        'import' => 'Importação',
        'backup' => 'Backup',
        'restore' => 'Restauração'
    ];
    
    // Categorias
    protected $categories = [
        'authentication' => 'Autenticação',
        'authorization' => 'Autorização',
        'data' => 'Dados',
        'system' => 'Sistema',
        'security' => 'Segurança',
        'api' => 'API',
        'user' => 'Usuário',
        'admin' => 'Administração',
        'financial' => 'Financeiro',
        'operational' => 'Operacional'
    ];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['severity'])) {
            $data['data']['severity'] = 'medium';
        }
        
        if (!isset($data['data']['category'])) {
            $data['data']['category'] = 'data';
        }
        
        // Obtém informações da requisição
        $request = service('request');
        
        if (!isset($data['data']['ip_address'])) {
            $data['data']['ip_address'] = $request->getIPAddress();
        }
        
        if (!isset($data['data']['user_agent'])) {
            $data['data']['user_agent'] = $request->getUserAgent()->getAgentString();
        }
        
        if (!isset($data['data']['url'])) {
            $data['data']['url'] = current_url();
        }
        
        if (!isset($data['data']['method'])) {
            $data['data']['method'] = $request->getMethod();
        }
        
        if (!isset($data['data']['session_id'])) {
            $data['data']['session_id'] = session_id();
        }
        
        if (!isset($data['data']['request_id'])) {
            $data['data']['request_id'] = uniqid('req_', true);
        }
        
        return $data;
    }
    
    /**
     * Prepara dados de auditoria
     */
    protected function prepareAuditData(array $data): array
    {
        // Converte arrays/objetos para JSON
        if (isset($data['data']['old_values']) && is_array($data['data']['old_values'])) {
            $data['data']['old_values'] = json_encode($data['data']['old_values']);
        }
        
        if (isset($data['data']['new_values']) && is_array($data['data']['new_values'])) {
            $data['data']['new_values'] = json_encode($data['data']['new_values']);
        }
        
        if (isset($data['data']['changed_fields']) && is_array($data['data']['changed_fields'])) {
            $data['data']['changed_fields'] = json_encode($data['data']['changed_fields']);
        }
        
        if (isset($data['data']['metadata']) && is_array($data['data']['metadata'])) {
            $data['data']['metadata'] = json_encode($data['data']['metadata']);
        }
        
        if (isset($data['data']['tags']) && is_array($data['data']['tags'])) {
            $data['data']['tags'] = json_encode($data['data']['tags']);
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Registra evento de auditoria
     */
    public function logEvent(array $eventData): bool
    {
        // Dados obrigatórios
        $requiredFields = ['event_type', 'action'];
        
        foreach ($requiredFields as $field) {
            if (!isset($eventData[$field])) {
                throw new \InvalidArgumentException("Campo obrigatório '{$field}' não fornecido");
            }
        }
        
        // Define usuário atual se não fornecido
        if (!isset($eventData['user_id'])) {
            $eventData['user_id'] = $this->getCurrentUserId();
        }
        
        return $this->insert($eventData) !== false;
    }
    
    /**
     * Registra criação de registro
     */
    public function logCreate(string $tableName, int $recordId, array $newValues, array $metadata = []): bool
    {
        return $this->logEvent([
            'event_type' => 'create',
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => "Criou registro em {$tableName}",
            'new_values' => $newValues,
            'category' => 'data',
            'severity' => 'low',
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Registra atualização de registro
     */
    public function logUpdate(string $tableName, int $recordId, array $oldValues, array $newValues, array $metadata = []): bool
    {
        $changedFields = [];
        
        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;
            
            if ($oldValue !== $newValue) {
                $changedFields[] = $field;
            }
        }
        
        return $this->logEvent([
            'event_type' => 'update',
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => "Atualizou registro em {$tableName}",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'category' => 'data',
            'severity' => 'medium',
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Registra exclusão de registro
     */
    public function logDelete(string $tableName, int $recordId, array $oldValues, array $metadata = []): bool
    {
        return $this->logEvent([
            'event_type' => 'delete',
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => "Excluiu registro de {$tableName}",
            'old_values' => $oldValues,
            'category' => 'data',
            'severity' => 'high',
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Registra login de usuário
     */
    public function logLogin(int $userId, bool $success = true, array $metadata = []): bool
    {
        return $this->logEvent([
            'event_type' => 'login',
            'user_id' => $userId,
            'action' => $success ? 'Login realizado com sucesso' : 'Tentativa de login falhada',
            'category' => 'authentication',
            'severity' => $success ? 'low' : 'medium',
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Registra logout de usuário
     */
    public function logLogout(int $userId, array $metadata = []): bool
    {
        return $this->logEvent([
            'event_type' => 'logout',
            'user_id' => $userId,
            'action' => 'Logout realizado',
            'category' => 'authentication',
            'severity' => 'low',
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Registra acesso a recurso
     */
    public function logAccess(string $resource, string $action, bool $granted = true, array $metadata = []): bool
    {
        return $this->logEvent([
            'event_type' => 'access',
            'action' => $granted ? "Acesso concedido a {$resource}" : "Acesso negado a {$resource}",
            'module' => $resource,
            'category' => 'authorization',
            'severity' => $granted ? 'low' : 'medium',
            'metadata' => array_merge($metadata, [
                'resource' => $resource,
                'action' => $action,
                'granted' => $granted
            ])
        ]);
    }
    
    /**
     * Registra evento de segurança
     */
    public function logSecurity(string $action, string $severity = 'high', array $metadata = []): bool
    {
        return $this->logEvent([
            'event_type' => 'security',
            'action' => $action,
            'category' => 'security',
            'severity' => $severity,
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Registra chamada de API
     */
    public function logApiCall(string $endpoint, string $method, int $statusCode, array $metadata = []): bool
    {
        $severity = match(true) {
            $statusCode >= 500 => 'high',
            $statusCode >= 400 => 'medium',
            default => 'low'
        };
        
        return $this->logEvent([
            'event_type' => 'api',
            'action' => "Chamada API: {$method} {$endpoint}",
            'method' => $method,
            'category' => 'api',
            'severity' => $severity,
            'metadata' => array_merge($metadata, [
                'endpoint' => $endpoint,
                'status_code' => $statusCode
            ])
        ]);
    }
    
    /**
     * Obtém logs por usuário
     */
    public function getLogsByUser(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém logs por tabela
     */
    public function getLogsByTable(string $tableName, int $recordId = null, int $limit = 50): array
    {
        $builder = $this->where('table_name', $tableName);
        
        if ($recordId !== null) {
            $builder->where('record_id', $recordId);
        }
        
        return $builder->orderBy('created_at', 'DESC')
                      ->limit($limit)
                      ->findAll();
    }
    
    /**
     * Obtém logs por tipo de evento
     */
    public function getLogsByEventType(string $eventType, int $limit = 50): array
    {
        return $this->where('event_type', $eventType)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém logs por categoria
     */
    public function getLogsByCategory(string $category, int $limit = 50): array
    {
        return $this->where('category', $category)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém logs por severidade
     */
    public function getLogsBySeverity(string $severity, int $limit = 50): array
    {
        return $this->where('severity', $severity)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém logs por período
     */
    public function getLogsByPeriod(string $startDate, string $endDate, int $limit = 100): array
    {
        return $this->where('created_at >=', $startDate)
                   ->where('created_at <=', $endDate)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Busca avançada de logs
     */
    public function advancedSearch(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $builder = $this;
        
        // Filtro por usuário
        if (!empty($filters['user_id'])) {
            $builder = $builder->where('user_id', $filters['user_id']);
        }
        
        // Filtro por tipo de evento
        if (!empty($filters['event_type'])) {
            $builder = $builder->where('event_type', $filters['event_type']);
        }
        
        // Filtro por tabela
        if (!empty($filters['table_name'])) {
            $builder = $builder->where('table_name', $filters['table_name']);
        }
        
        // Filtro por categoria
        if (!empty($filters['category'])) {
            $builder = $builder->where('category', $filters['category']);
        }
        
        // Filtro por severidade
        if (!empty($filters['severity'])) {
            $builder = $builder->where('severity', $filters['severity']);
        }
        
        // Filtro por módulo
        if (!empty($filters['module'])) {
            $builder = $builder->where('module', $filters['module']);
        }
        
        // Filtro por IP
        if (!empty($filters['ip_address'])) {
            $builder = $builder->where('ip_address', $filters['ip_address']);
        }
        
        // Filtro por período
        if (!empty($filters['start_date'])) {
            $builder = $builder->where('created_at >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder = $builder->where('created_at <=', $filters['end_date']);
        }
        
        // Busca por texto
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('action', $filters['search'])
                             ->orLike('description', $filters['search'])
                             ->groupEnd();
        }
        
        return $builder->orderBy('created_at', 'DESC')
                      ->limit($limit, $offset)
                      ->findAll();
    }
    
    /**
     * Obtém estatísticas de auditoria
     */
    public function getAuditStats(string $period = '30 days'): array
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        // Total de eventos
        $totalEvents = $this->where('created_at >=', $startDate)->countAllResults();
        
        // Eventos por tipo
        $eventsByType = [];
        foreach ($this->eventTypes as $type => $label) {
            $count = $this->where('event_type', $type)
                         ->where('created_at >=', $startDate)
                         ->countAllResults();
            $eventsByType[$type] = $count;
        }
        
        // Eventos por categoria
        $eventsByCategory = [];
        foreach ($this->categories as $category => $label) {
            $count = $this->where('category', $category)
                         ->where('created_at >=', $startDate)
                         ->countAllResults();
            $eventsByCategory[$category] = $count;
        }
        
        // Eventos por severidade
        $eventsBySeverity = [
            'low' => $this->where('severity', 'low')->where('created_at >=', $startDate)->countAllResults(),
            'medium' => $this->where('severity', 'medium')->where('created_at >=', $startDate)->countAllResults(),
            'high' => $this->where('severity', 'high')->where('created_at >=', $startDate)->countAllResults(),
            'critical' => $this->where('severity', 'critical')->where('created_at >=', $startDate)->countAllResults()
        ];
        
        // Usuários mais ativos
        $activeUsers = $this->select('user_id, COUNT(*) as event_count')
                           ->where('created_at >=', $startDate)
                           ->where('user_id IS NOT NULL')
                           ->groupBy('user_id')
                           ->orderBy('event_count', 'DESC')
                           ->limit(10)
                           ->findAll();
        
        // Eventos por dia (últimos 30 dias)
        $dailyEvents = $this->select('DATE(created_at) as date, COUNT(*) as count')
                           ->where('created_at >=', $startDate)
                           ->groupBy('DATE(created_at)')
                           ->orderBy('date', 'ASC')
                           ->findAll();
        
        return [
            'total_events' => $totalEvents,
            'events_by_type' => $eventsByType,
            'events_by_category' => $eventsByCategory,
            'events_by_severity' => $eventsBySeverity,
            'active_users' => $activeUsers,
            'daily_events' => $dailyEvents,
            'period' => $period
        ];
    }
    
    /**
     * Gera relatório de auditoria
     */
    public function generateAuditReport(array $filters = []): array
    {
        $logs = $this->advancedSearch($filters, 1000);
        
        $report = [
            'summary' => [
                'total_events' => count($logs),
                'period' => [
                    'start' => $filters['start_date'] ?? null,
                    'end' => $filters['end_date'] ?? null
                ],
                'filters_applied' => $filters
            ],
            'events' => []
        ];
        
        foreach ($logs as $log) {
            $report['events'][] = [
                'timestamp' => $log['created_at'],
                'user_id' => $log['user_id'],
                'event_type' => $log['event_type'],
                'action' => $log['action'],
                'table_name' => $log['table_name'],
                'record_id' => $log['record_id'],
                'category' => $log['category'],
                'severity' => $log['severity'],
                'ip_address' => $log['ip_address'],
                'changes' => [
                    'old_values' => $log['old_values'] ? json_decode($log['old_values'], true) : null,
                    'new_values' => $log['new_values'] ? json_decode($log['new_values'], true) : null,
                    'changed_fields' => $log['changed_fields'] ? json_decode($log['changed_fields'], true) : null
                ]
            ];
        }
        
        return $report;
    }
    
    /**
     * Exporta logs para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $logs = $this->advancedSearch($filters, 10000);
        
        $csv = "Data/Hora,Usuário,Tipo,Ação,Tabela,Registro,Categoria,Severidade,IP\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $log['created_at'],
                $log['user_id'] ?? '',
                $log['event_type'],
                str_replace('"', '""', $log['action']),
                $log['table_name'] ?? '',
                $log['record_id'] ?? '',
                $log['category'],
                $log['severity'],
                $log['ip_address']
            );
        }
        
        return $csv;
    }
    
    /**
     * Remove logs antigos
     */
    public function cleanOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        return $this->where('created_at <', $cutoffDate)->delete();
    }
    
    /**
     * Obtém tipos de evento disponíveis
     */
    public function getAvailableEventTypes(): array
    {
        return $this->eventTypes;
    }
    
    /**
     * Obtém categorias disponíveis
     */
    public function getAvailableCategories(): array
    {
        return $this->categories;
    }
    
    /**
     * Obtém ID do usuário atual
     */
    private function getCurrentUserId(): ?int
    {
        // Implementar lógica para obter usuário atual da sessão
        $session = session();
        return $session->get('user_id');
    }
}