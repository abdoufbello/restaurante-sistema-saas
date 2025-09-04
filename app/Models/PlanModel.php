<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table = 'plans';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'price_monthly',
        'price_yearly',
        'price_currency',
        'billing_cycle',
        'trial_days',
        'setup_fee',
        'is_popular',
        'is_active',
        'is_public',
        'is_custom',
        'sort_order',
        'features',
        'max_totems',
        'max_restaurants',
        'max_users',
        'max_orders_per_month',
        'max_menu_items',
        'max_tables',
        'max_storage_gb',
        'max_api_calls_per_month',
        'max_employees',
        'has_analytics',
        'has_api_access',
        'has_custom_branding',
        'has_priority_support',
        'support_level',
        'support_channels',
        'custom_branding',
        'white_label',
        'analytics_retention_days',
        'backup_frequency',
        'backup_retention_days',
        'ssl_certificate',
        'custom_domain',
        'priority_support',
        'dedicated_manager',
        'onboarding_included',
        'training_included',
        'api_access',
        'webhook_access',
        'export_data',
        'advanced_reporting',
        'multi_location',
        'pos_integration',
        'delivery_integration',
        'payment_gateways',
        'marketing_tools',
        'loyalty_program',
        'inventory_management',
        'staff_management',
        'financial_reporting',
        'tax_management',
        'compliance_tools',
        'restrictions',
        'integrations',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[100]',
        'slug' => 'required|min_length[3]|max_length[100]|is_unique[plans.slug,id,{id}]',
        'price' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'price_monthly' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'price_yearly' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'price_currency' => 'permit_empty|in_list[BRL,USD,EUR]',
        'billing_cycle' => 'permit_empty|in_list[monthly,yearly,both]',
        'trial_days' => 'permit_empty|integer|greater_than_equal_to[0]',
        'setup_fee' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'max_totems' => 'permit_empty|integer|greater_than[0]',
        'max_restaurants' => 'permit_empty|integer|greater_than_equal_to[1]',
        'max_users' => 'permit_empty|integer|greater_than_equal_to[1]',
        'max_orders_per_month' => 'permit_empty|integer|greater_than_equal_to[1]',
        'max_menu_items' => 'permit_empty|integer|greater_than_equal_to[1]',
        'max_tables' => 'permit_empty|integer|greater_than_equal_to[1]',
        'max_storage_gb' => 'permit_empty|integer|greater_than_equal_to[1]',
        'max_employees' => 'permit_empty|integer|greater_than[0]',
        'support_level' => 'permit_empty|in_list[basic,standard,premium,enterprise]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'O nome do plano é obrigatório.',
            'max_length' => 'O nome do plano deve ter no máximo 100 caracteres.'
        ],
        'slug' => [
            'required' => 'O slug do plano é obrigatório.',
            'is_unique' => 'Este slug já está em uso.'
        ],
        'price' => [
            'required' => 'O preço é obrigatório.',
            'decimal' => 'O preço deve ser um valor decimal válido.',
            'greater_than_equal_to' => 'O preço deve ser maior ou igual a zero.'
        ],
        'billing_cycle' => [
            'required' => 'O ciclo de cobrança é obrigatório.',
            'in_list' => 'O ciclo de cobrança deve ser mensal ou anual.'
        ]
    ];

    protected $beforeInsert = ['setDefaults', 'generateSlug', 'prepareJsonFields'];
    protected $beforeUpdate = ['generateSlug', 'prepareJsonFields'];
    protected $afterFind = ['parseJsonFields'];

    /**
     * Get all active plans ordered by sort_order
     */
    public function getActivePlans()
    {
        return $this->where('is_active', 1)
                   ->orderBy('sort_order', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém planos públicos ativos
     */
    public function getPublicPlans(): array
    {
        return $this->where('is_active', 1)
                   ->where('is_public', 1)
                   ->orderBy('sort_order')
                   ->orderBy('price_monthly')
                   ->findAll();
    }

    /**
     * Get plan by slug
     */
    public function findBySlug($slug)
    {
        return $this->where('slug', $slug)
                   ->where('is_active', 1)
                   ->first();
    }
    
    /**
     * Obtém plano popular
     */
    public function getPopularPlan(): ?array
    {
        return $this->where('is_popular', 1)
                   ->where('is_active', 1)
                   ->where('is_public', 1)
                   ->first();
    }

    /**
     * Get plan features as array
     */
    public function getPlanFeatures($planId)
    {
        $plan = $this->find($planId);
        if (!$plan) {
            return [];
        }

        return json_decode($plan['features'] ?? '[]', true);
    }

    /**
     * Check if plan has specific feature
     */
    public function hasFeature($planId, $feature)
    {
        $features = $this->getPlanFeatures($planId);
        return in_array($feature, $features);
    }

    /**
     * Get plan limits
     */
    public function getPlanLimits($planId)
    {
        $plan = $this->find($planId);
        if (!$plan) {
            return null;
        }

        return [
            'max_totems' => $plan['max_totems'],
            'max_orders_per_month' => $plan['max_orders_per_month'],
            'max_employees' => $plan['max_employees'],
            'has_analytics' => (bool)$plan['has_analytics'],
            'has_api_access' => (bool)$plan['has_api_access'],
            'has_custom_branding' => (bool)$plan['has_custom_branding'],
            'has_priority_support' => (bool)$plan['has_priority_support']
        ];
    }

    /**
     * Get plans for comparison
     */
    public function getPlansForComparison()
    {
        $plans = $this->getActivePlans();
        
        foreach ($plans as &$plan) {
            $plan['features_array'] = json_decode($plan['features'] ?? '[]', true);
        }
        
        return $plans;
    }

    /**
     * Create default plans
     */
    public function createDefaultPlans()
    {
        $defaultPlans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Plano ideal para pequenos restaurantes que estão começando',
                'price' => 99.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'Até 2 totems de autoatendimento',
                    'Até 500 pedidos por mês',
                    'Até 5 funcionários',
                    'Relatórios básicos',
                    'Suporte por email'
                ]),
                'max_totems' => 2,
                'max_orders_per_month' => 500,
                'max_employees' => 5,
                'has_analytics' => 0,
                'has_api_access' => 0,
                'has_custom_branding' => 0,
                'has_priority_support' => 0,
                'is_active' => 1,
                'sort_order' => 1
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Plano completo para restaurantes em crescimento',
                'price' => 199.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'Até 5 totems de autoatendimento',
                    'Até 2000 pedidos por mês',
                    'Até 15 funcionários',
                    'Analytics avançado',
                    'Acesso à API',
                    'Suporte prioritário'
                ]),
                'max_totems' => 5,
                'max_orders_per_month' => 2000,
                'max_employees' => 15,
                'has_analytics' => 1,
                'has_api_access' => 1,
                'has_custom_branding' => 0,
                'has_priority_support' => 1,
                'is_active' => 1,
                'sort_order' => 2
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solução empresarial para grandes redes de restaurantes',
                'price' => 399.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'Totems ilimitados',
                    'Pedidos ilimitados',
                    'Funcionários ilimitados',
                    'Analytics completo',
                    'Acesso total à API',
                    'Marca personalizada',
                    'Suporte dedicado 24/7',
                    'Integração personalizada'
                ]),
                'max_totems' => null,
                'max_orders_per_month' => null,
                'max_employees' => null,
                'has_analytics' => 1,
                'has_api_access' => 1,
                'has_custom_branding' => 1,
                'has_priority_support' => 1,
                'is_active' => 1,
                'sort_order' => 3
            ]
        ];

        foreach ($defaultPlans as $plan) {
            $existing = $this->where('slug', $plan['slug'])->first();
            if (!$existing) {
                $this->insert($plan);
            }
        }
    }

    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['price_currency'])) {
            $data['data']['price_currency'] = 'BRL';
        }
        
        if (!isset($data['data']['billing_cycle'])) {
            $data['data']['billing_cycle'] = 'monthly';
        }
        
        if (!isset($data['data']['trial_days'])) {
            $data['data']['trial_days'] = 0;
        }
        
        if (!isset($data['data']['setup_fee'])) {
            $data['data']['setup_fee'] = 0;
        }
        
        if (!isset($data['data']['is_popular'])) {
            $data['data']['is_popular'] = 0;
        }
        
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        
        if (!isset($data['data']['is_public'])) {
            $data['data']['is_public'] = 1;
        }
        
        if (!isset($data['data']['is_custom'])) {
            $data['data']['is_custom'] = 0;
        }
        
        if (!isset($data['data']['sort_order'])) {
            $maxOrder = $this->selectMax('sort_order')->first();
            $data['data']['sort_order'] = ($maxOrder['sort_order'] ?? 0) + 1;
        }
        
        if (!isset($data['data']['support_level'])) {
            $data['data']['support_level'] = 'basic';
        }
        
        return $data;
    }
    
    /**
     * Gera slug automaticamente
     */
    protected function generateSlug(array $data): array
    {
        if (!isset($data['data']['slug']) && isset($data['data']['name'])) {
            $data['data']['slug'] = url_title($data['data']['name'], '-', true);
        }
        
        return $data;
    }
    
    /**
     * Prepara campos JSON antes de salvar
     */
    protected function prepareJsonFields(array $data): array
    {
        $jsonFields = ['features', 'restrictions', 'integrations', 'support_channels', 'metadata'];
        
        foreach ($jsonFields as $field) {
            if (isset($data['data'][$field]) && is_array($data['data'][$field])) {
                $data['data'][$field] = json_encode($data['data'][$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Converte campos JSON após buscar
     */
    protected function parseJsonFields(array $data): array
    {
        $jsonFields = ['features', 'restrictions', 'integrations', 'support_channels', 'metadata'];
        
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true) ?? [];
            }
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS AVANÇADOS
    // ========================================
    
    /**
     * Verifica se plano permite recurso
     */
    public function planAllowsFeature(int $planId, string $feature): bool
    {
        $plan = $this->find($planId);
        
        if (!$plan) {
            return false;
        }
        
        // Verifica recursos booleanos
        if (isset($plan[$feature])) {
            return (bool) $plan[$feature];
        }
        
        // Verifica na lista de recursos
        if (isset($plan['features']) && is_array($plan['features'])) {
            return in_array($feature, $plan['features']);
        }
        
        return false;
    }
    
    /**
     * Verifica limite do plano
     */
    public function checkPlanLimit(int $planId, string $limitType, int $currentUsage): array
    {
        $plan = $this->find($planId);
        
        if (!$plan) {
            return [
                'allowed' => false,
                'limit' => 0,
                'current' => $currentUsage,
                'remaining' => 0,
                'percentage' => 100
            ];
        }
        
        $limit = $plan[$limitType] ?? 0;
        
        // -1 ou null significa ilimitado
        if ($limit === -1 || $limit === null) {
            return [
                'allowed' => true,
                'limit' => -1,
                'current' => $currentUsage,
                'remaining' => -1,
                'percentage' => 0,
                'unlimited' => true
            ];
        }
        
        $remaining = max(0, $limit - $currentUsage);
        $percentage = $limit > 0 ? ($currentUsage / $limit) * 100 : 100;
        
        return [
            'allowed' => $currentUsage < $limit,
            'limit' => $limit,
            'current' => $currentUsage,
            'remaining' => $remaining,
            'percentage' => min(100, $percentage),
            'unlimited' => false
        ];
    }
    
    /**
     * Calcula preço com desconto anual
     */
    public function calculateYearlyDiscount(int $planId): array
    {
        $plan = $this->find($planId);
        
        if (!$plan || !$plan['price_monthly'] || !$plan['price_yearly']) {
            return [
                'monthly_total' => 0,
                'yearly_price' => 0,
                'discount_amount' => 0,
                'discount_percentage' => 0
            ];
        }
        
        $monthlyTotal = $plan['price_monthly'] * 12;
        $yearlyPrice = $plan['price_yearly'];
        $discountAmount = $monthlyTotal - $yearlyPrice;
        $discountPercentage = ($discountAmount / $monthlyTotal) * 100;
        
        return [
            'monthly_total' => $monthlyTotal,
            'yearly_price' => $yearlyPrice,
            'discount_amount' => $discountAmount,
            'discount_percentage' => round($discountPercentage, 2)
        ];
    }
    
    /**
     * Compara planos
     */
    public function comparePlans(array $planIds): array
    {
        $plans = $this->whereIn('id', $planIds)->findAll();
        
        $comparison = [
            'plans' => $plans,
            'features' => [],
            'limits' => []
        ];
        
        // Coleta todas as features únicas
        $allFeatures = [];
        foreach ($plans as $plan) {
            if (isset($plan['features']) && is_array($plan['features'])) {
                $allFeatures = array_merge($allFeatures, $plan['features']);
            }
        }
        
        $comparison['features'] = array_unique($allFeatures);
        
        // Coleta limites
        $limitFields = [
            'max_restaurants',
            'max_users',
            'max_orders_per_month',
            'max_menu_items',
            'max_tables',
            'max_storage_gb',
            'max_totems',
            'max_employees'
        ];
        
        foreach ($limitFields as $field) {
            $comparison['limits'][$field] = [];
            foreach ($plans as $plan) {
                $comparison['limits'][$field][$plan['id']] = $plan[$field] ?? 0;
            }
        }
        
        return $comparison;
    }
    
    /**
     * Busca planos
     */
    public function searchPlans(string $search): array
    {
        return $this->groupStart()
                   ->like('name', $search)
                   ->orLike('description', $search)
                   ->orLike('short_description', $search)
                   ->groupEnd()
                   ->where('is_active', 1)
                   ->orderBy('sort_order')
                   ->findAll();
    }
    
    /**
     * Obtém estatísticas dos planos
     */
    public function getPlanStats(): array
    {
        // Total de planos
        $totalPlans = $this->countAllResults();
        
        // Planos ativos
        $activePlans = $this->where('is_active', 1)->countAllResults();
        
        // Planos públicos
        $publicPlans = $this->where('is_public', 1)->countAllResults();
        
        // Planos customizados
        $customPlans = $this->where('is_custom', 1)->countAllResults();
        
        // Plano mais popular
        $popularPlan = $this->getPopularPlan();
        
        // Faixa de preços
        $priceRange = $this->selectMin('price_monthly', 'min_price')
                          ->selectMax('price_monthly', 'max_price')
                          ->where('is_active', 1)
                          ->where('price_monthly >', 0)
                          ->first();
        
        return [
            'total_plans' => $totalPlans,
            'active_plans' => $activePlans,
            'public_plans' => $publicPlans,
            'custom_plans' => $customPlans,
            'popular_plan' => $popularPlan,
            'price_range' => $priceRange
        ];
    }
    
    /**
     * Obtém planos disponíveis para upgrade
     */
    public function getUpgradePlans(int $currentPlanId): array
    {
        $currentPlan = $this->find($currentPlanId);
        
        if (!$currentPlan) {
            return [];
        }
        
        $currentPrice = $currentPlan['price_monthly'] ?? $currentPlan['price'] ?? 0;
        
        return $this->where('is_active', 1)
                   ->where('is_public', 1)
                   ->groupStart()
                   ->where('price_monthly >', $currentPrice)
                   ->orWhere('price >', $currentPrice)
                   ->groupEnd()
                   ->orderBy('price_monthly')
                   ->orderBy('price')
                   ->findAll();
    }
    
    /**
     * Obtém planos disponíveis para downgrade
     */
    public function getDowngradePlans(int $currentPlanId): array
    {
        $currentPlan = $this->find($currentPlanId);
        
        if (!$currentPlan) {
            return [];
        }
        
        $currentPrice = $currentPlan['price_monthly'] ?? $currentPlan['price'] ?? 0;
        
        return $this->where('is_active', 1)
                   ->where('is_public', 1)
                   ->groupStart()
                   ->where('price_monthly <', $currentPrice)
                   ->orWhere('price <', $currentPrice)
                   ->groupEnd()
                   ->orderBy('price_monthly', 'DESC')
                   ->orderBy('price', 'DESC')
                   ->findAll();
    }
    
    /**
     * Clona plano
     */
    public function clonePlan(int $planId, array $overrides = []): int|false
    {
        $originalPlan = $this->find($planId);
        
        if (!$originalPlan) {
            return false;
        }
        
        // Remove campos que não devem ser clonados
        unset($originalPlan['id'], $originalPlan['created_at'], $originalPlan['updated_at'], $originalPlan['deleted_at']);
        
        // Aplica sobrescrições
        $newPlan = array_merge($originalPlan, $overrides);
        
        // Garante slug único
        if (!isset($overrides['slug'])) {
            $newPlan['slug'] = $originalPlan['slug'] . '_copy_' . time();
        }
        
        // Garante nome único
        if (!isset($overrides['name'])) {
            $newPlan['name'] = $originalPlan['name'] . ' (Cópia)';
        }
        
        return $this->insert($newPlan);
    }
    
    /**
     * Exporta planos para CSV
     */
    public function exportToCSV(): string
    {
        $plans = $this->orderBy('sort_order')->findAll();
        
        $csv = "Nome,Slug,Preço Mensal,Preço Anual,Moeda,Ativo,Público,Popular\n";
        
        foreach ($plans as $plan) {
            $priceMonthly = $plan['price_monthly'] ?? $plan['price'] ?? 0;
            $priceYearly = $plan['price_yearly'] ?? 0;
            
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                str_replace('"', '""', $plan['name']),
                $plan['slug'],
                number_format($priceMonthly, 2, ',', '.'),
                number_format($priceYearly, 2, ',', '.'),
                $plan['price_currency'] ?? 'BRL',
                $plan['is_active'] ? 'Sim' : 'Não',
                ($plan['is_public'] ?? 1) ? 'Sim' : 'Não',
                ($plan['is_popular'] ?? 0) ? 'Sim' : 'Não'
            );
        }
        
        return $csv;
    }
}