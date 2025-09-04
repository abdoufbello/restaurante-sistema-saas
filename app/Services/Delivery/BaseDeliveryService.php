<?php

namespace App\Services\Delivery;

use Config\DeliveryIntegrations;
use Exception;

abstract class BaseDeliveryService
{
    protected DeliveryIntegrations $config;
    protected string $platform;
    protected array $platformConfig;
    protected int $timeout;
    protected int $maxRetries;
    protected int $retryDelay;
    
    public function __construct(string $platform)
    {
        $this->config = new DeliveryIntegrations();
        $this->platform = $platform;
        $this->platformConfig = $this->config->getPlatformConfig($platform) ?? [];
        $this->timeout = $this->platformConfig['timeout'] ?? 30;
        $this->maxRetries = $this->config->general['max_retries'] ?? 3;
        $this->retryDelay = $this->config->general['retry_delay'] ?? 5;
    }
    
    /**
     * Métodos abstratos que devem ser implementados pelas classes filhas
     */
    abstract public function testConnection(array $credentials): array;
    abstract public function syncMenu(array $credentials, array $menuData): array;
    abstract public function getOrders(array $credentials, array $filters = []): array;
    abstract public function updateOrderStatus(array $credentials, string $orderId, string $status, string $reason = ''): array;
    abstract public function processWebhook(array $webhookData, array $credentials): array;
    
