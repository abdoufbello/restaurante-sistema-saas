<?php

namespace App\Models;

use App\Models\TenantModel;

/**
 * Modelo para Funcionários com Multi-Tenancy
 */
class EmployeeModel extends TenantModel
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'username',
        'password',
        'name',
        'email',
        'role',
        'permissions',
        'is_active',
        'last_login'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'username' => 'required|min_length[3]|max_length[50]',
        'password' => 'required|min_length[6]',
        'name' => 'required|min_length[2]|max_length[255]',
        'email' => 'required|valid_email|max_length[255]',
        'role' => 'in_list[admin,manager,employee]',
        'is_active' => 'in_list[0,1]'
    ];
    
    protected $validationMessages = [
        'username' => [
            'required' => 'Nome de usuário é obrigatório',
            'min_length' => 'Nome de usuário deve ter pelo menos 3 caracteres',
            'max_length' => 'Nome de usuário deve ter no máximo 50 caracteres'
        ],
        'password' => [
            'required' => 'Senha é obrigatória',
            'min_length' => 'Senha deve ter pelo menos 6 caracteres'
        ],
        'name' => [
            'required' => 'Nome é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres'
        ],
        'email' => [
            'required' => 'E-mail é obrigatório',
            'valid_email' => 'E-mail deve ter um formato válido'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];
    
    /**
     * Hash da senha antes de salvar
     */
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        
        return $data;
    }
    
    /**
     * Busca funcionário por username dentro do tenant
     */
    public function findByUsername(string $username)
    {
        return $this->where('username', $username)
                   ->where('is_active', 1)
                   ->first();
    }
    
    /**
     * Busca funcionário por email dentro do tenant
     */
    public function findByEmail(string $email)
    {
        return $this->where('email', $email)
                   ->where('is_active', 1)
                   ->first();
    }
    
    /**
     * Verifica se username já existe no tenant
     */
    public function usernameExists(string $username, int $excludeId = null): bool
    {
        $query = $this->where('username', $username);
        
        if ($excludeId) {
            $query->where('id !=', $excludeId);
        }
        
        return $query->countAllResults() > 0;
    }
    
    /**
     * Verifica se email já existe no tenant
     */
    public function emailExists(string $email, int $excludeId = null): bool
    {
        $query = $this->where('email', $email);
        
        if ($excludeId) {
            $query->where('id !=', $excludeId);
        }
        
        return $query->countAllResults() > 0;
    }
    
    /**
     * Autentica funcionário
     */
    public function authenticate(string $username, string $password)
    {
        $employee = $this->findByUsername($username);
        
        if ($employee && password_verify($password, $employee['password'])) {
            // Atualizar último login
            $this->update($employee['id'], ['last_login' => date('Y-m-d H:i:s')]);
            
            // Remover senha do retorno
            unset($employee['password']);
            
            return $employee;
        }
        
        return false;
    }
    
    /**
     * Obtém funcionários ativos do tenant
     */
    public function getActiveEmployees()
    {
        return $this->where('is_active', 1)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém funcionários por role
     */
    public function getByRole(string $role)
    {
        return $this->where('role', $role)
                   ->where('is_active', 1)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Verifica se funcionário tem permissão específica
     */
    public function hasPermission(int $employeeId, string $permission): bool
    {
        $employee = $this->find($employeeId);
        
        if (!$employee) {
            return false;
        }
        
        // Admin sempre tem todas as permissões
        if ($employee['role'] === 'admin') {
            return true;
        }
        
        $permissions = json_decode($employee['permissions'] ?? '[]', true);
        return in_array($permission, $permissions);
    }
    
    /**
     * Define permissões para um funcionário
     */
    public function setPermissions(int $employeeId, array $permissions): bool
    {
        return $this->update($employeeId, [
            'permissions' => json_encode($permissions)
        ]);
    }
    
    /**
     * Desativa funcionário (soft delete)
     */
    public function deactivate(int $employeeId): bool
    {
        return $this->update($employeeId, ['is_active' => 0]);
    }
    
    /**
     * Reativa funcionário
     */
    public function activate(int $employeeId): bool
    {
        return $this->update($employeeId, ['is_active' => 1]);
    }
    
    /**
     * Conta funcionários ativos do tenant
     */
    public function countActive(): int
    {
        return $this->where('is_active', 1)->countAllResults();
    }
    
    /**
     * Obtém estatísticas de funcionários
     */
    public function getStats(): array
    {
        return [
            'total' => $this->countAllResults(),
            'active' => $this->where('is_active', 1)->countAllResults(),
            'inactive' => $this->where('is_active', 0)->countAllResults(),
            'admins' => $this->where('role', 'admin')->where('is_active', 1)->countAllResults(),
            'managers' => $this->where('role', 'manager')->where('is_active', 1)->countAllResults(),
            'employees' => $this->where('role', 'employee')->where('is_active', 1)->countAllResults()
        ];
    }
}