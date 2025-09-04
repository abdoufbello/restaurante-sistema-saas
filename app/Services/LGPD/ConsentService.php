<?php

namespace App\Services\LGPD;

use App\Models\LGPDConsentModel;
use App\Models\LGPDConsentLogModel;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use Exception;

class ConsentService
{
    protected $consentModel;
    protected $consentLogModel;
    protected $db;
    protected $config;
    protected $logger;

    public function __construct()
    {
        $this->consentModel = new LGPDConsentModel();
        $this->consentLogModel = new LGPDConsentLogModel();
        $this->db = \Config\Database::connect();
        $this->config = config('LGPD');
        $this->logger = \Config\Services::logger();
    }

    /**
     * Registrar consentimento do usuário
     */
    public function recordConsent(array $data): array
    {
        try {
            $this->db->transStart();

            // Validar dados obrigatórios
            if (!isset($data['data_subject_id']) || !isset($data['consent_type'])) {
                throw new Exception('Dados obrigatórios não fornecidos');
            }

            $consentData = [
                'data_subject_id' => $data['data_subject_id'],
                'consent_type' => $data['consent_type'],
                'consent_given' => $data['consent_given'] ?? true,
                'consent_text' => $data['consent_text'] ?? null,
                'ip_address' => $data['ip_address'] ?? $this->getClientIP(),
                'user_agent' => $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
                'consent_version' => $data['consent_version'] ?? '1.0',
                'expires_at' => $this->calculateExpiryDate($data['consent_duration'] ?? '2 years'),
                'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
            ];

            // Verificar se já existe consentimento para este titular e tipo
            $existingConsent = $this->consentModel->getConsentBySubjectAndType(
                $data['data_subject_id'], 
                $data['consent_type']
            );

            if ($existingConsent) {
                // Atualizar consentimento existente
                $consentId = $existingConsent['id'];
                $oldValue = $existingConsent;
                $this->consentModel->update($consentId, $consentData);
                $action = 'consent_updated';
            } else {
                // Inserir novo consentimento
                $consentId = $this->consentModel->insert($consentData);
                $oldValue = null;
                $action = 'consent_granted';
            }

            if (!$consentId) {
                throw new Exception('Erro ao registrar consentimento');
            }

            // Registrar log de auditoria
            $this->consentLogModel->logAction(
                $consentId,
                $data['data_subject_id'],
                $action,
                $oldValue,
                $consentData
            );

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new Exception('Erro na transação do banco de dados');
            }

            return [
                'success' => true,
                'consent_id' => $consentId,
                'message' => 'Consentimento registrado com sucesso'
            ];

        } catch (Exception $e) {
            $this->db->transRollback();
            $this->logger->error('Erro ao registrar consentimento: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Revogar consentimento
     */
    public function revokeConsent(string $dataSubjectId, string $consentType = null): array
    {
        try {
            $this->db->transStart();

            $builder = $this->consentModel->where('data_subject_id', $dataSubjectId)
                                         ->where('consent_given', true);

            if ($consentType) {
                $builder->where('consent_type', $consentType);
            }

            $consents = $builder->findAll();

            if (empty($consents)) {
                return [
                    'success' => false,
                    'error' => 'Nenhum consentimento ativo encontrado'
                ];
            }

            foreach ($consents as $consent) {
                $oldValue = $consent;
                $newValue = array_merge($consent, [
                    'consent_given' => false,
                    'revoked_at' => date('Y-m-d H:i:s')
                ]);

                $this->consentModel->update($consent['id'], [
                    'consent_given' => false,
                    'revoked_at' => date('Y-m-d H:i:s')
                ]);

                // Registrar log
                $this->consentLogModel->logAction(
                    $consent['id'],
                    $dataSubjectId,
                    'consent_revoked',
                    $oldValue,
                    $newValue
                );
            }

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Consentimento(s) revogado(s) com sucesso',
                'revoked_count' => count($consents)
            ];

        } catch (Exception $e) {
            $this->db->transRollback();
            $this->logger->error('Erro ao revogar consentimento: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar se usuário tem consentimento ativo para um tipo
     */
    public function hasConsent(string $dataSubjectId, string $consentType): bool
    {
        return $this->consentModel->hasConsent($dataSubjectId, $consentType);
    }

    /**
     * Obter consentimento ativo do usuário
     */
    public function getActiveConsent(string $dataSubjectId, string $consentType): ?array
    {
        return $this->consentModel->getConsentBySubjectAndType($dataSubjectId, $consentType);
    }

    /**
     * Obter todos os consentimentos de um titular
     */
    public function getConsentsBySubject(string $dataSubjectId): array
    {
        return $this->consentModel->getConsentsBySubject($dataSubjectId);
    }

    /**
     * Obter histórico de consentimentos do usuário
     */
    public function getConsentHistory(string $dataSubjectId): array
    {
        $consents = $this->consentModel->getConsentsBySubject($dataSubjectId);
        $logs = $this->consentLogModel->getLogsBySubject($dataSubjectId);

        return [
            'consents' => $consents,
            'logs' => $logs
        ];
    }

    /**
     * Verificar consentimentos expirados
     */
    public function checkExpiredConsents(): array
    {
        $expiredConsents = $this->consentModel->where('consent_given', true)
                                             ->where('expires_at IS NOT NULL')
                                             ->where('expires_at <', date('Y-m-d H:i:s'))
                                             ->findAll();

        $expiredCount = 0;
        foreach ($expiredConsents as $consent) {
            $oldValue = $consent;
            $newValue = array_merge($consent, ['consent_given' => false]);

            $this->consentModel->update($consent['id'], [
                'consent_given' => false
            ]);

            // Registrar log
            $this->consentLogModel->logAction(
                $consent['id'],
                $consent['data_subject_id'],
                'consent_expired',
                $oldValue,
                $newValue
            );

            $expiredCount++;
        }

        return [
            'success' => true,
            'expired_count' => $expiredCount,
            'message' => "$expiredCount consentimentos expirados processados"
        ];
    }

    /**
     * Gerar relatório de consentimentos
     */
    public function generateConsentReport(array $filters = []): array
    {
        $builder = $this->consentModel->builder();

        // Aplicar filtros
        if (isset($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }

        if (isset($filters['consent_type'])) {
            $builder->where('consent_type', $filters['consent_type']);
        }

        if (isset($filters['consent_given'])) {
            $builder->where('consent_given', $filters['consent_given']);
        }

        $consents = $builder->findAll();

        // Estatísticas
        $stats = [
            'total_consents' => count($consents),
            'active_consents' => 0,
            'revoked_consents' => 0,
            'consent_types' => [],
            'monthly_stats' => []
        ];

        foreach ($consents as $consent) {
            // Contadores por status
            if ($consent['consent_given']) {
                $stats['active_consents']++;
            } else {
                $stats['revoked_consents']++;
            }

            // Breakdown por tipo
            $type = $consent['consent_type'];
            $stats['consent_types'][$type] = ($stats['consent_types'][$type] ?? 0) + 1;

            // Estatísticas mensais
            $month = date('Y-m', strtotime($consent['created_at']));
            $stats['monthly_stats'][$month] = ($stats['monthly_stats'][$month] ?? 0) + 1;
        }

        return [
            'success' => true,
            'data' => $consents,
            'statistics' => $stats
        ];
    }

    /**
     * Calcular data de expiração do consentimento
     */
    private function calculateExpiryDate(string $duration): ?string
    {
        if ($duration === 'never') {
            return null;
        }

        $time = new \DateTime();
        
        switch ($duration) {
            case '1 year':
                return $time->modify('+1 year')->format('Y-m-d H:i:s');
            case '2 years':
                return $time->modify('+2 years')->format('Y-m-d H:i:s');
            case '3 years':
                return $time->modify('+3 years')->format('Y-m-d H:i:s');
            case '5 years':
                return $time->modify('+5 years')->format('Y-m-d H:i:s');
            default:
                return $time->modify('+2 years')->format('Y-m-d H:i:s'); // Padrão: 2 anos
        }
    }

    /**
     * Obter IP do cliente
     */
    private function getClientIP(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return trim($ip);
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Validar estrutura de consentimento
     */
    public function validateConsentData(array $data): array
    {
        $errors = [];

        // Validar campos obrigatórios
        if (empty($data['data_subject_id'])) {
            $errors[] = 'ID do titular dos dados é obrigatório';
        }

        if (empty($data['consent_type'])) {
            $errors[] = 'Tipo de consentimento é obrigatório';
        }

        // Validar tipos de consentimento válidos
        $validTypes = ['cookies', 'marketing', 'analytics', 'personalization', 'third_party'];
        if (!empty($data['consent_type']) && !in_array($data['consent_type'], $validTypes)) {
            $errors[] = 'Tipo de consentimento inválido: ' . $data['consent_type'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Exportar dados de consentimento de um titular
     */
    public function exportConsentData(string $dataSubjectId): array
    {
        $consents = $this->getConsentsBySubject($dataSubjectId);
        $logs = $this->consentLogModel->getLogsBySubject($dataSubjectId);

        return [
            'data_subject_id' => $dataSubjectId,
            'consents' => $consents,
            'audit_logs' => $logs,
            'exported_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Excluir todos os dados de consentimento de um titular
     */
    public function deleteConsentData(string $dataSubjectId): array
    {
        try {
            $this->db->transStart();

            // Buscar consentimentos antes de excluir
            $consents = $this->getConsentsBySubject($dataSubjectId);

            // Excluir logs
            $this->consentLogModel->where('data_subject_id', $dataSubjectId)->delete();

            // Excluir consentimentos
            $this->consentModel->where('data_subject_id', $dataSubjectId)->delete();

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Dados de consentimento excluídos com sucesso',
                'deleted_consents' => count($consents)
            ];

        } catch (Exception $e) {
            $this->db->transRollback();
            $this->logger->error('Erro ao excluir dados de consentimento: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}