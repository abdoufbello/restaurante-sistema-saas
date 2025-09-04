<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Cadastro - Sistema de Restaurante' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            margin: 0 auto;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .register-body {
            padding: 40px;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-outline-secondary {
            border-radius: 10px;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .section-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .input-group .form-control:focus {
            border-left: none;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: 600;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <i class="fas fa-utensils fa-3x mb-3"></i>
                <h2>Cadastro de Restaurante</h2>
                <p class="lead mb-0">Crie sua conta e comece a gerenciar seu restaurante</p>
            </div>
            
            <div class="register-body">
                <?php if (isset($message) && $message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= esc($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($errors) && $errors): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php foreach ($errors as $error): ?>
                            <?= esc($error) ?><br>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth/processRegister') ?>" method="post" id="registerForm">
                    <?= csrf_field() ?>
                    
                    <!-- Informações do Restaurante -->
                    <div class="row">
                        <div class="col-12">
                            <h4 class="section-title"><i class="fas fa-store me-2"></i>Informações do Restaurante</h4>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cnpj" class="form-label fw-semibold">CNPJ *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" 
                                       placeholder="00.000.000/0000-00" 
                                       value="<?= old('cnpj') ?>" 
                                       maxlength="18" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="restaurant_name" class="form-label fw-semibold">Nome do Restaurante *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-utensils"></i></span>
                                <input type="text" class="form-control" id="restaurant_name" name="restaurant_name" 
                                       placeholder="Nome do seu restaurante" 
                                       value="<?= old('restaurant_name') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="restaurant_email" class="form-label fw-semibold">Email do Restaurante *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="restaurant_email" name="restaurant_email" 
                                       placeholder="contato@restaurante.com" 
                                       value="<?= old('restaurant_email') ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="restaurant_phone" class="form-label fw-semibold">Telefone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" class="form-control" id="restaurant_phone" name="restaurant_phone" 
                                       placeholder="(11) 99999-9999" 
                                       value="<?= old('restaurant_phone') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="address" class="form-label fw-semibold">Endereço</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" class="form-control" id="address" name="address" 
                                       placeholder="Rua, número, bairro" 
                                       value="<?= old('address') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="zip_code" class="form-label fw-semibold">CEP</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-mail-bulk"></i></span>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                       placeholder="00000-000" 
                                       value="<?= old('zip_code') ?>" 
                                       maxlength="9">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="city" class="form-label fw-semibold">Cidade</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-city"></i></span>
                                <input type="text" class="form-control" id="city" name="city" 
                                       placeholder="Nome da cidade" 
                                       value="<?= old('city') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="state" class="form-label fw-semibold">Estado</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-flag"></i></span>
                                <select class="form-control" id="state" name="state">
                                    <option value="">Selecione</option>
                                    <option value="SP" <?= old('state') == 'SP' ? 'selected' : '' ?>>SP</option>
                                    <option value="RJ" <?= old('state') == 'RJ' ? 'selected' : '' ?>>RJ</option>
                                    <option value="MG" <?= old('state') == 'MG' ? 'selected' : '' ?>>MG</option>
                                    <option value="RS" <?= old('state') == 'RS' ? 'selected' : '' ?>>RS</option>
                                    <option value="PR" <?= old('state') == 'PR' ? 'selected' : '' ?>>PR</option>
                                    <option value="SC" <?= old('state') == 'SC' ? 'selected' : '' ?>>SC</option>
                                    <!-- Adicione outros estados conforme necessário -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Informações do Administrador -->
                    <div class="row">
                        <div class="col-12">
                            <h4 class="section-title"><i class="fas fa-user-shield me-2"></i>Dados do Administrador</h4>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_name" class="form-label fw-semibold">Nome Completo *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" 
                                       placeholder="Nome completo do administrador" 
                                       value="<?= old('admin_name') ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="admin_email" class="form-label fw-semibold">Email *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       placeholder="email@exemplo.com" 
                                       value="<?= old('admin_email') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="admin_username" class="form-label fw-semibold">Nome de Usuário *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-at"></i></span>
                                <input type="text" class="form-control" id="admin_username" name="admin_username" 
                                       placeholder="usuario" 
                                       value="<?= old('admin_username') ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="admin_password" class="form-label fw-semibold">Senha *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" 
                                       placeholder="Mínimo 6 caracteres" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label fw-semibold">Confirmar Senha *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirme a senha" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword2">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <a href="<?= base_url('login') ?>" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Login
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Cadastrar Restaurante
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara para CNPJ
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/(\d{2})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        // Máscara para CEP
        document.getElementById('zip_code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        // Máscara para telefone
        document.getElementById('restaurant_phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        // Toggle password visibility
        document.getElementById('togglePassword1').addEventListener('click', function() {
            togglePasswordVisibility('admin_password', this);
        });
        
        document.getElementById('togglePassword2').addEventListener('click', function() {
            togglePasswordVisibility('confirm_password', this);
        });
        
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Validação de senha em tempo real
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('admin_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>