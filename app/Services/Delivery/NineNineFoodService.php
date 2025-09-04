<?php

namespace App\Services\Delivery;

use App\Services\Delivery\BaseDeliveryService;
use Exception;

class NineNineFoodService extends BaseDeliveryService
{
    public function __construct()
    {
        parent::__construct('99food');
    }

    /**
     * Autenticar com a API do 99Food
     */
    public function authenticate(array $credentials): array
    {
        try {
            $response = $this->makeRequest(
                'POST',
                $this->getApiBaseUrl() . '/auth/login',
                [
                    'email' => $credentials['email'],
                    'password' => $credentials['password'],
                    'api_key' => $credentials['api_key']
                ]
            );

            if (!$response['success']) {
                return $this->errorResponse('Falha na autenticação');
            }
            
            $data = $response['data'];
            
            if (isset($data['token'])) {
                return $this->successResponse([
                    'access_token' => $data['token'],
                    'expires_in' => $data['expires_in'] ?? 3600
                ]);
            }

            return $this->errorResponse('Token não encontrado na resposta');

        } catch (Exception $e) {
            return $this->errorResponse('Erro de conexão: ' . $e->getMessage());
        }
    }

    /**
     * Testar conexão com a API
     */
    public function testConnection(array $credentials): array
    {
        if (!$this->validateCredentials($credentials)) {
            return $this->errorResponse('Credenciais inválidas');
        }
        
        try {
            $auth = $this->authenticate($credentials);
            
            if (!$auth['success']) {
                return $auth;
            }
            
            // Testar uma chamada simples à API
            $response = $this->makeRequest(
                'GET',
                $this->getApiBaseUrl() . '/restaurants/profile',
                [],
                [
                    'Authorization' => 'Bearer ' . $auth['data']['access_token']
                ]
            );
            
            if ($response['success']) {
                return $this->successResponse([], 'Conexão estabelecida com sucesso');
            }
            
            return $this->errorResponse('Falha na conexão com a API');
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro de conexão: ' . $e->getMessage());
        }
    }

    /**
     * Sincronizar cardápio
     */
    public function syncMenu(array $menuData, array $credentials): array
    {
        $auth = $this->authenticate($credentials);
        
        if (!$auth['success']) {
            return $auth;
        }

        try {
            $results = [];

            // Sincronizar categorias
            foreach ($menuData['categories'] as $category) {
                $categoryResult = $this->syncCategory($auth['data']['access_token'], $category);
                $results['categories'][] = $categoryResult;
            }

            // Sincronizar produtos
            foreach ($menuData['products'] as $product) {
                $productResult = $this->syncProduct($auth['data']['access_token'], $product);
                $results['products'][] = $productResult;
            }

            return $this->successResponse($results, 'Sincronização concluída');

        } catch (Exception $e) {
            return $this->errorResponse('Erro na sincronização: ' . $e->getMessage());
        }
    }

    /**
     * Sincronizar categoria
     */
    private function syncCategory($accessToken, $category)
    {
        try {
            $categoryData = [
                'name' => $category['name'],
                'description' => $category['description'] ?? '',
                'active' => true,
                'order' => $category['sort_order'] ?? 0,
                'external_id' => (string)$category['id']
            ];

            // Adicionar imagem se disponível
            if (!empty($category['image'])) {
                $categoryData['image_url'] = $category['image'];
            }

            $response = $this->makeRequest(
                'POST',
                $this->getApiBaseUrl() . '/menu/categories',
                $categoryData,
                [
                    'Authorization' => 'Bearer ' . $accessToken
                ]
            );
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'category' => $category['name'],
                    'nineninenine_id' => $response['data']['id'] ?? null
                ];
            }

