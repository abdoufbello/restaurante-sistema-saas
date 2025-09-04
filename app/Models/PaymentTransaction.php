<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'order_id',
        'gateway_type',
        'payment_method',
        'amount',
        'currency',
        'gateway_transaction_id',
        'status',
        'gateway_response',
        'customer_data',
        'metadata',
        'processed_at',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'order_id' => 'required|integer',
        'gateway_type' => 'required|string',
        'payment_method' => 'required|string',
        'amount' => 'required|decimal',
        'currency' => 'permit_empty|string|max_length[3]',
        'status' => 'required|in_list[pending,processing,completed,failed,cancelled,refunded]'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'integer' => 'ID do restaurante deve ser um número'
        ],
        'order_id' => [
            'required' => 'ID do pedido é obrigatório',
            'integer' => 'ID do pedido deve ser um número'
        ],
        'gateway_type' => [
            'required' => 'Tipo de gateway é obrigatório',
            'string' => 'Tipo de gateway deve ser texto'
        ],
        'payment_method' => [
            'required' => 'Método de pagamento é obrigatório',
            'string' => 'Método de pagamento deve ser texto'
        ],
        'amount' => [
            'required' => 'Valor é obrigatório',
            'decimal' => 'Valor deve ser um número decimal'
        ],
        'status' => [
            'required' => 'Status é obrigatório',
            'in_list' => 'Status inválido'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setDefaults'];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Define valores padrão antes da inserção
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['currency'])) {
            $data['data']['currency'] = 'BRL';
        }
        
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'pending';
        }
        
        return $data;
    }

    /**
     * Busca transações por restaurante
     */
    public function getTransactionsByRestaurant($restaurantId, $limit = 50, $offset = 0)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll($limit, $offset);
    }

    /**
     * Busca transações por pedido
     */
    public function getTransactionsByOrder($orderId)
    {
        return $this->where('order_id', $orderId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Busca transações por status
     */
    public function getTransactionsByStatus($restaurantId, $status)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('status', $status)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Atualiza status da transação
     */
    public function updateStatus($id, $status, $gatewayResponse = null)
    {
        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'completed') {
            $updateData['processed_at'] = date('Y-m-d H:i:s');
        }

        if ($gatewayResponse) {
            $updateData['gateway_response'] = json_encode($gatewayResponse);
        }

        return $this->update($id, $updateData);
    }

    /**
     * Estatísticas de transações por período
     */
    public function getTransactionStats($restaurantId, $startDate = null, $endDate = null)
    {
        $builder = $this->where('restaurant_id', $restaurantId);

        if ($startDate) {
            $builder->where('created_at >=', $startDate);
        }

        if ($endDate) {
            $builder->where('created_at <=', $endDate);
        }

        $builder->select('
            COUNT(*) as total_transactions,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_transactions,
            COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_transactions,
            COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_transactions,
            SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount,
            AVG(CASE WHEN status = "completed" THEN amount ELSE NULL END) as avg_amount,
            MIN(CASE WHEN status = "completed" THEN amount ELSE NULL END) as min_amount,
            MAX(CASE WHEN status = "completed" THEN amount ELSE NULL END) as max_amount
        ');

        return $builder->get()->getRowArray();
    }

    /**
     * Transações por gateway
     */
    public function getTransactionsByGateway($restaurantId, $startDate = null, $endDate = null)
    {
        $builder = $this->where('restaurant_id', $restaurantId);

        if ($startDate) {
            $builder->where('created_at >=', $startDate);
        }

        if ($endDate) {
            $builder->where('created_at <=', $endDate);
        }

        $builder->select('
            gateway_type,
            COUNT(*) as total_transactions,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_transactions,
            SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount,
            AVG(CASE WHEN status = "completed" THEN amount ELSE NULL END) as avg_amount
        ')
        ->groupBy('gateway_type')
        ->orderBy('total_amount', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Transações por método de pagamento
     */
    public function getTransactionsByPaymentMethod($restaurantId, $startDate = null, $endDate = null)
    {
        $builder = $this->where('restaurant_id', $restaurantId);

        if ($startDate) {
            $builder->where('created_at >=', $startDate);
        }

        if ($endDate) {
            $builder->where('created_at <=', $endDate);
        }

        $builder->select('
            payment_method,
            COUNT(*) as total_transactions,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_transactions,
            SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount,
            AVG(CASE WHEN status = "completed" THEN amount ELSE NULL END) as avg_amount
        ')
        ->groupBy('payment_method')
        ->orderBy('total_amount', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Transações por dia (para gráficos)
     */
    public function getDailyTransactions($restaurantId, $days = 30)
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $builder = $this->where('restaurant_id', $restaurantId)
                       ->where('created_at >=', $startDate)
                       ->select('
                           DATE(created_at) as date,
                           COUNT(*) as total_transactions,
                           COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_transactions,
                           SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount
                       ')
                       ->groupBy('DATE(created_at)')
                       ->orderBy('date', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * Busca transações pendentes há mais de X minutos
     */
    public function getPendingTransactions($minutes = 30)
    {
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));
        
        return $this->where('status', 'pending')
                   ->where('created_at <=', $cutoffTime)
                   ->findAll();
    }

    /**
     * Marca transação como expirada
     */
    public function expireTransaction($id)
    {
        return $this->update($id, [
            'status' => 'expired',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Busca transações para reconciliação
     */
    public function getTransactionsForReconciliation($restaurantId, $gatewayType, $date)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('gateway_type', $gatewayType)
                   ->where('DATE(created_at)', $date)
                   ->where('status', 'completed')
                   ->findAll();
    }

    /**
     * Calcula taxa de conversão
     */
    public function getConversionRate($restaurantId, $startDate = null, $endDate = null)
    {
        $builder = $this->where('restaurant_id', $restaurantId);

        if ($startDate) {
            $builder->where('created_at >=', $startDate);
        }

        if ($endDate) {
            $builder->where('created_at <=', $endDate);
        }

        $stats = $builder->select('
            COUNT(*) as total,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed
        ')->get()->getRowArray();

        if ($stats['total'] > 0) {
            return ($stats['completed'] / $stats['total']) * 100;
        }

        return 0;
    }
}