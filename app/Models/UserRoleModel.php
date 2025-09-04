<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Relação Usuário-Role com Multi-Tenancy
 */
class UserRoleModel extends BaseMultiTenantModel
{
    protected $table = 'user_roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'user_id',
        'role_id',
        'assigned_by',
        'assigned_at',
        'expires_at',
        'is_active',
        'notes',
        'settings'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'user_id' => 'required|integer',
        'role_id' => 'required|integer',
        'assigned_by' => 'permit_empty|integer',
        'assigned_at' => 'permit_empty|valid_date',
        'expires_at' => 'permit_empty|valid_date',
        'is_active' => 'permit_empty|in_list[0,1]',
        'notes' => 'permit_empty|max_length[500]'
    ];
    
    protected $validationMessages = [
        'user_id' => [
            'required' => 'ID do usuário é obrigatório',
            'integer' => 'ID do usuário deve ser um número inteiro'
        ],
        'role_id' => [
            'required' => 'ID da função é obrigatório',
            'integer' => 'ID da função deve ser um número inteiro'
        ],
        'assigned_at' => [
            'valid_date' => 'Data de atribuição deve ser uma data válida'
        ],
        'expires_at' => [
            'valid_date' => 'Data de expiração deve ser uma data válida'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'validateUniqueAssignment'];
    protected $beforeUpdate = ['updateTimestamps', 'validateUniqueAssignment'];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        
        if (!isset($data['data']['assigned_at'])) {
            $data['data']['assigned_at'] = date('Y-m-d H:i:s');
        }
        
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = json_encode([]);
        }
        
        return $data;
    }
    
    /**
     * Valida se a atribuição é única (usuário não pode ter a mesma role duas vezes ativa)
     */
    protected function validateUniqueAssignment(array $data): array
    {
        $userId = $data['data']['user_id'];
        $roleId = $data['data']['role_id'];
        $excludeId = $data['id'] ?? null;
        
        $builder = $this->where('user_id', $userId)
                       ->where('role_id', $roleId)
                       ->where('is_active', 1);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        if ($builder->countAllResults() > 0) {
            throw new \Exception('Usuário já possui esta função ativa');
        }
        
        return $data;
    }
    
    /**
     * Atualiza timestamps
     */
    protected function updateTimestamps(array $data): array
    {
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Obtém roles de um usuário
     */
    public function getUserRoles(int $userId, bool $activeOnly = true): array
    {
        $builder = $this->select('user_roles.*, roles.name as role_name, roles.slug as role_slug, roles.level as role_level, roles.color as role_color, roles.icon as role_icon')
                       ->join('roles', 'roles.id = user_roles.role_id')
                       ->where('user_roles.user_id', $userId);
        
        if ($activeOnly) {
            $builder->where('user_roles.is_active', 1)
                   ->where('roles.is_active', 1);
        }
        
        // Verifica expiração
        $builder->groupStart()
               ->where('user_roles.expires_at IS NULL')
               ->orWhere('user_roles.expires_at >', date('Y-m-d H:i:s'))
               ->groupEnd();
        
        return $builder->orderBy('roles.level', 'DESC')->findAll();
    }
    
    /**
     * Obtém usuários de uma role
     */
    public function getRoleUsers(int $roleId, bool $activeOnly = true): array
    {
        $builder = $this->select('user_roles.*, users.name as user_name, users.email as user_email, users.avatar as user_avatar')
                       ->join('users', 'users.id = user_roles.user_id')
                       ->where('user_roles.role_id', $roleId);
        
        if ($activeOnly) {
            $builder->where('user_roles.is_active', 1)
                   ->where('users.is_active', 1);
        }
        
        // Verifica expiração
        $builder->groupStart()
               ->where('user_roles.expires_at IS NULL')
               ->orWhere('user_roles.expires_at >', date('Y-m-d H:i:s'))
               ->groupEnd();
        
        return $builder->orderBy('users.name', 'ASC')->findAll();
    }
    
    /**
     * Atribui role a usuário
     */
    public function assignRole(int $userId, int $roleId, ?int $assignedBy = null, ?string $expiresAt = null, ?string $notes = null): bool
    {
        // Verifica se já existe atribuição ativa
        $existing = $this->where('user_id', $userId)
                        ->where('role_id', $roleId)
                        ->where('is_active', 1)
                        ->first();
        
        if ($existing) {
            return false; // Já existe
        }
        
        $data = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'assigned_by' => $assignedBy,
            'assigned_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'is_active' => 1,
            'notes' => $notes
        ];
        
        return $this->insert($data) !== false;
    }
    
    /**
     * Remove role de usuário
     */
    public function removeRole(int $userId, int $roleId): bool
    {
        return $this->where('user_id', $userId)
                   ->where('role_id', $roleId)
                   ->where('is_active', 1)
                   ->set(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')])
                   ->update();
    }
    
    /**
     * Atualiza roles de um usuário (remove todas e adiciona as novas)
     */
    public function updateUserRoles(int $userId, array $roleIds, ?int $assignedBy = null): bool
    {
        // Remove todas as roles ativas do usuário
        $this->where('user_id', $userId)
            ->where('is_active', 1)
            ->set(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')])
            ->update();
        
        // Adiciona as novas roles
        foreach ($roleIds as $roleId) {
            $this->assignRole($userId, $roleId, $assignedBy);
        }
        
        return true;
    }
    
    /**
     * Verifica se usuário tem role específica
     */
    public function userHasRole(int $userId, int $roleId): bool
    {
        return $this->where('user_id', $userId)
                   ->where('role_id', $roleId)
                   ->where('is_active', 1)
                   ->groupStart()
                   ->where('expires_at IS NULL')
                   ->orWhere('expires_at >', date('Y-m-d H:i:s'))
                   ->groupEnd()
                   ->countAllResults() > 0;
    }
    
    /**
     * Verifica se usuário tem role por slug
     */
    public function userHasRoleBySlug(int $userId, string $roleSlug): bool
    {
        return $this->join('roles', 'roles.id = user_roles.role_id')
                   ->where('user_roles.user_id', $userId)
                   ->where('roles.slug', $roleSlug)
                   ->where('user_roles.is_active', 1)
                   ->where('roles.is_active', 1)
                   ->groupStart()
                   ->where('user_roles.expires_at IS NULL')
                   ->orWhere('user_roles.expires_at >', date('Y-m-d H:i:s'))
                   ->groupEnd()
                   ->countAllResults() > 0;
    }
    
    /**
     * Obtém role principal do usuário (maior nível)
     */
    public function getUserPrimaryRole(int $userId): ?array
    {
        $roles = $this->getUserRoles($userId);
        
        if (empty($roles)) {
            return null;
        }
        
        // Retorna a role com maior nível
        usort($roles, function($a, $b) {
            return $b['role_level'] - $a['role_level'];
        });
        
        return $roles[0];
    }
    
    /**
     * Obtém todas as permissões de um usuário (através de suas roles)
     */
    public function getUserPermissions(int $userId): array
    {
        $userRoles = $this->getUserRoles($userId);
        $roleModel = new \App\Models\RoleModel();
        $allPermissions = [];
        
        foreach ($userRoles as $userRole) {
            $rolePermissions = $roleModel->getRolePermissions($userRole['role_id']);
            $allPermissions = array_merge($allPermissions, $rolePermissions);
        }
        
        return array_unique($allPermissions);
    }
    
    /**
     * Verifica se usuário tem permissão específica
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        $userPermissions = $this->getUserPermissions($userId);
        return in_array($permission, $userPermissions);
    }
    
    /**
     * Obtém atribuições expiradas
     */
    public function getExpiredAssignments(): array
    {
        return $this->select('user_roles.*, users.name as user_name, users.email as user_email, roles.name as role_name')
                   ->join('users', 'users.id = user_roles.user_id')
                   ->join('roles', 'roles.id = user_roles.role_id')
                   ->where('user_roles.is_active', 1)
                   ->where('user_roles.expires_at IS NOT NULL')
                   ->where('user_roles.expires_at <=', date('Y-m-d H:i:s'))
                   ->findAll();
    }
    
    /**
     * Desativa atribuições expiradas
     */
    public function deactivateExpiredAssignments(): int
    {
        $affected = $this->where('is_active', 1)
                        ->where('expires_at IS NOT NULL')
                        ->where('expires_at <=', date('Y-m-d H:i:s'))
                        ->set(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')])
                        ->update();
        
        return $this->db->affectedRows();
    }
    
    /**
     * Obtém atribuições que expiram em breve
     */
    public function getExpiringAssignments(int $days = 7): array
    {
        $expirationDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        return $this->select('user_roles.*, users.name as user_name, users.email as user_email, roles.name as role_name')
                   ->join('users', 'users.id = user_roles.user_id')
                   ->join('roles', 'roles.id = user_roles.role_id')
                   ->where('user_roles.is_active', 1)
                   ->where('user_roles.expires_at IS NOT NULL')
                   ->where('user_roles.expires_at >', date('Y-m-d H:i:s'))
                   ->where('user_roles.expires_at <=', $expirationDate)
                   ->orderBy('user_roles.expires_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Estende expiração de atribuição
     */
    public function extendAssignment(int $assignmentId, string $newExpirationDate): bool
    {
        return $this->update($assignmentId, [
            'expires_at' => $newExpirationDate,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Busca avançada de atribuições
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this->select('user_roles.*, users.name as user_name, users.email as user_email, roles.name as role_name, roles.slug as role_slug')
                       ->join('users', 'users.id = user_roles.user_id')
                       ->join('roles', 'roles.id = user_roles.role_id');
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('users.name', $filters['search'])
                             ->orLike('users.email', $filters['search'])
                             ->orLike('roles.name', $filters['search'])
                             ->orLike('user_roles.notes', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['user_id'])) {
            $builder = $builder->where('user_roles.user_id', $filters['user_id']);
        }
        
        if (!empty($filters['role_id'])) {
            $builder = $builder->where('user_roles.role_id', $filters['role_id']);
        }
        
        if (isset($filters['is_active'])) {
            $builder = $builder->where('user_roles.is_active', $filters['is_active']);
        }
        
        if (!empty($filters['assigned_by'])) {
            $builder = $builder->where('user_roles.assigned_by', $filters['assigned_by']);
        }
        
        if (!empty($filters['expires_soon'])) {
            $days = $filters['expires_soon'];
            $expirationDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));
            $builder = $builder->where('user_roles.expires_at IS NOT NULL')
                             ->where('user_roles.expires_at >', date('Y-m-d H:i:s'))
                             ->where('user_roles.expires_at <=', $expirationDate);
        }
        
        if (!empty($filters['expired'])) {
            $builder = $builder->where('user_roles.expires_at IS NOT NULL')
                             ->where('user_roles.expires_at <=', date('Y-m-d H:i:s'));
        }
        
        $orderBy = $filters['order_by'] ?? 'user_roles.created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $builder->orderBy($orderBy, $orderDir)->findAll();
    }
    
    /**
     * Obtém estatísticas das atribuições
     */
    public function getAssignmentStats(): array
    {
        $stats = [];
        
        $stats['total_assignments'] = $this->countAllResults();
        $stats['active_assignments'] = $this->where('is_active', 1)->countAllResults();
        $stats['expired_assignments'] = $this->where('is_active', 1)
                                            ->where('expires_at IS NOT NULL')
                                            ->where('expires_at <=', date('Y-m-d H:i:s'))
                                            ->countAllResults();
        
        // Atribuições por role
        $roleStats = $this->select('roles.name as role_name, COUNT(*) as count')
                         ->join('roles', 'roles.id = user_roles.role_id')
                         ->where('user_roles.is_active', 1)
                         ->groupBy('user_roles.role_id')
                         ->orderBy('count', 'DESC')
                         ->findAll();
        
        $stats['assignments_by_role'] = [];
        foreach ($roleStats as $role) {
            $stats['assignments_by_role'][$role['role_name']] = $role['count'];
        }
        
        // Usuários com mais roles
        $userStats = $this->select('users.name as user_name, COUNT(*) as count')
                         ->join('users', 'users.id = user_roles.user_id')
                         ->where('user_roles.is_active', 1)
                         ->groupBy('user_roles.user_id')
                         ->orderBy('count', 'DESC')
                         ->limit(10)
                         ->findAll();
        
        $stats['users_with_most_roles'] = $userStats;
        
        return $stats;
    }
    
    /**
     * Exporta atribuições para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $assignments = $this->advancedSearch($filters);
        
        $csv = "Usuário,Email,Função,Atribuído Por,Atribuído Em,Expira Em,Ativo,Notas\n";
        
        foreach ($assignments as $assignment) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $assignment['user_name'],
                $assignment['user_email'],
                $assignment['role_name'],
                $assignment['assigned_by'] ?? 'Sistema',
                $assignment['assigned_at'],
                $assignment['expires_at'] ?? 'Nunca',
                $assignment['is_active'] ? 'Sim' : 'Não',
                str_replace(["\n", "\r", ','], [' ', ' ', ';'], $assignment['notes'] ?? '')
            );
        }
        
        return $csv;
    }
    
    /**
     * Clona atribuições de um usuário para outro
     */
    public function cloneUserRoles(int $fromUserId, int $toUserId, ?int $assignedBy = null): bool
    {
        $sourceRoles = $this->getUserRoles($fromUserId);
        
        foreach ($sourceRoles as $role) {
            $this->assignRole(
                $toUserId,
                $role['role_id'],
                $assignedBy,
                $role['expires_at'],
                'Clonado do usuário ID: ' . $fromUserId
            );
        }
        
        return true;
    }
    
    /**
     * Obtém histórico de atribuições de um usuário
     */
    public function getUserRoleHistory(int $userId): array
    {
        return $this->select('user_roles.*, roles.name as role_name, roles.slug as role_slug, assigners.name as assigned_by_name')
                   ->join('roles', 'roles.id = user_roles.role_id')
                   ->join('users as assigners', 'assigners.id = user_roles.assigned_by', 'left')
                   ->where('user_roles.user_id', $userId)
                   ->orderBy('user_roles.created_at', 'DESC')
                   ->findAll();
    }
}