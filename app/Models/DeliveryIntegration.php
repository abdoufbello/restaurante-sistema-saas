<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryIntegration extends Model
{
    protected $table = 'delivery_integrations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'platform',
        'credentials',
        'settings',
        'is_active',
        'last_sync_at',
        'last_sync_data',
        'last_test_at',
        'last_test_result',
        'webhook_url',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'platform' => 'required|in_list[ifood,ubereats,rappi,99food]',
        'credentials' => 'required',
        'is_active' => 'permit_empty|boolean'
    ];
    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'integer' => 'ID do restaurante deve ser um número'
        ],
        'platform' => [
            'required' => 'Plataforma é obrigatória',
            'in_list' => 'Plataforma deve ser: ifood, ubereats, rappi ou 99food'
        ],
        'credentials' => [
            'required' => 'Credenciais são obrigatórias'
        ]
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Buscar integrações ativas por restaurante
     */
    public function getActiveIntegrations($restaurantId)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('is_active', true)
                   ->findAll();
    }

    /**
     * Buscar integração por plataforma
     */
    public function getByPlatform($restaurantId, $platform)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('platform', $platform)
                   ->first();
    }

    /**
     * Ativar/Desativar integração
     */
    public function toggleActive($id, $isActive)
    {
        return $this->update($id, [
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Atualizar última sincronização
     */
    public function updateLastSync($id, $syncData)
    {
        return $this->update($id, [
            'last_sync_at' => date('Y-m-d H:i:s'),
            'last_sync_data' => json_encode($syncData),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Atualizar resultado do último teste
     */
    public function updateLastTest($id, $testResult)
    {
        return $this->update($id, [
            'last_test_at' => date('Y-m-d H:i:s'),
            'last_test_result' => json_encode($testResult),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obter estatísticas das integrações
     */
    public function getIntegrationStats($restaurantId)
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'by_platform' => [],
            'last_sync' => null
        ];

        $integrations = $this->where('restaurant_id', $restaurantId)->findAll();
        
        $stats['total'] = count($integrations);
        
        foreach ($integrations as $integration) {
            if ($integration['is_active']) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }
            
            $platform = $integration['platform'];
            if (!isset($stats['by_platform'][$platform])) {
                $stats['by_platform'][$platform] = [
                    'count' => 0,
                    'active' => 0,
                    'last_sync' => null
                ];
            }
            
            $stats['by_platform'][$platform]['count']++;
            
            if ($integration['is_active']) {
                $stats['by_platform'][$platform]['active']++;
            }
            
            if ($integration['last_sync_at']) {
                $syncTime = strtotime($integration['last_sync_at']);
                if (!$stats['last_sync'] || $syncTime > strtotime($stats['last_sync'])) {
                    $stats['last_sync'] = $integration['last_sync_at'];
                }
                
                if (!$stats['by_platform'][$platform]['last_sync'] || 
                    $syncTime > strtotime($stats['by_platform'][$platform]['last_sync'])) {
                    $stats['by_platform'][$platform]['last_sync'] = $integration['last_sync_at'];
                }
            }
        }

        return $stats;
    }

    /**
     * Verificar se integração está funcionando
     */
    public function isIntegrationHealthy($id)
    {
        $integration = $this->find($id);
        
        if (!$integration || !$integration['is_active']) {
            return false;
        }
        
        // Verificar se último teste foi bem-sucedido
        if ($integration['last_test_result']) {
            $testResult = json_decode($integration['last_test_result'], true);
            if (!$testResult['success']) {
                return false;
            }
        }
        
        // Verificar se sincronização não está muito antiga (mais de 24h)
        if ($integration['last_sync_at']) {
            $lastSync = strtotime($integration['last_sync_at']);
            $now = time();
            $hoursSinceSync = ($now - $lastSync) / 3600;
            
            if ($hoursSinceSync > 24) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Obter integrações que precisam de sincronização
     */
    public function getIntegrationsNeedingSync($hoursThreshold = 1)
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$hoursThreshold} hours"));
        
        return $this->where('is_active', true)
                   ->groupStart()
                       ->where('last_sync_at IS NULL')
                       ->orWhere('last_sync_at <', $threshold)
                   ->groupEnd()
                   ->findAll();
    }

    /**
     * Obter configurações da plataforma
     */
    public function getPlatformSettings($restaurantId, $platform)
    {
        $integration = $this->getByPlatform($restaurantId, $platform);
        
        if (!$integration) {
            return null;
        }
        
        return [
            'id' => $integration['id'],
            'platform' => $integration['platform'],
            'is_active' => $integration['is_active'],
            'settings' => json_decode($integration['settings'], true),
            'last_sync_at' => $integration['last_sync_at'],
            'webhook_url' => $integration['webhook_url']
        ];
    }

    /**
     * Atualizar configurações da integração
     */
    public function updateSettings($id, $settings)
    {
        return $this->update($id, [
            'settings' => json_encode($settings),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Definir URL do webhook
     */
    public function setWebhookUrl($id, $webhookUrl)
    {
        return $this->update($id, [
            'webhook_url' => $webhookUrl,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obter integrações com problemas
     */
    public function getProblematicIntegrations($restaurantId = null)
    {
        $builder = $this->where('is_active', true);
        
        if ($restaurantId) {
            $builder->where('restaurant_id', $restaurantId);
        }
        
        $integrations = $builder->findAll();
        $problematic = [];
        
        foreach ($integrations as $integration) {
            $issues = [];
            
            // Verificar último teste
            if ($integration['last_test_result']) {
                $testResult = json_decode($integration['last_test_result'], true);
                if (!$testResult['success']) {
                    $issues[] = 'Falha no teste de conexão';
                }
            } else {
                $issues[] = 'Nunca foi testada';
            }
            
            // Verificar sincronização
            if (!$integration['last_sync_at']) {
                $issues[] = 'Nunca foi sincronizada';
            } else {
                $lastSync = strtotime($integration['last_sync_at']);
                $hoursSinceSync = (time() - $lastSync) / 3600;
                
                if ($hoursSinceSync > 24) {
                    $issues[] = 'Sincronização desatualizada (mais de 24h)';
                }
            }
            
            if (!empty($issues)) {
                $integration['issues'] = $issues;
                $problematic[] = $integration;
            }
        }
        
        return $problematic;
    }

    /**
     * Limpar dados antigos de sincronização
     */
    public function cleanOldSyncData($daysOld = 30)
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        return $this->where('last_sync_at <', $threshold)
                   ->set([
                       'last_sync_data' => null,
                       'last_test_result' => null
                   ])
                   ->update();
    }

    /**
     * Obter resumo das plataformas disponíveis
     */
    public function getAvailablePlatforms()
    {
        return [
            'ifood' => [
                'name' => 'iFood',
                'description' => 'Maior plataforma de delivery do Brasil',
                'required_credentials' => ['client_id', 'client_secret', 'merchant_id'],
                'features' => ['menu_sync', 'order_management', 'real_time_updates'],
                'commission_range' => '12-18%'
            ],
            'ubereats' => [
                'name' => 'Uber Eats',
                'description' => 'Plataforma global de delivery da Uber',
                'required_credentials' => ['client_id', 'client_secret', 'store_id'],
                'features' => ['menu_sync', 'order_management', 'analytics'],
                'commission_range' => '15-25%'
            ],
            'rappi' => [
                'name' => 'Rappi',
                'description' => 'Super app de delivery e serviços',
                'required_credentials' => ['api_key', 'store_id'],
                'features' => ['menu_sync', 'order_management', 'promotions'],
                'commission_range' => '18-28%'
            ],
            '99food' => [
                'name' => '99Food',
                'description' => 'Plataforma de delivery da 99',
                'required_credentials' => ['username', 'password', 'store_id'],
                'features' => ['menu_sync', 'order_management'],
                'commission_range' => '10-15%'
            ]
        ];
    }
}