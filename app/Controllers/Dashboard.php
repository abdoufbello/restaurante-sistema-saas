<?php

namespace App\Controllers;

use App\Models\RestaurantModel;
use App\Models\EmployeeModel;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    protected $restaurantModel;
    protected $employeeModel;
    protected $session;
    
    public function __construct()
    {
        $this->restaurantModel = new RestaurantModel();
        $this->employeeModel = new EmployeeModel();
        $this->session = \Config\Services::session();
    }
    
    /**
     * Página principal do dashboard
     */
    public function index()
    {
        // Verificar autenticação
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $restaurantId = $this->session->get('restaurant_id');
        
        // Buscar dados do restaurante
        $restaurant = $this->restaurantModel->find($restaurantId);
        
        // Buscar funcionários do restaurante
        $employees = $this->employeeModel->findByRestaurant($restaurantId);
        
        // Estatísticas básicas
        $stats = [
            'total_employees' => count($employees),
            'active_employees' => count($this->employeeModel->findActive($restaurantId)),
            'admin_employees' => count($this->employeeModel->findAdmins($restaurantId))
        ];
        
        $data = [
            'title' => 'Dashboard - ' . $restaurant['name'],
            'restaurant' => $restaurant,
            'employees' => $employees,
            'stats' => $stats,
            'user' => [
                'name' => $this->session->get('name'),
                'role' => $this->session->get('role'),
                'username' => $this->session->get('username')
            ],
            'message' => $this->session->getFlashdata('message'),
            'errors' => $this->session->getFlashdata('errors')
        ];
        
        return view('dashboard/index', $data);
    }
    
    /**
     * Perfil do usuário
     */
    public function profile()
    {
        // Verificar autenticação
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $userId = $this->session->get('user_id');
        $employee = $this->employeeModel->find($userId);
        
        if (!$employee) {
            $this->session->setFlashdata('errors', ['Usuário não encontrado']);
            return redirect()->to('/dashboard');
        }
        
        $data = [
            'title' => 'Meu Perfil',
            'employee' => $employee,
            'message' => $this->session->getFlashdata('message'),
            'errors' => $this->session->getFlashdata('errors')
        ];
        
        return view('dashboard/profile', $data);
    }
    
    /**
     * Atualizar perfil
     */
    public function updateProfile()
    {
        // Verificar autenticação
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'email' => 'required|valid_email|max_length[255]'
        ];
        
        $messages = [
            'name' => [
                'required' => 'Nome é obrigatório',
                'min_length' => 'Nome deve ter pelo menos 3 caracteres'
            ],
            'email' => [
                'required' => 'Email é obrigatório',
                'valid_email' => 'Email inválido'
            ]
        ];
        
        if (!$this->validate($rules, $messages)) {
            $this->session->setFlashdata('errors', $validation->getErrors());
            return redirect()->back()->withInput();
        }
        
        $userId = $this->session->get('user_id');
        
        $updateData = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email')
        ];
        
        $result = $this->employeeModel->update($userId, $updateData);
        
        if ($result['success']) {
            // Atualizar dados da sessão
            $this->session->set([
                'name' => $updateData['name'],
                'email' => $updateData['email']
            ]);
            
            $this->session->setFlashdata('message', 'Perfil atualizado com sucesso!');
        } else {
            $this->session->setFlashdata('errors', $result['errors']);
        }
        
        return redirect()->to('/dashboard/profile');
    }
    
    /**
     * Alterar senha
     */
    public function changePassword()
    {
        // Verificar autenticação
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[new_password]'
        ];
        
        $messages = [
            'current_password' => [
                'required' => 'Senha atual é obrigatória'
            ],
            'new_password' => [
                'required' => 'Nova senha é obrigatória',
                'min_length' => 'Nova senha deve ter pelo menos 6 caracteres'
            ],
            'confirm_password' => [
                'required' => 'Confirmação de senha é obrigatória',
                'matches' => 'Senhas não coincidem'
            ]
        ];
        
        if (!$this->validate($rules, $messages)) {
            $this->session->setFlashdata('errors', $validation->getErrors());
            return redirect()->back();
        }
        
        $userId = $this->session->get('user_id');
        $username = $this->session->get('username');
        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');
        
        // Verificar senha atual
        $employee = $this->employeeModel->authenticate($username, $currentPassword);
        
        if (!$employee) {
            $this->session->setFlashdata('errors', ['Senha atual incorreta']);
            return redirect()->back();
        }
        
        // Alterar senha
        $result = $this->employeeModel->changePassword($userId, $newPassword);
        
        if ($result['success']) {
            $this->session->setFlashdata('message', 'Senha alterada com sucesso!');
        } else {
            $this->session->setFlashdata('errors', $result['errors']);
        }
        
        return redirect()->to('/dashboard/profile');
    }
    
    /**
     * Gerenciar funcionários (apenas admin)
     */
    public function employees()
    {
        // Verificar autenticação e permissão
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        if ($this->session->get('role') !== 'admin') {
            $this->session->setFlashdata('errors', ['Acesso negado. Apenas administradores podem gerenciar funcionários']);
            return redirect()->to('/dashboard');
        }
        
        $restaurantId = $this->session->get('restaurant_id');
        $employees = $this->employeeModel->findByRestaurant($restaurantId);
        
        $data = [
            'title' => 'Gerenciar Funcionários',
            'employees' => $employees,
            'message' => $this->session->getFlashdata('message'),
            'errors' => $this->session->getFlashdata('errors')
        ];
        
        return view('dashboard/employees', $data);
    }
    
    /**
     * Adicionar funcionário (apenas admin)
     */
    public function addEmployee()
    {
        // Verificar autenticação e permissão
        if (!$this->session->get('logged_in') || $this->session->get('role') !== 'admin') {
            return redirect()->to('/dashboard');
        }
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'email' => 'required|valid_email|max_length[255]',
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[6]',
            'role' => 'required|in_list[admin,operator,manager]'
        ];
        
        if (!$this->validate($rules)) {
            $this->session->setFlashdata('errors', $validation->getErrors());
            return redirect()->back()->withInput();
        }
        
        $employeeData = [
            'restaurant_id' => $this->session->get('restaurant_id'),
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'username' => $this->request->getPost('username'),
            'password' => $this->request->getPost('password'),
            'role' => $this->request->getPost('role'),
            'status' => 1
        ];
        
        $result = $this->employeeModel->insert($employeeData);
        
        if ($result['success']) {
            $this->session->setFlashdata('message', 'Funcionário adicionado com sucesso!');
        } else {
            $this->session->setFlashdata('errors', $result['errors']);
        }
        
        return redirect()->to('/dashboard/employees');
    }
    
    /**
     * Informações do restaurante (apenas admin)
     */
    public function restaurant()
    {
        // Verificar autenticação e permissão
        if (!$this->session->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        if ($this->session->get('role') !== 'admin') {
            $this->session->setFlashdata('errors', ['Acesso negado. Apenas administradores podem gerenciar informações do restaurante']);
            return redirect()->to('/dashboard');
        }
        
        $restaurantId = $this->session->get('restaurant_id');
        $restaurant = $this->restaurantModel->find($restaurantId);
        
        $data = [
            'title' => 'Informações do Restaurante',
            'restaurant' => $restaurant,
            'message' => $this->session->getFlashdata('message'),
            'errors' => $this->session->getFlashdata('errors')
        ];
        
        return view('dashboard/restaurant', $data);
    }
    
    /**
     * Atualizar informações do restaurante (apenas admin)
     */
    public function updateRestaurant()
    {
        // Verificar autenticação e permissão
        if (!$this->session->get('logged_in') || $this->session->get('role') !== 'admin') {
            return redirect()->to('/dashboard');
        }
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'email' => 'required|valid_email|max_length[255]',
            'phone' => 'max_length[20]',
            'city' => 'max_length[100]',
            'state' => 'max_length[2]',
            'zip_code' => 'max_length[10]'
        ];
        
        if (!$this->validate($rules)) {
            $this->session->setFlashdata('errors', $validation->getErrors());
            return redirect()->back()->withInput();
        }
        
        $restaurantId = $this->session->get('restaurant_id');
        
        $updateData = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'address' => $this->request->getPost('address'),
            'city' => $this->request->getPost('city'),
            'state' => $this->request->getPost('state'),
            'zip_code' => $this->request->getPost('zip_code')
        ];
        
        $result = $this->restaurantModel->update($restaurantId, $updateData);
        
        if ($result['success']) {
            // Atualizar nome do restaurante na sessão
            $this->session->set('restaurant_name', $updateData['name']);
            $this->session->setFlashdata('message', 'Informações do restaurante atualizadas com sucesso!');
        } else {
            $this->session->setFlashdata('errors', $result['errors']);
        }
        
        return redirect()->to('/dashboard/restaurant');
    }
}