<?php
session_start();

// Verificar se o usuário está logado
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

// Função para salvar dados JSON
function saveJsonData($table, $data) {
    $file = DATA_DIR . $table . '.json';
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_dishes') {
        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        
        $dishes = loadJsonData('dishes');
        $categories = loadJsonData('categories');
        
        // Filtrar pratos do restaurante e categoria
        $filtered_dishes = array_filter($dishes, function($dish) use ($restaurant_id, $category_id) {
            return $dish['restaurant_id'] == $restaurant_id && 
                   $dish['is_available'] == 1 &&
                   ($category_id == 0 || $dish['category_id'] == $category_id);
        });
        
        // Buscar informações das categorias
        $categories_info = [];
        foreach ($categories as $cat) {
            if ($cat['restaurant_id'] == $restaurant_id && $cat['is_active']) {
                $categories_info[$cat['id']] = $cat;
            }
        }
        
        echo json_encode([
            'success' => true,
            'dishes' => array_values($filtered_dishes),
            'categories' => $categories_info
        ]);
        exit;
    }
    
    if ($action === 'create_order') {
        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);
        $items = json_decode($_POST['items'] ?? '[]', true);
        $customer_name = trim($_POST['customer_name'] ?? '');
        $table_number = trim($_POST['table_number'] ?? '');
        
        if (empty($items) || empty($customer_name)) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            exit;
        }
        
        $orders = loadJsonData('orders');
        $dishes = loadJsonData('dishes');
        
        // Criar array de pratos por ID para facilitar busca
        $dishes_by_id = [];
        foreach ($dishes as $dish) {
            $dishes_by_id[$dish['id']] = $dish;
        }
        
        // Calcular total e validar itens
        $total = 0;
        $order_items = [];
        
        foreach ($items as $item) {
            $dish_id = intval($item['dish_id'] ?? 0);
            $quantity = intval($item['quantity'] ?? 0);
            
            if ($quantity <= 0 || !isset($dishes_by_id[$dish_id])) {
                continue;
            }
            
            $dish = $dishes_by_id[$dish_id];
            
            if ($dish['restaurant_id'] != $restaurant_id || !$dish['is_available']) {
                continue;
            }
            
            $item_total = $dish['price'] * $quantity;
            $total += $item_total;
            
            $order_items[] = [
                'dish_id' => $dish_id,
                'dish_name' => $dish['name'],
                'price' => $dish['price'],
                'quantity' => $quantity,
                'subtotal' => $item_total
            ];
        }
        
        if (empty($order_items)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum item válido no pedido']);
            exit;
        }
        
        // Gerar novo ID do pedido
        $new_id = 1;
        foreach ($orders as $order) {
            if ($order['id'] >= $new_id) {
                $new_id = $order['id'] + 1;
            }
        }
        
        // Criar novo pedido
        $new_order = [
            'id' => $new_id,
            'restaurant_id' => $restaurant_id,
            'customer_name' => $customer_name,
            'table_number' => $table_number,
            'items' => $order_items,
            'total' => $total,
            'status' => 'pending', // pending, preparing, ready, delivered
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $orders[] = $new_order;
        
        if (saveJsonData('orders', $orders)) {
            echo json_encode([
                'success' => true, 
                'order_id' => $new_id,
                'total' => $total,
                'message' => 'Pedido criado com sucesso!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar pedido']);
        }
        exit;
    }
}

// Carregar dados para exibição inicial
$restaurants = loadJsonData('restaurants');
$selected_restaurant = null;

// Verificar se há um restaurante selecionado via URL
$restaurant_id = intval($_GET['restaurant_id'] ?? 0);
if ($restaurant_id > 0) {
    foreach ($restaurants as $restaurant) {
        if ($restaurant['id'] == $restaurant_id) {
            $selected_restaurant = $restaurant;
            break;
        }
    }
}

