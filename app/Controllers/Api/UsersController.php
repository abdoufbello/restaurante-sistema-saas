<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\UserModel;

/**
 * Controlador de Usuários para APIs RESTful
 */
class UsersController extends BaseApiController
{
    protected $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    
    /**
     * Listar usuários
     * GET /api/users
     */
    public function index()
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.read');
            
            // Parâmetros de paginação e ordenação
            $pagination = $this->getPaginationParams();
            $sort = $this->getSortParams(['id', 'username', 'email', 'first_name', 'last_name', 'role', 'status', 'created_at']);
            $dateFilters = $this->getDateFilters();
            
            // Filtros específicos
            $filters = [
                'search' => $this->request->getGet('search'),
                'role' => $this->request->getGet('role'),
                'status' => $this->request->getGet('status'),
                'restaurant_id' => $this->restaurantId // Multi-tenancy
            ];
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('users_list', array_merge($pagination, $sort, $filters, $dateFilters));
            $cachedData = $this->getFromCache($cacheKey);
            
            if ($cachedData) {
                return $this->respondWithPagination(
                    $cachedData['data'],
                    $cachedData['total'],
                    $pagination
                );
            }
            
            // Query base
            $query = $this->userModel->select([
                'id', 'restaurant_id', 'username', 'email', 'first_name', 'last_name',
                'phone', 'role', 'status', 'avatar', 'email_verified', 'last_login',
                'login_count', 'created_at', 'updated_at'
            ]);
            
            // Aplicar filtro de multi-tenancy
            $query = $this->applyTenantFilter($query);
            
