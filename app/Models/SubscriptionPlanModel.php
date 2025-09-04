<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionPlanModel extends Model
{
    protected $table = 'subscription_plans';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'price',
        'max_totems',
        'max_orders_per_month',
        'features',
        'is_active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[100]|is_unique[subscription_plans.name,id,{id}]',
        'price' => 'required|decimal|greater_than_equal_to[0]',
        'max_totems' => 'required|integer|greater_than[0]',
        'max_orders_per_month' => 'required|integer|greater_than[0]'
    ];
    
    protected $validationMessages = [
        'name' => [
            'is_unique' => 'Já existe um plano com este nome.'
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
     * Obter planos ativos
     */
    public function getActivePlans()
    {
        return $this->where('is_active', 1)
                   ->orderBy('price', 'ASC')
                   ->findAll();
    }

    /**
     * Obter plano por nome
     */
    public function findByName($name)
    {
        return $this->where('name', $name)->first();
    }

    /**
     * Criar planos padrão do sistema
     */
    public function createDefaultPlans()
    {
        $defaultPlans = [
            [
                'name' => 'trial',
                'price' => 0.00,
                'max_totems' => 1,
                'max_orders_per_month' => 100,
                'features' => json_encode([
                    'trial_period' => '30 dias',
                    'support' => 'Email',
                    'analytics' => 'Básico'
                ]),
                'is_active' => 1
            ],
            [
                'name' => 'starter',
                'price' => 99.00,
                'max_totems' => 2,
                'max_orders_per_month' => 500,
                'features' => json_encode([
                    'totems' => '2 totems',
                    'orders' => '500 pedidos/mês',
                    'support' => 'Email + Chat',
                    'analytics' => 'Básico',
                    'integrations' => 'PIX, Cartão'
                ]),
                'is_active' => 1
            ],
            [
                'name' => 'professional',
                'price' => 199.00,
                'max_totems' => 5,
                'max_orders_per_month' => 2000,
                'features' => json_encode([
                    'totems' => '5 totems',
                    'orders' => '2000 pedidos/mês',
                    'support' => 'Email + Chat + Telefone',
                    'analytics' => 'Avançado',
                    'integrations' => 'PIX, Cartão, Vale Refeição',
                    'reports' => 'Relatórios detalhados',
                    'api_access' => 'API completa'
                ]),
                'is_active' => 1
            ],
            [
                'name' => 'enterprise',
                'price' => 399.00,
                'max_totems' => -1, // Ilimitado
                'max_orders_per_month' => -1, // Ilimitado
                'features' => json_encode([
                    'totems' => 'Ilimitado',
                    'orders' => 'Ilimitado',
                    'support' => 'Suporte dedicado 24/7',
                    'analytics' => 'Business Intelligence',
                    'integrations' => 'Todas as integrações',
                    'reports' => 'Relatórios personalizados',
                    'api_access' => 'API completa',
                    'white_label' => 'Marca própria',
                    'custom_features' => 'Funcionalidades customizadas'
                ]),
                'is_active' => 1
            ]
        ];

        foreach ($defaultPlans as $plan) {
            // Verificar se o plano já existe
            if (!$this->findByName($plan['name'])) {
                $this->insert($plan);
            }
        }

        return true;
    }

    /**
     * Verificar se um plano permite determinado número de totems
     */
    public function allowsTotems($planName, $totemsCount)
    {
        $plan = $this->findByName($planName);
        if (!$plan) {
            return false;
        }

        return $plan['max_totems'] === -1 || $totemsCount <= $plan['max_totems'];
    }

    /**
     * Verificar se um plano permite determinado número de pedidos
     */
    public function allowsOrders($planName, $ordersCount)
    {
        $plan = $this->findByName($planName);
        if (!$plan) {
            return false;
        }

        return $plan['max_orders_per_month'] === -1 || $ordersCount <= $plan['max_orders_per_month'];
    }
}