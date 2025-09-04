<?php
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['restaurant_id'])) {
    header('Location: simple_auth.php');
    exit;
}

// Função para carregar dados JSON
function loadJsonData($filename) {
    $filepath = '../writable/data/' . $filename;
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        return json_decode($content, true) ?: [];
    }
    return [];
}

$restaurants = loadJsonData('restaurants.json');
$dishes = loadJsonData('dishes.json');
$orders = loadJsonData('orders.json');
$categories = loadJsonData('categories.json');

$currentRestaurant = null;
foreach ($restaurants as $restaurant) {
    if ($restaurant['id'] == $_SESSION['restaurant_id']) {
        $currentRestaurant = $restaurant;
        break;
    }
}

if (!$currentRestaurant) {
    header('Location: simple_auth.php');
    exit;
}

// Filtrar dados do restaurante atual
$restaurantOrders = array_filter($orders, function($order) {
    return $order['restaurant_id'] == $_SESSION['restaurant_id'];
});

$restaurantDishes = array_filter($dishes, function($dish) {
    return $dish['restaurant_id'] == $_SESSION['restaurant_id'];
});

$restaurantCategories = array_filter($categories, function($category) {
    return $category['restaurant_id'] == $_SESSION['restaurant_id'];
});

// Período de análise (últimos 30 dias por padrão)
$period = $_GET['period'] ?? '30';
$startDate = date('Y-m-d', strtotime("-{$period} days"));
$endDate = date('Y-m-d');

// Filtrar pedidos por período
$periodOrders = array_filter($restaurantOrders, function($order) use ($startDate, $endDate) {
    $orderDate = date('Y-m-d', strtotime($order['created_at']));
    return $orderDate >= $startDate && $orderDate <= $endDate;
});

// Calcular estatísticas
$totalOrders = count($periodOrders);
$totalRevenue = array_sum(array_column($periodOrders, 'total'));
$averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

// Pedidos por status
$ordersByStatus = [];
foreach ($periodOrders as $order) {
    $status = $order['status'];
    $ordersByStatus[$status] = ($ordersByStatus[$status] ?? 0) + 1;
}

// Pratos mais vendidos
$dishSales = [];
foreach ($periodOrders as $order) {
    foreach ($order['items'] as $item) {
        $dishId = $item['dish_id'];
        $dishSales[$dishId] = ($dishSales[$dishId] ?? 0) + $item['quantity'];
    }
}
arsort($dishSales);

// Vendas por categoria
$categorySales = [];
foreach ($periodOrders as $order) {
    foreach ($order['items'] as $item) {
        $dish = array_filter($restaurantDishes, function($d) use ($item) {
            return $d['id'] == $item['dish_id'];
        });
        $dish = reset($dish);
        if ($dish) {
            $categoryId = $dish['category_id'];
            $categorySales[$categoryId] = ($categorySales[$categoryId] ?? 0) + ($item['price'] * $item['quantity']);
        }
    }
}
arsort($categorySales);

// Vendas por dia (últimos 7 dias)
$dailySales = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $dailySales[$date] = 0;
}

foreach ($periodOrders as $order) {
    $orderDate = date('Y-m-d', strtotime($order['created_at']));
    if (isset($dailySales[$orderDate])) {
        $dailySales[$orderDate] += $order['total'];
    }
}

// Horários de pico
$hourlyOrders = [];
for ($h = 0; $h < 24; $h++) {
    $hourlyOrders[sprintf('%02d:00', $h)] = 0;
}

foreach ($periodOrders as $order) {
    $hour = date('H:00', strtotime($order['created_at']));
    $hourlyOrders[$hour] = ($hourlyOrders[$hour] ?? 0) + 1;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Prato Rápido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-orange: #ff6b00;
            --secondary-yellow: #ffc700;
            --dark-gray: #2c3e50;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
        .stats-card {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
            color: white;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        .period-selector {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .bg-primary {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%) !important;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-utensils me-2"></i>Prato Rápido
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-chart-bar me-2"></i>
                    Relatórios e Analytics
                </h2>
            </div>
        </div>

        <!-- Seletor de Período -->
        <div class="period-selector">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar me-2"></i>
                        Período de Análise
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="btn-group w-100" role="group">
                        <a href="?period=7" class="btn <?= $period == '7' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            7 dias
                        </a>
                        <a href="?period=30" class="btn <?= $period == '30' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            30 dias
                        </a>
                        <a href="?period=90" class="btn <?= $period == '90' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            90 dias
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas Principais -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-3"></i>
                        <h3 class="mb-1"><?= number_format($totalOrders) ?></h3>
                        <p class="mb-0">Total de Pedidos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-3"></i>
                        <h3 class="mb-1">R$ <?= number_format($totalRevenue, 2, ',', '.') ?></h3>
                        <p class="mb-0">Faturamento Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-2x mb-3"></i>
                        <h3 class="mb-1">R$ <?= number_format($averageOrderValue, 2, ',', '.') ?></h3>
                        <p class="mb-0">Ticket Médio</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-utensils fa-2x mb-3"></i>
                        <h3 class="mb-1"><?= count($restaurantDishes) ?></h3>
                        <p class="mb-0">Pratos no Cardápio</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <!-- Vendas por Dia -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Vendas por Dia (Últimos 7 dias)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dailySalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pedidos por Status -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Pedidos por Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Horários de Pico -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Horários de Pico
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabelas de Dados -->
        <div class="row">
            <!-- Pratos Mais Vendidos -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Top 5 Pratos Mais Vendidos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Posição</th>
                                        <th>Prato</th>
                                        <th>Vendas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $position = 1;
                                    foreach (array_slice($dishSales, 0, 5, true) as $dishId => $sales): 
                                        $dish = array_filter($restaurantDishes, function($d) use ($dishId) {
                                            return $d['id'] == $dishId;
                                        });
                                        $dish = reset($dish);
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary"><?= $position ?></span>
                                            </td>
                                            <td><?= $dish ? htmlspecialchars($dish['name']) : 'Prato não encontrado' ?></td>
                                            <td>
                                                <strong><?= $sales ?></strong> unidades
                                            </td>
                                        </tr>
                                    <?php 
                                        $position++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendas por Categoria -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tags me-2"></i>
                            Vendas por Categoria
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th>Faturamento</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categorySales as $categoryId => $sales): 
                                        $category = array_filter($restaurantCategories, function($c) use ($categoryId) {
                                            return $c['id'] == $categoryId;
                                        });
                                        $category = reset($category);
                                        $percentage = $totalRevenue > 0 ? ($sales / $totalRevenue) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($category): ?>
                                                    <span class="badge" style="background-color: <?= $category['color'] ?>">
                                                        <i class="<?= $category['icon'] ?> me-1"></i>
                                                        <?= htmlspecialchars($category['name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    Categoria não encontrada
                                                <?php endif; ?>
                                            </td>
                                            <td>R$ <?= number_format($sales, 2, ',', '.') ?></td>
                                            <td><?= number_format($percentage, 1) ?>%</td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de Vendas Diárias
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($dailySales)) ?>,
                datasets: [{
                    label: 'Faturamento (R$)',
                    data: <?= json_encode(array_values($dailySales)) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Status dos Pedidos
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($ordersByStatus)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($ordersByStatus)) ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de Horários de Pico
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($hourlyOrders)) ?>,
                datasets: [{
                    label: 'Número de Pedidos',
                    data: <?= json_encode(array_values($hourlyOrders)) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>