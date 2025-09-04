<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id', 'user_id', 'payment_id', 'subscription_id', 'order_id',
        'transaction_number', 'external_id', 'type', 'category', 'status',
        'amount', 'currency', 'exchange_rate', 'amount_local', 'currency_local',
        'fee_amount', 'net_amount', 'tax_amount', 'discount_amount',
        'description', 'reference', 'gateway', 'gateway_transaction_id',
        'gateway_response', 'payment_method', 'card_brand', 'card_last_four',
        'bank_name', 'account_type', 'pix_key', 'boleto_url',
        'processed_at', 'settled_at', 'failed_at', 'refunded_at',
        'failure_reason', 'risk_score', 'fraud_detected', 'ip_address',
        'user_agent', 'location', 'device_info', 'metadata', 'notes', 'tags'
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
        'type' => 'required|in_list[payment,refund,chargeback,fee,adjustment,payout,withdrawal]',
        'category' => 'required|in_list[subscription,order,addon,credit,debit,transfer]',
        'status' => 'required|in_list[pending,processing,completed,failed,canceled,refunded,disputed]',
        'amount' => 'required|decimal|greater_than[0]',
        'currency' => 'required|exact_length[3]',
        'gateway' => 'permit_empty|max_length[50]',
        'payment_method' => 'permit_empty|in_list[credit_card,debit_card,pix,boleto,bank_transfer,wallet,cash]'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'integer' => 'ID do restaurante deve ser um número inteiro'
        ],
        'type' => [
            'required' => 'Tipo da transação é obrigatório',
            'in_list' => 'Tipo da transação deve ser válido'
        ],
        'category' => [
            'required' => 'Categoria da transação é obrigatória',
            'in_list' => 'Categoria da transação deve ser válida'
        ],
        'status' => [
            'required' => 'Status da transação é obrigatório',
            'in_list' => 'Status da transação deve ser válido'
        ],
        'amount' => [
            'required' => 'Valor da transação é obrigatório',
            'decimal' => 'Valor da transação deve ser um número decimal',
            'greater_than' => 'Valor da transação deve ser maior que zero'
        ],
        'currency' => [
            'required' => 'Moeda é obrigatória',
            'exact_length' => 'Moeda deve ter exatamente 3 caracteres'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setDefaults', 'generateTransactionNumber', 'calculateAmounts', 'prepareJsonFields'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['calculateAmounts', 'prepareJsonFields', 'updateTimestamps'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['parseJsonFields'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    // Constants
    const TYPE_PAYMENT = 'payment';
    const TYPE_REFUND = 'refund';
    const TYPE_CHARGEBACK = 'chargeback';
    const TYPE_FEE = 'fee';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_PAYOUT = 'payout';
    const TYPE_WITHDRAWAL = 'withdrawal';

    const CATEGORY_SUBSCRIPTION = 'subscription';
    const CATEGORY_ORDER = 'order';
    const CATEGORY_ADDON = 'addon';
    const CATEGORY_CREDIT = 'credit';
    const CATEGORY_DEBIT = 'debit';
    const CATEGORY_TRANSFER = 'transfer';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_DISPUTED = 'disputed';

    const PAYMENT_METHOD_CREDIT_CARD = 'credit_card';
    const PAYMENT_METHOD_DEBIT_CARD = 'debit_card';
    const PAYMENT_METHOD_PIX = 'pix';
    const PAYMENT_METHOD_BOLETO = 'boleto';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_WALLET = 'wallet';
    const PAYMENT_METHOD_CASH = 'cash';

    /**
     * Generate unique transaction number
     */
    protected function generateTransactionNumber(array $data)
    {
        if (!isset($data['data']['transaction_number'])) {
            $prefix = 'TXN';
            $timestamp = time();
            $random = mt_rand(1000, 9999);
            $data['data']['transaction_number'] = $prefix . $timestamp . $random;
        }
        return $data;
    }

    /**
     * Set default values
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = self::STATUS_PENDING;
        }

        if (!isset($data['data']['currency'])) {
            $data['data']['currency'] = 'BRL';
        }

        if (!isset($data['data']['currency_local'])) {
            $data['data']['currency_local'] = $data['data']['currency'];
        }

        if (!isset($data['data']['exchange_rate'])) {
            $data['data']['exchange_rate'] = 1.0;
        }

        if (!isset($data['data']['fraud_detected'])) {
            $data['data']['fraud_detected'] = 0;
        }

        if (!isset($data['data']['risk_score'])) {
            $data['data']['risk_score'] = 0;
        }

        return $data;
    }

    /**
     * Calculate transaction amounts
     */
    protected function calculateAmounts(array $data)
    {
        if (isset($data['data']['amount'])) {
            $amount = $data['data']['amount'];
            $exchangeRate = $data['data']['exchange_rate'] ?? 1.0;
            $feeAmount = $data['data']['fee_amount'] ?? 0;
            $taxAmount = $data['data']['tax_amount'] ?? 0;
            $discountAmount = $data['data']['discount_amount'] ?? 0;

            // Calculate local amount
            $data['data']['amount_local'] = $amount * $exchangeRate;

            // Calculate net amount
            $data['data']['net_amount'] = $amount - $feeAmount - $taxAmount + $discountAmount;
        }

        return $data;
    }

    /**
     * Update timestamps based on status
     */
    protected function updateTimestamps(array $data)
    {
        if (isset($data['data']['status'])) {
            $now = date('Y-m-d H:i:s');
            
            switch ($data['data']['status']) {
                case self::STATUS_PROCESSING:
                    if (!isset($data['data']['processed_at'])) {
                        $data['data']['processed_at'] = $now;
                    }
                    break;
                case self::STATUS_COMPLETED:
                    if (!isset($data['data']['settled_at'])) {
                        $data['data']['settled_at'] = $now;
                    }
                    break;
                case self::STATUS_FAILED:
                    if (!isset($data['data']['failed_at'])) {
                        $data['data']['failed_at'] = $now;
                    }
                    break;
                case self::STATUS_REFUNDED:
                    if (!isset($data['data']['refunded_at'])) {
                        $data['data']['refunded_at'] = $now;
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * Prepare JSON fields before insert/update
     */
    protected function prepareJsonFields(array $data)
    {
        $jsonFields = ['gateway_response', 'device_info', 'location', 'metadata', 'tags'];
        
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
        $jsonFields = ['gateway_response', 'device_info', 'location', 'metadata', 'tags'];
        
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
     * Get transactions by restaurant
     */
    public function getByRestaurant($restaurantId, $limit = 50, $offset = 0)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Get transactions by status
     */
    public function getByStatus($status, $limit = 50, $offset = 0)
    {
        return $this->where('status', $status)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Get transactions by type
     */
    public function getByType($type, $limit = 50, $offset = 0)
    {
        return $this->where('type', $type)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Get transactions by category
     */
    public function getByCategory($category, $limit = 50, $offset = 0)
    {
        return $this->where('category', $category)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Get transactions by payment method
     */
    public function getByPaymentMethod($paymentMethod, $limit = 50, $offset = 0)
    {
        return $this->where('payment_method', $paymentMethod)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Get transactions by gateway
     */
    public function getByGateway($gateway, $limit = 50, $offset = 0)
    {
        return $this->where('gateway', $gateway)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Get transactions by period
     */
    public function getByPeriod($startDate, $endDate, $restaurantId = null, $limit = 100, $offset = 0)
    {
        $query = $this->where('created_at >=', $startDate)
                     ->where('created_at <=', $endDate);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->orderBy('created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get pending transactions
     */
    public function getPendingTransactions($limit = 50)
    {
        return $this->where('status', self::STATUS_PENDING)
                   ->orderBy('created_at', 'ASC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Get failed transactions
     */
    public function getFailedTransactions($limit = 50)
    {
        return $this->where('status', self::STATUS_FAILED)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Get high-risk transactions
     */
    public function getHighRiskTransactions($riskThreshold = 70, $limit = 50)
    {
        return $this->where('risk_score >=', $riskThreshold)
                   ->orWhere('fraud_detected', 1)
                   ->orderBy('risk_score', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Get transactions for reconciliation
     */
    public function getForReconciliation($gateway = null, $date = null)
    {
        $query = $this->where('status', self::STATUS_COMPLETED);
        
        if ($gateway) {
            $query->where('gateway', $gateway);
        }
        
        if ($date) {
            $query->where('DATE(settled_at)', $date);
        } else {
            $query->where('DATE(settled_at)', date('Y-m-d'));
        }
        
        return $query->orderBy('settled_at', 'ASC')->findAll();
    }

    /**
     * Find transaction by number
     */
    public function findByTransactionNumber($transactionNumber)
    {
        return $this->where('transaction_number', $transactionNumber)->first();
    }

    /**
     * Find transaction by external ID
     */
    public function findByExternalId($externalId)
    {
        return $this->where('external_id', $externalId)->first();
    }

    /**
     * Find transaction by gateway transaction ID
     */
    public function findByGatewayTransactionId($gatewayTransactionId)
    {
        return $this->where('gateway_transaction_id', $gatewayTransactionId)->first();
    }

    /**
     * Update transaction status
     */
    public function updateStatus($id, $status, $additionalData = [])
    {
        $data = array_merge(['status' => $status], $additionalData);
        return $this->update($id, $data);
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted($id, $gatewayResponse = null)
    {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'settled_at' => date('Y-m-d H:i:s')
        ];
        
        if ($gatewayResponse) {
            $data['gateway_response'] = $gatewayResponse;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed($id, $failureReason = null, $gatewayResponse = null)
    {
        $data = [
            'status' => self::STATUS_FAILED,
            'failed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($failureReason) {
            $data['failure_reason'] = $failureReason;
        }
        
        if ($gatewayResponse) {
            $data['gateway_response'] = $gatewayResponse;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Process refund
     */
    public function processRefund($id, $refundAmount = null, $reason = null)
    {
        $transaction = $this->find($id);
        if (!$transaction) {
            return false;
        }
        
        $refundAmount = $refundAmount ?? $transaction['amount'];
        
        // Create refund transaction
        $refundData = [
            'restaurant_id' => $transaction['restaurant_id'],
            'user_id' => $transaction['user_id'],
            'payment_id' => $transaction['payment_id'],
            'subscription_id' => $transaction['subscription_id'],
            'order_id' => $transaction['order_id'],
            'type' => self::TYPE_REFUND,
            'category' => $transaction['category'],
            'status' => self::STATUS_COMPLETED,
            'amount' => -$refundAmount, // Negative amount for refund
            'currency' => $transaction['currency'],
            'gateway' => $transaction['gateway'],
            'payment_method' => $transaction['payment_method'],
            'description' => 'Refund for transaction ' . $transaction['transaction_number'],
            'reference' => $transaction['transaction_number'],
            'notes' => $reason,
            'processed_at' => date('Y-m-d H:i:s'),
            'settled_at' => date('Y-m-d H:i:s')
        ];
        
        $refundId = $this->insert($refundData);
        
        if ($refundId) {
            // Update original transaction status
            $this->update($id, [
                'status' => self::STATUS_REFUNDED,
                'refunded_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $refundId;
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStats($restaurantId = null, $period = '30 days')
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        $query = $this->select([
            'COUNT(*) as total_transactions',
            'SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_revenue',
            'SUM(CASE WHEN status = "completed" THEN net_amount ELSE 0 END) as net_revenue',
            'SUM(CASE WHEN status = "completed" THEN fee_amount ELSE 0 END) as total_fees',
            'COUNT(CASE WHEN status = "completed" THEN 1 END) as successful_transactions',
            'COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_transactions',
            'COUNT(CASE WHEN status = "refunded" THEN 1 END) as refunded_transactions',
            'AVG(CASE WHEN status = "completed" THEN amount END) as average_transaction_value',
            'AVG(risk_score) as average_risk_score'
        ])->where('created_at >=', $dateFrom);
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        $stats = $query->first();
        
        // Calculate success rate
        $stats['success_rate'] = $stats['total_transactions'] > 0 
            ? ($stats['successful_transactions'] / $stats['total_transactions']) * 100 
            : 0;
        
        return $stats;
    }

    /**
     * Get revenue by period
     */
    public function getRevenueByPeriod($period = 'daily', $restaurantId = null, $days = 30)
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        
        $dateFormat = match($period) {
            'hourly' => '%Y-%m-%d %H:00:00',
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d'
        };
        
        $query = $this->select([
            "DATE_FORMAT(created_at, '{$dateFormat}') as period",
            'SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as revenue',
            'SUM(CASE WHEN status = "completed" THEN net_amount ELSE 0 END) as net_revenue',
            'COUNT(CASE WHEN status = "completed" THEN 1 END) as transactions'
        ])
        ->where('created_at >=', $dateFrom)
        ->groupBy('period')
        ->orderBy('period', 'ASC');
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->findAll();
    }

    /**
     * Get revenue by payment method
     */
    public function getRevenueByPaymentMethod($restaurantId = null, $period = '30 days')
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        $query = $this->select([
            'payment_method',
            'SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as revenue',
            'COUNT(CASE WHEN status = "completed" THEN 1 END) as transactions',
            'AVG(CASE WHEN status = "completed" THEN amount END) as average_value'
        ])
        ->where('created_at >=', $dateFrom)
        ->where('payment_method IS NOT NULL')
        ->groupBy('payment_method')
        ->orderBy('revenue', 'DESC');
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        return $query->findAll();
    }

    /**
     * Advanced search transactions
     */
    public function advancedSearch($filters = [], $limit = 50, $offset = 0)
    {
        $query = $this->select('transactions.*, restaurants.name as restaurant_name')
                     ->join('restaurants', 'restaurants.id = transactions.restaurant_id', 'left');
        
        if (!empty($filters['search'])) {
            $query->groupStart()
                  ->like('transaction_number', $filters['search'])
                  ->orLike('external_id', $filters['search'])
                  ->orLike('gateway_transaction_id', $filters['search'])
                  ->orLike('description', $filters['search'])
                  ->orLike('reference', $filters['search'])
                  ->groupEnd();
        }
        
        if (!empty($filters['restaurant_id'])) {
            $query->where('transactions.restaurant_id', $filters['restaurant_id']);
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('transactions.status', $filters['status']);
            } else {
                $query->where('transactions.status', $filters['status']);
            }
        }
        
        if (!empty($filters['type'])) {
            $query->where('transactions.type', $filters['type']);
        }
        
        if (!empty($filters['category'])) {
            $query->where('transactions.category', $filters['category']);
        }
        
        if (!empty($filters['payment_method'])) {
            $query->where('transactions.payment_method', $filters['payment_method']);
        }
        
        if (!empty($filters['gateway'])) {
            $query->where('transactions.gateway', $filters['gateway']);
        }
        
        if (!empty($filters['min_amount'])) {
            $query->where('transactions.amount >=', $filters['min_amount']);
        }
        
        if (!empty($filters['max_amount'])) {
            $query->where('transactions.amount <=', $filters['max_amount']);
        }
        
        if (!empty($filters['start_date'])) {
            $query->where('transactions.created_at >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('transactions.created_at <=', $filters['end_date']);
        }
        
        if (!empty($filters['fraud_detected'])) {
            $query->where('transactions.fraud_detected', 1);
        }
        
        if (!empty($filters['min_risk_score'])) {
            $query->where('transactions.risk_score >=', $filters['min_risk_score']);
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $query->orderBy("transactions.{$orderBy}", $orderDir)
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Export transactions to CSV
     */
    public function exportToCSV($filters = [])
    {
        $transactions = $this->advancedSearch($filters, 10000);
        
        $csvData = [];
        $csvData[] = [
            'ID', 'Número da Transação', 'Restaurante', 'Tipo', 'Categoria', 'Status',
            'Valor', 'Moeda', 'Valor Líquido', 'Taxa', 'Método de Pagamento', 'Gateway',
            'Descrição', 'Referência', 'Processado em', 'Liquidado em', 'Criado em'
        ];
        
        foreach ($transactions as $transaction) {
            $csvData[] = [
                $transaction['id'],
                $transaction['transaction_number'],
                $transaction['restaurant_name'],
                $transaction['type'],
                $transaction['category'],
                $transaction['status'],
                $transaction['amount'],
                $transaction['currency'],
                $transaction['net_amount'],
                $transaction['fee_amount'],
                $transaction['payment_method'],
                $transaction['gateway'],
                $transaction['description'],
                $transaction['reference'],
                $transaction['processed_at'],
                $transaction['settled_at'],
                $transaction['created_at']
            ];
        }
        
        return $csvData;
    }

    /**
     * Get financial report
     */
    public function getFinancialReport($restaurantId = null, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? date('Y-m-01'); // First day of current month
        $endDate = $endDate ?? date('Y-m-t'); // Last day of current month
        
        $query = $this->select([
            'SUM(CASE WHEN status = "completed" AND type = "payment" THEN amount ELSE 0 END) as gross_revenue',
            'SUM(CASE WHEN status = "completed" AND type = "payment" THEN net_amount ELSE 0 END) as net_revenue',
            'SUM(CASE WHEN status = "completed" AND type = "refund" THEN ABS(amount) ELSE 0 END) as total_refunds',
            'SUM(CASE WHEN status = "completed" THEN fee_amount ELSE 0 END) as total_fees',
            'SUM(CASE WHEN status = "completed" THEN tax_amount ELSE 0 END) as total_taxes',
            'COUNT(CASE WHEN status = "completed" AND type = "payment" THEN 1 END) as successful_payments',
            'COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_payments',
            'COUNT(CASE WHEN status = "refunded" OR type = "refund" THEN 1 END) as refund_count'
        ])
        ->where('created_at >=', $startDate . ' 00:00:00')
        ->where('created_at <=', $endDate . ' 23:59:59');
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        $report = $query->first();
        
        // Calculate additional metrics
        $report['refund_rate'] = $report['successful_payments'] > 0 
            ? ($report['refund_count'] / $report['successful_payments']) * 100 
            : 0;
        
        $report['success_rate'] = ($report['successful_payments'] + $report['failed_payments']) > 0 
            ? ($report['successful_payments'] / ($report['successful_payments'] + $report['failed_payments'])) * 100 
            : 0;
        
        $report['average_transaction_value'] = $report['successful_payments'] > 0 
            ? $report['gross_revenue'] / $report['successful_payments'] 
            : 0;
        
        return $report;
    }

    /**
     * Check if transaction exists by external ID
     */
    public function transactionExists($externalId)
    {
        return $this->where('external_id', $externalId)->countAllResults() > 0;
    }

    /**
     * Clean old transactions (for data retention)
     */
    public function cleanOldTransactions($days = 2555) // ~7 years
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->where('created_at <', $cutoffDate)
                   ->where('status', self::STATUS_COMPLETED)
                   ->delete();
    }
}