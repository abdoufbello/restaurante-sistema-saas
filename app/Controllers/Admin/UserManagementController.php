<?php

namespace App\Controllers\Admin;

use App\Controllers\Secure_Controller;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\UserRoleModel;

/**
 * Controlador para Gestão de Usuários, Roles e Permissões
 */
class UserManagementController extends Secure_Controller
{
    protected $userModel;
    protected $roleModel;
    protected $permissionModel;
    protected $userRoleModel;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->permissionModel = new PermissionModel();
        $this->userRoleModel = new UserRoleModel();
        
        // Verifica se o usuário tem permissão para acessar este módulo
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.read')) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado');
        }
    }
    
    // ========================================
    // GESTÃO DE USUÁRIOS
    // ========================================
    
    /**
     * Lista usuários
     */
    public function users()
    {
        $data = [
            'title' => 'Gestão de Usuários',
            'users' => $this->userModel->findAll(),
            'roles' => $this->roleModel->getActiveRoles()
        ];
        
        return view('admin/user_management/users', $data);
    }
    
    /**
     * Formulário para criar usuário
     */
    public function createUser()
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.create')) {
            return redirect()->back()->with('error', 'Você não tem permissão para criar usuários');
        }
        
        $data = [
            'title' => 'Criar Usuário',
            'roles' => $this->roleModel->getActiveRoles()
        ];
        
        return view('admin/user_management/create_user', $data);
    }
    
    /**
     * Salva novo usuário
     */
    public function storeUser()
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.create')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'name' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email,restaurant_id,' . $this->session->get('restaurant_id') . ']',
            'password' => 'required|min_length[6]',
            'phone' => 'permit_empty|max_length[20]',
            'roles' => 'permit_empty'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validation->getErrors()
            ]);
        }
        
        $userData = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'phone' => $this->request->getPost('phone'),
            'is_active' => 1
        ];
        
        $userId = $this->userModel->insert($userData);
        
        if ($userId) {
            // Atribui roles se fornecidas
            $roles = $this->request->getPost('roles');
            if ($roles && is_array($roles)) {
                $this->userModel->updateUserRoles($userId, $roles, $this->session->get('user_id'));
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuário criado com sucesso',
                'user_id' => $userId
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao criar usuário'
        ]);
    }
    
    /**
     * Formulário para editar usuário
     */
    public function editUser($userId)
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.update')) {
            return redirect()->back()->with('error', 'Você não tem permissão para editar usuários');
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Usuário não encontrado');
        }
        
        $data = [
            'title' => 'Editar Usuário',
            'user' => $user,
            'userRoles' => $this->userModel->getUserRoles($userId),
            'userPermissions' => $this->userModel->getUserPermissions($userId),
            'roles' => $this->roleModel->getActiveRoles(),
            'permissions' => $this->permissionModel->getGroupedPermissions()
        ];
        
        return view('admin/user_management/edit_user', $data);
    }
    
    /**
     * Atualiza usuário
     */
    public function updateUser($userId)
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.update')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'Usuário não encontrado']);
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'name' => 'required|min_length[2]|max_length[100]',
            'email' => "required|valid_email|is_unique[users.email,id,{$userId}]",
            'phone' => 'permit_empty|max_length[20]',
            'is_active' => 'permit_empty|in_list[0,1]',
            'roles' => 'permit_empty',
            'custom_permissions' => 'permit_empty'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validation->getErrors()
            ]);
        }
        
        $userData = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'is_active' => $this->request->getPost('is_active') ?? 1
        ];
        
        // Atualiza senha se fornecida
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $userData['password'] = $password;
        }
        
        if ($this->userModel->update($userId, $userData)) {
            // Atualiza roles
            $roles = $this->request->getPost('roles');
            if ($roles !== null) {
                $roleIds = is_array($roles) ? $roles : [];
                $this->userModel->updateUserRoles($userId, $roleIds, $this->session->get('user_id'));
            }
            
            // Atualiza permissões customizadas
            $customPermissions = $this->request->getPost('custom_permissions');
            if ($customPermissions !== null) {
                $permissions = is_array($customPermissions) ? $customPermissions : [];
                $this->userModel->setCustomPermissions($userId, $permissions);
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuário atualizado com sucesso'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao atualizar usuário'
        ]);
    }
    
    /**
     * Exclui usuário
     */
    public function deleteUser($userId)
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.delete')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'Usuário não encontrado']);
        }
        
        // Não permite excluir o próprio usuário
        if ($userId == $this->session->get('user_id')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Você não pode excluir sua própria conta']);
        }
        
        if ($this->userModel->delete($userId)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuário excluído com sucesso'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao excluir usuário'
        ]);
    }
    
    // ========================================
    // GESTÃO DE ROLES
    // ========================================
    
    /**
     * Lista roles
     */
    public function roles()
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
            return redirect()->back()->with('error', 'Você não tem permissão para gerenciar roles');
        }
        
        $data = [
            'title' => 'Gestão de Funções',
            'roles' => $this->roleModel->findAll(),
            'stats' => $this->roleModel->getRoleStats()
        ];
        
        return view('admin/user_management/roles', $data);
    }
    
    /**
     * Formulário para criar role
     */
    public function createRole()
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
            return redirect()->back()->with('error', 'Acesso negado');
        }
        
        $data = [
            'title' => 'Criar Função',
            'permissions' => $this->permissionModel->getGroupedPermissions()
        ];
        
        return view('admin/user_management/create_role', $data);
    }
    
    /**
     * Salva nova role
     */
    public function storeRole()
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'name' => 'required|min_length[2]|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
            'level' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[100]',
            'color' => 'permit_empty|max_length[7]',
            'icon' => 'permit_empty|max_length[50]',
            'permissions' => 'permit_empty'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validation->getErrors()
            ]);
        }
        
        $roleData = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'level' => $this->request->getPost('level') ?? 10,
            'color' => $this->request->getPost('color') ?? '#6c757d',
            'icon' => $this->request->getPost('icon') ?? 'user',
            'is_system_role' => 0,
            'is_active' => 1,
            'created_by' => $this->session->get('user_id')
        ];
        
        $permissions = $this->request->getPost('permissions');
        if ($permissions && is_array($permissions)) {
            $roleData['permissions'] = json_encode($permissions);
        }
        
        $roleId = $this->roleModel->insert($roleData);
        
        if ($roleId) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Função criada com sucesso',
                'role_id' => $roleId
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao criar função'
        ]);
    }
    
    /**
     * Formulário para editar role
     */
    public function editRole($roleId)
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
            return redirect()->back()->with('error', 'Acesso negado');
        }
        
        $role = $this->roleModel->find($roleId);
        if (!$role) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Função não encontrada');
        }
        
        $data = [
            'title' => 'Editar Função',
            'role' => $role,
            'rolePermissions' => $this->roleModel->getRolePermissions($roleId),
            'permissions' => $this->permissionModel->getGroupedPermissions(),
            'users' => $this->userRoleModel->getRoleUsers($roleId)
        ];
        
        return view('admin/user_management/edit_role', $data);
    }
    
    /**
     * Atualiza role
     */
    public function updateRole($roleId)
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $role = $this->roleModel->find($roleId);
        if (!$role) {
            return $this->response->setJSON(['success' => false, 'message' => 'Função não encontrada']);
        }
        
        // Não permite editar roles do sistema
        if ($role['is_system_role']) {
            return $this->response->setJSON(['success' => false, 'message' => 'Não é possível editar funções do sistema']);
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'name' => 'required|min_length[2]|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
            'level' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[100]',
            'color' => 'permit_empty|max_length[7]',
            'icon' => 'permit_empty|max_length[50]',
            'is_active' => 'permit_empty|in_list[0,1]',
            'permissions' => 'permit_empty'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validation->getErrors()
            ]);
        }
        
        $roleData = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'level' => $this->request->getPost('level') ?? $role['level'],
            'color' => $this->request->getPost('color') ?? $role['color'],
            'icon' => $this->request->getPost('icon') ?? $role['icon'],
            'is_active' => $this->request->getPost('is_active') ?? 1,
            'updated_by' => $this->session->get('user_id')
        ];
        
        $permissions = $this->request->getPost('permissions');
        if ($permissions !== null) {
            $roleData['permissions'] = json_encode(is_array($permissions) ? $permissions : []);
        }
        
        if ($this->roleModel->update($roleId, $roleData)) {
            // Limpa cache de permissões dos usuários com esta role
            $roleUsers = $this->userRoleModel->getRoleUsers($roleId);
            foreach ($roleUsers as $user) {
                $this->userModel->clearPermissionsCache($user['user_id']);
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Função atualizada com sucesso'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao atualizar função'
        ]);
    }
    
    /**
     * Exclui role
     */
    public function deleteRole($roleId)
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $role = $this->roleModel->find($roleId);
        if (!$role) {
            return $this->response->setJSON(['success' => false, 'message' => 'Função não encontrada']);
        }
        
        // Não permite excluir roles do sistema
        if ($role['is_system_role']) {
            return $this->response->setJSON(['success' => false, 'message' => 'Não é possível excluir funções do sistema']);
        }
        
        // Verifica se há usuários com esta role
        $usersCount = $this->userRoleModel->where('role_id', $roleId)->where('is_active', 1)->countAllResults();
        if ($usersCount > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Não é possível excluir esta função pois há {$usersCount} usuário(s) associado(s)"
            ]);
        }
        
        if ($this->roleModel->delete($roleId)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Função excluída com sucesso'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao excluir função'
        ]);
    }
    
    // ========================================
    // GESTÃO DE PERMISSÕES
    // ========================================
    
    /**
     * Lista permissões
     */
    public function permissions()
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
            return redirect()->back()->with('error', 'Acesso negado');
        }
        
        $data = [
            'title' => 'Gestão de Permissões',
            'permissions' => $this->permissionModel->getGroupedPermissions(),
            'stats' => $this->permissionModel->getPermissionStats()
        ];
        
        return view('admin/user_management/permissions', $data);
    }
    
    /**
     * Cria permissões do sistema
     */
    public function createSystemPermissions()
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'admin.system_info')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $createdPermissions = $this->permissionModel->createSystemPermissions();
        
        return $this->response->setJSON([
            'success' => true,
            'message' => count($createdPermissions) . ' permissões criadas com sucesso',
            'created_count' => count($createdPermissions)
        ]);
    }
    
    /**
     * Cria roles do sistema
     */
    public function createSystemRoles()
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'admin.system_info')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $createdRoles = $this->roleModel->createSystemRoles();
        
        return $this->response->setJSON([
            'success' => true,
            'message' => count($createdRoles) . ' funções criadas com sucesso',
            'created_count' => count($createdRoles)
        ]);
    }
    
    // ========================================
    // RELATÓRIOS E ESTATÍSTICAS
    // ========================================
    
    /**
     * Dashboard de gestão de usuários
     */
    public function dashboard()
    {
        $data = [
            'title' => 'Dashboard - Gestão de Usuários',
            'userStats' => $this->userModel->getStats(),
            'roleStats' => $this->roleModel->getRoleStats(),
            'permissionStats' => $this->permissionModel->getPermissionStats(),
            'assignmentStats' => $this->userRoleModel->getAssignmentStats(),
            'recentUsers' => $this->userModel->getRecentUsers(10),
            'expiringAssignments' => $this->userRoleModel->getExpiringAssignments(30)
        ];
        
        return view('admin/user_management/dashboard', $data);
    }
    
    /**
     * Exporta dados para CSV
     */
    public function export($type)
    {
        $filters = $this->request->getGet();
        
        switch ($type) {
            case 'users':
                if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.read')) {
                    throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado');
                }
                $csv = $this->userModel->exportToCSV($filters);
                $filename = 'usuarios_' . date('Y-m-d_H-i-s') . '.csv';
                break;
                
            case 'roles':
                if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
                    throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado');
                }
                $csv = $this->roleModel->exportToCSV($filters);
                $filename = 'funcoes_' . date('Y-m-d_H-i-s') . '.csv';
                break;
                
            case 'permissions':
                if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
                    throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado');
                }
                $csv = $this->permissionModel->exportToCSV($filters);
                $filename = 'permissoes_' . date('Y-m-d_H-i-s') . '.csv';
                break;
                
            case 'assignments':
                if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.manage_roles')) {
                    throw new \CodeIgniter\Exceptions\PageNotFoundException('Acesso negado');
                }
                $csv = $this->userRoleModel->exportToCSV($filters);
                $filename = 'atribuicoes_' . date('Y-m-d_H-i-s') . '.csv';
                break;
                
            default:
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Tipo de exportação inválido');
        }
        
        return $this->response
                   ->setHeader('Content-Type', 'text/csv')
                   ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                   ->setBody($csv);
    }
    
    // ========================================
    // AJAX/API ENDPOINTS
    // ========================================
    
    /**
     * Busca usuários via AJAX
     */
    public function searchUsers()
    {
        $search = $this->request->getGet('search');
        $filters = [
            'search' => $search,
            'is_active' => $this->request->getGet('is_active'),
            'role' => $this->request->getGet('role')
        ];
        
        $users = $this->userModel->searchUsers($filters);
        
        return $this->response->setJSON([
            'success' => true,
            'users' => $users
        ]);
    }
    
    /**
     * Obtém permissões de uma role via AJAX
     */
    public function getRolePermissions($roleId)
    {
        $permissions = $this->roleModel->getRolePermissions($roleId);
        
        return $this->response->setJSON([
            'success' => true,
            'permissions' => $permissions
        ]);
    }
    
    /**
     * Obtém usuários de uma role via AJAX
     */
    public function getRoleUsers($roleId)
    {
        $users = $this->userRoleModel->getRoleUsers($roleId);
        
        return $this->response->setJSON([
            'success' => true,
            'users' => $users
        ]);
    }
    
    /**
     * Atualiza status de usuário via AJAX
     */
    public function toggleUserStatus($userId)
    {
        if (!$this->userModel->hasPermission($this->session->get('user_id'), 'users.update')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acesso negado']);
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'Usuário não encontrado']);
        }
        
        $newStatus = $user['is_active'] ? 0 : 1;
        
        if ($this->userModel->update($userId, ['is_active' => $newStatus])) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Status atualizado com sucesso',
                'new_status' => $newStatus
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao atualizar status'
        ]);
    }
}