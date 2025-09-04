<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\JWTAuthModel;
use Exception;

class PermissionFilter implements FilterInterface
{
    /**
     * Executa o filtro de verificação de permissões
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');
        
        try {
            // Verificar se o usuário está autenticado
            if (empty($request->user_id)) {
                return $this->unauthorizedResponse($response, 'Usuário não autenticado');
            }
            
            // Obter permissões necessárias dos argumentos
            $requiredPermissions = $arguments ?? [];
            
            if (empty($requiredPermissions)) {
                // Se não há permissões específicas, permitir acesso
                return $request;
            }
            
            // Obter permissões do usuário
            $userPermissions = $request->permissions ?? [];
            $userRoles = $request->roles ?? [];
            
            // Verificar se é super admin (bypass de todas as verificações)
            if (in_array('super_admin', $userRoles)) {
                return $request;
            }
            
            // Verificar permissões específicas
            if (!$this->hasPermissions($userPermissions, $userRoles, $requiredPermissions)) {
                return $this->forbiddenResponse($response, 'Permissões insuficientes');
            }
            
            // Verificar permissões contextuais (multi-tenancy)
            if (!$this->checkContextualPermissions($request, $requiredPermissions)) {
                return $this->forbiddenResponse($response, 'Acesso negado ao recurso');
            }
            
        } catch (Exception $e) {
            log_message('error', 'Permission Filter Error: ' . $e->getMessage());
            return $this->forbiddenResponse($response, 'Erro na verificação de permissões');
        }
        
        return $request;
    }
    
    /**
     * Executa após o processamento da requisição
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
    
    /**
     * Verifica se o usuário tem as permissões necessárias
     *
     * @param array $userPermissions
     * @param array $userRoles
     * @param array $requiredPermissions
     * @return bool
     */
    private function hasPermissions(array $userPermissions, array $userRoles, array $requiredPermissions): bool
    {
        // Mapeamento de roles para permissões
        $rolePermissions = [
            'admin' => [
                'users.*', 'products.*', 'orders.*', 'categories.*',
                'customers.*', 'payments.*', 'reports.*', 'analytics.*',
                'settings.*', 'notifications.*'
            ],
            'manager' => [
                'users.read', 'users.update',
                'products.*', 'orders.*', 'categories.*',
                'customers.*', 'payments.read', 'payments.update',
                'reports.read', 'analytics.read'
            ],
            'employee' => [
                'products.read', 'orders.read', 'orders.update',
                'categories.read', 'customers.read', 'customers.create',
                'customers.update'
            ],
            'viewer' => [
                'products.read', 'orders.read', 'categories.read',
                'customers.read', 'reports.read', 'analytics.read'
            ]
        ];
        
        // Coletar todas as permissões do usuário (diretas + por roles)
        $allPermissions = $userPermissions;
        
        foreach ($userRoles as $role) {
            if (isset($rolePermissions[$role])) {
                $allPermissions = array_merge($allPermissions, $rolePermissions[$role]);
            }
        }
        
        // Verificar cada permissão necessária
        foreach ($requiredPermissions as $required) {
            if (!$this->matchesPermission($allPermissions, $required)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verifica se uma permissão específica é atendida
     *
     * @param array $userPermissions
     * @param string $requiredPermission
     * @return bool
     */
    private function matchesPermission(array $userPermissions, string $requiredPermission): bool
    {
        foreach ($userPermissions as $permission) {
            // Verificação exata
            if ($permission === $requiredPermission) {
                return true;
            }
            
            // Verificação com wildcard
            if (str_ends_with($permission, '.*')) {
                $prefix = substr($permission, 0, -2);
                if (str_starts_with($requiredPermission, $prefix . '.')) {
                    return true;
                }
            }
            
            // Verificação com wildcard no final da permissão necessária
            if (str_ends_with($requiredPermission, '.*')) {
                $prefix = substr($requiredPermission, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Verifica permissões contextuais (multi-tenancy, ownership, etc.)
     *
     * @param RequestInterface $request
     * @param array $requiredPermissions
     * @return bool
     */
    private function checkContextualPermissions(RequestInterface $request, array $requiredPermissions): bool
    {
        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();
        $userId = $request->user_id;
        $restaurantId = $request->restaurant_id;
        
        // Extrair ID do recurso da URI (ex: /api/users/123)
        if (preg_match('/\/api\/\w+\/(\d+)/', $uri, $matches)) {
            $resourceId = (int) $matches[1];
            
            // Verificar ownership para recursos específicos
            if (str_contains($uri, '/users/') && !in_array('users.manage_all', $request->permissions ?? [])) {
                // Usuários só podem editar a si mesmos (exceto admins)
                if ($resourceId !== $userId && !in_array('admin', $request->roles ?? [])) {
                    return false;
                }
            }
            
            // Verificar multi-tenancy para todos os recursos
            if ($restaurantId && !$this->verifyResourceOwnership($uri, $resourceId, $restaurantId)) {
                return false;
            }
        }
        
        // Verificações específicas por tipo de operação
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            // Operações de escrita requerem permissões mais específicas
            $writePermissions = array_filter($requiredPermissions, function($perm) {
                return str_contains($perm, '.create') || str_contains($perm, '.update') || str_contains($perm, '.delete');
            });
            
            if (!empty($writePermissions)) {
                // Verificar se o usuário tem permissões de escrita
                $userPermissions = $request->permissions ?? [];
                foreach ($writePermissions as $writePerm) {
                    if (!$this->matchesPermission($userPermissions, $writePerm)) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Verifica se o recurso pertence ao restaurante do usuário
     *
     * @param string $uri
     * @param int $resourceId
     * @param int $restaurantId
     * @return bool
     */
    private function verifyResourceOwnership(string $uri, int $resourceId, int $restaurantId): bool
    {
        try {
            // Mapear URIs para modelos
            $modelMap = [
                '/users/' => 'App\Models\UserModel',
                '/products/' => 'App\Models\ProductModel',
                '/orders/' => 'App\Models\OrderModel',
                '/categories/' => 'App\Models\CategoryModel',
                '/customers/' => 'App\Models\CustomerModel',
                '/payments/' => 'App\Models\PaymentModel',
                '/reports/' => 'App\Models\ReportModel'
            ];
            
            foreach ($modelMap as $pattern => $modelClass) {
                if (str_contains($uri, $pattern)) {
                    if (class_exists($modelClass)) {
                        $model = new $modelClass();
                        $resource = $model->find($resourceId);
                        
                        if (!$resource) {
                            return false;
                        }
                        
                        // Verificar se o recurso pertence ao restaurante
                        if (isset($resource['restaurant_id']) && $resource['restaurant_id'] != $restaurantId) {
                            return false;
                        }
                    }
                    break;
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'Error verifying resource ownership: ' . $e->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * Retorna resposta de não autorizado
     *
     * @param ResponseInterface $response
     * @param string $message
     * @return ResponseInterface
     */
    private function unauthorizedResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        return $response->setStatusCode(401)
                       ->setContentType('application/json')
                       ->setBody(json_encode([
                           'success' => false,
                           'message' => $message,
                           'error_code' => 'UNAUTHORIZED',
                           'timestamp' => date('c')
                       ]));
    }
    
    /**
     * Retorna resposta de proibido
     *
     * @param ResponseInterface $response
     * @param string $message
     * @return ResponseInterface
     */
    private function forbiddenResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        return $response->setStatusCode(403)
                       ->setContentType('application/json')
                       ->setBody(json_encode([
                           'success' => false,
                           'message' => $message,
                           'error_code' => 'FORBIDDEN',
                           'timestamp' => date('c')
                       ]));
    }
}