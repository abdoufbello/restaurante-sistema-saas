<?php

namespace App\Services;

use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use CodeIgniter\HTTP\CURLRequest;

class PaymentGatewayService
{
    protected $config;
    protected $subscriptionModel;
    protected $planModel;
    protected $client;
    
    public function __construct()
    {
        $this->config = config('PaymentGateway');
        $this->subscriptionModel = new SubscriptionModel();
        $this->planModel = new PlanModel();
        $this->client = \Config\Services::curlrequest();
    }
    
    /**
     * Create subscription with payment gateway
     */
    public function createSubscription($restaurantId, $planId, $paymentData)
    {
        $plan = $this->planModel->find($planId);
        if (!$plan) {
            throw new \Exception('Plano não encontrado');
        }
        
        $gateway = $this->config->defaultGateway;
        
        switch ($gateway) {
            case 'pagseguro':
                return $this->createPagSeguroSubscription($restaurantId, $plan, $paymentData);
            case 'mercadopago':
                return $this->createMercadoPagoSubscription($restaurantId, $plan, $paymentData);
            default:
                throw new \Exception('Gateway de pagamento não suportado');
        }
    }
    
    /**
     * Create PagSeguro subscription
     */
    protected function createPagSeguroSubscription($restaurantId, $plan, $paymentData)
    {
        $config = $this->config->pagseguro;
        
        // Create subscription plan in PagSeguro
        $planData = [
            'reference' => 'plan_' . $plan['slug'],
            'preApproval' => [
                'name' => $plan['name'],
                'charge' => 'auto',
                'period' => 'monthly',
                'amountPerPayment' => number_format($plan['price'], 2, '.', ''),
                'maxAmountPerPayment' => number_format($plan['price'], 2, '.', ''),
                'details' => 'Assinatura do plano ' . $plan['name'],
                'maxPaymentsPerPeriod' => 1,
                'maxAmountPerPeriod' => number_format($plan['price'], 2, '.', ''),
                'initialDate' => date('Y-m-d\TH:i:s'),
                'finalDate' => date('Y-m-d\TH:i:s', strtotime('+1 year')),
                'membershipFee' => '0.00',
                'trialPeriodDuration' => 0
            ],
            'reviewURL' => base_url('subscription/review'),
            'maxUses' => 1
        ];
        
        $response = $this->client->post($config['api_url'] . '/pre-approvals/request', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.pagseguro.com.br.v3+json;charset=ISO-8859-1'
            ],
            'body' => json_encode($planData),
            'query' => [
                'email' => $config['email'],
                'token' => $config['token']
            ]
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Erro ao criar assinatura no PagSeguro: ' . ($result['message'] ?? 'Erro desconhecido'));
        }
        
        // Create subscription record
        $subscriptionData = [
            'restaurant_id' => $restaurantId,
            'plan_id' => $plan['id'],
            'status' => 'pending',
            'gateway' => 'pagseguro',
            'gateway_subscription_id' => $result['code'],
            'starts_at' => date('Y-m-d H:i:s'),
            'next_payment_date' => date('Y-m-d H:i:s', strtotime('+1 month')),
            'payment_method' => $paymentData['method'] ?? 'credit_card',
            'metadata' => json_encode([
                'gateway_response' => $result,
                'payment_data' => $paymentData
            ])
        ];
        
        $subscriptionId = $this->subscriptionModel->insert($subscriptionData);
        
