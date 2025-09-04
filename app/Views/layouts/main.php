<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Prato Rápido SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/prato-rapido-theme.css') ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .saas-badge {
            background: linear-gradient(135deg, #FF6B00 0%, #FFC700 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .plan-info {
            background: #E8F4FD;
            border: 1px solid #B3D9F2;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .real-time-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
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
                    <i class="fas fa-utensils text-white" style="font-size: 2.5rem;"></i>
                    <h4 class="text-white mt-2">Prato Rápido</h4>
                    <span class="saas-badge">SaaS Platform</span>
                </div>
                
                <div class="plan-info text-center">
                    <h6 class="text-primary mb-1">Plano Professional</h6>
                    <small class="text-muted">R$ 199/mês</small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link <?= uri_string() == '' ? 'active' : '' ?>" href="<?= base_url() ?>">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="<?= base_url('kiosk') ?>">
                        <i class="fas fa-desktop me-2"></i>Totem Self-Service
                    </a>
                    <a class="nav-link" href="<?= base_url('orders') ?>">
                        <i class="fas fa-shopping-cart me-2"></i>Pedidos
                    </a>
                    <a class="nav-link" href="<?= base_url('menu') ?>">
                        <i class="fas fa-utensils me-2"></i>Cardápio
                    </a>
                    <a class="nav-link" href="<?= base_url('reports') ?>">
                        <i class="fas fa-chart-bar me-2"></i>Relatórios
                    </a>
                    <a class="nav-link" href="<?= base_url('plans') ?>">
                        <i class="fas fa-credit-card me-2"></i>Planos & Cobrança
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="<?= base_url('settings') ?>">
                        <i class="fas fa-cog me-2"></i>Configurações
                    </a>
                    <a class="nav-link" href="<?= base_url('privacy') ?>">
                        <i class="fas fa-shield-alt me-2"></i>LGPD & Privacidade
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