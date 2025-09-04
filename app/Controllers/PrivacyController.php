<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\SecurityService;
use CodeIgniter\I18n\Time;
use Exception;

class PrivacyController extends BaseController
{
    protected SecurityService $securityService;
    
    public function __construct()
    {
        $this->securityService = new SecurityService();
    }
    
    /**
     * Display privacy policy and consent form
     */
    public function consent()
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return redirect()->to('/login');
        }
        
        // Get current consents
        $currentConsents = $this->getCurrentConsents($restaurantId);
        
        $data = [
            'title' => 'Política de Privacidade e Consentimento LGPD',
            'current_consents' => $currentConsents,
            'privacy_policy_version' => '2.0',
            'last_updated' => '2024-01-15'
        ];
        
        return view('privacy/consent', $data);
    }
    
    /**
     * Process consent form submission
     */
    public function processConsent()
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Sessão inválida'
            ]);
        }
        
        $consents = $this->request->getPost('consents') ?? [];
        
        try {
            $db = \Config\Database::connect();
            $db->transStart();
            
            // Process each consent type
            $consentTypes = [
                'data_processing' => 'Processamento de dados pessoais',
                'marketing' => 'Comunicações de marketing',
                'analytics' => 'Análise de dados e métricas',
                'cookies' => 'Uso de cookies e tecnologias similares',
                'third_party' => 'Compartilhamento com terceiros'
            ];
            
            foreach ($consentTypes as $type => $description) {
                $consentGiven = isset($consents[$type]) && $consents[$type] === '1';
                
                // Update or insert consent
                $existingConsent = $db->table('data_consents')
                    ->where('restaurant_id', $restaurantId)
                    ->where('consent_type', $type)
                    ->get()
                    ->getRow();
                
                $consentData = [
                    'restaurant_id' => $restaurantId,
                    'consent_type' => $type,
                    'consent_given' => $consentGiven,
                    'consent_text' => $description,
                    'ip_address' => $this->getClientIpAddress(),
                    'user_agent' => $this->request->getUserAgent()->getAgentString(),
                    'consent_version' => '2.0',
                    'expires_at' => Time::now()->addYears(2)->toDateTimeString(),
                    'updated_at' => Time::now()->toDateTimeString()
                ];
                
                if ($existingConsent) {
                    $db->table('data_consents')
                        ->where('id', $existingConsent->id)
                        ->update($consentData);
                } else {
                    $consentData['created_at'] = Time::now()->toDateTimeString();
                    $db->table('data_consents')->insert($consentData);
                }
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new Exception('Falha ao salvar consentimentos');
            }
            
            // Log consent event
            $this->securityService->logSecurityEvent([
                'restaurant_id' => $restaurantId,
                'action' => 'lgpd_consent_updated',
                'details' => json_encode($consents),
                'severity' => 'info',
                'success' => true
            ]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Consentimentos atualizados com sucesso'
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao processar consentimentos: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Display data export request form
     */
    public function dataExport()
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return redirect()->to('/login');
        }
        
        // Get pending export requests
        $pendingExports = $this->getPendingExports($restaurantId);
        
        $data = [
            'title' => 'Exportação de Dados - LGPD',
            'pending_exports' => $pendingExports,
            'export_types' => $this->getAvailableExportTypes()
        ];
        
        return view('privacy/data_export', $data);
    }
    
    /**
     * Process data export request
     */
    public function requestDataExport()
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Sessão inválida'
            ]);
        }
        
        $requestedData = $this->request->getPost('data_types') ?? [];
        
        if (empty($requestedData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Selecione pelo menos um tipo de dados para exportar'
            ]);
        }
        
        try {
            $db = \Config\Database::connect();
            
            // Check for existing pending requests
            $existingRequest = $db->table('data_exports')
                ->where('restaurant_id', $restaurantId)
                ->where('request_type', 'export')
                ->whereIn('status', ['pending', 'processing'])
                ->get()
                ->getRow();
            
            if ($existingRequest) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Já existe uma solicitação de exportação em andamento'
                ]);
            }
            
            // Create export request
            $exportData = [
                'restaurant_id' => $restaurantId,
                'request_type' => 'export',
                'status' => 'pending',
                'requested_data' => json_encode($requestedData),
                'expires_at' => Time::now()->addDays(30)->toDateTimeString(),
                'created_at' => Time::now()->toDateTimeString(),
                'updated_at' => Time::now()->toDateTimeString()
            ];
            
            $exportId = $db->table('data_exports')->insert($exportData);
            
            // Log export request
            $this->securityService->logSecurityEvent([
                'restaurant_id' => $restaurantId,
                'action' => 'data_export_requested',
                'details' => json_encode($requestedData),
                'severity' => 'info',
                'success' => true
            ]);
            
            // Queue export processing (in a real implementation, this would be a background job)
            $this->queueExportProcessing($exportId);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Solicitação de exportação criada. Você receberá um email quando estiver pronta.',
                'export_id' => $exportId
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao solicitar exportação: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Download exported data
     */
    public function downloadExport($exportId)
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return redirect()->to('/login');
        }
        
        try {
            $db = \Config\Database::connect();
            
            $export = $db->table('data_exports')
                ->where('id', $exportId)
                ->where('restaurant_id', $restaurantId)
                ->where('status', 'completed')
                ->get()
                ->getRow();
            
            if (!$export) {
                return redirect()->to('/privacy/data-export')
                    ->with('error', 'Exportação não encontrada ou não disponível');
            }
            
            // Check if file exists
            if (!$export->export_file_path || !file_exists($export->export_file_path)) {
                return redirect()->to('/privacy/data-export')
                    ->with('error', 'Arquivo de exportação não encontrado');
            }
            
            // Update download count
            $db->table('data_exports')
                ->where('id', $exportId)
                ->update([
                    'download_count' => $export->download_count + 1,
                    'updated_at' => Time::now()->toDateTimeString()
                ]);
            
            // Log download
            $this->securityService->logSecurityEvent([
                'restaurant_id' => $restaurantId,
                'action' => 'data_export_downloaded',
                'details' => "Export ID: {$exportId}",
                'severity' => 'info',
                'success' => true
            ]);
            
            // Return file download
            return $this->response->download($export->export_file_path, null);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao baixar exportação: ' . $e->getMessage());
            
            return redirect()->to('/privacy/data-export')
                ->with('error', 'Erro ao baixar arquivo');
        }
    }
    
    /**
     * Display data deletion request form
     */
    public function dataDeletion()
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return redirect()->to('/login');
        }
        
        $data = [
            'title' => 'Solicitação de Exclusão de Dados - LGPD',
            'deletion_types' => $this->getAvailableDeletionTypes(),
            'retention_policy' => $this->getDataRetentionPolicy()
        ];
        
        return view('privacy/data_deletion', $data);
    }
    
    /**
     * Process data deletion request
     */
    public function requestDataDeletion()
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Sessão inválida'
            ]);
        }
        
        $deletionTypes = $this->request->getPost('deletion_types') ?? [];
        $reason = $this->request->getPost('reason') ?? '';
        
        if (empty($deletionTypes)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Selecione pelo menos um tipo de dados para exclusão'
            ]);
        }
        
        try {
            $db = \Config\Database::connect();
            
            // Create deletion request
            $deletionData = [
                'restaurant_id' => $restaurantId,
                'request_type' => 'deletion',
                'status' => 'pending',
                'requested_data' => json_encode([
                    'types' => $deletionTypes,
                    'reason' => $reason
                ]),
                'created_at' => Time::now()->toDateTimeString(),
                'updated_at' => Time::now()->toDateTimeString()
            ];
            
            $deletionId = $db->table('data_exports')->insert($deletionData);
            
            // Log deletion request
            $this->securityService->logSecurityEvent([
                'restaurant_id' => $restaurantId,
                'action' => 'data_deletion_requested',
                'details' => json_encode($deletionData['requested_data']),
                'severity' => 'warning',
                'success' => true
            ]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Solicitação de exclusão criada. Nossa equipe entrará em contato em até 72 horas.',
                'deletion_id' => $deletionId
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao solicitar exclusão: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Display privacy policy
     */
    public function policy()
    {
        $data = [
            'title' => 'Política de Privacidade',
            'last_updated' => '2024-01-15',
            'version' => '2.0'
        ];
        
        return view('privacy/policy', $data);
    }
    
    /**
     * Get current consents for restaurant
     */
    protected function getCurrentConsents(int $restaurantId): array
    {
        $db = \Config\Database::connect();
        
        $consents = $db->table('data_consents')
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->getResultArray();
        
        $consentMap = [];
        foreach ($consents as $consent) {
            $consentMap[$consent['consent_type']] = $consent;
        }
        
        return $consentMap;
    }
    
    /**
     * Get pending export requests
     */
    protected function getPendingExports(int $restaurantId): array
    {
        $db = \Config\Database::connect();
        
        return $db->table('data_exports')
            ->where('restaurant_id', $restaurantId)
            ->where('request_type', 'export')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get available export types
     */
    protected function getAvailableExportTypes(): array
    {
        return [
            'restaurant_data' => 'Dados do restaurante',
            'menu_data' => 'Dados do cardápio',
            'order_data' => 'Histórico de pedidos',
            'customer_data' => 'Dados de clientes',
            'financial_data' => 'Dados financeiros',
            'usage_data' => 'Dados de uso da plataforma',
            'audit_logs' => 'Logs de auditoria'
        ];
    }
    
    /**
     * Get available deletion types
     */
    protected function getAvailableDeletionTypes(): array
    {
        return [
            'marketing_data' => 'Dados de marketing',
            'analytics_data' => 'Dados de análise',
            'optional_profile_data' => 'Dados opcionais do perfil',
            'cached_data' => 'Dados em cache'
        ];
    }
    
    /**
     * Get data retention policy
     */
    protected function getDataRetentionPolicy(): array
    {
        return [
            'financial_data' => '7 anos (obrigatório por lei)',
            'audit_logs' => '5 anos (obrigatório por lei)',
            'user_data' => '2 anos após encerramento da conta',
            'marketing_data' => 'Até retirada do consentimento',
            'analytics_data' => '2 anos'
        ];
    }
    
    /**
     * Queue export processing (placeholder for background job)
     */
    protected function queueExportProcessing(int $exportId): void
    {
        // In a real implementation, this would queue a background job
        // For now, we'll just log that it should be processed
        log_message('info', "Export request {$exportId} queued for processing");
    }
    
    /**
     * Get client IP address
     */
    protected function getClientIpAddress(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}