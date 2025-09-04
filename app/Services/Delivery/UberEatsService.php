<?php

namespace App\Services\Delivery;

use App\Services\Delivery\BaseDeliveryService;
use Exception;

/**
 * Serviço de integração com Uber Eats
 */
class UberEatsService extends BaseDeliveryService
{
    public function __construct()
    {
        parent::__construct('uber_eats');
    }

    /**
     * Configurar credenciais
     */
    public function setCredentials(array $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * Autenticar com Uber Eats
     */
    private function authenticate(array $credentials): ?string
    {
        try {
            $response = $this->makeRequest(
                $this->getAuthUrl(),
                [
                    'method' => 'POST',
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'body' => [
                        'client_id' => $credentials['client_id'],
                        'client_secret' => $credentials['client_secret'],
                        'grant_type' => 'client_credentials',
                        'scope' => 'eats.store'
                    ]
                ]
            );

            if ($response['status_code'] === 200) {
                return $response['body']['access_token'] ?? null;
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Validar credenciais
     */
    protected function validateCredentials(array $credentials): bool
    {
        $required = ['client_id', 'client_secret', 'store_id'];
        
        foreach ($required as $field) {
            if (empty($credentials[$field])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Obter URL base da API
     */
    protected function getApiBaseUrl(): string
    {
        return 'https://api.uber.com/v1/eats';
    }

    /**
     * Obter URL de autenticação
     */
    protected function getAuthUrl(): string
    {
        return 'https://login.uber.com/oauth/v2/token';
    }

    /**
     * Testar conexão com Uber Eats
     */
    public function testConnection(array $credentials): array
    {
        try {
            // Validar credenciais obrigatórias
            if (!$this->validateCredentials($credentials)) {
                return $this->errorResponse('Credenciais inválidas ou incompletas');
            }

            // Obter token de acesso
            $token = $this->authenticate($credentials);
            
            if (!$token) {
                return $this->errorResponse('Falha na autenticação com Uber Eats');
            }

            // Testar endpoint de informações da loja
            $response = $this->makeRequest(
                $this->getApiBaseUrl() . '/stores/' . $credentials['store_id'],
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
                    [
                        'store_info' => [
                            'id' => $response['body']['id'] ?? '',
                            'name' => $response['body']['name'] ?? '',
                            'status' => $response['body']['status'] ?? ''
                        ]
                    ],
                    'Conexão com Uber Eats estabelecida com sucesso'
                );
            }

            return $this->errorResponse('Erro na conexão com Uber Eats', $response['body']);

        } catch (Exception $e) {
            return $this->errorResponse('Erro de conexão: ' . $e->getMessage());
        }
    }

    /**
     * Sincronizar cardápio com Uber Eats
     */
    public function syncMenu(array $credentials, array $menuData): array
    {
        try {
            // Obter token de acesso
            $token = $this->authenticate($credentials);
            
            if (!$token) {
                return $this->errorResponse('Falha na autenticação com Uber Eats');
            }

            // Formatar dados do cardápio para o padrão do Uber Eats
            $formattedMenu = $this->formatMenuForUberEats($menuData);
            
            $response = $this->makeRequest(
                $this->getApiBaseUrl() . '/stores/' . $credentials['store_id'] . '/menus',
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
                    'Cardápio sincronizado com sucesso no Uber Eats'
                );
            }

            return $this->errorResponse('Erro na sincronização do cardápio', $response['body']);

        } catch (Exception $e) {
            return $this->errorResponse('Erro de sincronização: ' . $e->getMessage());
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
            $queryParams['since'] = strtotime($filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $queryParams['until'] = strtotime($filters['date_to']);
        }
        
        return http_build_query($queryParams);
    }

    /**
     * Formatar dados do cardápio para o padrão do Uber Eats
     */
    private function formatMenuForUberEats(array $menuData): array
    {
        $formattedMenu = [
            'categories' => [],
            'items' => []
        ];
        
        // Formatar categorias
        if (isset($menuData['categories'])) {
            foreach ($menuData['categories'] as $category) {
                $formattedMenu['categories'][] = [
                    'name' => $category['name'],
                    'sort_order' => $category['order'] ?? 1,
                    'enabled' => $category['available'] ?? true
                ];
            }
        }
        
        // Formatar produtos
        if (isset($menuData['products'])) {
            foreach ($menuData['products'] as $product) {
                $formattedMenu['items'][] = [
                    'title' => $product['name'],
                    'description' => $product['description'] ?? '',
                    'price' => $product['price'] * 100, // Uber Eats usa centavos
                    'category_id' => $product['category_id'],
                    'enabled' => $product['available'] ?? true,
                    'image_url' => $product['image_url'] ?? ''
                ];
            }
        }
        
        return $formattedMenu;
    }

    /**
     * Obter pedidos do Uber Eats
     */
    public function getOrders(array $credentials, array $filters = []): array
    {
        try {
            // Obter token de acesso
            $token = $this->authenticate($credentials);
            
            if (!$token) {
                return $this->errorResponse('Falha na autenticação com Uber Eats');
            }

            $queryParams = $this->buildOrderFilters($filters);
            $url = $this->getApiBaseUrl() . '/stores/' . $credentials['store_id'] . '/orders' . ($queryParams ? '?' . $queryParams : '');
            
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
                $orders = $this->formatOrdersFromUberEats($response['body']['orders'] ?? []);
                
                return $this->successResponse([
                    'orders' => $orders,
                    'total' => count($orders)
                ], 'Pedidos obtidos com sucesso do Uber Eats');
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
                return $this->errorResponse('Falha na autenticação com Uber Eats');
            }

            $statusData = [
                'status' => $this->mapStatusToPlatform($status),
                'reason' => $reason
            ];
            
            $response = $this->makeRequest(
                $this->getApiBaseUrl() . '/orders/' . $orderId . '/status',
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
                ], 'Status do pedido atualizado com sucesso no Uber Eats');
            }

            return $this->errorResponse('Erro ao atualizar status do pedido', $response['body']);

        } catch (Exception $e) {
            return $this->errorResponse('Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    /**
     * Processar webhook do Uber Eats
     */
    public function processWebhook(array $webhookData, array $credentials): array
    {
        try {
            // Validar se o evento é suportado
            $eventType = $webhookData['event_type'] ?? '';
            
            if (!$this->isWebhookEventSupported($eventType)) {
                return $this->errorResponse('Tipo de evento não suportado: ' . $eventType);
            }
            
            $orderData = $webhookData['data'] ?? [];
            
            switch ($eventType) {
                case 'orders.notification':
                    return $this->handleOrderNotification($orderData);
                    
                case 'orders.status_changed':
                    return $this->handleOrderStatusChanged($orderData);
                    
                case 'orders.cancel':
                    return $this->handleOrderCancelled($orderData);
                    
                default:
                    return $this->errorResponse('Tipo de evento não suportado: ' . $eventType);
            }
            
        } catch (Exception $e) {
            return $this->errorResponse('Erro ao processar webhook: ' . $e->getMessage());
        }
    }



    /**
     * Formatar dados do pedido
     */
    private function formatOrderData($orderData)
    {
        return [
            'platform' => 'ubereats',
            'order_id' => $orderData['id'] ?? null,
            'order_number' => $orderData['display_id'] ?? null,
            'status' => $this->mapStatusFromUberEats($orderData['current_state'] ?? 'created'),
            'customer' => [
                'name' => $orderData['eater']['first_name'] . ' ' . ($orderData['eater']['last_name'] ?? ''),
                'phone' => $orderData['eater']['phone'] ?? '',
                'email' => $orderData['eater']['email'] ?? ''
            ],
            'delivery' => [
                'address' => $orderData['delivery']['location'] ?? [],
                'fee' => ($orderData['payment']['charges']['total_fee'] ?? 0) / 100,
                'estimated_time' => $orderData['estimated_ready_for_pickup_at'] ?? null
            ],
            'items' => $this->formatOrderItems($orderData['cart']['items'] ?? []),
            'subtotal' => ($orderData['payment']['charges']['sub_total'] ?? 0) / 100,
            'delivery_fee' => ($orderData['payment']['charges']['total_fee'] ?? 0) / 100,
            'service_fee' => ($orderData['payment']['charges']['uber_fee'] ?? 0) / 100,
            'discount' => ($orderData['payment']['charges']['total_promo'] ?? 0) / 100,
            'total' => ($orderData['payment']['charges']['total_charge'] ?? 0) / 100,
            'payment_method' => $orderData['payment']['payment_method'] ?? 'unknown',
            'payment_status' => 'paid', // Uber Eats já processa o pagamento
            'notes' => $orderData['special_instructions'] ?? null,
            'created_at' => isset($orderData['placed_at']) ? date('Y-m-d H:i:s', $orderData['placed_at']) : date('Y-m-d H:i:s')
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
                'name' => $item['title'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => ($item['price'] ?? 0) / 100,
                'total' => (($item['price'] ?? 0) * ($item['quantity'] ?? 1)) / 100,
                'notes' => $item['special_instructions'] ?? null,
                'options' => $this->formatItemOptions($item['selected_modifier_groups'] ?? [])
            ];
        }
        
        return $formattedItems;
    }

    /**
     * Formatar opções do item
     */
    private function formatItemOptions($modifierGroups)
    {
        $formattedOptions = [];
        
        foreach ($modifierGroups as $group) {
            foreach ($group['selected_items'] ?? [] as $item) {
                $formattedOptions[] = [
                    'name' => $item['title'] ?? '',
                    'price' => ($item['price'] ?? 0) / 100
                ];
            }
        }
        
        return $formattedOptions;
    }

    /**
     * Formatar pedidos do Uber Eats para formato padrão
     */
    private function formatOrdersFromUberEats(array $orders): array
    {
        $formattedOrders = [];
        
        foreach ($orders as $order) {
            $formattedOrders[] = [
                'id' => $order['id'],
                'platform_order_id' => $order['id'],
                'status' => $this->mapStatusFromPlatform($order['current_state'] ?? ''),
                'total' => ($order['payment']['charges']['total'] ?? 0) / 100, // Converter de centavos
                'customer' => [
                    'name' => $order['eater']['first_name'] . ' ' . ($order['eater']['last_name'] ?? ''),
                    'phone' => $order['eater']['phone'] ?? ''
                ],
                'items' => $this->formatOrderItems($order['cart']['items'] ?? []),
                'delivery_address' => $order['delivery']['location'] ?? [],
                'created_at' => $order['placed_at'] ?? '',
                'platform' => 'uber_eats'
            ];
        }
        
        return $formattedOrders;
    }

    /**
     * Mapear status interno para plataforma
     */
    private function mapStatusToPlatform(string $status): string
    {
        $statusMap = [
            'confirmed' => 'accepted',
            'preparing' => 'preparing',
            'ready' => 'ready_for_pickup',
            'dispatched' => 'order_picked_up',
            'delivered' => 'delivered',
            'cancelled' => 'denied'
        ];
        
        return $statusMap[$status] ?? $status;
    }

    /**
     * Mapear status da plataforma para interno
     */
    private function mapStatusFromPlatform(string $platformStatus): string
    {
        $statusMap = [
            'created' => 'pending',
            'accepted' => 'confirmed',
            'preparing' => 'preparing',
            'ready_for_pickup' => 'ready',
            'order_picked_up' => 'dispatched',
            'delivered' => 'delivered',
            'denied' => 'cancelled',
            'cancelled' => 'cancelled'
        ];
        
        return $statusMap[$platformStatus] ?? 'pending';
    }

    /**
     * Verificar se o evento do webhook é suportado
     */
    private function isWebhookEventSupported(string $eventType): bool
    {
        $supportedEvents = [
            'orders.notification',
            'orders.status_changed',
            'orders.cancel'
        ];
        
        return in_array($eventType, $supportedEvents);
    }

    /**
     * Processar notificação de pedido
     */
    private function handleOrderNotification(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Notificação de pedido processada com sucesso'
        );
    }

    /**
     * Processar mudança de status do pedido
     */
    private function handleOrderStatusChanged(array $orderData): array
    {
        return $this->successResponse(
            ['order_id' => $orderData['id'] ?? ''],
            'Mudança de status processada com sucesso'
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
}