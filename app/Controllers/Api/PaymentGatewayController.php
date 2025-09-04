<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class PaymentGatewayController extends ResourceController
{
    use ResponseTrait;

    protected $modelName = 'App\Models\PaymentGateway';
    protected $format = 'json';

    /**
     * Lista todos os gateways de pagamento disponíveis
     * GET /api/v1/payment-gateways
     */
    public function index()
    {
        // Verificar permissão
        if (!$this->hasPermission('payments.read')) {
            return $this->failForbidden('Sem permissão para visualizar gateways de pagamento');
        }

        try {
            $restaurantId = $this->getRestaurantId();
            
            // Buscar gateways configurados para o restaurante
            $gateways = $this->model->where('restaurant_id', $restaurantId)
                                  ->where('is_active', 1)
                                  ->findAll();

            // Gateways disponíveis no sistema
            $availableGateways = [
                'pix' => [
                    'name' => 'PIX',
                    'type' => 'instant_payment',
                    'fees' => '0.99%',
                    'settlement' => 'D+0',
                    'supported_methods' => ['pix']
                ],
                'stripe' => [
                    'name' => 'Stripe',
                    'type' => 'credit_card',
                    'fees' => '3.4% + R$ 0,40',
                    'settlement' => 'D+2',
                    'supported_methods' => ['credit_card', 'debit_card']
                ],
                'pagseguro' => [
                    'name' => 'PagSeguro',
                    'type' => 'multiple',
                    'fees' => '3.79%',
                    'settlement' => 'D+14',
                    'supported_methods' => ['credit_card', 'debit_card', 'pix', 'boleto']
                ],
                'mercadopago' => [
                    'name' => 'Mercado Pago',
                    'type' => 'multiple',
                    'fees' => '3.99%',
                    'settlement' => 'D+14',
                    'supported_methods' => ['credit_card', 'debit_card', 'pix']
                ],
                'paypal' => [
                    'name' => 'PayPal',
                    'type' => 'digital_wallet',
                    'fees' => '4.99%',
                    'settlement' => 'D+1',
                    'supported_methods' => ['paypal']
                ]
            ];

            return $this->respond([
                'success' => true,
                'data' => [
                    'configured_gateways' => $gateways,
                    'available_gateways' => $availableGateways,
                    'total' => count($gateways)
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Erro ao listar gateways: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }

    /**
     * Configura um gateway de pagamento
     * POST /api/v1/payment-gateways
     */
    public function create()
    {
        // Verificar permissão
        if (!$this->hasPermission('payments.create')) {
            return $this->failForbidden('Sem permissão para configurar gateways');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'gateway_type' => 'required|in_list[pix,stripe,pagseguro,mercadopago,paypal]',
            'is_active' => 'required|boolean',
            'credentials' => 'required|valid_json',
            'webhook_url' => 'permit_empty|valid_url'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->failValidationErrors($validation->getErrors());
        }

        try {
            $restaurantId = $this->getRestaurantId();
            $data = $this->request->getJSON(true);

            // Verificar se já existe configuração para este gateway
            $existing = $this->model->where('restaurant_id', $restaurantId)
                                  ->where('gateway_type', $data['gateway_type'])
                                  ->first();

            if ($existing) {
                return $this->failConflict('Gateway já configurado para este restaurante');
            }

            // Criptografar credenciais sensíveis
            $credentials = json_decode($data['credentials'], true);
            $encryptedCredentials = $this->encryptCredentials($credentials);

            $gatewayData = [
                'restaurant_id' => $restaurantId,
                'gateway_type' => $data['gateway_type'],
                'is_active' => $data['is_active'],
                'credentials' => json_encode($encryptedCredentials),
                'webhook_url' => $data['webhook_url'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $gatewayId = $this->model->insert($gatewayData);

            // Log da atividade
            $this->logActivity('payment_gateway_configured', [
                'gateway_id' => $gatewayId,
                'gateway_type' => $data['gateway_type']
            ]);

            return $this->respondCreated([
                'success' => true,
                'message' => 'Gateway configurado com sucesso',
                'data' => ['id' => $gatewayId]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Erro ao configurar gateway: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }

    /**
     * Processa um pagamento
     * POST /api/v1/payment-gateways/process
     */
    public function processPayment()
    {
        // Verificar permissão
        if (!$this->hasPermission('payments.create')) {
            return $this->failForbidden('Sem permissão para processar pagamentos');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'gateway_type' => 'required|in_list[pix,stripe,pagseguro,mercadopago,paypal]',
            'amount' => 'required|decimal|greater_than[0]',
            'currency' => 'permit_empty|string|max_length[3]',
            'order_id' => 'permit_empty|integer',
            'payment_data' => 'permit_empty|valid_json',
            'description' => 'permit_empty|string|max_length[255]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->failValidationErrors($validation->getErrors());
        }

        try {
            $restaurantId = $this->getRestaurantId();
            $data = $this->request->getJSON(true);

            // Processar pagamento baseado no gateway
            $result = $this->processPaymentByGateway($restaurantId, $data);

            if (!$result['success']) {
                return $this->fail($result['error'], 400);
            }

            // Log da atividade
            $this->logActivity('payment_processed', [
                'gateway_type' => $data['gateway_type'],
                'amount' => $data['amount'],
                'transaction_id' => $result['transaction_id'],
                'status' => $result['status'] ?? 'pending'
            ]);

            return $this->respond([
                'success' => true,
                'message' => 'Pagamento processado com sucesso',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Erro ao processar pagamento: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }

    /**
     * Webhook para receber notificações dos gateways
     * POST /api/v1/payment-gateways/webhook/{gateway_type}
     */
    public function webhook($gatewayType = null)
    {
        try {
            $payload = $this->request->getBody();
            $headers = $this->request->getHeaders();

            // Determinar gateway se não especificado na URL
            if (!$gatewayType) {
                $gatewayType = $this->detectGatewayFromWebhook($headers, $payload);
            }
            
            if (!$gatewayType) {
                return $this->fail('Gateway não identificado', 400);
            }

            // Processar webhook baseado no gateway
            $result = $this->processWebhookByGateway($gatewayType, $payload, $headers);

            if ($result['success']) {
                return $this->respond(['success' => true, 'message' => 'Webhook processado']);
            } else {
                return $this->fail($result['error'], 400);
            }

        } catch (\Exception $e) {
            log_message('error', 'Erro no webhook: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Processar webhook baseado no gateway
     */
    private function processWebhookByGateway($gatewayType, $payload, $headers)
    {
        // Para webhooks, precisamos identificar o restaurante
        // Em produção, isso seria feito através da URL ou configuração do webhook
        $restaurantId = 1; // Temporário - implementar identificação real
        
        switch ($gatewayType) {
            case 'pix':
                $pixService = new \App\Services\PaymentServices\PixService();
                return $pixService->processPixWebhook($restaurantId, $payload, $headers);
                
            case 'stripe':
                $stripeService = new \App\Services\PaymentServices\StripeService();
                // Para Stripe, precisamos do endpoint secret
                $endpointSecret = 'whsec_test_secret'; // Em produção, buscar da configuração
                $signature = $headers['Stripe-Signature'] ?? '';
                return $stripeService->processWebhook($restaurantId, $payload, $signature, $endpointSecret);
                
            case 'mercadopago':
                $mercadoPagoService = new \App\Services\PaymentServices\MercadoPagoService();
                return $mercadoPagoService->processWebhook($restaurantId, $payload, $headers);
                
            default:
                // Para outros gateways, simular processamento
                return $this->simulateWebhookProcessing($gatewayType, $payload);
        }
    }
    
    /**
     * Detectar gateway a partir do webhook
     */
    private function detectGatewayFromWebhook($headers, $payload)
    {
        // Stripe
        if (isset($headers['Stripe-Signature'])) {
            return 'stripe';
        }
        
        // Mercado Pago
        if (isset($headers['X-Signature'])) {
            return 'mercadopago';
        }
        
        // PIX (baseado no conteúdo)
        $data = json_decode($payload, true);
        if (isset($data['pix']) || isset($data['endToEndId'])) {
            return 'pix';
        }
        
        return null;
    }
    
    /**
     * Simular processamento de webhook
     */
    private function simulateWebhookProcessing($gatewayType, $payload)
    {
        $data = json_decode($payload, true);
        
        // Log do webhook recebido
        log_message('info', "Webhook {$gatewayType} recebido: " . $payload);
        
        return [
            'success' => true,
            'message' => "Webhook {$gatewayType} processado com sucesso"
        ];
    }
    
    /**
     * Simular verificação de status
     */
    private function simulateStatusCheck($transaction)
    {
        // Simular diferentes cenários baseados no tempo
        $createdAt = strtotime($transaction['created_at']);
        $now = time();
        $diffMinutes = ($now - $createdAt) / 60;
        
        if ($diffMinutes > 30) {
            $status = rand(1, 10) <= 8 ? 'completed' : 'failed';
        } else if ($diffMinutes > 5) {
            $status = rand(1, 10) <= 6 ? 'completed' : 'processing';
        } else {
            $status = 'processing';
        }
        
        return [
            'success' => true,
            'status' => $status,
            'gateway_data' => [
                'checked_at' => date('Y-m-d H:i:s'),
                'simulated' => true
            ]
        ];
    }
    
    /**
     * Simular reembolso
     */
    private function simulateRefund($transaction, $amount, $reason)
    {
        $refundId = strtoupper($transaction['gateway_type']) . '_REFUND_' . uniqid();
        $newStatus = $amount < $transaction['amount'] ? 'partially_refunded' : 'refunded';
        
        // Atualizar transação
        $transactionModel = new \App\Models\PaymentTransaction();
        $transactionModel->update($transaction['id'], [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true,
            'refund_id' => $refundId,
            'amount' => $amount,
            'status' => $newStatus,
            'processed_at' => date('Y-m-d H:i:s'),
            'reason' => $reason
        ];
    }

    /**
     * Consulta status de um pagamento
     * GET /api/v1/payment-gateways/status/{transaction_id}
     */
    public function getPaymentStatus($transactionId = null)
    {
        if (!$this->hasPermission('payments.read')) {
            return $this->failForbidden('Sem permissão para consultar pagamentos');
        }

        try {
            $restaurantId = $this->getRestaurantId();
            
            $transactionModel = new \App\Models\PaymentTransaction();
            $transaction = $transactionModel->where('id', $transactionId)
                                          ->where('restaurant_id', $restaurantId)
                                          ->first();

            if (!$transaction) {
                return $this->failNotFound('Transação não encontrada');
            }

            // Consultar status real baseado no gateway
            $result = $this->checkPaymentStatusByGateway($transaction);
            
            if ($result['success'] && isset($result['status'])) {
                // Atualizar status se necessário
                if ($result['status'] !== $transaction['status']) {
                    $updateData = ['status' => $result['status']];
                    
                    if ($result['status'] === 'completed' && !$transaction['processed_at']) {
                        $updateData['processed_at'] = date('Y-m-d H:i:s');
                    }
                    
                    $transactionModel->update($transactionId, $updateData);
                    $transaction['status'] = $result['status'];
                }
            }

            return $this->respond([
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction['id'],
                    'gateway_transaction_id' => $transaction['gateway_transaction_id'],
                    'status' => $transaction['status'],
                    'amount' => $transaction['amount'],
                    'payment_method' => $transaction['payment_method'],
                    'created_at' => $transaction['created_at'],
                    'updated_at' => $transaction['updated_at'],
                    'gateway_data' => $result['gateway_data'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Erro ao consultar status: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }

    /**
     * Verificar status do pagamento baseado no gateway
     */
    private function checkPaymentStatusByGateway($transaction)
    {
        $gatewayType = $transaction['gateway_type'];
        $gatewayTransactionId = $transaction['transaction_id'];
        
        switch ($gatewayType) {
            case 'pix':
                $pixService = new \App\Services\PaymentServices\PixService();
                return $pixService->checkPixPayment($transaction['id']);
                
            case 'stripe':
                // Para Stripe, o status é atualizado via webhook
                return ['success' => true, 'status' => $transaction['status']];
                
            case 'mercadopago':
                $mercadoPagoService = new \App\Services\PaymentServices\MercadoPagoService();
                return $mercadoPagoService->getPaymentStatus($gatewayTransactionId);
                
            default:
                // Para outros gateways, simular verificação
                return $this->simulateStatusCheck($transaction);
        }
    }

    /**
     * Reembolsar pagamento
     * POST /api/v1/payment-gateways/refund/{transaction_id}
     */
    public function refundPayment($transactionId)
    {
        if (!$this->hasPermission('payments.refund')) {
            return $this->failForbidden('Sem permissão para reembolsar pagamentos');
        }

        try {
            $data = $this->request->getJSON(true);
            $restaurantId = $this->getRestaurantId();
            
            // Validar entrada
            $validation = \Config\Services::validation();
            $validation->setRules([
                'amount' => 'permit_empty|decimal|greater_than[0]',
                'reason' => 'permit_empty|string|max_length[255]'
            ]);
            
            if (!$validation->run($data)) {
                return $this->failValidationErrors($validation->getErrors());
            }
            
            // Buscar transação
            $transactionModel = new \App\Models\PaymentTransaction();
            $transaction = $transactionModel
                ->where('id', $transactionId)
                ->where('restaurant_id', $restaurantId)
                ->first();
            
            if (!$transaction) {
                return $this->failNotFound('Transação não encontrada');
            }
            
            // Verificar se pode ser reembolsada
            if (!in_array($transaction['status'], ['completed'])) {
                return $this->fail('Transação não pode ser reembolsada', 400);
            }
            
            $refundAmount = $data['amount'] ?? $transaction['amount'];
            $reason = $data['reason'] ?? 'requested_by_customer';
            
            // Validar valor do reembolso
            if ($refundAmount > $transaction['amount']) {
                return $this->fail('Valor do reembolso não pode ser maior que o valor da transação', 400);
            }
            
            // Processar reembolso baseado no gateway
            $result = $this->processRefundByGateway($transaction, $refundAmount, $reason);
            
            if (!$result['success']) {
                return $this->fail($result['error'], 400);
            }
            
            // Log da atividade
            $this->logActivity('payment_refunded', [
                'transaction_id' => $transactionId,
                'refund_amount' => $refundAmount,
                'reason' => $reason
            ]);
            
            return $this->respond([
                'success' => true,
                'message' => 'Reembolso processado com sucesso',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao processar reembolso: ' . $e->getMessage());
            return $this->fail('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Processar reembolso baseado no gateway
     */
    private function processRefundByGateway($transaction, $amount, $reason)
    {
        $gatewayType = $transaction['gateway_type'];
        $gatewayTransactionId = $transaction['transaction_id'];
        
        switch ($gatewayType) {
            case 'stripe':
                $stripeService = new \App\Services\PaymentServices\StripeService();
                return $stripeService->refundPayment($gatewayTransactionId, $amount, $reason);
                
            case 'mercadopago':
                $mercadoPagoService = new \App\Services\PaymentServices\MercadoPagoService();
                return $mercadoPagoService->refundPayment($gatewayTransactionId, $amount);
                
            case 'pix':
                // PIX não permite reembolso automático
                return [
                    'success' => false,
                    'error' => 'PIX não permite reembolso automático. Entre em contato com o cliente.'
                ];
                
            default:
                // Para outros gateways, simular reembolso
                return $this->simulateRefund($transaction, $amount, $reason);
        }
    }

    // Métodos auxiliares privados

    /**
     * Obter ID do restaurante do usuário autenticado
     */
    private function getRestaurantId()
    {
        return $this->request->restaurant_id ?? 1;
    }
    
    /**
     * Verificar permissão do usuário
     */
    private function hasPermission($permission)
    {
        $user = $this->request->user ?? null;
        return $user && in_array($permission, $user['permissions'] ?? []);
    }
    
    /**
     * Criptografar credenciais sensíveis
     */
    private function encryptCredentials($credentials)
    {
        $encrypter = \Config\Services::encrypter();
        $encrypted = [];
        
        foreach ($credentials as $key => $value) {
            $encrypted[$key] = $encrypter->encrypt($value);
        }
        
        return $encrypted;
    }
    
    /**
     * Descriptografar credenciais
     */
    private function decryptCredentials($credentials)
    {
        $encrypter = \Config\Services::encrypter();
        $decrypted = [];
        
        foreach ($credentials as $key => $value) {
            $decrypted[$key] = $encrypter->decrypt($value);
        }
        
        return $decrypted;
    }
    
    /**
     * Log de atividades
     */
    private function logActivity($action, $data = [])
    {
        $activityModel = new \App\Models\ActivityLog();
        $activityModel->insert([
            'restaurant_id' => $this->getRestaurantId(),
            'user_id' => $this->request->user['id'] ?? null,
            'action' => $action,
            'data' => json_encode($data),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Processar pagamento baseado no gateway
     */
    private function processPaymentByGateway($restaurantId, $input)
    {
        $gatewayType = $input['gateway_type'];
        $amount = $input['amount'];
        $orderId = $input['order_id'] ?? null;
        $description = $input['description'] ?? 'Pagamento Restaurante';
        $paymentData = isset($input['payment_data']) ? json_decode($input['payment_data'], true) : [];
        
        switch ($gatewayType) {
            case 'pix':
                $pixService = new \App\Services\PaymentServices\PixService();
                return $pixService->generatePixQRCode($restaurantId, $amount, $description, $orderId);
                
            case 'stripe':
                $stripeService = new \App\Services\PaymentServices\StripeService();
                $currency = $input['currency'] ?? 'brl';
                $metadata = $paymentData['metadata'] ?? [];
                return $stripeService->createPaymentIntent($restaurantId, $amount, $currency, $orderId, $metadata);
                
            case 'mercadopago':
                $mercadoPagoService = new \App\Services\PaymentServices\MercadoPagoService();
                
                // Se tem dados de cartão, processar pagamento direto
                if (isset($paymentData['token'])) {
                    $cardData = array_merge($paymentData, [
                        'amount' => $amount,
                        'description' => $description
                    ]);
                    return $mercadoPagoService->processCardPayment($restaurantId, $cardData, $orderId);
                } else {
                    // Criar preferência de pagamento
                    $items = [[
                        'title' => $description,
                        'quantity' => 1,
                        'unit_price' => $amount
                    ]];
                    return $mercadoPagoService->createPaymentPreference($restaurantId, $items, $orderId);
                }
                
            case 'pagseguro':
            case 'paypal':
                // Por enquanto, simular estes gateways
                return $this->simulatePaymentProcessing($gatewayType, $amount, $orderId, $description);
                
            default:
                return [
                    'success' => false,
                    'error' => 'Gateway não suportado: ' . $gatewayType
                ];
        }
    }
    
    /**
     * Simular processamento de pagamento (para gateways não implementados)
     */
    private function simulatePaymentProcessing($gatewayType, $amount, $orderId, $description)
    {
        $transactionId = strtoupper($gatewayType) . '_' . uniqid() . '_' . time();
        
        switch ($gatewayType) {
            case 'pagseguro':
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'status' => rand(1, 10) <= 6 ? 'completed' : 'processing',
                    'payment_method' => 'pagseguro',
                    'payment_url' => 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=' . $transactionId,
                    'amount' => $amount
                ];
                
            case 'paypal':
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'status' => rand(1, 10) <= 9 ? 'completed' : 'failed',
                    'payment_method' => 'paypal',
                    'payment_url' => 'https://www.paypal.com/checkoutnow?token=' . $transactionId,
                    'amount' => $amount
                ];
                
            default:
                return [
                    'success' => false,
                    'error' => 'Gateway não implementado: ' . $gatewayType
                ];
        }
    }

    private function processPixPayment($credentials, $amount, $orderId, $customerData)
    {
        // Implementação específica para PIX
        // Aqui você integraria com a API do seu provedor PIX
        
        $pixData = [
            'amount' => $amount,
            'description' => "Pedido #{$orderId}",
            'payer' => $customerData,
            'expiration' => date('Y-m-d\TH:i:s\Z', strtotime('+30 minutes'))
        ];

        // Simular resposta da API PIX
        return [
            'success' => true,
            'transaction_id' => 'PIX_' . uniqid(),
            'status' => 'pending',
            'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'payment_url' => null,
            'response' => $pixData
        ];
    }

    private function processStripePayment($credentials, $paymentMethod, $amount, $customerData)
    {
        // Implementação específica para Stripe
        return [
            'success' => true,
            'transaction_id' => 'STRIPE_' . uniqid(),
            'status' => 'processing',
            'payment_url' => 'https://checkout.stripe.com/pay/example',
            'response' => ['stripe_payment_intent' => 'pi_example']
        ];
    }

    private function processPagSeguroPayment($credentials, $paymentMethod, $amount, $customerData)
    {
        // Implementação específica para PagSeguro
        return [
            'success' => true,
            'transaction_id' => 'PAGSEGURO_' . uniqid(),
            'status' => 'pending',
            'payment_url' => 'https://pagseguro.uol.com.br/checkout/example',
            'response' => ['pagseguro_code' => 'PS_' . uniqid()]
        ];
    }

    private function processMercadoPagoPayment($credentials, $paymentMethod, $amount, $customerData)
    {
        // Implementação específica para Mercado Pago
        return [
            'success' => true,
            'transaction_id' => 'MP_' . uniqid(),
            'status' => 'pending',
            'payment_url' => 'https://mercadopago.com.br/checkout/example',
            'response' => ['mp_preference_id' => 'MP_PREF_' . uniqid()]
        ];
    }

    private function processPayPalPayment($credentials, $amount, $customerData)
    {
        // Implementação específica para PayPal
        return [
            'success' => true,
            'transaction_id' => 'PAYPAL_' . uniqid(),
            'status' => 'pending',
            'payment_url' => 'https://paypal.com/checkout/example',
            'response' => ['paypal_order_id' => 'PAYPAL_ORDER_' . uniqid()]
        ];
    }

    private function verifyWebhookSignature($gatewayType, $payload, $headers)
    {
        // Implementar verificação de assinatura específica para cada gateway
        return true; // Simplificado para exemplo
    }

    private function processWebhook($gatewayType, $data)
    {
        // Processar webhook específico para cada gateway
        return ['success' => true];
    }

    private function checkPaymentStatusInGateway($gateway, $transactionId)
    {
        // Consultar status no gateway específico
        return ['status' => 'completed']; // Simplificado
    }


}