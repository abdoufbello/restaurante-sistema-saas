<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Prato Rápido Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/prato-rapido-theme.css') ?>" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1E1E1E;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #FF6B00 0%, #FFC700 100%);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .main-content {
            background-color: #F5F5F5;
            min-height: 100vh;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FF6B00 0%, #FFC700 100%);
            border: none;
            border-radius: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.3);
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .admin-info {
            background: #FFF3CD;
            border: 1px solid #FFEAA7;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <div class="text-center mb-4">
                    <i class="fas fa-shield-alt text-white" style="font-size: 2.5rem;"></i>
                    <h4 class="text-white mt-2">Admin Panel</h4>
                    <span class="admin-badge">Administrador</span>
                </div>
                
                <div class="admin-info text-center">
                    <h6 class="text-warning mb-1">Área Restrita</h6>
                    <small class="text-muted">Acesso Administrativo</small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link <?= strpos(uri_string(), 'admin') === 0 ? 'active' : '' ?>" href="<?= base_url('admin') ?>">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
                    </a>
                    <a class="nav-link" href="<?= base_url('admin/users') ?>">
                        <i class="fas fa-users me-2"></i>Usuários
                    </a>
                    <a class="nav-link" href="<?= base_url('admin/tenants') ?>">
                        <i class="fas fa-building me-2"></i>Empresas
                    </a>
                    <a class="nav-link" href="<?= base_url('admin/subscriptions') ?>">
                        <i class="fas fa-credit-card me-2"></i>Assinaturas
                    </a>
                    <a class="nav-link" href="<?= base_url('admin/reports') ?>">
                        <i class="fas fa-chart-line me-2"></i>Relatórios Globais
                    </a>
                    <a class="nav-link" href="<?= base_url('admin/system') ?>">
                        <i class="fas fa-server me-2"></i>Sistema
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="<?= base_url() ?>">
                        <i class="fas fa-arrow-left me-2"></i>Voltar ao App
                    </a>
                    <a class="nav-link" href="<?= base_url('logout') ?>">
                        <i class="fas fa-sign-out-alt me-2"></i>Sair
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-4">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>