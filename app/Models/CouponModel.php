<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponModel extends Model
{
    protected $table = 'coupons';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id', 'code', 'name', 'description', 'type', 'status',
        'discount_type', 'discount_value', 'minimum_amount', 'maximum_discount',
        'usage_limit', 'usage_limit_per_customer', 'used_count', 'customer_used_count',
        'valid_from', 'valid_until', 'applicable_to', 'applicable_plans',
        'applicable_products', 'first_time_only', 'stackable', 'auto_apply',
        'promotion_id', 'referral_program', 'metadata', 'notes', 'tags'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'restaurant_id' => 'permit_empty|integer',
        'code' => 'required|string|max_length[50]|is_unique[coupons.code,id,{id}]',
        'name' => 'required|string|max_length[100]',
        'type' => 'required|in_list[percentage,fixed_amount,free_shipping,free_trial,buy_x_get_y]',
        'status' => 'required|in_list[active,inactive,expired,used_up,scheduled]',
        'discount_type' => 'required|in_list[percentage,fixed_amount]',
        'discount_value' => 'required|decimal|greater_than[0]',
        'minimum_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'maximum_discount' => 'permit_empty|decimal|greater_than[0]',
        'usage_limit' => 'permit_empty|integer|greater_than[0]',
        'usage_limit_per_customer' => 'permit_empty|integer|greater_than[0]',
        'valid_from' => 'permit_empty|valid_date',
        'valid_until' => 'permit_empty|valid_date',
        'applicable_to' => 'required|in_list[all,plans,products,subscriptions]'
    ];

    protected $validationMessages = [
        'code' => [
            'required' => 'Código do cupom é obrigatório',
            'is_unique' => 'Este código de cupom já existe'
        ],
        'name' => [
            'required' => 'Nome do cupom é obrigatório'
        ],
        'type' => [
            'required' => 'Tipo do cupom é obrigatório',
            'in_list' => 'Tipo do cupom deve ser válido'
        ],
        'status' => [
            'required' => 'Status do cupom é obrigatório',
            'in_list' => 'Status do cupom deve ser válido'
        ],
        'discount_type' => [
            'required' => 'Tipo de desconto é obrigatório',
            'in_list' => 'Tipo de desconto deve ser válido'
        ],
        'discount_value' => [
            'required' => 'Valor do desconto é obrigatório',
            'decimal' => 'Valor do desconto deve ser um número decimal',
            'greater_than' => 'Valor do desconto deve ser maior que zero'
        ],
        'applicable_to' => [
            'required' => 'Aplicabilidade do cupom é obrigatória',
            'in_list' => 'Aplicabilidade do cupom deve ser válida'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setDefaults', 'generateCode', 'prepareJsonFields'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['prepareJsonFields', 'updateStatus'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['parseJsonFields'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    // Constants
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed_amount';
    const TYPE_FREE_SHIPPING = 'free_shipping';
    const TYPE_FREE_TRIAL = 'free_trial';
    const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_EXPIRED = 'expired';
    const STATUS_USED_UP = 'used_up';
    const STATUS_SCHEDULED = 'scheduled';

    const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    const DISCOUNT_TYPE_FIXED_AMOUNT = 'fixed_amount';

    const APPLICABLE_ALL = 'all';
    const APPLICABLE_PLANS = 'plans';
    const APPLICABLE_PRODUCTS = 'products';
    const APPLICABLE_SUBSCRIPTIONS = 'subscriptions';

    /**
     * Generate coupon code if not provided
     */
    protected function generateCode(array $data)
    {
        if (!isset($data['data']['code']) || empty($data['data']['code'])) {
            do {
                $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
            } while ($this->where('code', $code)->countAllResults() > 0);
            
            $data['data']['code'] = $code;
        } else {
            $data['data']['code'] = strtoupper($data['data']['code']);
        }
        
        return $data;
    }

    /**
     * Set default values
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = self::STATUS_ACTIVE;
        }

        if (!isset($data['data']['type'])) {
            $data['data']['type'] = self::TYPE_PERCENTAGE;
        }

        if (!isset($data['data']['discount_type'])) {
            $data['data']['discount_type'] = self::DISCOUNT_TYPE_PERCENTAGE;
        }

        if (!isset($data['data']['applicable_to'])) {
            $data['data']['applicable_to'] = self::APPLICABLE_ALL;
        }

        if (!isset($data['data']['used_count'])) {
            $data['data']['used_count'] = 0;
        }

        if (!isset($data['data']['customer_used_count'])) {
            $data['data']['customer_used_count'] = 0;
        }

        if (!isset($data['data']['first_time_only'])) {
            $data['data']['first_time_only'] = 0;
        }

        if (!isset($data['data']['stackable'])) {
            $data['data']['stackable'] = 0;
        }

        if (!isset($data['data']['auto_apply'])) {
            $data['data']['auto_apply'] = 0;
        }

        if (!isset($data['data']['referral_program'])) {
            $data['data']['referral_program'] = 0;
        }

        return $data;
    }

    /**
     * Update status based on conditions
     */
    protected function updateStatus(array $data)
    {
        if (isset($data['data']['valid_until']) && $data['data']['valid_until'] < date('Y-m-d H:i:s')) {
            $data['data']['status'] = self::STATUS_EXPIRED;
        }

        if (isset($data['data']['usage_limit']) && isset($data['data']['used_count'])) {
            if ($data['data']['used_count'] >= $data['data']['usage_limit']) {
                $data['data']['status'] = self::STATUS_USED_UP;
            }
        }

        if (isset($data['data']['valid_from']) && $data['data']['valid_from'] > date('Y-m-d H:i:s')) {
            $data['data']['status'] = self::STATUS_SCHEDULED;
        }

        return $data;
    }

    /**
     * Prepare JSON fields before insert/update
     */
    protected function prepareJsonFields(array $data)
    {
        $jsonFields = ['applicable_plans', 'applicable_products', 'metadata', 'tags'];
        
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
        $jsonFields = ['applicable_plans', 'applicable_products', 'metadata', 'tags'];
        
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
     * Get active coupons
     */
    public function getActiveCoupons($restaurantId = null, $limit = 50, $offset = 0)
    {
        $query = $this->where('status', self::STATUS_ACTIVE)
                     ->where('valid_from <=', date('Y-m-d H:i:s'))
                     ->where('(valid_until IS NULL OR valid_until >=', date('Y-m-d H:i:s') . ')');
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get coupons by status
     */
    public function getCouponsByStatus($status, $restaurantId = null, $limit = 50, $offset = 0)
    {
        $query = $this->where('status', $status);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get expired coupons
     */
    public function getExpiredCoupons($restaurantId = null, $limit = 50)
    {
        $query = $this->where('valid_until <', date('Y-m-d H:i:s'))
                     ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_SCHEDULED]);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->orderBy('valid_until', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get coupons expiring soon
     */
    public function getCouponsExpiringSoon($days = 7, $restaurantId = null, $limit = 50)
    {
        $expiryDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        $query = $this->where('valid_until <=', $expiryDate)
                     ->where('valid_until >=', date('Y-m-d H:i:s'))
                     ->where('status', self::STATUS_ACTIVE);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->orderBy('valid_until', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Find coupon by code
     */
    public function findByCode($code, $restaurantId = null)
    {
        $query = $this->where('code', strtoupper($code));
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->first();
    }

    /**
     * Validate coupon
     */
    public function validateCoupon($code, $amount = 0, $customerId = null, $restaurantId = null, $planId = null)
    {
        $coupon = $this->findByCode($code, $restaurantId);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Cupom não encontrado'];
        }
        
        // Check if coupon is active
        if ($coupon['status'] !== self::STATUS_ACTIVE) {
            return ['valid' => false, 'message' => 'Cupom não está ativo'];
        }
        
        // Check validity period
        $now = date('Y-m-d H:i:s');
        if ($coupon['valid_from'] && $coupon['valid_from'] > $now) {
            return ['valid' => false, 'message' => 'Cupom ainda não é válido'];
        }
        
        if ($coupon['valid_until'] && $coupon['valid_until'] < $now) {
            return ['valid' => false, 'message' => 'Cupom expirado'];
        }
        
        // Check usage limits
        if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ['valid' => false, 'message' => 'Limite de uso do cupom atingido'];
        }
        
        // Check per-customer usage limit
        if ($customerId && $coupon['usage_limit_per_customer']) {
            $customerUsage = $this->getCustomerCouponUsage($coupon['id'], $customerId);
            if ($customerUsage >= $coupon['usage_limit_per_customer']) {
                return ['valid' => false, 'message' => 'Limite de uso por cliente atingido'];
            }
        }
        
        // Check minimum amount
        if ($coupon['minimum_amount'] && $amount < $coupon['minimum_amount']) {
            return ['valid' => false, 'message' => 'Valor mínimo não atingido'];
        }
        
        // Check first-time customer only
        if ($coupon['first_time_only'] && $customerId) {
            if ($this->isReturningCustomer($customerId, $restaurantId)) {
                return ['valid' => false, 'message' => 'Cupom válido apenas para novos clientes'];
            }
        }
        
        // Check plan applicability
        if ($coupon['applicable_to'] === self::APPLICABLE_PLANS && $planId) {
            $applicablePlans = $coupon['applicable_plans'] ?? [];
            if (!empty($applicablePlans) && !in_array($planId, $applicablePlans)) {
                return ['valid' => false, 'message' => 'Cupom não aplicável a este plano'];
            }
        }
        
        return ['valid' => true, 'coupon' => $coupon];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($coupon, $amount)
    {
        $discount = 0;
        
        switch ($coupon['discount_type']) {
            case self::DISCOUNT_TYPE_PERCENTAGE:
                $discount = $amount * ($coupon['discount_value'] / 100);
                break;
            case self::DISCOUNT_TYPE_FIXED_AMOUNT:
                $discount = $coupon['discount_value'];
                break;
        }
        
        // Apply maximum discount limit
        if ($coupon['maximum_discount'] && $discount > $coupon['maximum_discount']) {
            $discount = $coupon['maximum_discount'];
        }
        
        // Ensure discount doesn't exceed the amount
        if ($discount > $amount) {
            $discount = $amount;
        }
        
        return $discount;
    }

    /**
     * Apply coupon (increment usage count)
     */
    public function applyCoupon($couponId, $customerId = null, $amount = 0)
    {
        $coupon = $this->find($couponId);
        if (!$coupon) {
            return false;
        }
        
        $updateData = [
            'used_count' => $coupon['used_count'] + 1
        ];
        
        // Update status if usage limit reached
        if ($coupon['usage_limit'] && $updateData['used_count'] >= $coupon['usage_limit']) {
            $updateData['status'] = self::STATUS_USED_UP;
        }
        
        $result = $this->update($couponId, $updateData);
        
        // Record customer usage
        if ($result && $customerId) {
            $this->recordCustomerUsage($couponId, $customerId, $amount);
        }
        
        return $result;
    }

    /**
     * Record customer coupon usage
     */
    protected function recordCustomerUsage($couponId, $customerId, $amount)
    {
        $db = \Config\Database::connect();
        
        $data = [
            'coupon_id' => $couponId,
            'customer_id' => $customerId,
            'amount' => $amount,
            'used_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->table('coupon_usage')->insert($data);
    }

    /**
     * Get customer coupon usage count
     */
    public function getCustomerCouponUsage($couponId, $customerId)
    {
        $db = \Config\Database::connect();
        
        return $db->table('coupon_usage')
                 ->where('coupon_id', $couponId)
                 ->where('customer_id', $customerId)
                 ->countAllResults();
    }

    /**
     * Check if customer is returning
     */
    protected function isReturningCustomer($customerId, $restaurantId = null)
    {
        $subscriptionModel = new SubscriptionModel();
        
        $query = $subscriptionModel->where('user_id', $customerId)
                                  ->where('status !=', SubscriptionModel::STATUS_TRIAL);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->countAllResults() > 0;
    }

    /**
     * Deactivate coupon
     */
    public function deactivateCoupon($couponId, $reason = null)
    {
        $updateData = ['status' => self::STATUS_INACTIVE];
        
        if ($reason) {
            $updateData['notes'] = $reason;
        }
        
        return $this->update($couponId, $updateData);
    }

    /**
     * Activate coupon
     */
    public function activateCoupon($couponId)
    {
        return $this->update($couponId, ['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Update expired coupons status
     */
    public function updateExpiredCoupons()
    {
        return $this->set('status', self::STATUS_EXPIRED)
                   ->where('valid_until <', date('Y-m-d H:i:s'))
                   ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_SCHEDULED])
                   ->update();
    }

    /**
     * Update scheduled coupons status
     */
    public function updateScheduledCoupons()
    {
        return $this->set('status', self::STATUS_ACTIVE)
                   ->where('valid_from <=', date('Y-m-d H:i:s'))
                   ->where('status', self::STATUS_SCHEDULED)
                   ->update();
    }

    /**
     * Get coupon statistics
     */
    public function getCouponStats($restaurantId = null, $period = '30 days')
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        $query = $this->select([
            'COUNT(*) as total_coupons',
            'COUNT(CASE WHEN status = "active" THEN 1 END) as active_coupons',
            'COUNT(CASE WHEN status = "expired" THEN 1 END) as expired_coupons',
            'COUNT(CASE WHEN status = "used_up" THEN 1 END) as used_up_coupons',
            'SUM(used_count) as total_usage',
            'AVG(used_count) as average_usage_per_coupon'
        ]);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        $stats = $query->first();
        
        // Get most used coupons
        $mostUsedQuery = $this->select('code, name, used_count')
                             ->orderBy('used_count', 'DESC')
                             ->limit(5);
        
        if ($restaurantId) {
            $mostUsedQuery->where('restaurant_id', $restaurantId);
        }
        
        $stats['most_used'] = $mostUsedQuery->findAll();
        
        // Get usage by discount type
        $usageByTypeQuery = $this->select('discount_type, COUNT(*) as count, SUM(used_count) as usage')
                                ->groupBy('discount_type');
        
        if ($restaurantId) {
            $usageByTypeQuery->where('restaurant_id', $restaurantId);
        }
        
        $stats['by_discount_type'] = $usageByTypeQuery->findAll();
        
        return $stats;
    }

    /**
     * Advanced search coupons
     */
    public function advancedSearch($filters = [], $limit = 50, $offset = 0)
    {
        $query = $this->select('coupons.*');
        
        if (!empty($filters['search'])) {
            $query->groupStart()
                  ->like('code', $filters['search'])
                  ->orLike('name', $filters['search'])
                  ->orLike('description', $filters['search'])
                  ->groupEnd();
        }
        
        if (!empty($filters['restaurant_id'])) {
            $query->where('restaurant_id', $filters['restaurant_id']);
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }
        
        if (!empty($filters['applicable_to'])) {
            $query->where('applicable_to', $filters['applicable_to']);
        }
        
        if (!empty($filters['valid_from'])) {
            $query->where('valid_from >=', $filters['valid_from']);
        }
        
        if (!empty($filters['valid_until'])) {
            $query->where('valid_until <=', $filters['valid_until']);
        }
        
        if (!empty($filters['min_discount'])) {
            $query->where('discount_value >=', $filters['min_discount']);
        }
        
        if (!empty($filters['max_discount'])) {
            $query->where('discount_value <=', $filters['max_discount']);
        }
        
        if (!empty($filters['first_time_only'])) {
            $query->where('first_time_only', 1);
        }
        
        if (!empty($filters['stackable'])) {
            $query->where('stackable', 1);
        }
        
        if (!empty($filters['auto_apply'])) {
            $query->where('auto_apply', 1);
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $query->orderBy($orderBy, $orderDir)
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Export coupons to CSV
     */
    public function exportToCSV($filters = [])
    {
        $coupons = $this->advancedSearch($filters, 10000);
        
        $csvData = [];
        $csvData[] = [
            'ID', 'Código', 'Nome', 'Tipo', 'Status', 'Tipo de Desconto', 'Valor do Desconto',
            'Valor Mínimo', 'Desconto Máximo', 'Limite de Uso', 'Usado', 'Válido De', 'Válido Até',
            'Aplicável A', 'Apenas Novos Clientes', 'Empilhável', 'Auto Aplicar', 'Criado em'
        ];
        
        foreach ($coupons as $coupon) {
            $csvData[] = [
                $coupon['id'],
                $coupon['code'],
                $coupon['name'],
                $coupon['type'],
                $coupon['status'],
                $coupon['discount_type'],
                $coupon['discount_value'],
                $coupon['minimum_amount'] ?? '',
                $coupon['maximum_discount'] ?? '',
                $coupon['usage_limit'] ?? '',
                $coupon['used_count'],
                $coupon['valid_from'] ?? '',
                $coupon['valid_until'] ?? '',
                $coupon['applicable_to'],
                $coupon['first_time_only'] ? 'Sim' : 'Não',
                $coupon['stackable'] ? 'Sim' : 'Não',
                $coupon['auto_apply'] ? 'Sim' : 'Não',
                $coupon['created_at']
            ];
        }
        
        return $csvData;
    }

    /**
     * Get auto-apply coupons for amount
     */
    public function getAutoApplyCoupons($amount, $restaurantId = null, $customerId = null, $planId = null)
    {
        $query = $this->where('auto_apply', 1)
                     ->where('status', self::STATUS_ACTIVE)
                     ->where('valid_from <=', date('Y-m-d H:i:s'))
                     ->where('(valid_until IS NULL OR valid_until >=', date('Y-m-d H:i:s') . ')')
                     ->where('(minimum_amount IS NULL OR minimum_amount <=', $amount . ')')
                     ->where('(usage_limit IS NULL OR used_count < usage_limit)');
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        $coupons = $query->orderBy('discount_value', 'DESC')->findAll();
        
        // Filter by additional validation rules
        $validCoupons = [];
        foreach ($coupons as $coupon) {
            $validation = $this->validateCoupon($coupon['code'], $amount, $customerId, $restaurantId, $planId);
            if ($validation['valid']) {
                $validCoupons[] = $coupon;
            }
        }
        
        return $validCoupons;
    }

    /**
     * Get best coupon for amount
     */
    public function getBestCoupon($amount, $restaurantId = null, $customerId = null, $planId = null)
    {
        $coupons = $this->getAutoApplyCoupons($amount, $restaurantId, $customerId, $planId);
        
        $bestCoupon = null;
        $bestDiscount = 0;
        
        foreach ($coupons as $coupon) {
            $discount = $this->calculateDiscount($coupon, $amount);
            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $bestCoupon = $coupon;
            }
        }
        
        return $bestCoupon;
    }
}