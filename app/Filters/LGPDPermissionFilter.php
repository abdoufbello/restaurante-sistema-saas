<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Filtro de Permissões LGPD
 * 
 * Verifica se o usuário tem permissões adequadas para acessar
 * funcionalidades administrativas do sistema LGPD
 */
class LGPDPermissionFilter implements FilterInterface
{
    /**
     * Executa o filtro antes da requisição
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = Services::session();
        $response = Services::response();
        
        // Verifica se o usuário está autenticado
        if (!$session->get('isLoggedIn')) {
            return $response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Usuário não autenticado',
                'error_code' => 'UNAUTHORIZED'
            ]);
        }
        
        // Obtém informações do usuário
        $userId = $session->get('user_id');
        $userRole = $session->get('user_role');
        $isAdmin = $session->get('is_admin');
        
        // Verifica permissões baseadas no endpoint
        $uri = $request->getUri();
        $path = $uri->getPath();
        
        // Endpoints que requerem permissões de administrador
        $adminEndpoints = [
            '/api/lgpd/audit/',
            '/api/lgpd/admin/',
            '/api/lgpd/compliance-status'
        ];
        
        // Verifica se é um endpoint administrativo
        $isAdminEndpoint = false;
        foreach ($adminEndpoints as $adminPath) {
            if (strpos($path, $adminPath) !== false) {
                $isAdminEndpoint = true;
                break;
            }
        }
        
        // Se for endpoint administrativo, verifica permissões
        if ($isAdminEndpoint) {
            if (!$this->hasAdminPermission($userId, $userRole, $isAdmin)) {
                return $response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Acesso negado. Permissões de administrador necessárias.',
                    'error_code' => 'FORBIDDEN',
                    'required_permission' => 'lgpd_admin'
                ]);
            }
        }
        
        // Verifica permissões específicas para operações de dados
        if ($this->isDataOperationEndpoint($path)) {
            if (!$this->hasDataOperationPermission($userId, $userRole, $request)) {
                return $response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Acesso negado. Permissões insuficientes para operação de dados.',
                    'error_code' => 'FORBIDDEN',
                    'required_permission' => 'data_operation'
                ]);
            }
        }
        
        // Log da tentativa de acesso
        $this->logAccessAttempt($userId, $path, $request->getMethod(), true);
        
        return null; // Permite continuar
    }
    
    /**
     * Executa o filtro após a requisição
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Adiciona headers de segurança LGPD
        $response->setHeader('X-LGPD-Compliance', 'enabled');
        $response->setHeader('X-Data-Protection', 'active');
        
        return $response;
    }
    
    /**
     * Verifica se o usuário tem permissões de administrador
     */
    private function hasAdminPermission($userId, $userRole, $isAdmin): bool
    {
        // Verifica se é administrador global
        if ($isAdmin === true || $userRole === 'admin') {
            return true;
        }
        
        // Verifica permissões específicas LGPD no banco de dados
        $db = \Config\Database::connect();
        
        $query = $db->query(
            "SELECT COUNT(*) as count FROM user_permissions up 
             JOIN permissions p ON up.permission_id = p.id 
             WHERE up.user_id = ? AND p.name IN ('lgpd_admin', 'data_protection_officer')",
            [$userId]
        );
        
        $result = $query->getRow();
        return $result && $result->count > 0;
    }
    
