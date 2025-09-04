<?php

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;

class UserModel extends BaseMultiTenantModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'restaurant_id',
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'phone',
        'avatar',
        'is_active',
        'last_login_at',
        'email_verified_at',
        'remember_token',
        'settings'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $dateFormat = 'datetime';
    
    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[255]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[8]',
        'role' => 'in_list[owner,manager,employee,cashier]',
        'restaurant_id' => 'required|integer'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Nome é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres'
        ],
        'email' => [
            'required' => 'Email é obrigatório',
            'valid_email' => 'Email deve ter um formato válido',
            'is_unique' => 'Este email já está cadastrado'
        ],
        'password' => [
            'required' => 'Senha é obrigatória',
            'min_length' => 'Senha deve ter pelo menos 8 caracteres'
        ],
        'role' => [
            'in_list' => 'Função deve ser: owner, manager, employee ou cashier'
        ]
    ];
    
    protected $beforeInsert = ['hashPassword', 'setDefaults'];
    protected $beforeUpdate = ['hashPassword'];
    
    // Roles básicas (mantidas para compatibilidade)
    protected $basicRoles = ['owner', 'manager', 'employee', 'cashier'];
    
    // Cache de permissões do usuário
    protected $userPermissionsCache = [];
    
    /**
     * Hash da senha antes de inserir/atualizar
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }
    
    /**
     * Define valores padrão
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        
        if (!isset($data['data']['role'])) {
            $data['data']['role'] = 'employee';
        }
        
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = json_encode([
                'notifications' => true,
                'language' => 'pt-BR',
                'theme' => 'light'
            ]);
        }
        
        return $data;
    }
    
    /**
     * Busca usuário por email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
    
    /**
     * Verifica se email já existe no tenant
     */
    public function emailExistsInTenant(string $email, ?int $excludeId = null): bool
    {
        $query = $this->where('email', $email);
        
        if ($excludeId) {
            $query->where('id !=', $excludeId);
        }
        
        return $query->countAllResults() > 0;
    }
    
    /**
     * Busca usuários por função
     */
    public function findByRole(string $role): array
    {
        return $this->where('role', $role)
                   ->where('is_active', 1)
                   ->findAll();
    }
    
    /**
     * Busca usuários ativos
     */
    public function findActive(): array
    {
        return $this->where('is_active', 1)->findAll();
    }
    
    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $user = $this->find($userId);
        if (!$user || !$user['is_active']) {
            return false;
        }
        
        // Verifica permissões customizadas primeiro
        $customPermissions = json_decode($user['permissions'] ?? '[]', true);
        if (in_array($permission, $customPermissions)) {
            return true;
        }
        
        // Verifica permissões através do sistema de roles
        $userRoleModel = new \App\Models\UserRoleModel();
        return $userRoleModel->userHasPermission($userId, $permission);
    }
    
    /**
     * Obtém todas as permissões de um usuário
     */
    public function getUserPermissions(int $userId): array
    {
        // Verifica cache
        if (isset($this->userPermissionsCache[$userId])) {
            return $this->userPermissionsCache[$userId];
        }
        
        $user = $this->find($userId);
        if (!$user || !$user['is_active']) {
            return [];
        }
        
        // Permissões customizadas do usuário
        $customPermissions = json_decode($user['permissions'] ?? '[]', true);
        
        // Permissões através das roles
        $userRoleModel = new \App\Models\UserRoleModel();
        $rolePermissions = $userRoleModel->getUserPermissions($userId);
        
        // Combina e remove duplicatas
        $allPermissions = array_unique(array_merge($rolePermissions, $customPermissions));
        
        // Armazena no cache
        $this->userPermissionsCache[$userId] = $allPermissions;
        
        return $allPermissions;
    }
    
    /**
     * Obtém roles de um usuário
     */
    public function getUserRoles(int $userId): array
    {
        $userRoleModel = new \App\Models\UserRoleModel();
        return $userRoleModel->getUserRoles($userId);
    }
    
    /**
     * Obtém role principal do usuário
     */
    public function getUserPrimaryRole(int $userId): ?array
    {
        $userRoleModel = new \App\Models\UserRoleModel();
        return $userRoleModel->getUserPrimaryRole($userId);
    }
    
    /**
     * Verifica se usuário tem role específica
     */
    public function hasRole(int $userId, string $roleSlug): bool
    {
        $userRoleModel = new \App\Models\UserRoleModel();
        return $userRoleModel->userHasRoleBySlug($userId, $roleSlug);
    }
    
    /**
     * Atribui role a usuário
     */
    public function assignRole(int $userId, int $roleId, ?int $assignedBy = null): bool
    {
        $userRoleModel = new \App\Models\UserRoleModel();
        $result = $userRoleModel->assignRole($userId, $roleId, $assignedBy);
        
        // Limpa cache de permissões
        unset($this->userPermissionsCache[$userId]);
        
        return $result;
    }
    
    /**
     * Remove role de usuário
     */
    public function removeRole(int $userId, int $roleId): bool
    {
        $userRoleModel = new \App\Models\UserRoleModel();
        $result = $userRoleModel->removeRole($userId, $roleId);
        
        // Limpa cache de permissões
        unset($this->userPermissionsCache[$userId]);
        
        return $result;
    }
    
    /**
     * Atualiza roles de um usuário
     */
    public function updateUserRoles(int $userId, array $roleIds, ?int $assignedBy = null): bool
    {
        $userRoleModel = new \App\Models\UserRoleModel();
        $result = $userRoleModel->updateUserRoles($userId, $roleIds, $assignedBy);
        
        // Limpa cache de permissões
        unset($this->userPermissionsCache[$userId]);
        
        return $result;
    }
    
    /**
     * Adiciona permissão customizada ao usuário
     */
    public function addCustomPermission(int $userId, string $permission): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        
        $customPermissions = json_decode($user['permissions'] ?? '[]', true);
        if (!in_array($permission, $customPermissions)) {
            $customPermissions[] = $permission;
            $result = $this->update($userId, ['permissions' => json_encode($customPermissions)]);
            
            // Limpa cache de permissões
            unset($this->userPermissionsCache[$userId]);
            
            return $result;
        }
        
        return true;
    }
    
    /**
     * Remove permissão customizada do usuário
     */
    public function removeCustomPermission(int $userId, string $permission): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        
        $customPermissions = json_decode($user['permissions'] ?? '[]', true);
        $customPermissions = array_filter($customPermissions, function($p) use ($permission) {
            return $p !== $permission;
        });
        
        $result = $this->update($userId, ['permissions' => json_encode(array_values($customPermissions))]);
        
        // Limpa cache de permissões
        unset($this->userPermissionsCache[$userId]);
        
        return $result;
    }
    
    /**
     * Define permissões customizadas do usuário
     */
    public function setCustomPermissions(int $userId, array $permissions): bool
    {
        $result = $this->update($userId, ['permissions' => json_encode($permissions)]);
        
        // Limpa cache de permissões
        unset($this->userPermissionsCache[$userId]);
        
        return $result;
    }
    
    /**
     * Limpa cache de permissões
     */
    public function clearPermissionsCache(?int $userId = null): void
    {
        if ($userId) {
            unset($this->userPermissionsCache[$userId]);
        } else {
            $this->userPermissionsCache = [];
        }
    }
    
    /**
     * Atualiza último login
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Ativa/desativa usuário
     */
    public function toggleActive(int $userId): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        
        return $this->update($userId, [
            'is_active' => $user['is_active'] ? 0 : 1
        ]);
    }
    
    /**
     * Atualiza configurações do usuário
     */
    public function updateSettings(int $userId, array $settings): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        
        $currentSettings = json_decode($user['settings'] ?? '{}', true);
        $newSettings = array_merge($currentSettings, $settings);
        
        return $this->update($userId, [
            'settings' => json_encode($newSettings)
        ]);
    }
    
    /**
     * Obtém configurações do usuário
     */
    public function getSettings(int $userId): array
    {
        $user = $this->find($userId);
        if (!$user) {
            return [];
        }
        
        return json_decode($user['settings'] ?? '{}', true);
    }
    
    /**
     * Verifica senha
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Atualiza senha
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        return $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }
    
    /**
     * Obtém estatísticas de usuários
     */
    public function getStats(): array
    {
        return [
            'total' => $this->countAllResults(false),
            'active' => $this->where('is_active', 1)->countAllResults(false),
            'inactive' => $this->where('is_active', 0)->countAllResults(false),
            'owners' => $this->where('role', 'owner')->countAllResults(false),
            'managers' => $this->where('role', 'manager')->countAllResults(false),
            'employees' => $this->where('role', 'employee')->countAllResults(false),
            'cashiers' => $this->where('role', 'cashier')->countAllResults(false),
            'created_today' => $this->getCreatedToday(),
            'created_this_week' => $this->getCreatedThisWeek(),
            'created_this_month' => $this->getCreatedThisMonth()
        ];
    }
    
    /**
     * Obtém usuários criados recentemente
     */
    public function getRecentUsers(int $limit = 10): array
    {
        return $this->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Busca usuários com filtros
     */
    public function searchUsers(array $filters = []): array
    {
        $query = $this;
        
        if (!empty($filters['name'])) {
            $query = $query->like('name', $filters['name']);
        }
        
        if (!empty($filters['email'])) {
            $query = $query->like('email', $filters['email']);
        }
        
        if (!empty($filters['role'])) {
            $query = $query->where('role', $filters['role']);
        }
        
        if (isset($filters['is_active'])) {
            $query = $query->where('is_active', $filters['is_active']);
        }
        
        return $query->findAll();
    }
    
    /**
     * Obtém roles disponíveis (do sistema de roles)
     */
    public function getAvailableRoles(): array
    {
        $roleModel = new \App\Models\RoleModel();
        return $roleModel->getActiveRoles();
    }
    
    /**
     * Obtém permissões de uma role específica
     */
    public function getRolePermissions(int $roleId): array
    {
        $roleModel = new \App\Models\RoleModel();
        return $roleModel->getRolePermissions($roleId);
    }
    
    /**
     * Obtém roles básicas (compatibilidade)
     */
    public function getBasicRoles(): array
    {
        return $this->basicRoles;
    }
    
    /**
     * Verifica se é uma role básica
     */
    public function isBasicRole(string $role): bool
    {
        return in_array($role, $this->basicRoles);
    }
}