<?php

namespace App\Services\LGPD;

use CodeIgniter\Config\Services;
use CodeIgniter\Database\ConnectionInterface;
use App\Config\LGPD;
use Exception;

/**
 * Serviço de Proteção de Dados Pessoais - LGPD
 * 
 * Implementa funcionalidades para proteção, criptografia, anonimização
 * e controle de acesso aos dados pessoais conforme LGPD
 */
class DataProtectionService
{
    protected $db;
    protected $config;
    protected $encrypter;
    protected $auditService;
    
    public function __construct()
    {
        $this->db = Services::database();
        $this->config = new LGPD();
        $this->encrypter = Services::encrypter();
        $this->auditService = new AuditService();
    }
    
    /**
     * Criptografa dados pessoais sensíveis
     */
    public function encryptPersonalData(array $data, string $dataType = 'general'): array
    {
        try {
            $encryptedData = [];
            $sensitiveFields = $this->getSensitiveFields($dataType);
            
            foreach ($data as $field => $value) {
                if (in_array($field, $sensitiveFields) && !empty($value)) {
                    $encryptedData[$field] = $this->encrypter->encrypt($value);
                    $encryptedData[$field . '_encrypted'] = true;
                } else {
                    $encryptedData[$field] = $value;
                }
            }
            
            // Log da operação de criptografia
            $this->auditService->logDataOperation([
                'operation' => 'encrypt',
                'data_type' => $dataType,
                'fields_encrypted' => array_intersect(array_keys($data), $sensitiveFields),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $encryptedData;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na criptografia de dados: ' . $e->getMessage());
            throw new Exception('Falha na criptografia dos dados pessoais');
        }
    }
    
    /**
     * Descriptografa dados pessoais
     */
    public function decryptPersonalData(array $data, string $dataType = 'general'): array
    {
        try {
            $decryptedData = [];
            $sensitiveFields = $this->getSensitiveFields($dataType);
            
            foreach ($data as $field => $value) {
                if (in_array($field, $sensitiveFields) && 
                    isset($data[$field . '_encrypted']) && 
                    $data[$field . '_encrypted'] === true) {
                    $decryptedData[$field] = $this->encrypter->decrypt($value);
                } else {
                    $decryptedData[$field] = $value;
                }
            }
            
            // Remove flags de criptografia
            $decryptedData = array_filter($decryptedData, function($key) {
                return !str_ends_with($key, '_encrypted');
            }, ARRAY_FILTER_USE_KEY);
            
            return $decryptedData;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na descriptografia de dados: ' . $e->getMessage());
            throw new Exception('Falha na descriptografia dos dados pessoais');
        }
    }
    
    /**
     * Anonimiza dados pessoais
     */
    public function anonymizeData(array $data, string $dataType = 'general'): array
    {
        try {
            $anonymizedData = $data;
            $anonymizationRules = $this->getAnonymizationRules($dataType);
            
            foreach ($anonymizationRules as $field => $rule) {
                if (isset($data[$field])) {
                    $anonymizedData[$field] = $this->applyAnonymizationRule($data[$field], $rule);
                }
            }
            
            // Marca dados como anonimizados
            $anonymizedData['_anonymized'] = true;
            $anonymizedData['_anonymized_at'] = date('Y-m-d H:i:s');
            
            // Log da operação
            $this->auditService->logDataOperation([
                'operation' => 'anonymize',
                'data_type' => $dataType,
                'fields_anonymized' => array_keys($anonymizationRules),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $anonymizedData;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na anonimização de dados: ' . $e->getMessage());
            throw new Exception('Falha na anonimização dos dados');
        }
    }
    
    /**
     * Pseudonimiza dados pessoais
     */
    public function pseudonymizeData(array $data, string $dataType = 'general'): array
    {
        try {
            $pseudonymizedData = $data;
            $pseudonymizationFields = $this->getPseudonymizationFields($dataType);
            $pseudonymMap = [];
            
            foreach ($pseudonymizationFields as $field) {
                if (isset($data[$field])) {
                    $pseudonym = $this->generatePseudonym($data[$field], $field);
                    $pseudonymizedData[$field] = $pseudonym;
                    $pseudonymMap[$field] = [
                        'original_hash' => hash('sha256', $data[$field]),
                        'pseudonym' => $pseudonym
                    ];
                }
            }
            
            // Salva mapeamento de pseudônimos (para possível reversão)
            if (!empty($pseudonymMap)) {
                $this->savePseudonymMapping($pseudonymMap, $dataType);
            }
            
            $pseudonymizedData['_pseudonymized'] = true;
            $pseudonymizedData['_pseudonymized_at'] = date('Y-m-d H:i:s');
            
            // Log da operação
            $this->auditService->logDataOperation([
                'operation' => 'pseudonymize',
                'data_type' => $dataType,
                'fields_pseudonymized' => $pseudonymizationFields,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $pseudonymizedData;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na pseudonimização de dados: ' . $e->getMessage());
            throw new Exception('Falha na pseudonimização dos dados');
        }
    }
    
    /**
     * Verifica se o usuário tem permissão para acessar dados específicos
     */
    public function checkDataAccess(int $userId, string $dataType, string $operation = 'read'): bool
    {
        try {
            // Verifica permissões básicas do usuário
            $userPermissions = $this->getUserPermissions($userId);
            
            // Verifica se tem permissão para o tipo de dados
            $dataPermissionKey = "data_{$dataType}_{$operation}";
            if (!in_array($dataPermissionKey, $userPermissions)) {
                return false;
            }
            
            // Verifica restrições adicionais baseadas no contexto
            $contextRestrictions = $this->getContextRestrictions($userId, $dataType);
            if (!empty($contextRestrictions)) {
                foreach ($contextRestrictions as $restriction) {
                    if (!$this->evaluateRestriction($restriction, $operation)) {
                        return false;
                    }
                }
            }
            
            // Log do acesso
            $this->auditService->logDataAccess([
                'user_id' => $userId,
                'data_type' => $dataType,
                'operation' => $operation,
                'access_granted' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na verificação de acesso: ' . $e->getMessage());
            
            // Log do acesso negado
            $this->auditService->logDataAccess([
                'user_id' => $userId,
                'data_type' => $dataType,
                'operation' => $operation,
                'access_granted' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return false;
        }
    }
    
    /**
     * Aplica mascaramento de dados para exibição
     */
    public function maskSensitiveData(array $data, string $dataType = 'general', int $userId = null): array
    {
        $maskedData = $data;
        $maskingRules = $this->getMaskingRules($dataType);
        
        // Verifica se o usuário tem permissão para ver dados não mascarados
        $canViewUnmasked = $userId ? $this->checkDataAccess($userId, $dataType, 'view_unmasked') : false;
        
        if (!$canViewUnmasked) {
            foreach ($maskingRules as $field => $rule) {
                if (isset($data[$field])) {
                    $maskedData[$field] = $this->applyMaskingRule($data[$field], $rule);
                }
            }
        }
        
        return $maskedData;
    }
    
    /**
     * Remove dados pessoais (direito ao esquecimento)
     */
    public function erasePersonalData(string $dataSubject, string $dataType, array $options = []): bool
    {
        try {
            $this->db->transStart();
            
            // Identifica todos os registros relacionados ao titular
            $records = $this->findDataSubjectRecords($dataSubject, $dataType);
            
            foreach ($records as $table => $recordIds) {
                if ($options['soft_delete'] ?? true) {
                    // Soft delete - marca como removido mas mantém para auditoria
                    $this->db->table($table)
                           ->whereIn('id', $recordIds)
                           ->update([
                               'deleted_at' => date('Y-m-d H:i:s'),
                               'deletion_reason' => 'LGPD_RIGHT_TO_ERASURE',
                               'data_subject' => $dataSubject
                           ]);
                } else {
                    // Hard delete - remove permanentemente
                    $this->db->table($table)->whereIn('id', $recordIds)->delete();
                }
            }
            
            // Log da operação de apagamento
            $this->auditService->logDataErasure([
                'data_subject' => $dataSubject,
                'data_type' => $dataType,
                'records_affected' => array_sum(array_map('count', $records)),
                'soft_delete' => $options['soft_delete'] ?? true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->transComplete();
            
            return $this->db->transStatus();
            
        } catch (Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Erro no apagamento de dados: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gera relatório de dados pessoais (portabilidade)
     */
    public function generateDataPortabilityReport(string $dataSubject, array $dataTypes = []): array
    {
        try {
            $report = [
                'data_subject' => $dataSubject,
                'generated_at' => date('Y-m-d H:i:s'),
                'data_types' => [],
                'total_records' => 0
            ];
            
            if (empty($dataTypes)) {
                $dataTypes = array_keys($this->config->personalDataTypes);
            }
            
            foreach ($dataTypes as $dataType) {
                $records = $this->findDataSubjectRecords($dataSubject, $dataType);
                $data = $this->extractDataForPortability($records, $dataType);
                
                $report['data_types'][$dataType] = [
                    'records_count' => count($data),
                    'data' => $data,
                    'last_updated' => $this->getLastUpdateDate($dataSubject, $dataType)
                ];
                
                $report['total_records'] += count($data);
            }
            
            // Log da geração do relatório
            $this->auditService->logDataPortability([
                'data_subject' => $dataSubject,
                'data_types' => $dataTypes,
                'total_records' => $report['total_records'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $report;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na geração do relatório de portabilidade: ' . $e->getMessage());
            throw new Exception('Falha na geração do relatório de portabilidade');
        }
    }
    
    // Métodos auxiliares privados
    
    private function getSensitiveFields(string $dataType): array
    {
        return $this->config->personalDataTypes[$dataType]['sensitive_fields'] ?? [];
    }
    
    private function getAnonymizationRules(string $dataType): array
    {
        return $this->config->personalDataTypes[$dataType]['anonymization_rules'] ?? [];
    }
    
    private function getPseudonymizationFields(string $dataType): array
    {
        return $this->config->personalDataTypes[$dataType]['pseudonymization_fields'] ?? [];
    }
    
    private function getMaskingRules(string $dataType): array
    {
        return $this->config->personalDataTypes[$dataType]['masking_rules'] ?? [];
    }
    
    private function applyAnonymizationRule(string $value, string $rule): string
    {
        switch ($rule) {
            case 'hash':
                return hash('sha256', $value);
            case 'random_string':
                return 'ANONIMIZADO_' . bin2hex(random_bytes(8));
            case 'generic_value':
                return '***';
            case 'date_year_only':
                return date('Y', strtotime($value)) . '-01-01';
            default:
                return '***';
        }
    }
    
    private function generatePseudonym(string $value, string $field): string
    {
        $salt = $this->config->security['pseudonym_salt'] ?? 'default_salt';
        return 'PSE_' . hash('sha256', $value . $field . $salt);
    }
    
    private function applyMaskingRule(string $value, string $rule): string
    {
        switch ($rule) {
            case 'email':
                $parts = explode('@', $value);
                if (count($parts) === 2) {
                    $username = substr($parts[0], 0, 2) . str_repeat('*', strlen($parts[0]) - 2);
                    return $username . '@' . $parts[1];
                }
                return $value;
            case 'cpf':
                return substr($value, 0, 3) . '.***.**' . substr($value, -2);
            case 'phone':
                return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
            case 'partial':
                return substr($value, 0, 2) . str_repeat('*', max(0, strlen($value) - 4)) . substr($value, -2);
            default:
                return str_repeat('*', strlen($value));
        }
    }
    
    private function savePseudonymMapping(array $pseudonymMap, string $dataType): void
    {
        $this->db->table('lgpd_pseudonym_mappings')->insert([
            'data_type' => $dataType,
            'mapping_data' => json_encode($pseudonymMap),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function getUserPermissions(int $userId): array
    {
        $result = $this->db->table('user_permissions')
                          ->where('user_id', $userId)
                          ->get()
                          ->getResultArray();
        
        return array_column($result, 'permission');
    }
    
    private function getContextRestrictions(int $userId, string $dataType): array
    {
        return $this->db->table('lgpd_access_restrictions')
                       ->where('user_id', $userId)
                       ->where('data_type', $dataType)
                       ->get()
                       ->getResultArray();
    }
    
    private function evaluateRestriction(array $restriction, string $operation): bool
    {
        // Implementa lógica de avaliação de restrições contextuais
        // Por exemplo: horário de acesso, localização, etc.
        return true; // Simplificado para o exemplo
    }
    
    private function findDataSubjectRecords(string $dataSubject, string $dataType): array
    {
        $tables = $this->config->personalDataTypes[$dataType]['tables'] ?? [];
        $records = [];
        
        foreach ($tables as $table => $identifierField) {
            $result = $this->db->table($table)
                              ->where($identifierField, $dataSubject)
                              ->get()
                              ->getResultArray();
            
            if (!empty($result)) {
                $records[$table] = array_column($result, 'id');
            }
        }
        
        return $records;
    }
    
    private function extractDataForPortability(array $records, string $dataType): array
    {
        $portableData = [];
        $tables = $this->config->personalDataTypes[$dataType]['tables'] ?? [];
        
        foreach ($records as $table => $recordIds) {
            $data = $this->db->table($table)
                            ->whereIn('id', $recordIds)
                            ->get()
                            ->getResultArray();
            
            // Remove campos sensíveis que não devem ser exportados
            $excludeFields = $this->config->personalDataTypes[$dataType]['exclude_from_export'] ?? [];
            foreach ($data as &$record) {
                foreach ($excludeFields as $field) {
                    unset($record[$field]);
                }
            }
            
            $portableData[$table] = $data;
        }
        
        return $portableData;
    }
    
    private function getLastUpdateDate(string $dataSubject, string $dataType): ?string
    {
        $tables = $this->config->personalDataTypes[$dataType]['tables'] ?? [];
        $lastUpdate = null;
        
        foreach ($tables as $table => $identifierField) {
            $result = $this->db->table($table)
                              ->select('MAX(updated_at) as last_update')
                              ->where($identifierField, $dataSubject)
                              ->get()
                              ->getRow();
            
            if ($result && $result->last_update) {
                if (!$lastUpdate || $result->last_update > $lastUpdate) {
                    $lastUpdate = $result->last_update;
                }
            }
        }
        
        return $lastUpdate;
    }
}