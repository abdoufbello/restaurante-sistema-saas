<?php
session_start();

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: simple_auth.php');
    exit;
}

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

// Carregar dados
$employees = loadJsonData('employees');
$categories = loadJsonData('categories');
$dishes = loadJsonData('dishes');
$orders = loadJsonData('orders');

// Filtrar dados do restaurante atual
$restaurant_employees = array_filter($employees, function($emp) {
    return $emp['restaurant_id'] == $_SESSION['restaurant_id'];
});

$restaurant_categories = array_filter($categories, function($cat) {
    return $cat['restaurant_id'] == $_SESSION['restaurant_id'];
});

$restaurant_dishes = array_filter($dishes, function($dish) {
    return $dish['restaurant_id'] == $_SESSION['restaurant_id'];
});

$restaurant_orders = array_filter($orders, function($order) {
    return $order['restaurant_id'] == $_SESSION['restaurant_id'];
});

// Estatísticas
$stats = [
    'employees' => count($restaurant_employees),
    'categories' => count($restaurant_categories),
    'dishes' => count($restaurant_dishes),
    'orders' => count($restaurant_orders)
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Prato Rápido | <?= htmlspecialchars($_SESSION['restaurant_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/prato-rapido-theme.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-utensils fa-2x text-white mb-2"></i>
                        <h4 class="text-white fw-bold">Prato Rápido</h4>
                        <h6 class="text-white"><?= htmlspecialchars($_SESSION['restaurant_name']) ?></h6>
                        <small class="text-white-50"><?= htmlspecialchars($_SESSION['employee_name']) ?></small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#" onclick="showSection('dashboard', this)">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="#" onclick="showSection('funcionarios', this)">
                            <i class="fas fa-users me-2"></i>Funcionários
                        </a>
                        <a class="nav-link" href="#" onclick="showSection('categorias', this)">
                            <i class="fas fa-tags me-2"></i>Categorias
                        </a>
                        <a class="nav-link" href="#" onclick="showSection('pratos', this)">
                            <i class="fas fa-utensils me-2"></i>Pratos
                        </a>
                        <a class="nav-link" href="#" onclick="showSection('pedidos', this)">
                            <i class="fas fa-shopping-cart me-2"></i>Pedidos
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell me-2"></i>Notificações
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notification-badge" style="display: none;">
                                <span id="notification-count">0</span>
                            </span>
                        </a>
                        <a class="nav-link" href="simple_auth.php?logout=1">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Dashboard</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar me-2"></i>
                            <?= date('d/m/Y H:i') ?>
                        </div>
                    </div>
                    
                    <!-- Dashboard Section -->
                    <div id="dashboard-section" class="content-section">
                        <!-- Stats Cards -->
                        <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h4 class="card-title"><?= $stats['employees'] ?></h4>
                                    <p class="card-text text-muted">Funcionários</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-tags fa-2x text-success mb-2"></i>
                                    <h4 class="card-title"><?= $stats['categories'] ?></h4>
                                    <p class="card-text text-muted">Categorias</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-utensils fa-2x text-warning mb-2"></i>
                                    <h4 class="card-title"><?= $stats['dishes'] ?></h4>
                                    <p class="card-text text-muted">Pratos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-shopping-cart fa-2x text-info mb-2"></i>
                                    <h4 class="card-title"><?= $stats['orders'] ?></h4>
                                    <p class="card-text text-muted">Pedidos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Ações Rápidas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <button class="btn btn-primary w-100" onclick="showSection('funcionarios')">
                                                <i class="fas fa-plus me-2"></i>Novo Funcionário
                                            </button>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <button class="btn btn-success w-100" onclick="showSection('categorias')">
                                                <i class="fas fa-plus me-2"></i>Nova Categoria
                                            </button>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <button class="btn btn-warning w-100" onclick="showSection('pratos')">
                                                <i class="fas fa-plus me-2"></i>Novo Prato
                                            </button>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="orders.php" class="btn btn-info w-100">
                                                <i class="fas fa-clipboard-list me-2"></i>Gestão de Pedidos
                                            </a>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-3 mb-2">
                                            <a href="kiosk.php" class="btn btn-success w-100" target="_blank">
                                                <i class="fas fa-cog me-2"></i>Gerenciar Kiosk
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="kiosk_tokens.php" class="btn btn-info w-100">
                                                <i class="fas fa-link me-2"></i>Tokens Kiosk
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="plans.php" class="btn btn-warning w-100">
                                                <i class="fas fa-crown me-2"></i>Planos
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                             <a href="reports.php" class="btn btn-success w-100">
                                                 <i class="fas fa-chart-bar me-2"></i>Relatórios
                                             </a>
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Funcionários Recentes</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($restaurant_employees)): ?>
                                        <p class="text-muted">Nenhum funcionário cadastrado.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Nome</th>
                                                        <th>Usuário</th>
                                                        <th>Tipo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (array_slice($restaurant_employees, 0, 5) as $emp): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($emp['name']) ?></td>
                                                            <td><?= htmlspecialchars($emp['username']) ?></td>
                                                            <td>
                                                                <?php if ($emp['role'] === 'admin'): ?>
                                                                    <span class="badge bg-primary">Admin</span>
                                                                <?php elseif ($emp['role'] === 'manager'): ?>
                                                                    <span class="badge bg-success">Gerente</span>
                                                                <?php elseif ($emp['role'] === 'kitchen'): ?>
                                                                    <span class="badge bg-warning">Cozinha</span>
                                                                <?php elseif ($emp['role'] === 'cashier'): ?>
                                                                    <span class="badge bg-info">Caixa</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Funcionário</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Informações do Sistema</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li><strong>Restaurante:</strong> <?= htmlspecialchars($_SESSION['restaurant_name']) ?></li>
                                        <li><strong>Usuário:</strong> <?= htmlspecialchars($_SESSION['employee_name']) ?></li>
                                        <li><strong>Tipo:</strong> 
                                            <?php if ($_SESSION['is_admin']): ?>
                                                <span class="badge bg-primary">Administrador</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Funcionário</span>
                                            <?php endif; ?>
                                        </li>
                                        <li><strong>Último acesso:</strong> <?= date('d/m/Y H:i') ?></li>
                                        <li><strong>Status:</strong> <span class="badge bg-success">Online</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    
                    <!-- Funcionários Section -->
                    <div id="funcionarios-section" class="content-section" style="display: none;">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Gestão de Funcionários</h5>
                                <button class="btn btn-primary btn-sm" onclick="showAddEmployeeForm()">
                                    <i class="fas fa-plus me-2"></i>Novo Funcionário
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Usuário</th>
                                                <th>Email</th>
                                                <th>Cargo</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($restaurant_employees as $emp): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($emp['name']) ?></td>
                                                    <td><?= htmlspecialchars($emp['username']) ?></td>
                                                    <td><?= htmlspecialchars($emp['email']) ?></td>
                                                    <td>
                                                        <?php if ($emp['role'] === 'admin'): ?>
                                                            <span class="badge bg-primary">Admin</span>
                                                        <?php elseif ($emp['role'] === 'manager'): ?>
                                                            <span class="badge bg-success">Gerente</span>
                                                        <?php elseif ($emp['role'] === 'kitchen'): ?>
                                                            <span class="badge bg-warning">Cozinha</span>
                                                        <?php elseif ($emp['role'] === 'cashier'): ?>
                                                            <span class="badge bg-info">Caixa</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Funcionário</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($emp['is_active']): ?>
                                                            <span class="badge bg-success">Ativo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inativo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editEmployee(<?= $emp['id'] ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(<?= $emp['id'] ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Categorias Section -->
                    <div id="categorias-section" class="content-section" style="display: none;">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Gestão de Categorias</h5>
                                <button class="btn btn-success btn-sm" onclick="showAddCategoryForm()">
                                    <i class="fas fa-plus me-2"></i>Nova Categoria
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($restaurant_categories as $cat): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?= htmlspecialchars($cat['name']) ?></h6>
                                                    <p class="card-text text-muted"><?= htmlspecialchars($cat['description']) ?></p>
                                                    <div class="d-flex justify-content-between">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?= $cat['id'] ?>)">
                                                            <i class="fas fa-edit"></i> Editar
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $cat['id'] ?>)">
                                                            <i class="fas fa-trash"></i> Excluir
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pratos Section -->
                    <div id="pratos-section" class="content-section" style="display: none;">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Gestão de Pratos</h5>
                                <button class="btn btn-warning btn-sm" onclick="showAddDishForm()">
                                    <i class="fas fa-plus me-2"></i>Novo Prato
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($restaurant_dishes as $dish): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="card-title"><?= htmlspecialchars($dish['name']) ?></h6>
                                                            <p class="card-text text-muted"><?= htmlspecialchars($dish['description']) ?></p>
                                                            <p class="card-text"><strong>R$ <?= number_format($dish['price'], 2, ',', '.') ?></strong></p>
                                                        </div>
                                                        <div class="text-end">
                                                            <?php if ($dish['is_available']): ?>
                                                                <span class="badge bg-success mb-2">Disponível</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger mb-2">Indisponível</span>
                                                            <?php endif; ?>
                                                            <br>
                                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editDish(<?= $dish['id'] ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDish(<?= $dish['id'] ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pedidos Section -->
                    <div id="pedidos-section" class="content-section" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Gestão de Pedidos</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Cliente</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Data</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($restaurant_orders as $order): ?>
                                                <tr>
                                                    <td>#<?= $order['id'] ?></td>
                                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                                    <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                                                    <td>
                                                        <?php 
                                                        $statusColors = [
                                                            'pending' => 'warning',
                                                            'preparing' => 'info', 
                                                            'ready' => 'success',
                                                            'delivered' => 'primary',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $statusLabels = [
                                                            'pending' => 'Pendente',
                                                            'preparing' => 'Preparando',
                                                            'ready' => 'Pronto',
                                                            'delivered' => 'Entregue',
                                                            'cancelled' => 'Cancelado'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?= $statusColors[$order['status']] ?>">
                                                            <?= $statusLabels[$order['status']] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewOrder(<?= $order['id'] ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" onclick="updateOrderStatus(<?= $order['id'] ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Definir ID do restaurante globalmente
        window.restaurantId = <?= $_SESSION['restaurant_id'] ?>;
    </script>
    <script src="dashboard_functions.js"></script>
</body>
</html>