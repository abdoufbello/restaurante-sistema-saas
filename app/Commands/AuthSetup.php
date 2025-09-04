<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\UserModel;
use App\Models\UserRoleModel;

class AuthSetup extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:setup';
    protected $description = 'Configura o sistema de autenticação e autorização';
    protected $usage       = 'auth:setup [options]';
    protected $arguments   = [];
    protected $options     = [
        '--create-permissions' => 'Cria as permissões padrão do sistema',
        '--create-roles'       => 'Cria as roles padrão do sistema',
        '--create-admin'       => 'Cria um usuário super admin',
        '--reset'              => 'Reseta todas as roles e permissões (CUIDADO!)',
        '--restaurant-id'      => 'ID do restaurante (obrigatório para criar admin)'
    ];

    public function run(array $params)
    {
        CLI::write('=== Configuração do Sistema de Autenticação ===', 'yellow');
        CLI::newLine();

        $createPermissions = CLI::getOption('create-permissions');
        $createRoles = CLI::getOption('create-roles');
        $createAdmin = CLI::getOption('create-admin');
        $reset = CLI::getOption('reset');
        $restaurantId = CLI::getOption('restaurant-id');

        // Se nenhuma opção foi especificada, executa setup completo
        if (!$createPermissions && !$createRoles && !$createAdmin && !$reset) {
            $createPermissions = true;
            $createRoles = true;
        }

        try {
            // Reset se solicitado
            if ($reset) {
                $this->resetSystem();
            }

            // Criar permissões
            if ($createPermissions) {
                $this->createPermissions();
            }

            // Criar roles
            if ($createRoles) {
                $this->createRoles();
            }

            // Criar admin
            if ($createAdmin) {
                if (!$restaurantId) {
                    CLI::error('ID do restaurante é obrigatório para criar admin. Use --restaurant-id=ID');
                    return;
                }
                $this->createSuperAdmin($restaurantId);
            }

            CLI::newLine();
            CLI::write('✅ Configuração concluída com sucesso!', 'green');

        } catch (\Exception $e) {
            CLI::error('Erro durante a configuração: ' . $e->getMessage());
            CLI::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    private function resetSystem()
    {
        CLI::write('⚠️  Resetando sistema...', 'red');
        
        if (!CLI::prompt('Tem certeza? Isso irá remover todas as roles e permissões existentes', ['y', 'n']) === 'y') {
            CLI::write('Operação cancelada.');
            return;
        }

        $userRoleModel = new UserRoleModel();
        $roleModel = new RoleModel();
        $permissionModel = new PermissionModel();

        // Remove todas as atribuições
        $userRoleModel->truncate();
        CLI::write('- Atribuições de usuários removidas');

        // Remove todas as roles
        $roleModel->truncate();
        CLI::write('- Roles removidas');

        // Remove todas as permissões
        $permissionModel->truncate();
        CLI::write('- Permissões removidas');

        CLI::write('✅ Sistema resetado!', 'green');
        CLI::newLine();
    }

    private function createPermissions()
    {
        CLI::write('📋 Criando permissões padrão...', 'blue');
        
        $permissionModel = new PermissionModel();
        $created = $permissionModel->createSystemPermissions();
        
        CLI::write("✅ {$created} permissões criadas/atualizadas", 'green');
        CLI::newLine();
    }

    private function createRoles()
    {
        CLI::write('👥 Criando roles padrão...', 'blue');
        
        $roleModel = new RoleModel();
        $created = $roleModel->createSystemRoles();
        
        CLI::write("✅ {$created} roles criadas/atualizadas", 'green');
        CLI::newLine();
    }

    private function createSuperAdmin(int $restaurantId)
    {
        CLI::write('👑 Criando usuário Super Admin...', 'blue');
        
        $userModel = new UserModel();
        $roleModel = new RoleModel();
        $userRoleModel = new UserRoleModel();

        // Verifica se já existe um super admin
        $existingAdmin = $userModel->where('restaurant_id', $restaurantId)
                                  ->where('role', 'super_admin')
                                  ->first();

        if ($existingAdmin) {
            CLI::write('⚠️  Já existe um Super Admin para este restaurante', 'yellow');
            
            if (CLI::prompt('Deseja criar outro?', ['y', 'n']) !== 'y') {
                return;
            }
        }

        // Coleta dados do admin
        $name = CLI::prompt('Nome do Super Admin');
        $email = CLI::prompt('Email do Super Admin');
        $username = CLI::prompt('Username do Super Admin');
        $password = CLI::prompt('Senha do Super Admin');

        if (!$name || !$email || !$username || !$password) {
            CLI::error('Todos os campos são obrigatórios');
            return;
        }

        // Verifica se email/username já existem
        if ($userModel->where('email', $email)->first()) {
            CLI::error('Email já está em uso');
            return;
        }

        if ($userModel->where('username', $username)->first()) {
            CLI::error('Username já está em uso');
            return;
        }

        // Cria o usuário
        $userData = [
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'super_admin',
            'status' => 'active',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $userId = $userModel->insert($userData);

        if (!$userId) {
            CLI::error('Erro ao criar usuário: ' . implode(', ', $userModel->errors()));
            return;
        }

        // Atribui role de super_admin
        $superAdminRole = $roleModel->where('restaurant_id', $restaurantId)
                                   ->where('slug', 'super_admin')
                                   ->first();

        if ($superAdminRole) {
            $userRoleModel->assignRole($userId, $superAdminRole['id']);
            CLI::write('✅ Role Super Admin atribuída', 'green');
        }

        CLI::write("✅ Super Admin criado com sucesso! ID: {$userId}", 'green');
        CLI::write("📧 Email: {$email}", 'white');
        CLI::write("👤 Username: {$username}", 'white');
        CLI::newLine();
    }
}

class AuthPermissions extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:permissions';
    protected $description = 'Gerencia permissões do sistema';
    protected $usage       = 'auth:permissions [action] [options]';
    protected $arguments   = [
        'action' => 'Ação a executar: list, create, delete, sync'
    ];
    protected $options     = [
        '--restaurant-id' => 'ID do restaurante',
        '--name'          => 'Nome da permissão',
        '--slug'          => 'Slug da permissão',
        '--module'        => 'Módulo da permissão',
        '--action'        => 'Ação da permissão'
    ];

    public function run(array $params)
    {
        $action = $params[0] ?? 'list';
        
        switch ($action) {
            case 'list':
                $this->listPermissions();
                break;
            case 'create':
                $this->createPermission();
                break;
            case 'delete':
                $this->deletePermission();
                break;
            case 'sync':
                $this->syncPermissions();
                break;
            default:
                CLI::error('Ação inválida. Use: list, create, delete, sync');
        }
    }

    private function listPermissions()
    {
        $permissionModel = new PermissionModel();
        $restaurantId = CLI::getOption('restaurant-id');
        
        $query = $permissionModel;
        if ($restaurantId) {
            $query = $query->where('restaurant_id', $restaurantId);
        }
        
        $permissions = $query->findAll();
        
        if (empty($permissions)) {
            CLI::write('Nenhuma permissão encontrada', 'yellow');
            return;
        }
        
        CLI::write('=== Permissões do Sistema ===', 'yellow');
        CLI::newLine();
        
        $table = [];
        foreach ($permissions as $permission) {
            $table[] = [
                $permission['id'],
                $permission['restaurant_id'],
                $permission['name'],
                $permission['slug'],
                $permission['module'],
                $permission['action'],
                $permission['is_system_permission'] ? 'Sim' : 'Não',
                $permission['is_active'] ? 'Ativo' : 'Inativo'
            ];
        }
        
        CLI::table($table, ['ID', 'Restaurant', 'Nome', 'Slug', 'Módulo', 'Ação', 'Sistema', 'Status']);
    }

    private function createPermission()
    {
        $permissionModel = new PermissionModel();
        
        $restaurantId = CLI::getOption('restaurant-id') ?: CLI::prompt('ID do Restaurante');
        $name = CLI::getOption('name') ?: CLI::prompt('Nome da Permissão');
        $slug = CLI::getOption('slug') ?: CLI::prompt('Slug da Permissão');
        $module = CLI::getOption('module') ?: CLI::prompt('Módulo');
        $action = CLI::getOption('action') ?: CLI::prompt('Ação');
        
        $data = [
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'slug' => $slug,
            'module' => $module,
            'action' => $action,
            'is_system_permission' => 0,
            'is_active' => 1
        ];
        
        $id = $permissionModel->insert($data);
        
        if ($id) {
            CLI::write("✅ Permissão criada com ID: {$id}", 'green');
        } else {
            CLI::error('Erro ao criar permissão: ' . implode(', ', $permissionModel->errors()));
        }
    }

    private function deletePermission()
    {
        $permissionModel = new PermissionModel();
        $slug = CLI::getOption('slug') ?: CLI::prompt('Slug da Permissão para deletar');
        
        $permission = $permissionModel->where('slug', $slug)->first();
        
        if (!$permission) {
            CLI::error('Permissão não encontrada');
            return;
        }
        
        if ($permission['is_system_permission']) {
            CLI::error('Não é possível deletar permissões do sistema');
            return;
        }
        
        if (CLI::prompt("Tem certeza que deseja deletar a permissão '{$permission['name']}'?", ['y', 'n']) === 'y') {
            $permissionModel->delete($permission['id']);
            CLI::write('✅ Permissão deletada', 'green');
        }
    }

    private function syncPermissions()
    {
        CLI::write('🔄 Sincronizando permissões...', 'blue');
        
        $permissionModel = new PermissionModel();
        $roleModel = new RoleModel();
        
        $synced = $permissionModel->syncWithRoles();
        
        CLI::write("✅ {$synced} permissões sincronizadas com roles", 'green');
    }
}

class AuthRoles extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:roles';
    protected $description = 'Gerencia roles do sistema';
    protected $usage       = 'auth:roles [action] [options]';
    protected $arguments   = [
        'action' => 'Ação a executar: list, create, delete, assign, permissions'
    ];
    protected $options     = [
        '--restaurant-id' => 'ID do restaurante',
        '--user-id'       => 'ID do usuário',
        '--role-slug'     => 'Slug da role',
        '--name'          => 'Nome da role'
    ];

    public function run(array $params)
    {
        $action = $params[0] ?? 'list';
        
        switch ($action) {
            case 'list':
                $this->listRoles();
                break;
            case 'create':
                $this->createRole();
                break;
            case 'delete':
                $this->deleteRole();
                break;
            case 'assign':
                $this->assignRole();
                break;
            case 'permissions':
                $this->showRolePermissions();
                break;
            default:
                CLI::error('Ação inválida. Use: list, create, delete, assign, permissions');
        }
    }

    private function listRoles()
    {
        $roleModel = new RoleModel();
        $restaurantId = CLI::getOption('restaurant-id');
        
        $query = $roleModel;
        if ($restaurantId) {
            $query = $query->where('restaurant_id', $restaurantId);
        }
        
        $roles = $query->findAll();
        
        if (empty($roles)) {
            CLI::write('Nenhuma role encontrada', 'yellow');
            return;
        }
        
        CLI::write('=== Roles do Sistema ===', 'yellow');
        CLI::newLine();
        
        $table = [];
        foreach ($roles as $role) {
            $permissions = is_string($role['permissions']) ? json_decode($role['permissions'], true) : $role['permissions'];
            $permissionCount = is_array($permissions) ? count($permissions) : 0;
            
            $table[] = [
                $role['id'],
                $role['restaurant_id'],
                $role['name'],
                $role['slug'],
                $role['level'],
                $permissionCount,
                $role['is_system_role'] ? 'Sim' : 'Não',
                $role['is_active'] ? 'Ativo' : 'Inativo'
            ];
        }
        
        CLI::table($table, ['ID', 'Restaurant', 'Nome', 'Slug', 'Nível', 'Permissões', 'Sistema', 'Status']);
    }

    private function createRole()
    {
        $roleModel = new RoleModel();
        
        $restaurantId = CLI::getOption('restaurant-id') ?: CLI::prompt('ID do Restaurante');
        $name = CLI::getOption('name') ?: CLI::prompt('Nome da Role');
        $slug = strtolower(str_replace(' ', '_', $name));
        $description = CLI::prompt('Descrição (opcional)', '');
        $level = CLI::prompt('Nível (1-10)', '5');
        
        $data = [
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'level' => (int)$level,
            'permissions' => json_encode([]),
            'is_system_role' => 0,
            'is_active' => 1
        ];
        
        $id = $roleModel->insert($data);
        
        if ($id) {
            CLI::write("✅ Role criada com ID: {$id}", 'green');
        } else {
            CLI::error('Erro ao criar role: ' . implode(', ', $roleModel->errors()));
        }
    }

    private function deleteRole()
    {
        $roleModel = new RoleModel();
        $slug = CLI::getOption('role-slug') ?: CLI::prompt('Slug da Role para deletar');
        
        $role = $roleModel->where('slug', $slug)->first();
        
        if (!$role) {
            CLI::error('Role não encontrada');
            return;
        }
        
        if ($role['is_system_role']) {
            CLI::error('Não é possível deletar roles do sistema');
            return;
        }
        
        if (CLI::prompt("Tem certeza que deseja deletar a role '{$role['name']}'?", ['y', 'n']) === 'y') {
            $roleModel->delete($role['id']);
            CLI::write('✅ Role deletada', 'green');
        }
    }

    private function assignRole()
    {
        $userRoleModel = new UserRoleModel();
        $roleModel = new RoleModel();
        
        $userId = CLI::getOption('user-id') ?: CLI::prompt('ID do Usuário');
        $roleSlug = CLI::getOption('role-slug') ?: CLI::prompt('Slug da Role');
        
        $role = $roleModel->where('slug', $roleSlug)->first();
        
        if (!$role) {
            CLI::error('Role não encontrada');
            return;
        }
        
        $success = $userRoleModel->assignRole($userId, $role['id']);
        
        if ($success) {
            CLI::write("✅ Role '{$role['name']}' atribuída ao usuário {$userId}", 'green');
        } else {
            CLI::error('Erro ao atribuir role: ' . implode(', ', $userRoleModel->errors()));
        }
    }

    private function showRolePermissions()
    {
        $roleModel = new RoleModel();
        $slug = CLI::getOption('role-slug') ?: CLI::prompt('Slug da Role');
        
        $role = $roleModel->where('slug', $slug)->first();
        
        if (!$role) {
            CLI::error('Role não encontrada');
            return;
        }
        
        $permissions = $roleModel->getRolePermissions($role['id']);
        
        CLI::write("=== Permissões da Role '{$role['name']}' ===", 'yellow');
        CLI::newLine();
        
        if (empty($permissions)) {
            CLI::write('Nenhuma permissão atribuída', 'yellow');
            return;
        }
        
        foreach ($permissions as $permission) {
            CLI::write("• {$permission}", 'white');
        }
    }
}