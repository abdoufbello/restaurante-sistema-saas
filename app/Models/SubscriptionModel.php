<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table = 'subscriptions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'user_id',
        'plan_id',
        'status',
        'billing_cycle',
        'amount',
        'currency',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
        'grace_period_ends_at',
        'payment_method',
        'payment_gateway',
        'payment_gateway_id',
        'gateway_subscription_id',
        'gateway_customer_id',
        'last_payment_at',
        'next_payment_at',
        'payment_failures',
        'max_payment_failures',
        'auto_renew',
        'proration_enabled',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'tax_percentage',
        'subtotal',
        'total',
        'invoice_id',
        'notes',
        'metadata',
        'tags'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'user_id' => 'permit_empty|integer',
        'plan_id' => 'required|integer',
        'status' => 'required|in_list[trial,active,past_due,canceled,suspended,paused,expired]',
        'billing_cycle' => 'required|in_list[monthly,yearly,quarterly,weekly]',
        'amount' => 'required|decimal',
        'currency' => 'required|string|max_length[3]',
        'starts_at' => 'required|valid_date',
        'ends_at' => 'permit_empty|valid_date',
        'trial_ends_at' => 'permit_empty|valid_date',
        'canceled_at' => 'permit_empty|valid_date',
        'grace_period_ends_at' => 'permit_empty|valid_date',
        'payment_method' => 'permit_empty|string|max_length[50]',
        'payment_gateway' => 'permit_empty|string|max_length[50]',
        'payment_failures' => 'permit_empty|integer',
        'max_payment_failures' => 'permit_empty|integer',
        'auto_renew' => 'permit_empty|in_list[0,1]',
        'proration_enabled' => 'permit_empty|in_list[0,1]',
        'discount_amount' => 'permit_empty|decimal',
        'discount_percentage' => 'permit_empty|decimal',
        'tax_amount' => 'permit_empty|decimal',
        'tax_percentage' => 'permit_empty|decimal',
        'subtotal' => 'permit_empty|decimal',
        'total' => 'permit_empty|decimal'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'O ID do restaurante é obrigatório.',
            'integer' => 'O ID do restaurante deve ser um número inteiro.'
        ],
        'plan_id' => [
            'required' => 'O ID do plano é obrigatório.',
            'integer' => 'O ID do plano deve ser um número inteiro.'
        ],
        'status' => [
            'required' => 'O status da assinatura é obrigatório.',
            'in_list' => 'Status inválido para a assinatura.'
        ]
    ];

    // Status constants
    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_CANCELED = 'canceled';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PAUSED = 'paused';
    const STATUS_EXPIRED = 'expired';

    // Billing cycle constants
    const CYCLE_MONTHLY = 'monthly';
    const CYCLE_YEARLY = 'yearly';
    const CYCLE_QUARTERLY = 'quarterly';
    const CYCLE_WEEKLY = 'weekly';

    protected $beforeInsert = ['setDefaults', 'calculateAmounts', 'prepareJsonFields'];
    protected $beforeUpdate = ['updateTimestamps', 'calculateAmounts', 'prepareJsonFields'];
    protected $afterFind = ['parseJsonFields'];

    /**
     * Get active subscription for restaurant
     */
    public function getActiveSubscription($restaurantId)
    {
        return $this->select('subscriptions.*, plans.name as plan_name, plans.slug as plan_slug, plans.price, plans.billing_cycle')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->where('subscriptions.restaurant_id', $restaurantId)
                   ->whereIn('subscriptions.status', ['trial', 'active'])
                   ->orderBy('subscriptions.created_at', 'DESC')
                   ->first();
    }

    /**
     * Check if restaurant has active subscription
     */
    public function hasActiveSubscription($restaurantId)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->whereIn('status', ['trial', 'active'])
                   ->countAllResults() > 0;
    }

    /**
     * Check if restaurant is in trial period
     */
    public function isInTrial($restaurantId)
    {
        $subscription = $this->where('restaurant_id', $restaurantId)
                            ->where('status', 'trial')
                            ->where('trial_ends_at >=', date('Y-m-d H:i:s'))
                            ->first();
        
        return $subscription !== null;
    }

    /**
     * Get subscription with plan details
     */
    public function getSubscriptionWithPlan($subscriptionId)
    {
        return $this->select('subscriptions.*, plans.*')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->where('subscriptions.id', $subscriptionId)
                   ->first();
    }

    /**
     * Create trial subscription
     */
    public function createTrialSubscription($restaurantId, $planId, $trialDays = 30)
    {
        $trialEndsAt = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));
        
        $data = [
            'restaurant_id' => $restaurantId,
            'plan_id' => $planId,
            'status' => 'trial',
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => date('Y-m-d H:i:s'),
            'ends_at' => null,
            'payment_failures' => 0
        ];

        return $this->insert($data);
    }

    /**
     * Activate subscription (convert from trial or create new)
     */
    public function activateSubscription($restaurantId, $planId, $paymentMethod = null, $paymentGatewayId = null)
    {
        // Check if there's an existing trial
        $existingSubscription = $this->where('restaurant_id', $restaurantId)
                                    ->where('status', 'trial')
                                    ->first();

        $startsAt = date('Y-m-d H:i:s');
        $endsAt = date('Y-m-d H:i:s', strtotime('+1 month')); // Default to monthly
        
        $data = [
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'last_payment_at' => $startsAt,
            'next_payment_at' => $endsAt,
            'payment_method' => $paymentMethod,
            'payment_gateway_id' => $paymentGatewayId,
            'payment_failures' => 0
        ];

        if ($existingSubscription) {
            // Update existing trial subscription
            $data['plan_id'] = $planId;
            return $this->update($existingSubscription['id'], $data);
        } else {
            // Create new active subscription
            $data['restaurant_id'] = $restaurantId;
            $data['plan_id'] = $planId;
            return $this->insert($data);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription($subscriptionId, $immediately = false)
    {
        $subscription = $this->find($subscriptionId);
        if (!$subscription) {
            return false;
        }

        $data = [
            'status' => 'canceled',
            'canceled_at' => date('Y-m-d H:i:s')
        ];

        if ($immediately) {
            $data['ends_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($subscriptionId, $data);
    }

    /**
     * Suspend subscription
     */
    public function suspendSubscription($subscriptionId, $reason = null)
    {
        $metadata = $reason ? json_encode(['suspension_reason' => $reason]) : null;
        
        return $this->update($subscriptionId, [
            'status' => 'suspended',
            'metadata' => $metadata
        ]);
    }

    /**
     * Reactivate suspended subscription
     */
    public function reactivateSubscription($subscriptionId)
    {
        return $this->update($subscriptionId, [
            'status' => 'active',
            'metadata' => null
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function recordPaymentFailure($subscriptionId)
    {
        $subscription = $this->find($subscriptionId);
        if (!$subscription) {
            return false;
        }

        $failures = $subscription['payment_failures'] + 1;
        $status = $failures >= 3 ? 'past_due' : $subscription['status'];

        return $this->update($subscriptionId, [
            'payment_failures' => $failures,
            'status' => $status
        ]);
    }

    /**
     * Record successful payment
     */
    public function recordSuccessfulPayment($subscriptionId, $nextPaymentDate = null)
    {
        $nextPayment = $nextPaymentDate ?: date('Y-m-d H:i:s', strtotime('+1 month'));
        
        return $this->update($subscriptionId, [
            'status' => 'active',
            'last_payment_at' => date('Y-m-d H:i:s'),
            'next_payment_at' => $nextPayment,
            'payment_failures' => 0
        ]);
    }

    /**
     * Get subscriptions due for renewal
     */
    public function getSubscriptionsDueForRenewal($days = 3)
    {
        $dueDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        return $this->select('subscriptions.*, plans.name as plan_name, plans.price')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->where('subscriptions.status', 'active')
                   ->where('subscriptions.next_payment_at <=', $dueDate)
                   ->findAll();
    }

    /**
     * Get expired trials
     */
    public function getExpiredTrials()
    {
        return $this->select('subscriptions.*, plans.name as plan_name')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->where('subscriptions.status', 'trial')
                   ->where('subscriptions.trial_ends_at <', date('Y-m-d H:i:s'))
                   ->findAll();
    }

    /**
     * Get subscription statistics
     */
    public function getSubscriptionStats()
    {
        $stats = [];
        
        // Count by status
        $statusCounts = $this->select('status, COUNT(*) as count')
                           ->groupBy('status')
                           ->findAll();
        
        foreach ($statusCounts as $status) {
            $stats['by_status'][$status['status']] = $status['count'];
        }
        
        // Count by plan
        $planCounts = $this->select('plans.name, COUNT(*) as count')
                          ->join('plans', 'plans.id = subscriptions.plan_id')
                          ->where('subscriptions.status', 'active')
                          ->groupBy('plans.id')
                          ->findAll();
        
        foreach ($planCounts as $plan) {
            $stats['by_plan'][$plan['name']] = $plan['count'];
        }
        
        // Monthly recurring revenue
        $mrr = $this->select('SUM(plans.price) as total')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->where('subscriptions.status', 'active')
                   ->where('plans.billing_cycle', 'monthly')
                   ->first();
        
        $stats['mrr'] = $mrr['total'] ?? 0;
        
        return $stats;
    }

    /**
     * Get subscriptions by status
     */
    public function getSubscriptionsByStatus($status, $limit = null)
    {
        $query = $this->select('subscriptions.*, plans.name as plan_name, plans.slug as plan_slug')
                     ->join('plans', 'plans.id = subscriptions.plan_id')
                     ->where('subscriptions.status', $status)
                     ->orderBy('subscriptions.created_at', 'DESC');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->findAll();
    }

    /**
     * Get subscriptions expiring soon
     */
    public function getExpiringSubscriptions($days = 7)
    {
        $expirationDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        return $this->select('subscriptions.*, plans.name as plan_name, restaurants.name as restaurant_name')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->join('restaurants', 'restaurants.id = subscriptions.restaurant_id')
                   ->where('subscriptions.status', self::STATUS_ACTIVE)
                   ->where('subscriptions.ends_at <=', $expirationDate)
                   ->where('subscriptions.ends_at >=', date('Y-m-d H:i:s'))
                   ->orderBy('subscriptions.ends_at', 'ASC')
                   ->findAll();
    }

    /**
     * Get expired subscriptions
     */
    public function getExpiredSubscriptions()
    {
        return $this->select('subscriptions.*, plans.name as plan_name, restaurants.name as restaurant_name')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->join('restaurants', 'restaurants.id = subscriptions.restaurant_id')
                   ->whereIn('subscriptions.status', [self::STATUS_ACTIVE, self::STATUS_PAST_DUE])
                   ->where('subscriptions.ends_at <', date('Y-m-d H:i:s'))
                   ->orderBy('subscriptions.ends_at', 'ASC')
                   ->findAll();
    }

    /**
     * Get subscriptions in grace period
     */
    public function getSubscriptionsInGracePeriod()
    {
        return $this->select('subscriptions.*, plans.name as plan_name, restaurants.name as restaurant_name')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->join('restaurants', 'restaurants.id = subscriptions.restaurant_id')
                   ->where('subscriptions.status', self::STATUS_PAST_DUE)
                   ->where('subscriptions.grace_period_ends_at >=', date('Y-m-d H:i:s'))
                   ->orderBy('subscriptions.grace_period_ends_at', 'ASC')
                   ->findAll();
    }

    /**
     * Get subscriptions with payment failures
     */
    public function getSubscriptionsWithPaymentFailures($minFailures = 1)
    {
        return $this->select('subscriptions.*, plans.name as plan_name, restaurants.name as restaurant_name')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->join('restaurants', 'restaurants.id = subscriptions.restaurant_id')
                   ->where('subscriptions.payment_failures >=', $minFailures)
                   ->whereIn('subscriptions.status', [self::STATUS_ACTIVE, self::STATUS_PAST_DUE])
                   ->orderBy('subscriptions.payment_failures', 'DESC')
                   ->findAll();
    }

    /**
     * Create subscription
     */
    public function createSubscription($data)
    {
        // Validate required fields
        if (!isset($data['restaurant_id']) || !isset($data['plan_id'])) {
            return false;
        }

        // Get plan details
        $planModel = new \App\Models\PlanModel();
        $plan = $planModel->find($data['plan_id']);
        
        if (!$plan) {
            return false;
        }

        // Set subscription data based on plan
        $subscriptionData = [
            'restaurant_id' => $data['restaurant_id'],
            'user_id' => $data['user_id'] ?? null,
            'plan_id' => $data['plan_id'],
            'status' => $data['status'] ?? self::STATUS_TRIAL,
            'billing_cycle' => $data['billing_cycle'] ?? $plan['billing_cycle'] ?? self::CYCLE_MONTHLY,
            'amount' => $data['amount'] ?? ($data['billing_cycle'] === self::CYCLE_YEARLY ? $plan['price_yearly'] : $plan['price_monthly']),
            'currency' => $data['currency'] ?? 'BRL',
            'trial_ends_at' => $data['trial_ends_at'] ?? date('Y-m-d H:i:s', strtotime('+' . ($plan['trial_days'] ?? 30) . ' days')),
            'starts_at' => $data['starts_at'] ?? date('Y-m-d H:i:s'),
            'payment_method' => $data['payment_method'] ?? null,
            'payment_gateway' => $data['payment_gateway'] ?? null,
            'discount_percentage' => $data['discount_percentage'] ?? 0,
            'tax_percentage' => $data['tax_percentage'] ?? 0,
            'metadata' => $data['metadata'] ?? [],
            'tags' => $data['tags'] ?? []
        ];

        // Calculate end date based on billing cycle
        if (!isset($data['ends_at'])) {
            $interval = match($subscriptionData['billing_cycle']) {
                self::CYCLE_WEEKLY => '+1 week',
                self::CYCLE_MONTHLY => '+1 month',
                self::CYCLE_QUARTERLY => '+3 months',
                self::CYCLE_YEARLY => '+1 year',
                default => '+1 month'
            };
            $subscriptionData['ends_at'] = date('Y-m-d H:i:s', strtotime($interval));
        }

        return $this->insert($subscriptionData);
    }

    /**
     * Renew subscription
     */
    public function renewSubscription($subscriptionId, $paymentSuccessful = true)
    {
        $subscription = $this->find($subscriptionId);
        if (!$subscription) {
            return false;
        }

        $interval = match($subscription['billing_cycle']) {
            self::CYCLE_WEEKLY => '+1 week',
            self::CYCLE_MONTHLY => '+1 month',
            self::CYCLE_QUARTERLY => '+3 months',
            self::CYCLE_YEARLY => '+1 year',
            default => '+1 month'
        };

        $updateData = [
            'starts_at' => $subscription['ends_at'],
            'ends_at' => date('Y-m-d H:i:s', strtotime($interval, strtotime($subscription['ends_at']))),
            'next_payment_at' => date('Y-m-d H:i:s', strtotime($interval, strtotime($subscription['ends_at']))),
            'payment_failures' => 0
        ];

        if ($paymentSuccessful) {
            $updateData['status'] = self::STATUS_ACTIVE;
            $updateData['last_payment_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($subscriptionId, $updateData);
    }

    /**
     * Change subscription plan
     */
    public function changePlan($subscriptionId, $newPlanId, $prorated = true)
    {
        $subscription = $this->find($subscriptionId);
        if (!$subscription) {
            return false;
        }

        $planModel = new \App\Models\PlanModel();
        $newPlan = $planModel->find($newPlanId);
        
        if (!$newPlan) {
            return false;
        }

        $updateData = [
            'plan_id' => $newPlanId,
            'amount' => $subscription['billing_cycle'] === self::CYCLE_YEARLY ? $newPlan['price_yearly'] : $newPlan['price_monthly']
        ];

        // Calculate proration if enabled
        if ($prorated && $subscription['proration_enabled']) {
            $remainingDays = (strtotime($subscription['ends_at']) - time()) / (24 * 60 * 60);
            $totalDays = match($subscription['billing_cycle']) {
                self::CYCLE_WEEKLY => 7,
                self::CYCLE_MONTHLY => 30,
                self::CYCLE_QUARTERLY => 90,
                self::CYCLE_YEARLY => 365,
                default => 30
            };
            
            $proratedAmount = ($updateData['amount'] / $totalDays) * $remainingDays;
            $updateData['metadata'] = array_merge($subscription['metadata'] ?? [], [
                'plan_change' => [
                    'old_plan_id' => $subscription['plan_id'],
                    'new_plan_id' => $newPlanId,
                    'prorated_amount' => $proratedAmount,
                    'changed_at' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        return $this->update($subscriptionId, $updateData);
    }

    /**
     * Get subscription history for restaurant
     */
    public function getSubscriptionHistory($restaurantId, $limit = 10)
    {
        return $this->select('subscriptions.*, plans.name as plan_name, plans.slug as plan_slug')
                   ->join('plans', 'plans.id = subscriptions.plan_id')
                   ->where('subscriptions.restaurant_id', $restaurantId)
                   ->orderBy('subscriptions.created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Advanced search for subscriptions
     */
    public function searchSubscriptions($filters = [], $limit = 50, $offset = 0)
    {
        $query = $this->select('subscriptions.*, plans.name as plan_name, restaurants.name as restaurant_name')
                     ->join('plans', 'plans.id = subscriptions.plan_id')
                     ->join('restaurants', 'restaurants.id = subscriptions.restaurant_id');

        // Apply filters
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('subscriptions.status', $filters['status']);
            } else {
                $query->where('subscriptions.status', $filters['status']);
            }
        }

        if (!empty($filters['plan_id'])) {
            $query->where('subscriptions.plan_id', $filters['plan_id']);
        }

        if (!empty($filters['billing_cycle'])) {
            $query->where('subscriptions.billing_cycle', $filters['billing_cycle']);
        }

        if (!empty($filters['restaurant_id'])) {
            $query->where('subscriptions.restaurant_id', $filters['restaurant_id']);
        }

        if (!empty($filters['payment_gateway'])) {
            $query->where('subscriptions.payment_gateway', $filters['payment_gateway']);
        }

        if (!empty($filters['created_from'])) {
            $query->where('subscriptions.created_at >=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->where('subscriptions.created_at <=', $filters['created_to']);
        }

        if (!empty($filters['expires_from'])) {
            $query->where('subscriptions.ends_at >=', $filters['expires_from']);
        }

        if (!empty($filters['expires_to'])) {
            $query->where('subscriptions.ends_at <=', $filters['expires_to']);
        }

        if (!empty($filters['search'])) {
            $query->groupStart()
                  ->like('restaurants.name', $filters['search'])
                  ->orLike('plans.name', $filters['search'])
                  ->orLike('subscriptions.gateway_subscription_id', $filters['search'])
                  ->groupEnd();
        }

        return $query->orderBy('subscriptions.created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get advanced subscription statistics
     */
    public function getAdvancedStats($period = '30 days')
    {
        $stats = [];
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));

        // Total subscriptions
        $stats['total'] = $this->countAllResults();

        // Active subscriptions
        $stats['active'] = $this->where('status', self::STATUS_ACTIVE)->countAllResults();

        // Trial subscriptions
        $stats['trial'] = $this->where('status', self::STATUS_TRIAL)->countAllResults();

        // Canceled subscriptions
        $stats['canceled'] = $this->where('status', self::STATUS_CANCELED)->countAllResults();

        // New subscriptions in period
        $stats['new_in_period'] = $this->where('created_at >=', $dateFrom)->countAllResults();

        // Canceled in period
        $stats['canceled_in_period'] = $this->where('canceled_at >=', $dateFrom)
                                           ->where('canceled_at IS NOT NULL')
                                           ->countAllResults();

        // Churn rate
        $stats['churn_rate'] = $stats['active'] > 0 ? ($stats['canceled_in_period'] / $stats['active']) * 100 : 0;

        // Monthly Recurring Revenue (MRR)
        $mrrQuery = $this->select('SUM(CASE WHEN billing_cycle = "monthly" THEN amount WHEN billing_cycle = "yearly" THEN amount/12 WHEN billing_cycle = "quarterly" THEN amount/3 ELSE amount END) as mrr')
                        ->where('status', self::STATUS_ACTIVE)
                        ->first();
        $stats['mrr'] = $mrrQuery['mrr'] ?? 0;

        // Annual Recurring Revenue (ARR)
        $stats['arr'] = $stats['mrr'] * 12;

        // Average Revenue Per User (ARPU)
        $stats['arpu'] = $stats['active'] > 0 ? $stats['mrr'] / $stats['active'] : 0;

        // By billing cycle
        $cycleStats = $this->select('billing_cycle, COUNT(*) as count, SUM(amount) as revenue')
                          ->where('status', self::STATUS_ACTIVE)
                          ->groupBy('billing_cycle')
                          ->findAll();
        
        foreach ($cycleStats as $cycle) {
            $stats['by_cycle'][$cycle['billing_cycle']] = [
                'count' => $cycle['count'],
                'revenue' => $cycle['revenue']
            ];
        }

        // By plan
        $planStats = $this->select('plans.name, COUNT(*) as count, SUM(subscriptions.amount) as revenue')
                         ->join('plans', 'plans.id = subscriptions.plan_id')
                         ->where('subscriptions.status', self::STATUS_ACTIVE)
                         ->groupBy('plans.id')
                         ->findAll();
        
        foreach ($planStats as $plan) {
            $stats['by_plan'][$plan['name']] = [
                'count' => $plan['count'],
                'revenue' => $plan['revenue']
            ];
        }

        return $stats;
    }

    /**
     * Export subscriptions to CSV
     */
    public function exportToCSV($filters = [])
    {
        $subscriptions = $this->searchSubscriptions($filters, 10000);
        
        $csvData = [];
        $csvData[] = [
            'ID', 'Restaurante', 'Plano', 'Status', 'Ciclo de Cobrança', 'Valor', 'Moeda',
            'Data de Início', 'Data de Fim', 'Último Pagamento', 'Próximo Pagamento',
            'Falhas de Pagamento', 'Gateway', 'Criado em'
        ];
        
        foreach ($subscriptions as $subscription) {
            $csvData[] = [
                $subscription['id'],
                $subscription['restaurant_name'],
                $subscription['plan_name'],
                $subscription['status'],
                $subscription['billing_cycle'],
                $subscription['amount'],
                $subscription['currency'],
                $subscription['starts_at'],
                $subscription['ends_at'],
                $subscription['last_payment_at'],
                $subscription['next_payment_at'],
                $subscription['payment_failures'],
                $subscription['payment_gateway'],
                $subscription['created_at']
            ];
        }
        
        return $csvData;
    }

    /**
     * Set defaults before insert
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['payment_failures'])) {
            $data['data']['payment_failures'] = 0;
        }

        if (!isset($data['data']['max_payment_failures'])) {
            $data['data']['max_payment_failures'] = 3;
        }

        if (!isset($data['data']['auto_renew'])) {
            $data['data']['auto_renew'] = 1;
        }

        if (!isset($data['data']['proration_enabled'])) {
            $data['data']['proration_enabled'] = 1;
        }

        if (!isset($data['data']['currency'])) {
            $data['data']['currency'] = 'BRL';
        }

        if (!isset($data['data']['billing_cycle'])) {
            $data['data']['billing_cycle'] = self::CYCLE_MONTHLY;
        }

        if (!isset($data['data']['starts_at'])) {
            $data['data']['starts_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Calculate amounts before insert/update
     */
    protected function calculateAmounts(array $data)
    {
        if (isset($data['data']['amount'])) {
            $amount = $data['data']['amount'];
            $discountAmount = $data['data']['discount_amount'] ?? 0;
            $discountPercentage = $data['data']['discount_percentage'] ?? 0;
            $taxPercentage = $data['data']['tax_percentage'] ?? 0;

            // Calculate discount
            if ($discountPercentage > 0) {
                $discountAmount = $amount * ($discountPercentage / 100);
                $data['data']['discount_amount'] = $discountAmount;
            }

            // Calculate subtotal
            $subtotal = $amount - $discountAmount;
            $data['data']['subtotal'] = $subtotal;

            // Calculate tax
            if ($taxPercentage > 0) {
                $taxAmount = $subtotal * ($taxPercentage / 100);
                $data['data']['tax_amount'] = $taxAmount;
            } else {
                $taxAmount = $data['data']['tax_amount'] ?? 0;
            }

            // Calculate total
            $data['data']['total'] = $subtotal + $taxAmount;
        }

        return $data;
    }

    /**
     * Prepare JSON fields before insert/update
     */
    protected function prepareJsonFields(array $data)
    {
        $jsonFields = ['metadata', 'tags'];
        
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
        $jsonFields = ['metadata', 'tags'];
        
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
     * Update timestamps before update
     */
    protected function updateTimestamps(array $data)
    {
        // Custom logic for timestamp updates if needed
        return $data;
    }
}