<?php

namespace App\Services;

use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use App\Models\PaymentTransactionModel;
use App\Models\RestaurantModel;
use CodeIgniter\I18n\Time;
use Exception;

class BillingService
{
    protected SubscriptionModel $subscriptionModel;
    protected PlanModel $planModel;
    protected PaymentTransactionModel $transactionModel;
    protected RestaurantModel $restaurantModel;
    protected PaymentGatewayService $paymentGateway;
    
    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
        $this->planModel = new PlanModel();
        $this->transactionModel = new PaymentTransactionModel();
        $this->restaurantModel = new RestaurantModel();
        $this->paymentGateway = new PaymentGatewayService();
    }
    
    /**
     * Create a new subscription
     */
    public function createSubscription(int $restaurantId, int $planId, array $paymentData = []): array
    {
        try {
            $plan = $this->planModel->find($planId);
            if (!$plan) {
                throw new Exception('Plano não encontrado');
            }
            
            $restaurant = $this->restaurantModel->find($restaurantId);
            if (!$restaurant) {
                throw new Exception('Restaurante não encontrado');
            }
            
            // Check if restaurant already has an active subscription
            $existingSubscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
            if ($existingSubscription) {
                throw new Exception('Restaurante já possui uma assinatura ativa');
            }
            
            // Calculate subscription dates
            $startDate = Time::now();
            $endDate = $this->calculateEndDate($startDate, $plan['billing_cycle']);
            
            // Create subscription record
            $subscriptionData = [
                'restaurant_id' => $restaurantId,
                'plan_id' => $planId,
                'status' => 'pending',
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString(),
                'next_billing_date' => $endDate->toDateTimeString(),
                'amount' => $plan['price'],
                'currency' => 'BRL',
                'billing_cycle' => $plan['billing_cycle'],
                'payment_method' => $paymentData['payment_method'] ?? 'credit_card',
                'gateway' => $paymentData['gateway'] ?? config('PaymentGateway')->defaultGateway,
                'trial_ends_at' => null,
                'canceled_at' => null,
                'metadata' => json_encode($paymentData['metadata'] ?? [])
            ];
            
            $subscriptionId = $this->subscriptionModel->insert($subscriptionData);
            
            // Process payment through gateway
            $paymentResult = $this->paymentGateway->createSubscription([
                'subscription_id' => $subscriptionId,
                'restaurant_id' => $restaurantId,
                'plan' => $plan,
                'payment_data' => $paymentData,
                'return_urls' => config('PaymentGateway')->getReturnUrls()
            ]);
            
            // Update subscription with gateway data
            $this->subscriptionModel->update($subscriptionId, [
                'gateway_subscription_id' => $paymentResult['gateway_subscription_id'] ?? null,
                'gateway_customer_id' => $paymentResult['gateway_customer_id'] ?? null,
                'status' => $paymentResult['status'] ?? 'pending'
            ]);
            
            // Create initial transaction record
            $this->transactionModel->insert([
                'subscription_id' => $subscriptionId,
                'restaurant_id' => $restaurantId,
                'amount' => $plan['price'],
                'currency' => 'BRL',
                'status' => 'pending',
                'gateway' => $paymentData['gateway'] ?? config('PaymentGateway')->defaultGateway,
                'gateway_transaction_id' => $paymentResult['gateway_transaction_id'] ?? null,
                'payment_method' => $paymentData['payment_method'] ?? 'credit_card',
                'description' => "Assinatura {$plan['name']} - {$restaurant['name']}",
                'metadata' => json_encode($paymentResult['metadata'] ?? [])
            ]);
            
            return [
                'success' => true,
                'subscription_id' => $subscriptionId,
                'payment_url' => $paymentResult['payment_url'] ?? null,
                'status' => $paymentResult['status'] ?? 'pending',
                'message' => 'Assinatura criada com sucesso'
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao criar assinatura: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Start trial subscription
     */
    public function startTrial(int $restaurantId, int $planId): array
    {
        try {
            $plan = $this->planModel->find($planId);
            if (!$plan) {
                throw new Exception('Plano não encontrado');
            }
            
            // Check if restaurant already used trial
            $existingTrial = $this->subscriptionModel->where('restaurant_id', $restaurantId)
                ->where('trial_ends_at IS NOT NULL')
                ->first();
            
            if ($existingTrial) {
                throw new Exception('Período de teste já foi utilizado');
            }
            
            $startDate = Time::now();
            $trialEndDate = $startDate->addDays(config('PaymentGateway')->subscription['trial_days']);
            $endDate = $this->calculateEndDate($trialEndDate, $plan['billing_cycle']);
            
            $subscriptionData = [
                'restaurant_id' => $restaurantId,
                'plan_id' => $planId,
                'status' => 'trialing',
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString(),
                'next_billing_date' => $trialEndDate->toDateTimeString(),
                'amount' => $plan['price'],
                'currency' => 'BRL',
                'billing_cycle' => $plan['billing_cycle'],
                'trial_ends_at' => $trialEndDate->toDateTimeString(),
                'payment_method' => 'trial',
                'gateway' => config('PaymentGateway')->defaultGateway
            ];
            
            $subscriptionId = $this->subscriptionModel->insert($subscriptionData);
            
            return [
                'success' => true,
                'subscription_id' => $subscriptionId,
                'trial_ends_at' => $trialEndDate->toDateTimeString(),
                'message' => 'Período de teste iniciado com sucesso'
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao iniciar trial: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Change subscription plan
     */
    public function changePlan(int $subscriptionId, int $newPlanId): array
    {
        try {
            $subscription = $this->subscriptionModel->find($subscriptionId);
            if (!$subscription) {
                throw new Exception('Assinatura não encontrada');
            }
            
            $newPlan = $this->planModel->find($newPlanId);
            if (!$newPlan) {
                throw new Exception('Novo plano não encontrado');
            }
            
            $currentPlan = $this->planModel->find($subscription['plan_id']);
            
            // Calculate prorated amount
            $proratedAmount = $this->calculateProratedAmount(
                $subscription,
                $currentPlan,
                $newPlan
            );
            
            // Update subscription
            $updateData = [
                'plan_id' => $newPlanId,
                'amount' => $newPlan['price'],
                'billing_cycle' => $newPlan['billing_cycle']
            ];
            
            // If there's a prorated amount, process payment
            if ($proratedAmount > 0) {
                $paymentResult = $this->processProrationPayment(
                    $subscription,
                    $proratedAmount,
                    $newPlan
                );
                
                if (!$paymentResult['success']) {
                    throw new Exception('Falha no processamento do pagamento proporcional');
                }
            }
            
            $this->subscriptionModel->update($subscriptionId, $updateData);
            
            // Log plan change
            $this->transactionModel->insert([
                'subscription_id' => $subscriptionId,
                'restaurant_id' => $subscription['restaurant_id'],
                'amount' => $proratedAmount,
                'currency' => 'BRL',
                'status' => $proratedAmount > 0 ? 'completed' : 'no_charge',
                'gateway' => $subscription['gateway'],
                'payment_method' => $subscription['payment_method'],
                'description' => "Mudança de plano: {$currentPlan['name']} → {$newPlan['name']}",
                'metadata' => json_encode([
                    'type' => 'plan_change',
                    'old_plan_id' => $currentPlan['id'],
                    'new_plan_id' => $newPlan['id'],
                    'prorated_amount' => $proratedAmount
                ])
            ]);
            
            return [
                'success' => true,
                'prorated_amount' => $proratedAmount,
                'message' => 'Plano alterado com sucesso'
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao alterar plano: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel subscription
     */
    public function cancelSubscription(int $subscriptionId, bool $immediately = false): array
    {
        try {
            $subscription = $this->subscriptionModel->find($subscriptionId);
            if (!$subscription) {
                throw new Exception('Assinatura não encontrada');
            }
            
            if ($subscription['status'] === 'canceled') {
                throw new Exception('Assinatura já foi cancelada');
            }
            
            // Cancel with payment gateway
            if ($subscription['gateway_subscription_id']) {
                $cancelResult = $this->paymentGateway->cancelSubscription(
                    $subscription['gateway_subscription_id'],
                    $subscription['gateway']
                );
                
                if (!$cancelResult['success']) {
                    log_message('warning', 'Falha ao cancelar no gateway: ' . $cancelResult['message']);
                }
            }
            
            $updateData = [
                'status' => 'canceled',
                'canceled_at' => Time::now()->toDateTimeString()
            ];
            
            // If immediate cancellation, set end date to now
            if ($immediately) {
                $updateData['end_date'] = Time::now()->toDateTimeString();
            }
            
            $this->subscriptionModel->update($subscriptionId, $updateData);
            
            // Log cancellation
            $this->transactionModel->insert([
                'subscription_id' => $subscriptionId,
                'restaurant_id' => $subscription['restaurant_id'],
                'amount' => 0,
                'currency' => 'BRL',
                'status' => 'completed',
                'gateway' => $subscription['gateway'],
                'payment_method' => 'cancellation',
                'description' => 'Cancelamento de assinatura',
                'metadata' => json_encode([
                    'type' => 'cancellation',
                    'immediate' => $immediately,
                    'canceled_at' => Time::now()->toDateTimeString()
                ])
            ]);
            
            return [
                'success' => true,
                'message' => $immediately ? 
                    'Assinatura cancelada imediatamente' : 
                    'Assinatura será cancelada no final do período atual'
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao cancelar assinatura: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process recurring billing
     */
    public function processRecurringBilling(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Get subscriptions due for billing
        $dueSubscriptions = $this->subscriptionModel
            ->where('status', 'active')
            ->where('next_billing_date <=', Time::now()->toDateTimeString())
            ->findAll();
        
        foreach ($dueSubscriptions as $subscription) {
            try {
                $result = $this->processSingleBilling($subscription);
                
                if ($result['success']) {
                    $results['processed']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'subscription_id' => $subscription['id'],
                        'error' => $result['message']
                    ];
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription['id'],
                    'error' => $e->getMessage()
                ];
                
                log_message('error', 'Erro no billing recorrente: ' . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Process single subscription billing
     */
    protected function processSingleBilling(array $subscription): array
    {
        try {
            $plan = $this->planModel->find($subscription['plan_id']);
            
            // Process payment through gateway
            $paymentResult = $this->paymentGateway->processRecurringPayment([
                'subscription' => $subscription,
                'plan' => $plan,
                'amount' => $subscription['amount']
            ]);
            
            // Create transaction record
            $transactionId = $this->transactionModel->insert([
                'subscription_id' => $subscription['id'],
                'restaurant_id' => $subscription['restaurant_id'],
                'amount' => $subscription['amount'],
                'currency' => $subscription['currency'],
                'status' => $paymentResult['status'],
                'gateway' => $subscription['gateway'],
                'gateway_transaction_id' => $paymentResult['gateway_transaction_id'] ?? null,
                'payment_method' => $subscription['payment_method'],
                'description' => "Cobrança recorrente - {$plan['name']}",
                'metadata' => json_encode($paymentResult['metadata'] ?? [])
            ]);
            
            if ($paymentResult['success']) {
                // Update next billing date
                $nextBillingDate = $this->calculateEndDate(
                    Time::parse($subscription['next_billing_date']),
                    $subscription['billing_cycle']
                );
                
                $this->subscriptionModel->update($subscription['id'], [
                    'next_billing_date' => $nextBillingDate->toDateTimeString(),
                    'end_date' => $nextBillingDate->toDateTimeString(),
                    'payment_failures' => 0
                ]);
                
            } else {
                // Handle payment failure
                $this->handlePaymentFailure($subscription, $paymentResult['message']);
            }
            
            return $paymentResult;
            
        } catch (Exception $e) {
            log_message('error', 'Erro no processamento de billing: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle payment failure
     */
    protected function handlePaymentFailure(array $subscription, string $errorMessage): void
    {
        $failures = ($subscription['payment_failures'] ?? 0) + 1;
        $maxFailures = config('PaymentGateway')->subscription['max_payment_failures'];
        
        $updateData = ['payment_failures' => $failures];
        
        if ($failures >= $maxFailures) {
            // Suspend subscription after max failures
            $updateData['status'] = 'suspended';
            $updateData['suspended_at'] = Time::now()->toDateTimeString();
        } else {
            // Schedule retry
            $retryDelay = config('PaymentGateway')->subscription['retry_delay'];
            $nextRetry = Time::now()->addSeconds($retryDelay);
            $updateData['next_billing_date'] = $nextRetry->toDateTimeString();
        }
        
        $this->subscriptionModel->update($subscription['id'], $updateData);
        
        // Send notification (implement notification service)
        // $this->sendPaymentFailureNotification($subscription, $failures, $maxFailures);
    }
    
    /**
     * Calculate end date based on billing cycle
     */
    protected function calculateEndDate(Time $startDate, string $billingCycle): Time
    {
        switch ($billingCycle) {
            case 'monthly':
                return $startDate->addMonths(1);
            case 'quarterly':
                return $startDate->addMonths(3);
            case 'yearly':
                return $startDate->addYears(1);
            default:
                return $startDate->addMonths(1);
        }
    }
    
    /**
     * Calculate prorated amount for plan changes
     */
    protected function calculateProratedAmount(array $subscription, array $currentPlan, array $newPlan): float
    {
        $currentDate = Time::now();
        $endDate = Time::parse($subscription['end_date']);
        $startDate = Time::parse($subscription['start_date']);
        
        // Calculate remaining days in current billing cycle
        $totalDays = $startDate->difference($endDate)->getDays();
        $remainingDays = $currentDate->difference($endDate)->getDays();
        
        if ($remainingDays <= 0) {
            return $newPlan['price']; // Full amount if at end of cycle
        }
        
        // Calculate prorated amounts
        $currentPlanProrated = ($currentPlan['price'] / $totalDays) * $remainingDays;
        $newPlanProrated = ($newPlan['price'] / $totalDays) * $remainingDays;
        
        $difference = $newPlanProrated - $currentPlanProrated;
        
        return max(0, $difference); // Only charge if upgrade
    }
    
    /**
     * Process proration payment
     */
    protected function processProrationPayment(array $subscription, float $amount, array $newPlan): array
    {
        try {
            return $this->paymentGateway->processOneTimePayment([
                'subscription_id' => $subscription['id'],
                'restaurant_id' => $subscription['restaurant_id'],
                'amount' => $amount,
                'description' => "Cobrança proporcional - Upgrade para {$newPlan['name']}",
                'payment_method' => $subscription['payment_method'],
                'gateway' => $subscription['gateway']
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro no pagamento proporcional: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get subscription statistics
     */
    public function getSubscriptionStats(): array
    {
        return [
            'total_subscriptions' => $this->subscriptionModel->countAll(),
            'active_subscriptions' => $this->subscriptionModel->where('status', 'active')->countAllResults(),
            'trial_subscriptions' => $this->subscriptionModel->where('status', 'trialing')->countAllResults(),
            'canceled_subscriptions' => $this->subscriptionModel->where('status', 'canceled')->countAllResults(),
            'suspended_subscriptions' => $this->subscriptionModel->where('status', 'suspended')->countAllResults(),
            'monthly_revenue' => $this->getMonthlyRevenue(),
            'churn_rate' => $this->calculateChurnRate(),
            'average_revenue_per_user' => $this->calculateARPU()
        ];
    }
    
    /**
     * Get monthly revenue
     */
    protected function getMonthlyRevenue(): float
    {
        $startOfMonth = Time::now()->startOfMonth();
        $endOfMonth = Time::now()->endOfMonth();
        
        $result = $this->transactionModel
            ->selectSum('amount')
            ->where('status', 'completed')
            ->where('created_at >=', $startOfMonth->toDateTimeString())
            ->where('created_at <=', $endOfMonth->toDateTimeString())
            ->first();
        
        return (float) ($result['amount'] ?? 0);
    }
    
    /**
     * Calculate churn rate
     */
    protected function calculateChurnRate(): float
    {
        $startOfMonth = Time::now()->startOfMonth();
        $endOfMonth = Time::now()->endOfMonth();
        
        $totalAtStart = $this->subscriptionModel
            ->where('created_at <', $startOfMonth->toDateTimeString())
            ->where('status !=', 'canceled')
            ->countAllResults();
        
        $canceledThisMonth = $this->subscriptionModel
            ->where('canceled_at >=', $startOfMonth->toDateTimeString())
            ->where('canceled_at <=', $endOfMonth->toDateTimeString())
            ->countAllResults();
        
        return $totalAtStart > 0 ? ($canceledThisMonth / $totalAtStart) * 100 : 0;
    }
    
    /**
     * Calculate Average Revenue Per User
     */
    protected function calculateARPU(): float
    {
        $activeSubscriptions = $this->subscriptionModel
            ->where('status', 'active')
            ->countAllResults();
        
        $monthlyRevenue = $this->getMonthlyRevenue();
        
        return $activeSubscriptions > 0 ? $monthlyRevenue / $activeSubscriptions : 0;
    }
}