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
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Verificar token do restaurante
$token = $_GET['token'] ?? '';
$restaurant_id = null;
$restaurant = null;

if (empty($token)) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Token de acesso inválido</h2><p>Este link não é válido ou expirou.</p></div>');
}

// Carregar tokens de kiosk
$kiosk_tokens = loadJsonData('kiosk_tokens');
$valid_token = false;

foreach ($kiosk_tokens as $kiosk_token) {
    if ($kiosk_token['token'] === $token && $kiosk_token['is_active']) {
        $restaurant_id = $kiosk_token['restaurant_id'];
        $valid_token = true;
        break;
    }
}

if (!$valid_token) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Token de acesso inválido</h2><p>Este link não é válido ou expirou.</p></div>');
}

// Carregar dados do restaurante
$restaurants = loadJsonData('restaurants');
foreach ($restaurants as $rest) {
    if ($rest['id'] == $restaurant_id) {
        $restaurant = $rest;
        break;
    }
}

if (!$restaurant) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Restaurante não encontrado</h2><p>O restaurante associado a este link não foi encontrado.</p></div>');
}

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_dishes') {
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
        $customer_name = trim($_POST['customer_name'] ?? '');
        $table_number = trim($_POST['table_number'] ?? '');
        $items = json_decode($_POST['items'] ?? '[]', true);
        
        // Validações
        if (empty($customer_name) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            exit;
        }
        
        // Verificar se os pratos existem e calcular total
        $dishes = loadJsonData('dishes');
        $total = 0;
        $valid_items = [];
        
        foreach ($items as $item) {
            $dish_id = intval($item['dish_id'] ?? 0);
            $quantity = intval($item['quantity'] ?? 0);
            
            if ($quantity <= 0) continue;
            
            foreach ($dishes as $dish) {
                if ($dish['id'] == $dish_id && $dish['restaurant_id'] == $restaurant_id && $dish['is_available']) {
                    $item_total = $dish['price'] * $quantity;
                    $total += $item_total;
                    
                    $valid_items[] = [
                        'dish_id' => $dish_id,
                        'dish_name' => $dish['name'],
                        'price' => $dish['price'],
                        'quantity' => $quantity,
                        'subtotal' => $item_total
                    ];
                    break;
                }
            }
        }
        
        if (empty($valid_items)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum item válido encontrado']);
            exit;
        }
        
        // Carregar pedidos existentes
        $orders = loadJsonData('orders');
        
        // Gerar próximo ID
        $next_id = 1;
        if (!empty($orders)) {
            $next_id = max(array_column($orders, 'id')) + 1;
        }
        
        // Criar novo pedido
        $new_order = [
            'id' => $next_id,
            'restaurant_id' => $restaurant_id,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'items' => $valid_items,
            'total' => $total,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $orders[] = $new_order;
        
        if (saveJsonData('orders', $orders)) {
            echo json_encode([
                'success' => true,
                'order_id' => $next_id,
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

// Verificar se há um restaurante selecionado via URL (obrigatório para acesso público)
$restaurant_id = intval($_GET['restaurant_id'] ?? 0);
if ($restaurant_id > 0) {
    foreach ($restaurants as $restaurant) {
        if ($restaurant['id'] == $restaurant_id && $restaurant['is_active']) {
            $selected_restaurant = $restaurant;
            break;
        }
    }
}

// Se não há restaurante selecionado, mostrar erro
if (!$selected_restaurant) {
    echo '<div class="alert alert-danger text-center mt-5"><h4>Acesso Inválido</h4><p>Este link não é válido ou o restaurante não está ativo.</p></div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($selected_restaurant['name']) ?> - Faça seu Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/prato-rapido-theme.css" rel="stylesheet">
    <style>
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
        
        .restaurant-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .main-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .categories-sidebar {
            background: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #dee2e6;
        }
        
        .category-btn {
            width: 100%;
            margin-bottom: 10px;
            border: none;
            padding: 15px;
            border-radius: 10px;
            background: white;
            color: #1E1E1E;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .category-btn:hover, .category-btn.active {
            background: #FF6B00;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.3);
        }
        
        .dishes-grid {
            padding: 20px;
        }
        
        .dish-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .dish-card:hover {
            border-color: #FF6B00;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.2);
        }
        
        .cart-sidebar {
            background: #F5F5F5;
            padding: 20px;
            border-left: 1px solid #dee2e6;
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .cart-item {
            background: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            background: #FF6B00;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: #E55A00;
            transform: scale(1.1);
        }
        
        .loading {
            text-align: center;
            padding: 50px;
        }
    </style>
</head>
<body>
    <div class="kiosk-container">
        <!-- Cabeçalho do Restaurante -->
        <div class="restaurant-header">
            <h1 class="mb-3"><?= htmlspecialchars($selected_restaurant['name']) ?></h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($selected_restaurant['address'] ?? '') ?></p>
            <?php if (!empty($selected_restaurant['phone'])): ?>
                <p class="text-muted mb-0"><i class="fas fa-phone me-2"></i><?= htmlspecialchars($selected_restaurant['phone']) ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Card Principal -->
        <div class="main-card" data-restaurant-id="<?= $selected_restaurant['id'] ?>">
            <div class="row g-0">
                <!-- Sidebar de Categorias -->
                <div class="col-md-3 categories-sidebar">
                    <h5 class="mb-3">Categorias</h5>
                    <div id="categories-list">
                        <button class="category-btn active" data-category-id="0">
                            <i class="fas fa-th-large me-2"></i>Todos os Pratos
                        </button>
                    </div>
                </div>
                
                <!-- Área de Pratos -->
                <div class="col-md-6 dishes-grid">
                    <h5 class="mb-3">Nosso Cardápio</h5>
                    <div id="dishes-container">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Carregando cardápio...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar do Carrinho -->
                <div class="col-md-3 cart-sidebar">
                    <h5 class="mb-3">Seu Pedido</h5>
                    <div id="cart-items"></div>
                    <div id="cart-total" class="mt-3 p-3 bg-white rounded">
                        <strong>Total: R$ 0,00</strong>
                    </div>
                    <button id="checkout-btn" class="btn btn-success w-100 mt-3" disabled>
                        <i class="fas fa-shopping-cart me-2"></i>Finalizar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Checkout -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Finalizar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="checkout-form">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">Telefone</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone">
                        </div>
                        <div class="mb-3">
                            <h6>Resumo do Pedido:</h6>
                            <div id="order-summary"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirm-order-btn">
                        <i class="fas fa-check me-2"></i>Confirmar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentRestaurantId = <?= $selected_restaurant['id'] ?>;
        let cart = [];
        let dishes = [];
        let categories = [];
        
        // Carregar dados do restaurante
        function loadRestaurantData() {
            fetch('kiosk_public.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_dishes&restaurant_id=${currentRestaurantId}&category_id=0`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    dishes = data.dishes;
                    categories = Object.values(data.categories);
                    renderCategories();
                    renderDishes(dishes);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('dishes-container').innerHTML = 
                    '<div class="alert alert-danger">Erro ao carregar cardápio</div>';
            });
        }
        
        // Renderizar categorias
        function renderCategories() {
            const container = document.getElementById('categories-list');
            let html = '<button class="category-btn active" data-category-id="0"><i class="fas fa-th-large me-2"></i>Todos os Pratos</button>';
            
            categories.forEach(category => {
                html += `<button class="category-btn" data-category-id="${category.id}">
                    <i class="fas fa-utensils me-2"></i>${category.name}
                </button>`;
            });
            
            container.innerHTML = html;
            
            // Adicionar eventos
            container.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const categoryId = parseInt(this.dataset.categoryId);
                    selectCategory(categoryId, this);
                });
            });
        }
        
        // Selecionar categoria
        function selectCategory(categoryId, button) {
            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            const filteredDishes = categoryId === 0 ? dishes : dishes.filter(dish => dish.category_id == categoryId);
            renderDishes(filteredDishes);
        }
        
        // Renderizar pratos
        function renderDishes(dishesToRender) {
            const container = document.getElementById('dishes-container');
            
            if (dishesToRender.length === 0) {
                container.innerHTML = '<div class="alert alert-info">Nenhum prato disponível nesta categoria</div>';
                return;
            }
            
            let html = '';
            dishesToRender.forEach(dish => {
                const imageHtml = dish.image_url && dish.image_url !== '' ? 
                    `<img src="${dish.image_url}" alt="${dish.name}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 15px;">` : 
                    `<div style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 8px; margin-right: 15px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-utensils text-muted"></i></div>`;
                
                html += `
                    <div class="dish-card" onclick="addToCart(${dish.id})">
                        <div class="d-flex align-items-start">
                            ${imageHtml}
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${dish.name}</h6>
                                <p class="text-muted small mb-2">${dish.description || ''}</p>
                                ${dish.ingredients ? `<p class="text-muted small mb-2"><strong>Ingredientes:</strong> ${dish.ingredients}</p>` : ''}
                                <strong class="text-success">R$ ${parseFloat(dish.price).toFixed(2).replace('.', ',')}</strong>
                            </div>
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Adicionar ao carrinho
        function addToCart(dishId) {
            const dish = dishes.find(d => d.id == dishId);
            if (!dish) return;
            
            const existingItem = cart.find(item => item.dish_id == dishId);
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
            
            updateCartDisplay();
        }
        
        // Remover do carrinho
        function removeFromCart(dishId) {
            const itemIndex = cart.findIndex(item => item.dish_id == dishId);
            if (itemIndex > -1) {
                cart.splice(itemIndex, 1);
                updateCartDisplay();
            }
        }
        
        // Atualizar quantidade
        function updateQuantity(dishId, change) {
            const item = cart.find(item => item.dish_id == dishId);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeFromCart(dishId);
                } else {
                    updateCartDisplay();
                }
            }
        }
        
        // Atualizar exibição do carrinho
        function updateCartDisplay() {
            const cartContainer = document.getElementById('cart-items');
            const totalContainer = document.getElementById('cart-total');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            if (cart.length === 0) {
                cartContainer.innerHTML = '<p class="text-muted">Carrinho vazio</p>';
                totalContainer.innerHTML = '<strong>Total: R$ 0,00</strong>';
                checkoutBtn.disabled = true;
                return;
            }
            
            let html = '';
            let total = 0;
            
            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                
                html += `
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <small class="fw-bold">${item.dish_name}</small>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.dish_id})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(${item.dish_id}, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span>${item.quantity}</span>
                                <button class="quantity-btn" onclick="updateQuantity(${item.dish_id}, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small class="text-success fw-bold">R$ ${subtotal.toFixed(2).replace('.', ',')}</small>
                        </div>
                    </div>
                `;
            });
            
            cartContainer.innerHTML = html;
            totalContainer.innerHTML = `<strong>Total: R$ ${total.toFixed(2).replace('.', ',')}</strong>`;
            checkoutBtn.disabled = false;
        }
        
        // Finalizar pedido
        document.getElementById('checkout-btn').addEventListener('click', function() {
            if (cart.length === 0) return;
            
            // Atualizar resumo do pedido
            const summaryContainer = document.getElementById('order-summary');
            let html = '';
            let total = 0;
            
            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                html += `<div class="d-flex justify-content-between">
                    <span>${item.quantity}x ${item.dish_name}</span>
                    <span>R$ ${subtotal.toFixed(2).replace('.', ',')}</span>
                </div>`;
            });
            
            html += `<hr><div class="d-flex justify-content-between fw-bold">
                <span>Total:</span>
                <span>R$ ${total.toFixed(2).replace('.', ',')}</span>
            </div>`;
            
            summaryContainer.innerHTML = html;
            
            // Mostrar modal
            new bootstrap.Modal(document.getElementById('checkoutModal')).show();
        });
        
        // Confirmar pedido
        document.getElementById('confirm-order-btn').addEventListener('click', function() {
            const form = document.getElementById('checkout-form');
            const formData = new FormData(form);
            
            if (!formData.get('customer_name').trim()) {
                alert('Por favor, informe seu nome');
                return;
            }
            
            const orderData = {
                action: 'create_order',
                restaurant_id: currentRestaurantId,
                customer_name: formData.get('customer_name'),
                customer_phone: formData.get('customer_phone'),
                items: JSON.stringify(cart)
            };
            
            fetch('kiosk_public.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: Object.keys(orderData).map(key => key + '=' + encodeURIComponent(orderData[key])).join('&')
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Pedido #${data.order_id} criado com sucesso!\nTotal: R$ ${data.total.toFixed(2).replace('.', ',')}\n\nAguarde a confirmação do restaurante.`);
                    
                    // Limpar carrinho e fechar modal
                    cart = [];
                    updateCartDisplay();
                    bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
                    form.reset();
                } else {
                    alert('Erro ao criar pedido: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar pedido');
            });
        });
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadRestaurantData();
        });
    </script>
</body>
</html>