            return [
                'success' => false,
                'category' => $category['name'],
                'error' => $response['error'] ?? 'Falha na sincronização'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'category' => $category['name'],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sincronizar produto
     */
    private function syncProduct($accessToken, $product)
    {
        try {
            $productData = [
                'name' => $product['name'],
                'description' => $product['description'] ?? '',
                'price' => (float)$product['price'],
                'category_id' => $product['category_id'] ?? null,
                'active' => true,
                'available' => true,
                'order' => $product['sort_order'] ?? 0,
                'external_id' => (string)$product['id'],
                'preparation_time' => $product['preparation_time'] ?? 15
            ];

            // Adicionar imagem se disponível
            if (!empty($product['image'])) {
                $productData['image_url'] = $product['image'];
            }

            // Adicionar informações nutricionais se disponíveis
            if (!empty($product['nutritional_info'])) {
                $productData['nutritional_info'] = $product['nutritional_info'];
            }

            // Adicionar opções/complementos se disponíveis
            if (!empty($product['options'])) {
                $productData['complements'] = $this->formatProductComplements($product['options']);
            }

            $response = $this->makeRequest(
                'POST',
                $this->getApiBaseUrl() . '/menu/products',
                $productData,
                [
                    'Authorization' => 'Bearer ' . $accessToken
                ]
            );
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'product' => $product['name'],
                    'nineninenine_id' => $response['data']['id'] ?? null
                ];
            }

            return [
                'success' => false,
                'product' => $product['name'],
                'error' => $response['error'] ?? 'Falha na sincronização'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'product' => $product['name'],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formatar complementos do produto
     */
    private function formatProductComplements($options)
    {
        $complements = [];
        
        foreach ($options as $option) {
            $complements[] = [
                'name' => $option['name'],
                'required' => $option['required'] ?? false,
                'min_quantity' => $option['min_selection'] ?? 0,
                'max_quantity' => $option['max_selection'] ?? 1,
                'items' => array_map(function($item) {
                    return [
                        'name' => $item['name'],
                        'price' => (float)($item['price'] ?? 0),
                        'active' => true
                    ];
                }, $option['items'] ?? [])
            ];
        }
        
        return $complements;
    }

    /**
     * Obter pedidos
     */
    public function getOrders(array $filters, array $credentials): array
    {
        $auth = $this->authenticate($credentials);
        
        if (!$auth['success']) {
            return $auth;
        }

        try {
            $queryParams = [];
            
            if (isset($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            
            if (isset($filters['date_from'])) {
                $queryParams['date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to'])) {
                $queryParams['date_to'] = $filters['date_to'];
            }

            $queryParams['limit'] = $filters['limit'] ?? 50;
            $queryParams['offset'] = $filters['offset'] ?? 0;

            $url = $this->getApiBaseUrl() . '/orders';
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }

            $response = $this->makeRequest(
                'GET',
                $url,
                [],
                [
                    'Authorization' => 'Bearer ' . $auth['data']['access_token']
                ]
            );
            
            if ($response['success']) {
                return $this->successResponse([
                    'orders' => $this->formatOrders($response['data']['data'] ?? []),
                    'pagination' => $response['data']['pagination'] ?? []
                ]);
            }

            return $this->errorResponse('Falha ao obter pedidos');

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao obter pedidos: ' . $e->getMessage());
        }
    }

    /**
     * Atualizar status do pedido
     */
    public function updateOrderStatus(string $orderId, string $status, array $credentials): array
    {
        $auth = $this->authenticate($credentials);
        
        if (!$auth['success']) {
            return $auth;
        }

        try {
            // Mapear status interno para 99Food
            $nineNineStatus = $this->mapStatusToNineNineFood($status);
            
            if (!$nineNineStatus) {
                return $this->errorResponse('Status inválido: ' . $status);
            }

            $response = $this->makeRequest(
                'PUT',
                $this->getApiBaseUrl() . '/orders/' . $orderId . '/status',
                [
                    'status' => $nineNineStatus,
                    'updated_at' => date('c'),
                    'estimated_time' => $this->getEstimatedTimeByStatus($status)
                ],
                [
                    'Authorization' => 'Bearer ' . $auth['data']['access_token']
                ]
            );
            
            if ($response['success']) {
                return $this->successResponse([], 'Status atualizado com sucesso');
            }

            return $this->errorResponse('Falha ao atualizar status');

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    /**
     * Processar webhook
     */
    public function processWebhook(array $payload, array $headers = [], array $credentials = []): array
    {
        try {
            // Verificar assinatura do webhook se disponível
            if (isset($headers['X-99Food-Signature']) && isset($credentials['webhook_secret'])) {
                $expectedSignature = hash_hmac('sha256', json_encode($payload), $credentials['webhook_secret']);
                
                if (!hash_equals($expectedSignature, $headers['X-99Food-Signature'])) {
                    return $this->errorResponse('Assinatura inválida');
                }
            }

            $eventType = $payload['event'] ?? 'unknown';
            $orderData = $payload['order'] ?? [];

            // Processar diferentes tipos de eventos
            switch ($eventType) {
                case 'order_created':
                    return $this->processOrderCreated($orderData);
                    
                case 'order_confirmed':
                    return $this->processOrderConfirmed($orderData);
                    
                case 'order_cancelled':
                    return $this->processOrderCancelled($orderData);
                    
                case 'order_delivered':
                    return $this->processOrderDelivered($orderData);
                    
                case 'payment_confirmed':
                    return $this->processPaymentConfirmed($orderData);
                    
                default:
                    return $this->successResponse([], 'Evento não processado: ' . $eventType);
            }

        } catch (Exception $e) {
            return $this->errorResponse('Erro no processamento: ' . $e->getMessage());
        }
    }

    /**
     * Processar pedido criado
     */
    private function processOrderCreated($orderData)
    {
        return $this->successResponse([
            'event_type' => 'order.created',
            'order_data' => $this->formatOrderData($orderData)
        ]);
    }

    /**
     * Processar pedido confirmado
     */
    private function processOrderConfirmed($orderData)
    {
        return $this->successResponse([
            'event_type' => 'order.confirmed',
            'order_data' => $this->formatOrderData($orderData)
        ]);
    }

    /**
     * Processar pedido cancelado
     */
    private function processOrderCancelled($orderData)
    {
        return $this->successResponse([
            'event_type' => 'order.cancelled',
            'order_data' => $this->formatOrderData($orderData)
        ]);
    }

    /**
     * Processar pedido entregue
     */
    private function processOrderDelivered($orderData)
    {
        return $this->successResponse([
            'event_type' => 'order.delivered',
            'order_data' => $this->formatOrderData($orderData)
        ]);
    }

    /**
     * Processar pagamento confirmado
     */
    private function processPaymentConfirmed($orderData)
    {
        return $this->successResponse([
            'event_type' => 'payment.confirmed',
            'order_data' => $this->formatOrderData($orderData)
        ]);
    }

    /**
     * Formatar dados do pedido
     */
    private function formatOrderData($orderData)
    {
        return [
            'platform' => '99food',
            'order_id' => $orderData['id'] ?? null,
            'order_number' => $orderData['order_number'] ?? $orderData['id'],
            'status' => $this->mapStatusFromNineNineFood($orderData['status'] ?? 'pending'),
            'customer' => [
                'name' => $orderData['customer']['name'] ?? '',
                'phone' => $orderData['customer']['phone'] ?? '',
                'email' => $orderData['customer']['email'] ?? '',
                'document' => $orderData['customer']['document'] ?? ''
            ],
            'delivery' => [
                'type' => $orderData['delivery_type'] ?? 'delivery', // delivery ou pickup
                'address' => [
                    'street' => $orderData['delivery_address']['street'] ?? '',
                    'number' => $orderData['delivery_address']['number'] ?? '',
                    'neighborhood' => $orderData['delivery_address']['neighborhood'] ?? '',
                    'city' => $orderData['delivery_address']['city'] ?? '',
                    'state' => $orderData['delivery_address']['state'] ?? '',
                    'zipcode' => $orderData['delivery_address']['zipcode'] ?? '',
                    'complement' => $orderData['delivery_address']['complement'] ?? '',
                    'reference' => $orderData['delivery_address']['reference'] ?? ''
                ],
                'fee' => (float)($orderData['delivery_fee'] ?? 0),
                'estimated_time' => $orderData['estimated_delivery_time'] ?? null,
                'driver' => [
                    'name' => $orderData['driver']['name'] ?? null,
                    'phone' => $orderData['driver']['phone'] ?? null,
                    'vehicle' => $orderData['driver']['vehicle'] ?? null
                ]
            ],
            'items' => $this->formatOrderItems($orderData['items'] ?? []),
            'subtotal' => (float)($orderData['subtotal'] ?? 0),
            'delivery_fee' => (float)($orderData['delivery_fee'] ?? 0),
            'service_fee' => (float)($orderData['service_fee'] ?? 0),
            'discount' => (float)($orderData['discount'] ?? 0),
            'total' => (float)($orderData['total'] ?? 0),
            'payment_method' => $orderData['payment']['method'] ?? 'unknown',
            'payment_status' => $orderData['payment']['status'] ?? 'pending',
            'change_for' => (float)($orderData['payment']['change_for'] ?? 0),
            'notes' => $orderData['notes'] ?? null,
            'scheduled_for' => $orderData['scheduled_for'] ?? null,
            'created_at' => $orderData['created_at'] ?? date('Y-m-d H:i:s')
        ];
    }

    /**
     * Formatar itens do pedido
     */
    private function formatOrderItems($items)
    {
        $formattedItems = [];
        
        foreach ($items as $item) {
            $formattedItems[] = [
                'name' => $item['name'] ?? '',
                'quantity' => (int)($item['quantity'] ?? 1),
                'price' => (float)($item['unit_price'] ?? 0),
                'total' => (float)($item['total_price'] ?? 0),
                'notes' => $item['notes'] ?? null,
                'options' => $this->formatItemOptions($item['complements'] ?? [])
            ];
        }
        
        return $formattedItems;
    }

    /**
     * Formatar opções do item
     */
    private function formatItemOptions($complements)
    {
        $formattedOptions = [];
        
        foreach ($complements as $complement) {
            $formattedOptions[] = [
                'name' => $complement['name'] ?? '',
                'price' => (float)($complement['price'] ?? 0),
                'quantity' => (int)($complement['quantity'] ?? 1)
            ];
        }
        
        return $formattedOptions;
    }

    /**
     * Formatar lista de pedidos
     */
    private function formatOrders($orders)
    {
        $formattedOrders = [];
        
        foreach ($orders as $order) {
            $formattedOrders[] = $this->formatOrderData($order);
        }
        
        return $formattedOrders;
    }

    /**
     * Construir filtros de busca de pedidos
     */
    private function buildOrderFilters(array $filters): string
    {
        $queryParams = [];
        
        if (isset($filters['status'])) {
            $queryParams['status'] = $this->mapStatusToNineNineFood($filters['status']);
        }
        
        if (isset($filters['date_from'])) {
            $queryParams['date_from'] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $queryParams['date_to'] = $filters['date_to'];
        }
        
        if (isset($filters['limit'])) {
            $queryParams['limit'] = $filters['limit'];
        }
        
        if (isset($filters['offset'])) {
            $queryParams['offset'] = $filters['offset'];
        }
        
        return http_build_query($queryParams);
    }

    /**
     * Formatar pedidos do 99Food para o padrão do sistema
     */
    private function formatOrdersFrom99Food(array $orders): array
    {
        $formattedOrders = [];
        
        foreach ($orders as $order) {
            $formattedOrders[] = [
                'id' => $order['id'] ?? '',
                'external_id' => $order['id'] ?? '',
                'platform' => '99food',
                'status' => $this->mapStatusFromNineNineFood($order['status'] ?? ''),
                'customer' => [
                    'name' => $order['customer']['name'] ?? '',
                    'phone' => $order['customer']['phone'] ?? '',
                    'email' => $order['customer']['email'] ?? ''
                ],
                'items' => $this->formatOrderItems($order['items'] ?? []),
                'total' => $order['total'] ?? 0,
                'delivery_fee' => $order['delivery_fee'] ?? 0,
                'created_at' => $order['created_at'] ?? '',
                'delivery_address' => $order['delivery_address'] ?? []
            ];
        }
        
        return $formattedOrders;
    }

    /**
     * Processar novo pedido
     */
    private function handleNewOrder(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Novo pedido processado com sucesso'
        );
    }

    /**
     * Processar pedido confirmado
     */
    private function handleOrderConfirmed(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Confirmação de pedido processada com sucesso'
        );
    }

    /**
     * Processar pedido cancelado
     */
    private function handleOrderCancelled(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Cancelamento de pedido processado com sucesso'
        );
    }

    /**
     * Processar pedido entregue
     */
    private function handleOrderDelivered(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Entrega de pedido processada com sucesso'
        );
    }