            // Aplicar filtros
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->groupStart()
                      ->like('username', $search)
                      ->orLike('email', $search)
                      ->orLike('first_name', $search)
                      ->orLike('last_name', $search)
                      ->groupEnd();
            }
            
            if (!empty($filters['role'])) {
                if (is_array($filters['role'])) {
                    $query->whereIn('role', $filters['role']);
                } else {
                    $query->where('role', $filters['role']);
                }
            }
            
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            // Filtros de data
            if (!empty($dateFilters['created_from'])) {
                $query->where('created_at >=', $dateFilters['created_from']);
            }
            
            if (!empty($dateFilters['created_to'])) {
                $query->where('created_at <=', $dateFilters['created_to']);
            }
            
            // Contar total
            $total = $query->countAllResults(false);
            
            // Aplicar ordenação e paginação
            $users = $query->orderBy($sort['sort_by'], $sort['sort_dir'])
                          ->limit($pagination['limit'], $pagination['offset'])
                          ->findAll();
            
            // Sanitizar dados
            $users = $this->sanitizeOutput($users);
            
            // Salvar no cache
            $this->saveToCache($cacheKey, [
                'data' => $users,
                'total' => $total
            ]);
            
            return $this->respondWithPagination($users, $total, $pagination);
            
        } catch (\Exception $e) {
            log_message('error', 'Users index error: ' . $e->getMessage());
            return $this->failServerError('Erro ao buscar usuários');
        }
    }
    
    /**
     * Mostrar usuário específico
     * GET /api/users/{id}
     */
    public function show($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.read');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do usuário é obrigatório']);
            }
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('user_detail', ['id' => $id]);
            $cachedUser = $this->getFromCache($cacheKey);
            
            if ($cachedUser) {
                return $this->respondSuccess($cachedUser);
            }
            
            // Buscar usuário
            $query = $this->userModel->select([
                'id', 'restaurant_id', 'username', 'email', 'first_name', 'last_name',
                'phone', 'role', 'roles', 'permissions', 'status', 'avatar', 'preferences',
                'email_verified', 'email_verified_at', 'last_login', 'login_count',
                'created_at', 'updated_at'
            ]);
            
            // Aplicar filtro de multi-tenancy
            $query = $this->applyTenantFilter($query);
            
            $user = $query->find($id);
            
            if (!$user) {
                return $this->failNotFound('Usuário não encontrado');
            }
            
            // Sanitizar dados
            $user = $this->sanitizeOutput($user);
            
            // Adicionar informações extras se for admin
            if ($this->hasRole('admin') || $this->hasRole('super_admin')) {
                // Buscar tokens ativos
                $activeTokens = $this->jwtAuth->getUserActiveTokens($id);
                $user['active_sessions'] = count($activeTokens);
                
                // Estatísticas básicas
                $user['stats'] = [
                    'total_logins' => $user['login_count'] ?? 0,
                    'last_login' => $user['last_login'],
                    'account_age_days' => $user['created_at'] ? 
                        (int) ((time() - strtotime($user['created_at'])) / 86400) : 0
                ];
            }
            
            // Salvar no cache
            $this->saveToCache($cacheKey, $user);
            
            return $this->respondSuccess($user);
            
        } catch (\Exception $e) {
            log_message('error', 'Users show error: ' . $e->getMessage());
            return $this->failServerError('Erro ao buscar usuário');
        }
    }
    
    /**
     * Criar novo usuário
     * POST /api/users
     */
    public function create()
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.create');
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'username' => 'required|string|min_length[3]|max_length[50]|is_unique[users.username]',
                'email' => 'required|valid_email|is_unique[users.email]',
                'password' => 'required|min_length[8]',
                'first_name' => 'required|string|max_length[100]',
                'last_name' => 'required|string|max_length[100]',
                'phone' => 'permit_empty|string|max_length[20]',
                'role' => 'required|in_list[admin,manager,employee,customer]',
                'status' => 'permit_empty|in_list[active,inactive,suspended]',
                'permissions' => 'permit_empty|array',
                'preferences' => 'permit_empty|array'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Verificar se pode criar usuário com essa role
            if (!$this->canManageRole($validatedData['role'])) {
                return $this->failForbidden('Sem permissão para criar usuário com essa role');
            }
            
            // Preparar dados do usuário
            $userData = [
                'restaurant_id' => $this->restaurantId,
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'password' => password_hash($validatedData['password'], PASSWORD_DEFAULT),
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'phone' => $validatedData['phone'] ?? null,
                'role' => $validatedData['role'],
                'status' => $validatedData['status'] ?? 'active',
                'permissions' => json_encode($validatedData['permissions'] ?? []),
                'preferences' => json_encode($validatedData['preferences'] ?? []),
                'email_verified' => false,
                'created_by' => $this->currentUser['user_id']
            ];
            
            // Criar usuário
            $userId = $this->userModel->insert($userData);
            
            if (!$userId) {
                return $this->failServerError('Erro ao criar usuário');
            }
            
            // Buscar usuário criado
            $user = $this->userModel->find($userId);
            $user = $this->sanitizeOutput($user);
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('users_list'));
            
            // Log da atividade
            $this->logActivity('user_create', [
                'created_user_id' => $userId,
                'created_user_email' => $user['email'],
                'role' => $user['role']
            ]);
            
            return $this->respondSuccess($user, 'Usuário criado com sucesso', 201);
            
        } catch (\Exception $e) {
            log_message('error', 'Users create error: ' . $e->getMessage());
            return $this->failServerError('Erro ao criar usuário');
        }
    }
    
    /**
     * Atualizar usuário
     * PUT /api/users/{id}
     */
    public function update($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.update');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do usuário é obrigatório']);
            }
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Buscar usuário existente
            $query = $this->userModel->select('*');
            $query = $this->applyTenantFilter($query);
            $existingUser = $query->find($id);
            
            if (!$existingUser) {
                return $this->failNotFound('Usuário não encontrado');
            }
            
            // Verificar se pode editar este usuário
            if (!$this->canEditUser($existingUser)) {
                return $this->failForbidden('Sem permissão para editar este usuário');
            }
            
            // Validar dados de entrada
            $rules = [
                'username' => "permit_empty|string|min_length[3]|max_length[50]|is_unique[users.username,id,{$id}]",
                'email' => "permit_empty|valid_email|is_unique[users.email,id,{$id}]",
                'first_name' => 'permit_empty|string|max_length[100]',
                'last_name' => 'permit_empty|string|max_length[100]',
                'phone' => 'permit_empty|string|max_length[20]',
                'role' => 'permit_empty|in_list[admin,manager,employee,customer]',
                'status' => 'permit_empty|in_list[active,inactive,suspended]',
                'permissions' => 'permit_empty|array',
                'preferences' => 'permit_empty|array',
                'avatar' => 'permit_empty|string|max_length[255]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Verificar mudança de role
            if (isset($validatedData['role']) && $validatedData['role'] !== $existingUser['role']) {
                if (!$this->canManageRole($validatedData['role'])) {
                    return $this->failForbidden('Sem permissão para alterar para essa role');
                }
            }
            
            // Preparar dados para atualização
            $updateData = [];
            
            $allowedFields = ['username', 'email', 'first_name', 'last_name', 'phone', 'role', 'status', 'avatar'];
            
            foreach ($allowedFields as $field) {
                if (isset($validatedData[$field])) {
                    $updateData[$field] = $validatedData[$field];
                }
            }
            
            // Campos JSON
            if (isset($validatedData['permissions'])) {
                $updateData['permissions'] = json_encode($validatedData['permissions']);
            }
            
            if (isset($validatedData['preferences'])) {
                $updateData['preferences'] = json_encode($validatedData['preferences']);
            }
            
            if (!empty($updateData)) {
                $updateData['updated_by'] = $this->currentUser['user_id'];
                
                // Atualizar usuário
                $updated = $this->userModel->update($id, $updateData);
                
                if (!$updated) {
                    return $this->failServerError('Erro ao atualizar usuário');
                }
            }
            
            // Buscar dados atualizados
            $user = $this->userModel->find($id);
            $user = $this->sanitizeOutput($user);
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('users_list'));
            $this->deleteFromCache($this->generateCacheKey('user_detail', ['id' => $id]));
            
            // Log da atividade
            $this->logActivity('user_update', [
                'updated_user_id' => $id,
                'updated_fields' => array_keys($updateData)
            ]);
            
            return $this->respondSuccess($user, 'Usuário atualizado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Users update error: ' . $e->getMessage());
            return $this->failServerError('Erro ao atualizar usuário');
        }
    }
    
    /**
     * Excluir usuário
     * DELETE /api/users/{id}
     */
    public function delete($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.delete');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do usuário é obrigatório']);
            }
            
            // Buscar usuário existente
            $query = $this->userModel->select('*');
            $query = $this->applyTenantFilter($query);
            $user = $query->find($id);
            
            if (!$user) {
                return $this->failNotFound('Usuário não encontrado');
            }
            
            // Verificar se pode excluir este usuário
            if (!$this->canDeleteUser($user)) {
                return $this->failForbidden('Sem permissão para excluir este usuário');
            }
            
            // Não permitir auto-exclusão
            if ($id == $this->currentUser['user_id']) {
                return $this->failForbidden('Não é possível excluir sua própria conta');
            }
            
            // Soft delete
            $deleted = $this->userModel->delete($id);
            
            if (!$deleted) {
                return $this->failServerError('Erro ao excluir usuário');
            }
            
            // Revogar todos os tokens do usuário
            $this->jwtAuth->revokeUserTokens(
                $id,
                $this->currentUser['user_id'],
                'User deleted'
            );
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('users_list'));
            $this->deleteFromCache($this->generateCacheKey('user_detail', ['id' => $id]));
            
            // Log da atividade
            $this->logActivity('user_delete', [
                'deleted_user_id' => $id,
                'deleted_user_email' => $user['email']
            ]);
            
            return $this->respondSuccess(null, 'Usuário excluído com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Users delete error: ' . $e->getMessage());
            return $this->failServerError('Erro ao excluir usuário');
        }
    }
    
    /**
     * Alterar senha de usuário
     * PUT /api/users/{id}/password
     */
    public function changePassword($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.update');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do usuário é obrigatório']);
            }
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'new_password' => 'required|min_length[8]',
                'new_password_confirm' => 'required|matches[new_password]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar usuário existente
            $query = $this->userModel->select('*');
            $query = $this->applyTenantFilter($query);
            $user = $query->find($id);
            
            if (!$user) {
                return $this->failNotFound('Usuário não encontrado');
            }
            
            // Verificar se pode editar este usuário
            if (!$this->canEditUser($user)) {
                return $this->failForbidden('Sem permissão para alterar senha deste usuário');
            }
            
            // Atualizar senha
            $updated = $this->userModel->update($id, [
                'password' => password_hash($validatedData['new_password'], PASSWORD_DEFAULT),
                'password_changed_at' => date('Y-m-d H:i:s'),
                'updated_by' => $this->currentUser['user_id']
            ]);
            
            if (!$updated) {
                return $this->failServerError('Erro ao alterar senha');
            }
            
            // Revogar todos os tokens do usuário
            $this->jwtAuth->revokeUserTokens(
                $id,
                $this->currentUser['user_id'],
                'Password changed by admin'
            );
            
            // Log da atividade
            $this->logActivity('user_password_change', [
                'target_user_id' => $id,
                'target_user_email' => $user['email']
            ]);
            
            return $this->respondSuccess(null, 'Senha alterada com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Users change password error: ' . $e->getMessage());
            return $this->failServerError('Erro ao alterar senha');
        }
    }
    
    /**
     * Ativar/Desativar usuário
     * PUT /api/users/{id}/status
     */
    public function changeStatus($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.update');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do usuário é obrigatório']);
            }
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'status' => 'required|in_list[active,inactive,suspended]',
                'reason' => 'permit_empty|string|max_length[255]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar usuário existente
            $query = $this->userModel->select('*');
            $query = $this->applyTenantFilter($query);
            $user = $query->find($id);
            
            if (!$user) {
                return $this->failNotFound('Usuário não encontrado');
            }
            
            // Verificar se pode editar este usuário
            if (!$this->canEditUser($user)) {
                return $this->failForbidden('Sem permissão para alterar status deste usuário');
            }
            
            // Não permitir desativar própria conta
            if ($id == $this->currentUser['user_id'] && $validatedData['status'] !== 'active') {
                return $this->failForbidden('Não é possível desativar sua própria conta');
            }
            
            // Atualizar status
            $updated = $this->userModel->update($id, [
                'status' => $validatedData['status'],
                'updated_by' => $this->currentUser['user_id']
            ]);
            
            if (!$updated) {
                return $this->failServerError('Erro ao alterar status');
            }
            
            // Se desativado/suspenso, revogar tokens
            if ($validatedData['status'] !== 'active') {
                $this->jwtAuth->revokeUserTokens(
                    $id,
                    $this->currentUser['user_id'],
                    $validatedData['reason'] ?? 'Status changed to ' . $validatedData['status']
                );
            }
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('users_list'));
            $this->deleteFromCache($this->generateCacheKey('user_detail', ['id' => $id]));
            
            // Log da atividade
            $this->logActivity('user_status_change', [
                'target_user_id' => $id,
                'target_user_email' => $user['email'],
                'new_status' => $validatedData['status'],
                'reason' => $validatedData['reason'] ?? null
            ]);
            
            return $this->respondSuccess(null, 'Status alterado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Users change status error: ' . $e->getMessage());
            return $this->failServerError('Erro ao alterar status');
        }
    }
    
    /**
     * Buscar usuários
     * GET /api/users/search
     */
    public function search()
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.read');
            
            $query = $this->request->getGet('q');
            $limit = min(50, max(1, (int) $this->request->getGet('limit') ?: 10));
            
            if (empty($query) || strlen($query) < 2) {
                return $this->failValidationErrors(['q' => 'Termo de busca deve ter pelo menos 2 caracteres']);
            }
            
            // Buscar usuários
            $queryBuilder = $this->userModel->select([
                'id', 'username', 'email', 'first_name', 'last_name', 'role', 'status', 'avatar'
            ]);
            
            // Aplicar filtro de multi-tenancy
            $queryBuilder = $this->applyTenantFilter($queryBuilder);
            
            $users = $queryBuilder->groupStart()
                                 ->like('username', $query)
                                 ->orLike('email', $query)
                                 ->orLike('first_name', $query)
                                 ->orLike('last_name', $query)
                                 ->groupEnd()
                                 ->where('status', 'active')
                                 ->limit($limit)
                                 ->findAll();
            
            // Sanitizar dados
            $users = $this->sanitizeOutput($users);
            
            return $this->respondSuccess($users);
            
        } catch (\Exception $e) {
            log_message('error', 'Users search error: ' . $e->getMessage());
            return $this->failServerError('Erro ao buscar usuários');
        }
    }
    
    /**
     * Estatísticas de usuários
     * GET /api/users/stats
     */
    public function stats()
    {
        try {
            // Verificar permissão
            $this->requirePermission('users.read');
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('users_stats');
            $cachedStats = $this->getFromCache($cacheKey);
            
            if ($cachedStats) {
                return $this->respondSuccess($cachedStats);
            }
            
            // Query base com filtro de tenant
            $query = $this->userModel->select('*');
            $query = $this->applyTenantFilter($query);
            
            // Estatísticas gerais
            $stats = $query->select([
                'COUNT(*) as total_users',
                'COUNT(CASE WHEN status = "active" THEN 1 END) as active_users',
                'COUNT(CASE WHEN status = "inactive" THEN 1 END) as inactive_users',
                'COUNT(CASE WHEN status = "suspended" THEN 1 END) as suspended_users',
                'COUNT(CASE WHEN email_verified = 1 THEN 1 END) as verified_users',
                'COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_last_30_days',
                'COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_last_30_days'
            ])->first();
            
            // Estatísticas por role
            $byRole = $query->select([
                'role',
                'COUNT(*) as count'
            ])->groupBy('role')
              ->orderBy('count', 'DESC')
              ->findAll();
            
            // Usuários mais ativos (por login)
            $mostActive = $query->select([
                'id', 'username', 'email', 'first_name', 'last_name', 'login_count', 'last_login'
            ])->where('login_count >', 0)
              ->orderBy('login_count', 'DESC')
              ->limit(10)
              ->findAll();
            
            $result = [
                'general' => $stats,
                'by_role' => $byRole,
                'most_active' => $this->sanitizeOutput($mostActive)
            ];
            
            // Salvar no cache por 10 minutos
            $this->saveToCache($cacheKey, $result, 600);
            
            return $this->respondSuccess($result);
            
        } catch (\Exception $e) {
            log_message('error', 'Users stats error: ' . $e->getMessage());
            return $this->failServerError('Erro ao obter estatísticas');
        }
    }
    
    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================
    
    /**
     * Verifica se pode gerenciar uma role específica
     */
    private function canManageRole(string $role): bool
    {
        // Super admin pode gerenciar qualquer role
        if ($this->hasRole('super_admin')) {
            return true;
        }
        
        // Admin pode gerenciar roles abaixo de admin
        if ($this->hasRole('admin')) {
            return in_array($role, ['manager', 'employee', 'customer']);
        }
        
        // Manager pode gerenciar apenas employee e customer
        if ($this->hasRole('manager')) {
            return in_array($role, ['employee', 'customer']);
        }
        
        return false;
    }
    
    /**
     * Verifica se pode editar um usuário específico
     */
    private function canEditUser(array $user): bool
    {
        // Super admin pode editar qualquer usuário
        if ($this->hasRole('super_admin')) {
            return true;
        }
        
        // Admin pode editar usuários com roles menores
        if ($this->hasRole('admin')) {
            return in_array($user['role'], ['manager', 'employee', 'customer']);
        }
        
        // Manager pode editar apenas employee e customer
        if ($this->hasRole('manager')) {
            return in_array($user['role'], ['employee', 'customer']);
        }
        
        // Usuário pode editar apenas a si mesmo (em alguns casos)
        return $user['id'] == $this->currentUser['user_id'];
    }
    
    /**
     * Verifica se pode excluir um usuário específico
     */
    private function canDeleteUser(array $user): bool
    {
        // Super admin pode excluir qualquer usuário (exceto a si mesmo)
        if ($this->hasRole('super_admin')) {
            return $user['id'] != $this->currentUser['user_id'];
        }
        
        // Admin pode excluir usuários com roles menores
        if ($this->hasRole('admin')) {
            return in_array($user['role'], ['manager', 'employee', 'customer']) && 
                   $user['id'] != $this->currentUser['user_id'];
        }
        
        // Manager pode excluir apenas employee e customer
        if ($this->hasRole('manager')) {
            return in_array($user['role'], ['employee', 'customer']);
        }
        
        return false;
    }
}