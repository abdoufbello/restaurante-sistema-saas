<?php

namespace App\Services\Delivery;

use Exception;

class IFoodService extends BaseDeliveryService
{
    public function __construct()
    {
        parent::__construct('ifood');
    }


    /**
     * Autenticar com a API do iFood
     */
    private function authenticate(array $credentials): ?string
    {
        try {
            $response = $this->makeRequest(
                $this->getAuthUrl(),
                [
                    'method' => 'POST',
                    'body' => [
                        'grantType' => 'client_credentials',
                        'clientId' => $credentials['client_id'],
                        'clientSecret' => $credentials['client_secret']
                    ]
                ]
            );
            
            if ($response['status_code'] === 200 && isset($response['body']['accessToken'])) {
                return $response['body']['accessToken'];
            }
            
            return null;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na autenticação iFood: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Testar conexão com a API do iFood
     */
    public function testConnection(array $credentials): array
    {
        try {
            // Validar credenciais obrigatórias
            if (!$this->validateCredentials($credentials)) {
                return $this->errorResponse(
                    'Credenciais incompletas para iFood',
                    ['required' => $this->config->getRequiredCredentials('ifood')]
                );
            }
            
            // Obter token de acesso
            $token = $this->authenticate($credentials);
            
            if (!$token) {
                return $this->errorResponse('Falha na autenticação com iFood');
            }
            
            // Testar uma chamada simples da API
            $response = $this->makeRequest(
                $this->getApiBaseUrl() . '/merchant/v1.0/merchants/' . $credentials['merchant_id'],
                [
                    'method' => 'GET',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json'
                    ]
                ]
            );
            
            if ($response['status_code'] === 200) {
                return $this->successResponse(
                    ['merchant_info' => $response['body']],
                    'Conexão com iFood estabelecida com sucesso'
                );
            }
            
            return $this->errorResponse('Falha na verificação da conexão', $response['body']);
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao testar conexão: ' . $e->getMessage());
        }
    }

    /**
     * Sincronizar cardápio com iFood
     */
    public function syncMenu(array $credentials, array $menuData): array
    {
        try {
            // Obter token de acesso
            $token = $this->authenticate($credentials);
            
            if (!$token) {
                return $this->errorResponse('Falha na autenticação com iFood');
            }

            // Formatar dados do cardápio para o padrão do iFood
            $formattedMenu = $this->formatMenuForIFood($menuData);
            
            $response = $this->makeRequest(
                $this->getApiBaseUrl() . '/merchant/v1.0/merchants/' . $credentials['merchant_id'] . '/menu',
                [
                    'method' => 'PUT',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json'
                    ],
                    'body' => $formattedMenu
                ]
            );

            if ($response['status_code'] === 200 || $response['status_code'] === 201) {
                return $this->successResponse(
                    ['synced_items' => count($menuData)],
                    'Cardápio sincronizado com sucesso no iFood'
                );
            }

            return $this->errorResponse('Erro na sincronização do cardápio', $response['body']);

        } catch (Exception $e) {
            return $this->errorResponse('Erro de sincronização: ' . $e->getMessage());
        }
    }

    /**
     * Sincronizar categoria
     */
    private function syncCategory($accessToken, $merchantId, $category)
    {
        try {
            $categoryData = [
                'name' => $category['name'],
                'template' => 'PIZZA', // Template padrão
                'order' => $category['order'] ?? 1,
                'availability' => [
                    'available' => $category['available'] ?? true
                ]
            ];

            $response = $this->client->post(
                $this->baseUrl . '/catalog/v1.0/merchants/' . $merchantId . '/categories',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $categoryData
                ]
            );

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 201 || $statusCode === 200) {
                return [
                    'success' => true,
                    'category' => $category['name'],
                    'ifood_id' => json_decode($response->getBody(), true)['id'] ?? null
                ];
            }

