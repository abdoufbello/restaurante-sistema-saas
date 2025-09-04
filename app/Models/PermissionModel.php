<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Permissões com Multi-Tenancy
 */
class PermissionModel extends BaseMultiTenantModel
{
    protected $table = 'permissions';
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
        'module',
        'action',
        'resource',
        'is_system_permission',
        'is_active',
        'level',
        'group_name',
        'icon',
        'color',
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
        'module' => 'required|min_length[2]|max_length[50]|alpha_dash',
        'action' => 'required|min_length[2]|max_length[50]|alpha_dash',
        'resource' => 'permit_empty|max_length[50]|alpha_dash',
        'is_system_permission' => 'permit_empty|in_list[0,1]',
        'is_active' => 'permit_empty|in_list[0,1]',
        'level' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[100]',
        'group_name' => 'permit_empty|max_length[100]',
        'icon' => 'permit_empty|max_length[50]',
        'color' => 'permit_empty|max_length[7]'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Nome da permissão é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome deve ter no máximo 100 caracteres'
        ],
        'slug' => [
            'required' => 'Slug é obrigatório',
            'min_length' => 'Slug deve ter pelo menos 2 caracteres',
            'max_length' => 'Slug deve ter no máximo 100 caracteres',
            'alpha_dash' => 'Slug deve conter apenas letras, números, hífens e underscores'
        ],
        'module' => [
            'required' => 'Módulo é obrigatório',
            'alpha_dash' => 'Módulo deve conter apenas letras, números, hífens e underscores'
        ],
        'action' => [
            'required' => 'Ação é obrigatória',
            'alpha_dash' => 'Ação deve conter apenas letras, números, hífens e underscores'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generateSlug'];
    protected $beforeUpdate = ['updateTimestamps'];
    
    // Módulos disponíveis
    protected $availableModules = [
        'users' => 'Usuários',
        'employees' => 'Funcionários',
        'customers' => 'Clientes',
        'dishes' => 'Pratos',
        'categories' => 'Categorias',
        'orders' => 'Pedidos',
        'payments' => 'Pagamentos',
        'reservations' => 'Reservas',
        'inventory' => 'Estoque',
        'suppliers' => 'Fornecedores',
        'tables' => 'Mesas',
        'notifications' => 'Notificações',
        'reports' => 'Relatórios',
        'settings' => 'Configurações',
        'admin' => 'Administração'
    ];
    
    // Ações disponíveis
    protected $availableActions = [
        'create' => 'Criar',
        'read' => 'Visualizar',
        'update' => 'Editar',
        'delete' => 'Excluir',
        'manage' => 'Gerenciar',
        'export' => 'Exportar',
        'import' => 'Importar',
        'approve' => 'Aprovar',
        'reject' => 'Rejeitar',
        'send' => 'Enviar',
        'process' => 'Processar',
        'refund' => 'Reembolsar',
        'cancel' => 'Cancelar',
        'activate' => 'Ativar',
        'deactivate' => 'Desativar',
        'suspend' => 'Suspender',
        'restore' => 'Restaurar',
        'backup' => 'Backup',
        'configure' => 'Configurar'
    ];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['is_system_permission'])) {
            $data['data']['is_system_permission'] = 0;
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
            $data['data']['icon'] = 'key';
        }
        
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = json_encode([]);
        }
        
        // Gera group_name baseado no módulo
        if (!isset($data['data']['group_name']) && isset($data['data']['module'])) {
            $data['data']['group_name'] = $this->availableModules[$data['data']['module']] ?? ucfirst($data['data']['module']);
        }
        
        return $data;
    }
    
    /**
     * Gera slug automaticamente
     */
    protected function generateSlug(array $data): array
    {
        if (!isset($data['data']['slug']) || empty($data['data']['slug'])) {
            // Gera slug baseado em module.action.resource ou module.action
            $slugParts = [$data['data']['module'], $data['data']['action']];
            if (!empty($data['data']['resource'])) {
                $slugParts[] = $data['data']['resource'];
            }
            $data['data']['slug'] = implode('.', $slugParts);
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
     * Busca permissão por slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }
    
    /**
     * Obtém permissões ativas
     */
    public function getActivePermissions(): array
    {
        return $this->where('is_active', 1)
                   ->orderBy('module', 'ASC')
                   ->orderBy('action', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém permissões do sistema
     */
    public function getSystemPermissions(): array
    {
        return $this->where('is_system_permission', 1)
                   ->orderBy('module', 'ASC')
                   ->orderBy('action', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém permissões customizadas
     */
    public function getCustomPermissions(): array
    {
        return $this->where('is_system_permission', 0)
                   ->orderBy('module', 'ASC')
                   ->orderBy('action', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém permissões por módulo
     */
    public function getPermissionsByModule(string $module): array
    {
        return $this->where('module', $module)
                   ->where('is_active', 1)
                   ->orderBy('action', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém permissões por ação
     */
    public function getPermissionsByAction(string $action): array
    {
        return $this->where('action', $action)
                   ->where('is_active', 1)
                   ->orderBy('module', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém permissões por nível
     */
    public function getPermissionsByLevel(int $minLevel = 1, int $maxLevel = 100): array
    {
        return $this->where('level >=', $minLevel)
                   ->where('level <=', $maxLevel)
                   ->where('is_active', 1)
                   ->orderBy('level', 'DESC')
                   ->orderBy('module', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém permissões agrupadas por módulo
     */
    public function getGroupedPermissions(): array
    {
        $permissions = $this->getActivePermissions();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $module = $permission['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [
                    'name' => $this->availableModules[$module] ?? ucfirst($module),
                    'permissions' => []
                ];
            }
            $grouped[$module]['permissions'][] = $permission;
        }
        
        return $grouped;
    }
    
    /**
     * Obtém permissões agrupadas por grupo
     */
    public function getPermissionsByGroup(): array
    {
        $permissions = $this->getActivePermissions();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $group = $permission['group_name'] ?? 'Outros';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $permission;
        }
        
        return $grouped;
    }
    
    /**
     * Cria permissões padrão do sistema
     */
    public function createSystemPermissions(): array
    {
        $createdPermissions = [];
        
        // Permissões básicas para cada módulo
        $basicActions = ['create', 'read', 'update', 'delete'];
        
        foreach ($this->availableModules as $moduleSlug => $moduleName) {
            foreach ($basicActions as $action) {
                $slug = $moduleSlug . '.' . $action;
                
                // Verifica se já existe
                $existingPermission = $this->findBySlug($slug);
                if ($existingPermission) {
                    continue;
                }
                
                $data = [
                    'name' => $this->availableActions[$action] . ' ' . $moduleName,
                    'slug' => $slug,
                    'description' => 'Permite ' . strtolower($this->availableActions[$action]) . ' ' . strtolower($moduleName),
                    'module' => $moduleSlug,
                    'action' => $action,
                    'is_system_permission' => 1,
                    'is_active' => 1,
                    'level' => $this->getActionLevel($action),
                    'group_name' => $moduleName,
                    'icon' => $this->getModuleIcon($moduleSlug),
                    'color' => $this->getModuleColor($moduleSlug)
                ];
                
                $permissionId = $this->insert($data);
                if ($permissionId) {
                    $createdPermissions[] = $permissionId;
                }
            }
        }
        
        // Permissões especiais
        $specialPermissions = [
            'orders.manage_status' => [
                'name' => 'Gerenciar Status de Pedidos',
                'description' => 'Permite alterar status de pedidos',
                'module' => 'orders',
                'action' => 'manage',
                'resource' => 'status'
            ],
            'payments.refund' => [
                'name' => 'Processar Reembolsos',
                'description' => 'Permite processar reembolsos de pagamentos',
                'module' => 'payments',
                'action' => 'refund'
            ],
            'reports.export' => [
                'name' => 'Exportar Relatórios',
                'description' => 'Permite exportar relatórios',
                'module' => 'reports',
                'action' => 'export'
            ],
            'admin.backup' => [
                'name' => 'Fazer Backup',
                'description' => 'Permite fazer backup do sistema',
                'module' => 'admin',
                'action' => 'backup'
            ],
            'admin.restore' => [
                'name' => 'Restaurar Backup',
                'description' => 'Permite restaurar backup do sistema',
                'module' => 'admin',
                'action' => 'restore'
            ]
        ];
        
        foreach ($specialPermissions as $slug => $permissionData) {
            // Verifica se já existe
            $existingPermission = $this->findBySlug($slug);
            if ($existingPermission) {
                continue;
            }
            
            $data = array_merge($permissionData, [
                'slug' => $slug,
                'is_system_permission' => 1,
                'is_active' => 1,
                'level' => $this->getActionLevel($permissionData['action']),
                'group_name' => $this->availableModules[$permissionData['module']] ?? ucfirst($permissionData['module']),
                'icon' => $this->getModuleIcon($permissionData['module']),
                'color' => $this->getModuleColor($permissionData['module'])
            ]);
            
            $permissionId = $this->insert($data);
            if ($permissionId) {
                $createdPermissions[] = $permissionId;
            }
        }
        
        return $createdPermissions;
    }
    
    /**
     * Obtém nível baseado na ação
     */
    protected function getActionLevel(string $action): int
    {
        $levels = [
            'read' => 10,
            'create' => 20,
            'update' => 30,
            'delete' => 40,
            'manage' => 50,
            'export' => 60,
            'import' => 70,
            'approve' => 80,
            'backup' => 90,
            'restore' => 100
        ];
        
        return $levels[$action] ?? 10;
    }
    
    /**
     * Obtém ícone do módulo
     */
    protected function getModuleIcon(string $module): string
    {
        $icons = [
            'users' => 'users',
            'employees' => 'user-tie',
            'customers' => 'user-friends',
            'dishes' => 'utensils',
            'categories' => 'tags',
            'orders' => 'shopping-cart',
            'payments' => 'credit-card',
            'reservations' => 'calendar-check',
            'inventory' => 'boxes',
            'suppliers' => 'truck',
            'tables' => 'chair',
            'notifications' => 'bell',
            'reports' => 'chart-bar',
            'settings' => 'cog',
            'admin' => 'shield-alt'
        ];
        
        return $icons[$module] ?? 'key';
    }
    
    /**
     * Obtém cor do módulo
     */
    protected function getModuleColor(string $module): string
    {
        $colors = [
            'users' => '#007bff',
            'employees' => '#28a745',
            'customers' => '#17a2b8',
            'dishes' => '#fd7e14',
            'categories' => '#6f42c1',
            'orders' => '#dc3545',
            'payments' => '#ffc107',
            'reservations' => '#20c997',
            'inventory' => '#6c757d',
            'suppliers' => '#343a40',
            'tables' => '#e83e8c',
            'notifications' => '#f8f9fa',
            'reports' => '#495057',
            'settings' => '#868e96',
            'admin' => '#212529'
        ];
        
        return $colors[$module] ?? '#6c757d';
    }
    
    /**
     * Busca avançada de permissões
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('name', $filters['search'])
                             ->orLike('description', $filters['search'])
                             ->orLike('slug', $filters['search'])
                             ->orLike('module', $filters['search'])
                             ->orLike('action', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['module'])) {
            $builder = $builder->where('module', $filters['module']);
        }
        
        if (!empty($filters['action'])) {
            $builder = $builder->where('action', $filters['action']);
        }
        
        if (isset($filters['is_active'])) {
            $builder = $builder->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['is_system_permission'])) {
            $builder = $builder->where('is_system_permission', $filters['is_system_permission']);
        }
        
        if (!empty($filters['min_level'])) {
            $builder = $builder->where('level >=', $filters['min_level']);
        }
        
        if (!empty($filters['max_level'])) {
            $builder = $builder->where('level <=', $filters['max_level']);
        }
        
        $orderBy = $filters['order_by'] ?? 'module';
        $orderDir = $filters['order_dir'] ?? 'ASC';
        
        return $builder->orderBy($orderBy, $orderDir)->findAll();
    }
    
    /**
     * Obtém estatísticas das permissões
     */
    public function getPermissionStats(): array
    {
        $stats = [];
        
        $stats['total_permissions'] = $this->countAllResults();
        $stats['active_permissions'] = $this->where('is_active', 1)->countAllResults();
        $stats['system_permissions'] = $this->where('is_system_permission', 1)->countAllResults();
        $stats['custom_permissions'] = $this->where('is_system_permission', 0)->countAllResults();
        
        // Permissões por módulo
        $moduleStats = $this->select('module, COUNT(*) as count')
                           ->groupBy('module')
                           ->orderBy('count', 'DESC')
                           ->findAll();
        
        $stats['permissions_by_module'] = [];
        foreach ($moduleStats as $module) {
            $stats['permissions_by_module'][$module['module']] = $module['count'];
        }
        
        // Permissões por ação
        $actionStats = $this->select('action, COUNT(*) as count')
                           ->groupBy('action')
                           ->orderBy('count', 'DESC')
                           ->findAll();
        
        $stats['permissions_by_action'] = [];
        foreach ($actionStats as $action) {
            $stats['permissions_by_action'][$action['action']] = $action['count'];
        }
        
        return $stats;
    }
    
    /**
     * Exporta permissões para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $permissions = $this->advancedSearch($filters);
        
        $csv = "Nome,Slug,Descrição,Módulo,Ação,Recurso,Nível,Ativo,Sistema,Grupo,Criado\n";
        
        foreach ($permissions as $permission) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s\n",
                $permission['name'],
                $permission['slug'],
                $permission['description'] ?? '',
                $permission['module'],
                $permission['action'],
                $permission['resource'] ?? '',
                $permission['level'],
                $permission['is_active'] ? 'Sim' : 'Não',
                $permission['is_system_permission'] ? 'Sim' : 'Não',
                $permission['group_name'] ?? '',
                $permission['created_at']
            );
        }
        
        return $csv;
    }
    
    /**
     * Obtém módulos disponíveis
     */
    public function getAvailableModules(): array
    {
        return $this->availableModules;
    }
    
    /**
     * Obtém ações disponíveis
     */
    public function getAvailableActions(): array
    {
        return $this->availableActions;
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
    
    /**
     * Sincroniza permissões com roles
     */
    public function syncWithRoles(): bool
    {
        // Atualiza as permissões nas roles baseado nas permissões ativas
        $roleModel = new \App\Models\RoleModel();
        $roles = $roleModel->findAll();
        
        foreach ($roles as $role) {
            $rolePermissions = json_decode($role['permissions'] ?? '[]', true);
            $validPermissions = [];
            
            foreach ($rolePermissions as $permission) {
                if ($permission === '*') {
                    $validPermissions[] = $permission;
                } else {
                    // Verifica se a permissão ainda existe e está ativa
                    $existingPermission = $this->where('slug', $permission)
                                              ->where('is_active', 1)
                                              ->first();
                    if ($existingPermission) {
                        $validPermissions[] = $permission;
                    }
                }
            }
            
            // Atualiza a role se houve mudanças
            if (count($validPermissions) !== count($rolePermissions)) {
                $roleModel->update($role['id'], [
                    'permissions' => json_encode($validPermissions)
                ]);
            }
        }
        
        return true;
    }
}