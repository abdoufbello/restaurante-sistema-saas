<?php

namespace App\Models;

use App\Models\TenantModel;

/**
 * Modelo para Pedidos com Multi-Tenancy
 */
class OrderModel extends TenantModel
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'table_number',
        'totem_id',
        'order_type',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'notes',
        'estimated_time',
        'completed_at',
        'cancelled_at',
        'cancel_reason'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'order_number' => 'required|max_length[50]',
        'customer_name' => 'max_length[255]',
        'customer_phone' => 'max_length[20]',
        'customer_email' => 'valid_email|max_length[255]',
        'table_number' => 'max_length[10]',
        'order_type' => 'in_list[dine_in,takeaway,delivery,totem]',
        'status' => 'in_list[pending,confirmed,preparing,ready,completed,cancelled]',
        'subtotal' => 'required|decimal|greater_than_equal_to[0]',
        'tax_amount' => 'decimal|greater_than_equal_to[0]',
        'discount_amount' => 'decimal|greater_than_equal_to[0]',
        'total_amount' => 'required|decimal|greater_than[0]',
        'payment_method' => 'in_list[cash,card,pix,online]',
        'payment_status' => 'in_list[pending,paid,failed,refunded]',
        'estimated_time' => 'integer|greater_than[0]'
    ];
    
    protected $validationMessages = [
        'order_number' => [
            'required' => 'Número do pedido é obrigatório'
        ],
        'subtotal' => [
            'required' => 'Subtotal é obrigatório',
            'decimal' => 'Subtotal deve ser um valor decimal válido'
        ],
        'total_amount' => [
            'required' => 'Total é obrigatório',
            'decimal' => 'Total deve ser um valor decimal válido',
            'greater_than' => 'Total deve ser maior que zero'
        ]
    ];
    
    /**
     * Gera próximo número de pedido
     */
    public function generateOrderNumber(): string
    {
        $today = date('Ymd');
        $lastOrder = $this->like('order_number', $today, 'after')
                         ->orderBy('id', 'DESC')
                         ->first();
        
        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder['order_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $today . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Obtém pedidos por status
     */
    public function getOrdersByStatus(string $status)
    {
        return $this->where('status', $status)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pedidos pendentes
     */
    public function getPendingOrders()
    {
        return $this->whereIn('status', ['pending', 'confirmed', 'preparing'])
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém pedidos do dia
     */
    public function getTodayOrders()
    {
        $today = date('Y-m-d');
        return $this->where('DATE(created_at)', $today)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pedidos por período
     */
    public function getOrdersByPeriod(string $startDate, string $endDate)
    {
        return $this->where('DATE(created_at) >=', $startDate)
                   ->where('DATE(created_at) <=', $endDate)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pedidos por tipo
     */
    public function getOrdersByType(string $orderType)
    {
        return $this->where('order_type', $orderType)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Busca pedido por número
     */
    public function findByOrderNumber(string $orderNumber)
    {
        return $this->where('order_number', $orderNumber)->first();
    }
    
    /**
     * Atualiza status do pedido
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $updateData = ['status' => $status];
        
        // Adicionar timestamp específico baseado no status
        switch ($status) {
            case 'completed':
                $updateData['completed_at'] = date('Y-m-d H:i:s');
                break;
            case 'cancelled':
                $updateData['cancelled_at'] = date('Y-m-d H:i:s');
                break;
        }
        
        return $this->update($orderId, $updateData);
    }
    
    /**
     * Cancela pedido
     */
    public function cancelOrder(int $orderId, string $reason = null): bool
    {
        $updateData = [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ];
        
        if ($reason) {
            $updateData['cancel_reason'] = $reason;
        }
        
        return $this->update($orderId, $updateData);
    }
    
    /**
     * Atualiza status de pagamento
     */
    public function updatePaymentStatus(int $orderId, string $paymentStatus): bool
    {
        return $this->update($orderId, ['payment_status' => $paymentStatus]);
    }
    
    /**
     * Obtém receita por período
     */
    public function getRevenueByPeriod(string $startDate, string $endDate): float
    {
        $result = $this->selectSum('total_amount')
                      ->where('DATE(created_at) >=', $startDate)
                      ->where('DATE(created_at) <=', $endDate)
                      ->where('status', 'completed')
                      ->where('payment_status', 'paid')
                      ->first();
        
        return (float) ($result['total_amount'] ?? 0);
    }
    
    /**
     * Obtém receita do dia
     */
    public function getTodayRevenue(): float
    {
        $today = date('Y-m-d');
        return $this->getRevenueByPeriod($today, $today);
    }
    
    /**
     * Obtém receita do mês
     */
    public function getMonthRevenue(int $year = null, int $month = null): float
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');
        
        $startDate = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        return $this->getRevenueByPeriod($startDate, $endDate);
    }
    
    /**
     * Conta pedidos por status
     */
    public function countByStatus(string $status): int
    {
        return $this->where('status', $status)->countAllResults();
    }
    
    /**
     * Obtém estatísticas de pedidos
     */
    public function getStats(): array
    {
        $today = date('Y-m-d');
        
        return [
            'total' => $this->countAllResults(),
            'today' => $this->where('DATE(created_at)', $today)->countAllResults(),
            'pending' => $this->countByStatus('pending'),
            'confirmed' => $this->countByStatus('confirmed'),
            'preparing' => $this->countByStatus('preparing'),
            'ready' => $this->countByStatus('ready'),
            'completed' => $this->countByStatus('completed'),
            'cancelled' => $this->countByStatus('cancelled'),
            'today_revenue' => $this->getTodayRevenue(),
            'month_revenue' => $this->getMonthRevenue(),
            'avg_order_value' => $this->getAverageOrderValue()
        ];
    }
    
    /**
     * Obtém valor médio do pedido
     */
    public function getAverageOrderValue(): float
    {
        $result = $this->selectAvg('total_amount')
                      ->where('status', 'completed')
                      ->first();
        
        return (float) ($result['total_amount'] ?? 0);
    }
    
    /**
     * Obtém pedidos por mesa
     */
    public function getOrdersByTable(string $tableNumber)
    {
        return $this->where('table_number', $tableNumber)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pedidos por totem
     */
    public function getOrdersByTotem(int $totemId)
    {
        return $this->where('totem_id', $totemId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém tempo médio de preparo
     */
    public function getAveragePreparationTime(): int
    {
        $result = $this->selectAvg('estimated_time')
                      ->where('status', 'completed')
                      ->first();
        
        return (int) ($result['estimated_time'] ?? 0);
    }
    
    /**
     * Obtém pedidos com atraso
     */
    public function getDelayedOrders()
    {
        $currentTime = date('Y-m-d H:i:s');
        
        return $this->select('*, TIMESTAMPDIFF(MINUTE, created_at, NOW()) as elapsed_minutes')
                   ->whereIn('status', ['confirmed', 'preparing'])
                   ->having('elapsed_minutes > estimated_time')
                   ->orderBy('elapsed_minutes', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém relatório de vendas por período
     */
    public function getSalesReport(string $startDate, string $endDate): array
    {
        return [
            'total_orders' => $this->where('DATE(created_at) >=', $startDate)
                                  ->where('DATE(created_at) <=', $endDate)
                                  ->countAllResults(),
            'completed_orders' => $this->where('DATE(created_at) >=', $startDate)
                                      ->where('DATE(created_at) <=', $endDate)
                                      ->where('status', 'completed')
                                      ->countAllResults(),
            'cancelled_orders' => $this->where('DATE(created_at) >=', $startDate)
                                      ->where('DATE(created_at) <=', $endDate)
                                      ->where('status', 'cancelled')
                                      ->countAllResults(),
            'total_revenue' => $this->getRevenueByPeriod($startDate, $endDate),
            'avg_order_value' => $this->getAverageOrderValueByPeriod($startDate, $endDate)
        ];
    }
    
    /**
     * Obtém valor médio do pedido por período
     */
    public function getAverageOrderValueByPeriod(string $startDate, string $endDate): float
    {
        $result = $this->selectAvg('total_amount')
                      ->where('DATE(created_at) >=', $startDate)
                      ->where('DATE(created_at) <=', $endDate)
                      ->where('status', 'completed')
                      ->first();
        
        return (float) ($result['total_amount'] ?? 0);
    }
}