<?php

namespace App\Controllers;

use App\Models\RestaurantModel;
use App\Models\EmployeeModel;
use CodeIgniter\Controller;

class Auth extends Controller
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
     * Página de login
     */
    public function login()
    {
        // Se já estiver logado, redirecionar para dashboard
        if ($this->session->get('logged_in')) {
            return redirect()->to('/dashboard');
        }
        
        $data = [
            'title' => 'Login - Sistema de Restaurante',
            'errors' => $this->session->getFlashdata('errors'),
            'message' => $this->session->getFlashdata('message')
        ];
        
        return view('auth/login', $data);
    }
    
    /**
     * Processar login
     */
    public function authenticate()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'cnpj' => 'required|min_length[14]',
            'username' => 'required|min_length[3]',
            'password' => 'required|min_length[6]'
        ];
        
        $messages = [
            'cnpj' => [
                'required' => 'CNPJ é obrigatório',
                'min_length' => 'CNPJ deve ter pelo menos 14 caracteres'
            ],
            'username' => [
                'required' => 'Nome de usuário é obrigatório',
                'min_length' => 'Nome de usuário deve ter pelo menos 3 caracteres'
            ],
            'password' => [
                'required' => 'Senha é obrigatória',
                'min_length' => 'Senha deve ter pelo menos 6 caracteres'
            ]
        ];
        
        if (!$this->validate($rules, $messages)) {
            $this->session->setFlashdata('errors', $validation->getErrors());
            return redirect()->back()->withInput();
        }
        
        $cnpj = $this->request->getPost('cnpj');
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        
        // Buscar restaurante pelo CNPJ
        $restaurant = $this->restaurantModel->findByCnpj($cnpj);
        
        if (!$restaurant) {
            $this->session->setFlashdata('errors', ['CNPJ não encontrado']);
            return redirect()->back()->withInput();
        }
        
        // Verificar se restaurante está ativo
        if ($restaurant['status'] != 1) {
            $this->session->setFlashdata('errors', ['Restaurante inativo']);
            return redirect()->back()->withInput();
        }
        
        // Buscar funcionário pelo username no restaurante
        $employees = $this->employeeModel->findByRestaurant($restaurant['id']);
        $employee = null;
        
        foreach ($employees as $emp) {
            if ($emp['username'] === $username) {
                $employee = $emp;
                break;
            }
        }
        
        if (!$employee) {
            $this->session->setFlashdata('errors', ['Usuário não encontrado neste restaurante']);
            return redirect()->back()->withInput();
        }
        
        // Autenticar funcionário
        $authenticatedEmployee = $this->employeeModel->authenticate($username, $password);
        
        if (!$authenticatedEmployee) {
            $this->session->setFlashdata('errors', ['Credenciais inválidas']);
            return redirect()->back()->withInput();
        }
        
        // Criar sessão
        $sessionData = [
            'logged_in' => true,
            'user_id' => $authenticatedEmployee['id'],
            'username' => $authenticatedEmployee['username'],
            'name' => $authenticatedEmployee['name'],
            'email' => $authenticatedEmployee['email'],
            'role' => $authenticatedEmployee['role'],
            'restaurant_id' => $restaurant['id'],
            'restaurant_name' => $restaurant['name'],
            'restaurant_cnpj' => $restaurant['cnpj']
        ];
        
        $this->session->set($sessionData);
        
        // Log de acesso
        log_message('info', 'Login realizado: ' . $username . ' - Restaurante: ' . $restaurant['name']);
        
        $this->session->setFlashdata('message', 'Login realizado com sucesso!');
        return redirect()->to('/dashboard');
    }
    
    /**
     * Logout
     */
    public function logout()
    {
        // Log de logout
        if ($this->session->get('logged_in')) {
            log_message('info', 'Logout realizado: ' . $this->session->get('username'));
        }
        
        $this->session->destroy();
        $this->session->setFlashdata('message', 'Logout realizado com sucesso!');
        return redirect()->to('/login');
    }
    
    /**
     * Página de registro de restaurante
     */
    public function register()
    {
        $data = [
            'title' => 'Cadastro de Restaurante',
            'errors' => $this->session->getFlashdata('errors'),
            'message' => $this->session->getFlashdata('message')
        ];
        
        return view('auth/register', $data);
    }
    
    /**
     * Processar registro de restaurante
     */
    public function processRegister()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'cnpj' => 'required|min_length[14]',
            'restaurant_name' => 'required|min_length[3]|max_length[255]',
            'restaurant_email' => 'required|valid_email|max_length[255]',
            'restaurant_phone' => 'max_length[20]',
            'admin_name' => 'required|min_length[3]|max_length[255]',
            'admin_email' => 'required|valid_email|max_length[255]',
            'admin_username' => 'required|min_length[3]|max_length[50]',
            'admin_password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[admin_password]'
        ];
        
        $messages = [
            'cnpj' => [
                'required' => 'CNPJ é obrigatório',
                'min_length' => 'CNPJ deve ter pelo menos 14 caracteres'
            ],
            'restaurant_name' => [
                'required' => 'Nome do restaurante é obrigatório',
                'min_length' => 'Nome deve ter pelo menos 3 caracteres'
            ],
            'restaurant_email' => [
                'required' => 'Email do restaurante é obrigatório',
                'valid_email' => 'Email inválido'
            ],
            'admin_name' => [
                'required' => 'Nome do administrador é obrigatório',
                'min_length' => 'Nome deve ter pelo menos 3 caracteres'
            ],
            'admin_email' => [
                'required' => 'Email do administrador é obrigatório',
                'valid_email' => 'Email inválido'
            ],
            'admin_username' => [
                'required' => 'Nome de usuário é obrigatório',
                'min_length' => 'Nome de usuário deve ter pelo menos 3 caracteres'
            ],
            'admin_password' => [
                'required' => 'Senha é obrigatória',
                'min_length' => 'Senha deve ter pelo menos 6 caracteres'
            ],
            'confirm_password' => [
                'required' => 'Confirmação de senha é obrigatória',
                'matches' => 'Senhas não coincidem'
            ]
        ];
        
        if (!$this->validate($rules, $messages)) {
            $this->session->setFlashdata('errors', $validation->getErrors());
            return redirect()->back()->withInput();
        }
        
        // Dados do restaurante
        $restaurantData = [
            'cnpj' => $this->request->getPost('cnpj'),
            'name' => $this->request->getPost('restaurant_name'),
            'email' => $this->request->getPost('restaurant_email'),
            'phone' => $this->request->getPost('restaurant_phone'),
            'address' => $this->request->getPost('address'),
            'city' => $this->request->getPost('city'),
            'state' => $this->request->getPost('state'),
            'zip_code' => $this->request->getPost('zip_code'),
            'status' => 1
        ];
        
        // Inserir restaurante
        $restaurantResult = $this->restaurantModel->insert($restaurantData);
        
        if (!$restaurantResult['success']) {
            $this->session->setFlashdata('errors', $restaurantResult['errors']);
            return redirect()->back()->withInput();
        }
        
        // Dados do administrador
        $adminData = [
            'restaurant_id' => $restaurantResult['id'],
            'name' => $this->request->getPost('admin_name'),
            'email' => $this->request->getPost('admin_email'),
            'username' => $this->request->getPost('admin_username'),
            'password' => $this->request->getPost('admin_password'),
            'role' => 'admin',
            'status' => 1
        ];
        
        // Inserir administrador
        $adminResult = $this->employeeModel->insert($adminData);
        
        if (!$adminResult['success']) {
            // Se falhar ao criar admin, remover restaurante
            $this->restaurantModel->delete($restaurantResult['id']);
            $this->session->setFlashdata('errors', $adminResult['errors']);
            return redirect()->back()->withInput();
        }
        
        // Log de registro
        log_message('info', 'Novo restaurante registrado: ' . $restaurantData['name'] . ' - CNPJ: ' . $restaurantData['cnpj']);
        
        $this->session->setFlashdata('message', 'Restaurante cadastrado com sucesso! Faça login para continuar.');
        return redirect()->to('/login');
    }
    
    /**
     * Verificar se usuário está logado (middleware)
     */
    public function checkAuth()
    {
        if (!$this->session->get('logged_in')) {
            $this->session->setFlashdata('errors', ['Você precisa fazer login para acessar esta página']);
            return redirect()->to('/login');
        }
        return true;
    }
    
    /**
     * Verificar se usuário é admin (middleware)
     */
    public function checkAdmin()
    {
        if (!$this->session->get('logged_in') || $this->session->get('role') !== 'admin') {
            $this->session->setFlashdata('errors', ['Acesso negado. Apenas administradores podem acessar esta página']);
            return redirect()->to('/dashboard');
        }
        return true;
    }
}