    /**
     * Fazer requisição HTTP com retry automático
     */
    protected function makeRequest(string $url, array $options = []): array
    {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $this->maxRetries) {
            try {
                $attempt++;
                
                // Configurações padrão
                $defaultOptions = [
                    'timeout' => $this->timeout,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'User-Agent' => 'SaaS-Restaurant-Platform/1.0'
                    ]
                ];
                
                $options = array_merge_recursive($defaultOptions, $options);
                
                // Log da requisição se habilitado
                if ($this->config->general['log_api_calls']) {
                    log_message('info', "[{$this->platform}] API Request: {$url}", [
                        'options' => $this->sanitizeLogData($options)
                    ]);
                }
                
                // Fazer a requisição usando cURL
                $response = $this->executeCurlRequest($url, $options);
                
                // Log da resposta se habilitado
                if ($this->config->general['log_api_calls']) {
                    log_message('info', "[{$this->platform}] API Response", [
                        'status_code' => $response['status_code'],
                        'response' => $this->sanitizeLogData($response['body'])
                    ]);
                }
                
                return $response;
                
            } catch (Exception $e) {
                $lastError = $e;
                
                log_message('error', "[{$this->platform}] API Request failed (attempt {$attempt}): " . $e->getMessage());
                
                // Se não é a última tentativa, aguardar antes de tentar novamente
                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay);
                }
            }
        }
        
        // Se chegou aqui, todas as tentativas falharam
        throw new Exception("Falha na requisição após {$this->maxRetries} tentativas: " . $lastError->getMessage());
    }
    
    /**
     * Executar requisição cURL
     */
    private function executeCurlRequest(string $url, array $options): array
    {
        $curl = curl_init();
        
        // Configurações básicas do cURL
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $options['timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $this->formatHeaders($options['headers'] ?? [])
        ]);
        
        // Configurar método HTTP
        if (isset($options['method'])) {
            switch (strtoupper($options['method'])) {
                case 'POST':
                    curl_setopt($curl, CURLOPT_POST, true);
                    break;
                case 'PUT':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    break;
                case 'DELETE':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
                case 'PATCH':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    break;
            }
        }
        
        // Configurar dados do corpo da requisição
        if (isset($options['body'])) {
            if (is_array($options['body'])) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($options['body']));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $options['body']);
            }
        }
        
        // Executar requisição
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($response === false || !empty($error)) {
            throw new Exception("Erro na requisição cURL: {$error}");
        }
        
        return [
            'status_code' => $statusCode,
            'body' => json_decode($response, true) ?? $response
        ];
    }
    
    /**
     * Formatar headers para cURL
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "{$key}: {$value}";
        }
        return $formatted;
    }
    
    /**
     * Sanitizar dados para log (remover informações sensíveis)
     */
    protected function sanitizeLogData($data): array
    {
        if (!is_array($data)) {
            return ['data' => 'non-array-data'];
        }
        
        $sensitiveKeys = [
            'password', 'token', 'secret', 'key', 'authorization',
            'client_secret', 'api_key', 'access_token', 'refresh_token'
        ];
        
        $sanitized = $data;
        
        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '***HIDDEN***';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Mapear status da plataforma para status interno
     */
    protected function mapStatusFromPlatform(string $platformStatus): string
    {
        $mapping = $this->config->getStatusMapping($this->platform);
        return $mapping[$platformStatus] ?? 'unknown';
    }
    
    /**
     * Mapear status interno para status da plataforma
     */
    protected function mapStatusToPlatform(string $internalStatus): string
    {
        $mapping = array_flip($this->config->getStatusMapping($this->platform));
        return $mapping[$internalStatus] ?? $internalStatus;
    }
    
    /**
     * Validar credenciais obrigatórias
     */
    protected function validateCredentials(array $credentials): bool
    {
        $required = $this->config->getRequiredCredentials($this->platform);
        
        foreach ($required as $field) {
            if (empty($credentials[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Formatar resposta padrão de sucesso
     */
    protected function successResponse(array $data = [], string $message = 'Operação realizada com sucesso'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'platform' => $this->platform,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Formatar resposta padrão de erro
     */
    protected function errorResponse(string $message, array $details = [], int $code = 0): array
    {
        return [
            'success' => false,
            'message' => $message,
            'error_code' => $code,
            'details' => $details,
            'platform' => $this->platform,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Obter URL base da API da plataforma
     */
    protected function getApiBaseUrl(): string
    {
        return $this->platformConfig['api_base_url'] ?? '';
    }
    
    /**
     * Obter URL de autenticação da plataforma
     */
    protected function getAuthUrl(): string
    {
        return $this->platformConfig['auth_url'] ?? '';
    }
    
    /**
     * Verificar se um evento de webhook é suportado
     */
    protected function isWebhookEventSupported(string $event): bool
    {
        $supportedEvents = $this->config->getWebhookEvents($this->platform);
        return in_array($event, $supportedEvents);
    }
    
    /**
     * Calcular comissão da plataforma
     */
    protected function calculateCommission(float $orderTotal): float
    {
        $rate = $this->config->getCommissionRate($this->platform);
        return round($orderTotal * $rate, 2);
    }
    
    /**
     * Formatar dados do cardápio para a plataforma
     */
    protected function formatMenuForPlatform(array $menuData): array
    {
        // Implementação base - pode ser sobrescrita pelas classes filhas
        return [
            'categories' => $this->formatCategories($menuData),
            'products' => $this->formatProducts($menuData)
        ];
    }
    
    /**
     * Formatar categorias do cardápio
     */
    protected function formatCategories(array $menuData): array
    {
        $categories = [];
        
        foreach ($menuData as $item) {
            if (!isset($categories[$item['category_id']])) {
                $categories[$item['category_id']] = [
                    'id' => $item['category_id'],
                    'name' => $item['category_name'] ?? 'Categoria',
                    'description' => $item['category_description'] ?? '',
                    'active' => true
                ];
            }
        }
        
        return array_values($categories);
    }
    
    /**
     * Formatar produtos do cardápio
     */
    protected function formatProducts(array $menuData): array
    {
        $products = [];
        
        foreach ($menuData as $item) {
            $products[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'description' => $item['description'] ?? '',
                'price' => (float) $item['price'],
                'category_id' => $item['category_id'],
                'image_url' => $item['image_url'] ?? '',
                'active' => (bool) $item['is_active'],
                'preparation_time' => $item['preparation_time'] ?? 15
            ];
        }
        
        return $products;
    }
}