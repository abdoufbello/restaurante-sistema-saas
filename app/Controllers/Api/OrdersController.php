<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\CustomerModel;

/**
 * Controlador de Pedidos para APIs RESTful
 */
class OrdersController extends BaseApiController
{
    protected $orderModel;
    protected $productModel;
    protected $customerModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductModel();
        $this->customerModel = new CustomerModel();
    }
    
    /**
     * Listar pedidos
     * GET /api/orders
     */
    public function index()
    {
        try {
            // Verificar permissão
            $this->requirePermission('orders.read');
            
            // Parâmetros de paginação e ordenação
            $pagination = $this->getPaginationParams();
            $sort = $this->getSortParams(['id', 'order_number', 'customer_id', 'status', 'total_amount', 'created_at']);
            $dateFilters = $this->getDateFilters();
            
            // Filtros específicos
            $filters = [
                'search' => $this->request->getGet('search'),
                'customer_id' => $this->request->getGet('customer_id'),
                'status' => $this->request->getGet('status'),
                'payment_status' => $this->request->getGet('payment_status'),
                'delivery_method' => $this->request->getGet('delivery_method'),
                'min_amount' => $this->request->getGet('min_amount'),
                'max_amount' => $this->request->getGet('max_amount'),
                'restaurant_id' => $this->restaurantId // Multi-tenancy
            ];
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('orders_list', array_merge($pagination, $sort, $filters, $dateFilters));
            $cachedData = $this->getFromCache($cacheKey);
            
            if ($cachedData) {
                return $this->respondWithPagination(
                    $cachedData['data'],
                    $cachedData['total'],
                    $pagination
                );
            }
            
            // Query base
            $query = $this->orderModel->select([
                'orders.id', 'orders.restaurant_id', 'orders.customer_id', 'orders.order_number',
                'orders.status', 'orders.payment_status', 'orders.delivery_method', 'orders.subtotal',
                'orders.tax_amount', 'orders.delivery_fee', 'orders.discount_amount', 'orders.total_amount',
                'orders.notes', 'orders.estimated_delivery_time', 'orders.delivered_at',
                'orders.created_at', 'orders.updated_at',
                'customers.name as customer_name', 'customers.email as customer_email',
                'customers.phone as customer_phone'
            ])->join('customers', 'customers.id = orders.customer_id', 'left');
            
            // Aplicar filtro de multi-tenancy
            $query = $this->applyTenantFilter($query, 'orders');
            
            // Aplicar filtros
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->groupStart()
                      ->like('orders.order_number', $search)
                      ->orLike('customers.name', $search)
                      ->orLike('customers.email', $search)
                      ->orLike('customers.phone', $search)
                      ->groupEnd();
            }
            
            if (!empty($filters['customer_id'])) {
                $query->where('orders.customer_id', $filters['customer_id']);
            }
            
            if (!empty($filters['status'])) {
                if (is_array($filters['status'])) {
                    $query->whereIn('orders.status', $filters['status']);
                } else {
                    $query->where('orders.status', $filters['status']);
                }
            }
            
            if (!empty($filters['payment_status'])) {
                $query->where('orders.payment_status', $filters['payment_status']);
            }
            
            if (!empty($filters['delivery_method'])) {
                $query->where('orders.delivery_method', $filters['delivery_method']);
            }
            
            if (!empty($filters['min_amount'])) {
                $query->where('orders.total_amount >=', $filters['min_amount']);
            }
            
            if (!empty($filters['max_amount'])) {
                $query->where('orders.total_amount <=', $filters['max_amount']);
            }
            
            // Filtros de data
            if (!empty($dateFilters['created_from'])) {
                $query->where('orders.created_at >=', $dateFilters['created_from']);
            }
            
            if (!empty($dateFilters['created_to'])) {
                $query->where('orders.created_at <=', $dateFilters['created_to']);
            }
            
            // Contar total
            $total = $query->countAllResults(false);
            
            // Aplicar ordenação e paginação
            $orders = $query->orderBy('orders.' . $sort['sort_by'], $sort['sort_dir'])
                           ->limit($pagination['limit'], $pagination['offset'])
                           ->findAll();
            
            // Processar dados dos pedidos
            foreach ($orders as &$order) {
                $order['items_count'] = $this->getOrderItemsCount($order['id']);
                $order['can_cancel'] = $this->canCancelOrder($order);
                $order['can_refund'] = $this->canRefundOrder($order);
            }
            
            // Sanitizar dados
            $orders = $this->sanitizeOutput($orders);
            
            // Salvar no cache
            $this->saveToCache($cacheKey, [
                'data' => $orders,
                'total' => $total
            ]);
            
            return $this->respondWithPagination($orders, $total, $pagination);
            
        } catch (\Exception $e) {
            log_message('error', 'Orders index error: ' . $e->getMessage());
            return $this->failServerError('Erro ao buscar pedidos');
        }
    }
    
    /**
     * Mostrar pedido específico
     * GET /api/orders/{id}
     */
    public function show($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('orders.read');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do pedido é obrigatório']);
            }
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('order_detail', ['id' => $id]);
            $cachedOrder = $this->getFromCache($cacheKey);
            
            if ($cachedOrder) {
                return $this->respondSuccess($cachedOrder);
            }
            
            // Buscar pedido
            $query = $this->orderModel->select([
                'orders.*',
                'customers.name as customer_name', 'customers.email as customer_email',
                'customers.phone as customer_phone', 'customers.address as customer_address'
            ])->join('customers', 'customers.id = orders.customer_id', 'left');
            
            // Aplicar filtro de multi-tenancy
            $query = $this->applyTenantFilter($query, 'orders');
            
            $order = $query->find($id);
            
            if (!$order) {
                return $this->failNotFound('Pedido não encontrado');
            }
            
            // Processar dados do pedido
            $order['delivery_address'] = json_decode($order['delivery_address'] ?? '{}', true);
            $order['payment_details'] = json_decode($order['payment_details'] ?? '{}', true);
            $order['metadata'] = json_decode($order['metadata'] ?? '{}', true);
            
            // Buscar itens do pedido
            $order['items'] = $this->getOrderItems($id);
            
            // Adicionar informações de status
            $order['can_cancel'] = $this->canCancelOrder($order);
            $order['can_refund'] = $this->canRefundOrder($order);
            $order['status_history'] = $this->getOrderStatusHistory($id);
            
            // Sanitizar dados
            $order = $this->sanitizeOutput($order);
            
            // Salvar no cache
            $this->saveToCache($cacheKey, $order);
            
            return $this->respondSuccess($order);
            
        } catch (\Exception $e) {
            log_message('error', 'Orders show error: ' . $e->getMessage());
            return $this->failServerError('Erro ao buscar pedido');
        }
    }
    
    /**
     * Criar novo pedido
     * POST /api/orders
     */
    public function create()
    {
        try {
            // Verificar permissão
            $this->requirePermission('orders.create');
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'customer_id' => 'required|integer|greater_than[0]',
                'items' => 'required|array',
                'items.*.product_id' => 'required|integer|greater_than[0]',
                'items.*.quantity' => 'required|integer|greater_than[0]',
                'items.*.price' => 'permit_empty|decimal|greater_than[0]',
                'items.*.notes' => 'permit_empty|string|max_length[255]',
                'delivery_method' => 'required|in_list[delivery,pickup,dine_in]',
                'delivery_address' => 'permit_empty|array',
                'payment_method' => 'required|in_list[cash,card,pix,online]',
                'notes' => 'permit_empty|string|max_length[500]',
                'discount_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
                'delivery_fee' => 'permit_empty|decimal|greater_than_equal_to[0]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Verificar se o cliente existe
            $customer = $this->customerModel->where('restaurant_id', $this->restaurantId)
                                          ->find($validatedData['customer_id']);
            
            if (!$customer) {
                return $this->failValidationErrors(['customer_id' => 'Cliente não encontrado']);
            }
            
            // Validar e calcular itens do pedido
            $orderItems = [];
            $subtotal = 0;
            
            foreach ($validatedData['items'] as $item) {
                // Buscar produto
                $product = $this->productModel->where('restaurant_id', $this->restaurantId)
                                             ->find($item['product_id']);
                
                if (!$product) {
                    return $this->failValidationErrors(['items' => "Produto ID {$item['product_id']} não encontrado"]);
                }
                
                if ($product['status'] !== 'active') {
                    return $this->failValidationErrors(['items' => "Produto '{$product['name']}' não está ativo"]);
                }
                
                // Verificar estoque
                if ($product['stock_quantity'] < $item['quantity']) {
                    return $this->failValidationErrors(['items' => "Estoque insuficiente para o produto '{$product['name']}'"]);  
                }
                
                // Usar preço do produto se não fornecido
                $price = $item['price'] ?? $product['price'];
                $itemTotal = $price * $item['quantity'];
                
                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'total_price' => $itemTotal,
                    'notes' => $item['notes'] ?? null
                ];
                
                $subtotal += $itemTotal;
            }
            
            // Calcular valores do pedido
            $discountAmount = $validatedData['discount_amount'] ?? 0;
            $deliveryFee = $validatedData['delivery_fee'] ?? 0;
            $taxRate = 0.10; // 10% de taxa (configurável)
            $taxAmount = ($subtotal - $discountAmount) * $taxRate;
            $totalAmount = $subtotal - $discountAmount + $deliveryFee + $taxAmount;
            
            // Gerar número do pedido
            $orderNumber = $this->generateOrderNumber();
            
            // Preparar dados do pedido
            $orderData = [
                'restaurant_id' => $this->restaurantId,
                'customer_id' => $validatedData['customer_id'],
                'order_number' => $orderNumber,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $validatedData['payment_method'],
                'delivery_method' => $validatedData['delivery_method'],
                'delivery_address' => json_encode($validatedData['delivery_address'] ?? []),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_fee' => $deliveryFee,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validatedData['notes'] ?? null,
                'items' => json_encode($orderItems),
                'created_by' => $this->currentUser['user_id']
            ];
            
            // Criar pedido
            $orderId = $this->orderModel->insert($orderData);
            
            if (!$orderId) {
                return $this->failServerError('Erro ao criar pedido');
            }
            
            // Atualizar estoque dos produtos
            foreach ($validatedData['items'] as $item) {
                $this->productModel->where('id', $item['product_id'])
                                  ->set('stock_quantity', 'stock_quantity - ' . $item['quantity'], false)
                                  ->update();
            }
            
            // Buscar pedido criado
            $order = $this->orderModel->find($orderId);
            $order['items'] = json_decode($order['items'], true);
            $order['delivery_address'] = json_decode($order['delivery_address'] ?? '{}', true);
            
            $order = $this->sanitizeOutput($order);
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('orders_list'));
            
            // Log da atividade
            $this->logActivity('order_create', [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'customer_id' => $validatedData['customer_id'],
                'total_amount' => $totalAmount
            ]);
            
            return $this->respondSuccess($order, 'Pedido criado com sucesso', 201);
            
        } catch (\Exception $e) {
            log_message('error', 'Orders create error: ' . $e->getMessage());
            return $this->failServerError('Erro ao criar pedido');
        }
    }
    
    /**
     * Atualizar status do pedido
     * PUT /api/orders/{id}/status
     */
    public function updateStatus($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('orders.update');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do pedido é obrigatório']);
            }
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'status' => 'required|in_list[pending,confirmed,preparing,ready,out_for_delivery,delivered,cancelled]',
                'notes' => 'permit_empty|string|max_length[255]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar pedido existente
            $query = $this->orderModel->select('*');
            $query = $this->applyTenantFilter($query);
            $order = $query->find($id);
            
            if (!$order) {
                return $this->failNotFound('Pedido não encontrado');
            }
            
            // Verificar se a mudança de status é válida
            if (!$this->isValidStatusTransition($order['status'], $validatedData['status'])) {
                return $this->failValidationErrors(['status' => 'Transição de status inválida']);
            }
            
            $updateData = [
                'status' => $validatedData['status'],
                'updated_by' => $this->currentUser['user_id']
            ];
            
            // Definir timestamps específicos
            switch ($validatedData['status']) {
                case 'confirmed':
                    $updateData['confirmed_at'] = date('Y-m-d H:i:s');
                    break;
                case 'ready':
                    $updateData['ready_at'] = date('Y-m-d H:i:s');
                    break;
                case 'delivered':
                    $updateData['delivered_at'] = date('Y-m-d H:i:s');
                    break;
                case 'cancelled':
                    $updateData['cancelled_at'] = date('Y-m-d H:i:s');
                    // Restaurar estoque se cancelado
                    $this->restoreOrderStock($order);
                    break;
            }
            
            // Atualizar pedido
            $updated = $this->orderModel->update($id, $updateData);
            
            if (!$updated) {
                return $this->failServerError('Erro ao atualizar status do pedido');
            }
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('orders_list'));
            $this->deleteFromCache($this->generateCacheKey('order_detail', ['id' => $id]));
            
            // Log da atividade
            $this->logActivity('order_status_update', [
                'order_id' => $id,
                'order_number' => $order['order_number'],
                'previous_status' => $order['status'],
                'new_status' => $validatedData['status'],
                'notes' => $validatedData['notes'] ?? null
            ]);
            
            return $this->respondSuccess([
                'previous_status' => $order['status'],
                'new_status' => $validatedData['status']
            ], 'Status do pedido atualizado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Orders update status error: ' . $e->getMessage());
            return $this->failServerError('Erro ao atualizar status do pedido');
        }
    }
    
    /**
     * Cancelar pedido
     * PUT /api/orders/{id}/cancel
     */
    public function cancel($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('orders.update');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do pedido é obrigatório']);
            }
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'reason' => 'required|string|max_length[255]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar pedido existente
            $query = $this->orderModel->select('*');
            $query = $this->applyTenantFilter($query);
            $order = $query->find($id);
            
            if (!$order) {
                return $this->failNotFound('Pedido não encontrado');
            }
            
            // Verificar se o pedido pode ser cancelado
            if (!$this->canCancelOrder($order)) {
                return $this->failForbidden('Pedido não pode ser cancelado no status atual');
            }
            
            // Atualizar pedido
            $updated = $this->orderModel->update($id, [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancellation_reason' => $validatedData['reason'],
                'updated_by' => $this->currentUser['user_id']
            ]);
            
            if (!$updated) {
                return $this->failServerError('Erro ao cancelar pedido');
            }
            
            // Restaurar estoque
            $this->restoreOrderStock($order);
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('orders_list'));
            $this->deleteFromCache($this->generateCacheKey('order_detail', ['id' => $id]));
            
            // Log da atividade
            $this->logActivity('order_cancel', [
                'order_id' => $id,
                'order_number' => $order['order_number'],
                'reason' => $validatedData['reason']
            ]);
            
            return $this->respondSuccess(null, 'Pedido cancelado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Orders cancel error: ' . $e->getMessage());
            return $this->failServerError('Erro ao cancelar pedido');
        }
    }
    
    /**
     * Estatísticas de pedidos
     * GET /api/orders/stats
     */
    public function stats()
    {
        try {
            // Verificar permissão
            $this->requirePermission('orders.read');
            
            $period = $this->request->getGet('period') ?: '30'; // dias
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('orders_stats', ['period' => $period]);
            $cachedStats = $this->getFromCache($cacheKey);
            
            if ($cachedStats) {
                return $this->respondSuccess($cachedStats);
            }
            
            // Query base com filtro de tenant
            $query = $this->orderModel->select('*');
            $query = $this->applyTenantFilter($query);
            
            // Estatísticas gerais
            $stats = $query->select([
                'COUNT(*) as total_orders',
                'COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_orders',
                'COUNT(CASE WHEN status = "confirmed" THEN 1 END) as confirmed_orders',
                'COUNT(CASE WHEN status = "preparing" THEN 1 END) as preparing_orders',
                'COUNT(CASE WHEN status = "ready" THEN 1 END) as ready_orders',
                'COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered_orders',
                'COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_orders',
                'SUM(total_amount) as total_revenue',
                'AVG(total_amount) as average_order_value',
                'MIN(total_amount) as min_order_value',
                'MAX(total_amount) as max_order_value'
            ])->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$period} days")))
              ->first();
            
            // Pedidos por método de entrega
            $byDeliveryMethod = $query->select([
                'delivery_method',
                'COUNT(*) as count',
                'SUM(total_amount) as revenue'
            ])->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$period} days")))
              ->groupBy('delivery_method')
              ->orderBy('count', 'DESC')
              ->findAll();
            
            // Pedidos por método de pagamento
            $byPaymentMethod = $query->select([
                'payment_method',
                'COUNT(*) as count',
                'SUM(total_amount) as revenue'
            ])->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$period} days")))
              ->groupBy('payment_method')
              ->orderBy('count', 'DESC')
              ->findAll();
            
            // Pedidos por dia (últimos 7 dias)
            $dailyOrders = $query->select([
                'DATE(created_at) as date',
                'COUNT(*) as orders',
                'SUM(total_amount) as revenue'
            ])->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
              ->groupBy('DATE(created_at)')
              ->orderBy('date', 'ASC')
              ->findAll();
            
            $result = [
                'general' => $stats,
                'by_delivery_method' => $byDeliveryMethod,
                'by_payment_method' => $byPaymentMethod,
                'daily_orders' => $dailyOrders,
                'period_days' => (int) $period
            ];
            
            // Salvar no cache por 5 minutos
            $this->saveToCache($cacheKey, $result, 300);
            
            return $this->respondSuccess($result);
            
        } catch (\Exception $e) {
            log_message('error', 'Orders stats error: ' . $e->getMessage());
            return $this->failServerError('Erro ao obter estatísticas');
        }
    }
    
    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================
    
    /**
     * Gerar número único do pedido
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = date('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $orderNumber = $prefix . $timestamp . $random;
        
        // Verificar se já existe
        while ($this->orderModel->where('restaurant_id', $this->restaurantId)
                               ->where('order_number', $orderNumber)
                               ->first()) {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $orderNumber = $prefix . $timestamp . $random;
        }
        
        return $orderNumber;
    }
    
    /**
     * Obter itens do pedido
     */
    private function getOrderItems(int $orderId): array
    {
        $order = $this->orderModel->find($orderId);
        
        if (!$order || empty($order['items'])) {
            return [];
        }
        
        return json_decode($order['items'], true) ?: [];
    }
    
    /**
     * Obter contagem de itens do pedido
     */
    private function getOrderItemsCount(int $orderId): int
    {
        $items = $this->getOrderItems($orderId);
        return array_sum(array_column($items, 'quantity'));
    }
    
    /**
     * Obter histórico de status do pedido
     */
    private function getOrderStatusHistory(int $orderId): array
    {
        // TODO: Implementar tabela de histórico de status
        return [];
    }
    
    /**
     * Verificar se o pedido pode ser cancelado
     */
    private function canCancelOrder(array $order): bool
    {
        $cancellableStatuses = ['pending', 'confirmed', 'preparing'];
        return in_array($order['status'], $cancellableStatuses);
    }
    
    /**
     * Verificar se o pedido pode ser reembolsado
     */
    private function canRefundOrder(array $order): bool
    {
        return $order['payment_status'] === 'paid' && 
               in_array($order['status'], ['cancelled', 'delivered']);
    }
    
    /**
     * Verificar se a transição de status é válida
     */
    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['out_for_delivery', 'delivered'],
            'out_for_delivery' => ['delivered'],
            'delivered' => [], // Status final
            'cancelled' => [] // Status final
        ];
        
        return isset($validTransitions[$currentStatus]) && 
               in_array($newStatus, $validTransitions[$currentStatus]);
    }
    
    /**
     * Restaurar estoque dos produtos do pedido
     */
    private function restoreOrderStock(array $order): void
    {
        $items = json_decode($order['items'] ?? '[]', true);
        
        foreach ($items as $item) {
            $this->productModel->where('id', $item['product_id'])
                              ->set('stock_quantity', 'stock_quantity + ' . $item['quantity'], false)
                              ->update();
        }
    }
}