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

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    header('Content-Type: application/json');
    
    $cnpj = $_POST['cnpj'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validar campos obrigatórios
    if (empty($cnpj) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios!']);
        exit;
    }
    
    // Carregar dados
    $restaurants = loadJsonData('restaurants');
    $employees = loadJsonData('employees');
    
    // Verificar restaurante
    $restaurant = null;
    foreach ($restaurants as $r) {
        if ($r['cnpj'] === $cnpj) {
            $restaurant = $r;
            break;
        }
    }
    
    if ($restaurant) {
        // Verificar funcionário
        foreach ($employees as $emp) {
            if ($emp['restaurant_id'] == $restaurant['id'] && 
                $emp['username'] === $username && 
                $emp['password'] === $password && 
                $emp['is_active'] == 1) {
                
                // Login bem-sucedido
                $_SESSION['logged_in'] = true;
                $_SESSION['employee_id'] = $emp['id'];
                $_SESSION['restaurant_id'] = $restaurant['id'];
                $_SESSION['employee_name'] = $emp['name'];
                $_SESSION['restaurant_name'] = $restaurant['name'];
                $_SESSION['employee_role'] = $emp['role'];
                $_SESSION['is_admin'] = ($emp['role'] === 'admin');
                
                echo json_encode(['success' => true, 'message' => 'Login realizado com sucesso!', 'redirect' => 'dashboard.php']);
                exit;
            }
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'CNPJ, usuário ou senha inválidos!']);
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: simple_auth.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Prato Rápido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Inter', sans-serif;
            color: var(--dark-gray);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        .form-label {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: var(--dark-gray);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .text-muted {
            color: var(--medium-gray) !important;
        }
        .form-control:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #e55a00 0%, #e6b300 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-utensils fa-3x mb-3" style="color: var(--primary-orange);"></i>
                            <h2 class="card-title" style="color: var(--primary-orange); font-weight: 700;">Prato Rápido</h2>
                            <p class="text-muted">Faça login para acessar sua conta</p>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="loginForm">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="mb-3">
                                <label for="cnpj" class="form-label">
                                    <i class="fas fa-building me-2"></i>CNPJ do Restaurante
                                </label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" 
                                       placeholder="00.000.000/0000-00" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Usuário
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Digite seu usuário" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Senha
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Digite sua senha" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Entrar
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">Não possui uma conta? 
                                <a href="register.php" class="text-decoration-none">Cadastre-se aqui</a>
                            </p>
                            <p class="text-muted">Esqueceu sua senha? 
                                <a href="#" class="text-decoration-none">Clique aqui</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // CNPJ mask
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });
        
        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Entrando...';
            submitBtn.disabled = true;
            
            fetch('simple_auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success';
                    alert.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
                    document.querySelector('.card-body').insertBefore(alert, document.querySelector('form'));
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    // Show error message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger';
                    alert.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.message || 'Erro no login');
                    document.querySelector('.card-body').insertBefore(alert, document.querySelector('form'));
                    
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Erro de conexão. Tente novamente.';
                document.querySelector('.card-body').insertBefore(alert, document.querySelector('form'));
                
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>