            return [
                'success' => false,
                'category' => $category['name'],
                'error' => 'HTTP ' . $statusCode
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
    private function syncProduct($accessToken, $merchantId, $product)
    {
        try {
            $productData = [
                'name' => $product['name'],
                'description' => $product['description'] ?? '',
                'externalCode' => $product['id'],
                'price' => [
                    'value' => $product['price'] * 100 // iFood usa centavos
                ],
                'availability' => [
                    'available' => $product['available'] ?? true
                ],
                'categoryId' => $product['ifood_category_id'] ?? null
            ];

            // Adicionar imagem se disponível
            if (!empty($product['image'])) {
                $productData['images'] = [
                    [
                        'url' => $product['image']
                    ]
                ];
            }

            $response = $this->client->post(
                $this->baseUrl . '/catalog/v1.0/merchants/' . $merchantId . '/items',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $productData
                ]
            );

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 201 || $statusCode === 200) {
                return [
                    'success' => true,
                    'product' => $product['name'],
                    'ifood_id' => json_decode($response->getBody(), true)['id'] ?? null
                ];
            }

            return [
                'success' => false,
                'product' => $product['name'],
                'error' => 'HTTP ' . $statusCode
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
     * Obter pedidos do iFood
     */
    public function getOrders(array $credentials, array $filters = []): array
    {
        try {
            // Obter token de acesso
            $token = $this->authenticate($credentials);
            
            if (!$token) {
                return $this->errorResponse('Falha na autenticação com iFood');
            }

            $queryParams = $this->buildOrderFilters($filters);
            $url = $this->getApiBaseUrl() . '/order/v1.0/orders' . ($queryParams ? '?' . $queryParams : '');
            
            $response = $this->makeRequest(
                $url,
                [
                    'method' => 'GET',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json'
                    ]
                ]
            );

            if ($response['status_code'] === 200) {
                $orders = $this->formatOrdersFromIFood($response['body']['orders'] ?? []);
                
                return $this->successResponse([
                    'orders' => $orders,
                    'total' => count($orders)
                ], 'Pedidos obtidos com sucesso do iFood');
            }

            return $this->errorResponse('Erro ao buscar pedidos', $response['body']);

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao buscar pedidos: ' . $e->getMessage());
        }
    }

    /**
     * Atualizar status do pedido
     */
    public function updateOrderStatus(array $credentials, string $orderId, string $status, string $reason = ''): array
    {
        try {
            // Obter token de acesso
            $token = $this->authenticate($credentials);
            
            if (!$token) {
                return $this->errorResponse('Falha na autenticação com iFood');
            }

            $statusData = [
                'status' => $this->mapStatusToPlatform($status),
                'reason' => $reason
            ];
            
            $response = $this->makeRequest(
                $this->getApiBaseUrl() . '/order/v1.0/orders/' . $orderId . '/status',
                [
                    'method' => 'POST',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json'
                    ],
                    'body' => $statusData
                ]
            );

            if ($response['status_code'] === 200 || $response['status_code'] === 202) {
                return $this->successResponse([
                    'order_id' => $orderId,
                    'new_status' => $status
                ], 'Status do pedido atualizado com sucesso no iFood');
            }

            return $this->errorResponse('Erro ao atualizar status do pedido', $response['body']);

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    /**
     * Processar webhook do iFood
     */
    public function processWebhook(array $webhookData, array $credentials): array
    {
        try {
            // Validar se o evento é suportado
            $eventType = $webhookData['eventType'] ?? '';
            
            if (!$this->isWebhookEventSupported($eventType)) {
                return $this->errorResponse('Tipo de evento não suportado: ' . $eventType);
            }
            
            $orderData = $webhookData['order'] ?? [];
            
            switch ($eventType) {
                case 'ORDER_PLACED':
                    return $this->handleNewOrder($orderData);
                    
                case 'ORDER_CONFIRMED':
                    return $this->handleOrderConfirmed($orderData);
                    
                case 'ORDER_CANCELLED':
                    return $this->handleOrderCancelled($orderData);
                    
                case 'ORDER_DISPATCHED':
                    return $this->handleOrderDispatched($orderData);
                    
                case 'ORDER_DELIVERED':
                    return $this->handleOrderDelivered($orderData);
                    
                default:
                    return $this->errorResponse('Tipo de evento não suportado: ' . $eventType);
            }
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao processar webhook: ' . $e->getMessage());
        }
    }

    /**
     * Processar pedido criado
     */
    private function processOrderPlaced($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.created',
            'order_data' => $this->formatOrderData($orderData)
        ];
    }

    /**
     * Processar pedido confirmado
     */
    private function processOrderConfirmed($orderData)
    {
        return [
            'success' => true,
            'event_type' => 'order.confirmed',
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
     * Formatar dados do pedido
     */
    private function formatOrderData($orderData)
    {
        return [
            'platform' => 'ifood',
            'order_id' => $orderData['id'] ?? null,
            'order_number' => $orderData['shortReference'] ?? null,
            'status' => $this->mapStatusFromIFood($orderData['status'] ?? 'pending'),
            'customer' => [
                'name' => $orderData['customer']['name'] ?? '',
                'phone' => $orderData['customer']['phone'] ?? '',
                'email' => $orderData['customer']['email'] ?? ''
            ],
            'delivery' => [
                'address' => $orderData['delivery']['deliveryAddress'] ?? [],
                'fee' => ($orderData['total']['deliveryFee'] ?? 0) / 100,
                'estimated_time' => $orderData['delivery']['deliveryDateTime'] ?? null
            ],
            'items' => $this->formatOrderItems($orderData['items'] ?? []),
            'subtotal' => ($orderData['total']['subTotal'] ?? 0) / 100,
            'delivery_fee' => ($orderData['total']['deliveryFee'] ?? 0) / 100,
            'service_fee' => ($orderData['total']['benefits'] ?? 0) / 100,
            'discount' => ($orderData['total']['discount'] ?? 0) / 100,
            'total' => ($orderData['total']['orderAmount'] ?? 0) / 100,
            'payment_method' => $orderData['payments'][0]['method'] ?? 'unknown',
            'payment_status' => 'paid', // iFood já processa o pagamento
            'notes' => $orderData['customer']['remarks'] ?? null,
            'created_at' => $orderData['createdAt'] ?? date('Y-m-d H:i:s')
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
                'quantity' => $item['quantity'] ?? 1,
                'price' => ($item['unitPrice'] ?? 0) / 100,
                'total' => ($item['totalPrice'] ?? 0) / 100,
                'notes' => $item['observations'] ?? null,
                'options' => $this->formatItemOptions($item['options'] ?? [])
            ];
        }
        
        return $formattedItems;
    }

    /**
     * Formatar opções do item
     */
    private function formatItemOptions($options)
    {
        $formattedOptions = [];
        
        foreach ($options as $option) {
            $formattedOptions[] = [
                'name' => $option['name'] ?? '',
                'price' => ($option['unitPrice'] ?? 0) / 100
            ];
        }
        
        return $formattedOptions;
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
            $queryParams['createdAt.gte'] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $queryParams['createdAt.lte'] = $filters['date_to'];
        }
        
        return http_build_query($queryParams);
    }

    /**
     * Formatar dados do cardápio para o padrão do iFood
     */
    private function formatMenuForIFood(array $menuData): array
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
                    'template' => 'PIZZA',
                    'order' => $category['order'] ?? 1,
                    'availability' => [
                        'available' => $category['available'] ?? true
                    ]
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
                    'categoryId' => $product['category_id'],
                    'availability' => [
                        'available' => $product['available'] ?? true
                    ],
                    'images' => $product['images'] ?? []
                ];
            }
        }
        
        return $formattedMenu;
    }

    /**
     * Formatar pedidos do iFood para formato padrão
     */
    private function formatOrdersFromIFood(array $orders): array
    {
        $formattedOrders = [];
        
        foreach ($orders as $order) {
            $formattedOrders[] = [
                'id' => $order['id'],
                'platform_order_id' => $order['id'],
                'status' => $this->mapStatusFromPlatform($order['status'] ?? ''),
                'total' => $order['total']['value'] ?? 0,
                'customer' => [
                    'name' => $order['customer']['name'] ?? '',
                    'phone' => $order['customer']['phone'] ?? ''
                ],
                'items' => $this->formatOrderItems($order['items'] ?? []),
                'delivery_address' => $order['deliveryAddress'] ?? [],
                'created_at' => $order['createdAt'] ?? '',
                'platform' => 'ifood'
            ];
        }
        
        return $formattedOrders;
    }

    /**
     * Processar novo pedido
     */
    private function handleNewOrder(array $orderData): array
    {
        // Lógica para processar novo pedido
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
            'Pedido confirmado processado com sucesso'
        );
    }

    /**
     * Processar pedido cancelado
     */
    private function handleOrderCancelled(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Pedido cancelado processado com sucesso'
        );
    }

    /**
     * Processar pedido despachado
     */
    private function handleOrderDispatched(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Pedido despachado processado com sucesso'
        );
    }

    /**
     * Processar pedido entregue
     */
    private function handleOrderDelivered(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Pedido entregue processado com sucesso'
        );
    }

    /**
     * Obter URL de autenticação
     */
    private function getAuthUrl(): string
    {
        return $this->getApiBaseUrl() . '/authentication/v1.0/oauth/token';
    }

    /**
     * Mapear status interno para iFood
     */
    private function mapStatusToIFood($status)
    {
        $statusMap = [
            'pending' => 'PLACED',
            'confirmed' => 'CONFIRMED',
            'preparing' => 'INTEGRATED',
            'ready' => 'READY_TO_PICKUP',
            'dispatched' => 'DISPATCHED',
            'delivered' => 'DELIVERED',
            'cancelled' => 'CANCELLED'
        ];
        
        return $statusMap[$status] ?? 'PLACED';
    }

    /**
     * Mapear status do iFood para interno
     */
    private function mapStatusFromIFood($ifoodStatus)
    {
        $statusMap = [
            'PLACED' => 'pending',
            'CONFIRMED' => 'confirmed',
            'INTEGRATED' => 'preparing',
            'READY_TO_PICKUP' => 'ready',
            'DISPATCHED' => 'dispatched',
            'DELIVERED' => 'delivered',
            'CANCELLED' => 'cancelled'
        ];
        
        return $statusMap[$ifoodStatus] ?? 'pending';
    }
}