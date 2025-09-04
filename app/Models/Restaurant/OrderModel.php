<?php

namespace App\Models\Restaurant;

use CodeIgniter\Model;

/**
 * Order Model
 * Manages orders for the Restaurant Kiosk system
 */
class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'order_type',
        'table_number',
        'items',
        'subtotal',
        'tax_amount',
        'service_fee',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_reference',
        'status',
        'notes',
        'preparation_time',
        'estimated_ready_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'processed_by'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|is_natural_no_zero',
        'order_number' => 'required|is_unique[orders.order_number,id,{id}]',
        'customer_name' => 'permit_empty|max_length[255]',
        'customer_phone' => 'permit_empty|max_length[20]',
        'customer_email' => 'permit_empty|valid_email',
        'order_type' => 'required|in_list[dine_in,takeaway,delivery]',
        'table_number' => 'permit_empty|is_natural_no_zero',
        'subtotal' => 'required|decimal|greater_than_equal_to[0]',
        'tax_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'service_fee' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'discount_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'total_amount' => 'required|decimal|greater_than[0]',
        'payment_method' => 'required|in_list[pix,credit_card,debit_card,cash,multiple]',
        'payment_status' => 'required|in_list[pending,processing,paid,failed,refunded]',
        'status' => 'required|in_list[pending,confirmed,preparing,ready,completed,cancelled]',
        'preparation_time' => 'permit_empty|is_natural'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'is_natural_no_zero' => 'ID do restaurante deve ser um número válido'
        ],
        'order_number' => [
            'required' => 'Número do pedido é obrigatório',
            'is_unique' => 'Este número de pedido já existe'
        ],
        'customer_email' => [
            'valid_email' => 'Email deve ser válido'
        ],
        'order_type' => [
            'required' => 'Tipo do pedido é obrigatório',
            'in_list' => 'Tipo deve ser: dine_in, takeaway ou delivery'
        ],
        'table_number' => [
            'is_natural_no_zero' => 'Número da mesa deve ser um número válido'
        ],
        'subtotal' => [
            'required' => 'Subtotal é obrigatório',
            'decimal' => 'Subtotal deve ser um valor decimal válido',
            'greater_than_equal_to' => 'Subtotal deve ser maior ou igual a zero'
        ],
        'tax_amount' => [
            'decimal' => 'Valor do imposto deve ser um valor decimal válido',
            'greater_than_equal_to' => 'Valor do imposto deve ser maior ou igual a zero'
        ],
        'service_fee' => [
            'decimal' => 'Taxa de serviço deve ser um valor decimal válido',
            'greater_than_equal_to' => 'Taxa de serviço deve ser maior ou igual a zero'
        ],
        'discount_amount' => [
            'decimal' => 'Valor do desconto deve ser um valor decimal válido',
            'greater_than_equal_to' => 'Valor do desconto deve ser maior ou igual a zero'
        ],
        'total_amount' => [
            'required' => 'Valor total é obrigatório',
            'decimal' => 'Valor total deve ser um valor decimal válido',
            'greater_than' => 'Valor total deve ser maior que zero'
        ],
        'payment_method' => [
            'required' => 'Método de pagamento é obrigatório',
            'in_list' => 'Método deve ser: pix, credit_card, debit_card, cash ou multiple'
        ],
        'payment_status' => [
            'required' => 'Status do pagamento é obrigatório',
            'in_list' => 'Status deve ser: pending, processing, paid, failed ou refunded'
        ],
        'status' => [
            'required' => 'Status do pedido é obrigatório',
            'in_list' => 'Status deve ser: pending, confirmed, preparing, ready, completed ou cancelled'
        ],
        'preparation_time' => [
            'is_natural' => 'Tempo de preparo deve ser um número inteiro'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['beforeInsert'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['beforeUpdate'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['afterFind'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Before insert callback
     */
    protected function beforeInsert(array $data)
    {
        // Generate order number if not provided
        if (empty($data['data']['order_number'])) {
            $data['data']['order_number'] = $this->generateOrderNumber($data['data']['restaurant_id']);
        }
        
        // Process items JSON
        if (isset($data['data']['items']) && is_array($data['data']['items'])) {
            $data['data']['items'] = json_encode($data['data']['items']);
        }
        
        // Set default values
        $data['data']['payment_status'] = $data['data']['payment_status'] ?? 'pending';
        $data['data']['status'] = $data['data']['status'] ?? 'pending';
        $data['data']['tax_amount'] = $data['data']['tax_amount'] ?? 0.00;
        $data['data']['service_fee'] = $data['data']['service_fee'] ?? 0.00;
        $data['data']['discount_amount'] = $data['data']['discount_amount'] ?? 0.00;
        
        // Calculate estimated ready time
        if (isset($data['data']['preparation_time']) && $data['data']['preparation_time'] > 0) {
            $data['data']['estimated_ready_at'] = date('Y-m-d H:i:s', time() + ($data['data']['preparation_time'] * 60));
        }

        return $data;
    }

    /**
     * Before update callback
     */
    protected function beforeUpdate(array $data)
    {
        // Process items JSON
        if (isset($data['data']['items']) && is_array($data['data']['items'])) {
            $data['data']['items'] = json_encode($data['data']['items']);
        }
        
        // Set completion timestamp
        if (isset($data['data']['status']) && $data['data']['status'] === 'completed' && !isset($data['data']['completed_at'])) {
            $data['data']['completed_at'] = date('Y-m-d H:i:s');
        }
        
        // Set cancellation timestamp
        if (isset($data['data']['status']) && $data['data']['status'] === 'cancelled' && !isset($data['data']['cancelled_at'])) {
            $data['data']['cancelled_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * After find callback
     */
    protected function afterFind(array $data)
    {
        if (isset($data['data'])) {
            // Single record
            if (isset($data['data']['items']) && is_string($data['data']['items'])) {
                $data['data']['items'] = json_decode($data['data']['items'], true) ?? [];
            }
        } else {
            // Multiple records
            foreach ($data as &$record) {
                if (isset($record['items']) && is_string($record['items'])) {
                    $record['items'] = json_decode($record['items'], true) ?? [];
                }
            }
        }

        return $data;
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber($restaurantId)
    {
        $prefix = str_pad($restaurantId, 3, '0', STR_PAD_LEFT);
        $date = date('ymd');
        
        // Get last order number for today
        $lastOrder = $this->where('restaurant_id', $restaurantId)
                          ->where('DATE(created_at)', date('Y-m-d'))
                          ->orderBy('id', 'DESC')
                          ->first();
        
        $sequence = 1;
        if ($lastOrder && $lastOrder['order_number']) {
            // Extract sequence from last order number
            $lastSequence = (int) substr($lastOrder['order_number'], -4);
            $sequence = $lastSequence + 1;
        }
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get orders by restaurant
     */
    public function getByRestaurant($restaurantId, $status = null, $limit = null)
    {
        $builder = $this->where('restaurant_id', $restaurantId)
                        ->orderBy('created_at', 'DESC');
        
        if ($status) {
            if (is_array($status)) {
                $builder->whereIn('status', $status);
            } else {
                $builder->where('status', $status);
            }
        }
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        return $builder->findAll();
    }

    /**
     * Get active orders (not completed or cancelled)
     */
    public function getActiveOrders($restaurantId)
    {
        return $this->getByRestaurant($restaurantId, ['pending', 'confirmed', 'preparing', 'ready']);
    }

    /**
     * Get orders by date range
     */
    public function getByDateRange($restaurantId, $startDate, $endDate)
    {
        return $this->where('restaurant_id', $restaurantId)
                    ->where('DATE(created_at) >=', $startDate)
                    ->where('DATE(created_at) <=', $endDate)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get today's orders
     */
    public function getTodayOrders($restaurantId)
    {
        return $this->where('restaurant_id', $restaurantId)
                    ->where('DATE(created_at)', date('Y-m-d'))
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get orders by payment status
     */
    public function getByPaymentStatus($restaurantId, $paymentStatus)
    {
        return $this->where('restaurant_id', $restaurantId)
                    ->where('payment_status', $paymentStatus)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Update order status
     */
    public function updateStatus($orderId, $status, $processedBy = null)
    {
        $updateData = ['status' => $status];
        
        if ($processedBy) {
            $updateData['processed_by'] = $processedBy;
        }
        
        return $this->update($orderId, $updateData);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $paymentStatus, $paymentReference = null)
    {
        $updateData = ['payment_status' => $paymentStatus];
        
        if ($paymentReference) {
            $updateData['payment_reference'] = $paymentReference;
        }
        
        return $this->update($orderId, $updateData);
    }

    /**
     * Cancel order
     */
    public function cancelOrder($orderId, $reason, $processedBy = null)
    {
        $updateData = [
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => date('Y-m-d H:i:s')
        ];
        
        if ($processedBy) {
            $updateData['processed_by'] = $processedBy;
        }
        
        return $this->update($orderId, $updateData);
    }

    /**
     * Get order statistics
     */
    public function getStatistics($restaurantId, $period = 'today')
    {
        $builder = $this->where('restaurant_id', $restaurantId);
        
        switch ($period) {
            case 'today':
                $builder->where('DATE(created_at)', date('Y-m-d'));
                break;
            case 'week':
                $builder->where('created_at >=', date('Y-m-d', strtotime('-7 days')));
                break;
            case 'month':
                $builder->where('created_at >=', date('Y-m-d', strtotime('-30 days')));
                break;
        }
        
        $orders = $builder->findAll();
        
        $stats = [
            'total_orders' => count($orders),
            'total_revenue' => 0,
            'average_order_value' => 0,
            'by_status' => [
                'pending' => 0,
                'confirmed' => 0,
                'preparing' => 0,
                'ready' => 0,
                'completed' => 0,
                'cancelled' => 0
            ],
            'by_payment_method' => [
                'pix' => 0,
                'credit_card' => 0,
                'debit_card' => 0,
                'cash' => 0,
                'multiple' => 0
            ],
            'by_order_type' => [
                'dine_in' => 0,
                'takeaway' => 0,
                'delivery' => 0
            ]
        ];
        
        foreach ($orders as $order) {
            if ($order['status'] === 'completed') {
                $stats['total_revenue'] += $order['total_amount'];
            }
            
            $stats['by_status'][$order['status']]++;
            $stats['by_payment_method'][$order['payment_method']]++;
            $stats['by_order_type'][$order['order_type']]++;
        }
        
        if ($stats['total_orders'] > 0) {
            $stats['average_order_value'] = $stats['total_revenue'] / $stats['by_status']['completed'];
        }
        
        return $stats;
    }

    /**
     * Get popular dishes
     */
    public function getPopularDishes($restaurantId, $limit = 10, $period = 'month')
    {
        $builder = $this->where('restaurant_id', $restaurantId)
                        ->where('status', 'completed');
        
        switch ($period) {
            case 'today':
                $builder->where('DATE(created_at)', date('Y-m-d'));
                break;
            case 'week':
                $builder->where('created_at >=', date('Y-m-d', strtotime('-7 days')));
                break;
            case 'month':
                $builder->where('created_at >=', date('Y-m-d', strtotime('-30 days')));
                break;
        }
        
        $orders = $builder->findAll();
        $dishCount = [];
        
        foreach ($orders as $order) {
            foreach ($order['items'] as $item) {
                $dishId = $item['dish_id'];
                $quantity = $item['quantity'];
                
                if (!isset($dishCount[$dishId])) {
                    $dishCount[$dishId] = [
                        'dish_id' => $dishId,
                        'dish_name' => $item['name'],
                        'total_quantity' => 0,
                        'total_revenue' => 0
                    ];
                }
                
                $dishCount[$dishId]['total_quantity'] += $quantity;
                $dishCount[$dishId]['total_revenue'] += ($item['price'] * $quantity);
            }
        }
        
        // Sort by quantity
        usort($dishCount, function($a, $b) {
            return $b['total_quantity'] - $a['total_quantity'];
        });
        
        return array_slice($dishCount, 0, $limit);
    }

    /**
     * Get revenue by period
     */
    public function getRevenueByPeriod($restaurantId, $period = 'daily', $days = 30)
    {
        $db = \Config\Database::connect();
        
        $dateFormat = $period === 'daily' ? '%Y-%m-%d' : '%Y-%m';
        $groupBy = $period === 'daily' ? 'DATE(created_at)' : 'YEAR(created_at), MONTH(created_at)';
        
        $query = $db->query("
            SELECT 
                DATE_FORMAT(created_at, '{$dateFormat}') as period,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE restaurant_id = ? 
                AND status = 'completed'
                AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY {$groupBy}
            ORDER BY period ASC
        ", [$restaurantId]);
        
        return $query->getResultArray();
    }

    /**
     * Search orders
     */
    public function search($restaurantId, $query)
    {
        return $this->where('restaurant_id', $restaurantId)
                    ->groupStart()
                        ->like('order_number', $query)
                        ->orLike('customer_name', $query)
                        ->orLike('customer_phone', $query)
                        ->orLike('customer_email', $query)
                    ->groupEnd()
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}