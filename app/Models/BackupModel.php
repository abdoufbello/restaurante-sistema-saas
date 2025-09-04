<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Backups com Multi-Tenancy
 */
class BackupModel extends BaseMultiTenantModel
{
    protected $table = 'backups';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'restaurant_id',
        'name',
        'description',
        'type',
        'status',
        'file_path',
        'file_name',
        'file_size',
        'file_hash',
        'compression_type',
        'encryption_enabled',
        'encryption_key_hash',
        'backup_method',
        'backup_scope',
        'tables_included',
        'tables_excluded',
        'records_count',
        'start_time',
        'end_time',
        'duration',
        'progress_percentage',
        'error_message',
        'error_details',
        'storage_location',
        'storage_provider',
        'storage_path',
        'retention_days',
        'expires_at',
        'is_scheduled',
        'schedule_frequency',
        'schedule_time',
        'schedule_days',
        'next_run_at',
        'last_run_at',
        'run_count',
        'success_count',
        'failure_count',
        'metadata',
        'tags',
        'created_by',
        'updated_by'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'name' => 'required|min_length[3]|max_length[200]',
        'type' => 'required|in_list[full,incremental,differential,schema,data,custom]',
        'status' => 'permit_empty|in_list[pending,running,completed,failed,cancelled,expired]',
        'backup_method' => 'permit_empty|in_list[manual,automatic,scheduled,api]',
        'backup_scope' => 'permit_empty|in_list[database,files,complete,custom]',
        'compression_type' => 'permit_empty|in_list[none,gzip,zip,bzip2]',
        'storage_location' => 'permit_empty|in_list[local,s3,ftp,sftp,dropbox,google_drive]',
        'retention_days' => 'permit_empty|integer|greater_than[0]',
        'schedule_frequency' => 'permit_empty|in_list[daily,weekly,monthly,custom]'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Nome do backup é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 3 caracteres',
            'max_length' => 'Nome deve ter no máximo 200 caracteres'
        ],
        'type' => [
            'required' => 'Tipo de backup é obrigatório',
            'in_list' => 'Tipo de backup inválido'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generateBackupName', 'prepareBackupData'];
    protected $beforeUpdate = ['updateTimestamps', 'prepareBackupData'];
    
    // Tipos de backup
    protected $backupTypes = [
        'full' => 'Backup Completo',
        'incremental' => 'Backup Incremental',
        'differential' => 'Backup Diferencial',
        'schema' => 'Backup de Estrutura',
        'data' => 'Backup de Dados',
        'custom' => 'Backup Personalizado'
    ];
    
    // Status de backup
    protected $backupStatuses = [
        'pending' => 'Pendente',
        'running' => 'Executando',
        'completed' => 'Concluído',
        'failed' => 'Falhou',
        'cancelled' => 'Cancelado',
        'expired' => 'Expirado'
    ];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'pending';
        }
        
        if (!isset($data['data']['backup_method'])) {
            $data['data']['backup_method'] = 'manual';
        }
        
        if (!isset($data['data']['backup_scope'])) {
            $data['data']['backup_scope'] = 'database';
        }
        
        if (!isset($data['data']['compression_type'])) {
            $data['data']['compression_type'] = 'gzip';
        }
        
        if (!isset($data['data']['storage_location'])) {
            $data['data']['storage_location'] = 'local';
        }
        
        if (!isset($data['data']['retention_days'])) {
            $data['data']['retention_days'] = 30;
        }
        
        if (!isset($data['data']['encryption_enabled'])) {
            $data['data']['encryption_enabled'] = 0;
        }
        
        if (!isset($data['data']['is_scheduled'])) {
            $data['data']['is_scheduled'] = 0;
        }
        
        if (!isset($data['data']['progress_percentage'])) {
            $data['data']['progress_percentage'] = 0;
        }
        
        if (!isset($data['data']['run_count'])) {
            $data['data']['run_count'] = 0;
        }
        
        if (!isset($data['data']['success_count'])) {
            $data['data']['success_count'] = 0;
        }
        
        if (!isset($data['data']['failure_count'])) {
            $data['data']['failure_count'] = 0;
        }
        
        // Define data de expiração
        if (!isset($data['data']['expires_at']) && isset($data['data']['retention_days'])) {
            $data['data']['expires_at'] = date('Y-m-d H:i:s', strtotime("+{$data['data']['retention_days']} days"));
        }
        
        return $data;
    }
    
    /**
     * Gera nome do backup se não fornecido
     */
    protected function generateBackupName(array $data): array
    {
        if (!isset($data['data']['name']) || empty($data['data']['name'])) {
            $type = $data['data']['type'] ?? 'full';
            $timestamp = date('Y-m-d_H-i-s');
            $data['data']['name'] = "backup_{$type}_{$timestamp}";
        }
        
        return $data;
    }
    
    /**
     * Prepara dados do backup
     */
    protected function prepareBackupData(array $data): array
    {
        // Converte arrays para JSON
        if (isset($data['data']['tables_included']) && is_array($data['data']['tables_included'])) {
            $data['data']['tables_included'] = json_encode($data['data']['tables_included']);
        }
        
        if (isset($data['data']['tables_excluded']) && is_array($data['data']['tables_excluded'])) {
            $data['data']['tables_excluded'] = json_encode($data['data']['tables_excluded']);
        }
        
        if (isset($data['data']['schedule_days']) && is_array($data['data']['schedule_days'])) {
            $data['data']['schedule_days'] = json_encode($data['data']['schedule_days']);
        }
        
        if (isset($data['data']['metadata']) && is_array($data['data']['metadata'])) {
            $data['data']['metadata'] = json_encode($data['data']['metadata']);
        }
        
        if (isset($data['data']['tags']) && is_array($data['data']['tags'])) {
            $data['data']['tags'] = json_encode($data['data']['tags']);
        }
        
        return $data;
    }
    
    /**
     * Atualiza timestamps
     */
    protected function updateTimestamps(array $data): array
    {
        // Calcula duração se start_time e end_time estão definidos
        if (isset($data['data']['start_time']) && isset($data['data']['end_time'])) {
            $start = strtotime($data['data']['start_time']);
            $end = strtotime($data['data']['end_time']);
            $data['data']['duration'] = $end - $start;
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Cria novo backup
     */
    public function createBackup(array $backupData): int|false
    {
        // Valida dados obrigatórios
        $requiredFields = ['name', 'type'];
        
        foreach ($requiredFields as $field) {
            if (!isset($backupData[$field])) {
                throw new \InvalidArgumentException("Campo obrigatório '{$field}' não fornecido");
            }
        }
        
        return $this->insert($backupData);
    }
    
    /**
     * Inicia execução do backup
     */
    public function startBackup(int $backupId): bool
    {
        $backup = $this->find($backupId);
        
        if (!$backup) {
            return false;
        }
        
        return $this->update($backupId, [
            'status' => 'running',
            'start_time' => date('Y-m-d H:i:s'),
            'progress_percentage' => 0,
            'run_count' => $backup['run_count'] + 1
        ]);
    }
    
    /**
     * Atualiza progresso do backup
     */
    public function updateProgress(int $backupId, int $percentage, array $metadata = []): bool
    {
        $updateData = [
            'progress_percentage' => min(100, max(0, $percentage))
        ];
        
        if (!empty($metadata)) {
            $updateData['metadata'] = json_encode($metadata);
        }
        
        return $this->update($backupId, $updateData);
    }
    
    /**
     * Completa backup com sucesso
     */
    public function completeBackup(int $backupId, array $backupInfo = []): bool
    {
        $backup = $this->find($backupId);
        
        if (!$backup) {
            return false;
        }
        
        $updateData = [
            'status' => 'completed',
            'end_time' => date('Y-m-d H:i:s'),
            'progress_percentage' => 100,
            'success_count' => $backup['success_count'] + 1,
            'last_run_at' => date('Y-m-d H:i:s')
        ];
        
        // Adiciona informações do backup
        if (isset($backupInfo['file_path'])) {
            $updateData['file_path'] = $backupInfo['file_path'];
        }
        
        if (isset($backupInfo['file_name'])) {
            $updateData['file_name'] = $backupInfo['file_name'];
        }
        
        if (isset($backupInfo['file_size'])) {
            $updateData['file_size'] = $backupInfo['file_size'];
        }
        
        if (isset($backupInfo['file_hash'])) {
            $updateData['file_hash'] = $backupInfo['file_hash'];
        }
        
        if (isset($backupInfo['records_count'])) {
            $updateData['records_count'] = $backupInfo['records_count'];
        }
        
        // Calcula duração
        if ($backup['start_time']) {
            $start = strtotime($backup['start_time']);
            $end = time();
            $updateData['duration'] = $end - $start;
        }
        
        // Agenda próxima execução se for backup agendado
        if ($backup['is_scheduled']) {
            $updateData['next_run_at'] = $this->calculateNextRun($backup);
        }
        
        return $this->update($backupId, $updateData);
    }
    
    /**
     * Marca backup como falhou
     */
    public function failBackup(int $backupId, string $errorMessage, array $errorDetails = []): bool
    {
        $backup = $this->find($backupId);
        
        if (!$backup) {
            return false;
        }
        
        $updateData = [
            'status' => 'failed',
            'end_time' => date('Y-m-d H:i:s'),
            'error_message' => $errorMessage,
            'failure_count' => $backup['failure_count'] + 1,
            'last_run_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($errorDetails)) {
            $updateData['error_details'] = json_encode($errorDetails);
        }
        
        // Calcula duração
        if ($backup['start_time']) {
            $start = strtotime($backup['start_time']);
            $end = time();
            $updateData['duration'] = $end - $start;
        }
        
        return $this->update($backupId, $updateData);
    }
    
    /**
     * Cancela backup em execução
     */
    public function cancelBackup(int $backupId): bool
    {
        $backup = $this->find($backupId);
        
        if (!$backup || $backup['status'] !== 'running') {
            return false;
        }
        
        return $this->update($backupId, [
            'status' => 'cancelled',
            'end_time' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Obtém backups por status
     */
    public function getBackupsByStatus(string $status): array
    {
        return $this->where('status', $status)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém backups por tipo
     */
    public function getBackupsByType(string $type): array
    {
        return $this->where('type', $type)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém backups agendados
     */
    public function getScheduledBackups(): array
    {
        return $this->where('is_scheduled', 1)
                   ->where('status !=', 'running')
                   ->orderBy('next_run_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém backups que devem ser executados
     */
    public function getBackupsDueForExecution(): array
    {
        return $this->where('is_scheduled', 1)
                   ->where('status !=', 'running')
                   ->where('next_run_at <=', date('Y-m-d H:i:s'))
                   ->findAll();
    }
    
    /**
     * Obtém backups expirados
     */
    public function getExpiredBackups(): array
    {
        return $this->where('expires_at <=', date('Y-m-d H:i:s'))
                   ->where('status', 'completed')
                   ->findAll();
    }
    
    /**
     * Remove backups expirados
     */
    public function cleanExpiredBackups(): int
    {
        $expiredBackups = $this->getExpiredBackups();
        $deletedCount = 0;
        
        foreach ($expiredBackups as $backup) {
            // Remove arquivo físico se existir
            if (!empty($backup['file_path']) && file_exists($backup['file_path'])) {
                unlink($backup['file_path']);
            }
            
            // Marca como expirado ou remove do banco
            if ($this->update($backup['id'], ['status' => 'expired'])) {
                $deletedCount++;
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * Busca avançada de backups
     */
    public function advancedSearch(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $builder = $this;
        
        // Filtro por nome
        if (!empty($filters['name'])) {
            $builder = $builder->like('name', $filters['name']);
        }
        
        // Filtro por tipo
        if (!empty($filters['type'])) {
            $builder = $builder->where('type', $filters['type']);
        }
        
        // Filtro por status
        if (!empty($filters['status'])) {
            $builder = $builder->where('status', $filters['status']);
        }
        
        // Filtro por método
        if (!empty($filters['backup_method'])) {
            $builder = $builder->where('backup_method', $filters['backup_method']);
        }
        
        // Filtro por localização de armazenamento
        if (!empty($filters['storage_location'])) {
            $builder = $builder->where('storage_location', $filters['storage_location']);
        }
        
        // Filtro por período
        if (!empty($filters['start_date'])) {
            $builder = $builder->where('created_at >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder = $builder->where('created_at <=', $filters['end_date']);
        }
        
        // Filtro por agendamento
        if (isset($filters['is_scheduled'])) {
            $builder = $builder->where('is_scheduled', $filters['is_scheduled']);
        }
        
        return $builder->orderBy('created_at', 'DESC')
                      ->limit($limit, $offset)
                      ->findAll();
    }
    
    /**
     * Obtém estatísticas de backup
     */
    public function getBackupStats(string $period = '30 days'): array
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        // Total de backups
        $totalBackups = $this->where('created_at >=', $startDate)->countAllResults();
        
        // Backups por status
        $backupsByStatus = [];
        foreach ($this->backupStatuses as $status => $label) {
            $count = $this->where('status', $status)
                         ->where('created_at >=', $startDate)
                         ->countAllResults();
            $backupsByStatus[$status] = $count;
        }
        
        // Backups por tipo
        $backupsByType = [];
        foreach ($this->backupTypes as $type => $label) {
            $count = $this->where('type', $type)
                         ->where('created_at >=', $startDate)
                         ->countAllResults();
            $backupsByType[$type] = $count;
        }
        
        // Tamanho total dos backups
        $totalSize = $this->selectSum('file_size')
                         ->where('status', 'completed')
                         ->where('created_at >=', $startDate)
                         ->first()['file_size'] ?? 0;
        
        // Taxa de sucesso
        $successfulBackups = $this->where('status', 'completed')
                                 ->where('created_at >=', $startDate)
                                 ->countAllResults();
        
        $successRate = $totalBackups > 0 ? ($successfulBackups / $totalBackups) * 100 : 0;
        
        // Backups agendados
        $scheduledBackups = $this->where('is_scheduled', 1)->countAllResults();
        
        // Próximos backups
        $upcomingBackups = $this->where('is_scheduled', 1)
                               ->where('next_run_at >', date('Y-m-d H:i:s'))
                               ->orderBy('next_run_at', 'ASC')
                               ->limit(5)
                               ->findAll();
        
        return [
            'total_backups' => $totalBackups,
            'backups_by_status' => $backupsByStatus,
            'backups_by_type' => $backupsByType,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'success_rate' => round($successRate, 2),
            'scheduled_backups' => $scheduledBackups,
            'upcoming_backups' => $upcomingBackups,
            'period' => $period
        ];
    }
    
    /**
     * Exporta lista de backups para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $backups = $this->advancedSearch($filters, 10000);
        
        $csv = "Nome,Tipo,Status,Tamanho,Criado em,Concluído em,Duração\n";
        
        foreach ($backups as $backup) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                str_replace('"', '""', $backup['name']),
                $backup['type'],
                $backup['status'],
                $this->formatBytes($backup['file_size'] ?? 0),
                $backup['created_at'],
                $backup['end_time'] ?? '',
                $this->formatDuration($backup['duration'] ?? 0)
            );
        }
        
        return $csv;
    }
    
    /**
     * Calcula próxima execução do backup agendado
     */
    private function calculateNextRun(array $backup): ?string
    {
        if (!$backup['is_scheduled'] || !$backup['schedule_frequency']) {
            return null;
        }
        
        $now = time();
        $scheduleTime = $backup['schedule_time'] ?? '02:00:00';
        
        return match($backup['schedule_frequency']) {
            'daily' => date('Y-m-d H:i:s', strtotime("tomorrow {$scheduleTime}")),
            'weekly' => date('Y-m-d H:i:s', strtotime("next week {$scheduleTime}")),
            'monthly' => date('Y-m-d H:i:s', strtotime("first day of next month {$scheduleTime}")),
            default => null
        };
    }
    
    /**
     * Formata bytes em formato legível
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        
        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
    
    /**
     * Formata duração em formato legível
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return "{$minutes}m {$remainingSeconds}s";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return "{$hours}h {$remainingMinutes}m";
    }
    
    /**
     * Obtém tipos de backup disponíveis
     */
    public function getAvailableTypes(): array
    {
        return $this->backupTypes;
    }
    
    /**
     * Obtém status disponíveis
     */
    public function getAvailableStatuses(): array
    {
        return $this->backupStatuses;
    }
}