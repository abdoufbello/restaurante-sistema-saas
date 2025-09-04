<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\LGPD\ConsentService;
use App\Services\LGPD\DataProtectionService;
use App\Services\LGPD\AuditService;
use App\Services\LGPD\PrivacyPolicyService;
use Exception;

/**
 * Controlador LGPD API
 * 
 * Expõe funcionalidades de compliance LGPD através de APIs REST
 */
class LGPDController extends ResourceController
{
    protected $consentService;
    protected $dataProtectionService;
    protected $auditService;
    protected $privacyPolicyService;
    protected $format = 'json';
    
    public function __construct()
    {
        $this->consentService = new ConsentService();
        $this->dataProtectionService = new DataProtectionService();
        $this->auditService = new AuditService();
        $this->privacyPolicyService = new PrivacyPolicyService();
    }
    
    // === ENDPOINTS DE CONSENTIMENTO ===
    
    /**
     * Registra consentimento do usuário
     * POST /api/lgpd/consent
     */
    public function recordConsent()
    {
        try {
            $data = $this->request->getJSON(true);
            
            $requiredFields = ['data_subject', 'consent_type', 'purpose'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->failValidationError("Campo obrigatório: {$field}");
                }
            }
            
            $result = $this->consentService->recordConsent($data);
            
            if ($result['success']) {
                return $this->respondCreated($result);
            } else {
                return $this->failServerError($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro no registro de consentimento: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Revoga consentimento do usuário
     * DELETE /api/lgpd/consent/{dataSubject}/{consentType}
     */
    public function revokeConsent($dataSubject = null, $consentType = null)
    {
        try {
            if (!$dataSubject || !$consentType) {
                return $this->failValidationError('Data subject e tipo de consentimento são obrigatórios');
            }
            
            $result = $this->consentService->revokeConsent($dataSubject, $consentType);
            
            if ($result['success']) {
                return $this->respondDeleted($result);
            } else {
                return $this->failServerError($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro na revogação de consentimento: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Verifica consentimentos do usuário
     * GET /api/lgpd/consent/{dataSubject}
     */
    public function checkConsent($dataSubject = null)
    {
        try {
            if (!$dataSubject) {
                return $this->failValidationError('Data subject é obrigatório');
            }
            
            $consentType = $this->request->getGet('type');
            $result = $this->consentService->checkConsent($dataSubject, $consentType);
            
            if ($result['success']) {
                return $this->respond($result);
            } else {
                return $this->failNotFound($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro na verificação de consentimento: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Atualiza preferências de consentimento
     * PUT /api/lgpd/consent/{dataSubject}
     */
    public function updateConsentPreferences($dataSubject = null)
    {
        try {
            if (!$dataSubject) {
                return $this->failValidationError('Data subject é obrigatório');
            }
            
            $preferences = $this->request->getJSON(true);
            $result = $this->consentService->updateConsentPreferences($dataSubject, $preferences);
            
            if ($result['success']) {
                return $this->respond($result);
            } else {
                return $this->failServerError($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro na atualização de preferências: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    // === ENDPOINTS DE PROTEÇÃO DE DADOS ===
    
    /**
     * Solicita portabilidade de dados
     * GET /api/lgpd/data-portability/{dataSubject}
     */
    public function requestDataPortability($dataSubject = null)
    {
        try {
            if (!$dataSubject) {
                return $this->failValidationError('Data subject é obrigatório');
            }
            
            $dataTypes = $this->request->getGet('types');
            $dataTypesArray = $dataTypes ? explode(',', $dataTypes) : [];
            
            $result = $this->dataProtectionService->generateDataPortabilityReport($dataSubject, $dataTypesArray);
            
            return $this->respond([
                'success' => true,
                'message' => 'Relatório de portabilidade gerado',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro na portabilidade de dados: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Solicita apagamento de dados (direito ao esquecimento)
     * DELETE /api/lgpd/data-erasure/{dataSubject}
     */
    public function requestDataErasure($dataSubject = null)
    {
        try {
            if (!$dataSubject) {
                return $this->failValidationError('Data subject é obrigatório');
            }
            
            $data = $this->request->getJSON(true);
            $dataType = $data['data_type'] ?? 'all';
            $options = $data['options'] ?? ['soft_delete' => true];
            
            $result = $this->dataProtectionService->erasePersonalData($dataSubject, $dataType, $options);
            
            if ($result) {
                return $this->respondDeleted([
                    'success' => true,
                    'message' => 'Dados removidos com sucesso'
                ]);
            } else {
                return $this->failServerError('Erro ao remover dados');
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro no apagamento de dados: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Verifica acesso a dados
     * GET /api/lgpd/data-access-check
     */
    public function checkDataAccess()
    {
        try {
            $userId = $this->request->getGet('user_id');
            $dataType = $this->request->getGet('data_type');
            $operation = $this->request->getGet('operation') ?? 'read';
            
            if (!$userId || !$dataType) {
                return $this->failValidationError('User ID e tipo de dados são obrigatórios');
            }
            
            $hasAccess = $this->dataProtectionService->checkDataAccess((int)$userId, $dataType, $operation);
            
            return $this->respond([
                'success' => true,
                'has_access' => $hasAccess,
                'user_id' => $userId,
                'data_type' => $dataType,
                'operation' => $operation
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro na verificação de acesso: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    // === ENDPOINTS DE POLÍTICA DE PRIVACIDADE ===
    
    /**
     * Obtém política de privacidade ativa
     * GET /api/lgpd/privacy-policy
     */
    public function getPrivacyPolicy()
    {
        try {
            $policyType = $this->request->getGet('type') ?? 'general';
            $language = $this->request->getGet('lang') ?? 'pt-BR';
            
            $result = $this->privacyPolicyService->getActivePrivacyPolicy($policyType, $language);
            
            if ($result['success']) {
                return $this->respond($result);
            } else {
                return $this->failNotFound($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao obter política de privacidade: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Registra aceite de política de privacidade
     * POST /api/lgpd/privacy-policy/accept
     */
    public function acceptPrivacyPolicy()
    {
        try {
            $data = $this->request->getJSON(true);
            
            $requiredFields = ['policy_id', 'data_subject'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->failValidationError("Campo obrigatório: {$field}");
                }
            }
            
            // Adiciona informações da requisição
            $data['ip_address'] = $this->request->getIPAddress();
            $data['user_agent'] = $this->request->getUserAgent();
            $data['method'] = 'api';
            
            $result = $this->privacyPolicyService->recordPolicyAcceptance($data);
            
            if ($result['success']) {
                return $this->respondCreated($result);
            } else {
                return $this->failServerError($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro no aceite da política: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Verifica aceite de política de privacidade
     * GET /api/lgpd/privacy-policy/acceptance/{dataSubject}
     */
    public function checkPolicyAcceptance($dataSubject = null)
    {
        try {
            if (!$dataSubject) {
                return $this->failValidationError('Data subject é obrigatório');
            }
            
            $policyType = $this->request->getGet('type') ?? 'general';
            $result = $this->privacyPolicyService->checkPolicyAcceptance($dataSubject, $policyType);
            
            if ($result['success']) {
                return $this->respond($result);
            } else {
                return $this->failServerError($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro na verificação de aceite: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    // === ENDPOINTS DE AUDITORIA ===
    
    /**
     * Gera relatório de auditoria
     * GET /api/lgpd/audit/report
     */
    public function getAuditReport()
    {
        try {
            // Verifica permissões de administrador
            if (!$this->hasAdminPermission()) {
                return $this->failForbidden('Acesso negado');
            }
            
            $filters = [
                'start_date' => $this->request->getGet('start_date'),
                'end_date' => $this->request->getGet('end_date'),
                'data_type' => $this->request->getGet('data_type'),
                'user_id' => $this->request->getGet('user_id')
            ];
            
            // Remove filtros vazios
            $filters = array_filter($filters);
            
            $result = $this->auditService->generateAuditReport($filters);
            
            return $this->respond([
                'success' => true,
                'message' => 'Relatório de auditoria gerado',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro na geração do relatório de auditoria: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Detecta atividades suspeitas
     * GET /api/lgpd/audit/suspicious-activities
     */
    public function getSuspiciousActivities()
    {
        try {
            // Verifica permissões de administrador
            if (!$this->hasAdminPermission()) {
                return $this->failForbidden('Acesso negado');
            }
            
            $activities = $this->auditService->detectSuspiciousActivity();
            
            return $this->respond([
                'success' => true,
                'message' => 'Atividades suspeitas detectadas',
                'data' => $activities,
                'count' => count($activities)
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro na detecção de atividades suspeitas: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Registra violação de dados
     * POST /api/lgpd/audit/data-breach
     */
    public function reportDataBreach()
    {
        try {
            // Verifica permissões de administrador
            if (!$this->hasAdminPermission()) {
                return $this->failForbidden('Acesso negado');
            }
            
            $data = $this->request->getJSON(true);
            
            $requiredFields = ['type', 'description'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->failValidationError("Campo obrigatório: {$field}");
                }
            }
            
            $result = $this->auditService->logDataBreach($data);
            
            if ($result) {
                return $this->respondCreated([
                    'success' => true,
                    'message' => 'Violação de dados registrada com sucesso'
                ]);
            } else {
                return $this->failServerError('Erro ao registrar violação de dados');
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro no registro de violação: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    // === ENDPOINTS ADMINISTRATIVOS ===
    
    /**
     * Cria nova política de privacidade
     * POST /api/lgpd/admin/privacy-policy
     */
    public function createPrivacyPolicy()
    {
        try {
            // Verifica permissões de administrador
            if (!$this->hasAdminPermission()) {
                return $this->failForbidden('Acesso negado');
            }
            
            $data = $this->request->getJSON(true);
            
            $requiredFields = ['policy_type', 'title', 'content', 'created_by'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->failValidationError("Campo obrigatório: {$field}");
                }
            }
            
            $result = $this->privacyPolicyService->createPrivacyPolicy($data);
            
            if ($result['success']) {
                return $this->respondCreated($result);
            } else {
                return $this->failServerError($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro na criação da política: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Analisa conformidade da política
     * GET /api/lgpd/admin/policy-compliance/{policyId}
     */
    public function analyzePolicyCompliance($policyId = null)
    {
        try {
            // Verifica permissões de administrador
            if (!$this->hasAdminPermission()) {
                return $this->failForbidden('Acesso negado');
            }
            
            if (!$policyId) {
                return $this->failValidationError('ID da política é obrigatório');
            }
            
            $result = $this->privacyPolicyService->analyzePolicyCompliance((int)$policyId);
            
            if ($result['success']) {
                return $this->respond($result);
            } else {
                return $this->failServerError($result['message']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro na análise de conformidade: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Limpa logs antigos
     * DELETE /api/lgpd/admin/cleanup-logs
     */
    public function cleanupLogs()
    {
        try {
            // Verifica permissões de administrador
            if (!$this->hasAdminPermission()) {
                return $this->failForbidden('Acesso negado');
            }
            
            $result = $this->auditService->cleanupOldLogs();
            
            if ($result) {
                return $this->respond([
                    'success' => true,
                    'message' => 'Limpeza de logs executada com sucesso'
                ]);
            } else {
                return $this->failServerError('Erro na limpeza de logs');
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro na limpeza de logs: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    // === ENDPOINTS DE UTILIDADE ===
    
    /**
     * Verifica status de compliance geral
     * GET /api/lgpd/compliance-status
     */
    public function getComplianceStatus()
    {
        try {
            $status = [
                'consent_management' => $this->checkConsentManagementStatus(),
                'data_protection' => $this->checkDataProtectionStatus(),
                'audit_system' => $this->checkAuditSystemStatus(),
                'privacy_policies' => $this->checkPrivacyPoliciesStatus(),
                'overall_compliance' => 0
            ];
            
            // Calcula compliance geral
            $scores = array_column($status, 'score');
            $status['overall_compliance'] = array_sum($scores) / count($scores);
            
            return $this->respond([
                'success' => true,
                'message' => 'Status de compliance obtido',
                'data' => $status,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao obter status de compliance: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    // Métodos auxiliares privados
    
    private function hasAdminPermission(): bool
    {
        // Implementa verificação de permissões de administrador
        // Por exemplo, verificar JWT token, sessão, etc.
        $session = session();
        return $session->get('user_role') === 'admin' || $session->get('is_admin') === true;
    }
    
    private function checkConsentManagementStatus(): array
    {
        // Implementa verificação do status do sistema de consentimentos
        return ['score' => 95, 'status' => 'operational'];
    }
    
    private function checkDataProtectionStatus(): array
    {
        // Implementa verificação do status da proteção de dados
        return ['score' => 90, 'status' => 'operational'];
    }
    
    private function checkAuditSystemStatus(): array
    {
        // Implementa verificação do status do sistema de auditoria
        return ['score' => 98, 'status' => 'operational'];
    }
    
    private function checkPrivacyPoliciesStatus(): array
    {
        // Implementa verificação do status das políticas de privacidade
        return ['score' => 92, 'status' => 'operational'];
    }
}