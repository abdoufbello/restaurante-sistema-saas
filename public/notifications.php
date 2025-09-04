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

// Função para salvar dados JSON
function saveJsonData($filename, $data) {
    $filepath = '../writable/data/' . $filename;
    return file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Definir limites dos planos
$planLimits = [
    'trial' => ['totems' => 1, 'orders_per_month' => 50, 'dishes' => 10],
    'starter' => ['totems' => 2, 'orders_per_month' => 200, 'dishes' => 50],
    'professional' => ['totems' => 5, 'orders_per_month' => 1000, 'dishes' => 200],
    'enterprise' => ['totems' => -1, 'orders_per_month' => -1, 'dishes' => -1]
];

$restaurants = loadJsonData('restaurants.json');
$dishes = loadJsonData('dishes.json');
$orders = loadJsonData('orders.json');

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

$currentPlan = $currentRestaurant['subscription_plan'] ?? 'trial';
$limits = $planLimits[$currentPlan];

// Calcular uso atual
$currentDishes = count(array_filter($dishes, function($dish) {
    return $dish['restaurant_id'] == $_SESSION['restaurant_id'];
}));

$currentMonth = date('Y-m');
$currentOrders = count(array_filter($orders, function($order) use ($currentMonth) {
    return $order['restaurant_id'] == $_SESSION['restaurant_id'] && 
           strpos($order['created_at'], $currentMonth) === 0;
}));

// Calcular dias até expiração
$expiresAt = new DateTime($currentRestaurant['subscription_expires']);
$now = new DateTime();
$daysLeft = $now->diff($expiresAt)->days;
if ($expiresAt < $now) {
    $daysLeft = -$daysLeft; // Negativo se já expirou
}

// Gerar notificações
$notifications = [];

// Verificar expiração da assinatura
if ($daysLeft <= 0) {
    $notifications[] = [
        'type' => 'danger',
        'icon' => 'fas fa-exclamation-triangle',
        'title' => 'Assinatura Expirada!',
        'message' => 'Sua assinatura expirou. Renove agora para continuar usando o sistema.',
        'action' => 'plans.php',
        'action_text' => 'Renovar Agora'
    ];
} elseif ($daysLeft <= 7) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'fas fa-clock',
        'title' => 'Assinatura Expirando',
        'message' => "Sua assinatura expira em {$daysLeft} dias. Renove para evitar interrupções.",
        'action' => 'plans.php',
        'action_text' => 'Renovar'
    ];
}

// Verificar limite de pratos
if ($limits['dishes'] > 0) {
    $dishUsage = ($currentDishes / $limits['dishes']) * 100;
    if ($currentDishes >= $limits['dishes']) {
        $notifications[] = [
            'type' => 'danger',
            'icon' => 'fas fa-utensils',
            'title' => 'Limite de Pratos Atingido',
            'message' => "Você atingiu o limite de {$limits['dishes']} pratos do seu plano.",
            'action' => 'plans.php',
            'action_text' => 'Fazer Upgrade'
        ];
    } elseif ($dishUsage >= 80) {
        $notifications[] = [
            'type' => 'warning',
            'icon' => 'fas fa-utensils',
            'title' => 'Limite de Pratos Próximo',
            'message' => "Você está usando {$currentDishes} de {$limits['dishes']} pratos disponíveis.",
            'action' => 'plans.php',
            'action_text' => 'Ver Planos'
        ];
    }
}

// Verificar limite de pedidos mensais
if ($limits['orders_per_month'] > 0) {
    $orderUsage = ($currentOrders / $limits['orders_per_month']) * 100;
    if ($currentOrders >= $limits['orders_per_month']) {
        $notifications[] = [
            'type' => 'danger',
            'icon' => 'fas fa-shopping-cart',
            'title' => 'Limite de Pedidos Atingido',
            'message' => "Você atingiu o limite de {$limits['orders_per_month']} pedidos mensais.",
            'action' => 'plans.php',
            'action_text' => 'Fazer Upgrade'
        ];
    } elseif ($orderUsage >= 80) {
        $notifications[] = [
            'type' => 'warning',
            'icon' => 'fas fa-shopping-cart',
            'title' => 'Limite de Pedidos Próximo',
            'message' => "Você processou {$currentOrders} de {$limits['orders_per_month']} pedidos este mês.",
            'action' => 'plans.php',
            'action_text' => 'Ver Planos'
        ];
    }
}