// Se há apenas um restaurante, selecionar automaticamente
if (!$selected_restaurant && count($restaurants) === 1) {
    $selected_restaurant = $restaurants[0];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Totem de Pedidos - Prato Rápido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/prato-rapido-theme.css" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff6b00;
            --secondary-yellow: #ffc700;
            --dark-gray: #2c3e50;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
        body {
            background: linear-gradient(135deg, #FF6B00 0%, #FFC700 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: #1E1E1E;
        }
        
        .kiosk-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            min-height: 70vh;
        }
        
        .category-btn {
            border: none;
            border-radius: 15px;
            padding: 15px;
            margin: 5px;
            transition: all 0.3s;
            background: #FFFFFF;
            color: #1E1E1E;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .category-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 107, 0, 0.3);
            background: #FF6B00;
            color: #FFFFFF;
        }
        
        .category-btn.active {
            background: #FF6B00;
            color: #FFFFFF;
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.4);
        }
        
        .dish-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .dish-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .dish-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 3rem;
        }
        
        .price-display {
            background: #00B894;
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .cart-sidebar {
            background: #F5F5F5;
            border-radius: 15px;
            padding: 20px;
            position: sticky;
            top: 20px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid #dee2e6;
        }
        
        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 50%;
            background: #FF6B00;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
        }
        
        .quantity-btn:hover {
            background: #E55A00;
            transform: scale(1.1);
        }
        
        .quantity-display {
            font-weight: bold;
            font-size: 1.1rem;
            min-width: 30px;
            text-align: center;
        }
        
        .checkout-btn {
            background: #00B894;
            border: none;
            border-radius: 15px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            padding: 15px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .checkout-btn:hover {
            background: #00A085;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 184, 148, 0.3);
        }
        
        .restaurant-selector {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .restaurant-btn {
            background: #FF6B00;
            border: none;
            border-radius: 15px;
            color: white;
            padding: 20px;
            margin: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s;
            min-width: 200px;
        }
        
        .restaurant-btn:hover {
            background: #E55A00;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 107, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="kiosk-container">
        <!-- Header -->
        <div class="header-card p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-utensils text-primary me-3"></i>
                        Pedidos Online
                    </h1>
                    <p class="text-muted mb-0" id="restaurantName">
                        <?= $selected_restaurant ? htmlspecialchars($selected_restaurant['name']) : 'Selecione um restaurante' ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <i class="fas fa-clock text-muted me-2"></i>
                        <span id="currentTime" class="text-muted"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Seletor de Restaurante -->
        <?php if (!$selected_restaurant): ?>
            <div class="main-card p-4">
                <div class="restaurant-selector">
                    <h2 class="mb-4">Escolha seu Restaurante</h2>
                    <p class="text-muted mb-4">Faça seu pedido online de forma rápida e prática</p>
                    <div class="row justify-content-center">
                        <?php foreach ($restaurants as $restaurant): ?>
                            <div class="col-auto">
                                <button class="restaurant-btn" onclick="selectRestaurant(<?= $restaurant['id'] ?>, '<?= htmlspecialchars($restaurant['name']) ?>')">
                                    <i class="fas fa-store mb-2 d-block"></i>
                                    <?= htmlspecialchars($restaurant['name']) ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Interface Principal -->
            <div class="main-card p-4" id="mainInterface" data-restaurant-id="<?= $selected_restaurant['id'] ?>">
                <div class="row">
                    <!-- Menu Principal -->
                    <div class="col-lg-8">
                        <!-- Categorias -->
                        <div class="mb-4">
                            <h3 class="mb-3">Categorias</h3>
                            <div class="row" id="categoriesContainer">
                                <div class="col-auto">
                                    <button class="category-btn active" style="background: #6c757d;" onclick="selectCategory(0)">
                                        <i class="fas fa-th-large fa-2x mb-2"></i>
                                        <span>Todos</span>
                                    </button>
                                </div>
                                <!-- Categorias serão carregadas via JavaScript -->
                            </div>
                        </div>
                        
                        <!-- Pratos -->
                        <div>
                            <h3 class="mb-3">Pratos Disponíveis</h3>
                            <div class="row" id="dishesContainer">
                                <!-- Pratos serão carregados via JavaScript -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carrinho -->
                    <div class="col-lg-4">
                        <div class="cart-sidebar">
                            <h4 class="mb-3">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Seu Pedido
                            </h4>
                            
                            <div id="cartItems">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                    <p>Seu carrinho está vazio</p>
                                </div>
                            </div>
                            
                            <div id="cartSummary" class="d-none">
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <strong>Total:</strong>
                                    <strong class="h4 text-success" id="cartTotal">R$ 0,00</strong>
                                </div>
                                
                                <button class="btn checkout-btn w-100" onclick="showCheckoutModal()">
                                    <i class="fas fa-check me-2"></i>
                                    Finalizar Pedido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal de Finalização -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Finalizar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="checkoutForm">
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Nome do Cliente *</label>
                            <input type="text" class="form-control" id="customerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="tableNumber" class="form-label">Número da Mesa</label>
                            <input type="text" class="form-control" id="tableNumber" placeholder="Opcional">
                        </div>
                        
                        <div class="mb-3">
                            <h6>Resumo do Pedido:</h6>
                            <div id="orderSummary"></div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong class="text-success" id="finalTotal">R$ 0,00</strong>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="submitOrder()">
                        <i class="fas fa-check me-2"></i>Confirmar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Sucesso -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h4>Pedido Realizado com Sucesso!</h4>
                    <p class="text-muted mb-3">Seu pedido foi enviado para a cozinha.</p>
                    <div class="alert alert-info">
                        <strong>Número do Pedido:</strong> <span id="orderNumber"></span>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="resetKiosk()">Fazer Novo Pedido</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentRestaurantId = <?= $selected_restaurant ? $selected_restaurant['id'] : 0 ?>;
        let currentCategoryId = 0;
        let cart = [];
        let dishes = [];
        let categories = {};
        
        // Atualizar relógio
        function updateClock() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('pt-BR');
        }
        
        setInterval(updateClock, 1000);
        updateClock();
        
        // Selecionar restaurante
        function selectRestaurant(restaurantId, restaurantName) {
            currentRestaurantId = restaurantId;
            document.getElementById('restaurantName').textContent = restaurantName;
            
            // Recarregar a página com o restaurante selecionado
            window.location.href = 'kiosk.php?restaurant_id=' + restaurantId;
        }
        
        // Carregar dados do restaurante
        function loadRestaurantData() {
            if (currentRestaurantId === 0) return;
            
            const formData = new FormData();
            formData.append('action', 'get_dishes');
            formData.append('restaurant_id', currentRestaurantId);
            formData.append('category_id', currentCategoryId);
            
            fetch('kiosk.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    dishes = data.dishes;
                    categories = data.categories;
                    renderCategories();
                    renderDishes();
                }
            })
            .catch(error => console.error('Erro:', error));
        }
        
        // Renderizar categorias
        function renderCategories() {
            const container = document.getElementById('categoriesContainer');
            const existingButtons = container.querySelectorAll('.category-btn:not(.active)');
            existingButtons.forEach(btn => btn.remove());
            
            Object.values(categories).forEach(category => {
                const button = document.createElement('div');
                button.className = 'col-auto';
                button.innerHTML = `
                    <button class="category-btn" style="background: ${category.color};" onclick="selectCategory(${category.id})">
                        <i class="${category.icon} fa-2x mb-2"></i>
                        <span>${category.name}</span>
                    </button>
                `;
                container.appendChild(button);
            });
        }
        
        // Selecionar categoria
        function selectCategory(categoryId) {
            currentCategoryId = categoryId;
            
            // Atualizar botões ativos
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.closest('.category-btn').classList.add('active');
            
            loadRestaurantData();
        }
        
        // Renderizar pratos
        function renderDishes() {
            const container = document.getElementById('dishesContainer');
            container.innerHTML = '';
            
            if (dishes.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-utensils fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Nenhum prato disponível</h4>
                    </div>
                `;
                return;
            }
            
            dishes.forEach(dish => {
                const dishCard = document.createElement('div');
                dishCard.className = 'col-md-6 col-lg-4';
                dishCard.innerHTML = `
                    <div class="card dish-card">
                        <div class="dish-image">
                            ${dish.image_url && dish.image_url !== '' ? `<img src="${dish.image_url}" alt="${dish.name}" style="width: 100%; height: 100%; object-fit: cover;">` : '<i class="fas fa-utensils fa-3x"></i>'}
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">${dish.name}</h5>
                            <p class="card-text text-muted small">${dish.description || ''}</p>
                            ${dish.ingredients ? `<p class="card-text"><small class="text-muted"><strong>Ingredientes:</strong> ${dish.ingredients}</small></p>` : ''}
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price-display">R$ ${parseFloat(dish.price).toFixed(2).replace('.', ',')}</span>
                                <button class="btn btn-primary" onclick="addToCart(${dish.id})">
                                    <i class="fas fa-plus me-1"></i>Adicionar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(dishCard);
            });
        }
        
        // Adicionar ao carrinho
        function addToCart(dishId) {
            const dish = dishes.find(d => d.id === dishId);
            if (!dish) return;
            
            const existingItem = cart.find(item => item.dish_id === dishId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    dish_id: dishId,
                    dish_name: dish.name,
                    price: parseFloat(dish.price),
                    quantity: 1
                });
            }
            
            renderCart();
        }
        
        // Renderizar carrinho
        function renderCart() {
            const cartItems = document.getElementById('cartItems');
            const cartSummary = document.getElementById('cartSummary');
            
            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <p>Seu carrinho está vazio</p>
                    </div>
                `;
                cartSummary.classList.add('d-none');
                return;
            }
            
            let total = 0;
            cartItems.innerHTML = '';
            
            cart.forEach((item, index) => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                
                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';
                cartItem.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${item.dish_name}</h6>
                            <small class="text-muted">R$ ${item.price.toFixed(2).replace('.', ',')}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="updateQuantity(${index}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="quantity-display">${item.quantity}</span>
                            <button class="quantity-btn" onclick="updateQuantity(${index}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <strong>R$ ${subtotal.toFixed(2).replace('.', ',')}</strong>
                    </div>
                `;
                cartItems.appendChild(cartItem);
            });
            
            document.getElementById('cartTotal').textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
            cartSummary.classList.remove('d-none');
        }
        
        // Atualizar quantidade
        function updateQuantity(index, change) {
            cart[index].quantity += change;
            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }
            renderCart();
        }
        
        // Remover do carrinho
        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }
        
        // Mostrar modal de checkout
        function showCheckoutModal() {
            if (cart.length === 0) return;
            
            const orderSummary = document.getElementById('orderSummary');
            const finalTotal = document.getElementById('finalTotal');
            
            let total = 0;
            orderSummary.innerHTML = '';
            
            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                
                const summaryItem = document.createElement('div');
                summaryItem.className = 'd-flex justify-content-between mb-1';
                summaryItem.innerHTML = `
                    <span>${item.dish_name} x${item.quantity}</span>
                    <span>R$ ${subtotal.toFixed(2).replace('.', ',')}</span>
                `;
                orderSummary.appendChild(summaryItem);
            });
            
            finalTotal.textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
            
            new bootstrap.Modal(document.getElementById('checkoutModal')).show();
        }
        
        // Enviar pedido
        function submitOrder() {
            const customerName = document.getElementById('customerName').value.trim();
            const tableNumber = document.getElementById('tableNumber').value.trim();
            
            if (!customerName) {
                alert('Por favor, informe o nome do cliente.');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create_order');
            formData.append('restaurant_id', currentRestaurantId);
            formData.append('customer_name', customerName);
            formData.append('table_number', tableNumber);
            formData.append('items', JSON.stringify(cart));
            
            fetch('kiosk.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('orderNumber').textContent = data.order_id;
                    bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
                    new bootstrap.Modal(document.getElementById('successModal')).show();
                } else {
                    alert(data.message || 'Erro ao criar pedido');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar pedido');
            });
        }
        
        // Resetar kiosk
        function resetKiosk() {
            cart = [];
            renderCart();
            document.getElementById('customerName').value = '';
            document.getElementById('tableNumber').value = '';
            bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
        }
        
        // Inicializar
        if (currentRestaurantId > 0) {
            loadRestaurantData();
        }
    </script>
</body>
</html>