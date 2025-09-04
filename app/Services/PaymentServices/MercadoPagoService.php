<?php

namespace App\Services\PaymentServices;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use CodeIgniter\HTTP\CURLRequest;
use Exception;

class MercadoPagoService
{
    protected $gatewayModel;
    protected $transactionModel;
    protected $webhookModel;
    protected $client;
    protected $apiUrl = 'https://api.mercadopago.com';
    
    public function __construct()
    {
        $this->gatewayModel = new PaymentGateway();
        $this->transactionModel = new PaymentTransaction();
        $this->webhookModel = new PaymentWebhook();
        $this->client = \Config\Services::curlrequest();
    }

    /**
     * Criar preferência de pagamento
     */
    public function createPaymentPreference($restaurantId, $items, $orderId = null, $metadata = [])
    {
        try {
            // Buscar configuração Mercado Pago do restaurante
            $gateway = $this->gatewayModel->getActiveByType($restaurantId, 'mercadopago');
            
            if (!$gateway) {
                throw new Exception('Gateway Mercado Pago não configurado para este restaurante');
            }

            $credentials = json_decode($gateway['credentials'], true);
            $accessToken = $credentials['access_token'];
            
            // Calcular total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['unit_price'] * $item['quantity'];
            }
            
            // Preparar dados da preferência
            $preferenceData = [
                'items' => $items,
                'payer' => [
                    'email' => 'customer@restaurant.com' // Em produção, usar email real
                ],
                'back_urls' => [
                    'success' => base_url('payment/mercadopago/success'),
                    'failure' => base_url('payment/mercadopago/failure'),
                    'pending' => base_url('payment/mercadopago/pending')
                ],
                'auto_return' => 'approved',
                'external_reference' => $orderId ? "ORDER_{$orderId}" : "PAYMENT_" . time(),
                'notification_url' => base_url('api/payment-gateways/webhook/mercadopago'),
                'metadata' => array_merge($metadata, [
                    'restaurant_id' => $restaurantId,
                    'order_id' => $orderId,
                    'platform' => 'restaurant_saas'
                ])
            ];
            
            // Fazer requisição para Mercado Pago
            $response = $this->makeMercadoPagoRequest(
                'POST',
                '/checkout/preferences',
                $preferenceData,
                $accessToken
            );
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            $preference = $response['data'];
            
            // Criar transação local
            $transactionData = [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'gateway_id' => $gateway['id'],
                'gateway_type' => 'mercadopago',
                'transaction_id' => $preference['id'],
                'external_id' => $preference['external_reference'],
                'amount' => $totalAmount,
                'currency' => 'BRL',
                'status' => 'pending',
                'payment_method' => 'mercadopago',
                'gateway_response' => json_encode($preference)
            ];
            
            $transactionId = $this->transactionModel->insert($transactionData);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'preference_id' => $preference['id'],
                'init_point' => $preference['init_point'],
                'sandbox_init_point' => $preference['sandbox_init_point'],
                'amount' => $totalAmount,
                'external_reference' => $preference['external_reference']
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao criar preferência Mercado Pago: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processar pagamento direto (cartão)
     */
    public function processCardPayment($restaurantId, $paymentData, $orderId = null)
    {
        try {
            // Buscar configuração Mercado Pago do restaurante
            $gateway = $this->gatewayModel->getActiveByType($restaurantId, 'mercadopago');
            
            if (!$gateway) {
                throw new Exception('Gateway Mercado Pago não configurado para este restaurante');
            }

            $credentials = json_decode($gateway['credentials'], true);
            $accessToken = $credentials['access_token'];
            
            // Preparar dados do pagamento
            $payment = [
                'transaction_amount' => $paymentData['amount'],
                'token' => $paymentData['token'],
                'description' => $paymentData['description'] ?? 'Pagamento Restaurante',
                'installments' => $paymentData['installments'] ?? 1,
                'payment_method_id' => $paymentData['payment_method_id'],
                'issuer_id' => $paymentData['issuer_id'] ?? null,
                'payer' => [
                    'email' => $paymentData['payer_email'] ?? 'customer@restaurant.com'
                ],
                'external_reference' => $orderId ? "ORDER_{$orderId}" : "PAYMENT_" . time(),
                'notification_url' => base_url('api/payment-gateways/webhook/mercadopago'),
                'metadata' => [
                    'restaurant_id' => $restaurantId,
                    'order_id' => $orderId,
                    'platform' => 'restaurant_saas'
                ]
            ];
            
            // Fazer requisição para Mercado Pago
            $response = $this->makeMercadoPagoRequest(
                'POST',
                '/v1/payments',
                $payment,
                $accessToken
            );
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            $paymentResult = $response['data'];
            
            // Criar transação local
            $transactionData = [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'gateway_id' => $gateway['id'],
                'gateway_type' => 'mercadopago',
                'transaction_id' => $paymentResult['id'],
                'external_id' => $paymentResult['external_reference'],
                'amount' => $paymentResult['transaction_amount'],
                'currency' => 'BRL',
                'status' => $this->mapMercadoPagoStatus($paymentResult['status']),
                'payment_method' => $paymentResult['payment_method_id'],
                'gateway_response' => json_encode($paymentResult)
            ];
            
            if ($paymentResult['status'] === 'approved') {
                $transactionData['processed_at'] = date('Y-m-d H:i:s');
                $transactionData['fees'] = $paymentResult['fee_details'][0]['amount'] ?? 0;
                $transactionData['net_amount'] = $paymentResult['transaction_amount'] - $transactionData['fees'];
            }
            
            $transactionId = $this->transactionModel->insert($transactionData);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_id' => $paymentResult['id'],
                'status' => $paymentResult['status'],
                'status_detail' => $paymentResult['status_detail'],
                'amount' => $paymentResult['transaction_amount']
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao processar pagamento Mercado Pago: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Consultar status do pagamento
     */
    public function getPaymentStatus($paymentId)
    {
        try {
            // Buscar transação local
            $transaction = $this->transactionModel->where('transaction_id', $paymentId)->first();
            
            if (!$transaction) {
                throw new Exception('Transação não encontrada');
            }
            
            // Buscar gateway
            $gateway = $this->gatewayModel->find($transaction['gateway_id']);
            $credentials = json_decode($gateway['credentials'], true);
            $accessToken = $credentials['access_token'];
            
            // Consultar Mercado Pago
            $response = $this->makeMercadoPagoRequest(
                'GET',
                '/v1/payments/' . $paymentId,
                [],
                $accessToken
            );
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            $payment = $response['data'];
            
            // Atualizar transação local se necessário
            $newStatus = $this->mapMercadoPagoStatus($payment['status']);
            if ($newStatus !== $transaction['status']) {
                $updateData = [
                    'status' => $newStatus,
                    'gateway_response' => json_encode($payment)
                ];
                
                if ($payment['status'] === 'approved' && !$transaction['processed_at']) {
                    $updateData['processed_at'] = date('Y-m-d H:i:s');
                    $updateData['fees'] = $payment['fee_details'][0]['amount'] ?? 0;
                    $updateData['net_amount'] = $payment['transaction_amount'] - $updateData['fees'];
                }
                
                $this->transactionModel->update($transaction['id'], $updateData);
            }
            
            return [
                'success' => true,
                'payment_id' => $payment['id'],
                'status' => $payment['status'],
                'status_detail' => $payment['status_detail'],
                'amount' => $payment['transaction_amount'],
                'net_amount' => $payment['transaction_amount'] - ($payment['fee_details'][0]['amount'] ?? 0)
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao consultar status Mercado Pago: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reembolsar pagamento
     */
    public function refundPayment($paymentId, $amount = null)
    {
        try {
            // Buscar transação local
            $transaction = $this->transactionModel->where('transaction_id', $paymentId)->first();
            
            if (!$transaction) {
                throw new Exception('Transação não encontrada');
            }
            
            // Buscar gateway
            $gateway = $this->gatewayModel->find($transaction['gateway_id']);
            $credentials = json_decode($gateway['credentials'], true);
            $accessToken = $credentials['access_token'];
            
            $refundData = [];
            if ($amount) {
                $refundData['amount'] = $amount;
            }
            
            // Fazer reembolso no Mercado Pago
            $response = $this->makeMercadoPagoRequest(
                'POST',
                '/v1/payments/' . $paymentId . '/refunds',
                $refundData,
                $accessToken
            );
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            $refund = $response['data'];
            
            // Atualizar status da transação
            $newStatus = $amount && $amount < $transaction['amount'] ? 'partially_refunded' : 'refunded';
            
            $gatewayResponse = json_decode($transaction['gateway_response'], true);
            $this->transactionModel->update($transaction['id'], [
                'status' => $newStatus,
                'gateway_response' => json_encode(array_merge($gatewayResponse, [
                    'refund' => $refund
                ]))
            ]);
            
            return [
                'success' => true,
                'refund_id' => $refund['id'],
                'amount' => $refund['amount'],
                'status' => $refund['status']
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao reembolsar pagamento Mercado Pago: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processar webhook Mercado Pago
     */
    public function processWebhook($restaurantId, $payload, $headers = [])
    {
        try {
            $data = json_decode($payload, true);
            
            // Salvar webhook
            $webhookData = [
                'restaurant_id' => $restaurantId,
                'gateway_type' => 'mercadopago',
                'event_type' => $data['type'] ?? 'unknown',
                'payload' => $payload,
                'headers' => json_encode($headers),
                'transaction_id' => $data['data']['id'] ?? null
            ];
            
            $webhookId = $this->webhookModel->insert($webhookData);
            
            // Processar evento
            $result = $this->processMercadoPagoEvent($data);
            
            // Marcar webhook como processado
            $this->webhookModel->markAsProcessed(
                $webhookId,
                $result['success'] ? null : $result['error']
            );
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao processar webhook Mercado Pago: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fazer requisição para API Mercado Pago
     */
    private function makeMercadoPagoRequest($method, $endpoint, $data = [], $accessToken = null)
    {
        try {
            $url = $this->apiUrl . $endpoint;
            
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'X-Idempotency-Key' => uniqid()
                ],
                'timeout' => 30
            ];
            
            if ($method === 'POST' && !empty($data)) {
                $options['json'] = $data;
            }
            
            $response = $this->client->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            
            $responseData = json_decode($body, true);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                $errorMessage = 'Erro desconhecido';
                if (isset($responseData['message'])) {
                    $errorMessage = $responseData['message'];
                } elseif (isset($responseData['cause'])) {
                    $errorMessage = $responseData['cause'][0]['description'] ?? $errorMessage;
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mapear status do Mercado Pago para status local
     */
    private function mapMercadoPagoStatus($mpStatus)
    {
        $statusMap = [
            'pending' => 'pending',
            'approved' => 'completed',
            'authorized' => 'processing',
            'in_process' => 'processing',
            'in_mediation' => 'processing',
            'rejected' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'charged_back' => 'refunded'
        ];
        
        return $statusMap[$mpStatus] ?? 'pending';
    }

    /**
     * Processar evento Mercado Pago
     */
    private function processMercadoPagoEvent($data)
    {
        try {
            $eventType = $data['type'] ?? 'unknown';
            
            switch ($eventType) {
                case 'payment':
                    return $this->handlePaymentEvent($data['data']['id']);
                    
                case 'plan':
                case 'subscription':
                case 'invoice':
                    // Eventos de assinatura - implementar se necessário
                    return ['success' => true, 'message' => 'Evento de assinatura ignorado'];
                    
                default:
                    log_message('info', 'Evento Mercado Pago não processado: ' . $eventType);
                    return ['success' => true, 'message' => 'Evento ignorado'];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processar evento de pagamento
     */
    private function handlePaymentEvent($paymentId)
    {
        try {
            // Consultar status atual do pagamento
            $result = $this->getPaymentStatus($paymentId);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Status do pagamento atualizado'
                ];
            } else {
                throw new Exception($result['error']);
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obter métodos de pagamento disponíveis
     */
    public function getPaymentMethods($restaurantId)
    {
        try {
            // Buscar configuração Mercado Pago do restaurante
            $gateway = $this->gatewayModel->getActiveByType($restaurantId, 'mercadopago');
            
            if (!$gateway) {
                throw new Exception('Gateway Mercado Pago não configurado para este restaurante');
            }

            $credentials = json_decode($gateway['credentials'], true);
            $accessToken = $credentials['access_token'];
            
            // Consultar métodos de pagamento
            $response = $this->makeMercadoPagoRequest(
                'GET',
                '/v1/payment_methods',
                [],
                $accessToken
            );
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            return [
                'success' => true,
                'payment_methods' => $response['data']
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao obter métodos de pagamento Mercado Pago: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}