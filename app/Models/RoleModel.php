<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Roles (Funções) com Multi-Tenancy
 */
class RoleModel extends BaseMultiTenantModel
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'name',
        'slug',
        'description',
        'permissions',
        'is_system_role',
        'is_active',
        'level',
        'color',
        'icon',
        'settings',
        'created_by',
        'updated_by'
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
        'name' => 'required|min_length[2]|max_length[100]',
        'slug' => 'required|min_length[2]|max_length[100]|alpha_dash',
        'description' => 'permit_empty|max_length[500]',
        'permissions' => 'permit_empty',
        'is_system_role' => 'permit_empty|in_list[0,1]',
        'is_active' => 'permit_empty|in_list[0,1]',
        'level' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[100]',
        'color' => 'permit_empty|max_length[7]',
        'icon' => 'permit_empty|max_length[50]'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Nome da função é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome deve ter no máximo 100 caracteres'
        ],
        'slug' => [
            'required' => 'Slug é obrigatório',
            'min_length' => 'Slug deve ter pelo menos 2 caracteres',
            'max_length' => 'Slug deve ter no máximo 100 caracteres',
            'alpha_dash' => 'Slug deve conter apenas letras, números, hífens e underscores'
        ],
        'level' => [
            'integer' => 'Nível deve ser um número inteiro',
            'greater_than_equal_to' => 'Nível deve ser pelo menos 1',
            'less_than_equal_to' => 'Nível deve ser no máximo 100'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generateSlug', 'validatePermissions'];
    protected $beforeUpdate = ['updateTimestamps', 'validatePermissions'];
    
    // Permissões disponíveis no sistema
    protected $availablePermissions = [
        // Gestão de Usuários
        'users.create' => 'Criar usuários',
        'users.read' => 'Visualizar usuários',
        'users.update' => 'Editar usuários',
        'users.delete' => 'Excluir usuários',
        'users.manage_roles' => 'Gerenciar funções de usuários',
        
        // Gestão de Funcionários
        'employees.create' => 'Criar funcionários',
        'employees.read' => 'Visualizar funcionários',
        'employees.update' => 'Editar funcionários',
        'employees.delete' => 'Excluir funcionários',
        'employees.manage_schedule' => 'Gerenciar horários',
        
        // Gestão de Clientes
        'customers.create' => 'Criar clientes',
        'customers.read' => 'Visualizar clientes',
        'customers.update' => 'Editar clientes',
        'customers.delete' => 'Excluir clientes',
        'customers.manage_loyalty' => 'Gerenciar programa de fidelidade',
        
        // Gestão de Cardápio
        'dishes.create' => 'Criar pratos',
        'dishes.read' => 'Visualizar pratos',
        'dishes.update' => 'Editar pratos',
        'dishes.delete' => 'Excluir pratos',
        'dishes.manage_prices' => 'Gerenciar preços',
        
        // Gestão de Categorias
        'categories.create' => 'Criar categorias',
        'categories.read' => 'Visualizar categorias',
        'categories.update' => 'Editar categorias',
        'categories.delete' => 'Excluir categorias',
        
        // Gestão de Pedidos
        'orders.create' => 'Criar pedidos',
        'orders.read' => 'Visualizar pedidos',
        'orders.update' => 'Editar pedidos',
        'orders.delete' => 'Excluir pedidos',
        'orders.manage_status' => 'Gerenciar status de pedidos',
        'orders.refund' => 'Processar reembolsos',
        
        // Gestão de Pagamentos
        'payments.create' => 'Processar pagamentos',
        'payments.read' => 'Visualizar pagamentos',
        'payments.update' => 'Editar pagamentos',
        'payments.refund' => 'Processar reembolsos',
        'payments.manage_methods' => 'Gerenciar métodos de pagamento',
        
        // Gestão de Reservas
        'reservations.create' => 'Criar reservas',
        'reservations.read' => 'Visualizar reservas',
        'reservations.update' => 'Editar reservas',
        'reservations.delete' => 'Excluir reservas',
        'reservations.manage_tables' => 'Gerenciar mesas',
        
        // Gestão de Estoque
        'inventory.create' => 'Criar itens de estoque',
        'inventory.read' => 'Visualizar estoque',
        'inventory.update' => 'Editar estoque',
        'inventory.delete' => 'Excluir itens de estoque',
        'inventory.manage_suppliers' => 'Gerenciar fornecedores',
        'inventory.adjust_stock' => 'Ajustar estoque',
        
        // Gestão de Fornecedores
        'suppliers.create' => 'Criar fornecedores',
        'suppliers.read' => 'Visualizar fornecedores',
        'suppliers.update' => 'Editar fornecedores',
        'suppliers.delete' => 'Excluir fornecedores',
        'suppliers.manage_contracts' => 'Gerenciar contratos',
        
        // Gestão de Mesas
        'tables.create' => 'Criar mesas',
        'tables.read' => 'Visualizar mesas',
        'tables.update' => 'Editar mesas',
        'tables.delete' => 'Excluir mesas',
        'tables.manage_layout' => 'Gerenciar layout',
        
        // Notificações
        'notifications.create' => 'Criar notificações',
        'notifications.read' => 'Visualizar notificações',
        'notifications.update' => 'Editar notificações',
        'notifications.delete' => 'Excluir notificações',
        'notifications.send' => 'Enviar notificações',
        
        // Relatórios
        'reports.sales' => 'Relatórios de vendas',
        'reports.financial' => 'Relatórios financeiros',
        'reports.inventory' => 'Relatórios de estoque',
        'reports.customers' => 'Relatórios de clientes',
        'reports.employees' => 'Relatórios de funcionários',
        'reports.export' => 'Exportar relatórios',
        
        // Configurações
        'settings.restaurant' => 'Configurações do restaurante',
        'settings.system' => 'Configurações do sistema',
        'settings.integrations' => 'Configurações de integrações',
        'settings.notifications' => 'Configurações de notificações',
        'settings.payments' => 'Configurações de pagamentos',
        
        // Administração
        'admin.backup' => 'Fazer backup',
        'admin.restore' => 'Restaurar backup',
        'admin.logs' => 'Visualizar logs',
        'admin.maintenance' => 'Modo manutenção',
        'admin.system_info' => 'Informações do sistema'
    ];
    
    // Roles padrão do sistema
    protected $systemRoles = [
        'super_admin' => [
            'name' => 'Super Administrador',
            'description' => 'Acesso total ao sistema',
            'level' => 100,
            'color' => '#dc3545',
            'icon' => 'crown',
            'permissions' => '*' // Todas as permissões
        ],
        'owner' => [
            'name' => 'Proprietário',
            'description' => 'Proprietário do restaurante com acesso completo',
            'level' => 90,
            'color' => '#6f42c1',
            'icon' => 'user-crown',
            'permissions' => [
                'users.*', 'employees.*', 'customers.*', 'dishes.*', 'categories.*',
                'orders.*', 'payments.*', 'reservations.*', 'inventory.*', 'suppliers.*',
                'tables.*', 'notifications.*', 'reports.*', 'settings.*'
            ]
        ],
        'manager' => [
            'name' => 'Gerente',
            'description' => 'Gerente do restaurante',
            'level' => 80,
            'color' => '#007bff',
            'icon' => 'user-tie',
            'permissions' => [
                'users.read', 'users.update', 'employees.*', 'customers.*',
                'dishes.*', 'categories.*', 'orders.*', 'payments.read',
                'reservations.*', 'inventory.*', 'suppliers.read',
                'tables.*', 'notifications.*', 'reports.sales', 'reports.customers'
            ]
        ],
        'supervisor' => [
            'name' => 'Supervisor',
            'description' => 'Supervisor de operações',
            'level' => 70,
            'color' => '#28a745',
            'icon' => 'user-check',
            'permissions' => [
                'employees.read', 'customers.*', 'dishes.read', 'categories.read',
                'orders.*', 'reservations.*', 'tables.*', 'notifications.read',
                'reports.sales'
            ]
        ],
        'cashier' => [
            'name' => 'Caixa',
            'description' => 'Operador de caixa',
            'level' => 60,
            'color' => '#ffc107',
            'icon' => 'cash-register',
            'permissions' => [
                'customers.read', 'dishes.read', 'categories.read',
                'orders.create', 'orders.read', 'orders.update',
                'payments.create', 'payments.read', 'notifications.read'
            ]
        ],
        'waiter' => [
            'name' => 'Garçom',
            'description' => 'Atendente/Garçom',
            'level' => 50,
            'color' => '#17a2b8',
            'icon' => 'user-friends',
            'permissions' => [
                'customers.read', 'dishes.read', 'categories.read',
                'orders.create', 'orders.read', 'orders.update',
                'reservations.*', 'tables.read', 'notifications.read'
            ]
        ],
        'cook' => [
            'name' => 'Cozinheiro',
            'description' => 'Cozinheiro/Chef',
            'level' => 40,
            'color' => '#fd7e14',
            'icon' => 'utensils',
            'permissions' => [
                'dishes.read', 'categories.read', 'orders.read', 'orders.update',
                'inventory.read', 'notifications.read'
            ]
        ],
        'delivery' => [
            'name' => 'Entregador',
            'description' => 'Entregador',
            'level' => 30,
            'color' => '#20c997',
            'icon' => 'truck',
            'permissions' => [
                'orders.read', 'orders.update', 'customers.read', 'notifications.read'
            ]
        ],
        'employee' => [
            'name' => 'Funcionário',
            'description' => 'Funcionário básico',
            'level' => 20,
            'color' => '#6c757d',
            'icon' => 'user',
            'permissions' => [
                'orders.read', 'notifications.read'
            ]
        ]
    ];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['is_system_role'])) {
            $data['data']['is_system_role'] = 0;
        }
        
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        
        if (!isset($data['data']['level'])) {
            $data['data']['level'] = 10;
        }
        
        if (!isset($data['data']['color'])) {
            $data['data']['color'] = '#6c757d';
        }
        
        if (!isset($data['data']['icon'])) {
            $data['data']['icon'] = 'user';
        }
        
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = json_encode([]);
        }
        
        return $data;
    }
    
    /**
     * Gera slug automaticamente
     */
    protected function generateSlug(array $data): array
    {
        if (!isset($data['data']['slug']) || empty($data['data']['slug'])) {
            $data['data']['slug'] = url_title($data['data']['name'], '-', true);
        }
        
        return $data;
    }
    
    /**
     * Valida permissões
     */
    protected function validatePermissions(array $data): array
    {
        if (isset($data['data']['permissions'])) {
            if (is_string($data['data']['permissions'])) {
                $permissions = json_decode($data['data']['permissions'], true);
            } else {
                $permissions = $data['data']['permissions'];
            }
            
            if ($permissions && is_array($permissions)) {
                // Validar se as permissões existem
                $validPermissions = [];
                foreach ($permissions as $permission) {
                    if ($permission === '*' || $this->isValidPermission($permission)) {
                        $validPermissions[] = $permission;
                    }
                }
                $data['data']['permissions'] = json_encode($validPermissions);
            } else {
                $data['data']['permissions'] = json_encode([]);
            }
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
    
    /**
     * Verifica se uma permissão é válida
     */
    protected function isValidPermission(string $permission): bool
    {
        // Verifica permissões com wildcard (ex: users.*)
        if (strpos($permission, '*') !== false) {
            $prefix = str_replace('*', '', $permission);
            foreach (array_keys($this->availablePermissions) as $availablePermission) {
                if (strpos($availablePermission, $prefix) === 0) {
                    return true;
                }
            }
            return false;
        }
        
        // Verifica permissão específica
        return array_key_exists($permission, $this->availablePermissions);
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Busca role por slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }
    
    /**
     * Obtém roles ativas
     */
    public function getActiveRoles(): array
    {
        return $this->where('is_active', 1)
                   ->orderBy('level', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém roles do sistema
     */
    public function getSystemRoles(): array
    {
        return $this->where('is_system_role', 1)
                   ->orderBy('level', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém roles customizadas
     */
    public function getCustomRoles(): array
    {
        return $this->where('is_system_role', 0)
                   ->orderBy('level', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém roles por nível
     */
    public function getRolesByLevel(int $minLevel = 1, int $maxLevel = 100): array
    {
        return $this->where('level >=', $minLevel)
                   ->where('level <=', $maxLevel)
                   ->where('is_active', 1)
                   ->orderBy('level', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém permissões de uma role
     */
    public function getRolePermissions(int $roleId): array
    {
        $role = $this->find($roleId);
        if (!$role) {
            return [];
        }
        
        $permissions = json_decode($role['permissions'] ?? '[]', true);
        
        // Se tem permissão total (*), retorna todas
        if (in_array('*', $permissions)) {
            return array_keys($this->availablePermissions);
        }
        
        // Expande permissões com wildcard
        $expandedPermissions = [];
        foreach ($permissions as $permission) {
            if (strpos($permission, '*') !== false) {
                $prefix = str_replace('*', '', $permission);
                foreach (array_keys($this->availablePermissions) as $availablePermission) {
                    if (strpos($availablePermission, $prefix) === 0) {
                        $expandedPermissions[] = $availablePermission;
                    }
                }
            } else {
                $expandedPermissions[] = $permission;
            }
        }
        
        return array_unique($expandedPermissions);
    }
    
    /**
     * Verifica se role tem permissão
     */
    public function hasPermission(int $roleId, string $permission): bool
    {
        $rolePermissions = $this->getRolePermissions($roleId);
        return in_array($permission, $rolePermissions);
    }
    
    /**
     * Adiciona permissão à role
     */
    public function addPermission(int $roleId, string $permission): bool
    {
        $role = $this->find($roleId);
        if (!$role || !$this->isValidPermission($permission)) {
            return false;
        }
        
        $permissions = json_decode($role['permissions'] ?? '[]', true);
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            return $this->update($roleId, ['permissions' => json_encode($permissions)]);
        }
        
        return true;
    }
    
    /**
     * Remove permissão da role
     */
    public function removePermission(int $roleId, string $permission): bool
    {
        $role = $this->find($roleId);
        if (!$role) {
            return false;
        }
        
        $permissions = json_decode($role['permissions'] ?? '[]', true);
        $permissions = array_filter($permissions, function($p) use ($permission) {
            return $p !== $permission;
        });
        
        return $this->update($roleId, ['permissions' => json_encode(array_values($permissions))]);
    }
    
    /**
     * Define permissões da role
     */
    public function setPermissions(int $roleId, array $permissions): bool
    {
        $validPermissions = [];
        foreach ($permissions as $permission) {
            if ($permission === '*' || $this->isValidPermission($permission)) {
                $validPermissions[] = $permission;
            }
        }
        
        return $this->update($roleId, ['permissions' => json_encode($validPermissions)]);
    }
    
    /**
     * Obtém todas as permissões disponíveis
     */
    public function getAvailablePermissions(): array
    {
        return $this->availablePermissions;
    }
    
    /**
     * Obtém permissões agrupadas por módulo
     */
    public function getGroupedPermissions(): array
    {
        $grouped = [];
        foreach ($this->availablePermissions as $permission => $description) {
            $parts = explode('.', $permission);
            $module = $parts[0];
            $action = $parts[1] ?? 'general';
            
            if (!isset($grouped[$module])) {
                $grouped[$module] = [
                    'name' => ucfirst($module),
                    'permissions' => []
                ];
            }
            
            $grouped[$module]['permissions'][$permission] = $description;
        }
        
        return $grouped;
    }
    
    /**
     * Cria roles padrão do sistema
     */
    public function createSystemRoles(): array
    {
        $createdRoles = [];
        
        foreach ($this->systemRoles as $slug => $roleData) {
            // Verifica se já existe
            $existingRole = $this->findBySlug($slug);
            if ($existingRole) {
                continue;
            }
            
            $data = [
                'name' => $roleData['name'],
                'slug' => $slug,
                'description' => $roleData['description'],
                'permissions' => json_encode($roleData['permissions']),
                'is_system_role' => 1,
                'is_active' => 1,
                'level' => $roleData['level'],
                'color' => $roleData['color'],
                'icon' => $roleData['icon']
            ];
            
            $roleId = $this->insert($data);
            if ($roleId) {
                $createdRoles[] = $roleId;
            }
        }
        
        return $createdRoles;
    }
    
    /**
     * Duplica role
     */
    public function duplicateRole(int $roleId, string $newName): ?int
    {
        $role = $this->find($roleId);
        if (!$role) {
            return null;
        }
        
        $newRole = $role;
        unset($newRole['id']);
        $newRole['name'] = $newName;
        $newRole['slug'] = url_title($newName, '-', true);
        $newRole['is_system_role'] = 0;
        $newRole['created_at'] = date('Y-m-d H:i:s');
        $newRole['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->insert($newRole);
    }
    
    /**
     * Busca avançada de roles
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('name', $filters['search'])
                             ->orLike('description', $filters['search'])
                             ->orLike('slug', $filters['search'])
                             ->groupEnd();
        }
        
        if (isset($filters['is_active'])) {
            $builder = $builder->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['is_system_role'])) {
            $builder = $builder->where('is_system_role', $filters['is_system_role']);
        }
        
        if (!empty($filters['min_level'])) {
            $builder = $builder->where('level >=', $filters['min_level']);
        }
        
        if (!empty($filters['max_level'])) {
            $builder = $builder->where('level <=', $filters['max_level']);
        }
        
        $orderBy = $filters['order_by'] ?? 'level';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $builder->orderBy($orderBy, $orderDir)->findAll();
    }
    
    /**
     * Obtém estatísticas das roles
     */
    public function getRoleStats(): array
    {
        $stats = [];
        
        $stats['total_roles'] = $this->countAllResults();
        $stats['active_roles'] = $this->where('is_active', 1)->countAllResults();
        $stats['system_roles'] = $this->where('is_system_role', 1)->countAllResults();
        $stats['custom_roles'] = $this->where('is_system_role', 0)->countAllResults();
        
        // Roles por nível
        $levelStats = $this->select('level, COUNT(*) as count')
                          ->groupBy('level')
                          ->orderBy('level', 'DESC')
                          ->findAll();
        
        $stats['roles_by_level'] = [];
        foreach ($levelStats as $level) {
            $stats['roles_by_level'][$level['level']] = $level['count'];
        }
        
        return $stats;
    }
    
    /**
     * Exporta roles para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $roles = $this->advancedSearch($filters);
        
        $csv = "Nome,Slug,Descrição,Nível,Ativo,Sistema,Permissões,Criado\n";
        
        foreach ($roles as $role) {
            $permissions = json_decode($role['permissions'] ?? '[]', true);
            $permissionsStr = implode('; ', $permissions);
            
            $csv .= sprintf(
                "%s,%s,%s,%d,%s,%s,%s,%s\n",
                $role['name'],
                $role['slug'],
                $role['description'] ?? '',
                $role['level'],
                $role['is_active'] ? 'Sim' : 'Não',
                $role['is_system_role'] ? 'Sim' : 'Não',
                $permissionsStr,
                $role['created_at']
            );
        }
        
        return $csv;
    }
    
    /**
     * Verifica se slug já existe
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $builder = $this->where('slug', $slug);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
}