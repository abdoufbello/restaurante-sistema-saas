<?php
session_start();

// Configurações
define('DATA_DIR', '../writable/data/');

// Função para carregar dados JSON
function loadJsonData($table) {
    $file = DATA_DIR . $table . '.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return [];
}

// Função para salvar dados JSON
function saveJsonData($table, $data) {
    $file = DATA_DIR . $table . '.json';
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Processar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    header('Content-Type: application/json');
    
    $restaurant_name = $_POST['restaurant_name'] ?? '';
    $cnpj = $_POST['cnpj'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $admin_name = $_POST['admin_name'] ?? '';
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $plan = $_POST['plan'] ?? 'trial';
    
    // Validar campos obrigatórios
    if (empty($restaurant_name) || empty($cnpj) || empty($email) || empty($admin_name) || empty($admin_username) || empty($admin_password)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos!']);
        exit;
    }
    
    // Carregar dados existentes
    $restaurants = loadJsonData('restaurants');
    $employees = loadJsonData('employees');
    
    // Verificar se CNPJ já existe
    foreach ($restaurants as $r) {
        if ($r['cnpj'] === $cnpj) {
            echo json_encode(['success' => false, 'message' => 'CNPJ já cadastrado no sistema!']);
            exit;
        }
    }
    
    // Gerar novo ID para restaurante
    $restaurant_id = count($restaurants) > 0 ? max(array_column($restaurants, 'id')) + 1 : 1;
    
    // Criar novo restaurante
    $new_restaurant = [
        'id' => $restaurant_id,
        'name' => $restaurant_name,
        'cnpj' => $cnpj,
        'address' => $address,
        'phone' => $phone,
        'email' => $email,
        'subscription_plan' => $plan,
        'subscription_expires' => date('Y-m-d', strtotime('+30 days')),
        'created_at' => date('Y-m-d H:i:s'),
        'is_active' => 1
    ];
    
    // Gerar novo ID para funcionário
    $employee_id = count($employees) > 0 ? max(array_column($employees, 'id')) + 1 : 1;
    
    // Criar usuário admin
    $new_employee = [
        'id' => $employee_id,
        'restaurant_id' => $restaurant_id,
        'username' => $admin_username,
        'password' => $admin_password, // Em produção, usar password_hash()
        'name' => $admin_name,
        'email' => $email,
        'role' => 'admin',
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Salvar dados
    $restaurants[] = $new_restaurant;
    $employees[] = $new_employee;
    
    if (saveJsonData('restaurants', $restaurants) && saveJsonData('employees', $employees)) {
        echo json_encode(['success' => true, 'message' => 'Conta criada com sucesso! Você pode fazer login agora.', 'redirect' => 'simple_auth.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar dados. Tente novamente.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Prato Rápido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/prato-rapido-theme.css" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF6B00;
            --secondary-yellow: #FFC700;
            --dark-gray: #1E1E1E;
            --medium-gray: #6B6B6B;
            --light-gray: #F5F5F5;
            --white: #FFFFFF;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-gray) 0%, var(--white) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
            color: var(--dark-gray);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .register-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-yellow));
            color: var(--white);
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .register-body {
            padding: 3rem 2rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, 0.25);
        }
        
        .form-label {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-yellow));
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.3);
        }
        
        .plan-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .plan-card:hover {
            border-color: var(--primary-orange);
            transform: translateY(-5px);
        }
        
        .plan-card.selected {
            border-color: var(--primary-orange);
            background: rgba(255, 107, 0, 0.05);
        }
        
        .plan-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-orange);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .back-link {
            color: var(--white);
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .back-link:hover {
            opacity: 1;
            color: var(--white);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-card">
            <div class="register-header">
                <a href="index.html" class="back-link">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
                <h1 class="mt-3 mb-2">
                    <i class="fas fa-utensils me-2"></i>Prato Rápido
                </h1>
                <p class="mb-0 fs-5">Crie sua conta e comece a revolucionar seu restaurante hoje mesmo!</p>
            </div>
            
            <div class="register-body">
                <div id="alert-container"></div>
                
                <form id="registerForm">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="row">
                        <div class="col-12 mb-4">
                            <h4 class="text-center mb-4">Escolha seu Plano</h4>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="plan-card" data-plan="trial">
                                        <h5>Trial Gratuito</h5>
                                        <div class="plan-price">Grátis</div>
                                        <p class="small text-muted">30 dias</p>
                                        <ul class="list-unstyled small">
                                            <li>✓ 1 totem ativo</li>
                                            <li>✓ 50 pedidos por mês</li>
                                            <li>✓ 10 pratos no cardápio</li>
                                            <li>✓ Suporte por email</li>
                                            <li>✓ Relatórios básicos</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="plan-card" data-plan="starter">
                                        <h5>Starter</h5>
                                        <div class="plan-price">R$ 99</div>
                                        <p class="small text-muted">/mês</p>
                                        <ul class="list-unstyled small">
                                            <li>✓ 2 totems ativos</li>
                                            <li>✓ 200 pedidos por mês</li>
                                            <li>✓ 50 pratos no cardápio</li>
                                            <li>✓ Suporte por email e chat</li>
                                            <li>✓ Relatórios avançados</li>
                                            <li>✓ Integração com delivery</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="plan-card" data-plan="professional">
                                        <h5>Professional</h5>
                                        <div class="plan-price">R$ 199</div>
                                        <p class="small text-muted">/mês</p>
                                        <ul class="list-unstyled small">
                                            <li>✓ 5 totems ativos</li>
                                            <li>✓ 1000 pedidos por mês</li>
                                            <li>✓ 200 pratos no cardápio</li>
                                            <li>✓ Suporte prioritário 24/7</li>
                                            <li>✓ Relatórios completos</li>
                                            <li>✓ Integração com delivery</li>
                                            <li>✓ API personalizada</li>
                                            <li>✓ White label básico</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="plan-card" data-plan="enterprise">
                                        <h5>Enterprise</h5>
                                        <div class="plan-price">R$ 399</div>
                                        <p class="small text-muted">/mês</p>
                                        <ul class="list-unstyled small">
                                            <li>✓ Totems ilimitados</li>
                                            <li>✓ Pedidos ilimitados</li>
                                            <li>✓ Pratos ilimitados</li>
                                            <li>✓ Suporte dedicado 24/7</li>
                                            <li>✓ Relatórios personalizados</li>
                                            <li>✓ Todas as integrações</li>
                                            <li>✓ API completa</li>
                                            <li>✓ White label completo</li>
                                            <li>✓ Treinamento personalizado</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="plan" id="selectedPlan" value="trial">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome do Restaurante *</label>
                            <input type="text" class="form-control" name="restaurant_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CNPJ *</label>
                            <input type="text" class="form-control" name="cnpj" placeholder="00.000.000/0000-00" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="tel" class="form-control" name="phone" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Endereço</label>
                        <input type="text" class="form-control" name="address">
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Dados do Administrador</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="admin_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome de Usuário *</label>
                            <input type="text" class="form-control" name="admin_username" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Senha *</label>
                        <input type="password" class="form-control" name="admin_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-rocket me-2"></i>Criar Minha Conta
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Já tem uma conta? <a href="simple_auth.php" class="text-decoration-none" style="color: var(--primary-orange);">Fazer Login</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Plan selection
        document.querySelectorAll('.plan-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedPlan').value = this.dataset.plan;
            });
        });
        
        // Set default plan
        document.querySelector('.plan-card[data-plan="trial"]').classList.add('selected');
        
        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando conta...';
            submitBtn.disabled = true;
            
            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alertContainer = document.getElementById('alert-container');
                
                if (data.success) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>${data.message}
                        </div>
                    `;
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    alertContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>${data.message}
                        </div>
                    `;
                    
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('alert-container').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Erro de conexão. Tente novamente.
                    </div>
                `;
                
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // CNPJ mask
        document.querySelector('input[name="cnpj"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });
        
        // Phone mask
        document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    </script>
</body>
</html>