    /**
     * Mapear status interno para 99Food
     */
    private function mapStatusToNineNineFood($status)
    {
        $statusMap = [
            'confirmed' => 'confirmed',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'dispatched' => 'dispatched',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled'
        ];
        
        return $statusMap[$status] ?? null;
    }

    /**
     * Mapear status do 99Food para interno
     */
    private function mapStatusFromNineNineFood($nineNineStatus)
    {
        $statusMap = [
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'dispatched' => 'dispatched',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'rejected' => 'cancelled'
        ];
        
        return $statusMap[$nineNineStatus] ?? 'pending';
    }

    /**
     * Obter tempo estimado por status
     */
    private function getEstimatedTimeByStatus($status)
    {
        $timeMap = [
            'confirmed' => 5,  // 5 minutos para confirmar
            'preparing' => 20, // 20 minutos para preparar
            'ready' => 30,     // 30 minutos total até ficar pronto
            'dispatched' => 45, // 45 minutos total até sair para entrega
            'delivered' => 60   // 60 minutos total até entregar
        ];
        
        return $timeMap[$status] ?? null;
    }

    /**
     * Atualizar horário de funcionamento
     */
    public function updateOperatingHours(array $schedule, array $credentials): array
    {
        $auth = $this->authenticate($credentials);
        
        if (!$auth['success']) {
            return $auth;
        }

        try {
            $response = $this->makeRequest(
                'PUT',
                $this->getApiBaseUrl() . '/restaurant/schedule',
                [
                    'schedule' => $schedule,
                    'updated_at' => date('c')
                ],
                [
                    'Authorization' => 'Bearer ' . $auth['data']['access_token']
                ]
            );
            
            if ($response['success']) {
                return $this->successResponse([], 'Horário de funcionamento atualizado com sucesso');
            }

            return $this->errorResponse('Falha ao atualizar horário');

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao atualizar horário: ' . $e->getMessage());
        }
    }

    /**
     * Obter relatório de vendas
     */
    public function getSalesReport(string $dateFrom, string $dateTo, array $credentials): array
    {
        $auth = $this->authenticate($credentials);
        
        if (!$auth['success']) {
            return $auth;
        }

        try {
            $queryParams = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'format' => 'json'
            ];

            $url = $this->getApiBaseUrl() . '/reports/sales?' . http_build_query($queryParams);

            $response = $this->makeRequest(
                'GET',
                $url,
                [],
                [
                    'Authorization' => 'Bearer ' . $auth['data']['access_token']
                ]
            );
            
            if ($response['success']) {
                return $this->successResponse($response['data']);
            }

            return $this->errorResponse('Falha ao obter relatório');

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao obter relatório: ' . $e->getMessage());
        }
    }
}