<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
        }
        .user-info {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .nav-text {
            transition: opacity 0.3s ease;
        }
        .sidebar.collapsed .nav-text {
            opacity: 0;
            display: none;
        }
        .sidebar.collapsed .logo h4,
        .sidebar.collapsed .user-info {
            display: none;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h4 class="mb-0"><i class="fas fa-utensils me-2"></i>RestaurantePOS</h4>
        </div>
        
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="avatar bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="fw-bold"><?= esc($user['name']) ?></div>
                    <small class="text-light"><?= ucfirst($user['role']) ?></small>
                </div>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="<?= base_url('dashboard') ?>">
                    <i class="fas fa-tachometer-alt me-3"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('dashboard/profile') ?>">
                    <i class="fas fa-user me-3"></i>
                    <span class="nav-text">Meu Perfil</span>
                </a>
            </li>
            <?php if ($user['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('dashboard/restaurant') ?>">
                    <i class="fas fa-store me-3"></i>
                    <span class="nav-text">Restaurante</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('dashboard/employees') ?>">
                    <i class="fas fa-users me-3"></i>
                    <span class="nav-text">Funcionários</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-utensils me-3"></i>
                    <span class="nav-text">Cardápio</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-shopping-cart me-3"></i>
                    <span class="nav-text">Pedidos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-chart-bar me-3"></i>
                    <span class="nav-text">Relatórios</span>
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="<?= base_url('auth/logout') ?>">
                    <i class="fas fa-sign-out-alt me-3"></i>
                    <span class="nav-text">Sair</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <button class="btn d-md-none" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <?= esc($user['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= base_url('dashboard/profile') ?>"><i class="fas fa-user me-2"></i>Meu Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('auth/logout') ?>"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid p-4">
            <!-- Header com boas-vindas -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Bem-vindo ao Prato Rápido SaaS! 🍽️</h1>
                    <p class="text-muted mb-0">Gerencie seu restaurante com inteligência e eficiência</p>
                    <div class="d-flex align-items-center mt-2">
                        <span class="real-time-indicator me-2"></span>
                        <small class="text-success">Sistema online - Dados em tempo real</small>
                    </div>
                </div>
                <div class="text-end">
                    <h6 class="text-primary mb-1">Rokku Burger</h6>
                    <small class="text-muted">Plano Professional Ativo</small>
                </div>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Pedidos Hoje</h6>
                                    <h2 class="mb-0">127</h2>
                                    <small class="opacity-75">+12% vs ontem</small>
                                </div>
                                <div class="text-end">
                                    <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card text-white" style="background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Receita Hoje</h6>
                                    <h2 class="mb-0">R$ 3.247</h2>
                                    <small class="opacity-75">+8% vs ontem</small>
                                </div>
                                <div class="text-end">
                                    <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card text-white" style="background: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Ticket Médio</h6>
                                    <h2 class="mb-0">R$ 25,60</h2>
                                    <small class="opacity-75">-3% vs ontem</small>
                                </div>
                                <div class="text-end">
                                    <i class="fas fa-receipt fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card text-white" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Clientes Ativos</h6>
                                    <h2 class="mb-0">1.234</h2>
                                    <small class="opacity-75">+15% este mês</small>
                                </div>
                                <div class="text-end">
                                    <i class="fas fa-users fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos e Informações -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Receita dos Últimos 7 Dias</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Produtos Mais Vendidos</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">X-Burger Especial</h6>
                                    <small class="text-muted">47 vendas hoje</small>
                                </div>
                                <span class="badge bg-success">R$ 18,90</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">Batata Frita Grande</h6>
                                    <small class="text-muted">38 vendas hoje</small>
                                </div>
                                <span class="badge bg-success">R$ 12,50</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">Refrigerante 350ml</h6>
                                    <small class="text-muted">52 vendas hoje</small>
                                </div>
                                <span class="badge bg-success">R$ 5,90</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Milkshake Chocolate</h6>
                                    <small class="text-muted">23 vendas hoje</small>
                                </div>
                                <span class="badge bg-success">R$ 14,90</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pedidos Recentes -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Pedidos Recentes</h5>
                            <a href="<?= base_url('orders') ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>Ver Todos
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Cliente</th>
                                            <th>Itens</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Horário</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>#1247</strong></td>
                                            <td>João Silva</td>
                                            <td>X-Burger, Batata, Coca-Cola</td>
                                            <td><strong>R$ 37,30</strong></td>
                                            <td><span class="badge bg-warning">Preparando</span></td>
                                            <td>14:32</td>
                                        </tr>
                                        <tr>
                                            <td><strong>#1246</strong></td>
                                            <td>Maria Santos</td>
                                            <td>Salada Caesar, Suco Natural</td>
                                            <td><strong>R$ 24,90</strong></td>
                                            <td><span class="badge bg-success">Pronto</span></td>
                                            <td>14:28</td>
                                        </tr>
                                        <tr>
                                            <td><strong>#1245</strong></td>
                                            <td>Pedro Costa</td>
                                            <td>Pizza Margherita, Refrigerante</td>
                                            <td><strong>R$ 42,50</strong></td>
                                            <td><span class="badge bg-info">Entregue</span></td>
                                            <td>14:15</td>
                                        </tr>
                                        <tr>
                                            <td><strong>#1244</strong></td>
                                            <td>Ana Oliveira</td>
                                            <td>Hambúrguer Vegano, Água</td>
                                            <td><strong>R$ 28,90</strong></td>
                                            <td><span class="badge bg-info">Entregue</span></td>
                                            <td>14:02</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Gráfico de Receita
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        datasets: [{
            label: 'Receita (R$)',
            data: [2800, 3200, 2900, 3500, 3100, 4200, 3247],
            borderColor: '#FF6B00',
            backgroundColor: 'rgba(255, 107, 0, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'R$ ' + value;
                    }
                }
            }
        }
    }
});
</script>
<?= $this->endSection() ?>