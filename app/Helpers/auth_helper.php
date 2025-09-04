<?php

use App\Services\AuthService;

if (!function_exists('auth')) {
    /**
     * Obtém instância do AuthService
     */
    function auth(): AuthService
    {
        static $authService = null;
        
        if ($authService === null) {
            $authService = new AuthService();
        }
        
        return $authService;
    }
}

if (!function_exists('is_authenticated')) {
    /**
     * Verifica se o usuário está autenticado
     */
    function is_authenticated(): bool
    {
        return auth()->isAuthenticated();
    }
}

if (!function_exists('current_user')) {
    /**
     * Obtém o usuário autenticado
     */
    function current_user(): ?array
    {
        return auth()->getAuthenticatedUser();
    }
}

if (!function_exists('user_id')) {
    /**
     * Obtém o ID do usuário autenticado
     */
    function user_id(): ?int
    {
        $user = current_user();
        return $user ? $user['id'] : null;
    }
}

if (!function_exists('user_name')) {
    /**
     * Obtém o nome do usuário autenticado
     */
    function user_name(): ?string
    {
        $user = current_user();
        return $user ? $user['name'] : null;
    }
}

if (!function_exists('user_email')) {
    /**
     * Obtém o email do usuário autenticado
     */
    function user_email(): ?string
    {
        $user = current_user();
        return $user ? $user['email'] : null;
    }
}

if (!function_exists('user_avatar')) {
    /**
     * Obtém o avatar do usuário autenticado
     */
    function user_avatar(): ?string
    {
        $user = current_user();
        return $user ? $user['avatar'] : null;
    }
}

if (!function_exists('restaurant_id')) {
    /**
     * Obtém o ID do restaurante do usuário autenticado
     */
    function restaurant_id(): ?int
    {
        $user = current_user();
        return $user ? $user['restaurant_id'] : null;
    }
}

if (!function_exists('has_permission')) {
    /**
     * Verifica se o usuário tem uma permissão específica
     */
    function has_permission(string $permission, ?int $userId = null): bool
    {
        return auth()->hasPermission($permission, $userId);
    }
}

if (!function_exists('has_role')) {
    /**
     * Verifica se o usuário tem uma role específica
     */
    function has_role(string $roleSlug, ?int $userId = null): bool
    {
        return auth()->hasRole($roleSlug, $userId);
    }
}

if (!function_exists('has_any_permission')) {
    /**
     * Verifica se o usuário tem qualquer uma das permissões fornecidas
     */
    function has_any_permission(array $permissions, ?int $userId = null): bool
    {
        return auth()->hasAnyPermission($permissions, $userId);
    }
}

if (!function_exists('has_all_permissions')) {
    /**
     * Verifica se o usuário tem todas as permissões fornecidas
     */
    function has_all_permissions(array $permissions, ?int $userId = null): bool
    {
        return auth()->hasAllPermissions($permissions, $userId);
    }
}

if (!function_exists('has_any_role')) {
    /**
     * Verifica se o usuário tem qualquer uma das roles fornecidas
     */
    function has_any_role(array $roles, ?int $userId = null): bool
    {
        return auth()->hasAnyRole($roles, $userId);
    }
}

if (!function_exists('user_permissions')) {
    /**
     * Obtém todas as permissões do usuário
     */
    function user_permissions(?int $userId = null): array
    {
        return auth()->getUserPermissions($userId);
    }
}

if (!function_exists('user_roles')) {
    /**
     * Obtém todas as roles do usuário
     */
    function user_roles(?int $userId = null): array
    {
        return auth()->getUserRoles($userId);
    }
}

if (!function_exists('user_primary_role')) {
    /**
     * Obtém a role principal do usuário
     */
    function user_primary_role(?int $userId = null): ?array
    {
        return auth()->getUserPrimaryRole($userId);
    }
}

if (!function_exists('can_access')) {
    /**
     * Verifica se o usuário pode acessar um recurso específico
     */
    function can_access(string $resource, string $action = 'read', ?int $userId = null): bool
    {
        return auth()->canAccess($resource, $action, $userId);
    }
}

if (!function_exists('user_menu')) {
    /**
     * Obtém menu do usuário baseado em suas permissões
     */
    function user_menu(?int $userId = null): array
    {
        return auth()->getUserMenu($userId);
    }
}

