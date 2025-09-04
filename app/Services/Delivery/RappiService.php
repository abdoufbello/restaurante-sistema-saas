<?php

namespace App\Services\Delivery;

use App\Services\Delivery\BaseDeliveryService;
use Exception;

class RappiService extends BaseDeliveryService
{
    public function __construct()
    {
        parent::__construct('rappi');
    }

    /**
     * Autenticar com a API do Rappi
     */
    private function authenticate(array $credentials): ?string
    {
        try {
            $response = $this->makeRequest(
                'POST',
                $this->getAuthUrl(),
                [
                    'client_id' => $credentials['client_id'],
                    'client_secret' => $credentials['client_secret'],
                    'grant_type' => 'client_credentials'
                ]
            );
            
            if ($response['success'] && isset($response['data']['access_token'])) {
                return $response['data']['access_token'];
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Testar conexão com a API do Rappi
     */
    public function testConnection(array $credentials): array
    {
        try {
            // Validar credenciais obrigatórias
            $validation = $this->validateCredentials($credentials);
            if (!$validation['valid']) {
                return $this->errorResponse($validation['message']);
            }
            
            // Tentar autenticar
            $token = $this->authenticate($credentials);
            
            if (!$token) {
                return $this->errorResponse('Falha na autenticação com Rappi');
            }
            
            // Testar endpoint de status
            $response = $this->makeRequest(
                'GET',
                $this->getApiBaseUrl() . '/stores/status',
                [],
                ['Authorization' => 'Bearer ' . $token]
            );
            
            if ($response['success']) {
                return $this->successResponse(
                    $response['data'],
                    'Conexão com Rappi estabelecida com sucesso'
                );
            }
            
            return $this->errorResponse('Falha ao testar conexão: ' . ($response['error'] ?? 'Erro desconhecido'));
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao conectar com Rappi: ' . $e->getMessage());
        }
    }

    /**
     * Sincronizar cardápio com o Rappi
     */
    public function syncMenu(array $credentials, array $menuData): array
    {
        try {
            // Autenticar
            $token = $this->authenticate($credentials);
            if (!$token) {
                return $this->errorResponse('Falha na autenticação');
            }
            
            // Formatar dados do cardápio para o padrão do Rappi
            $formattedMenu = $this->formatMenuForRappi($menuData);
            
            $results = [
                'categories' => [],
                'products' => []
            ];
            
            // Sincronizar categorias
            if (!empty($formattedMenu['categories'])) {
                foreach ($formattedMenu['categories'] as $category) {
                    $response = $this->makeRequest(
                        'POST',
                        $this->getApiBaseUrl() . '/stores/menu/categories',
                        $category,
                        ['Authorization' => 'Bearer ' . $token]
                    );
                    
                    $results['categories'][] = [
                        'name' => $category['name'],
                        'success' => $response['success'],
                        'error' => $response['error'] ?? null
                    ];
                }
            }
            
            // Sincronizar produtos
            if (!empty($formattedMenu['products'])) {
                foreach ($formattedMenu['products'] as $product) {
                    $response = $this->makeRequest(
                        'POST',
                        $this->getApiBaseUrl() . '/stores/menu/products',
                        $product,
                        ['Authorization' => 'Bearer ' . $token]
                    );
                    
                    $results['products'][] = [
                        'name' => $product['name'],
                        'success' => $response['success'],
                        'error' => $response['error'] ?? null
                    ];
                }
            }
            
            return $this->successResponse($results, 'Cardápio sincronizado com sucesso');
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro na sincronização: ' . $e->getMessage());
        }
    }

    /**
     * Construir filtros para busca de pedidos
     */
    private function buildOrderFilters(array $filters): string
    {
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
        
        return http_build_query($queryParams);
    }

    /**
     * Formatar dados do cardápio para o padrão do Rappi
     */
    private function formatMenuForRappi(array $menuData): array
    {
        $formattedMenu = [
            'categories' => [],
            'products' => []
        ];
        
        // Formatar categorias
        if (isset($menuData['categories'])) {
            foreach ($menuData['categories'] as $category) {
                $formattedMenu['categories'][] = [
                    'name' => $category['name'],
                    'description' => $category['description'] ?? '',
                    'sort_order' => $category['order'] ?? 1,
                    'is_active' => $category['available'] ?? true
                ];
            }
        }
        
        // Formatar produtos
        if (isset($menuData['products'])) {
            foreach ($menuData['products'] as $product) {
                $formattedMenu['products'][] = [
                    'name' => $product['name'],
                    'description' => $product['description'] ?? '',
                    'price' => $product['price'],
                    'category_id' => $product['category_id'],
                    'is_active' => $product['available'] ?? true,
                    'image_url' => $product['image_url'] ?? ''
                ];
            }
        }
        
        return $formattedMenu;
    }

    /**
     * Formatar modificadores do produto
     */
    private function formatProductModifiers($options)
    {
        $modifiers = [];
        
        foreach ($options as $option) {
            $modifiers[] = [
                'name' => $option['name'],
                'required' => $option['required'] ?? false,
                'min_selection' => $option['min_selection'] ?? 0,
                'max_selection' => $option['max_selection'] ?? 1,
                'items' => array_map(function($item) {
                    return [
                        'name' => $item['name'],
                        'price' => (float)($item['price'] ?? 0)
                    ];
                }, $option['items'] ?? [])
            ];
        }
        
        return $modifiers;
    }

    /**
     * Obter pedidos do Rappi
     */
    public function getOrders(array $credentials, array $filters = []): array
    {
        try {
            // Autenticar
            $token = $this->authenticate($credentials);
            if (!$token) {
                return $this->errorResponse('Falha na autenticação');
            }
            
            // Formatar pedidos do Rappi para formato padrão
            $orders = $this->formatOrdersFromRappi($response['data']['orders'] ?? []);
            
            return $this->successResponse([
                'orders' => $orders,
                'total' => count($orders)
            ], 'Pedidos obtidos com sucesso');
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao obter pedidos: ' . $e->getMessage());
        }
    }

    /**
     * Formatar pedidos do Rappi para formato padrão
     */
    private function formatOrdersFromRappi(array $orders): array
    {
        $formattedOrders = [];
        
        foreach ($orders as $order) {
            $formattedOrders[] = [
                'id' => $order['id'],
                'platform_order_id' => $order['id'],
                'status' => $this->mapStatusFromPlatform($order['status'] ?? ''),
                'total' => $order['total'] ?? 0,
                'customer' => [
                    'name' => $order['customer']['name'] ?? '',
                    'phone' => $order['customer']['phone'] ?? ''
                ],
                'items' => $this->formatOrderItems($order['items'] ?? []),
                'delivery_address' => $order['delivery_address'] ?? [],
                'created_at' => $order['created_at'] ?? '',
                'platform' => 'rappi'
            ];
        }
        
        return $formattedOrders;
    }

    /**
     * Construir filtros para busca de pedidos
     */
    private function buildOrderFilters(array $filters): string
    {
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
        
        return http_build_query($queryParams);
    }

    /**
     * Atualizar status do pedido
     */
    public function updateOrderStatus(array $credentials, string $orderId, string $status): array
    {
        try {
            // Autenticar
            $token = $this->authenticate($credentials);
            if (!$token) {
                return $this->errorResponse('Falha na autenticação');
            }
            
            // Mapear status para o padrão do Rappi
            $rappiStatus = $this->mapStatusToPlatform($status);
            
            // Fazer requisição de atualização
            $response = $this->makeRequest(
                'PUT',
                $this->getApiBaseUrl() . '/orders/' . $orderId . '/status',
                [
                    'status' => $rappiStatus,
                    'updated_at' => date('c')
                ],
                ['Authorization' => 'Bearer ' . $token]
            );
            
            if ($response['success']) {
                return $this->successResponse([
                    'order_id' => $orderId,
                    'new_status' => $status
                ], 'Status atualizado com sucesso');
            }
            
            return $this->errorResponse('Erro ao atualizar status: ' . ($response['error'] ?? 'Erro desconhecido'));
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    /**
     * Processar webhook do Rappi
     */
    public function processWebhook(array $credentials, array $webhookData): array
    {
        try {
            $eventType = $webhookData['event_type'] ?? '';
            $orderData = $webhookData['data'] ?? [];
            
            // Verificar se o evento é suportado
            if (!$this->isWebhookEventSupported($eventType)) {
                return $this->errorResponse('Evento não suportado: ' . $eventType);
            }
            
            switch ($eventType) {
                case 'order.created':
                    return $this->handleNewOrder($orderData);
                    
                case 'order.confirmed':
                    return $this->handleOrderConfirmed($orderData);
                    
                case 'order.cancelled':
                    return $this->handleOrderCancelled($orderData);
                    
                case 'order.dispatched':
                    return $this->handleOrderDispatched($orderData);
                    
                case 'order.delivered':
                    return $this->handleOrderDelivered($orderData);
                    
                default:
                    return $this->errorResponse('Evento não reconhecido: ' . $eventType);
            }
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro no processamento do webhook: ' . $e->getMessage());
        }
    }

    /**
     * Processar pedido criado
     */
    private function processOrderCreated($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.created',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Processar mudança de status
     */
    private function processOrderStatusChanged($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.status_changed',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Processar pedido cancelado
     */
    private function processOrderCancelled($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.cancelled',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Processar pedido entregue
     */
    private function processOrderDelivered($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.delivered',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Formatar dados do pedido
     */
    private function formatOrderData($orderData)
    {
        return [
            'platform' => 'rappi',
            'order_id' => $orderData['id'] ?? null,
            'order_number' => $orderData['order_number'] ?? $orderData['id'],
            'status' => $this->mapStatusFromRappi($orderData['status'] ?? 'pending'),
            'customer' => [
                'name' => $orderData['customer']['name'] ?? '',
                'phone' => $orderData['customer']['phone'] ?? '',
                'email' => $orderData['customer']['email'] ?? ''
            ],
            'delivery' => [
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
                'estimated_time' => $orderData['estimated_delivery_time'] ?? null
            ],
            'items' => $this->formatOrderItems($orderData['items'] ?? []),
            'subtotal' => (float)($orderData['subtotal'] ?? 0),
            'delivery_fee' => (float)($orderData['delivery_fee'] ?? 0),
            'service_fee' => (float)($orderData['service_fee'] ?? 0),
            'discount' => (float)($orderData['discount'] ?? 0),
            'total' => (float)($orderData['total'] ?? 0),
            'payment_method' => $orderData['payment_method'] ?? 'unknown',
            'payment_status' => $orderData['payment_status'] ?? 'paid',
            'notes' => $orderData['notes'] ?? null,
            'created_at' => $orderData['created_at'] ?? date('Y-m-d H:i:s')
        ];
    }

    /**
      * Formatar itens do pedido
      */
     private function formatOrderItems(array $items): array
     {
         $formattedItems = [];
         
         foreach ($items as $item) {
             $formattedItems[] = [
                 'name' => $item['name'] ?? '',
                 'quantity' => $item['quantity'] ?? 1,
                 'price' => $item['price'] ?? 0,
                 'total' => ($item['quantity'] ?? 1) * ($item['price'] ?? 0)
             ];
         }
         
         return $formattedItems;
     }

    /**
      * Verificar se o evento do webhook é suportado
      */
     private function isWebhookEventSupported(string $eventType): bool
     {
         $supportedEvents = [
             'order.created',
             'order.confirmed',
             'order.cancelled',
             'order.dispatched',
             'order.delivered'
         ];
         
         return in_array($eventType, $supportedEvents);
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
     * Mapear status interno para plataforma
     */
    private function mapStatusToPlatform(string $status): string
    {
        $statusMap = [
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'dispatched' => 'dispatched',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled'
        ];
        
        return $statusMap[$status] ?? $status;
    }

    /**
     * Mapear status da plataforma para interno
     */
    private function mapStatusFromPlatform(string $platformStatus): string
    {
        $statusMap = [
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'dispatched' => 'dispatched',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled'
        ];
        
        return $statusMap[$platformStatus] ?? 'pending';
    }

    /**
      * Processar pedido despachado
      */
     private function handleOrderDispatched(array $orderData): array
     {
         return $this->successResponse(
             ['order_id' => $orderData['id'] ?? ''],
             'Despacho de pedido processado com sucesso'
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
            $url = $this->getApiBaseUrl() . '/orders' . ($queryParams ? '?' . $queryParams : '');
            
            // Fazer requisição
            $response = $this->makeRequest(
                'GET',
                $url,
                [],
                ['Authorization' => 'Bearer ' . $token]
            );
            
            if (!$response['success']) {
                return $this->errorResponse('Erro ao obter pedidos: ' . ($response['error'] ?? 'Erro desconhecido'));
            }
            
            // Formatar pedidos
            $orders = $this->formatOrdersFromRappi($response['data']['orders'] ?? []);
            
            return $this->successResponse([
                'orders' => $orders,
                'total' => count($orders)
            ], 'Pedidos obtidos com sucesso');
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao obter pedidos: ' . $e->getMessage());
        }
    }

    /**
     * Atualizar status do pedido
     */
    public function updateOrderStatus(array $credentials, string $orderId, string $status): array
    {
        try {
            // Autenticar
            $token = $this->authenticate($credentials);
            if (!$token) {
                return $this->errorResponse('Falha na autenticação');
            }
            
            // Mapear status para o padrão do Rappi
            $rappiStatus = $this->mapStatusToPlatform($status);
            
            // Fazer requisição de atualização
            $response = $this->makeRequest(
                'PUT',
                $this->getApiBaseUrl() . '/orders/' . $orderId . '/status',
                [
                    'status' => $rappiStatus,
                    'updated_at' => date('c')
                ],
                ['Authorization' => 'Bearer ' . $token]
            );
            
            if ($response['success']) {
                return $this->successResponse([
                    'order_id' => $orderId,
                    'new_status' => $status
                ], 'Status atualizado com sucesso');
            }
            
            return $this->errorResponse('Erro ao atualizar status: ' . ($response['error'] ?? 'Erro desconhecido'));
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    /**
     * Processar webhook do Rappi
     */
    public function processWebhook(array $credentials, array $webhookData): array
    {
        try {
            $eventType = $webhookData['event_type'] ?? '';
            $orderData = $webhookData['data'] ?? [];
            
            // Verificar se o evento é suportado
            if (!$this->isWebhookEventSupported($eventType)) {
                return $this->errorResponse('Evento não suportado: ' . $eventType);
            }
            
            switch ($eventType) {
                case 'order.created':
                    return $this->handleNewOrder($orderData);
                    
                case 'order.confirmed':
                    return $this->handleOrderConfirmed($orderData);
                    
                case 'order.cancelled':
                    return $this->handleOrderCancelled($orderData);
                    
                case 'order.dispatched':
                    return $this->handleOrderDispatched($orderData);
                    
                case 'order.delivered':
                    return $this->handleOrderDelivered($orderData);
                    
                default:
                    return $this->errorResponse('Evento não reconhecido: ' . $eventType);
            }
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro no processamento do webhook: ' . $e->getMessage());
        }
    }

    /**
     * Processar pedido criado
     */
    private function processOrderCreated($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.created',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Processar mudança de status
     */
    private function processOrderStatusChanged($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.status_changed',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Processar pedido cancelado
     */
    private function processOrderCancelled($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.cancelled',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Processar pedido entregue
     */
    private function processOrderDelivered($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.delivered',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Formatar dados do pedido
     */
    private function formatOrderData($orderData)
    {
        return [
            'platform' => 'rappi',
            'order_id' => $orderData['id'] ?? null,
            'order_number' => $orderData['order_number'] ?? $orderData['id'],
            'status' => $this->mapStatusFromRappi($orderData['status'] ?? 'pending'),
            'customer' => [
                'name' => $orderData['customer']['name'] ?? '',
                'phone' => $orderData['customer']['phone'] ?? '',
                'email' => $orderData['customer']['email'] ?? ''
            ],
            'delivery' => [
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
                'estimated_time' => $orderData['estimated_delivery_time'] ?? null
            ],
            'items' => $this->formatOrderItems($orderData['items'] ?? []),
            'subtotal' => (float)($orderData['subtotal'] ?? 0),
            'delivery_fee' => (float)($orderData['delivery_fee'] ?? 0),
            'service_fee' => (float)($orderData['service_fee'] ?? 0),
            'discount' => (float)($orderData['discount'] ?? 0),
            'total' => (float)($orderData['total'] ?? 0),
            'payment_method' => $orderData['payment_method'] ?? 'unknown',
            'payment_status' => $orderData['payment_status'] ?? 'paid',
            'notes' => $orderData['notes'] ?? null,
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
                'options' => $this->formatItemOptions($item['modifiers'] ?? [])
            ];
        }
        
        return $formattedItems;
    }

    /**
     * Formatar opções do item
     */
    private function formatItemOptions($modifiers)
    {
        $formattedOptions = [];
        
        foreach ($modifiers as $modifier) {
            $formattedOptions[] = [
                'name' => $modifier['name'] ?? '',
                'price' => (float)($modifier['price'] ?? 0)
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
     * Mapear status interno para Rappi
     */
    private function mapStatusToRappi($status)
    {
        $statusMap = [
            'confirmed' => 'accepted',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'dispatched' => 'dispatched',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled'
        ];
        
        return $statusMap[$status] ?? null;
    }

    /**
     * Mapear status do Rappi para interno
     */
    private function mapStatusFromRappi($rappiStatus)
    {
        $statusMap = [
            'pending' => 'pending',
            'accepted' => 'confirmed',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'dispatched' => 'dispatched',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'rejected' => 'cancelled'
        ];
        
        return $statusMap[$rappiStatus] ?? 'pending';
    }

    /**
     * Obter estatísticas de vendas
     */
    public function getSalesStats($dateFrom = null, $dateTo = null)
    {
        $auth = $this->authenticate();
        
        if (!$auth['success']) {
            return $auth;
        }

        try {
            $queryParams = [];
            
            if ($dateFrom) {
                $queryParams['date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $queryParams['date_to'] = $dateTo;
            }

            $url = $this->baseUrl . '/analytics/sales';
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }

            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $auth['access_token'],
                    'Content-Type' => 'application/json'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                return [
                    'success' => true,
                    'data' => json_decode($response->getBody(), true)
                ];
            }

            return [
                'success' => false,
                'error' => 'Erro HTTP: ' . $statusCode
            ];

        } catch (Exception $e) {
            $this->logger->error('Rappi Sales Stats Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Atualizar disponibilidade da loja
     */
    public function updateStoreAvailability($available = true)
    {
        $auth = $this->authenticate();
        
        if (!$auth['success']) {
            return $auth;
        }

        try {
            $response = $this->client->patch(
                $this->baseUrl . '/stores/availability',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $auth['access_token'],
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'available' => $available,
                        'updated_at' => date('c')
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200 || $statusCode === 204) {
                return [
                    'success' => true,
                    'message' => 'Disponibilidade atualizada com sucesso'
                ];
            }

            return [
                'success' => false,
                'error' => 'Erro HTTP: ' . $statusCode
            ];

        } catch (Exception $e) {
            $this->logger->error('Rappi Store Availability Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao atualizar disponibilidade: ' . $e->getMessage()
            ];
        }
    }
}