<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Services\Delivery\IFoodService;
use App\Services\Delivery\UberEatsService;
use App\Services\Delivery\RappiService;
use App\Services\Delivery\NineNineFoodService;

class DeliveryIntegrationController extends ResourceController
{
    use ResponseTrait;
    
    protected $modelName = 'App\Models\DeliveryIntegration';
    protected $format = 'json';
    
    public function __construct()
    {
        $this->model = new \App\Models\DeliveryIntegration();
    }
    
    /**
     * Listar integrações de delivery
     * GET /api/v1/delivery-integrations
     */
    public function index()
    {
        if (!$this->hasPermission('delivery.view')) {
            return $this->failForbidden('Sem permissão para visualizar integrações de delivery');
        }
        
        try {
            $restaurantId = $this->getRestaurantId();
            
            $integrations = $this->model
                ->where('restaurant_id', $restaurantId)
                ->orderBy('created_at', 'DESC')
                ->findAll();
            
            // Descriptografar credenciais para exibição (apenas campos não sensíveis)
            foreach ($integrations as &$integration) {
                $integration['credentials'] = $this->sanitizeCredentials(
                    json_decode($integration['credentials'], true)
                );
                $integration['settings'] = json_decode($integration['settings'], true);
                $integration['last_sync_data'] = json_decode($integration['last_sync_data'], true);
            }
            
            return $this->respond([
                'success' => true,
                'data' => $integrations,
                'total' => count($integrations)
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao listar integrações de delivery: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Obter integração específica
     * GET /api/v1/delivery-integrations/{id}
     */
    public function show($id = null)
    {
        if (!$this->hasPermission('delivery.view')) {
            return $this->failForbidden('Sem permissão para visualizar integrações de delivery');
        }
        
        try {
            $restaurantId = $this->getRestaurantId();
            
            $integration = $this->model
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->first();
            
            if (!$integration) {
                return $this->failNotFound('Integração não encontrada');
            }
            
            // Sanitizar credenciais
            $integration['credentials'] = $this->sanitizeCredentials(
                json_decode($integration['credentials'], true)
            );
            $integration['settings'] = json_decode($integration['settings'], true);
            $integration['last_sync_data'] = json_decode($integration['last_sync_data'], true);
            
            return $this->respond([
                'success' => true,
                'data' => $integration
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao obter integração de delivery: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Criar nova integração
     * POST /api/v1/delivery-integrations
     */
    public function create()
    {
        if (!$this->hasPermission('delivery.create')) {
            return $this->failForbidden('Sem permissão para criar integrações de delivery');
        }
        
        try {
            $data = $this->request->getJSON(true);
            $restaurantId = $this->getRestaurantId();
            
            // Validar entrada
            $validation = \Config\Services::validation();
            $validation->setRules([
                'platform' => 'required|in_list[ifood,ubereats,rappi,99food]',
                'credentials' => 'required|array',
                'settings' => 'permit_empty|array'
            ]);
            
            if (!$validation->run($data)) {
                return $this->failValidationErrors($validation->getErrors());
            }
            
            // Verificar se já existe integração para esta plataforma
            $existing = $this->model
                ->where('restaurant_id', $restaurantId)
                ->where('platform', $data['platform'])
                ->first();
            
            if ($existing) {
                return $this->fail('Integração já existe para esta plataforma', 400);
            }
            
            // Validar credenciais específicas da plataforma
            $credentialsValidation = $this->validatePlatformCredentials(
                $data['platform'], 
                $data['credentials']
            );
            
            if (!$credentialsValidation['valid']) {
                return $this->fail($credentialsValidation['error'], 400);
            }
            
            // Preparar dados para inserção
            $insertData = [
                'restaurant_id' => $restaurantId,
                'platform' => $data['platform'],
                'credentials' => json_encode($this->encryptCredentials($data['credentials'])),
                'settings' => json_encode($data['settings'] ?? []),
                'is_active' => false, // Inicia inativo até teste de conexão
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $integrationId = $this->model->insert($insertData);
            
            // Testar conexão
            $connectionTest = $this->testConnection($integrationId);
            
            if ($connectionTest['success']) {
                $this->model->update($integrationId, ['is_active' => true]);
            }
            
            // Log da atividade
            $this->logActivity('delivery_integration_created', [
                'integration_id' => $integrationId,
                'platform' => $data['platform'],
                'connection_test' => $connectionTest
            ]);
            
            return $this->respondCreated([
                'success' => true,
                'message' => 'Integração criada com sucesso',
                'data' => [
                    'id' => $integrationId,
                    'platform' => $data['platform'],
                    'is_active' => $connectionTest['success'],
                    'connection_test' => $connectionTest
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao criar integração de delivery: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Atualizar integração
     * PUT /api/v1/delivery-integrations/{id}
     */
    public function update($id = null)
    {
        if (!$this->hasPermission('delivery.update')) {
            return $this->failForbidden('Sem permissão para atualizar integrações de delivery');
        }
        
        try {
            $data = $this->request->getJSON(true);
            $restaurantId = $this->getRestaurantId();
            
            // Buscar integração
            $integration = $this->model
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->first();
            
            if (!$integration) {
                return $this->failNotFound('Integração não encontrada');
            }
            
            // Validar entrada
            $validation = \Config\Services::validation();
            $validation->setRules([
                'credentials' => 'permit_empty|array',
                'settings' => 'permit_empty|array',
                'is_active' => 'permit_empty|boolean'
            ]);
            
            if (!$validation->run($data)) {
                return $this->failValidationErrors($validation->getErrors());
            }
            
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];
            
            // Atualizar credenciais se fornecidas
            if (isset($data['credentials'])) {
                $credentialsValidation = $this->validatePlatformCredentials(
                    $integration['platform'], 
                    $data['credentials']
                );
                
                if (!$credentialsValidation['valid']) {
                    return $this->fail($credentialsValidation['error'], 400);
                }
                
                $updateData['credentials'] = json_encode($this->encryptCredentials($data['credentials']));
            }
            
            // Atualizar configurações
            if (isset($data['settings'])) {
                $updateData['settings'] = json_encode($data['settings']);
            }
            
            // Atualizar status
            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }
            
            $this->model->update($id, $updateData);
            
            // Testar conexão se credenciais foram atualizadas
            $connectionTest = null;
            if (isset($data['credentials'])) {
                $connectionTest = $this->testConnection($id);
                
                if (!$connectionTest['success']) {
                    $this->model->update($id, ['is_active' => false]);
                }
            }
            
            // Log da atividade
            $this->logActivity('delivery_integration_updated', [
                'integration_id' => $id,
                'platform' => $integration['platform'],
                'connection_test' => $connectionTest
            ]);
            
            return $this->respond([
                'success' => true,
                'message' => 'Integração atualizada com sucesso',
                'data' => [
                    'connection_test' => $connectionTest
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao atualizar integração de delivery: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Excluir integração
     * DELETE /api/v1/delivery-integrations/{id}
     */
    public function delete($id = null)
    {
        if (!$this->hasPermission('delivery.delete')) {
            return $this->failForbidden('Sem permissão para excluir integrações de delivery');
        }
        
        try {
            $restaurantId = $this->getRestaurantId();
            
            $integration = $this->model
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->first();
            
            if (!$integration) {
                return $this->failNotFound('Integração não encontrada');
            }
            
            $this->model->delete($id);
            
            // Log da atividade
            $this->logActivity('delivery_integration_deleted', [
                'integration_id' => $id,
                'platform' => $integration['platform']
            ]);
            
            return $this->respondDeleted([
                'success' => true,
                'message' => 'Integração excluída com sucesso'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao excluir integração de delivery: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Testar conexão com plataforma
     * POST /api/v1/delivery-integrations/{id}/test
     */
    public function testConnection($id = null)
    {
        if (!$this->hasPermission('delivery.test')) {
            return $this->failForbidden('Sem permissão para testar integrações de delivery');
        }
        
        try {
            $restaurantId = $this->getRestaurantId();
            
            $integration = $this->model
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->first();
            
            if (!$integration) {
                return $this->failNotFound('Integração não encontrada');
            }
            
            $credentials = $this->decryptCredentials(
                json_decode($integration['credentials'], true)
            );
            
            // Usar o serviço específico da plataforma
            $result = $this->testConnectionByPlatform(
                $integration['platform'],
                $credentials
            );
            
            // Atualizar último teste
            $this->model->update($id, [
                'last_test_at' => date('Y-m-d H:i:s'),
                'last_test_result' => json_encode($result)
            ]);
            
            return $this->respond([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao testar conexão de delivery: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Sincronizar cardápio com plataforma
     * POST /api/v1/delivery-integrations/{id}/sync-menu
     */
    public function syncMenu($id = null)
    {
        if (!$this->hasPermission('delivery.sync')) {
            return $this->failForbidden('Sem permissão para sincronizar com plataformas de delivery');
        }
        
        try {
            $restaurantId = $this->getRestaurantId();
            
            $integration = $this->model
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->first();
            
            if (!$integration) {
                return $this->failNotFound('Integração ativa não encontrada');
            }
            
            // Obter dados do cardápio
            $menuData = $this->getMenuData($restaurantId);
            
            // Usar o serviço específico da plataforma
            $result = $this->syncMenuByPlatform(
                $integration['platform'],
                $integration,
                $menuData
            );
            
            // Atualizar última sincronização
            $this->model->update($id, [
                'last_sync_at' => date('Y-m-d H:i:s'),
                'last_sync_data' => json_encode($result)
            ]);
            
            return $this->respond([
                'success' => true,
                'message' => 'Cardápio sincronizado com sucesso',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao sincronizar cardápio: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Obter pedidos da plataforma
     * GET /api/v1/delivery-integrations/{id}/orders
     */
    public function getOrders($id = null)
    {
        if (!$this->hasPermission('delivery.orders')) {
            return $this->failForbidden('Sem permissão para visualizar pedidos de delivery');
        }
        
        try {
            $restaurantId = $this->getRestaurantId();
            $params = $this->request->getGet();
            
            $integration = $this->model
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->first();
            
            if (!$integration) {
                return $this->failNotFound('Integração ativa não encontrada');
            }
            
            // Usar o serviço específico da plataforma
            $result = $this->getOrdersByPlatform(
                $integration['platform'],
                $integration,
                $params
            );
            
            return $this->respond([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao obter pedidos de delivery: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Atualizar status do pedido na plataforma
     * PUT /api/v1/delivery-integrations/{id}/orders/{orderId}/status
     */
    public function updateOrderStatus($id = null, $orderId = null)
    {
        if (!$this->hasPermission('delivery.orders.update')) {
            return $this->failForbidden('Sem permissão para atualizar pedidos de delivery');
        }
        
        try {
            $data = $this->request->getJSON(true);
            $restaurantId = $this->getRestaurantId();
            
            // Validar entrada
            $validation = \Config\Services::validation();
            $validation->setRules([
                'status' => 'required|in_list[confirmed,preparing,ready,dispatched,delivered,cancelled]',
                'reason' => 'permit_empty|string|max_length[255]'
            ]);
            
            if (!$validation->run($data)) {
                return $this->failValidationErrors($validation->getErrors());
            }
            
            $integration = $this->model
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->first();
            
            if (!$integration) {
                return $this->failNotFound('Integração ativa não encontrada');
            }
            
            // Usar o serviço específico da plataforma
            $result = $this->updateOrderStatusByPlatform(
                $integration['platform'],
                $integration,
                $orderId,
                $data['status'],
                $data['reason'] ?? null
            );
            
            return $this->respond([
                'success' => true,
                'message' => 'Status do pedido atualizado com sucesso',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao atualizar status do pedido: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    // Métodos auxiliares privados
    
    /**
     * Obter ID do restaurante do usuário autenticado
     */
    private function getRestaurantId()
    {
        return $this->request->restaurant_id ?? 1;
    }
    
    /**
     * Verificar permissão do usuário
     */
    private function hasPermission($permission)
    {
        $user = $this->request->user ?? null;
        return $user && in_array($permission, $user['permissions'] ?? []);
    }
    
    /**
     * Criptografar credenciais sensíveis
     */
    private function encryptCredentials($credentials)
    {
        $encrypter = \Config\Services::encrypter();
        $encrypted = [];
        
        foreach ($credentials as $key => $value) {
            $encrypted[$key] = $encrypter->encrypt($value);
        }
        
        return $encrypted;
    }
    
    /**
     * Descriptografar credenciais
     */
    private function decryptCredentials($credentials)
    {
        $encrypter = \Config\Services::encrypter();
        $decrypted = [];
        
        foreach ($credentials as $key => $value) {
            $decrypted[$key] = $encrypter->decrypt($value);
        }
        
        return $decrypted;
    }
    
    /**
     * Sanitizar credenciais para exibição
     */
    private function sanitizeCredentials($credentials)
    {
        $sanitized = [];
        
        foreach ($credentials as $key => $value) {
            if (in_array($key, ['password', 'secret', 'token', 'key'])) {
                $sanitized[$key] = '***';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validar credenciais específicas da plataforma
     */
    private function validatePlatformCredentials($platform, $credentials)
    {
        switch ($platform) {
            case 'ifood':
                $required = ['client_id', 'client_secret', 'merchant_id'];
                break;
            case 'ubereats':
                $required = ['client_id', 'client_secret', 'store_id'];
                break;
            case 'rappi':
                $required = ['api_key', 'store_id'];
                break;
            case '99food':
                $required = ['username', 'password', 'store_id'];
                break;
            default:
                return ['valid' => false, 'error' => 'Plataforma não suportada'];
        }
        
        foreach ($required as $field) {
            if (!isset($credentials[$field]) || empty($credentials[$field])) {
                return [
                    'valid' => false, 
                    'error' => "Campo obrigatório '{$field}' não fornecido para {$platform}"
                ];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Testar conexão com plataforma específica
     */
    private function testConnectionByPlatform($platform, $credentials)
    {
        switch ($platform) {
            case 'ifood':
                $service = new \App\Services\Delivery\IfoodService();
                return $service->testConnection($credentials);
            case 'ubereats':
                $service = new \App\Services\Delivery\UberEatsService();
                return $service->testConnection($credentials);
            case 'rappi':
                $service = new \App\Services\Delivery\RappiService();
                return $service->testConnection($credentials);
            case '99food':
                $service = new \App\Services\Delivery\NineNineFoodService();
                return $service->testConnection($credentials);
            default:
                return [
                    'success' => false,
                    'error' => 'Plataforma não suportada',
                    'tested_at' => date('Y-m-d H:i:s')
                ];
        }
    }
    
    /**
     * Obter dados do cardápio
     */
    private function getMenuData($restaurantId)
    {
        $menuModel = new \App\Models\MenuItem();
        return $menuModel->where('restaurant_id', $restaurantId)
                        ->where('is_active', true)
                        ->findAll();
    }
    
    /**
     * Sincronizar cardápio com plataforma
     */
    private function syncMenuByPlatform($platform, $integration, $menuData)
    {
        $credentials = $this->decryptCredentials(
            json_decode($integration['credentials'], true)
        );
        
        switch ($platform) {
            case 'ifood':
                $service = new \App\Services\Delivery\IfoodService();
                return $service->syncMenu($credentials, $menuData);
            case 'ubereats':
                $service = new \App\Services\Delivery\UberEatsService();
                return $service->syncMenu($credentials, $menuData);
            case 'rappi':
                $service = new \App\Services\Delivery\RappiService();
                return $service->syncMenu($credentials, $menuData);
            case '99food':
                $service = new \App\Services\Delivery\NineNineFoodService();
                return $service->syncMenu($credentials, $menuData);
            default:
                return [
                    'success' => false,
                    'error' => 'Plataforma não suportada',
                    'synced_at' => date('Y-m-d H:i:s')
                ];
        }
    }
    
    /**
     * Buscar pedidos da plataforma
     */
    private function getOrdersByPlatform($platform, $integration, $params)
    {
        $credentials = $this->decryptCredentials(
            json_decode($integration['credentials'], true)
        );
        
        switch ($platform) {
            case 'ifood':
                $service = new \App\Services\Delivery\IfoodService();
                return $service->getOrders($credentials, $params);
            case 'ubereats':
                $service = new \App\Services\Delivery\UberEatsService();
                return $service->getOrders($credentials, $params);
            case 'rappi':
                $service = new \App\Services\Delivery\RappiService();
                return $service->getOrders($credentials, $params);
            case '99food':
                $service = new \App\Services\Delivery\NineNineFoodService();
                return $service->getOrders($credentials, $params);
            default:
                return [
                    'success' => false,
                    'error' => 'Plataforma não suportada',
                    'fetched_at' => date('Y-m-d H:i:s')
                ];
        }
    }
    
    /**
     * Atualizar status do pedido na plataforma
     */
    private function updateOrderStatusByPlatform($platform, $integration, $orderId, $status, $reason)
    {
        $credentials = $this->decryptCredentials(
            json_decode($integration['credentials'], true)
        );
        
        switch ($platform) {
            case 'ifood':
                $service = new \App\Services\Delivery\IfoodService();
                return $service->updateOrderStatus($credentials, $orderId, $status, $reason);
            case 'ubereats':
                $service = new \App\Services\Delivery\UberEatsService();
                return $service->updateOrderStatus($credentials, $orderId, $status, $reason);
            case 'rappi':
                $service = new \App\Services\Delivery\RappiService();
                return $service->updateOrderStatus($credentials, $orderId, $status, $reason);
            case '99food':
                $service = new \App\Services\Delivery\NineNineFoodService();
                return $service->updateOrderStatus($credentials, $orderId, $status, $reason);
            default:
                return [
                    'success' => false,
                    'error' => 'Plataforma não suportada',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
        }
    }
    
    /**
     * Webhook para receber notificações das plataformas
     * POST /api/v1/delivery-integrations/webhook/{platform}
     */
    public function webhook($platform)
    {
        try {
            // Obter dados do webhook
            $webhookData = $this->request->getJSON(true) ?? $this->request->getPost();
            
            if (empty($webhookData)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Dados do webhook não encontrados'
                ], 400);
            }
            
            // Buscar integração da plataforma
            $integration = $this->model
                ->where('platform', $platform)
                ->where('is_active', true)
                ->first();
            
            if (!$integration) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Integração não encontrada para a plataforma'
                ], 404);
            }
            
            // Processar webhook com o serviço específico
            $result = $this->processWebhookByPlatform($platform, $webhookData, $integration);
            
            // Log da atividade
            $this->logActivity('delivery_webhook_received', [
                'platform' => $platform,
                'webhook_data' => $webhookData,
                'result' => $result
            ]);
            
            return $this->respond($result);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao processar webhook: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
    
    /**
     * Processar webhook por plataforma
     */
    private function processWebhookByPlatform($platform, $webhookData, $integration)
    {
        $credentials = $this->decryptCredentials(
            json_decode($integration['credentials'], true)
        );
        
        switch ($platform) {
            case 'ifood':
                $service = new \App\Services\Delivery\IfoodService();
                return $service->processWebhook($webhookData, $credentials);
            case 'ubereats':
                $service = new \App\Services\Delivery\UberEatsService();
                return $service->processWebhook($webhookData, $credentials);
            case 'rappi':
                $service = new \App\Services\Delivery\RappiService();
                return $service->processWebhook($webhookData, $credentials);
            case '99food':
                $service = new \App\Services\Delivery\NineNineFoodService();
                return $service->processWebhook($webhookData, $credentials);
            default:
                return [
                    'success' => false,
                    'message' => 'Plataforma não suportada',
                    'processed_at' => date('Y-m-d H:i:s')
                ];
        }
    }
    
    /**
     * Log de atividades
     */
    private function logActivity($action, $data = [])
    {
        $activityModel = new \App\Models\ActivityLog();
        $activityModel->insert([
            'restaurant_id' => $this->getRestaurantId(),
            'user_id' => $this->request->user['id'] ?? null,
            'action' => $action,
            'data' => json_encode($data),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}