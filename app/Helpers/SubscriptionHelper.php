<?php

namespace App\Helpers;

use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use App\Models\UsageTrackingModel;
use App\Models\RestaurantModel;

class SubscriptionHelper
{
    protected static $subscriptionModel;
    protected static $planModel;
    protected static $usageModel;
    protected static $restaurantModel;
    
    /**
     * Initialize models
     */
    protected static function initModels()
    {
        if (!self::$subscriptionModel) {
            self::$subscriptionModel = new SubscriptionModel();
            self::$planModel = new PlanModel();
            self::$usageModel = new UsageTrackingModel();
            self::$restaurantModel = new RestaurantModel();
        }
    }
    
    /**
     * Check if restaurant has access to a feature
     */
    public static function hasFeature($restaurantId, $feature)
    {
        self::initModels();
        
        $subscription = self::$subscriptionModel->getActiveSubscription($restaurantId);
        
        if (!$subscription) {
            // No subscription, check if in trial
            $restaurant = self::$restaurantModel->find($restaurantId);
            if ($restaurant && self::$subscriptionModel->isInTrial($restaurantId)) {
                // Trial users get Professional features
                $plan = self::$planModel->where('slug', 'professional')->first();
            } else {
                // No subscription, basic features only
                return self::getBasicFeatures()[$feature] ?? false;
            }
        } else {
            $plan = self::$planModel->find($subscription['plan_id']);
        }
        
        if (!$plan) {
            return false;
        }
        
        $features = json_decode($plan['features'], true);
        return $features[$feature] ?? false;
    }
    
    /**
     * Get plan limits for restaurant
     */
    public static function getPlanLimits($restaurantId)
    {
        self::initModels();
        
        $subscription = self::$subscriptionModel->getActiveSubscription($restaurantId);
        
        if (!$subscription) {
            // Check if in trial
            if (self::$subscriptionModel->isInTrial($restaurantId)) {
                $plan = self::$planModel->where('slug', 'professional')->first();
            } else {
                return self::getBasicLimits();
            }
        } else {
            $plan = self::$planModel->find($subscription['plan_id']);
        }
        
        if (!$plan) {
            return self::getBasicLimits();
        }
        
        return [
            'max_totems' => $plan['max_totems'],
            'max_orders_per_month' => $plan['max_orders_per_month'],
            'max_employees' => $plan['max_employees']
        ];
    }
    
    /**
     * Get current usage for restaurant
     */
    public static function getCurrentUsage($restaurantId)
    {
        self::initModels();
        return self::$usageModel->getCurrentMonthUsage($restaurantId);
    }
    
    /**
     * Check if restaurant can perform action
     */
    public static function canPerformAction($restaurantId, $action)
    {
        self::initModels();
        
        $limits = self::$usageModel->checkLimits($restaurantId);
        
        if (!$limits['has_limits']) {
            return true;
        }
        
        return !in_array($action, $limits['exceeded']);
    }
    
    /**
     * Get subscription status for restaurant
     */
    public static function getSubscriptionStatus($restaurantId)
    {
        self::initModels();
        
        $subscription = self::$subscriptionModel->getActiveSubscription($restaurantId);
        
        if (!$subscription) {
            if (self::$subscriptionModel->isInTrial($restaurantId)) {
                $trial = self::$subscriptionModel->where('restaurant_id', $restaurantId)
                                                 ->where('status', 'trial')
                                                 ->first();
                return [
                    'status' => 'trial',
                    'plan_name' => 'Trial Professional',
                    'expires_at' => $trial['trial_ends_at'] ?? null,
                    'days_remaining' => self::getDaysRemaining($trial['trial_ends_at'] ?? null)
                ];
            }
            
            return [
                'status' => 'none',
                'plan_name' => 'Sem Plano',
                'expires_at' => null,
                'days_remaining' => 0
            ];
        }
        
        $plan = self::$planModel->find($subscription['plan_id']);
        
        return [
            'status' => $subscription['status'],
            'plan_name' => $plan['name'] ?? 'Plano Desconhecido',
            'plan_slug' => $plan['slug'] ?? null,
            'expires_at' => $subscription['ends_at'],
            'next_payment' => $subscription['next_payment_date'],
            'days_remaining' => self::getDaysRemaining($subscription['ends_at'])
        ];
    }
    
    /**
     * Get plan comparison data
     */
    public static function getPlanComparison()
    {
        self::initModels();
        
        $plans = self::$planModel->getActivePlans();
        $comparison = [];
        
        foreach ($plans as $plan) {
            $features = json_decode($plan['features'], true);
            
            $comparison[] = [
                'id' => $plan['id'],
                'name' => $plan['name'],
                'slug' => $plan['slug'],
                'price' => $plan['price'],
                'billing_cycle' => $plan['billing_cycle'],
                'max_totems' => $plan['max_totems'],
                'max_orders_per_month' => $plan['max_orders_per_month'],
                'max_employees' => $plan['max_employees'],
                'features' => $features,
                'popular' => $plan['slug'] === 'professional' // Mark Professional as popular
            ];
        }
        
        return $comparison;
    }
    
