<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UsageTrackingModel;
use App\Models\RestaurantModel;
use App\Models\SubscriptionModel;
use App\Models\PlanModel;

class UsageLimitFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $restaurantId = $session->get('restaurant_id');
        
        if (!$restaurantId) {
            return redirect()->to('/login')->with('error', 'Sessão expirada. Faça login novamente.');
        }
        
        // Get the action being performed
        $action = $arguments[0] ?? 'general';
        
        // Check limits based on action
        $usageModel = new UsageTrackingModel();
        $limits = $usageModel->checkLimits($restaurantId);
        
        if (!$limits['has_limits']) {
            // No active subscription, allow basic usage
            return null;
        }
        
        // Check specific limits based on action
        switch ($action) {
            case 'order':
                if (in_array('orders', $limits['exceeded'])) {
                    return $this->handleLimitExceeded('orders', $limits);
                }
                break;
                
            case 'totem':
                if (in_array('totems', $limits['exceeded'])) {
                    return $this->handleLimitExceeded('totems', $limits);
                }
                break;
                
            case 'employee':
                if (in_array('employees', $limits['exceeded'])) {
                    return $this->handleLimitExceeded('employees', $limits);
                }
                break;
                
            case 'api':
                // For API calls, we might want to return JSON response
                if (in_array('api', $limits['exceeded'])) {
                    return $this->handleApiLimitExceeded($limits);
                }
                break;
        }
        
        // Store usage info in session for display
        $session->setTempdata('usage_info', $limits, 300); // 5 minutes
        
        return null;
    }
    
    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
        return null;
    }
    
    /**
     * Handle limit exceeded for web requests
     */
    protected function handleLimitExceeded($limitType, $limits)
    {
        $messages = [
            'orders' => 'Você atingiu o limite de pedidos do seu plano. Faça upgrade para continuar.',
            'totems' => 'Você atingiu o limite de totems do seu plano. Faça upgrade para usar mais totems.',
            'employees' => 'Você atingiu o limite de funcionários do seu plano. Faça upgrade para adicionar mais funcionários.'
        ];
        
        $message = $messages[$limitType] ?? 'Limite do plano atingido.';
        
        // Check if it's an AJAX request
        if (service('request')->isAJAX()) {
            return service('response')
                ->setJSON([
                    'success' => false,
                    'error' => $message,
                    'limit_exceeded' => true,
                    'limit_type' => $limitType,
                    'usage' => $limits['usage'],
                    'limits' => $limits['limits']
                ])
                ->setStatusCode(402); // Payment Required
        }
        
        // For regular requests, redirect to subscription page
        return redirect()->to('/subscription/plans')
            ->with('error', $message)
            ->with('limit_exceeded', $limitType);
    }
    
    /**
     * Handle API limit exceeded
     */
    protected function handleApiLimitExceeded($limits)
    {
        return service('response')
            ->setJSON([
                'success' => false,
                'error' => 'API limit exceeded for your plan',
                'limit_exceeded' => true,
                'limit_type' => 'api',
                'usage' => $limits['usage'],
                'limits' => $limits['limits']
            ])
            ->setStatusCode(429) // Too Many Requests
            ->setHeader('Retry-After', '3600'); // Retry after 1 hour
    }
    
    /**
     * Check if restaurant can perform action
     */
    public static function canPerformAction($restaurantId, $action)
    {
        $usageModel = new UsageTrackingModel();
        $limits = $usageModel->checkLimits($restaurantId);
        
        if (!$limits['has_limits']) {
            return true;
        }
        
        switch ($action) {
            case 'order':
                return !in_array('orders', $limits['exceeded']);
            case 'totem':
                return !in_array('totems', $limits['exceeded']);
            case 'employee':
                return !in_array('employees', $limits['exceeded']);
            default:
                return true;
        }
    }
    
    /**
     * Get usage warnings for display
     */
    public static function getUsageWarnings($restaurantId)
    {
        $usageModel = new UsageTrackingModel();
        $limits = $usageModel->checkLimits($restaurantId);
        
        if (!$limits['has_limits'] || empty($limits['warnings'])) {
            return [];
        }
        
        $warnings = [];
        $usage = $limits['usage'];
        $planLimits = $limits['limits'];
        
        foreach ($limits['warnings'] as $warning) {
            switch ($warning) {
                case 'orders':
                    $percentage = round(($usage['orders_count'] / $planLimits['max_orders_per_month']) * 100);
                    $warnings[] = [
                        'type' => 'orders',
                        'message' => "Você usou {$percentage}% do limite de pedidos deste mês ({$usage['orders_count']}/{$planLimits['max_orders_per_month']})",
                        'percentage' => $percentage
                    ];
                    break;
            }
        }
        
        return $warnings;
    }
}