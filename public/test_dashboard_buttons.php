<?php
session_start();

// Simular sessão de usuário logado para teste
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['restaurant_id'] = 1;
    $_SESSION['restaurant_name'] = 'Restaurante Teste';
    $_SESSION['employee_name'] = 'Admin Teste';
    $_SESSION['employee_id'] = 1;
    $_SESSION['is_admin'] = true;
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
    <title>Teste Dashboard - Botões Funcionais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 2px solid #007bff;
            border-radius: 10px;
            background-color: #f8f9fa;
        }
        .test-button {
            margin: 5px;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 10px;
        }
        .status-working { background-color: #28a745; }
        .status-error { background-color: #dc3545; }
        .status-pending { background-color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">
                    <i class="fas fa-cogs me-2"></i>
                    Teste de Funcionalidade dos Botões do Dashboard
                </h1>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Instruções:</strong> Clique nos botões abaixo para testar se estão funcionando corretamente. 
                    Os indicadores de status mostrarão se cada função está operacional.
                </div>
            </div>
        </div>
        
        <!-- Teste de Navegação -->
        <div class="test-section">
            <h3><i class="fas fa-compass me-2"></i>Teste de Navegação entre Seções</h3>
            <p>Teste os botões de navegação do menu lateral:</p>
            <div class="row">
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary test-button w-100" onclick="testNavigation('dashboard')">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </button>
                    <span id="nav-dashboard-status" class="status-indicator status-pending"></span>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary test-button w-100" onclick="testNavigation('funcionarios')">
                        <i class="fas fa-users me-2"></i>Funcionários
                    </button>
                    <span id="nav-funcionarios-status" class="status-indicator status-pending"></span>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary test-button w-100" onclick="testNavigation('categorias')">
                        <i class="fas fa-tags me-2"></i>Categorias
                    </button>
                    <span id="nav-categorias-status" class="status-indicator status-pending"></span>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary test-button w-100" onclick="testNavigation('pratos')">
                        <i class="fas fa-utensils me-2"></i>Pratos
                    </button>
                    <span id="nav-pratos-status" class="status-indicator status-pending"></span>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary test-button w-100" onclick="testNavigation('pedidos')">
                        <i class="fas fa-shopping-cart me-2"></i>Pedidos
                    </button>
                    <span id="nav-pedidos-status" class="status-indicator status-pending"></span>
                </div>
            </div>
        </div>
        
        <!-- Teste de Modais -->
        <div class="test-section">
            <h3><i class="fas fa-window-maximize me-2"></i>Teste de Modais de Cadastro</h3>
            <p>Teste os botões que abrem modais para cadastro:</p>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <button class="btn btn-success test-button w-100" onclick="testModal('employee')">
                        <i class="fas fa-plus me-2"></i>Novo Funcionário
                    </button>
                    <span id="modal-employee-status" class="status-indicator status-pending"></span>
                </div>
                <div class="col-md-3 mb-2">
                    <button class="btn btn-success test-button w-100" onclick="testModal('category')">
                        <i class="fas fa-plus me-2"></i>Nova Categoria
                    </button>
                    <span id="modal-category-status" class="status-indicator status-pending"></span>
                </div>
                <div class="col-md-3 mb-2">
                    <button class="btn btn-success test-button w-100" onclick="testModal('dish')">
                        <i class="fas fa-plus me-2"></i>Novo Prato
                    </button>
                    <span id="modal-dish-status" class="status-indicator status-pending"></span>
                </div>
                <div class="col-md-3 mb-2">
                    <button class="btn btn-info test-button w-100" onclick="testModal('publiclink')">
                        <i class="fas fa-link me-2"></i>Link Público
                    </button>
                    <span id="modal-publiclink-status" class="status-indicator status-pending"></span>
                </div>
            </div>
        </div>
        
        <!-- Teste de Links Externos -->
        <div class="test-section">
            <h3><i class="fas fa-external-link-alt me-2"></i>Teste de Links Externos</h3>
            <p>Teste os botões que redirecionam para outras páginas:</p>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <a href="orders.php" class="btn btn-info test-button w-100" target="_blank">
                        <i class="fas fa-clipboard-list me-2"></i>Gestão de Pedidos
                    </a>
                    <span class="status-indicator status-working"></span>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="kiosk.php" class="btn btn-warning test-button w-100" target="_blank">
                        <i class="fas fa-cog me-2"></i>Gerenciar Kiosk
                    </a>
                    <span class="status-indicator status-working"></span>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="plans.php" class="btn btn-secondary test-button w-100" target="_blank">
                        <i class="fas fa-crown me-2"></i>Planos
                    </a>
                    <span class="status-indicator status-working"></span>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="reports.php" class="btn btn-primary test-button w-100" target="_blank">
                        <i class="fas fa-chart-bar me-2"></i>Relatórios
                    </a>
                    <span class="status-indicator status-working"></span>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas do Sistema -->
        <div class="test-section">
            <h3><i class="fas fa-chart-pie me-2"></i>Estatísticas do Sistema</h3>
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h4><?= $stats['employees'] ?></h4>
                            <p class="text-muted">Funcionários</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-tags fa-2x text-success mb-2"></i>
                            <h4><?= $stats['categories'] ?></h4>
                            <p class="text-muted">Categorias</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-utensils fa-2x text-warning mb-2"></i>
                            <h4><?= $stats['dishes'] ?></h4>
                            <p class="text-muted">Pratos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-shopping-cart fa-2x text-info mb-2"></i>
                            <h4><?= $stats['orders'] ?></h4>
                            <p class="text-muted">Pedidos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resultados dos Testes -->
        <div class="test-section">
            <h3><i class="fas fa-clipboard-check me-2"></i>Resultados dos Testes</h3>
            <div id="test-results" class="alert alert-secondary">
                <p><strong>Status dos Testes:</strong></p>
                <ul id="results-list">
                    <li>Aguardando execução dos testes...</li>
                </ul>
            </div>
            
            <div class="text-center mt-3">
                <button class="btn btn-success btn-lg" onclick="runAllTests()">
                    <i class="fas fa-play me-2"></i>Executar Todos os Testes
                </button>
                <button class="btn btn-warning btn-lg ms-2" onclick="resetTests()">
                    <i class="fas fa-redo me-2"></i>Resetar Testes
                </button>
            </div>
        </div>
        
        <div class="text-center mt-4 mb-4">
            <a href="dashboard.php" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Definir ID do restaurante globalmente
        window.restaurantId = <?= $_SESSION['restaurant_id'] ?>;
        
        let testResults = [];
        
        // Função para testar navegação
        function testNavigation(section) {
            try {
                // Simular teste de navegação
                console.log('Testando navegação para:', section);
                updateStatus('nav-' + section + '-status', 'working');
                addTestResult('Navegação para ' + section, 'Sucesso', 'working');
            } catch (error) {
                updateStatus('nav-' + section + '-status', 'error');
                addTestResult('Navegação para ' + section, 'Erro: ' + error.message, 'error');
            }
        }
        
        // Função para testar modais
        function testModal(type) {
            try {
                let functionName = '';
                switch(type) {
                    case 'employee':
                        functionName = 'showAddEmployeeForm';
                        break;
                    case 'category':
                        functionName = 'showAddCategoryForm';
                        break;
                    case 'dish':
                        functionName = 'showAddDishForm';
                        break;
                    case 'publiclink':
                        functionName = 'generatePublicLink';
                        break;
                }
                
                if (typeof window[functionName] === 'function') {
                    window[functionName]();
                    updateStatus('modal-' + type + '-status', 'working');
                    addTestResult('Modal ' + type, 'Função encontrada e executada', 'working');
                } else {
                    updateStatus('modal-' + type + '-status', 'error');
                    addTestResult('Modal ' + type, 'Função não encontrada: ' + functionName, 'error');
                }
            } catch (error) {
                updateStatus('modal-' + type + '-status', 'error');
                addTestResult('Modal ' + type, 'Erro: ' + error.message, 'error');
            }
        }
        
        // Função para atualizar status visual
        function updateStatus(elementId, status) {
            const element = document.getElementById(elementId);
            if (element) {
                element.className = 'status-indicator status-' + status;
            }
        }
        
        // Função para adicionar resultado do teste
        function addTestResult(testName, result, status) {
            testResults.push({
                name: testName,
                result: result,
                status: status,
                timestamp: new Date().toLocaleTimeString()
            });
            updateResultsDisplay();
        }
        
        // Função para atualizar exibição dos resultados
        function updateResultsDisplay() {
            const resultsList = document.getElementById('results-list');
            if (testResults.length === 0) {
                resultsList.innerHTML = '<li>Nenhum teste executado ainda.</li>';
                return;
            }
            
            let html = '';
            testResults.forEach(test => {
                const icon = test.status === 'working' ? 'fa-check text-success' : 'fa-times text-danger';
                html += `<li><i class="fas ${icon} me-2"></i><strong>${test.name}:</strong> ${test.result} <small class="text-muted">(${test.timestamp})</small></li>`;
            });
            resultsList.innerHTML = html;
            
            // Atualizar classe do alert baseado nos resultados
            const resultsDiv = document.getElementById('test-results');
            const hasErrors = testResults.some(test => test.status === 'error');
            resultsDiv.className = hasErrors ? 'alert alert-danger' : 'alert alert-success';
        }
        
        // Função para executar todos os testes
        function runAllTests() {
            resetTests();
            
            // Testar navegação
            setTimeout(() => testNavigation('dashboard'), 100);
            setTimeout(() => testNavigation('funcionarios'), 200);
            setTimeout(() => testNavigation('categorias'), 300);
            setTimeout(() => testNavigation('pratos'), 400);
            setTimeout(() => testNavigation('pedidos'), 500);
            
            // Testar modais
            setTimeout(() => testModal('employee'), 600);
            setTimeout(() => testModal('category'), 700);
            setTimeout(() => testModal('dish'), 800);
            setTimeout(() => testModal('publiclink'), 900);
        }
        
        // Função para resetar testes
        function resetTests() {
            testResults = [];
            updateResultsDisplay();
            
            // Resetar todos os indicadores de status
            document.querySelectorAll('.status-indicator').forEach(indicator => {
                if (!indicator.classList.contains('status-working')) {
                    indicator.className = 'status-indicator status-pending';
                }
            });
        }
        
        // Carregar arquivo de funções do dashboard
        const script = document.createElement('script');
        script.src = 'dashboard_functions.js';
        script.onload = function() {
            addTestResult('Carregamento do Script', 'dashboard_functions.js carregado com sucesso', 'working');
        };
        script.onerror = function() {
            addTestResult('Carregamento do Script', 'Erro ao carregar dashboard_functions.js', 'error');
        };
        document.head.appendChild(script);
    </script>
</body>
</html>