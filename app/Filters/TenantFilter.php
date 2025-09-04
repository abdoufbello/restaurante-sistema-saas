<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RestaurantModel;
use App\Models\EmployeeModel;

/**
 * Filtro Multi-Tenant para isolamento de dados por restaurante
 * Garante que cada usuário só acesse dados do seu próprio restaurante
 */
class TenantFilter implements FilterInterface
{
    /**
     * Executa antes da requisição
     * Identifica o tenant (restaurante) baseado no usuário logado
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // Verificar se o usuário está logado
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $userId = $session->get('user_id');
        $userType = $session->get('user_type'); // 'restaurant' ou 'employee'
        
        try {
            $restaurantId = null;
            
            if ($userType === 'restaurant') {
                // Usuário é dono do restaurante
                $restaurantModel = new RestaurantModel();
                $restaurant = $restaurantModel->find($userId);
                
                if (!$restaurant || !$restaurant['is_active']) {
                    $session->destroy();
                    return redirect()->to('/login')->with('error', 'Restaurante inativo ou não encontrado.');
                }
                
                $restaurantId = $restaurant['id'];
                
                // Verificar se a assinatura está ativa
                if (!$this->isSubscriptionActive($restaurant)) {
                    return redirect()->to('/subscription/expired');
                }
                
            } elseif ($userType === 'employee') {
                // Usuário é funcionário
                $employeeModel = new EmployeeModel();
                $employee = $employeeModel->find($userId);
                
                if (!$employee || !$employee['is_active']) {
                    $session->destroy();
                    return redirect()->to('/login')->with('error', 'Funcionário inativo ou não encontrado.');
                }
                
                $restaurantId = $employee['restaurant_id'];
                
                // Verificar se o restaurante está ativo
                $restaurantModel = new RestaurantModel();
                $restaurant = $restaurantModel->find($restaurantId);
                
                if (!$restaurant || !$restaurant['is_active']) {
                    $session->destroy();
                    return redirect()->to('/login')->with('error', 'Restaurante inativo.');
                }
                
                // Verificar se a assinatura está ativa
                if (!$this->isSubscriptionActive($restaurant)) {
                    return redirect()->to('/subscription/expired');
                }
                
                // Armazenar permissões do funcionário
                $session->set('employee_permissions', json_decode($employee['permissions'] ?? '[]', true));
                $session->set('employee_role', $employee['role']);
            }
            
            if (!$restaurantId) {
                $session->destroy();
                return redirect()->to('/login')->with('error', 'Erro na identificação do tenant.');
            }
            
            // Definir o tenant atual na sessão
            $session->set('tenant_id', $restaurantId);
            $session->set('restaurant_id', $restaurantId);
            
            // Definir constante global para uso nos models
            if (!defined('CURRENT_TENANT_ID')) {
                define('CURRENT_TENANT_ID', $restaurantId);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Erro no TenantFilter: ' . $e->getMessage());
            $session->destroy();
            return redirect()->to('/login')->with('error', 'Erro interno. Tente novamente.');
        }
        
        return null;
    }
    
    /**
     * Executa após a requisição
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nada a fazer após a requisição
        return null;
    }
    
    /**
     * Verifica se a assinatura do restaurante está ativa
     */
    private function isSubscriptionActive($restaurant): bool
    {
        // Se está em período de trial
        if ($restaurant['subscription_status'] === 'trial') {
            $trialEnds = strtotime($restaurant['trial_ends_at']);
            return $trialEnds > time();
        }
        
        // Se tem assinatura paga
        if ($restaurant['subscription_status'] === 'active') {
            $subscriptionEnds = strtotime($restaurant['subscription_expires_at']);
            return $subscriptionEnds > time();
        }
        
        return false;
    }
}