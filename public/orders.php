<?php
session_start();

// Verificar se está logado
if (!isset($_SESSION['restaurant_id'])) {
    header('Location: simple_auth.php');
    exit;
}

// Incluir helper de planos
require_once 'plan_helper.php';

// Verificar funcionalidades disponíveis
$has_order_tracking = hasFeatureAccess($_SESSION['restaurant_id'], 'order_tracking');
$has_advanced_reports = hasFeatureAccess($_SESSION['restaurant_id'], 'advanced_reports');
$current_plan = getCurrentPlanName($_SESSION['restaurant_id']);

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
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $restaurant_id = $_SESSION['restaurant_id'];
    
    if ($action === 'update_order_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        
        $valid_statuses = ['pending', 'preparing', 'ready', 'delivered', 'cancelled'];
        
        if (!in_array($new_status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Status inválido']);
            exit;
        }
        
        $orders = loadJsonData('orders');
        $updated = false;
        
        foreach ($orders as &$order) {
            if ($order['id'] == $order_id && $order['restaurant_id'] == $restaurant_id) {
                $order['status'] = $new_status;
                $order['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }
        
        if ($updated && saveJsonData('orders', $orders)) {
            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status']);
        }
        exit;
    }
    
    if ($action === 'get_orders') {
        $status_filter = $_POST['status_filter'] ?? 'all';
        $date_filter = $_POST['date_filter'] ?? date('Y-m-d');
        
        $orders = loadJsonData('orders');
        
        // Filtrar pedidos do restaurante
        $filtered_orders = array_filter($orders, function($order) use ($restaurant_id, $status_filter, $date_filter) {
            $order_date = date('Y-m-d', strtotime($order['created_at']));
            
            $restaurant_match = $order['restaurant_id'] == $restaurant_id;
            $date_match = ($date_filter === 'all' || $order_date === $date_filter);
            $status_match = ($status_filter === 'all' || $order['status'] === $status_filter);
            
            return $restaurant_match && $date_match && $status_match;
        });
        
        // Ordenar por data de criação (mais recentes primeiro)
        usort($filtered_orders, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        echo json_encode([
            'success' => true,
            'orders' => array_values($filtered_orders)
        ]);
        exit;
    }
}

// Carregar dados para estatísticas
$restaurant_id = $_SESSION['restaurant_id'];
$orders = loadJsonData('orders');
$restaurant_orders = array_filter($orders, function($order) use ($restaurant_id) {
    return $order['restaurant_id'] == $restaurant_id;
});

// Calcular estatísticas do dia
$today = date('Y-m-d');
$today_orders = array_filter($restaurant_orders, function($order) use ($today) {
    return date('Y-m-d', strtotime($order['created_at'])) === $today;
});

$stats = [
    'total_today' => count($today_orders),
    'pending' => count(array_filter($today_orders, function($o) { return $o['status'] === 'pending'; })),
    'preparing' => count(array_filter($today_orders, function($o) { return $o['status'] === 'preparing'; })),
    'ready' => count(array_filter($today_orders, function($o) { return $o['status'] === 'ready'; })),
    'delivered' => count(array_filter($today_orders, function($o) { return $o['status'] === 'delivered'; })),
    'cancelled' => count(array_filter($today_orders, function($o) { return $o['status'] === 'cancelled'; })),
    'revenue_today' => array_sum(array_map(function($o) { return $o['total']; }, $today_orders))
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pedidos - Prato Rápido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff6b00;
            --secondary-yellow: #ffc700;
            --dark-gray: #2c3e50;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
        body {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .order-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-preparing {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #74c0fc;
        }
        
        .status-ready {
            background: #d4edda;
            color: #155724;
            border: 1px solid #51cf66;
        }
        
        .status-delivered {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #ced4da;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .order-header {
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .order-items {
            padding: 15px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-actions {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            border-top: 1px solid #dee2e6;
        }
        
        .status-btn {
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            margin: 2px;
            font-size: 0.85rem;
            font-weight: bold;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .btn-pending {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-preparing {
            background: #007bff;
            color: white;
        }
        
        .btn-ready {
            background: #28a745;
            color: white;
        }
        
        .btn-delivered {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancelled {
            background: #dc3545;
            color: white;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .refresh-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 10px 20px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .nav-pills .nav-link {
            border-radius: 25px;
            margin: 0 5px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(45deg, #007bff, #0056b3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        /* Estilos Kanban */
        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 20px;
        }

        .kanban-column {
            flex: 1;
            min-width: 300px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            padding: 15px;
        }

        .kanban-column-header {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
            color: var(--dark-gray);
        }

        .kanban-cards {
            min-height: 400px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-card p-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-clipboard-list text-primary me-3"></i>
                        Gestão de Pedidos
                    </h1>
                    <p class="text-muted mb-0">Gerencie os pedidos do seu restaurante</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <?php if ($has_order_tracking): ?>
                            <button class="refresh-btn" onclick="loadOrders()">
                                <i class="fas fa-sync-alt me-2"></i>Atualizar
                            </button>
                        <?php else: ?>
                            <button class="btn btn-warning" onclick="window.location.href='plans.php'">
                                <i class="fas fa-lock me-2"></i>Fazer Upgrade
                            </button>
                        <?php endif; ?>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock text-muted me-2"></i>
                            <span id="currentTime" class="text-muted"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!$has_order_tracking): ?>
                <div class="alert alert-warning mt-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <i class="fas fa-lock me-2"></i>
                            <strong>Rastreamento Avançado de Pedidos</strong> disponível no plano Professional ou superior.
                            <br><small>Plano atual: <?= $current_plan ?> - Funcionalidades limitadas</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="plans.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-up me-2"></i>Fazer Upgrade
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Estatísticas -->
        <div class="row">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_today'] ?></div>
                    <div class="stat-label">Total Hoje</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #ff8f00);">
                    <div class="stat-number"><?= $stats['pending'] ?></div>
                    <div class="stat-label">Pendentes</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                    <div class="stat-number"><?= $stats['preparing'] ?></div>
                    <div class="stat-label">Preparando</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <div class="stat-number"><?= $stats['ready'] ?></div>
                    <div class="stat-label">Prontos</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card" style="background: linear-gradient(135deg, #6c757d, #495057);">
                    <div class="stat-number"><?= $stats['delivered'] ?></div>
                    <div class="stat-label">Entregues</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                    <div class="stat-number"><?= $stats['cancelled'] ?></div>
                    <div class="stat-label">Cancelados</div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <div class="stat-number">R$ <?= number_format($stats['revenue_today'], 2, ',', '.') ?></div>
                    <div class="stat-label">Receita Hoje</div>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filter-card">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-3">Filtros</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="dateFilter" class="form-label">Data:</label>
                            <input type="date" class="form-control" id="dateFilter" value="<?= date('Y-m-d') ?>" onchange="loadOrders()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status:</label>
                            <ul class="nav nav-pills" id="statusFilter">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#" data-status="all" onclick="filterByStatus('all')">Todos</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-status="pending" onclick="filterByStatus('pending')">Pendentes</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-status="preparing" onclick="filterByStatus('preparing')">Preparando</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-status="ready" onclick="filterByStatus('ready')">Prontos</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-status="cancelled" onclick="filterByStatus('cancelled')">Cancelados</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <span class="me-3">Auto-atualização:</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                            <label class="form-check-label" for="autoRefresh">Ativada</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kanban Board -->
        <div class="kanban-board">
            <!-- Coluna Pendente -->
            <div class="kanban-column">
                <div class="kanban-column-header status-pending">Pendente</div>
                <div class="kanban-cards" id="kanban-pending"></div>
            </div>
            <!-- Coluna Preparando -->
            <div class="kanban-column">
                <div class="kanban-column-header status-preparing">Preparando</div>
                <div class="kanban-cards" id="kanban-preparing"></div>
            </div>
            <!-- Coluna Pronto -->
            <div class="kanban-column">
                <div class="kanban-column-header status-ready">Pronto</div>
                <div class="kanban-cards" id="kanban-ready"></div>
            </div>
        </div>

        <!-- Pedidos Entregues e Cancelados -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="admin-card p-3">
                    <h5 class="text-center"><i class="fas fa-check-circle me-2"></i>Entregues</h5>
                    <div id="orders-delivered"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-card p-3">
                    <h5 class="text-center"><i class="fas fa-times-circle me-2"></i>Cancelados</h5>
                    <div id="orders-cancelled"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStatusFilter = 'all';
        let autoRefreshInterval;
        
        // Atualizar relógio
        function updateClock() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('pt-BR');
        }
        
        setInterval(updateClock, 1000);
        updateClock();
        
        // Filtrar por status
        function filterByStatus(status) {
            currentStatusFilter = status;
            
            // Atualizar navegação
            document.querySelectorAll('#statusFilter .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`[data-status="${status}"]`).classList.add('active');
            
            loadOrders();
        }
        
        // Carregar pedidos
        function loadOrders() {
            const dateFilter = document.getElementById('dateFilter').value;
            
            const formData = new FormData();
            formData.append('action', 'get_orders');
            formData.append('status_filter', currentStatusFilter);
            formData.append('date_filter', dateFilter);
            
            fetch('orders.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderOrders(data.orders);
                } else {
                    console.error('Erro ao carregar pedidos');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
            });
        }
        
        // Renderizar pedidos no Kanban
        function renderOrders(orders) {
            // Limpar colunas
            const columns = {
                pending: document.getElementById('kanban-pending'),
                preparing: document.getElementById('kanban-preparing'),
                ready: document.getElementById('kanban-ready'),
                delivered: document.getElementById('orders-delivered'),
                cancelled: document.getElementById('orders-cancelled')
            };

            for (const col in columns) {
                columns[col].innerHTML = '';
            }

            if (orders.length === 0) {
                columns.pending.innerHTML = '<p class="text-muted text-center">Nenhum pedido pendente.</p>';
                return;
            }

            orders.forEach(order => {
                const orderCard = createOrderCard(order);
                const status = order.status;

                if (columns[status]) {
                    columns[status].appendChild(orderCard);
                }
            });
        }

        // Criar card do pedido
        function createOrderCard(order) {
            const orderCard = document.createElement('div');
            orderCard.className = 'order-card card mb-3';
            orderCard.setAttribute('data-order-id', order.id);

            const statusClass = `status-${order.status}`;
            const statusText = getStatusText(order.status);

            let itemsHtml = '';
            order.items.forEach(item => {
                itemsHtml += `
                    <div class="order-item">
                        <span>${item.quantity}x ${item.dish_name}</span>
                        <span class="fw-bold">R$ ${parseFloat(item.subtotal).toFixed(2).replace('.', ',')}</span>
                    </div>
                `;
            });

            orderCard.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Pedido #${order.id}</h5>
                        <span class="badge ${statusClass}">${statusText}</span>
                    </div>
                    <p class="card-text mb-1"><strong>Cliente:</strong> ${order.customer_name}</p>
                    <p class="card-text text-muted small mb-2">
                        <i class="fas fa-clock"></i> ${getTimeAgo(order.created_at)}
                        <span class="mx-2">|</span>
                        <i class="${getOriginIcon(order.origin || 'totem')}"></i> ${getOriginText(order.origin || 'totem')}
                    </p>
                    <div class="order-items mb-3">${itemsHtml}</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Total: R$ ${parseFloat(order.total).toFixed(2).replace('.', ',')}</h6>
                        <div>${getStatusButtons(order.id, order.status)}</div>
                    </div>
                </div>
            `;
            return orderCard;
        }
        
        // Obter texto do status
        function getStatusText(status) {
            const statusMap = {
                'pending': 'Pendente',
                'preparing': 'Preparando',
                'ready': 'Pronto',
                'delivered': 'Entregue',
                'cancelled': 'Cancelado'
            };
            return statusMap[status] || status;
        }
        
        // Obter botões de status
        function getStatusButtons(orderId, currentStatus) {
            const buttons = [];
            const buttonClasses = {
                preparing: 'btn-primary',
                ready: 'btn-success',
                delivered: 'btn-secondary',
                cancelled: 'btn-danger'
            };

            if (currentStatus === 'pending') {
                buttons.push(`<button class="btn ${buttonClasses.preparing} btn-sm" onclick="updateOrderStatus(${orderId}, 'preparing')">Preparar</button>`);
                buttons.push(`<button class="btn ${buttonClasses.cancelled} btn-sm" onclick="updateOrderStatus(${orderId}, 'cancelled')">Cancelar</button>`);
            } else if (currentStatus === 'preparing') {
                buttons.push(`<button class="btn ${buttonClasses.ready} btn-sm" onclick="updateOrderStatus(${orderId}, 'ready')">Pronto</button>`);
            } else if (currentStatus === 'ready') {
                buttons.push(`<button class="btn ${buttonClasses.delivered} btn-sm" onclick="updateOrderStatus(${orderId}, 'delivered')">Entregar</button>`);
            }
            
            return buttons.join(' ');
        }
        
        // Atualizar status do pedido
        function updateOrderStatus(orderId, newStatus) {
            const formData = new FormData();
            formData.append('action', 'update_order_status');
            formData.append('order_id', orderId);
            formData.append('status', newStatus);
            
            fetch('orders.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadOrders(); // Recarregar pedidos
                } else {
                    alert(data.message || 'Erro ao atualizar status');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar solicitação');
            });
        }
        
        // Formatar data e hora
        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleString('pt-BR');
        }
        
        function getTimeAgo(dateString) {
            const now = new Date();
            const orderTime = new Date(dateString);
            const diffMs = now - orderTime;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);

            if (diffMins < 1) return 'Agora mesmo';
            if (diffMins < 60) return `${diffMins} min atrás`;
            if (diffHours < 24) return `${diffHours}h atrás`;
            return `${diffDays} dias atrás`;
        }

        function getOriginText(origin) {
            const origins = {
                'totem': 'Totem',
                'app': 'App',
                'phone': 'Telefone',
                'web': 'Site',
                'whatsapp': 'WhatsApp'
            };
            return origins[origin] || 'Totem';
        }

        function getOriginIcon(origin) {
            const icons = {
                'totem': 'fas fa-desktop',
                'app': 'fas fa-mobile-alt',
                'phone': 'fas fa-phone',
                'web': 'fas fa-globe',
                'whatsapp': 'fab fa-whatsapp'
            };
            return icons[origin] || 'fas fa-desktop';
        }
        
        // Configurar auto-atualização
        document.getElementById('autoRefresh').addEventListener('change', function() {
            if (this.checked) {
                autoRefreshInterval = setInterval(loadOrders, 15000); // Atualiza a cada 15 segundos
            } else {
                clearInterval(autoRefreshInterval);
            }
        });

        // Carregamento inicial
        document.addEventListener('DOMContentLoaded', () => {
            loadOrders();
            autoRefreshInterval = setInterval(loadOrders, 15000);
        });
    </script>
</body>
</html>