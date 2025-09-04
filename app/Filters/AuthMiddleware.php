<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AuthService;

class AuthMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica se o usuário está autenticado
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login')->with('error', 'Você precisa fazer login para acessar esta página.');
        }
        
        // Verifica se a sessão expirou
        if ($this->authService->isSessionExpired()) {
            $this->authService->logout();
            
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Sessão expirada', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login')->with('error', 'Sua sessão expirou. Faça login novamente.');
        }
        
        // Renova a sessão se necessário
        $this->authService->renewSession();
        
        return null;
    }
    
    /**
     * Executa após a requisição
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

class PermissionMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     * 
     * @param array $arguments Primeiro argumento deve ser a permissão necessária
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica autenticação primeiro
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login');
        }
        
        // Verifica permissão
        $permission = $arguments[0] ?? null;
        
        if (!$permission) {
            throw new \InvalidArgumentException('Permissão não especificada no middleware');
        }
        
        if (!$this->authService->hasPermission($permission)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Acesso negado', 'message' => 'Você não tem permissão para acessar este recurso'])
                    ->setStatusCode(403);
            }
            
            return redirect()->back()->with('error', 'Você não tem permissão para acessar este recurso.');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

class RoleMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     * 
     * @param array $arguments Primeiro argumento deve ser a role necessária
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica autenticação primeiro
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login');
        }
        
        // Verifica role
        $role = $arguments[0] ?? null;
        
        if (!$role) {
            throw new \InvalidArgumentException('Role não especificada no middleware');
        }
        
        if (!$this->authService->hasRole($role)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Acesso negado', 'message' => 'Você não tem o nível de acesso necessário'])
                    ->setStatusCode(403);
            }
            
            return redirect()->back()->with('error', 'Você não tem o nível de acesso necessário.');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

class SuperAdminMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica autenticação primeiro
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login');
        }
        
        // Verifica se é super admin
        if (!$this->authService->hasRole('super_admin')) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Acesso negado', 'message' => 'Acesso restrito a super administradores'])
                    ->setStatusCode(403);
            }
            
            return redirect()->to('/dashboard')->with('error', 'Acesso restrito a super administradores.');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

class OwnerMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica autenticação primeiro
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login');
        }
        
        // Verifica se é proprietário ou super admin
        if (!$this->authService->hasAnyRole(['owner', 'super_admin'])) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Acesso negado', 'message' => 'Acesso restrito a proprietários'])
                    ->setStatusCode(403);
            }
            
            return redirect()->back()->with('error', 'Acesso restrito a proprietários.');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

class AnyPermissionMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     * 
     * @param array $arguments Array de permissões (usuário precisa ter pelo menos uma)
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica autenticação primeiro
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login');
        }
        
        // Verifica se tem pelo menos uma das permissões
        $permissions = $arguments ?? [];
        
        if (empty($permissions)) {
            throw new \InvalidArgumentException('Permissões não especificadas no middleware');
        }
        
        if (!$this->authService->hasAnyPermission($permissions)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Acesso negado', 'message' => 'Você não tem permissão para acessar este recurso'])
                    ->setStatusCode(403);
            }
            
            return redirect()->back()->with('error', 'Você não tem permissão para acessar este recurso.');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

class AllPermissionsMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     * 
     * @param array $arguments Array de permissões (usuário precisa ter todas)
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica autenticação primeiro
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login');
        }
        
        // Verifica se tem todas as permissões
        $permissions = $arguments ?? [];
        
        if (empty($permissions)) {
            throw new \InvalidArgumentException('Permissões não especificadas no middleware');
        }
        
        if (!$this->authService->hasAllPermissions($permissions)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Acesso negado', 'message' => 'Você não tem todas as permissões necessárias'])
                    ->setStatusCode(403);
            }
            
            return redirect()->back()->with('error', 'Você não tem todas as permissões necessárias.');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

class AnyRoleMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     * 
     * @param array $arguments Array de roles (usuário precisa ter pelo menos uma)
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica autenticação primeiro
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login');
        }
        
        // Verifica se tem pelo menos uma das roles
        $roles = $arguments ?? [];
        
        if (empty($roles)) {
            throw new \InvalidArgumentException('Roles não especificadas no middleware');
        }
        
        if (!$this->authService->hasAnyRole($roles)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Acesso negado', 'message' => 'Você não tem o nível de acesso necessário'])
                    ->setStatusCode(403);
            }
            
            return redirect()->back()->with('error', 'Você não tem o nível de acesso necessário.');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

class ResourceAccessMiddleware implements FilterInterface
{
    protected $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Executa antes da requisição
     * 
     * @param array $arguments [0] = resource, [1] = action (opcional, padrão 'read')
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica autenticação primeiro
        if (!$this->authService->isAuthenticated()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Não autenticado', 'redirect' => '/login'])
                    ->setStatusCode(401);
            }
            
            return redirect()->to('/login');
        }
        
        // Verifica acesso ao recurso
        $resource = $arguments[0] ?? null;
        $action = $arguments[1] ?? 'read';
        
        if (!$resource) {
            throw new \InvalidArgumentException('Recurso não especificado no middleware');
        }
        
        if (!$this->authService->canAccess($resource, $action)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'Acesso negado', 'message' => 'Você não tem permissão para acessar este recurso'])
                    ->setStatusCode(403);
            }
            
            return redirect()->back()->with('error', 'Você não tem permissão para acessar este recurso.');
        }
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}