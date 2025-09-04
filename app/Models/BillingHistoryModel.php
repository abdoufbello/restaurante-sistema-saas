<?php

namespace App\Models;

use CodeIgniter\Model;

class BillingHistoryModel extends Model
{
    protected $table = 'billing_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'subscription_plan_id',
        'amount',
        'currency',
        'payment_method',
        'payment_gateway',
        'gateway_transaction_id',
        'status',
        'due_date',
        'paid_at',
        'invoice_url',
        'notes'
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
        'subscription_plan_id' => 'required|integer',
        'amount' => 'required|decimal|greater_than[0]',
        'currency' => 'required|max_length[3]',
        'payment_gateway' => 'required|in_list[pagseguro,mercadopago,stripe,manual]',
        'status' => 'required|in_list[pending,paid,failed,cancelled,refunded]',
        'due_date' => 'required|valid_date'
    ];
    
    protected $validationMessages = [];
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
     * Obter histórico de pagamentos de um restaurante
     */
    public function getRestaurantBillingHistory($restaurantId, $limit = 50)
    {
        return $this->select('billing_history.*, subscription_plans.name as plan_name')
                   ->join('subscription_plans', 'subscription_plans.id = billing_history.subscription_plan_id')
                   ->where('restaurant_id', $restaurantId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Obter pagamentos pendentes
     */
    public function getPendingPayments($restaurantId = null)
    {
        $builder = $this->select('billing_history.*, subscription_plans.name as plan_name, restaurants.name as restaurant_name')
                       ->join('subscription_plans', 'subscription_plans.id = billing_history.subscription_plan_id')
                       ->join('restaurants', 'restaurants.id = billing_history.restaurant_id')
                       ->where('billing_history.status', 'pending');
        
        if ($restaurantId) {
            $builder->where('billing_history.restaurant_id', $restaurantId);
        }
        
        return $builder->orderBy('due_date', 'ASC')->findAll();
    }

    /**
     * Obter pagamentos vencidos
     */
    public function getOverduePayments($restaurantId = null)
    {
        $builder = $this->select('billing_history.*, subscription_plans.name as plan_name, restaurants.name as restaurant_name')
                       ->join('subscription_plans', 'subscription_plans.id = billing_history.subscription_plan_id')
                       ->join('restaurants', 'restaurants.id = billing_history.restaurant_id')
                       ->where('billing_history.status', 'pending')
                       ->where('billing_history.due_date <', date('Y-m-d'));
        
        if ($restaurantId) {
            $builder->where('billing_history.restaurant_id', $restaurantId);
        }
        
        return $builder->orderBy('due_date', 'ASC')->findAll();
    }

    /**
     * Criar nova cobrança
     */
    public function createBilling($restaurantId, $subscriptionPlanId, $amount, $dueDate, $paymentGateway = 'manual')
    {
        $data = [
            'restaurant_id' => $restaurantId,
            'subscription_plan_id' => $subscriptionPlanId,
            'amount' => $amount,
            'currency' => 'BRL',
            'payment_gateway' => $paymentGateway,
            'status' => 'pending',
            'due_date' => $dueDate
        ];
        
        return $this->insert($data);
    }

    /**
     * Marcar pagamento como pago
     */
    public function markAsPaid($billingId, $gatewayTransactionId = null, $paymentMethod = null)
    {
        $data = [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ];
        
        if ($gatewayTransactionId) {
            $data['gateway_transaction_id'] = $gatewayTransactionId;
        }
        
        if ($paymentMethod) {
            $data['payment_method'] = $paymentMethod;
        }
        
        return $this->update($billingId, $data);
    }

    /**
     * Marcar pagamento como falhado
     */
    public function markAsFailed($billingId, $notes = null)
    {
        $data = [
            'status' => 'failed'
        ];
        
        if ($notes) {
            $data['notes'] = $notes;
        }
        
        return $this->update($billingId, $data);
    }

    /**
     * Obter receita total por período
     */
    public function getRevenueByPeriod($startDate, $endDate, $restaurantId = null)
    {
        $builder = $this->selectSum('amount', 'total_revenue')
                       ->where('status', 'paid')
                       ->where('paid_at >=', $startDate)
                       ->where('paid_at <=', $endDate);
        
        if ($restaurantId) {
            $builder->where('restaurant_id', $restaurantId);
        }
        
        $result = $builder->first();
        return $result['total_revenue'] ?? 0;
    }

    /**
     * Obter estatísticas de pagamento
     */
    public function getPaymentStats($restaurantId = null)
    {
        $builder = $this->select('status, COUNT(*) as count, SUM(amount) as total')
                       ->groupBy('status');
        
        if ($restaurantId) {
            $builder->where('restaurant_id', $restaurantId);
        }
        
        return $builder->findAll();
    }

    /**
     * Gerar cobrança recorrente mensal
     */
    public function generateMonthlyBilling()
    {
        // Este método pode ser executado por um cron job
        $restaurantModel = new \App\Models\RestaurantModel();
        $subscriptionPlanModel = new \App\Models\SubscriptionPlanModel();
        
        $activeRestaurants = $restaurantModel->where('subscription_status', 'active')
                                           ->where('subscription_plan !=', 'trial')
                                           ->findAll();
        
        $nextMonth = date('Y-m-d', strtotime('+1 month'));
        
        foreach ($activeRestaurants as $restaurant) {
            $plan = $subscriptionPlanModel->findByName($restaurant['subscription_plan']);
            
            if ($plan && $plan['price'] > 0) {
                // Verificar se já existe cobrança para o próximo mês
                $existingBilling = $this->where([
                    'restaurant_id' => $restaurant['id'],
                    'subscription_plan_id' => $plan['id'],
                    'due_date' => $nextMonth,
                    'status' => 'pending'
                ])->first();
                
                if (!$existingBilling) {
                    $this->createBilling(
                        $restaurant['id'],
                        $plan['id'],
                        $plan['price'],
                        $nextMonth
                    );
                }
            }
        }
        
        return true;
    }
}