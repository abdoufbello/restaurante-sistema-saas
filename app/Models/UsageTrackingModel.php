<?php

namespace App\Models;

use CodeIgniter\Model;

class UsageTrackingModel extends Model
{
    protected $table = 'usage_tracking';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'month',
        'year',
        'orders_count',
        'totems_used',
        'employees_count',
        'api_calls',
        'storage_used_mb',
        'last_updated'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'month' => 'required|integer|greater_than[0]|less_than[13]',
        'year' => 'required|integer|greater_than[2020]',
        'orders_count' => 'permit_empty|integer|greater_than_equal_to[0]',
        'totems_used' => 'permit_empty|integer|greater_than_equal_to[0]',
        'employees_count' => 'permit_empty|integer|greater_than_equal_to[0]',
        'api_calls' => 'permit_empty|integer|greater_than_equal_to[0]',
        'storage_used_mb' => 'permit_empty|decimal|greater_than_equal_to[0]'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'O ID do restaurante é obrigatório.',
            'integer' => 'O ID do restaurante deve ser um número inteiro.'
        ],
        'month' => [
            'required' => 'O mês é obrigatório.',
            'integer' => 'O mês deve ser um número inteiro.',
            'greater_than' => 'O mês deve ser maior que 0.',
            'less_than' => 'O mês deve ser menor que 13.'
        ],
        'year' => [
            'required' => 'O ano é obrigatório.',
            'integer' => 'O ano deve ser um número inteiro.',
            'greater_than' => 'O ano deve ser maior que 2020.'
        ]
    ];

    protected $beforeInsert = ['setDefaults'];
    protected $beforeUpdate = ['updateLastUpdated'];

    /**
     * Get current month usage for restaurant
     */
    public function getCurrentMonthUsage($restaurantId)
    {
        $month = date('n');
        $year = date('Y');
        
        $usage = $this->where('restaurant_id', $restaurantId)
                     ->where('month', $month)
                     ->where('year', $year)
                     ->first();
        
        if (!$usage) {
            // Create initial usage record
            $usage = $this->createUsageRecord($restaurantId, $month, $year);
        }
        
        return $usage;
    }

    /**
     * Get usage for specific month/year
     */
    public function getUsageForPeriod($restaurantId, $month, $year)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('month', $month)
                   ->where('year', $year)
                   ->first();
    }

    /**
     * Increment order count
     */
    public function incrementOrderCount($restaurantId, $count = 1)
    {
        $usage = $this->getCurrentMonthUsage($restaurantId);
        
        return $this->update($usage['id'], [
            'orders_count' => $usage['orders_count'] + $count,
            'last_updated' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update totems used count
     */
    public function updateTotemsUsed($restaurantId, $count)
    {
        $usage = $this->getCurrentMonthUsage($restaurantId);
        
        return $this->update($usage['id'], [
            'totems_used' => $count,
            'last_updated' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update employees count
     */
    public function updateEmployeesCount($restaurantId, $count)
    {
        $usage = $this->getCurrentMonthUsage($restaurantId);
        
        return $this->update($usage['id'], [
            'employees_count' => $count,
            'last_updated' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Increment API calls
     */
    public function incrementApiCalls($restaurantId, $count = 1)
    {
        $usage = $this->getCurrentMonthUsage($restaurantId);
        
        return $this->update($usage['id'], [
            'api_calls' => $usage['api_calls'] + $count,
            'last_updated' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update storage used
     */
    public function updateStorageUsed($restaurantId, $sizeMb)
    {
        $usage = $this->getCurrentMonthUsage($restaurantId);
        
        return $this->update($usage['id'], [
            'storage_used_mb' => $sizeMb,
            'last_updated' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if restaurant has exceeded limits
     */
    public function checkLimits($restaurantId)
    {
        $usage = $this->getCurrentMonthUsage($restaurantId);
        
        // Get restaurant's plan limits
        $restaurantModel = new \App\Models\RestaurantModel();
        $subscriptionModel = new \App\Models\SubscriptionModel();
        $planModel = new \App\Models\PlanModel();
        
        $subscription = $subscriptionModel->getActiveSubscription($restaurantId);
        if (!$subscription) {
            return [
                'has_limits' => false,
                'exceeded' => [],
                'warnings' => []
            ];
        }
        
        $limits = $planModel->getPlanLimits($subscription['plan_id']);
        $exceeded = [];
        $warnings = [];
        
        // Check order limits
        if ($limits['max_orders_per_month'] && $usage['orders_count'] >= $limits['max_orders_per_month']) {
            $exceeded[] = 'orders';
        } elseif ($limits['max_orders_per_month'] && $usage['orders_count'] >= ($limits['max_orders_per_month'] * 0.8)) {
            $warnings[] = 'orders';
        }
        
        // Check totem limits
        if ($limits['max_totems'] && $usage['totems_used'] >= $limits['max_totems']) {
            $exceeded[] = 'totems';
        }
        
        // Check employee limits
        if ($limits['max_employees'] && $usage['employees_count'] >= $limits['max_employees']) {
            $exceeded[] = 'employees';
        }
        
        return [
            'has_limits' => true,
            'limits' => $limits,
            'usage' => $usage,
            'exceeded' => $exceeded,
            'warnings' => $warnings
        ];
    }

    /**
     * Get usage history for restaurant
     */
    public function getUsageHistory($restaurantId, $months = 12)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->orderBy('year', 'DESC')
                   ->orderBy('month', 'DESC')
                   ->limit($months)
                   ->findAll();
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats($restaurantId = null)
    {
        $query = $this->select('SUM(orders_count) as total_orders, 
                               AVG(orders_count) as avg_orders,
                               SUM(api_calls) as total_api_calls,
                               AVG(storage_used_mb) as avg_storage')
                     ->where('month', date('n'))
                     ->where('year', date('Y'));
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->first();
    }

    /**
     * Reset monthly counters (for new month)
     */
    public function resetMonthlyCounters($restaurantId)
    {
        $month = date('n');
        $year = date('Y');
        
        // Check if record exists for current month
        $existing = $this->where('restaurant_id', $restaurantId)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();
        
        if (!$existing) {
            return $this->createUsageRecord($restaurantId, $month, $year);
        }
        
        return $existing;
    }

    /**
     * Create initial usage record
     */
    protected function createUsageRecord($restaurantId, $month, $year)
    {
        // Get current employee count
        $employeeModel = new \App\Models\EmployeeModel();
        $employeeCount = $employeeModel->where('restaurant_id', $restaurantId)
                                      ->where('is_active', 1)
                                      ->countAllResults();
        
        $data = [
            'restaurant_id' => $restaurantId,
            'month' => $month,
            'year' => $year,
            'orders_count' => 0,
            'totems_used' => 0,
            'employees_count' => $employeeCount,
            'api_calls' => 0,
            'storage_used_mb' => 0,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        $id = $this->insert($data);
        return $this->find($id);
    }

    /**
     * Set defaults before insert
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['orders_count'])) {
            $data['data']['orders_count'] = 0;
        }
        
        if (!isset($data['data']['totems_used'])) {
            $data['data']['totems_used'] = 0;
        }
        
        if (!isset($data['data']['employees_count'])) {
            $data['data']['employees_count'] = 0;
        }
        
        if (!isset($data['data']['api_calls'])) {
            $data['data']['api_calls'] = 0;
        }
        
        if (!isset($data['data']['storage_used_mb'])) {
            $data['data']['storage_used_mb'] = 0;
        }
        
        if (!isset($data['data']['last_updated'])) {
            $data['data']['last_updated'] = date('Y-m-d H:i:s');
        }
        
        return $data;
    }

    /**
     * Update last_updated timestamp
     */
    protected function updateLastUpdated(array $data)
    {
        $data['data']['last_updated'] = date('Y-m-d H:i:s');
        return $data;
    }
}