if (!function_exists('is_super_admin')) {
    /**
     * Verifica se o usuário é super admin
     */
    function is_super_admin(?int $userId = null): bool
    {
        return has_role('super_admin', $userId);
    }
}

if (!function_exists('is_owner')) {
    /**
     * Verifica se o usuário é proprietário
     */
    function is_owner(?int $userId = null): bool
    {
        return has_role('owner', $userId);
    }
}

if (!function_exists('is_manager')) {
    /**
     * Verifica se o usuário é gerente
     */
    function is_manager(?int $userId = null): bool
    {
        return has_role('manager', $userId);
    }
}

if (!function_exists('is_employee')) {
    /**
     * Verifica se o usuário é funcionário
     */
    function is_employee(?int $userId = null): bool
    {
        return has_role('employee', $userId);
    }
}

if (!function_exists('can_manage_users')) {
    /**
     * Verifica se o usuário pode gerenciar usuários
     */
    function can_manage_users(?int $userId = null): bool
    {
        return has_permission('users.create', $userId) || 
               has_permission('users.update', $userId) || 
               has_permission('users.delete', $userId);
    }
}

if (!function_exists('can_manage_roles')) {
    /**
     * Verifica se o usuário pode gerenciar roles
     */
    function can_manage_roles(?int $userId = null): bool
    {
        return has_permission('users.manage_roles', $userId);
    }
}

if (!function_exists('can_view_reports')) {
    /**
     * Verifica se o usuário pode visualizar relatórios
     */
    function can_view_reports(?int $userId = null): bool
    {
        return has_permission('reports.read', $userId);
    }
}

if (!function_exists('can_manage_menu')) {
    /**
     * Verifica se o usuário pode gerenciar cardápio
     */
    function can_manage_menu(?int $userId = null): bool
    {
        return has_permission('menu.create', $userId) || 
               has_permission('menu.update', $userId) || 
               has_permission('menu.delete', $userId);
    }
}

if (!function_exists('can_manage_orders')) {
    /**
     * Verifica se o usuário pode gerenciar pedidos
     */
    function can_manage_orders(?int $userId = null): bool
    {
        return has_permission('orders.create', $userId) || 
               has_permission('orders.update', $userId) || 
               has_permission('orders.delete', $userId);
    }
}

if (!function_exists('can_manage_inventory')) {
    /**
     * Verifica se o usuário pode gerenciar estoque
     */
    function can_manage_inventory(?int $userId = null): bool
    {
        return has_permission('inventory.create', $userId) || 
               has_permission('inventory.update', $userId) || 
               has_permission('inventory.delete', $userId);
    }
}

if (!function_exists('can_manage_customers')) {
    /**
     * Verifica se o usuário pode gerenciar clientes
     */
    function can_manage_customers(?int $userId = null): bool
    {
        return has_permission('customers.create', $userId) || 
               has_permission('customers.update', $userId) || 
               has_permission('customers.delete', $userId);
    }
}

if (!function_exists('can_manage_tables')) {
    /**
     * Verifica se o usuário pode gerenciar mesas
     */
    function can_manage_tables(?int $userId = null): bool
    {
        return has_permission('tables.create', $userId) || 
               has_permission('tables.update', $userId) || 
               has_permission('tables.delete', $userId);
    }
}

if (!function_exists('can_manage_reservations')) {
    /**
     * Verifica se o usuário pode gerenciar reservas
     */
    function can_manage_reservations(?int $userId = null): bool
    {
        return has_permission('reservations.create', $userId) || 
               has_permission('reservations.update', $userId) || 
               has_permission('reservations.delete', $userId);
    }
}

if (!function_exists('can_manage_payments')) {
    /**
     * Verifica se o usuário pode gerenciar pagamentos
     */
    function can_manage_payments(?int $userId = null): bool
    {
        return has_permission('payments.create', $userId) || 
               has_permission('payments.update', $userId) || 
               has_permission('payments.delete', $userId);
    }
}

if (!function_exists('can_manage_suppliers')) {
    /**
     * Verifica se o usuário pode gerenciar fornecedores
     */
    function can_manage_suppliers(?int $userId = null): bool
    {
        return has_permission('suppliers.create', $userId) || 
               has_permission('suppliers.update', $userId) || 
               has_permission('suppliers.delete', $userId);
    }
}