// Marcar notificação como lida via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    // Em um sistema real, isso seria salvo no banco de dados
    echo json_encode(['success' => true]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Sistema de Totem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .notification-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .notification-danger {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%);
        }
        .notification-warning {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fffbf0 0%, #fff3cd 100%);
        }
        .notification-info {
            border-left-color: #0dcaf0;
            background: linear-gradient(135deg, #f0fcff 0%, #cff4fc 100%);
        }
        .notification-success {
            border-left-color: #198754;
            background: linear-gradient(135deg, #f0fff4 0%, #d1e7dd 100%);
        }
        .usage-bar {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }
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
                    <i class="fas fa-bell me-2"></i>
                    Central de Notificações
                    <?php if (count($notifications) > 0): ?>
                        <span class="badge bg-danger ms-2"><?= count($notifications) ?></span>
                    <?php endif; ?>
                </h2>
            </div>
        </div>

        <!-- Estatísticas de Uso -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie me-2"></i>
                            Uso do Plano <?= ucfirst($currentPlan) ?>
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Pratos no Cardápio</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <?php if ($limits['dishes'] > 0): ?>
                                            <div class="usage-bar bg-light bg-opacity-25">
                                                <div class="bg-warning h-100" style="width: <?= min(100, ($currentDishes / $limits['dishes']) * 100) ?>%"></div>
                                            </div>
                                            <small><?= $currentDishes ?> / <?= $limits['dishes'] ?></small>
                                        <?php else: ?>
                                            <small>Ilimitado (<?= $currentDishes ?> em uso)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6>Pedidos Este Mês</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <?php if ($limits['orders_per_month'] > 0): ?>
                                            <div class="usage-bar bg-light bg-opacity-25">
                                                <div class="bg-info h-100" style="width: <?= min(100, ($currentOrders / $limits['orders_per_month']) * 100) ?>%"></div>
                                            </div>
                                            <small><?= $currentOrders ?> / <?= $limits['orders_per_month'] ?></small>
                                        <?php else: ?>
                                            <small>Ilimitado (<?= $currentOrders ?> este mês)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6>Dias Restantes</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <?php if ($daysLeft > 0): ?>
                                            <div class="usage-bar bg-light bg-opacity-25">
                                                <div class="bg-success h-100" style="width: <?= min(100, ($daysLeft / 30) * 100) ?>%"></div>
                                            </div>
                                            <small><?= $daysLeft ?> dias</small>
                                        <?php else: ?>
                                            <small class="text-warning">⚠️ Expirado</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notificações -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($notifications)): ?>
                    <div class="card notification-card notification-success">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4>Tudo em Ordem!</h4>
                            <p class="text-muted mb-0">Não há notificações pendentes no momento.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $index => $notification): ?>
                        <div class="card notification-card notification-<?= $notification['type'] ?> mb-3" id="notification-<?= $index ?>">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-1 text-center">
                                        <i class="<?= $notification['icon'] ?> fa-2x text-<?= $notification['type'] ?>"></i>
                                    </div>
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-1 text-<?= $notification['type'] ?>">
                                            <?= $notification['title'] ?>
                                        </h5>
                                        <p class="card-text mb-0">
                                            <?= $notification['message'] ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="<?= $notification['action'] ?>" class="btn btn-<?= $notification['type'] ?> me-2">
                                            <?= $notification['action_text'] ?>
                                        </a>
                                        <button class="btn btn-outline-secondary btn-sm" onclick="markAsRead(<?= $index ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dicas e Sugestões -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            Dicas para Otimizar seu Uso
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-utensils me-2 text-warning"></i>Gestão de Cardápio</h6>
                                <ul class="list-unstyled">
                                    <li>• Mantenha apenas pratos populares ativos</li>
                                    <li>• Use categorias para organizar melhor</li>
                                    <li>• Atualize preços regularmente</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-chart-line me-2 text-success"></i>Aumento de Vendas</h6>
                                <ul class="list-unstyled">
                                    <li>• Promova combos e ofertas especiais</li>
                                    <li>• Use fotos atrativas nos pratos</li>
                                    <li>• Monitore relatórios de vendas</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markAsRead(index) {
            const notification = document.getElementById('notification-' + index);
            if (notification) {
                notification.style.opacity = '0.5';
                notification.style.transform = 'translateX(-100%)';
                
                setTimeout(() => {
                    notification.remove();
                    
                    // Verificar se não há mais notificações
                    const remainingNotifications = document.querySelectorAll('[id^="notification-"]');
                    if (remainingNotifications.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        }
        
        // Auto-refresh a cada 5 minutos
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>