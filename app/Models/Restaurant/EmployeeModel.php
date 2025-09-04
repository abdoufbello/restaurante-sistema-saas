<?php

namespace App\Models\Restaurant;

use CodeIgniter\Model;

/**
 * Employee Model
 * Manages employee/operator data for the Kiosk system
 */
class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'username',
        'password',
        'full_name',
        'email',
        'phone',
        'role',
        'permissions',
        'status',
        'last_login_at',
        'login_attempts',
        'locked_until'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|is_natural_no_zero',
        'username' => 'required|min_length[3]|max_length[50]|is_unique[employees.username,id,{id}]',
        'password' => 'required|min_length[6]',
        'full_name' => 'required|min_length[3]|max_length[255]',
        'email' => 'permit_empty|valid_email|is_unique[employees.email,id,{id}]',
        'phone' => 'permit_empty|min_length[10]|max_length[15]',
        'role' => 'required|in_list[admin,manager,operator,cashier]',
        'status' => 'required|in_list[active,inactive,suspended]'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'is_natural_no_zero' => 'ID do restaurante deve ser um número válido'
        ],
        'username' => [
            'required' => 'Nome de usuário é obrigatório',
            'min_length' => 'Nome de usuário deve ter pelo menos 3 caracteres',
            'max_length' => 'Nome de usuário deve ter no máximo 50 caracteres',
            'is_unique' => 'Este nome de usuário já está em uso'
        ],
        'password' => [
            'required' => 'Senha é obrigatória',
            'min_length' => 'Senha deve ter pelo menos 6 caracteres'
        ],
        'full_name' => [
            'required' => 'Nome completo é obrigatório',
            'min_length' => 'Nome completo deve ter pelo menos 3 caracteres',
            'max_length' => 'Nome completo deve ter no máximo 255 caracteres'
        ],
        'email' => [
            'valid_email' => 'Email deve ser válido',
            'is_unique' => 'Este email já está cadastrado'
        ],
        'phone' => [
            'min_length' => 'Telefone deve ter pelo menos 10 dígitos',
            'max_length' => 'Telefone deve ter no máximo 15 dígitos'
        ],
        'role' => [
            'required' => 'Função é obrigatória',
            'in_list' => 'Função deve ser: admin, manager, operator ou cashier'
        ],
        'status' => [
            'required' => 'Status é obrigatório',
            'in_list' => 'Status deve ser: active, inactive ou suspended'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['beforeInsert'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['beforeUpdate'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['afterFind'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Before insert callback
     */
    protected function beforeInsert(array $data)
    {
        // Hash password
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }

        // Clean phone
        if (isset($data['data']['phone'])) {
            $data['data']['phone'] = preg_replace('/[^0-9]/', '', $data['data']['phone']);
        }

        // Set default permissions based on role
        if (!isset($data['data']['permissions']) && isset($data['data']['role'])) {
            $data['data']['permissions'] = json_encode($this->getDefaultPermissions($data['data']['role']));
        }

        // Initialize login attempts
        $data['data']['login_attempts'] = 0;

        return $data;
    }

    /**
     * Before update callback
     */
    protected function beforeUpdate(array $data)
    {
        // Hash password if provided
        if (isset($data['data']['password']) && !empty($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        } else {
            // Remove password from update if empty
            unset($data['data']['password']);
        }

        // Clean phone
        if (isset($data['data']['phone'])) {
            $data['data']['phone'] = preg_replace('/[^0-9]/', '', $data['data']['phone']);
        }

        // Update permissions if role changed
        if (isset($data['data']['role']) && !isset($data['data']['permissions'])) {
            $data['data']['permissions'] = json_encode($this->getDefaultPermissions($data['data']['role']));
        }

        return $data;
    }

    /**
     * After find callback
     */
    protected function afterFind(array $data)
    {
        if (isset($data['data'])) {
            // Single record
            if (isset($data['data']['permissions']) && is_string($data['data']['permissions'])) {
                $data['data']['permissions'] = json_decode($data['data']['permissions'], true);
            }
        } else {
            // Multiple records
            foreach ($data as &$record) {
                if (isset($record['permissions']) && is_string($record['permissions'])) {
                    $record['permissions'] = json_decode($record['permissions'], true);
                }
            }
        }

        return $data;
    }

    /**
     * Get default permissions by role
     */
    private function getDefaultPermissions($role)
    {
        $permissions = [
            'admin' => [
                'dashboard' => true,
                'dishes' => ['create', 'read', 'update', 'delete'],
                'categories' => ['create', 'read', 'update', 'delete'],
                'orders' => ['read', 'update', 'delete'],
                'employees' => ['create', 'read', 'update', 'delete'],
                'reports' => ['read'],
                'settings' => ['read', 'update'],
                'kiosk' => ['read']
            ],
            'manager' => [
                'dashboard' => true,
                'dishes' => ['create', 'read', 'update'],
                'categories' => ['create', 'read', 'update'],
                'orders' => ['read', 'update'],
                'employees' => ['read'],
                'reports' => ['read'],
                'settings' => ['read'],
                'kiosk' => ['read']
            ],
            'operator' => [
                'dashboard' => true,
                'dishes' => ['read'],
                'categories' => ['read'],
                'orders' => ['read', 'update'],
                'kiosk' => ['read']
            ],
            'cashier' => [
                'dashboard' => true,
                'orders' => ['read', 'update'],
                'kiosk' => ['read']
            ]
        ];

        return $permissions[$role] ?? $permissions['operator'];
    }

    /**
     * Authenticate employee
     */
    public function authenticate($username, $password, $restaurantId)
    {
        $employee = $this->where('username', $username)
                         ->where('restaurant_id', $restaurantId)
                         ->where('status', 'active')
                         ->first();

        if (!$employee) {
            return false;
        }

        // Check if account is locked
        if ($employee['locked_until'] && strtotime($employee['locked_until']) > time()) {
            return ['error' => 'Conta bloqueada temporariamente. Tente novamente mais tarde.'];
        }

        // Verify password
        if (!password_verify($password, $employee['password'])) {
            $this->incrementLoginAttempts($employee['id']);
            return false;
        }

        // Reset login attempts and update last login
        $this->update($employee['id'], [
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s')
        ]);

        // Remove password from returned data
        unset($employee['password']);

        return $employee;
    }

    /**
     * Increment login attempts
     */
    private function incrementLoginAttempts($employeeId)
    {
        $employee = $this->find($employeeId);
        $attempts = $employee['login_attempts'] + 1;
        
        $updateData = ['login_attempts' => $attempts];
        
        // Lock account after 5 failed attempts for 30 minutes
        if ($attempts >= 5) {
            $updateData['locked_until'] = date('Y-m-d H:i:s', time() + (30 * 60));
        }
        
        $this->update($employeeId, $updateData);
    }

    /**
     * Get employees by restaurant
     */
    public function getByRestaurant($restaurantId, $status = null)
    {
        $builder = $this->where('restaurant_id', $restaurantId);
        
        if ($status) {
            $builder->where('status', $status);
        }
        
        return $builder->findAll();
    }

    /**
     * Get active employees by restaurant
     */
    public function getActiveByRestaurant($restaurantId)
    {
        return $this->getByRestaurant($restaurantId, 'active');
    }

    /**
     * Check if employee has permission
     */
    public function hasPermission($employeeId, $resource, $action = null)
    {
        $employee = $this->find($employeeId);
        
        if (!$employee || !isset($employee['permissions'][$resource])) {
            return false;
        }
        
        $permission = $employee['permissions'][$resource];
        
        // If permission is boolean (like dashboard)
        if (is_bool($permission)) {
            return $permission;
        }
        
        // If permission is array (like dishes, orders)
        if (is_array($permission) && $action) {
            return in_array($action, $permission);
        }
        
        return false;
    }

    /**
     * Update employee permissions
     */
    public function updatePermissions($employeeId, array $permissions)
    {
        return $this->update($employeeId, ['permissions' => json_encode($permissions)]);
    }

    /**
     * Get employee statistics
     */
    public function getStatistics($restaurantId)
    {
        $total = $this->where('restaurant_id', $restaurantId)->countAllResults(false);
        $active = $this->where('status', 'active')->countAllResults(false);
        $inactive = $this->where('status', 'inactive')->countAllResults(false);
        $suspended = $this->where('status', 'suspended')->countAllResults();
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'suspended' => $suspended
        ];
    }

    /**
     * Reset employee password
     */
    public function resetPassword($employeeId, $newPassword)
    {
        return $this->update($employeeId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'login_attempts' => 0,
            'locked_until' => null
        ]);
    }
}