<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class DeliveryIntegrations extends BaseConfig
{
    /**
     * Configurações das plataformas de delivery
     */
    public array $platforms = [
        'ifood' => [
            'name' => 'iFood',
            'api_base_url' => 'https://merchant-api.ifood.com.br',
            'auth_url' => 'https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token',
            'webhook_events' => [
                'ORDER_PLACED',
                'ORDER_CONFIRMED',
                'ORDER_CANCELLED',
                'ORDER_DISPATCHED',
                'ORDER_DELIVERED'
            ],
            'required_credentials' => [
                'client_id',
                'client_secret',
                'merchant_id'
            ],
            'status_mapping' => [
                'PLACED' => 'pending',
                'CONFIRMED' => 'confirmed',
                'PREPARING' => 'preparing',
                'READY_TO_PICKUP' => 'ready',
                'DISPATCHED' => 'dispatched',
                'DELIVERED' => 'delivered',
                'CANCELLED' => 'cancelled'
            ],
            'commission_rate' => 0.27, // 27%
            'sync_interval' => 300, // 5 minutos
            'timeout' => 30
        ],
        
        'ubereats' => [
            'name' => 'Uber Eats',
            'api_base_url' => 'https://api.uber.com',
            'auth_url' => 'https://login.uber.com/oauth/v2/token',
            'webhook_events' => [
                'orders.notification',
                'orders.status_changed',
                'orders.cancel'
            ],
            'required_credentials' => [
                'client_id',
                'client_secret',
                'store_id'
            ],
            'status_mapping' => [
                'created' => 'pending',
                'accepted' => 'confirmed',
                'denied' => 'cancelled',
                'finished' => 'preparing',
                'ready_for_pickup' => 'ready',
                'picked_up' => 'dispatched',
                'delivered' => 'delivered',
                'cancelled' => 'cancelled'
            ],
            'commission_rate' => 0.30, // 30%
            'sync_interval' => 180, // 3 minutos
            'timeout' => 25
        ],
        
        'rappi' => [
            'name' => 'Rappi',
            'api_base_url' => 'https://services.grability.rappi.com',
            'auth_url' => 'https://services.grability.rappi.com/api/auth',
            'webhook_events' => [
                'order_created',
                'order_confirmed',
                'order_cancelled',
                'order_ready',
                'order_picked_up',
                'order_delivered'
            ],
            'required_credentials' => [
                'api_key',
                'store_id'
            ],
            'status_mapping' => [
                'CREATED' => 'pending',
                'CONFIRMED' => 'confirmed',
                'PREPARING' => 'preparing',
                'READY' => 'ready',
                'PICKED_UP' => 'dispatched',
                'DELIVERED' => 'delivered',
                'CANCELLED' => 'cancelled'
            ],
            'commission_rate' => 0.28, // 28%
            'sync_interval' => 240, // 4 minutos
            'timeout' => 20
        ],
        
        '99food' => [
            'name' => '99Food',
            'api_base_url' => 'https://api.99food.com.br',
            'auth_url' => 'https://api.99food.com.br/auth/login',
            'webhook_events' => [
                'new_order',
                'order_confirmed',
                'order_cancelled',
                'order_ready',
                'order_dispatched',
                'order_delivered'
            ],
            'required_credentials' => [
                'username',
                'password',
                'store_id'
            ],
            'status_mapping' => [
                'NEW' => 'pending',
                'CONFIRMED' => 'confirmed',
                'PREPARING' => 'preparing',
                'READY' => 'ready',
                'DISPATCHED' => 'dispatched',
                'DELIVERED' => 'delivered',
                'CANCELLED' => 'cancelled'
            ],
            'commission_rate' => 0.25, // 25%
            'sync_interval' => 360, // 6 minutos
            'timeout' => 30
        ]
    ];
    
    /**
     * Configurações gerais
     */
    public array $general = [
        'max_retries' => 3,
        'retry_delay' => 5, // segundos
        'webhook_timeout' => 10, // segundos
        'cache_ttl' => 3600, // 1 hora
        'log_webhooks' => true,
        'log_api_calls' => true,
        'encrypt_credentials' => true,
        'webhook_signature_validation' => true
    ];
    
    /**
     * Configurações de sincronização
     */
    public array $sync = [
        'batch_size' => 50,
        'max_sync_age' => 86400, // 24 horas
        'auto_sync_enabled' => true,
        'sync_on_webhook' => true,
        'cleanup_old_data' => true,
        'cleanup_age' => 2592000 // 30 dias
    ];
    
    /**
     * Configurações de notificações
     */
    public array $notifications = [
        'email_on_error' => true,
        'slack_webhook_url' => env('DELIVERY_SLACK_WEBHOOK'),
        'discord_webhook_url' => env('DELIVERY_DISCORD_WEBHOOK'),
        'notify_on_new_order' => true,
        'notify_on_cancelled_order' => true,
        'notify_on_sync_failure' => true
    ];
    
    /**
     * Obter configuração de uma plataforma
     */
    public function getPlatformConfig(string $platform): ?array
    {
        return $this->platforms[$platform] ?? null;
    }
    
    /**
     * Obter todas as plataformas disponíveis
     */
    public function getAvailablePlatforms(): array
    {
        return array_keys($this->platforms);
    }
    
    /**
     * Verificar se uma plataforma é suportada
     */
    public function isPlatformSupported(string $platform): bool
    {
        return isset($this->platforms[$platform]);
    }
    
    /**
     * Obter mapeamento de status para uma plataforma
     */
    public function getStatusMapping(string $platform): array
    {
        return $this->platforms[$platform]['status_mapping'] ?? [];
    }
    
    /**
     * Obter taxa de comissão de uma plataforma
     */
    public function getCommissionRate(string $platform): float
    {
        return $this->platforms[$platform]['commission_rate'] ?? 0.0;
    }
    
    /**
     * Obter intervalo de sincronização de uma plataforma
     */
    public function getSyncInterval(string $platform): int
    {
        return $this->platforms[$platform]['sync_interval'] ?? 300;
    }
    
    /**
     * Obter eventos de webhook de uma plataforma
     */
    public function getWebhookEvents(string $platform): array
    {
        return $this->platforms[$platform]['webhook_events'] ?? [];
    }
    
    /**
     * Obter credenciais obrigatórias de uma plataforma
     */
    public function getRequiredCredentials(string $platform): array
    {
        return $this->platforms[$platform]['required_credentials'] ?? [];
    }
}