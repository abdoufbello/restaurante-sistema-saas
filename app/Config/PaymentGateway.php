<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class PaymentGateway extends BaseConfig
{
    /**
     * Default payment gateway
     * Options: 'pagseguro', 'mercadopago'
     */
    public string $defaultGateway = 'mercadopago';
    
    /**
     * Environment
     * Options: 'sandbox', 'production'
     */
    public string $environment = 'sandbox';
    
    /**
     * PagSeguro Configuration
     */
    public array $pagseguro = [
        'sandbox' => [
            'api_url' => 'https://ws.sandbox.pagseguro.uol.com.br',
            'checkout_url' => 'https://sandbox.pagseguro.uol.com.br',
            'email' => '', // Set in .env as PAGSEGURO_EMAIL
            'token' => '', // Set in .env as PAGSEGURO_TOKEN
            'app_id' => '', // Set in .env as PAGSEGURO_APP_ID
            'app_key' => '', // Set in .env as PAGSEGURO_APP_KEY
        ],
        'production' => [
            'api_url' => 'https://ws.pagseguro.uol.com.br',
            'checkout_url' => 'https://pagseguro.uol.com.br',
            'email' => '', // Set in .env as PAGSEGURO_EMAIL
            'token' => '', // Set in .env as PAGSEGURO_TOKEN
            'app_id' => '', // Set in .env as PAGSEGURO_APP_ID
            'app_key' => '', // Set in .env as PAGSEGURO_APP_KEY
        ]
    ];
    
    /**
     * Mercado Pago Configuration
     */
    public array $mercadopago = [
        'sandbox' => [
            'api_url' => 'https://api.mercadopago.com',
            'checkout_url' => 'https://www.mercadopago.com.br',
            'public_key' => '', // Set in .env as MP_PUBLIC_KEY
            'access_token' => '', // Set in .env as MP_ACCESS_TOKEN
            'client_id' => '', // Set in .env as MP_CLIENT_ID
            'client_secret' => '', // Set in .env as MP_CLIENT_SECRET
            'webhook_secret' => '', // Set in .env as MP_WEBHOOK_SECRET
        ],
        'production' => [
            'api_url' => 'https://api.mercadopago.com',
            'checkout_url' => 'https://www.mercadopago.com.br',
            'public_key' => '', // Set in .env as MP_PUBLIC_KEY
            'access_token' => '', // Set in .env as MP_ACCESS_TOKEN
            'client_id' => '', // Set in .env as MP_CLIENT_ID
            'client_secret' => '', // Set in .env as MP_CLIENT_SECRET
            'webhook_secret' => '', // Set in .env as MP_WEBHOOK_SECRET
        ]
    ];
    
    /**
     * Webhook Configuration
     */
    public array $webhooks = [
        'enabled' => true,
        'timeout' => 30, // seconds
        'retry_attempts' => 3,
        'retry_delay' => 300, // seconds (5 minutes)
    ];
    
    /**
     * Subscription Configuration
     */
    public array $subscription = [
        'trial_days' => 30,
        'grace_period_days' => 7, // Days after payment failure before suspension
        'max_payment_failures' => 3,
        'auto_retry_payments' => true,
        'send_payment_reminders' => true,
        'reminder_days_before' => [7, 3, 1], // Days before payment due
    ];
    
    /**
     * Currency Configuration
     */
    public array $currency = [
        'default' => 'BRL',
        'symbol' => 'R$',
        'decimal_places' => 2,
        'decimal_separator' => ',',
        'thousands_separator' => '.'
    ];
    
    /**
     * Security Configuration
     */
    public array $security = [
        'encrypt_sensitive_data' => true,
        'log_transactions' => true,
        'mask_card_numbers' => true,
        'require_cvv' => true,
        'max_transaction_amount' => 50000.00, // R$ 50,000
    ];
    
    public function __construct()
    {
        parent::__construct();
        
        // Load environment-specific configurations
        $this->loadEnvironmentConfig();
    }
    
    /**
     * Load configuration from environment variables
     */
    protected function loadEnvironmentConfig()
    {
        // PagSeguro configuration
        $this->pagseguro['sandbox']['email'] = env('PAGSEGURO_SANDBOX_EMAIL', '');
        $this->pagseguro['sandbox']['token'] = env('PAGSEGURO_SANDBOX_TOKEN', '');
        $this->pagseguro['sandbox']['app_id'] = env('PAGSEGURO_SANDBOX_APP_ID', '');
        $this->pagseguro['sandbox']['app_key'] = env('PAGSEGURO_SANDBOX_APP_KEY', '');
        
        $this->pagseguro['production']['email'] = env('PAGSEGURO_EMAIL', '');
        $this->pagseguro['production']['token'] = env('PAGSEGURO_TOKEN', '');
        $this->pagseguro['production']['app_id'] = env('PAGSEGURO_APP_ID', '');
        $this->pagseguro['production']['app_key'] = env('PAGSEGURO_APP_KEY', '');
        
        // Mercado Pago configuration
        $this->mercadopago['sandbox']['public_key'] = env('MP_SANDBOX_PUBLIC_KEY', '');
        $this->mercadopago['sandbox']['access_token'] = env('MP_SANDBOX_ACCESS_TOKEN', '');
        $this->mercadopago['sandbox']['client_id'] = env('MP_SANDBOX_CLIENT_ID', '');
        $this->mercadopago['sandbox']['client_secret'] = env('MP_SANDBOX_CLIENT_SECRET', '');
        $this->mercadopago['sandbox']['webhook_secret'] = env('MP_SANDBOX_WEBHOOK_SECRET', '');
        
        $this->mercadopago['production']['public_key'] = env('MP_PUBLIC_KEY', '');
        $this->mercadopago['production']['access_token'] = env('MP_ACCESS_TOKEN', '');
        $this->mercadopago['production']['client_id'] = env('MP_CLIENT_ID', '');
        $this->mercadopago['production']['client_secret'] = env('MP_CLIENT_SECRET', '');
        $this->mercadopago['production']['webhook_secret'] = env('MP_WEBHOOK_SECRET', '');
        
        // Environment
        $this->environment = env('PAYMENT_ENVIRONMENT', 'sandbox');
        $this->defaultGateway = env('PAYMENT_DEFAULT_GATEWAY', 'mercadopago');
    }
    
    /**
     * Get configuration for specific gateway
     */
    public function getGatewayConfig(string $gateway = null): array
    {
        $gateway = $gateway ?? $this->defaultGateway;
        
        switch ($gateway) {
            case 'pagseguro':
                return $this->pagseguro[$this->environment];
            case 'mercadopago':
                return $this->mercadopago[$this->environment];
            default:
                throw new \InvalidArgumentException("Gateway '{$gateway}' não é suportado");
        }
    }
    
    /**
     * Check if gateway is properly configured
     */
    public function isGatewayConfigured(string $gateway = null): bool
    {
        $gateway = $gateway ?? $this->defaultGateway;
        $config = $this->getGatewayConfig($gateway);
        
        switch ($gateway) {
            case 'pagseguro':
                return !empty($config['email']) && !empty($config['token']);
            case 'mercadopago':
                return !empty($config['public_key']) && !empty($config['access_token']);
            default:
                return false;
        }
    }
    
    /**
     * Get webhook URL for gateway
     */
    public function getWebhookUrl(string $gateway = null): string
    {
        $gateway = $gateway ?? $this->defaultGateway;
        return base_url("subscription/webhook/{$gateway}");
    }
    
    /**
     * Get return URLs
     */
    public function getReturnUrls(): array
    {
        return [
            'success' => base_url('subscription/success'),
            'failure' => base_url('subscription/failure'),
            'pending' => base_url('subscription/pending'),
            'cancel' => base_url('subscription/cancel')
        ];
    }
    
    /**
     * Format currency amount
     */
    public function formatCurrency(float $amount): string
    {
        return $this->currency['symbol'] . ' ' . number_format(
            $amount,
            $this->currency['decimal_places'],
            $this->currency['decimal_separator'],
            $this->currency['thousands_separator']
        );
    }
    
    /**
     * Get supported payment methods for gateway
     */
    public function getSupportedPaymentMethods(string $gateway = null): array
    {
        $gateway = $gateway ?? $this->defaultGateway;
        
        $methods = [
            'pagseguro' => [
                'credit_card' => [
                    'name' => 'Cartão de Crédito',
                    'icon' => 'fas fa-credit-card',
                    'installments' => true,
                    'max_installments' => 12
                ],
                'debit_card' => [
                    'name' => 'Cartão de Débito',
                    'icon' => 'fas fa-credit-card',
                    'installments' => false
                ],
                'boleto' => [
                    'name' => 'Boleto Bancário',
                    'icon' => 'fas fa-barcode',
                    'installments' => false
                ],
                'pix' => [
                    'name' => 'PIX',
                    'icon' => 'fas fa-qrcode',
                    'installments' => false
                ]
            ],
            'mercadopago' => [
                'credit_card' => [
                    'name' => 'Cartão de Crédito',
                    'icon' => 'fas fa-credit-card',
                    'installments' => true,
                    'max_installments' => 12
                ],
                'debit_card' => [
                    'name' => 'Cartão de Débito',
                    'icon' => 'fas fa-credit-card',
                    'installments' => false
                ],
                'boleto' => [
                    'name' => 'Boleto Bancário',
                    'icon' => 'fas fa-barcode',
                    'installments' => false
                ],
                'pix' => [
                    'name' => 'PIX',
                    'icon' => 'fas fa-qrcode',
                    'installments' => false
                ]
            ]
        ];
        
        return $methods[$gateway] ?? [];
    }
    
    /**
     * Get installment options
     */
    public function getInstallmentOptions(float $amount, string $gateway = null): array
    {
        $gateway = $gateway ?? $this->defaultGateway;
        $methods = $this->getSupportedPaymentMethods($gateway);
        
        if (!isset($methods['credit_card']) || !$methods['credit_card']['installments']) {
            return [];
        }
        
        $maxInstallments = $methods['credit_card']['max_installments'];
        $minInstallmentAmount = 50.00; // Minimum R$ 50 per installment
        
        $options = [];
        for ($i = 1; $i <= $maxInstallments; $i++) {
            $installmentAmount = $amount / $i;
            
            if ($installmentAmount < $minInstallmentAmount && $i > 1) {
                break;
            }
            
            $options[] = [
                'installments' => $i,
                'amount' => $installmentAmount,
                'total' => $amount,
                'formatted' => $i . 'x de ' . $this->formatCurrency($installmentAmount),
                'interest_free' => $i <= 6 // First 6 installments are interest-free
            ];
        }
        
        return $options;
    }
}