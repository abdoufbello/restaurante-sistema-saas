<?php

namespace App\Models;

use CodeIgniter\Model;

class RestaurantModel extends Model
{
    protected $table = 'restaurants';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_uuid',
        'name',
        'slug',
        'cnpj',
        'address',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
        'logo_url',
        'website',
        'description',
        'cuisine_type',
        'opening_hours',
        'subscription_plan',
        'subscription_status',
        'subscription_expires_at',
        'trial_ends_at',
        'settings',
        'is_active',
        'owner_id',
        'onboarding_completed'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[255]',
        'slug' => 'required|max_length[100]|is_unique[restaurants.slug,id,{id}]',
        'cnpj' => 'required|max_length[18]|is_unique[restaurants.cnpj,id,{id}]',
        'email' => 'required|valid_email|is_unique[restaurants.email,id,{id}]',
        'subscription_plan' => 'in_list[trial,starter,professional,enterprise]',
        'subscription_status' => 'in_list[active,inactive,suspended,cancelled]',
        'owner_id' => 'required|integer'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'O nome do restaurante é obrigatório.',
            'max_length' => 'O nome deve ter no máximo 255 caracteres.'
        ],
        'slug' => [
            'required' => 'O slug é obrigatório.',
            'is_unique' => 'Este slug já está em uso.'
        ],
        'cnpj' => [
            'required' => 'O CNPJ é obrigatório.',
            'is_unique' => 'Este CNPJ já está cadastrado no sistema.'
        ],
        'email' => [
            'required' => 'O email é obrigatório.',
            'is_unique' => 'Este email já está cadastrado no sistema.',
            'valid_email' => 'Por favor, insira um email válido.'
        ],
        'owner_id' => [
            'required' => 'O proprietário é obrigatório.',
            'integer' => 'ID do proprietário inválido.'
        ]
    ];
    
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['generateSlug', 'generateTenantUuid', 'setDefaults', 'setTrialPeriod'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['generateSlug', 'updateSubscriptionStatus'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];
    
    /**
     * Buscar restaurante por CNPJ
     */
    public function findByCnpj($cnpj)
    {
        return $this->where('cnpj', $cnpj)->first();
    }
    
    /**
     * Buscar restaurante por email
     */
    public function findByEmail($email)
    {
        return $this->where('email', $email)->first();
    }
    
    /**
     * Verificar se CNPJ já existe
     */
    public function cnpjExists($cnpj, $excludeId = null)
    {
        $builder = $this->where('cnpj', $cnpj);
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    /**
     * Get restaurant with subscription details
     */
    public function getRestaurantWithSubscription($restaurantId)
    {
        return $this->select('restaurants.*, subscriptions.status as subscription_status, 
                             subscriptions.trial_ends_at, subscriptions.ends_at as subscription_ends_at,
                             plans.name as plan_name, plans.slug as plan_slug, plans.price as plan_price')
                   ->join('subscriptions', 'subscriptions.restaurant_id = restaurants.id', 'left')
                   ->join('plans', 'plans.id = subscriptions.plan_id', 'left')
                   ->where('restaurants.id', $restaurantId)
                   ->where('subscriptions.status IN ("trial", "active") OR subscriptions.id IS NULL')
                   ->first();
    }

    /**
     * Check if restaurant has active subscription
     */
    public function hasActiveSubscription($restaurantId)
    {
        $restaurant = $this->getRestaurantWithSubscription($restaurantId);
        return $restaurant && in_array($restaurant['subscription_status'], ['trial', 'active']);
    }

    /**
     * Check if restaurant is in trial
     */
    public function isInTrial($restaurantId)
    {
        $restaurant = $this->getRestaurantWithSubscription($restaurantId);
        return $restaurant && 
               $restaurant['subscription_status'] === 'trial' && 
               strtotime($restaurant['trial_ends_at']) > time();
    }

    /**
     * Get restaurant by slug
     */
    public function findBySlug($slug)
    {
        return $this->where('slug', $slug)
                   ->where('is_active', 1)
                   ->first();
    }

    /**
     * Get restaurant by owner
     */
    public function findByOwner($ownerId)
    {
        return $this->where('owner_id', $ownerId)
                   ->where('is_active', 1)
                   ->first();
    }

    /**
     * Verificar se email já existe
     */
    public function emailExists($email, $excludeId = null)
    {
        $builder = $this->where('email', $email);
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    /**
     * Buscar restaurantes ativos
     */
    public function findActive()
    {
        return $this->where('is_active', 1)->findAll();
    }

    /**
     * Get restaurant settings
     */
    public function getSettings($restaurantId)
    {
        $restaurant = $this->find($restaurantId);
        if (!$restaurant) {
            return [];
        }
        
        return json_decode($restaurant['settings'] ?? '{}', true);
    }

    /**
     * Update restaurant settings
     */
    public function updateSettings($restaurantId, $settings)
    {
        $currentSettings = $this->getSettings($restaurantId);
        $newSettings = array_merge($currentSettings, $settings);
        
        return $this->update($restaurantId, [
            'settings' => json_encode($newSettings)
        ]);
    }

    /**
     * Mark onboarding as completed
     */
    public function completeOnboarding($restaurantId)
    {
        return $this->update($restaurantId, [
            'onboarding_completed' => 1
        ]);
    }

    /**
     * Formatar CNPJ para exibição
     */
    public function formatCnpj($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) == 14) {
            return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
        }
        return $cnpj;
    }

    /**
     * Generate slug from name
     */
    protected function generateSlug(array $data)
    {
        if (isset($data['data']['name']) && empty($data['data']['slug'])) {
            $slug = url_title($data['data']['name'], '-', true);
            
            // Ensure uniqueness
            $counter = 1;
            $originalSlug = $slug;
            
            while ($this->slugExists($slug, $data['id'] ?? null)) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $data['data']['slug'] = $slug;
        }
        
        return $data;
    }

    /**
     * Check if slug exists
     */
    public function slugExists($slug, $excludeId = null)
    {
        $query = $this->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id !=', $excludeId);
        }
        
        return $query->countAllResults() > 0;
    }

    /**
     * Set defaults before insert
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        
        if (!isset($data['data']['onboarding_completed'])) {
            $data['data']['onboarding_completed'] = 0;
        }
        
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = json_encode([
                'currency' => 'BRL',
                'timezone' => 'America/Sao_Paulo',
                'language' => 'pt-BR',
                'tax_rate' => 0,
                'service_fee' => 0
            ]);
        }
        
        return $data;
    }
    
    /**
     * Gera UUID único para o tenant
     */
    protected function generateTenantUuid(array $data): array
    {
        if (!isset($data['data']['tenant_uuid'])) {
            $data['data']['tenant_uuid'] = $this->generateUuid();
        }
        return $data;
    }
    
    /**
     * Define período de trial para novos restaurantes
     */
    protected function setTrialPeriod(array $data): array
    {
        if (!isset($data['data']['subscription_plan'])) {
            $data['data']['subscription_plan'] = 'trial';
        }
        
        if (!isset($data['data']['subscription_status'])) {
            $data['data']['subscription_status'] = 'active';
        }
        
        if (!isset($data['data']['trial_ends_at'])) {
            $data['data']['trial_ends_at'] = date('Y-m-d H:i:s', strtotime('+30 days'));
        }
        
        return $data;
    }
    
    /**
     * Atualiza status da assinatura baseado na data de expiração
     */
    protected function updateSubscriptionStatus(array $data): array
    {
        if (isset($data['data']['subscription_expires_at'])) {
            $expiresAt = strtotime($data['data']['subscription_expires_at']);
            $now = time();
            
            if ($expiresAt < $now) {
                $data['data']['subscription_status'] = 'inactive';
            } else {
                $data['data']['subscription_status'] = 'active';
            }
        }
        
        return $data;
    }
    
    /**
     * Gera UUID v4
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Busca restaurante por UUID do tenant
     */
    public function findByTenantUuid(string $uuid): ?array
    {
        return $this->where('tenant_uuid', $uuid)->first();
    }
    
    /**
     * Busca restaurantes em trial
     */
    public function getTrialRestaurants(): array
    {
        return $this->where('subscription_plan', 'trial')
                   ->where('is_active', 1)
                   ->findAll();
    }
    
    /**
     * Busca restaurantes com trial expirando em X dias
     */
    public function getTrialExpiringIn(int $days = 7): array
    {
        $date = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        return $this->where('subscription_plan', 'trial')
                   ->where('trial_ends_at <=', $date)
                   ->where('is_active', 1)
                   ->findAll();
    }
    
    /**
     * Atualiza plano de assinatura
     */
    public function updateSubscriptionPlan(int $id, string $plan, ?string $expiresAt = null): bool
    {
        $data = [
            'subscription_plan' => $plan,
            'subscription_status' => 'active'
        ];
        
        if ($expiresAt) {
            $data['subscription_expires_at'] = $expiresAt;
        } else {
            // Define expiração padrão baseada no plano
            $months = $this->getSubscriptionMonths($plan);
            $data['subscription_expires_at'] = date('Y-m-d H:i:s', strtotime("+{$months} months"));
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Suspende restaurante
     */
    public function suspendRestaurant(int $id, string $reason = ''): bool
    {
        $data = [
            'subscription_status' => 'suspended',
            'is_active' => 0
        ];
        
        if ($reason) {
            $settings = $this->find($id)['settings'] ?? [];
            if (is_string($settings)) {
                $settings = json_decode($settings, true) ?? [];
            }
            $settings['suspension_reason'] = $reason;
            $settings['suspended_at'] = date('Y-m-d H:i:s');
            $data['settings'] = json_encode($settings);
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Obtém meses de assinatura por plano
     */
    private function getSubscriptionMonths(string $plan): int
    {
        $months = [
            'starter' => 1,
            'professional' => 1,
            'enterprise' => 1
        ];
        
        return $months[$plan] ?? 1;
    }
    
    /**
     * Obtém estatísticas SaaS dos restaurantes
     */
    public function getSaasStats(): array
    {
        return [
            'total' => $this->countAllResults(false),
            'active' => $this->where('is_active', 1)->countAllResults(false),
            'trial' => $this->where('subscription_plan', 'trial')->countAllResults(false),
            'paid' => $this->where('subscription_plan !=', 'trial')
                          ->where('subscription_status', 'active')
                          ->countAllResults(false),
            'suspended' => $this->where('subscription_status', 'suspended')->countAllResults(false),
            'cancelled' => $this->where('subscription_status', 'cancelled')->countAllResults(false)
        ];
    }
}