if (!function_exists('can_access_admin')) {
    /**
     * Verifica se o usuário pode acessar área administrativa
     */
    function can_access_admin(?int $userId = null): bool
    {
        return has_permission('admin.access', $userId);
    }
}

if (!function_exists('can_view_dashboard')) {
    /**
     * Verifica se o usuário pode visualizar dashboard
     */
    function can_view_dashboard(?int $userId = null): bool
    {
        return has_permission('dashboard.read', $userId);
    }
}

if (!function_exists('format_user_role')) {
    /**
     * Formata o nome da role para exibição
     */
    function format_user_role(string $roleSlug): string
    {
        $roleNames = [
            'super_admin' => 'Super Administrador',
            'owner' => 'Proprietário',
            'manager' => 'Gerente',
            'supervisor' => 'Supervisor',
            'cashier' => 'Caixa',
            'waiter' => 'Garçom',
            'cook' => 'Cozinheiro',
            'delivery' => 'Entregador',
            'employee' => 'Funcionário'
        ];
        
        return $roleNames[$roleSlug] ?? ucfirst(str_replace('_', ' ', $roleSlug));
    }
}

if (!function_exists('get_role_color')) {
    /**
     * Obtém a cor da role para exibição
     */
    function get_role_color(string $roleSlug): string
    {
        $roleColors = [
            'super_admin' => '#dc3545', // Vermelho
            'owner' => '#6f42c1',       // Roxo
            'manager' => '#fd7e14',     // Laranja
            'supervisor' => '#20c997',  // Teal
            'cashier' => '#0d6efd',     // Azul
            'waiter' => '#198754',      // Verde
            'cook' => '#ffc107',        // Amarelo
            'delivery' => '#6c757d',    // Cinza
            'employee' => '#adb5bd'     // Cinza claro
        ];
        
        return $roleColors[$roleSlug] ?? '#6c757d';
    }
}

if (!function_exists('get_role_icon')) {
    /**
     * Obtém o ícone da role para exibição
     */
    function get_role_icon(string $roleSlug): string
    {
        $roleIcons = [
            'super_admin' => 'crown',
            'owner' => 'star',
            'manager' => 'briefcase',
            'supervisor' => 'eye',
            'cashier' => 'calculator',
            'waiter' => 'utensils',
            'cook' => 'fire',
            'delivery' => 'truck',
            'employee' => 'user'
        ];
        
        return $roleIcons[$roleSlug] ?? 'user';
    }
}

if (!function_exists('permission_label')) {
    /**
     * Converte slug de permissão em label legível
     */
    function permission_label(string $permission): string
    {
        $parts = explode('.', $permission);
        $resource = $parts[0] ?? '';
        $action = $parts[1] ?? '';
        
        $resourceLabels = [
            'users' => 'Usuários',
            'employees' => 'Funcionários',
            'customers' => 'Clientes',
            'menu' => 'Cardápio',
            'orders' => 'Pedidos',
            'payments' => 'Pagamentos',
            'reservations' => 'Reservas',
            'inventory' => 'Estoque',
            'suppliers' => 'Fornecedores',
            'tables' => 'Mesas',
            'notifications' => 'Notificações',
            'reports' => 'Relatórios',
            'settings' => 'Configurações',
            'admin' => 'Administração',
            'dashboard' => 'Dashboard'
        ];
        
        $actionLabels = [
            'create' => 'Criar',
            'read' => 'Visualizar',
            'update' => 'Editar',
            'delete' => 'Excluir',
            'manage' => 'Gerenciar',
            'access' => 'Acessar',
            'export' => 'Exportar',
            'import' => 'Importar'
        ];
        
        $resourceLabel = $resourceLabels[$resource] ?? ucfirst($resource);
        $actionLabel = $actionLabels[$action] ?? ucfirst($action);
        
        return $actionLabel . ' ' . $resourceLabel;
    }
}

if (!function_exists('check_session_timeout')) {
    /**
     * Verifica se a sessão expirou
     */
    function check_session_timeout(): bool
    {
        return auth()->isSessionExpired();
    }
}

if (!function_exists('renew_session')) {
    /**
     * Renova a sessão do usuário
     */
    function renew_session(): void
    {
        auth()->renewSession();
    }
}

if (!function_exists('logout_user')) {
    /**
     * Faz logout do usuário
     */
    function logout_user(): bool
    {
        return auth()->logout();
    }
}