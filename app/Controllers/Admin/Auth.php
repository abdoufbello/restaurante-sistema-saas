<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Restaurant\RestaurantModel;
use App\Models\Restaurant\EmployeeModel;

/**
 * Authentication Controller for Restaurant Admin Panel
 * Handles CNPJ-based login for restaurant management
 */
class Auth extends BaseController
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
     * Display login form
     */
    public function login()
    {
        // If already logged in, redirect to dashboard
        if ($this->session->get('admin_logged_in')) {
            return redirect()->to('/admin/dashboard');
        }

        $data = [
            'title' => 'Login - Restaurant Kiosk Admin',
            'error' => $this->session->getFlashdata('error'),
            'success' => $this->session->getFlashdata('success')
        ];

        return view('admin/auth/login', $data);
    }

    /**
     * Process login authentication
     */
    public function authenticate()
    {
        $validation = \Config\Services::validation();
        
        // Validation rules
        $validation->setRules([
            'cnpj' => [
                'label' => 'CNPJ',
                'rules' => 'required|min_length[14]|max_length[18]',
                'errors' => [
                    'required' => 'CNPJ é obrigatório',
                    'min_length' => 'CNPJ deve ter pelo menos 14 caracteres',
                    'max_length' => 'CNPJ deve ter no máximo 18 caracteres'
                ]
            ],
            'username' => [
                'label' => 'Usuário',
                'rules' => 'required|min_length[3]|max_length[50]',
                'errors' => [
                    'required' => 'Usuário é obrigatório',
                    'min_length' => 'Usuário deve ter pelo menos 3 caracteres',
                    'max_length' => 'Usuário deve ter no máximo 50 caracteres'
                ]
            ],
            'password' => [
                'label' => 'Senha',
                'rules' => 'required|min_length[6]',
                'errors' => [
                    'required' => 'Senha é obrigatória',
                    'min_length' => 'Senha deve ter pelo menos 6 caracteres'
                ]
            ]
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            $this->session->setFlashdata('error', 'Dados inválidos: ' . implode(', ', $validation->getErrors()));
            return redirect()->back()->withInput();
        }

        $cnpj = $this->cleanCNPJ($this->request->getPost('cnpj'));
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Validate CNPJ format
        if (!$this->isValidCNPJ($cnpj)) {
            $this->session->setFlashdata('error', 'CNPJ inválido');
            return redirect()->back()->withInput();
        }

        // Find restaurant by CNPJ
        $restaurant = $this->restaurantModel->where('cnpj', $cnpj)->first();
        
        if (!$restaurant) {
            $this->session->setFlashdata('error', 'Restaurante não encontrado');
            return redirect()->back()->withInput();
        }

        // Check if restaurant is active
        if ($restaurant['status'] !== 'active') {
            $this->session->setFlashdata('error', 'Restaurante inativo. Entre em contato com o suporte.');
            return redirect()->back()->withInput();
        }

        // Find employee
        $employee = $this->employeeModel
            ->where('restaurant_id', $restaurant['id'])
            ->where('username', $username)
            ->where('status', 'active')
            ->first();

        if (!$employee) {
            $this->session->setFlashdata('error', 'Usuário não encontrado ou inativo');
            return redirect()->back()->withInput();
        }

        // Verify password
        if (!password_verify($password, $employee['password'])) {
            $this->session->setFlashdata('error', 'Senha incorreta');
            return redirect()->back()->withInput();
        }

        // Set session data
        $sessionData = [
            'admin_logged_in' => true,
            'restaurant_id' => $restaurant['id'],
            'restaurant_name' => $restaurant['name'],
            'restaurant_cnpj' => $restaurant['cnpj'],
            'employee_id' => $employee['id'],
            'employee_name' => $employee['name'],
            'employee_role' => $employee['role'],
            'login_time' => time()
        ];

        $this->session->set($sessionData);

        // Update last login
        $this->employeeModel->update($employee['id'], [
            'last_login' => date('Y-m-d H:i:s'),
            'login_count' => $employee['login_count'] + 1
        ]);

        $this->session->setFlashdata('success', 'Login realizado com sucesso!');
        return redirect()->to('/admin/dashboard');
    }

    /**
     * Logout user
     */
    public function logout()
    {
        $this->session->destroy();
        $this->session->setFlashdata('success', 'Logout realizado com sucesso!');
        return redirect()->to('/admin/login');
    }

    /**
     * Clean CNPJ removing special characters
     */
    private function cleanCNPJ($cnpj)
    {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }

    /**
     * Validate CNPJ format and check digit
     */
    private function isValidCNPJ($cnpj)
    {
        // Remove any non-numeric characters
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Check if it has 14 digits
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Check for known invalid CNPJs
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        
        // Calculate first check digit
        $sum = 0;
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights[$i];
        }
        
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if ($cnpj[12] != $digit1) {
            return false;
        }
        
        // Calculate second check digit
        $sum = 0;
        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights[$i];
        }
        
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
        
        return $cnpj[13] == $digit2;
    }
}