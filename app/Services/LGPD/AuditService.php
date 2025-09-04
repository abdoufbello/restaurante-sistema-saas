<?php

namespace App\Services\LGPD;

use CodeIgniter\Config\Services;
use CodeIgniter\Database\ConnectionInterface;
use App\Config\LGPD;
use Exception;

/**
 * Serviço de Auditoria LGPD
 * 
 * Responsável por registrar e monitorar todas as operações
 * relacionadas ao tratamento de dados pessoais
 */
class AuditService
{
    protected $db;
    protected $config;
    protected $session;
    
    public function __construct()
    {
        $this->db = Services::database();
        $this->config = new LGPD();
        $this->session = Services::session();
    }
    
    /**
     * Registra operação de dados pessoais
     */
    public function logDataOperation(array $operationData): bool
    {
        try {
            $logData = [
                'operation_type' => $operationData['operation'],
                'data_type' => $operationData['data_type'] ?? 'unknown',
                'user_id' => $this->getCurrentUserId(),
                'user_ip' => $this->getUserIP(),
                'user_agent' => $this->getUserAgent(),
                'operation_details' => json_encode($operationData),
                'timestamp' => date('Y-m-d H:i:s'),
                'session_id' => session_id(),
                'request_id' => $this->generateRequestId()
            ];
            
            return $this->db->table('lgpd_audit_logs')->insert($logData);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao registrar log de auditoria: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra acesso a dados pessoais
     */
    public function logDataAccess(array $accessData): bool
    {
        try {
            $logData = [
                'access_type' => 'data_access',
                'user_id' => $accessData['user_id'],
                'data_type' => $accessData['data_type'],
                'operation' => $accessData['operation'],
                'access_granted' => $accessData['access_granted'] ? 1 : 0,
                'denial_reason' => $accessData['error'] ?? null,
                'user_ip' => $this->getUserIP(),
                'user_agent' => $this->getUserAgent(),
                'timestamp' => $accessData['timestamp'],
                'session_id' => session_id(),
                'request_id' => $this->generateRequestId()
            ];
            
            return $this->db->table('lgpd_access_logs')->insert($logData);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao registrar log de acesso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra operações de consentimento
     */
    public function logConsentOperation(array $consentData): bool
    {
        try {
            $logData = [
                'consent_type' => $consentData['consent_type'],
                'operation' => $consentData['operation'], // granted, revoked, updated
                'data_subject' => $consentData['data_subject'],
                'purpose' => $consentData['purpose'] ?? null,
                'legal_basis' => $consentData['legal_basis'] ?? null,
                'consent_details' => json_encode($consentData),
                'user_ip' => $this->getUserIP(),
                'user_agent' => $this->getUserAgent(),
                'timestamp' => date('Y-m-d H:i:s'),
                'session_id' => session_id()
            ];
            
            return $this->db->table('lgpd_consent_logs')->insert($logData);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao registrar log de consentimento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra operações de apagamento de dados
     */
    public function logDataErasure(array $erasureData): bool
    {
        try {
            $logData = [
                'erasure_type' => 'data_erasure',
                'data_subject' => $erasureData['data_subject'],
                'data_type' => $erasureData['data_type'],
                'records_affected' => $erasureData['records_affected'],
                'soft_delete' => $erasureData['soft_delete'] ? 1 : 0,
                'erasure_reason' => 'LGPD_RIGHT_TO_ERASURE',
                'user_id' => $this->getCurrentUserId(),
                'user_ip' => $this->getUserIP(),
                'timestamp' => $erasureData['timestamp'],
                'session_id' => session_id()
            ];
            
            return $this->db->table('lgpd_erasure_logs')->insert($logData);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao registrar log de apagamento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra operações de portabilidade de dados
     */
    public function logDataPortability(array $portabilityData): bool
    {
        try {
            $logData = [
                'portability_type' => 'data_export',
                'data_subject' => $portabilityData['data_subject'],
                'data_types' => json_encode($portabilityData['data_types']),
                'total_records' => $portabilityData['total_records'],
                'export_format' => 'json',
                'user_id' => $this->getCurrentUserId(),
                'user_ip' => $this->getUserIP(),
                'timestamp' => $portabilityData['timestamp'],
                'session_id' => session_id()
            ];
            
            return $this->db->table('lgpd_portability_logs')->insert($logData);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao registrar log de portabilidade: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra violações de dados (data breach)
     */
    public function logDataBreach(array $breachData): bool
    {
        try {
            $logData = [
                'breach_id' => $this->generateBreachId(),
                'breach_type' => $breachData['type'],
                'severity_level' => $breachData['severity'] ?? 'medium',
                'affected_data_types' => json_encode($breachData['affected_data_types'] ?? []),
                'affected_records_count' => $breachData['affected_records'] ?? 0,
                'breach_description' => $breachData['description'],
                'detection_method' => $breachData['detection_method'] ?? 'system',
                'detected_at' => $breachData['detected_at'] ?? date('Y-m-d H:i:s'),
                'reported_by' => $breachData['reported_by'] ?? $this->getCurrentUserId(),
                'status' => 'detected',
                'requires_anpd_notification' => $this->requiresANPDNotification($breachData),
                'requires_data_subject_notification' => $this->requiresDataSubjectNotification($breachData),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->db->table('lgpd_data_breaches')->insert($logData);
            
            // Se for uma violação grave, dispara alertas
            if ($breachData['severity'] === 'high') {
                $this->triggerBreachAlerts($logData);
            }
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao registrar violação de dados: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gera relatório de auditoria
     */
    public function generateAuditReport(array $filters = []): array
    {
        try {
            $startDate = $filters['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $filters['end_date'] ?? date('Y-m-d');
            $dataType = $filters['data_type'] ?? null;
            $userId = $filters['user_id'] ?? null;
            
            $report = [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'summary' => $this->getAuditSummary($startDate, $endDate, $dataType, $userId),
                'operations' => $this->getOperationsReport($startDate, $endDate, $dataType, $userId),
                'access_patterns' => $this->getAccessPatternsReport($startDate, $endDate, $dataType, $userId),
                'consent_activities' => $this->getConsentActivitiesReport($startDate, $endDate),
                'data_breaches' => $this->getDataBreachesReport($startDate, $endDate),
                'compliance_metrics' => $this->getComplianceMetrics($startDate, $endDate),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            // Log da geração do relatório
            $this->logDataOperation([
                'operation' => 'audit_report_generated',
                'data_type' => 'audit',
                'filters' => $filters,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $report;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na geração do relatório de auditoria: ' . $e->getMessage());
            throw new Exception('Falha na geração do relatório de auditoria');
        }
    }
    
    /**
     * Monitora atividades suspeitas
     */
    public function detectSuspiciousActivity(): array
    {
        try {
            $suspiciousActivities = [];
            
            // Detecta múltiplos acessos negados
            $deniedAccesses = $this->db->query("
                SELECT user_id, COUNT(*) as denied_count, MAX(timestamp) as last_attempt
                FROM lgpd_access_logs 
                WHERE access_granted = 0 
                AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY user_id 
                HAVING denied_count >= 5
            ")->getResultArray();
            
            foreach ($deniedAccesses as $access) {
                $suspiciousActivities[] = [
                    'type' => 'multiple_denied_access',
                    'user_id' => $access['user_id'],
                    'count' => $access['denied_count'],
                    'last_attempt' => $access['last_attempt'],
                    'severity' => 'medium'
                ];
            }
            
            // Detecta acessos fora do horário comercial
            $afterHoursAccess = $this->db->query("
                SELECT user_id, COUNT(*) as access_count, MAX(timestamp) as last_access
                FROM lgpd_access_logs 
                WHERE (HOUR(timestamp) < 8 OR HOUR(timestamp) > 18)
                AND DATE(timestamp) = CURDATE()
                AND access_granted = 1
                GROUP BY user_id
                HAVING access_count >= 3
            ")->getResultArray();
            
            foreach ($afterHoursAccess as $access) {
                $suspiciousActivities[] = [
                    'type' => 'after_hours_access',
                    'user_id' => $access['user_id'],
                    'count' => $access['access_count'],
                    'last_access' => $access['last_access'],
                    'severity' => 'low'
                ];
            }
            
            // Detecta downloads massivos de dados
            $massiveDownloads = $this->db->query("
                SELECT user_id, COUNT(*) as download_count, SUM(total_records) as total_records
                FROM lgpd_portability_logs 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                GROUP BY user_id
                HAVING download_count >= 10 OR total_records >= 1000
            ")->getResultArray();
            
            foreach ($massiveDownloads as $download) {
                $suspiciousActivities[] = [
                    'type' => 'massive_data_download',
                    'user_id' => $download['user_id'],
                    'download_count' => $download['download_count'],
                    'total_records' => $download['total_records'],
                    'severity' => 'high'
                ];
            }
            
            // Log das atividades suspeitas detectadas
            if (!empty($suspiciousActivities)) {
                $this->logDataOperation([
                    'operation' => 'suspicious_activity_detected',
                    'data_type' => 'security',
                    'activities_count' => count($suspiciousActivities),
                    'activities' => $suspiciousActivities,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
            
            return $suspiciousActivities;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na detecção de atividades suspeitas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Limpa logs antigos conforme política de retenção
     */
    public function cleanupOldLogs(): bool
    {
        try {
            $retentionPeriod = $this->config->audit['log_retention_days'] ?? 2555; // 7 anos por padrão
            $cutoffDate = date('Y-m-d', strtotime("-{$retentionPeriod} days"));
            
            $tables = [
                'lgpd_audit_logs',
                'lgpd_access_logs',
                'lgpd_consent_logs',
                'lgpd_erasure_logs',
                'lgpd_portability_logs'
            ];
            
            $totalDeleted = 0;
            
            foreach ($tables as $table) {
                $deleted = $this->db->table($table)
                                   ->where('timestamp <', $cutoffDate)
                                   ->delete();
                $totalDeleted += $this->db->affectedRows();
            }
            
            // Log da limpeza
            $this->logDataOperation([
                'operation' => 'log_cleanup',
                'data_type' => 'audit',
                'cutoff_date' => $cutoffDate,
                'records_deleted' => $totalDeleted,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na limpeza de logs: ' . $e->getMessage());
            return false;
        }
    }
    
    // Métodos auxiliares privados
    
    private function getCurrentUserId(): ?int
    {
        return $this->session->get('user_id');
    }
    
    private function getUserIP(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
    
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }
    
    private function generateBreachId(): string
    {
        return 'BREACH_' . date('Ymd') . '_' . strtoupper(bin2hex(random_bytes(4)));
    }
    
    private function requiresANPDNotification(array $breachData): bool
    {
        // Critérios para notificação à ANPD
        return $breachData['severity'] === 'high' || 
               ($breachData['affected_records'] ?? 0) > 1000 ||
               in_array('sensitive', $breachData['affected_data_types'] ?? []);
    }
    
    private function requiresDataSubjectNotification(array $breachData): bool
    {
        // Critérios para notificação aos titulares
        return $breachData['severity'] === 'high' ||
               in_array('sensitive', $breachData['affected_data_types'] ?? []);
    }
    
    private function triggerBreachAlerts(array $breachData): void
    {
        // Implementa sistema de alertas para violações graves
        // Pode enviar emails, SMS, notificações push, etc.
        log_message('critical', 'VIOLAÇÃO DE DADOS DETECTADA: ' . json_encode($breachData));
    }
    
    private function getAuditSummary(string $startDate, string $endDate, ?string $dataType, ?int $userId): array
    {
        $builder = $this->db->table('lgpd_audit_logs')
                           ->where('timestamp >=', $startDate)
                           ->where('timestamp <=', $endDate . ' 23:59:59');
        
        if ($dataType) {
            $builder->where('data_type', $dataType);
        }
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        return [
            'total_operations' => $builder->countAllResults(false),
            'operations_by_type' => $builder->select('operation_type, COUNT(*) as count')
                                           ->groupBy('operation_type')
                                           ->get()
                                           ->getResultArray(),
            'operations_by_user' => $builder->select('user_id, COUNT(*) as count')
                                           ->groupBy('user_id')
                                           ->orderBy('count', 'DESC')
                                           ->limit(10)
                                           ->get()
                                           ->getResultArray()
        ];
    }
    
    private function getOperationsReport(string $startDate, string $endDate, ?string $dataType, ?int $userId): array
    {
        $builder = $this->db->table('lgpd_audit_logs')
                           ->where('timestamp >=', $startDate)
                           ->where('timestamp <=', $endDate . ' 23:59:59')
                           ->orderBy('timestamp', 'DESC')
                           ->limit(100);
        
        if ($dataType) {
            $builder->where('data_type', $dataType);
        }
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->get()->getResultArray();
    }
    
    private function getAccessPatternsReport(string $startDate, string $endDate, ?string $dataType, ?int $userId): array
    {
        $builder = $this->db->table('lgpd_access_logs')
                           ->where('timestamp >=', $startDate)
                           ->where('timestamp <=', $endDate . ' 23:59:59');
        
        if ($dataType) {
            $builder->where('data_type', $dataType);
        }
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        return [
            'total_accesses' => $builder->countAllResults(false),
            'successful_accesses' => $builder->where('access_granted', 1)->countAllResults(false),
            'denied_accesses' => $builder->where('access_granted', 0)->countAllResults(false),
            'access_by_hour' => $builder->select('HOUR(timestamp) as hour, COUNT(*) as count')
                                       ->groupBy('HOUR(timestamp)')
                                       ->orderBy('hour')
                                       ->get()
                                       ->getResultArray()
        ];
    }
    
    private function getConsentActivitiesReport(string $startDate, string $endDate): array
    {
        return $this->db->table('lgpd_consent_logs')
                       ->select('operation, COUNT(*) as count')
                       ->where('timestamp >=', $startDate)
                       ->where('timestamp <=', $endDate . ' 23:59:59')
                       ->groupBy('operation')
                       ->get()
                       ->getResultArray();
    }
    
    private function getDataBreachesReport(string $startDate, string $endDate): array
    {
        return $this->db->table('lgpd_data_breaches')
                       ->where('detected_at >=', $startDate)
                       ->where('detected_at <=', $endDate . ' 23:59:59')
                       ->orderBy('detected_at', 'DESC')
                       ->get()
                       ->getResultArray();
    }
    
    private function getComplianceMetrics(string $startDate, string $endDate): array
    {
        return [
            'consent_compliance_rate' => $this->calculateConsentComplianceRate($startDate, $endDate),
            'data_retention_compliance' => $this->checkDataRetentionCompliance(),
            'access_control_effectiveness' => $this->calculateAccessControlEffectiveness($startDate, $endDate),
            'incident_response_time' => $this->calculateIncidentResponseTime($startDate, $endDate)
        ];
    }
    
    private function calculateConsentComplianceRate(string $startDate, string $endDate): float
    {
        // Implementa cálculo da taxa de conformidade de consentimentos
        return 95.5; // Exemplo
    }
    
    private function checkDataRetentionCompliance(): array
    {
        // Implementa verificação de conformidade de retenção de dados
        return [
            'compliant_records' => 95,
            'non_compliant_records' => 5,
            'compliance_rate' => 95.0
        ];
    }
    
    private function calculateAccessControlEffectiveness(string $startDate, string $endDate): float
    {
        // Implementa cálculo da efetividade do controle de acesso
        return 98.2; // Exemplo
    }
    
    private function calculateIncidentResponseTime(string $startDate, string $endDate): array
    {
        // Implementa cálculo do tempo de resposta a incidentes
        return [
            'average_response_time_hours' => 2.5,
            'incidents_within_sla' => 18,
            'total_incidents' => 20
        ];
    }
}