    /**
     * Verifica se é um endpoint de operação de dados
     */
    private function isDataOperationEndpoint(string $path): bool
    {
        $dataEndpoints = [
            '/api/lgpd/data-portability/',
            '/api/lgpd/data-erasure/',
            '/api/lgpd/data-access-check'
        ];
        
        foreach ($dataEndpoints as $endpoint) {
            if (strpos($path, $endpoint) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica permissões para operações de dados
     */
    private function hasDataOperationPermission($userId, $userRole, RequestInterface $request): bool
    {
        // Administradores sempre têm acesso
        if ($userRole === 'admin') {
            return true;
        }
        
        // Para operações de dados próprios, verifica se o data_subject corresponde ao usuário
        $dataSubject = $this->extractDataSubjectFromRequest($request);
        
        if ($dataSubject) {
            // Verifica se o usuário está tentando acessar seus próprios dados
            $db = \Config\Database::connect();
            
            $query = $db->query(
                "SELECT email, cpf FROM users WHERE id = ?",
                [$userId]
            );
            
            $user = $query->getRow();
            
            if ($user && ($user->email === $dataSubject || $user->cpf === $dataSubject)) {
                return true;
            }
        }
        
        // Verifica permissões específicas no banco
        $db = \Config\Database::connect();
        
        $query = $db->query(
            "SELECT COUNT(*) as count FROM user_permissions up 
             JOIN permissions p ON up.permission_id = p.id 
             WHERE up.user_id = ? AND p.name IN ('data_operator', 'customer_service')",
            [$userId]
        );
        
        $result = $query->getRow();
        return $result && $result->count > 0;
    }
    
    /**
     * Extrai o data_subject da requisição
     */
    private function extractDataSubjectFromRequest(RequestInterface $request): ?string
    {
        // Tenta extrair do path
        $uri = $request->getUri();
        $segments = explode('/', trim($uri->getPath(), '/'));
        
        // Procura por padrões conhecidos
        $patterns = [
            '/api/lgpd/data-portability/',
            '/api/lgpd/data-erasure/',
            '/api/lgpd/consent/'
        ];
        
        foreach ($patterns as $pattern) {
            if (strpos($uri->getPath(), $pattern) !== false) {
                // O data_subject geralmente é o próximo segmento após o padrão
                $patternSegments = explode('/', trim($pattern, '/'));
                $patternIndex = count($patternSegments) - 1;
                
                if (isset($segments[$patternIndex + 1])) {
                    return urldecode($segments[$patternIndex + 1]);
                }
            }
        }
        
        // Tenta extrair do body da requisição
        if ($request->getMethod() === 'POST' || $request->getMethod() === 'PUT') {
            $body = $request->getJSON(true);
            if (isset($body['data_subject'])) {
                return $body['data_subject'];
            }
        }
        
        // Tenta extrair dos parâmetros GET
        $dataSubject = $request->getGet('data_subject');
        if ($dataSubject) {
            return $dataSubject;
        }
        
        return null;
    }
    
    /**
     * Registra tentativa de acesso nos logs de auditoria
     */
    private function logAccessAttempt($userId, $path, $method, $success): void
    {
        try {
            $db = \Config\Database::connect();
            
            $data = [
                'event_type' => 'lgpd_access_attempt',
                'user_id' => $userId,
                'operation' => $method,
                'description' => "Tentativa de acesso ao endpoint LGPD: {$path}",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'request_data' => json_encode([
                    'path' => $path,
                    'method' => $method,
                    'success' => $success
                ]),
                'severity' => $success ? 'low' : 'medium',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('lgpd_audit_logs')->insert($data);
            
        } catch (\Exception $e) {
            // Log silencioso - não deve interromper a requisição
            log_message('error', 'Erro ao registrar log de acesso LGPD: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica rate limiting para operações sensíveis
     */
    private function checkRateLimit($userId, $operation): bool
    {
        $cache = \Config\Services::cache();
        $key = "lgpd_rate_limit_{$userId}_{$operation}";
        
        $attempts = $cache->get($key) ?? 0;
        
        // Limites por operação (por hora)
        $limits = [
            'data_portability' => 5,
            'data_erasure' => 3,
            'consent_revoke' => 10,
            'policy_access' => 50
        ];
        
        $limit = $limits[$operation] ?? 20;
        
        if ($attempts >= $limit) {
            return false;
        }
        
        // Incrementa contador
        $cache->save($key, $attempts + 1, 3600); // 1 hora
        
        return true;
    }
}