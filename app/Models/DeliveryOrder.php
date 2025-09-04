<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryOrder extends Model
{
    protected $table = 'delivery_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'integration_id',
        'platform',
        'platform_order_id',
        'order_number',
        'status',
        'customer_data',
        'delivery_data',
        'items',
        'subtotal',
        'delivery_fee',
        'service_fee',
        'discount',
        'total',
        'commission',
        'net_amount',
        'payment_method',
        'payment_status',
        'estimated_delivery_time',
        'actual_delivery_time',
        'preparation_time',
        'notes',
        'platform_data',
        'webhook_data',
        'created_at',
        'updated_at'
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
        'integration_id' => 'required|integer',
        'platform' => 'required|in_list[ifood,ubereats,rappi,99food]',
        'platform_order_id' => 'required|string',
        'status' => 'required|in_list[pending,confirmed,preparing,ready,dispatched,delivered,cancelled]',
        'total' => 'required|decimal'
    ];
    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'integer' => 'ID do restaurante deve ser um número'
        ],
        'platform' => [
            'required' => 'Plataforma é obrigatória',
            'in_list' => 'Plataforma deve ser: ifood, ubereats, rappi ou 99food'
        ],
        'platform_order_id' => [
            'required' => 'ID do pedido na plataforma é obrigatório'
        ],
        'status' => [
            'required' => 'Status é obrigatório',
            'in_list' => 'Status inválido'
        ],
        'total' => [
            'required' => 'Total é obrigatório',
            'decimal' => 'Total deve ser um valor decimal'
        ]
    ];
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
     * Buscar pedidos por restaurante
     */
    public function getOrdersByRestaurant($restaurantId, $filters = [])
    {
        $builder = $this->where('restaurant_id', $restaurantId);
        
        // Filtros opcionais
        if (isset($filters['platform'])) {
            $builder->where('platform', $filters['platform']);
        }
        
        if (isset($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder->whereIn('status', $filters['status']);
            } else {
                $builder->where('status', $filters['status']);
            }
        }
        
        if (isset($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }
        
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Buscar pedido por ID da plataforma
     */
    public function getByPlatformOrderId($platformOrderId, $platform)
    {
        return $this->where('platform_order_id', $platformOrderId)
                   ->where('platform', $platform)
                   ->first();
    }

    /**
     * Atualizar status do pedido
     */
    public function updateStatus($id, $status, $notes = null)
    {
        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($notes) {
            $updateData['notes'] = $notes;
        }
        
        // Registrar tempo de entrega se status for 'delivered'
        if ($status === 'delivered') {
            $updateData['actual_delivery_time'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($id, $updateData);
    }

    /**
     * Obter estatísticas de pedidos
     */
    public function getOrderStats($restaurantId, $period = '30 days')
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        $orders = $this->where('restaurant_id', $restaurantId)
                      ->where('created_at >=', $dateFrom)
                      ->findAll();
        
        $stats = [
            'total_orders' => count($orders),
            'total_revenue' => 0,
            'total_commission' => 0,
            'net_revenue' => 0,
            'by_status' => [],
            'by_platform' => [],
            'avg_order_value' => 0,
            'avg_preparation_time' => 0,
            'avg_delivery_time' => 0
        ];
        
        $preparationTimes = [];
        $deliveryTimes = [];
        
        foreach ($orders as $order) {
            // Totais
            $stats['total_revenue'] += $order['total'];
            $stats['total_commission'] += $order['commission'] ?? 0;
            $stats['net_revenue'] += $order['net_amount'] ?? $order['total'];
            
            // Por status
            $status = $order['status'];
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = ['count' => 0, 'revenue' => 0];
            }
            $stats['by_status'][$status]['count']++;
            $stats['by_status'][$status]['revenue'] += $order['total'];
            
            // Por plataforma
            $platform = $order['platform'];
            if (!isset($stats['by_platform'][$platform])) {
                $stats['by_platform'][$platform] = [
                    'count' => 0, 
                    'revenue' => 0, 
                    'commission' => 0
                ];
            }
            $stats['by_platform'][$platform]['count']++;
            $stats['by_platform'][$platform]['revenue'] += $order['total'];
            $stats['by_platform'][$platform]['commission'] += $order['commission'] ?? 0;
            
            // Tempos
            if ($order['preparation_time']) {
                $preparationTimes[] = $order['preparation_time'];
            }
            
            if ($order['actual_delivery_time'] && $order['created_at']) {
                $deliveryTime = strtotime($order['actual_delivery_time']) - strtotime($order['created_at']);
                $deliveryTimes[] = $deliveryTime / 60; // em minutos
            }
        }
        
        // Médias
        if ($stats['total_orders'] > 0) {
            $stats['avg_order_value'] = $stats['total_revenue'] / $stats['total_orders'];
        }
        
        if (!empty($preparationTimes)) {
            $stats['avg_preparation_time'] = array_sum($preparationTimes) / count($preparationTimes);
        }
        
        if (!empty($deliveryTimes)) {
            $stats['avg_delivery_time'] = array_sum($deliveryTimes) / count($deliveryTimes);
        }
        
        return $stats;
    }

    /**
     * Obter pedidos pendentes
     */
    public function getPendingOrders($restaurantId)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->whereIn('status', ['pending', 'confirmed', 'preparing'])
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }

    /**
     * Obter pedidos por período
     */
    public function getOrdersByPeriod($restaurantId, $startDate, $endDate)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('created_at >=', $startDate)
                   ->where('created_at <=', $endDate)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Calcular comissão por plataforma
     */
    public function calculateCommissionByPlatform($restaurantId, $period = '30 days')
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        $orders = $this->select('platform, SUM(total) as total_revenue, SUM(commission) as total_commission, COUNT(*) as order_count')
                      ->where('restaurant_id', $restaurantId)
                      ->where('created_at >=', $dateFrom)
                      ->where('status', 'delivered')
                      ->groupBy('platform')
                      ->findAll();
        
        $result = [];
        foreach ($orders as $order) {
            $commissionRate = $order['total_revenue'] > 0 ? 
                ($order['total_commission'] / $order['total_revenue']) * 100 : 0;
            
            $result[$order['platform']] = [
                'order_count' => $order['order_count'],
                'total_revenue' => $order['total_revenue'],
                'total_commission' => $order['total_commission'],
                'commission_rate' => round($commissionRate, 2),
                'net_revenue' => $order['total_revenue'] - $order['total_commission']
            ];
        }
        
        return $result;
    }

    /**
     * Obter pedidos atrasados
     */
    public function getDelayedOrders($restaurantId, $delayMinutes = 30)
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$delayMinutes} minutes"));
        
        return $this->where('restaurant_id', $restaurantId)
                   ->whereIn('status', ['confirmed', 'preparing'])
                   ->where('created_at <', $threshold)
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }

    /**
     * Marcar pedido como sincronizado
     */
    public function markAsSynced($id, $syncData = null)
    {
        $updateData = [
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($syncData) {
            $updateData['platform_data'] = json_encode($syncData);
        }
        
        return $this->update($id, $updateData);
    }

    /**
     * Obter relatório de performance por plataforma
     */
    public function getPlatformPerformanceReport($restaurantId, $period = '30 days')
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));
        
        $orders = $this->where('restaurant_id', $restaurantId)
                      ->where('created_at >=', $dateFrom)
                      ->findAll();
        
        $report = [];
        
        foreach ($orders as $order) {
            $platform = $order['platform'];
            
            if (!isset($report[$platform])) {
                $report[$platform] = [
                    'total_orders' => 0,
                    'completed_orders' => 0,
                    'cancelled_orders' => 0,
                    'total_revenue' => 0,
                    'total_commission' => 0,
                    'avg_order_value' => 0,
                    'completion_rate' => 0,
                    'cancellation_rate' => 0
                ];
            }
            
            $report[$platform]['total_orders']++;
            $report[$platform]['total_revenue'] += $order['total'];
            $report[$platform]['total_commission'] += $order['commission'] ?? 0;
            
            if ($order['status'] === 'delivered') {
                $report[$platform]['completed_orders']++;
            } elseif ($order['status'] === 'cancelled') {
                $report[$platform]['cancelled_orders']++;
            }
        }
        
        // Calcular médias e taxas
        foreach ($report as $platform => &$data) {
            if ($data['total_orders'] > 0) {
                $data['avg_order_value'] = $data['total_revenue'] / $data['total_orders'];
                $data['completion_rate'] = ($data['completed_orders'] / $data['total_orders']) * 100;
                $data['cancellation_rate'] = ($data['cancelled_orders'] / $data['total_orders']) * 100;
            }
        }
        
        return $report;
    }

    /**
     * Obter pedidos que precisam de atualização de status
     */
    public function getOrdersNeedingStatusUpdate($restaurantId, $minutesThreshold = 5)
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$minutesThreshold} minutes"));
        
        return $this->where('restaurant_id', $restaurantId)
                   ->whereIn('status', ['confirmed', 'preparing', 'ready', 'dispatched'])
                   ->where('updated_at <', $threshold)
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }

    /**
     * Criar pedido a partir de webhook
     */
    public function createFromWebhook($webhookData, $integrationId)
    {
        $orderData = [
            'restaurant_id' => $webhookData['restaurant_id'],
            'integration_id' => $integrationId,
            'platform' => $webhookData['platform'],
            'platform_order_id' => $webhookData['order_id'],
            'order_number' => $webhookData['order_number'] ?? null,
            'status' => $webhookData['status'] ?? 'pending',
            'customer_data' => json_encode($webhookData['customer'] ?? []),
            'delivery_data' => json_encode($webhookData['delivery'] ?? []),
            'items' => json_encode($webhookData['items'] ?? []),
            'subtotal' => $webhookData['subtotal'] ?? 0,
            'delivery_fee' => $webhookData['delivery_fee'] ?? 0,
            'service_fee' => $webhookData['service_fee'] ?? 0,
            'discount' => $webhookData['discount'] ?? 0,
            'total' => $webhookData['total'],
            'commission' => $webhookData['commission'] ?? 0,
            'net_amount' => $webhookData['net_amount'] ?? $webhookData['total'],
            'payment_method' => $webhookData['payment_method'] ?? null,
            'payment_status' => $webhookData['payment_status'] ?? 'pending',
            'estimated_delivery_time' => $webhookData['estimated_delivery_time'] ?? null,
            'preparation_time' => $webhookData['preparation_time'] ?? null,
            'notes' => $webhookData['notes'] ?? null,
            'platform_data' => json_encode($webhookData),
            'webhook_data' => json_encode($webhookData),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert($orderData);
    }

    /**
     * Limpar pedidos antigos
     */
    public function cleanOldOrders($daysOld = 90)
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        return $this->where('created_at <', $threshold)
                   ->whereIn('status', ['delivered', 'cancelled'])
                   ->delete();
    }
}