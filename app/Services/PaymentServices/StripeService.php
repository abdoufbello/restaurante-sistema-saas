<?php

namespace App\Services\PaymentServices;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use CodeIgniter\HTTP\CURLRequest;
use Exception;

class StripeService
{
    protected $gatewayModel;
    protected $transactionModel;
    protected $webhookModel;
    protected $client;
    protected $apiUrl = 'https://api.stripe.com/v1';
    
    public function __construct()
    {
        $this->gatewayModel = new PaymentGateway();
        $this->transactionModel = new PaymentTransaction();
        $this->webhookModel = new PaymentWebhook();
        $this->client = \Config\Services::curlrequest();
    }

    /**
     * Criar Payment Intent
     */
    public function createPaymentIntent($restaurantId, $amount, $currency = 'brl', $orderId = null, $metadata = [])
    {
        try {
            // Buscar configuração Stripe do restaurante
            $gateway = $this->gatewayModel->getActiveByType($restaurantId, 'stripe');
            
            if (!$gateway) {
                throw new Exception('Gateway Stripe não configurado para este restaurante');
            }

            $credentials = json_decode($gateway['credentials'], true);
            $secretKey = $credentials['secret_key'];
            
            // Preparar dados para o Stripe
            $data = [
                'amount' => intval($amount * 100), // Stripe usa centavos
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => array_merge($metadata, [
                    'restaurant_id' => $restaurantId,
                    'order_id' => $orderId,
                    'platform' => 'restaurant_saas'
                ])
            ];
            
            // Fazer requisição para Stripe
            $response = $this->makeStripeRequest('POST', '/payment_intents', $data, $secretKey);
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            $paymentIntent = $response['data'];
            
            // Criar transação local
            $transactionData = [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'gateway_id' => $gateway['id'],
                'gateway_type' => 'stripe',
                'transaction_id' => $paymentIntent['id'],
                'external_id' => $paymentIntent['client_secret'],
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'status' => $this->mapStripeStatus($paymentIntent['status']),
                'payment_method' => 'card',
                'gateway_response' => json_encode($paymentIntent)
            ];
            
            $transactionId = $this->transactionModel->insert($transactionData);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_intent_id' => $paymentIntent['id'],
                'client_secret' => $paymentIntent['client_secret'],
                'amount' => $amount,
                'currency' => $currency,
                'status' => $paymentIntent['status']
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao criar Payment Intent Stripe: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Confirmar pagamento
     */
    public function confirmPayment($paymentIntentId, $paymentMethodId)
    {
        try {
            // Buscar transação local
            $transaction = $this->transactionModel->where('transaction_id', $paymentIntentId)->first();
            
            if (!$transaction) {
                throw new Exception('Transação não encontrada');
            }
            
            // Buscar gateway
            $gateway = $this->gatewayModel->find($transaction['gateway_id']);
            $credentials = json_decode($gateway['credentials'], true);
            $secretKey = $credentials['secret_key'];
            
            // Confirmar pagamento no Stripe
            $data = [
                'payment_method' => $paymentMethodId,
                'return_url' => base_url('payment/stripe/return')
            ];
            
            $response = $this->makeStripeRequest(
                'POST',
                '/payment_intents/' . $paymentIntentId . '/confirm',
                $data,
                $secretKey
            );
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            $paymentIntent = $response['data'];
            
            // Atualizar transação local
            $updateData = [
                'status' => $this->mapStripeStatus($paymentIntent['status']),
                'gateway_response' => json_encode($paymentIntent)
            ];
            
            if ($paymentIntent['status'] === 'succeeded') {
                $updateData['processed_at'] = date('Y-m-d H:i:s');
                
                // Calcular taxas
                if (isset($paymentIntent['charges']['data'][0])) {
                    $charge = $paymentIntent['charges']['data'][0];
                    $updateData['fees'] = ($charge['application_fee_amount'] ?? 0) / 100;
                    $updateData['net_amount'] = $transaction['amount'] - $updateData['fees'];
                }
            }
            
            $this->transactionModel->update($transaction['id'], $updateData);
            
            return [
                'success' => true,
                'status' => $paymentIntent['status'],
                'payment_intent' => $paymentIntent
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao confirmar pagamento Stripe: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Capturar pagamento
     */
    public function capturePayment($paymentIntentId, $amount = null)
    {
        try {
            // Buscar transação local
            $transaction = $this->transactionModel->where('transaction_id', $paymentIntentId)->first();
            
            if (!$transaction) {
                throw new Exception('Transação não encontrada');
            }
            
            // Buscar gateway
            $gateway = $this->gatewayModel->find($transaction['gateway_id']);
            $credentials = json_decode($gateway['credentials'], true);
            $secretKey = $credentials['secret_key'];
            
            $data = [];
            if ($amount) {
                $data['amount_to_capture'] = intval($amount * 100);
            }
            
            $response = $this->makeStripeRequest(
                'POST',
                '/payment_intents/' . $paymentIntentId . '/capture',
                $data,
                $secretKey
            );
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            $paymentIntent = $response['data'];
            
            // Atualizar transação
            $this->transactionModel->update($transaction['id'], [
                'status' => $this->mapStripeStatus($paymentIntent['status']),
                'processed_at' => date('Y-m-d H:i:s'),
                'gateway_response' => json_encode($paymentIntent)
            ]);
            
            return [
                'success' => true,
                'status' => $paymentIntent['status'],
                'captured_amount' => ($paymentIntent['amount_received'] ?? 0) / 100
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao capturar pagamento Stripe: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reembolsar pagamento
     */
    public function refundPayment($paymentIntentId, $amount = null, $reason = 'requested_by_customer')
    {
        try {
            // Buscar transação local
            $transaction = $this->transactionModel->where('transaction_id', $paymentIntentId)->first();
            
            if (!$transaction) {
                throw new Exception('Transação não encontrada');
            }
            
            // Buscar gateway
            $gateway = $this->gatewayModel->find($transaction['gateway_id']);
            $credentials = json_decode($gateway['credentials'], true);
            $secretKey = $credentials['secret_key'];
            
            // Buscar charge ID
            $gatewayResponse = json_decode($transaction['gateway_response'], true);
            $chargeId = $gatewayResponse['charges']['data'][0]['id'] ?? null;
            
            if (!$chargeId) {
                throw new Exception('ID da cobrança não encontrado');
            }
            
            $data = [
                'charge' => $chargeId,
                'reason' => $reason
            ];
            
            if ($amount) {
                $data['amount'] = intval($amount * 100);
            }
            
            $response = $this->makeStripeRequest('POST', '/refunds', $data, $secretKey);
            
            if (!$response['success']) {
                throw new Exception($response['error']);
            }
            
            $refund = $response['data'];
            
            // Atualizar status da transação
            $newStatus = $amount && $amount < $transaction['amount'] ? 'partially_refunded' : 'refunded';
            
            $this->transactionModel->update($transaction['id'], [
                'status' => $newStatus,
                'gateway_response' => json_encode(array_merge($gatewayResponse, [
                    'refund' => $refund
                ]))
            ]);
            
            return [
                'success' => true,
                'refund_id' => $refund['id'],
                'amount' => $refund['amount'] / 100,
                'status' => $refund['status']
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao reembolsar pagamento Stripe: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processar webhook Stripe
     */
    public function processWebhook($restaurantId, $payload, $signature, $endpointSecret)
    {
        try {
            // Verificar assinatura do webhook
            if (!$this->verifyWebhookSignature($payload, $signature, $endpointSecret)) {
                throw new Exception('Assinatura do webhook inválida');
            }
            
            $event = json_decode($payload, true);
            
            // Salvar webhook
            $webhookData = [
                'restaurant_id' => $restaurantId,
                'gateway_type' => 'stripe',
                'event_type' => $event['type'],
                'payload' => $payload,
                'transaction_id' => $event['data']['object']['id'] ?? null
            ];
            
            $webhookId = $this->webhookModel->insert($webhookData);
            
            // Processar evento
            $result = $this->processStripeEvent($event);
            
            // Marcar webhook como processado
            $this->webhookModel->markAsProcessed(
                $webhookId,
                $result['success'] ? null : $result['error']
            );
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao processar webhook Stripe: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fazer requisição para API Stripe
     */
    private function makeStripeRequest($method, $endpoint, $data = [], $secretKey = null)
    {
        try {
            $url = $this->apiUrl . $endpoint;
            
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'timeout' => 30
            ];
            
            if ($method === 'POST' && !empty($data)) {
                $options['form_params'] = $data;
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
                return [
                    'success' => false,
                    'error' => $responseData['error']['message'] ?? 'Erro desconhecido'
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
     * Mapear status do Stripe para status local
     */
    private function mapStripeStatus($stripeStatus)
    {
        $statusMap = [
            'requires_payment_method' => 'pending',
            'requires_confirmation' => 'pending',
            'requires_action' => 'processing',
            'processing' => 'processing',
            'requires_capture' => 'processing',
            'succeeded' => 'completed',
            'canceled' => 'cancelled'
        ];
        
        return $statusMap[$stripeStatus] ?? 'pending';
    }

    /**
     * Verificar assinatura do webhook
     */
    private function verifyWebhookSignature($payload, $signature, $endpointSecret)
    {
        $elements = explode(',', $signature);
        $signatureHash = '';
        $timestamp = '';
        
        foreach ($elements as $element) {
            list($key, $value) = explode('=', $element, 2);
            if ($key === 'v1') {
                $signatureHash = $value;
            } elseif ($key === 't') {
                $timestamp = $value;
            }
        }
        
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $endpointSecret);
        
        return hash_equals($expectedSignature, $signatureHash);
    }

    /**
     * Processar evento Stripe
     */
    private function processStripeEvent($event)
    {
        try {
            $eventType = $event['type'];
            $object = $event['data']['object'];
            
            switch ($eventType) {
                case 'payment_intent.succeeded':
                    return $this->handlePaymentSucceeded($object);
                    
                case 'payment_intent.payment_failed':
                    return $this->handlePaymentFailed($object);
                    
                case 'charge.dispute.created':
                    return $this->handleChargeDispute($object);
                    
                default:
                    log_message('info', 'Evento Stripe não processado: ' . $eventType);
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
     * Processar pagamento bem-sucedido
     */
    private function handlePaymentSucceeded($paymentIntent)
    {
        $transaction = $this->transactionModel->where('transaction_id', $paymentIntent['id'])->first();
        
        if ($transaction) {
            $this->transactionModel->update($transaction['id'], [
                'status' => 'completed',
                'processed_at' => date('Y-m-d H:i:s'),
                'gateway_response' => json_encode($paymentIntent)
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Pagamento confirmado'
        ];
    }

    /**
     * Processar pagamento falhado
     */
    private function handlePaymentFailed($paymentIntent)
    {
        $transaction = $this->transactionModel->where('transaction_id', $paymentIntent['id'])->first();
        
        if ($transaction) {
            $this->transactionModel->update($transaction['id'], [
                'status' => 'failed',
                'gateway_response' => json_encode($paymentIntent)
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Pagamento falhou'
        ];
    }

    /**
     * Processar disputa de cobrança
     */
    private function handleChargeDispute($dispute)
    {
        // Implementar lógica de disputa
        log_message('warning', 'Disputa criada: ' . json_encode($dispute));
        
        return [
            'success' => true,
            'message' => 'Disputa registrada'
        ];
    }
}