    /**
     * Calculate upgrade/downgrade price difference
     */
    public static function calculatePriceDifference($currentPlanId, $newPlanId)
    {
        self::initModels();
        
        $currentPlan = self::$planModel->find($currentPlanId);
        $newPlan = self::$planModel->find($newPlanId);
        
        if (!$currentPlan || !$newPlan) {
            return null;
        }
        
        return [
            'current_price' => $currentPlan['price'],
            'new_price' => $newPlan['price'],
            'difference' => $newPlan['price'] - $currentPlan['price'],
            'is_upgrade' => $newPlan['price'] > $currentPlan['price']
        ];
    }
    
    /**
     * Get recommended plan based on usage
     */
    public static function getRecommendedPlan($restaurantId)
    {
        self::initModels();
        
        $usage = self::$usageModel->getCurrentMonthUsage($restaurantId);
        $plans = self::$planModel->getActivePlans();
        
        // Sort plans by price
        usort($plans, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        
        foreach ($plans as $plan) {
            // Check if plan can handle current usage with some buffer
            $canHandle = true;
            
            if ($plan['max_orders_per_month'] && $usage['orders_count'] > ($plan['max_orders_per_month'] * 0.7)) {
                $canHandle = false;
            }
            
            if ($plan['max_totems'] && $usage['totems_used'] > $plan['max_totems']) {
                $canHandle = false;
            }
            
            if ($plan['max_employees'] && $usage['employees_count'] > $plan['max_employees']) {
                $canHandle = false;
            }
            
            if ($canHandle) {
                return $plan;
            }
        }
        
        // If no plan can handle, return the highest tier
        return end($plans);
    }
    
    /**
     * Get basic features for non-subscribers
     */
    protected static function getBasicFeatures()
    {
        return [
            'online_ordering' => true,
            'basic_analytics' => true,
            'customer_support' => false,
            'advanced_analytics' => false,
            'custom_branding' => false,
            'api_access' => false,
            'priority_support' => false,
            'white_label' => false,
            'custom_integrations' => false,
            'dedicated_manager' => false
        ];
    }
    
    /**
     * Get basic limits for non-subscribers
     */
    protected static function getBasicLimits()
    {
        return [
            'max_totems' => 1,
            'max_orders_per_month' => 50,
            'max_employees' => 2
        ];
    }
    
    /**
     * Calculate days remaining
     */
    protected static function getDaysRemaining($endDate)
    {
        if (!$endDate) {
            return 0;
        }
        
        $end = new \DateTime($endDate);
        $now = new \DateTime();
        
        if ($end < $now) {
            return 0;
        }
        
        return $now->diff($end)->days;
    }
    
    /**
     * Format price for display
     */
    public static function formatPrice($price, $currency = 'BRL')
    {
        return 'R$ ' . number_format($price, 2, ',', '.');
    }
    
    /**
     * Get billing cycle text
     */
    public static function getBillingCycleText($cycle)
    {
        $cycles = [
            'monthly' => 'por mês',
            'yearly' => 'por ano',
            'quarterly' => 'por trimestre'
        ];
        
        return $cycles[$cycle] ?? $cycle;
    }
    
    /**
     * Check if restaurant needs to upgrade
     */
    public static function needsUpgrade($restaurantId)
    {
        self::initModels();
        
        $limits = self::$usageModel->checkLimits($restaurantId);
        
        return !empty($limits['exceeded']);
    }
    
    /**
     * Get upgrade suggestions
     */
    public static function getUpgradeSuggestions($restaurantId)
    {
        self::initModels();
        
        $limits = self::$usageModel->checkLimits($restaurantId);
        $suggestions = [];
        
        if (in_array('orders', $limits['exceeded'])) {
            $suggestions[] = [
                'type' => 'orders',
                'message' => 'Você atingiu o limite de pedidos. Considere fazer upgrade para processar mais pedidos.',
                'action' => 'upgrade'
            ];
        }
        
        if (in_array('totems', $limits['exceeded'])) {
            $suggestions[] = [
                'type' => 'totems',
                'message' => 'Você atingiu o limite de totems. Faça upgrade para usar mais totems.',
                'action' => 'upgrade'
            ];
        }
        
        if (in_array('employees', $limits['exceeded'])) {
            $suggestions[] = [
                'type' => 'employees',
                'message' => 'Você atingiu o limite de funcionários. Faça upgrade para adicionar mais funcionários.',
                'action' => 'upgrade'
            ];
        }
        
        return $suggestions;
    }
}