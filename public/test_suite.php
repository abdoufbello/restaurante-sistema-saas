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

// Função para executar teste
function runTest($testName, $testFunction) {
    try {
        $result = $testFunction();
        return [
            'name' => $testName,
            'status' => $result ? 'PASS' : 'FAIL',
            'message' => $result ? 'Teste passou' : 'Teste falhou'
        ];
    } catch (Exception $e) {
        return [
            'name' => $testName,
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
    }
}

// Testes
$tests = [];

// Teste 1: Verificar se arquivos de dados existem
$tests[] = runTest('Arquivos de Dados', function() {
    $files = ['restaurants.json', 'employees.json', 'categories.json', 'dishes.json', 'orders.json'];
    foreach ($files as $file) {
        if (!file_exists(DATA_DIR . $file)) {
            throw new Exception("Arquivo {$file} não encontrado");
        }
    }
    return true;
});

// Teste 2: Verificar estrutura dos restaurantes
$tests[] = runTest('Estrutura Restaurantes', function() {
    $restaurants = loadJsonData('restaurants');
    if (count($restaurants) < 5) {
        throw new Exception('Menos de 5 restaurantes encontrados');
    }
    
    $requiredFields = ['id', 'name', 'cnpj', 'subscription_plan'];
    foreach ($restaurants as $restaurant) {
        foreach ($requiredFields as $field) {
            if (!isset($restaurant[$field])) {
                throw new Exception("Campo {$field} ausente no restaurante");
            }
        }
    }
    return true;
});

// Teste 3: Verificar funcionários ativos
$tests[] = runTest('Funcionários Ativos', function() {
    $employees = loadJsonData('employees');
    $activeCount = 0;
    $adminCount = 0;
    
    foreach ($employees as $employee) {
        if ($employee['is_active'] == 1) {
            $activeCount++;
        }
        if ($employee['role'] === 'admin') {
            $adminCount++;
        }
    }
    
    if ($activeCount < 10) {
        throw new Exception('Menos de 10 funcionários ativos');
    }
    if ($adminCount < 5) {
        throw new Exception('Menos de 5 administradores');
    }
    
    return true;
});

// Teste 4: Verificar categorias por restaurante
$tests[] = runTest('Categorias por Restaurante', function() {
    $categories = loadJsonData('categories');
    $restaurants = loadJsonData('restaurants');
    
    foreach ($restaurants as $restaurant) {
        $restaurantCategories = array_filter($categories, function($cat) use ($restaurant) {
            return $cat['restaurant_id'] == $restaurant['id'] && $cat['is_active'];
        });
        
        if (count($restaurantCategories) < 2) {
            throw new Exception("Restaurante {$restaurant['name']} tem menos de 2 categorias");
        }
    }
    
    return true;
});

// Teste 5: Verificar pratos disponíveis
$tests[] = runTest('Pratos Disponíveis', function() {
    $dishes = loadJsonData('dishes');
    $availableDishes = array_filter($dishes, function($dish) {
        return $dish['is_available'] == 1;
    });
    
    if (count($availableDishes) < 15) {
        throw new Exception('Menos de 15 pratos disponíveis');
    }
    
    // Verificar se todos os pratos têm preço válido
    foreach ($availableDishes as $dish) {
        if (!isset($dish['price']) || $dish['price'] <= 0) {
            throw new Exception("Prato {$dish['name']} tem preço inválido");
        }
    }
    
    return true;
});

// Teste 6: Verificar pedidos de exemplo
$tests[] = runTest('Pedidos de Exemplo', function() {
    $orders = loadJsonData('orders');
    
    if (count($orders) < 6) {
        throw new Exception('Menos de 6 pedidos de exemplo');
    }
    
    $statuses = ['pending', 'preparing', 'ready', 'delivered'];
    $foundStatuses = [];
    
    foreach ($orders as $order) {
        if (!in_array($order['status'], $statuses)) {
            throw new Exception("Status de pedido inválido: {$order['status']}");
        }
        $foundStatuses[] = $order['status'];
    }
    
    // Verificar se temos pelo menos 3 status diferentes
    if (count(array_unique($foundStatuses)) < 3) {
        throw new Exception('Menos de 3 status diferentes nos pedidos');
    }
    
    return true;
});

// Teste 7: Verificar planos de assinatura
$tests[] = runTest('Planos de Assinatura', function() {
    $restaurants = loadJsonData('restaurants');
    $validPlans = ['trial', 'starter', 'professional', 'enterprise'];
    $foundPlans = [];
    
    foreach ($restaurants as $restaurant) {
        if (!in_array($restaurant['subscription_plan'], $validPlans)) {
            throw new Exception("Plano inválido: {$restaurant['subscription_plan']}");
        }
        $foundPlans[] = $restaurant['subscription_plan'];
    }
    
    // Verificar se temos pelo menos 3 planos diferentes
    if (count(array_unique($foundPlans)) < 3) {
        throw new Exception('Menos de 3 planos diferentes');
    }
    
    return true;
});

// Teste 8: Verificar credenciais de admin
$tests[] = runTest('Credenciais Admin', function() {
    $employees = loadJsonData('employees');
    $restaurants = loadJsonData('restaurants');
    
    $expectedAdmins = [
        ['cnpj' => '12.345.678/0001-90', 'username' => 'admin_pizza'],
        ['cnpj' => '98.765.432/0001-10', 'username' => 'admin_burger'],
        ['cnpj' => '11.222.333/0001-44', 'username' => 'admin_sushi'],
        ['cnpj' => '44.555.666/0001-77', 'username' => 'admin_cafe'],
        ['cnpj' => '77.888.999/0001-33', 'username' => 'admin_taco']
    ];
    
    foreach ($expectedAdmins as $expected) {
        $restaurant = null;
        foreach ($restaurants as $rest) {
            if ($rest['cnpj'] === $expected['cnpj']) {
                $restaurant = $rest;
                break;
            }
        }
        
        if (!$restaurant) {
            throw new Exception("Restaurante com CNPJ {$expected['cnpj']} não encontrado");
        }
        
        $admin = null;
        foreach ($employees as $emp) {
            if ($emp['restaurant_id'] == $restaurant['id'] && 
                $emp['username'] === $expected['username'] && 
                $emp['role'] === 'admin') {
                $admin = $emp;
                break;
            }
        }
        
        if (!$admin) {
            throw new Exception("Admin {$expected['username']} não encontrado");
        }
        
        if ($admin['password'] !== '123456') {
            throw new Exception("Senha do admin {$expected['username']} não é 123456");
        }
    }
    
    return true;
});

// Calcular estatísticas
$totalTests = count($tests);
$passedTests = count(array_filter($tests, function($test) { return $test['status'] === 'PASS'; }));
$failedTests = count(array_filter($tests, function($test) { return $test['status'] === 'FAIL'; }));
$errorTests = count(array_filter($tests, function($test) { return $test['status'] === 'ERROR'; }));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suite de Testes - Sistema SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-pass { color: #28a745; }
        .test-fail { color: #dc3545; }
        .test-error { color: #ffc107; }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card summary-card mb-4">
                    <div class="card-body text-center">
                        <h1 class="mb-3">
                            <i class="fas fa-vial me-3"></i>
                            Suite de Testes Automatizados
                        </h1>
                        <div class="row">
                            <div class="col-md-3">
                                <h3><?= $totalTests ?></h3>
                                <p class="mb-0">Total de Testes</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-success"><?= $passedTests ?></h3>
                                <p class="mb-0">Passou</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-danger"><?= $failedTests ?></h3>
                                <p class="mb-0">Falhou</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-warning"><?= $errorTests ?></h3>
                                <p class="mb-0">Erro</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?= ($passedTests / $totalTests) * 100 ?>%"></div>
                                <div class="progress-bar bg-danger" style="width: <?= ($failedTests / $totalTests) * 100 ?>%"></div>
                                <div class="progress-bar bg-warning" style="width: <?= ($errorTests / $totalTests) * 100 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Resultados Detalhados</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Teste</th>
                                        <th>Status</th>
                                        <th>Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tests as $test): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($test['name']) ?></td>
                                            <td>
                                                <?php if ($test['status'] === 'PASS'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>PASSOU
                                                    </span>
                                                <?php elseif ($test['status'] === 'FAIL'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>FALHOU
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation me-1"></i>ERRO
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="<?= $test['status'] === 'PASS' ? 'test-pass' : ($test['status'] === 'FAIL' ? 'test-fail' : 'test-error') ?>">
                                                <?= htmlspecialchars($test['message']) ?>
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
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ações de Teste</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="simple_auth.php" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Testar Login
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="register.php" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-2"></i>Testar Registro
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="kiosk.php" class="btn btn-info w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Testar Pedidos
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button class="btn btn-warning w-100" onclick="location.reload()">
                                    <i class="fas fa-redo me-2"></i>Executar Novamente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Informações</h6>
                    <p class="mb-0">
                        Esta suite de testes valida automaticamente a integridade dos dados e funcionalidades básicas do sistema.
                        Para testes manuais completos, consulte o arquivo <strong>CREDENCIAIS_TESTE.md</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>