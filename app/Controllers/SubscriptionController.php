<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PlanModel;
use App\Models\SubscriptionModel;
use App\Models\RestaurantModel;
use CodeIgniter\HTTP\ResponseInterface;

class SubscriptionController extends BaseController
{
    protected $planModel;
    protected $subscriptionModel;
    protected $restaurantModel;

    public function __construct()
    {
        $this->planModel = new PlanModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->restaurantModel = new RestaurantModel();
    }

    /**
     * Display subscription plans
     */
    public function plans()
    {
        $data = [
            'title' => 'Planos de Assinatura',
            'plans' => $this->planModel->getPlansForComparison()
        ];

        return view('subscription/plans', $data);
    }

    /**
     * Show current subscription status
     */
    public function index()
    {
        $restaurantId = session()->get('restaurant_id');
        if (!$restaurantId) {
            return redirect()->to('/login')->with('error', 'Acesso negado.');
        }

        $subscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
        $restaurant = $this->restaurantModel->getRestaurantWithSubscription($restaurantId);
        $availablePlans = $this->planModel->getActivePlans();

        $data = [
            'title' => 'Minha Assinatura',
            'subscription' => $subscription,
            'restaurant' => $restaurant,
            'plans' => $availablePlans,
            'isInTrial' => $this->restaurantModel->isInTrial($restaurantId)
        ];

        return view('subscription/index', $data);
    }

    /**
     * Start trial subscription
     */
    public function startTrial()
    {
        $restaurantId = session()->get('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Restaurante não identificado.'
            ]);
        }

        // Check if already has subscription
        if ($this->subscriptionModel->hasActiveSubscription($restaurantId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Restaurante já possui uma assinatura ativa.'
            ]);
        }

        $planId = $this->request->getPost('plan_id');
        if (!$planId) {
            // Default to starter plan for trial
            $starterPlan = $this->planModel->findBySlug('starter');
            $planId = $starterPlan['id'] ?? 1;
        }

        $subscriptionId = $this->subscriptionModel->createTrialSubscription($restaurantId, $planId, 30);

        if ($subscriptionId) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Trial de 30 dias iniciado com sucesso!',
                'subscription_id' => $subscriptionId
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao iniciar trial. Tente novamente.'
        ]);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe()
    {
        $restaurantId = session()->get('restaurant_id');
        if (!$restaurantId) {
            return redirect()->to('/login')->with('error', 'Acesso negado.');
        }

        $planSlug = $this->request->getPost('plan');
        $plan = $this->planModel->findBySlug($planSlug);

        if (!$plan) {
            return redirect()->back()->with('error', 'Plano não encontrado.');
        }

        // For now, we'll simulate the subscription activation
        // In a real implementation, this would integrate with a payment gateway
        $success = $this->subscriptionModel->activateSubscription(
            $restaurantId, 
            $plan['id'], 
            'credit_card', // payment method
            'simulated_gateway_id' // payment gateway ID
        );

        if ($success) {
            return redirect()->to('/subscription')
                           ->with('success', 'Assinatura ativada com sucesso!');
        }

        return redirect()->back()
                       ->with('error', 'Erro ao processar assinatura. Tente novamente.');
    }

    /**
     * Change subscription plan
     */
    public function changePlan()
    {
        $restaurantId = session()->get('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado.'
            ]);
        }

        $newPlanSlug = $this->request->getPost('plan');
        $newPlan = $this->planModel->findBySlug($newPlanSlug);

        if (!$newPlan) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Plano não encontrado.'
            ]);
        }

        $currentSubscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
        if (!$currentSubscription) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nenhuma assinatura ativa encontrada.'
            ]);
        }

        // Update subscription plan
        $success = $this->subscriptionModel->update($currentSubscription['id'], [
            'plan_id' => $newPlan['id']
        ]);

        if ($success) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Plano alterado com sucesso!'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao alterar plano. Tente novamente.'
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel()
    {
        $restaurantId = session()->get('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado.'
            ]);
        }

        $subscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
        if (!$subscription) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nenhuma assinatura ativa encontrada.'
            ]);
        }

        $immediately = $this->request->getPost('immediately') === 'true';
        $success = $this->subscriptionModel->cancelSubscription($subscription['id'], $immediately);

        if ($success) {
            $message = $immediately ? 
                'Assinatura cancelada imediatamente.' : 
                'Assinatura será cancelada no final do período atual.';

            return $this->response->setJSON([
                'success' => true,
                'message' => $message
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao cancelar assinatura. Tente novamente.'
        ]);
    }

    /**
     * Get subscription usage and limits
     */
    public function usage()
    {
        $restaurantId = session()->get('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado.'
            ]);
        }

        $subscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
        if (!$subscription) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nenhuma assinatura ativa encontrada.'
            ]);
        }

        $planLimits = $this->planModel->getPlanLimits($subscription['plan_id']);
        
        // Get current usage (this would be implemented with actual usage tracking)
        $currentUsage = [
            'totems_used' => 1, // Placeholder
            'orders_this_month' => 45, // Placeholder
            'employees_count' => 3 // Placeholder
        ];

        $data = [
            'success' => true,
            'limits' => $planLimits,
            'usage' => $currentUsage,
            'subscription' => $subscription
        ];

        return $this->response->setJSON($data);
    }

    /**
     * Webhook for payment notifications (placeholder)
     */
    public function webhook()
    {
        // This would handle webhooks from payment gateways
        // For now, it's just a placeholder
        
        $payload = $this->request->getJSON(true);
        
        // Log the webhook for debugging
        log_message('info', 'Subscription webhook received: ' . json_encode($payload));
        
        // Process the webhook based on the payment gateway
        // This would include updating subscription status, recording payments, etc.
        
        return $this->response->setStatusCode(200)->setJSON(['status' => 'received']);
    }

    /**
     * Admin: Get subscription statistics
     */
    public function adminStats()
    {
        // This would be protected by admin authentication
        $stats = $this->subscriptionModel->getSubscriptionStats();
        
        return $this->response->setJSON([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Check if restaurant can access feature based on plan
     */
    public function checkFeatureAccess($feature)
    {
        $restaurantId = session()->get('restaurant_id');
        if (!$restaurantId) {
            return false;
        }

        $subscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
        if (!$subscription) {
            return false;
        }

        return $this->planModel->hasFeature($subscription['plan_id'], $feature);
    }
}