        return [
            'success' => true,
            'subscription_id' => $subscriptionId,
            'gateway_url' => $result['redirectURL'] ?? null,
            'payment_code' => $result['code']
        ];
    }
    
    /**
     * Create Mercado Pago subscription
     */
    protected function createMercadoPagoSubscription($restaurantId, $plan, $paymentData)
    {
        $config = $this->config->mercadopago;
        
        // Create subscription plan
        $planData = [
            'reason' => $plan['name'],
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => (float) $plan['price'],
                'currency_id' => 'BRL'
            ],
            'payment_methods_allowed' => [
                'payment_types' => [
                    ['id' => 'credit_card'],
                    ['id' => 'debit_card']
                ],
                'payment_methods' => [
                    ['id' => 'visa'],
                    ['id' => 'master'],
                    ['id' => 'elo']
                ]
            ],
            'back_url' => base_url('subscription/callback'),
            'external_reference' => 'restaurant_' . $restaurantId . '_plan_' . $plan['id']
        ];
        
        $response = $this->client->post($config['api_url'] . '/preapproval', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $config['access_token']
            ],
            'body' => json_encode($planData)
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() !== 201) {
            throw new \Exception('Erro ao criar assinatura no Mercado Pago: ' . ($result['message'] ?? 'Erro desconhecido'));
        }
        
        // Create subscription record
        $subscriptionData = [
            'restaurant_id' => $restaurantId,
            'plan_id' => $plan['id'],
            'status' => 'pending',
            'gateway' => 'mercadopago',
            'gateway_subscription_id' => $result['id'],
            'starts_at' => date('Y-m-d H:i:s'),
            'next_payment_date' => date('Y-m-d H:i:s', strtotime('+1 month')),
            'payment_method' => $paymentData['method'] ?? 'credit_card',
            'metadata' => json_encode([
                'gateway_response' => $result,
                'payment_data' => $paymentData
            ])
        ];
        
        $subscriptionId = $this->subscriptionModel->insert($subscriptionData);
        
        return [
            'success' => true,
            'subscription_id' => $subscriptionId,
            'gateway_url' => $result['init_point'] ?? null,
            'payment_id' => $result['id']
        ];
    }
    
    /**
     * Handle webhook from payment gateway
     */
    public function handleWebhook($gateway, $data)
    {
        switch ($gateway) {
            case 'pagseguro':
                return $this->handlePagSeguroWebhook($data);
            case 'mercadopago':
                return $this->handleMercadoPagoWebhook($data);
            default:
                throw new \Exception('Gateway não suportado');
        }
    }
    
    /**
     * Handle PagSeguro webhook
     */
    protected function handlePagSeguroWebhook($data)
    {
        $config = $this->config->pagseguro;
        
        // Get notification details
        $notificationCode = $data['notificationCode'] ?? null;
        if (!$notificationCode) {
            throw new \Exception('Código de notificação não fornecido');
        }
        
        $response = $this->client->get($config['api_url'] . '/pre-approvals/notifications/' . $notificationCode, [
            'query' => [
                'email' => $config['email'],
                'token' => $config['token']
            ]
        ]);
        
        $notification = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Erro ao obter detalhes da notificação');
        }
        
        // Find subscription
        $subscription = $this->subscriptionModel->where('gateway_subscription_id', $notification['code'])->first();
        if (!$subscription) {
            throw new \Exception('Assinatura não encontrada');
        }
        
        // Update subscription status
        $status = $this->mapPagSeguroStatus($notification['status']);
        $updateData = [
            'status' => $status,
            'last_payment_date' => date('Y-m-d H:i:s'),
            'metadata' => json_encode(array_merge(
                json_decode($subscription['metadata'], true) ?? [],
                ['last_webhook' => $notification]
            ))
        ];
        
        if ($status === 'active') {
            $updateData['next_payment_date'] = date('Y-m-d H:i:s', strtotime('+1 month'));
        }
        
        $this->subscriptionModel->update($subscription['id'], $updateData);
        
        return ['success' => true, 'status' => $status];
    }
    
    /**
     * Handle Mercado Pago webhook
     */
    protected function handleMercadoPagoWebhook($data)
    {
        $config = $this->config->mercadopago;
        
        $subscriptionId = $data['data']['id'] ?? null;
        if (!$subscriptionId) {
            throw new \Exception('ID da assinatura não fornecido');
        }
        
        // Get subscription details
        $response = $this->client->get($config['api_url'] . '/preapproval/' . $subscriptionId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $config['access_token']
            ]
        ]);
        
        $subscriptionData = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Erro ao obter detalhes da assinatura');
        }
        
        // Find local subscription
        $subscription = $this->subscriptionModel->where('gateway_subscription_id', $subscriptionId)->first();
        if (!$subscription) {
            throw new \Exception('Assinatura não encontrada');
        }
        
        // Update subscription status
        $status = $this->mapMercadoPagoStatus($subscriptionData['status']);
        $updateData = [
            'status' => $status,
            'last_payment_date' => date('Y-m-d H:i:s'),
            'metadata' => json_encode(array_merge(
                json_decode($subscription['metadata'], true) ?? [],
                ['last_webhook' => $subscriptionData]
            ))
        ];
        
        if ($status === 'active') {
            $updateData['next_payment_date'] = date('Y-m-d H:i:s', strtotime('+1 month'));
        }
        
        $this->subscriptionModel->update($subscription['id'], $updateData);
        
        return ['success' => true, 'status' => $status];
    }
    
    /**
     * Cancel subscription
     */
    public function cancelSubscription($subscriptionId)
    {
        $subscription = $this->subscriptionModel->find($subscriptionId);
        if (!$subscription) {
            throw new \Exception('Assinatura não encontrada');
        }
        
        switch ($subscription['gateway']) {
            case 'pagseguro':
                return $this->cancelPagSeguroSubscription($subscription);
            case 'mercadopago':
                return $this->cancelMercadoPagoSubscription($subscription);
            default:
                throw new \Exception('Gateway não suportado');
        }
    }
    
    /**
     * Cancel PagSeguro subscription
     */
    protected function cancelPagSeguroSubscription($subscription)
    {
        $config = $this->config->pagseguro;
        
        $response = $this->client->put(
            $config['api_url'] . '/pre-approvals/' . $subscription['gateway_subscription_id'] . '/cancel',
            [
                'query' => [
                    'email' => $config['email'],
                    'token' => $config['token']
                ]
            ]
        );
        
        if ($response->getStatusCode() === 204) {
            $this->subscriptionModel->update($subscription['id'], [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s')
            ]);
            return ['success' => true];
        }
        
        throw new \Exception('Erro ao cancelar assinatura no PagSeguro');
    }
    
    /**
     * Cancel Mercado Pago subscription
     */
    protected function cancelMercadoPagoSubscription($subscription)
    {
        $config = $this->config->mercadopago;
        
        $response = $this->client->put(
            $config['api_url'] . '/preapproval/' . $subscription['gateway_subscription_id'],
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $config['access_token']
                ],
                'body' => json_encode(['status' => 'cancelled'])
            ]
        );
        
        if ($response->getStatusCode() === 200) {
            $this->subscriptionModel->update($subscription['id'], [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s')
            ]);
            return ['success' => true];
        }
        
        throw new \Exception('Erro ao cancelar assinatura no Mercado Pago');
    }
    
    /**
     * Map PagSeguro status to internal status
     */
    protected function mapPagSeguroStatus($status)
    {
        $statusMap = [
            'INITIATED' => 'pending',
            'PENDING' => 'pending',
            'ACTIVE' => 'active',
            'PAYMENT_METHOD_CHANGE' => 'active',
            'SUSPENDED' => 'suspended',
            'CANCELLED' => 'cancelled',
            'CANCELLED_BY_RECEIVER' => 'cancelled',
            'CANCELLED_BY_SENDER' => 'cancelled',
            'EXPIRED' => 'expired'
        ];
        
        return $statusMap[$status] ?? 'unknown';
    }
    
    /**
     * Map Mercado Pago status to internal status
     */
    protected function mapMercadoPagoStatus($status)
    {
        $statusMap = [
            'pending' => 'pending',
            'authorized' => 'active',
            'paused' => 'suspended',
            'cancelled' => 'cancelled',
            'finished' => 'expired'
        ];
        
        return $statusMap[$status] ?? 'unknown';
    }
    
    /**
     * Get available payment methods
     */
    public function getPaymentMethods($gateway = null)
    {
        $gateway = $gateway ?? $this->config->defaultGateway;
        
        $methods = [
            'pagseguro' => [
                'credit_card' => 'Cartão de Crédito',
                'debit_card' => 'Cartão de Débito',
                'boleto' => 'Boleto Bancário',
                'pix' => 'PIX'
            ],
            'mercadopago' => [
                'credit_card' => 'Cartão de Crédito',
                'debit_card' => 'Cartão de Débito',
                'boleto' => 'Boleto Bancário',
                'pix' => 'PIX'
            ]
        ];
        
        return $methods[$gateway] ?? [];
    }
    
    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature($gateway, $payload, $signature)
    {
        switch ($gateway) {
            case 'pagseguro':
                // PagSeguro doesn't use signature validation in the same way
                return true;
            case 'mercadopago':
                $config = $this->config->mercadopago;
                $expectedSignature = hash_hmac('sha256', $payload, $config['webhook_secret']);
                return hash_equals($expectedSignature, $signature);
            default:
                return false;
        }
    }
}