<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AuthService;

/**
 * Authentication Filter
 * Protects admin routes by checking if user is logged in
 */
class AuthFilter implements FilterInterface
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
        $authService = new AuthService();
        
        // Verifica se o usuário está autenticado (novo sistema)
        if ($authService->isAuthenticated()) {
            // Verifica se a sessão expirou
            if ($authService->isSessionExpired()) {
                $authService->logout();
                
                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(401)
                        ->setJSON([
                            'success' => false,
                            'message' => 'Sessão expirada',
                            'error_code' => 'SESSION_EXPIRED'
                        ]);
                }
                
                return redirect()->to('/admin/login')->with('error', 'Sua sessão expirou. Faça login novamente.');
            }
            
            // Renova a sessão
            $authService->renewSession();
            return null;
        }
        
        // Fallback para o sistema antigo (compatibilidade)
        // Check if user is logged in (old system)
        if (!$session->get('logged_in')) {
            // Store the intended URL for redirect after login
            $session->set('redirect_url', current_url());
            
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Não autenticado',
                        'error_code' => 'UNAUTHENTICATED'
                    ]);
            }
            
            // Redirect to login page
            return redirect()->to('/admin/login')->with('error', 'Você precisa fazer login para acessar esta página.');
        }
        
        // Check if session is still valid (not expired)
        $lastActivity = $session->get('last_activity');
        $sessionTimeout = 3600; // 1 hour in seconds
        
        if ($lastActivity && (time() - $lastActivity > $sessionTimeout)) {
            // Session expired, destroy it
            $session->destroy();
            
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Sessão expirada',
                        'error_code' => 'SESSION_EXPIRED'
                    ]);
            }
            
            return redirect()->to('/admin/login')->with('error', 'Sua sessão expirou. Faça login novamente.');
        }
        
        // Update last activity time
        $session->set('last_activity', time());
        
        // Check if user account is still active
        $employeeModel = new \App\Models\Restaurant\EmployeeModel();
        $employee = $employeeModel->find($session->get('employee_id'));
        
        if (!$employee || $employee['status'] !== 'active') {
            $session->destroy();
            
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Conta desativada',
                        'error_code' => 'ACCOUNT_DISABLED'
                    ]);
            }
            
            return redirect()->to('/admin/login')->with('error', 'Sua conta foi desativada. Entre em contato com o administrador.');
        }
        
        // Check if restaurant is still active
        $restaurantModel = new \App\Models\Restaurant\RestaurantModel();
        $restaurant = $restaurantModel->find($session->get('restaurant_id'));
        
        if (!$restaurant || $restaurant['status'] !== 'active') {
            $session->destroy();
            
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Restaurante desativado',
                        'error_code' => 'RESTAURANT_DISABLED'
                    ]);
            }
            
            return redirect()->to('/admin/login')->with('error', 'O restaurante foi desativado. Entre em contato com o suporte.');
        }
        
        // Check subscription validity
        if ($restaurant['subscription_expires_at'] && strtotime($restaurant['subscription_expires_at']) < time()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(402)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Assinatura expirada',
                        'error_code' => 'SUBSCRIPTION_EXPIRED'
                    ]);
            }
            
            return redirect()->to('/admin/subscription-expired')->with('error', 'Sua assinatura expirou. Renove para continuar usando o sistema.');
        }
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
    }
}

/**
 * Filtro de Permissões
 */
class PermissionFilter implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    public function before(RequestInterface $request, $arguments = null)
    {
        // Primeiro verifica autenticação
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Não autenticado',
                        'error_code' => 'UNAUTHENTICATED'
                    ]);
            }
            
            return redirect()->to('/admin/login');
        }
        
        // Verifica permissões se fornecidas
        if ($arguments && !empty($arguments)) {
            $permission = $arguments[0];
            
            if (!$this->authService->hasPermission($permission)) {
                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON([
                            'success' => false,
                            'message' => 'Acesso negado',
                            'error_code' => 'FORBIDDEN',
                            'required_permission' => $permission
                        ]);
                }
                
                return redirect()->to('/admin/dashboard')->with('error', 'Você não tem permissão para acessar esta página');
            }
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

/**
 * Filtro de Roles
 */
class RoleFilter implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    public function before(RequestInterface $request, $arguments = null)
    {
        // Primeiro verifica autenticação
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Não autenticado',
                        'error_code' => 'UNAUTHENTICATED'
                    ]);
            }
            
            return redirect()->to('/admin/login');
        }
        
        // Verifica roles se fornecidas
        if ($arguments && !empty($arguments)) {
            $requiredRole = $arguments[0];
            
            // Se múltiplas roles são fornecidas (separadas por vírgula)
            if (strpos($requiredRole, ',') !== false) {
                $roles = array_map('trim', explode(',', $requiredRole));
                
                if (!$this->authService->hasAnyRole($roles)) {
                    if ($request->isAJAX()) {
                        return service('response')
                            ->setStatusCode(403)
                            ->setJSON([
                                'success' => false,
                                'message' => 'Acesso negado',
                                'error_code' => 'FORBIDDEN',
                                'required_roles' => $roles
                            ]);
                    }
                    
                    return redirect()->to('/admin/dashboard')->with('error', 'Você não tem a função necessária para acessar esta página');
                }
            } else {
                // Verifica role única
                if (!$this->authService->hasRole($requiredRole)) {
                    if ($request->isAJAX()) {
                        return service('response')
                            ->setStatusCode(403)
                            ->setJSON([
                                'success' => false,
                                'message' => 'Acesso negado',
                                'error_code' => 'FORBIDDEN',
                                'required_role' => $requiredRole
                            ]);
                    }
                    
                    return redirect()->to('/admin/dashboard')->with('error', 'Você não tem a função necessária para acessar esta página');
                }
            }
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

/**
 * Filtro para Super Admin
 */
class SuperAdminFilter implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Não autenticado',
                        'error_code' => 'UNAUTHENTICATED'
                    ]);
            }
            
            return redirect()->to('/admin/login');
        }
        
        if (!$this->authService->hasRole('super_admin')) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Acesso restrito a Super Administradores',
                        'error_code' => 'SUPER_ADMIN_REQUIRED'
                    ]);
            }
            
            return redirect()->to('/admin/dashboard')->with('error', 'Acesso restrito a Super Administradores');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}