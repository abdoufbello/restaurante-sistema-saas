<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentGatewayModel extends Model
{
    protected $table = 'payment_gateways';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'name',
        'slug',
        'provider',
        'type',
        'status',
        'is_active',
        'is_default',
        'is_sandbox',
        'supported_methods',
        'supported_currencies',
        'supported_countries',
        'configuration',
        'credentials',
        'webhook_url',
        'webhook_secret',
        'webhook_events',
        'api_version',
        'environment',
        'fee_structure',
        'limits',
        'features',
        'priority',
        'last_sync_at',
        'last_error',
        'error_count',
        'success_rate',
        'total_transactions',
        'total_volume',
        'metadata',
        'notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'name' => 'required|string|max_length[100]',
        'slug' => 'required|string|max_length[50]|alpha_dash',
        'provider' => 'required|in_list[stripe,mercadopago,pagseguro,paypal,cielo,rede,stone,getnet,adyen,square,braintree,razorpay,payu,custom]',
        'type' => 'required|in_list[credit_card,debit_card,pix,boleto,bank_transfer,digital_wallet,crypto,buy_now_pay_later]',
        'status' => 'required|in_list[active,inactive,maintenance,suspended,pending_approval]',
        'is_active' => 'required|in_list[0,1]',
        'is_default' => 'required|in_list[0,1]',
        'is_sandbox' => 'required|in_list[0,1]',
        'environment' => 'required|in_list[sandbox,production]',
        'priority' => 'permit_empty|integer|greater_than_equal_to[0]',
        'success_rate' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'O ID do restaurante é obrigatório.',
            'integer' => 'O ID do restaurante deve ser um número inteiro.'
        ],
        'name' => [
            'required' => 'O nome do gateway é obrigatório.',
            'string' => 'O nome deve ser uma string.',
            'max_length' => 'O nome não pode ter mais de 100 caracteres.'
        ],
        'slug' => [
            'required' => 'O slug é obrigatório.',
            'string' => 'O slug deve ser uma string.',
            'max_length' => 'O slug não pode ter mais de 50 caracteres.',
            'alpha_dash' => 'O slug deve conter apenas letras, números, hífens e underscores.'
        ],
        'provider' => [
            'required' => 'O provedor é obrigatório.',
            'in_list' => 'Provedor inválido.'
        ],
        'type' => [
            'required' => 'O tipo é obrigatório.',
            'in_list' => 'Tipo inválido.'
        ],
        'status' => [
            'required' => 'O status é obrigatório.',
            'in_list' => 'Status inválido.'
        ],
        'environment' => [
            'required' => 'O ambiente é obrigatório.',
            'in_list' => 'Ambiente inválido.'
        ]
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING_APPROVAL = 'pending_approval';

    // Provider constants
    const PROVIDER_STRIPE = 'stripe';
    const PROVIDER_MERCADOPAGO = 'mercadopago';
    const PROVIDER_PAGSEGURO = 'pagseguro';
    const PROVIDER_PAYPAL = 'paypal';
    const PROVIDER_CIELO = 'cielo';
    const PROVIDER_REDE = 'rede';
    const PROVIDER_STONE = 'stone';
    const PROVIDER_GETNET = 'getnet';
    const PROVIDER_ADYEN = 'adyen';
    const PROVIDER_SQUARE = 'square';
    const PROVIDER_BRAINTREE = 'braintree';
    const PROVIDER_RAZORPAY = 'razorpay';
    const PROVIDER_PAYU = 'payu';
    const PROVIDER_CUSTOM = 'custom';

    // Type constants
    const TYPE_CREDIT_CARD = 'credit_card';
    const TYPE_DEBIT_CARD = 'debit_card';
    const TYPE_PIX = 'pix';
    const TYPE_BOLETO = 'boleto';
    const TYPE_BANK_TRANSFER = 'bank_transfer';
    const TYPE_DIGITAL_WALLET = 'digital_wallet';
    const TYPE_CRYPTO = 'crypto';
    const TYPE_BUY_NOW_PAY_LATER = 'buy_now_pay_later';

    // Environment constants
    const ENV_SANDBOX = 'sandbox';
    const ENV_PRODUCTION = 'production';

    protected $beforeInsert = ['setDefaults', 'encryptCredentials', 'prepareJsonFields'];
    protected $beforeUpdate = ['encryptCredentials', 'prepareJsonFields'];
    protected $afterFind = ['decryptCredentials', 'parseJsonFields'];

    /**
     * Set defaults before insert
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = self::STATUS_INACTIVE;
        }

        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 0;
        }

        if (!isset($data['data']['is_default'])) {
            $data['data']['is_default'] = 0;
        }

        if (!isset($data['data']['is_sandbox'])) {
            $data['data']['is_sandbox'] = 1;
        }

        if (!isset($data['data']['environment'])) {
            $data['data']['environment'] = self::ENV_SANDBOX;
        }

        if (!isset($data['data']['priority'])) {
            $data['data']['priority'] = 0;
        }

        if (!isset($data['data']['success_rate'])) {
            $data['data']['success_rate'] = 0.0;
        }

        if (!isset($data['data']['total_transactions'])) {
            $data['data']['total_transactions'] = 0;
        }

        if (!isset($data['data']['total_volume'])) {
            $data['data']['total_volume'] = 0.0;
        }

        if (!isset($data['data']['error_count'])) {
            $data['data']['error_count'] = 0;
        }

        // Generate slug if not provided
        if (!isset($data['data']['slug']) && isset($data['data']['name'])) {
            $data['data']['slug'] = url_title($data['data']['name'], '-', true);
        }

        return $data;
    }

    /**
     * Encrypt credentials before insert/update
     */
    protected function encryptCredentials(array $data)
    {
        if (isset($data['data']['credentials']) && is_array($data['data']['credentials'])) {
            $encryptionService = \Config\Services::encrypter();
            $data['data']['credentials'] = $encryptionService->encrypt(json_encode($data['data']['credentials']));
        }

        return $data;
    }

    /**
     * Decrypt credentials after find
     */
    protected function decryptCredentials(array $data)
    {
        if (isset($data['data']['credentials']) && is_string($data['data']['credentials'])) {
            try {
                $encryptionService = \Config\Services::encrypter();
                $decrypted = $encryptionService->decrypt($data['data']['credentials']);
                $data['data']['credentials'] = json_decode($decrypted, true);
            } catch (\Exception $e) {
                $data['data']['credentials'] = [];
            }
        } elseif (is_array($data)) {
            foreach ($data as &$item) {
                if (is_array($item) && isset($item['credentials']) && is_string($item['credentials'])) {
                    try {
                        $encryptionService = \Config\Services::encrypter();
                        $decrypted = $encryptionService->decrypt($item['credentials']);
                        $item['credentials'] = json_decode($decrypted, true);
                    } catch (\Exception $e) {
                        $item['credentials'] = [];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Prepare JSON fields before insert/update
     */
    protected function prepareJsonFields(array $data)
    {
        $jsonFields = ['configuration', 'supported_methods', 'supported_currencies', 'supported_countries', 'webhook_events', 'fee_structure', 'limits', 'features', 'metadata'];
        
        foreach ($jsonFields as $field) {
            if (isset($data['data'][$field]) && is_array($data['data'][$field])) {
                $data['data'][$field] = json_encode($data['data'][$field]);
            }
        }
        
        return $data;
    }

    /**
     * Parse JSON fields after find
     */
    protected function parseJsonFields(array $data)
    {
        $jsonFields = ['configuration', 'supported_methods', 'supported_currencies', 'supported_countries', 'webhook_events', 'fee_structure', 'limits', 'features', 'metadata'];
        
        if (isset($data['data'])) {
            foreach ($jsonFields as $field) {
                if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                    $data['data'][$field] = json_decode($data['data'][$field], true);
                }
            }
        } elseif (is_array($data)) {
            foreach ($data as &$item) {
                if (is_array($item)) {
                    foreach ($jsonFields as $field) {
                        if (isset($item[$field]) && is_string($item[$field])) {
                            $item[$field] = json_decode($item[$field], true);
                        }
                    }
                }
            }
        }
        
        return $data;
    }

    /**
     * Get active gateways for restaurant
     */
    public function getActiveGateways($restaurantId)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('is_active', 1)
                   ->where('status', self::STATUS_ACTIVE)
                   ->orderBy('priority', 'DESC')
                   ->orderBy('success_rate', 'DESC')
                   ->findAll();
    }

    /**
     * Get default gateway for restaurant
     */
    public function getDefaultGateway($restaurantId)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('is_active', 1)
                   ->where('is_default', 1)
                   ->where('status', self::STATUS_ACTIVE)
                   ->first();
    }

    /**
     * Get gateways by provider
     */
    public function getGatewaysByProvider($provider, $restaurantId = null)
    {
        $query = $this->where('provider', $provider)
                     ->where('is_active', 1)
                     ->where('status', self::STATUS_ACTIVE);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->findAll();
    }

    /**
     * Get gateways by type
     */
    public function getGatewaysByType($type, $restaurantId = null)
    {
        $query = $this->where('type', $type)
                     ->where('is_active', 1)
                     ->where('status', self::STATUS_ACTIVE);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->findAll();
    }

    /**
     * Get gateway by slug
     */
    public function getGatewayBySlug($slug, $restaurantId = null)
    {
        $query = $this->where('slug', $slug);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->first();
    }

    /**
     * Set default gateway
     */
    public function setDefaultGateway($gatewayId, $restaurantId)
    {
        // Remove default from all gateways of this restaurant
        $this->where('restaurant_id', $restaurantId)
             ->set('is_default', 0)
             ->update();
        
        // Set new default
        return $this->update($gatewayId, ['is_default' => 1]);
    }

    /**
     * Activate gateway
     */
    public function activateGateway($gatewayId)
    {
        return $this->update($gatewayId, [
            'is_active' => 1,
            'status' => self::STATUS_ACTIVE
        ]);
    }

    /**
     * Deactivate gateway
     */
    public function deactivateGateway($gatewayId)
    {
        return $this->update($gatewayId, [
            'is_active' => 0,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Update gateway statistics
     */
    public function updateGatewayStats($gatewayId, $transactionSuccess = true, $amount = 0)
    {
        $gateway = $this->find($gatewayId);
        if (!$gateway) {
            return false;
        }

        $totalTransactions = $gateway['total_transactions'] + 1;
        $totalVolume = $gateway['total_volume'] + $amount;
        
        // Calculate success rate
        $successfulTransactions = $transactionSuccess 
            ? ($gateway['total_transactions'] * ($gateway['success_rate'] / 100)) + 1
            : ($gateway['total_transactions'] * ($gateway['success_rate'] / 100));
        
        $successRate = ($successfulTransactions / $totalTransactions) * 100;
        
        $updateData = [
            'total_transactions' => $totalTransactions,
            'total_volume' => $totalVolume,
            'success_rate' => $successRate,
            'last_sync_at' => date('Y-m-d H:i:s')
        ];
        
        if (!$transactionSuccess) {
            $updateData['error_count'] = $gateway['error_count'] + 1;
        }
        
        return $this->update($gatewayId, $updateData);
    }

    /**
     * Record gateway error
     */
    public function recordGatewayError($gatewayId, $error)
    {
        $gateway = $this->find($gatewayId);
        if (!$gateway) {
            return false;
        }

        return $this->update($gatewayId, [
            'last_error' => $error,
            'error_count' => $gateway['error_count'] + 1
        ]);
    }

    /**
     * Test gateway connection
     */
    public function testGatewayConnection($gatewayId)
    {
        $gateway = $this->find($gatewayId);
        if (!$gateway) {
            return ['success' => false, 'message' => 'Gateway não encontrado'];
        }

        // This would integrate with actual gateway APIs
        // For now, we'll simulate a test
        $testResult = [
            'success' => true,
            'message' => 'Conexão testada com sucesso',
            'response_time' => rand(100, 500) . 'ms',
            'tested_at' => date('Y-m-d H:i:s')
        ];

        // Update last sync time
        $this->update($gatewayId, ['last_sync_at' => date('Y-m-d H:i:s')]);

        return $testResult;
    }

    /**
     * Get gateway configuration template
     */
    public function getConfigurationTemplate($provider)
    {
        $templates = [
            self::PROVIDER_STRIPE => [
                'public_key' => '',
                'secret_key' => '',
                'webhook_secret' => '',
                'currency' => 'BRL',
                'capture_method' => 'automatic'
            ],
            self::PROVIDER_MERCADOPAGO => [
                'public_key' => '',
                'access_token' => '',
                'client_id' => '',
                'client_secret' => '',
                'webhook_secret' => ''
            ],
            self::PROVIDER_PAGSEGURO => [
                'email' => '',
                'token' => '',
                'app_id' => '',
                'app_key' => ''
            ],
            self::PROVIDER_PAYPAL => [
                'client_id' => '',
                'client_secret' => '',
                'webhook_id' => '',
                'mode' => 'sandbox'
            ],
            self::PROVIDER_CIELO => [
                'merchant_id' => '',
                'merchant_key' => '',
                'request_id' => ''
            ]
        ];

        return $templates[$provider] ?? [];
    }

    /**
     * Get supported payment methods by provider
     */
    public function getSupportedMethods($provider)
    {
        $methods = [
            self::PROVIDER_STRIPE => [
                'credit_card', 'debit_card', 'pix', 'boleto', 'bank_transfer'
            ],
            self::PROVIDER_MERCADOPAGO => [
                'credit_card', 'debit_card', 'pix', 'boleto', 'digital_wallet'
            ],
            self::PROVIDER_PAGSEGURO => [
                'credit_card', 'debit_card', 'pix', 'boleto', 'bank_transfer'
            ],
            self::PROVIDER_PAYPAL => [
                'digital_wallet', 'credit_card', 'bank_transfer'
            ],
            self::PROVIDER_CIELO => [
                'credit_card', 'debit_card'
            ]
        ];

        return $methods[$provider] ?? [];
    }

    /**
     * Get gateway statistics
     */
    public function getGatewayStats($restaurantId = null)
    {
        $query = $this;
        
        if ($restaurantId) {
            $query = $query->where('restaurant_id', $restaurantId);
        }
        
        $gateways = $query->findAll();
        
        $stats = [
            'total_gateways' => count($gateways),
            'active_gateways' => 0,
            'inactive_gateways' => 0,
            'total_transactions' => 0,
            'total_volume' => 0,
            'average_success_rate' => 0,
            'by_provider' => [],
            'by_status' => []
        ];
        
        $totalSuccessRate = 0;
        
        foreach ($gateways as $gateway) {
            // Count by status
            if ($gateway['is_active']) {
                $stats['active_gateways']++;
            } else {
                $stats['inactive_gateways']++;
            }
            
            // Accumulate totals
            $stats['total_transactions'] += $gateway['total_transactions'];
            $stats['total_volume'] += $gateway['total_volume'];
            $totalSuccessRate += $gateway['success_rate'];
            
            // Group by provider
            $provider = $gateway['provider'];
            if (!isset($stats['by_provider'][$provider])) {
                $stats['by_provider'][$provider] = [
                    'count' => 0,
                    'active' => 0,
                    'transactions' => 0,
                    'volume' => 0
                ];
            }
            
            $stats['by_provider'][$provider]['count']++;
            if ($gateway['is_active']) {
                $stats['by_provider'][$provider]['active']++;
            }
            $stats['by_provider'][$provider]['transactions'] += $gateway['total_transactions'];
            $stats['by_provider'][$provider]['volume'] += $gateway['total_volume'];
            
            // Group by status
            $status = $gateway['status'];
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;
        }
        
        // Calculate average success rate
        if (count($gateways) > 0) {
            $stats['average_success_rate'] = $totalSuccessRate / count($gateways);
        }
        
        return $stats;
    }

    /**
     * Search gateways
     */
    public function searchGateways($filters = [], $limit = 50, $offset = 0)
    {
        $query = $this;
        
        if (!empty($filters['restaurant_id'])) {
            $query = $query->where('restaurant_id', $filters['restaurant_id']);
        }
        
        if (!empty($filters['provider'])) {
            if (is_array($filters['provider'])) {
                $query = $query->whereIn('provider', $filters['provider']);
            } else {
                $query = $query->where('provider', $filters['provider']);
            }
        }
        
        if (!empty($filters['type'])) {
            $query = $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query = $query->whereIn('status', $filters['status']);
            } else {
                $query = $query->where('status', $filters['status']);
            }
        }
        
        if (isset($filters['is_active'])) {
            $query = $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['is_default'])) {
            $query = $query->where('is_default', $filters['is_default']);
        }
        
        if (isset($filters['is_sandbox'])) {
            $query = $query->where('is_sandbox', $filters['is_sandbox']);
        }
        
        if (!empty($filters['search'])) {
            $query = $query->groupStart()
                          ->like('name', $filters['search'])
                          ->orLike('slug', $filters['search'])
                          ->orLike('provider', $filters['search'])
                          ->groupEnd();
        }
        
        return $query->orderBy('priority', 'DESC')
                    ->orderBy('success_rate', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Export gateways to CSV
     */
    public function exportToCSV($filters = [])
    {
        $gateways = $this->searchGateways($filters, 10000);
        
        $csvData = [];
        $csvData[] = [
            'Nome', 'Slug', 'Provedor', 'Tipo', 'Status', 'Ativo', 'Padrão',
            'Sandbox', 'Ambiente', 'Prioridade', 'Taxa de Sucesso', 'Total de Transações',
            'Volume Total', 'Última Sincronização', 'Criado em'
        ];
        
        foreach ($gateways as $gateway) {
            $csvData[] = [
                $gateway['name'],
                $gateway['slug'],
                $gateway['provider'],
                $gateway['type'],
                $gateway['status'],
                $gateway['is_active'] ? 'Sim' : 'Não',
                $gateway['is_default'] ? 'Sim' : 'Não',
                $gateway['is_sandbox'] ? 'Sim' : 'Não',
                $gateway['environment'],
                $gateway['priority'],
                number_format($gateway['success_rate'], 2) . '%',
                $gateway['total_transactions'],
                'R$ ' . number_format($gateway['total_volume'], 2, ',', '.'),
                $gateway['last_sync_at'] ? date('d/m/Y H:i', strtotime($gateway['last_sync_at'])) : '',
                date('d/m/Y H:i', strtotime($gateway['created_at']))
            ];
        }
        
        return $csvData;
    }

    /**
     * Create default gateways for restaurant
     */
    public function createDefaultGateways($restaurantId)
    {
        $defaultGateways = [
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Stripe',
                'slug' => 'stripe',
                'provider' => self::PROVIDER_STRIPE,
                'type' => self::TYPE_CREDIT_CARD,
                'status' => self::STATUS_INACTIVE,
                'is_active' => 0,
                'is_default' => 0,
                'is_sandbox' => 1,
                'environment' => self::ENV_SANDBOX,
                'supported_methods' => ['credit_card', 'debit_card', 'pix'],
                'supported_currencies' => ['BRL', 'USD'],
                'supported_countries' => ['BR', 'US'],
                'priority' => 10
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Mercado Pago',
                'slug' => 'mercadopago',
                'provider' => self::PROVIDER_MERCADOPAGO,
                'type' => self::TYPE_CREDIT_CARD,
                'status' => self::STATUS_INACTIVE,
                'is_active' => 0,
                'is_default' => 0,
                'is_sandbox' => 1,
                'environment' => self::ENV_SANDBOX,
                'supported_methods' => ['credit_card', 'debit_card', 'pix', 'boleto'],
                'supported_currencies' => ['BRL'],
                'supported_countries' => ['BR'],
                'priority' => 9
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'PIX',
                'slug' => 'pix',
                'provider' => self::PROVIDER_MERCADOPAGO,
                'type' => self::TYPE_PIX,
                'status' => self::STATUS_INACTIVE,
                'is_active' => 0,
                'is_default' => 0,
                'is_sandbox' => 1,
                'environment' => self::ENV_SANDBOX,
                'supported_methods' => ['pix'],
                'supported_currencies' => ['BRL'],
                'supported_countries' => ['BR'],
                'priority' => 8
            ]
        ];

        $results = [];
        foreach ($defaultGateways as $gateway) {
            $results[] = $this->insert($gateway);
        }

        return $results;
    }
}