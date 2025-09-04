<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\UserRoleModel;
use CodeIgniter\Session\Session;

/**
 * Serviço de Autenticação e Autorização
 */
class AuthService
{
    protected $userModel;
    protected $roleModel;
    protected $userRoleModel;
    protected $session;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->userRoleModel = new UserRoleModel();
        $this->session = \Config\Services::session();
    }
    
    // ========================================
    // AUTENTICAÇÃO
    // ========================================
    
    /**
     * Realiza login do usuário
     */
    public function login(string $email, string $password, int $restaurantId, bool $rememberMe = false): array
    {
        try {
            // Busca usuário por email no tenant
            $user = $this->userModel->where('email', $email)
                                   ->where('restaurant_id', $restaurantId)
                                   ->first();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Credenciais inválidas',
                    'error_code' => 'INVALID_CREDENTIALS'
                ];
            }
            
            // Verifica se o usuário está ativo
            if (!$user['is_active']) {
                return [
                    'success' => false,
                    'message' => 'Conta desativada. Entre em contato com o administrador.',
                    'error_code' => 'ACCOUNT_DISABLED'
                ];
            }
            
            // Verifica a senha
            if (!$this->userModel->verifyPassword($user['id'], $password)) {
                // Log da tentativa de login inválida
                $this->logLoginAttempt($user['id'], false, 'Senha incorreta');
                
                return [
                    'success' => false,
                    'message' => 'Credenciais inválidas',
                    'error_code' => 'INVALID_CREDENTIALS'
                ];
            }
            
            // Atualiza último login
            $this->userModel->updateLastLogin($user['id']);
            
            // Cria sessão do usuário
            $this->createUserSession($user, $rememberMe);
            
            // Log da tentativa de login válida
            $this->logLoginAttempt($user['id'], true, 'Login realizado com sucesso');
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => $this->sanitizeUserData($user)
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'Erro no login: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error_code' => 'INTERNAL_ERROR'
            ];
        }
    }
    
    /**
     * Realiza logout do usuário
     */
    public function logout(): bool
    {
        try {
            $userId = $this->session->get('user_id');
            
            if ($userId) {
                // Log do logout
                $this->logLoginAttempt($userId, true, 'Logout realizado');
                
                // Limpa cache de permissões
                $this->userModel->clearPermissionsCache($userId);
            }
            
            // Destrói a sessão
            $this->session->destroy();
            
            // Remove cookie de "lembrar-me" se existir
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
            
            return true;
            
        } catch (\Exception $e) {
            log_message('error', 'Erro no logout: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    public function isAuthenticated(): bool
    {
        $userId = $this->session->get('user_id');
        $restaurantId = $this->session->get('restaurant_id');
        
        if (!$userId || !$restaurantId) {
            return false;
        }
        
        // Verifica se o usuário ainda existe e está ativo
        $user = $this->userModel->find($userId);
        
        return $user && $user['is_active'] && $user['restaurant_id'] == $restaurantId;
    }
    
    /**
     * Obtém o usuário autenticado
     */
    public function getAuthenticatedUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $userId = $this->session->get('user_id');
        $user = $this->userModel->find($userId);
        
        return $user ? $this->sanitizeUserData($user) : null;
    }
    
    /**
     * Cria sessão do usuário
     */
    protected function createUserSession(array $user, bool $rememberMe = false): void
    {
        $sessionData = [
            'user_id' => $user['id'],
            'restaurant_id' => $user['restaurant_id'],
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'user_avatar' => $user['avatar'],
            'is_logged_in' => true,
            'login_time' => time()
        ];
        
        $this->session->set($sessionData);
        
        // Implementa "lembrar-me" se solicitado
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            
            // Salva token no banco (você pode criar uma tabela remember_tokens)
            // Por enquanto, vamos usar um cookie simples
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 dias
        }
    }
    
    /**
     * Remove dados sensíveis do usuário
     */
    protected function sanitizeUserData(array $user): array
    {
        unset($user['password']);
        unset($user['remember_token']);
        
        return $user;
    }
    
    // ========================================
    // AUTORIZAÇÃO
    // ========================================
    
    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public function hasPermission(string $permission, ?int $userId = null): bool
    {
        $userId = $userId ?? $this->session->get('user_id');
        
        if (!$userId) {
            return false;
        }
        
        return $this->userModel->hasPermission($userId, $permission);
    }
    
    /**
     * Verifica se o usuário tem uma role específica
     */
    public function hasRole(string $roleSlug, ?int $userId = null): bool
    {
        $userId = $userId ?? $this->session->get('user_id');
        
        if (!$userId) {
            return false;
        }
        
        return $this->userModel->hasRole($userId, $roleSlug);
    }
    
    /**
     * Verifica se o usuário tem qualquer uma das permissões fornecidas
     */
    public function hasAnyPermission(array $permissions, ?int $userId = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission, $userId)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica se o usuário tem todas as permissões fornecidas
     */
    public function hasAllPermissions(array $permissions, ?int $userId = null): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission, $userId)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verifica se o usuário tem qualquer uma das roles fornecidas
     */
    public function hasAnyRole(array $roles, ?int $userId = null): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role, $userId)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtém todas as permissões do usuário
     */
    public function getUserPermissions(?int $userId = null): array
    {
        $userId = $userId ?? $this->session->get('user_id');
        
        if (!$userId) {
            return [];
        }
        
        return $this->userModel->getUserPermissions($userId);
    }
    
    /**
     * Obtém todas as roles do usuário
     */
    public function getUserRoles(?int $userId = null): array
    {
        $userId = $userId ?? $this->session->get('user_id');
        
        if (!$userId) {
            return [];
        }
        
        return $this->userModel->getUserRoles($userId);
    }
    
    /**
     * Obtém a role principal do usuário
     */
    public function getUserPrimaryRole(?int $userId = null): ?array
    {
        $userId = $userId ?? $this->session->get('user_id');
        
        if (!$userId) {
            return null;
        }
        
        return $this->userModel->getUserPrimaryRole($userId);
    }
    
    // ========================================
    // MIDDLEWARE DE AUTORIZAÇÃO
    // ========================================
    
    /**
     * Middleware para verificar autenticação
     */
    public function requireAuth(): bool
    {
        if (!$this->isAuthenticated()) {
            // Redireciona para login ou retorna erro 401
            return false;
        }
        
        return true;
    }
    
    /**
     * Middleware para verificar permissão
     */
    public function requirePermission(string $permission): bool
    {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if (!$this->hasPermission($permission)) {
            // Retorna erro 403 - Forbidden
            return false;
        }
        
        return true;
    }
    
    /**
     * Middleware para verificar role
     */
    public function requireRole(string $roleSlug): bool
    {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if (!$this->hasRole($roleSlug)) {
            // Retorna erro 403 - Forbidden
            return false;
        }
        
        return true;
    }
    
    /**
     * Middleware para verificar qualquer uma das permissões
     */
    public function requireAnyPermission(array $permissions): bool
    {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if (!$this->hasAnyPermission($permissions)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Middleware para verificar todas as permissões
     */
    public function requireAllPermissions(array $permissions): bool
    {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if (!$this->hasAllPermissions($permissions)) {
            return false;
        }
        
        return true;
    }
    
    // ========================================
    // GESTÃO DE SESSÃO
    // ========================================
    
    /**
     * Atualiza dados da sessão do usuário
     */
    public function updateSessionData(array $data): void
    {
        $this->session->set($data);
    }
    
    /**
     * Obtém dados da sessão
     */
    public function getSessionData(string $key = null)
    {
        if ($key) {
            return $this->session->get($key);
        }
        
        return $this->session->get();
    }
    
    /**
     * Verifica se a sessão expirou
     */
    public function isSessionExpired(): bool
    {
        $loginTime = $this->session->get('login_time');
        
        if (!$loginTime) {
            return true;
        }
        
        // Verifica se passou do tempo limite (ex: 8 horas)
        $sessionTimeout = 8 * 60 * 60; // 8 horas em segundos
        
        return (time() - $loginTime) > $sessionTimeout;
    }
    
    /**
     * Renova a sessão
     */
    public function renewSession(): void
    {
        $this->session->regenerate();
        $this->session->set('login_time', time());
    }
    
    // ========================================
    // LOGS E AUDITORIA
    // ========================================
    
    /**
     * Registra tentativa de login
     */
    protected function logLoginAttempt(int $userId, bool $success, string $message = ''): void
    {
        try {
            // Aqui você pode implementar um sistema de logs mais robusto
            // Por exemplo, salvando em uma tabela login_logs
            
            $logData = [
                'user_id' => $userId,
                'success' => $success,
                'message' => $message,
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Log no arquivo por enquanto
            log_message('info', 'Login attempt: ' . json_encode($logData));
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao registrar log de login: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém IP do cliente
     */
    protected function getClientIP(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    // ========================================
    // UTILITÁRIOS
    // ========================================
    
    /**
     * Gera token seguro
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Valida força da senha
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra maiúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um caractere especial';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password)
        ];
    }
    
    /**
     * Calcula força da senha (0-100)
     */
    protected function calculatePasswordStrength(string $password): int
    {
        $score = 0;
        
        // Comprimento
        if (strlen($password) >= 8) $score += 20;
        if (strlen($password) >= 12) $score += 10;
        if (strlen($password) >= 16) $score += 10;
        
        // Complexidade
        if (preg_match('/[a-z]/', $password)) $score += 15;
        if (preg_match('/[A-Z]/', $password)) $score += 15;
        if (preg_match('/[0-9]/', $password)) $score += 15;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 15;
        
        return min(100, $score);
    }
    
    /**
     * Verifica se o usuário pode acessar um recurso específico
     */
    public function canAccess(string $resource, string $action = 'read', ?int $userId = null): bool
    {
        $permission = $resource . '.' . $action;
        return $this->hasPermission($permission, $userId);
    }
    
    /**
     * Obtém menu do usuário baseado em suas permissões
     */
    public function getUserMenu(?int $userId = null): array
    {
        $userId = $userId ?? $this->session->get('user_id');
        
        if (!$userId) {
            return [];
        }
        
        $permissions = $this->getUserPermissions($userId);
        
        // Aqui você pode definir a estrutura do menu baseada nas permissões
        $menu = [];
        
        // Exemplo de estrutura de menu
        if (in_array('dashboard.read', $permissions)) {
            $menu[] = [
                'title' => 'Dashboard',
                'url' => '/admin/dashboard',
                'icon' => 'dashboard'
            ];
        }
        
        if (in_array('users.read', $permissions)) {
            $menu[] = [
                'title' => 'Usuários',
                'url' => '/admin/users',
                'icon' => 'users',
                'submenu' => []
            ];
            
            if (in_array('users.manage_roles', $permissions)) {
                $menu[count($menu) - 1]['submenu'][] = [
                    'title' => 'Funções',
                    'url' => '/admin/users/roles'
                ];
            }
        }
        
        // Continue adicionando outros módulos...
        
        return $menu;
    }
}