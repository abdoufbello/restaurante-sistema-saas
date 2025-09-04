<?php
session_start();

// Função para carregar dados JSON
function loadJsonData($filename) {
    $path = '../writable/data/' . $filename;
    if (file_exists($path)) {
        return json_decode(file_get_contents($path), true);
    }
    return [];
}

// Função para salvar dados JSON
function saveJsonData($filename, $data) {
    $path = '../writable/data/' . $filename;
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

// Teste de autenticação
function testAuthentication() {
    $employees = loadJsonData('employees.json');
    $testUser = null;
    
    foreach ($employees as $employee) {
        if ($employee['role'] === 'admin') {
            $testUser = $employee;
            break;
        }
    }
    
    if ($testUser) {
        $_SESSION['user_id'] = $testUser['id'];
        $_SESSION['restaurant_id'] = $testUser['restaurant_id'];
        $_SESSION['username'] = $testUser['username'];
        $_SESSION['role'] = $testUser['role'];
        return true;
    }
    return false;
}

// Teste de CRUD para categorias
function testCategoryCRUD() {
    $categories = loadJsonData('categories.json');
    $originalCount = count($categories);
    
    // Teste CREATE
    $newCategory = [
        'id' => time(),
        'restaurant_id' => $_SESSION['restaurant_id'],
        'name' => 'Categoria Teste',
        'description' => 'Categoria criada para teste',
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $categories[] = $newCategory;
    saveJsonData('categories.json', $categories);
    
    // Teste READ
    $categories = loadJsonData('categories.json');
    $found = false;
    foreach ($categories as $category) {
        if ($category['name'] === 'Categoria Teste') {
            $found = true;
            break;
        }
    }
    
    // Teste DELETE (limpar teste)
    $categories = array_filter($categories, function($cat) {
        return $cat['name'] !== 'Categoria Teste';
    });
    saveJsonData('categories.json', $categories);
    
    return $found && count($categories) === $originalCount;
}

// Teste de CRUD para pratos
function testDishCRUD() {
    $dishes = loadJsonData('dishes.json');
    $originalCount = count($dishes);
    
    // Teste CREATE
    $newDish = [
        'id' => time(),
        'restaurant_id' => $_SESSION['restaurant_id'],
        'category_id' => 1,
        'name' => 'Prato Teste',
        'description' => 'Prato criado para teste',
        'price' => 25.90,
        'image' => '',
        'available' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $dishes[] = $newDish;
    saveJsonData('dishes.json', $dishes);
    
    // Teste READ
    $dishes = loadJsonData('dishes.json');
    $found = false;
    foreach ($dishes as $dish) {
        if ($dish['name'] === 'Prato Teste') {
            $found = true;
            break;
        }
    }
    
    // Teste DELETE (limpar teste)
    $dishes = array_filter($dishes, function($dish) {
        return $dish['name'] !== 'Prato Teste';
    });
    saveJsonData('dishes.json', $dishes);
    
    return $found && count($dishes) === $originalCount;
}

// Teste de criação de pedido
function testOrderCreation() {
    $orders = loadJsonData('orders.json');
    $originalCount = count($orders);
    
    $newOrder = [
        'id' => time(),
        'restaurant_id' => $_SESSION['restaurant_id'],
        'customer_name' => 'Cliente Teste',
        'customer_phone' => '(11) 99999-9999',
        'items' => [
            [
                'dish_id' => 1,
                'dish_name' => 'Prato Teste',
                'quantity' => 2,
                'price' => 25.90
            ]
        ],
        'total' => 51.80,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $orders[] = $newOrder;
    saveJsonData('orders.json', $orders);
    
    // Verificar se foi criado
    $orders = loadJsonData('orders.json');
    $found = false;
    foreach ($orders as $order) {
        if ($order['customer_name'] === 'Cliente Teste') {
            $found = true;
            break;
        }
    }
    
    // Limpar teste
    $orders = array_filter($orders, function($order) {
        return $order['customer_name'] !== 'Cliente Teste';
    });
    saveJsonData('orders.json', $orders);
    
    return $found && count($orders) === $originalCount;
}

// Executar testes
$results = [];

echo "<h1>Teste Completo de Funcionalidades - SAAS</h1>";

// Teste 1: Autenticação
echo "<h2>1. Teste de Autenticação</h2>";
$authResult = testAuthentication();
$results['auth'] = $authResult;
echo $authResult ? "<p>✅ Autenticação funcionando</p>" : "<p>❌ Falha na autenticação</p>";

if ($authResult) {
    echo "<p>Usuário logado: {$_SESSION['username']} (Restaurante ID: {$_SESSION['restaurant_id']})</p>";
    
    // Teste 2: CRUD Categorias
    echo "<h2>2. Teste CRUD - Categorias</h2>";
    $categoryResult = testCategoryCRUD();
    $results['categories'] = $categoryResult;
    echo $categoryResult ? "<p>✅ CRUD de categorias funcionando</p>" : "<p>❌ Falha no CRUD de categorias</p>";
    
    // Teste 3: CRUD Pratos
    echo "<h2>3. Teste CRUD - Pratos</h2>";
    $dishResult = testDishCRUD();
    $results['dishes'] = $dishResult;
    echo $dishResult ? "<p>✅ CRUD de pratos funcionando</p>" : "<p>❌ Falha no CRUD de pratos</p>";
    
    // Teste 4: Criação de Pedidos
    echo "<h2>4. Teste de Criação de Pedidos</h2>";
    $orderResult = testOrderCreation();
    $results['orders'] = $orderResult;
    echo $orderResult ? "<p>✅ Criação de pedidos funcionando</p>" : "<p>❌ Falha na criação de pedidos</p>";
}

// Resumo dos testes
echo "<h2>Resumo dos Testes</h2>";
$totalTests = count($results);
$passedTests = array_sum($results);
echo "<p><strong>Testes executados: {$totalTests}</strong></p>";
echo "<p><strong>Testes aprovados: {$passedTests}</strong></p>";
echo "<p><strong>Taxa de sucesso: " . round(($passedTests / $totalTests) * 100, 2) . "%</strong></p>";

if ($passedTests === $totalTests) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Todos os testes passaram! Sistema funcionando corretamente.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Alguns testes falharam. Verificar funcionalidades.</p>";
}

echo "<h2>Links para Teste Manual</h2>";
echo "<ul>";
echo "<li><a href='dashboard.php' target='_blank'>Dashboard Principal</a></li>";
echo "<li><a href='categories.php' target='_blank'>Gerenciar Categorias</a></li>";
echo "<li><a href='dishes.php' target='_blank'>Gerenciar Pratos</a></li>";
echo "<li><a href='orders.php' target='_blank'>Gerenciar Pedidos</a></li>";
echo "<li><a href='kiosk_public.php?restaurant_id=1' target='_blank'>Kiosk Público</a></li>";
echo "</ul>";
?>