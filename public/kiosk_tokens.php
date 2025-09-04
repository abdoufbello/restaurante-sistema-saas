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

// Função para gerar token único
function generateToken() {
    return bin2hex(random_bytes(32));
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_token') {
        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if ($restaurant_id <= 0 || empty($name)) {
            $_SESSION['error'] = 'Dados inválidos';
            header('Location: kiosk_tokens.php');
            exit;
        }
        
        $tokens = loadJsonData('kiosk_tokens');
        
        // Gerar novo ID
        $new_id = 1;
        foreach ($tokens as $token) {
            if ($token['id'] >= $new_id) {
                $new_id = $token['id'] + 1;
            }
        }
        
        // Criar novo token
        $new_token = [
            'id' => $new_id,
            'restaurant_id' => $restaurant_id,
            'name' => $name,
            'token' => generateToken(),
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'last_used' => null,
            'usage_count' => 0
        ];
        
        $tokens[] = $new_token;
        
        if (saveJsonData('kiosk_tokens', $tokens)) {
            $_SESSION['success'] = 'Token gerado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao gerar token';
        }
        
        header('Location: kiosk_tokens.php');
        exit;
    }
    
    if ($action === 'toggle_token') {
        $token_id = intval($_POST['token_id'] ?? 0);
        
        $tokens = loadJsonData('kiosk_tokens');
        
        foreach ($tokens as &$token) {
            if ($token['id'] == $token_id) {
                $token['is_active'] = !$token['is_active'];
                break;
            }
        }
        
        if (saveJsonData('kiosk_tokens', $tokens)) {
            $_SESSION['success'] = 'Status do token atualizado!';
        } else {
            $_SESSION['error'] = 'Erro ao atualizar token';
        }
        
        header('Location: kiosk_tokens.php');
        exit;
    }
    
    if ($action === 'delete_token') {
        $token_id = intval($_POST['token_id'] ?? 0);
        
        $tokens = loadJsonData('kiosk_tokens');
        $tokens = array_filter($tokens, function($token) use ($token_id) {
            return $token['id'] != $token_id;
        });
        
        if (saveJsonData('kiosk_tokens', array_values($tokens))) {
            $_SESSION['success'] = 'Token excluído com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao excluir token';
        }
        
        header('Location: kiosk_tokens.php');
        exit;
    }
}

// Carregar dados
$restaurants = loadJsonData('restaurants');
$tokens = loadJsonData('kiosk_tokens');

// Filtrar tokens por restaurante se não for admin
if ($_SESSION['role'] !== 'admin') {
    $user_restaurant_id = $_SESSION['restaurant_id'] ?? 0;
    $tokens = array_filter($tokens, function($token) use ($user_restaurant_id) {
        return $token['restaurant_id'] == $user_restaurant_id;
    });
    $restaurants = array_filter($restaurants, function($restaurant) use ($user_restaurant_id) {
        return $restaurant['id'] == $user_restaurant_id;
    });
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tokens de Kiosk - Prato Rápido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff6b00;
            --secondary-yellow: #ffc700;
        }
        body {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .nav-link {
            color: white !important;
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        .main-content {
            padding: 20px;
        }
        .token-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .token-header {
            background: linear-gradient(45deg, var(--primary-orange), var(--secondary-yellow));
            color: white;
            padding: 15px 20px;
        }
        .token-url {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            border: 1px solid #dee2e6;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <h4 class="text-white">Prato Rápido</h4>
                    <p class="text-white-50 mb-0"><?= htmlspecialchars($_SESSION['restaurant_name'] ?? 'Sistema') ?></p>
                    <small class="text-white-50"><?= htmlspecialchars($_SESSION['employee_name'] ?? 'Usuário') ?></small>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-tags me-2"></i> Categorias
                    </a>
                    <a class="nav-link" href="dishes.php">
                        <i class="fas fa-utensils me-2"></i> Pratos
                    </a>
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-cart me-2"></i> Pedidos
                    </a>
                    <a class="nav-link active" href="kiosk_tokens.php">
                        <i class="fas fa-link me-2"></i> Links do Kiosk
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i> Relatórios
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="simple_auth.php?action=logout">
                        <i class="fas fa-sign-out-alt me-2"></i> Sair
                    </a>
                </nav>
            </div>
            
            <!-- Conteúdo Principal -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Links do Kiosk</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTokenModal">
                        <i class="fas fa-plus me-2"></i> Gerar Novo Link
                    </button>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="row">
                    <?php if (empty($tokens)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-link fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">Nenhum link criado</h4>
                                <p class="text-muted">Crie seu primeiro link para compartilhar o cardápio com seus clientes</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTokenModal">
                                    <i class="fas fa-plus me-2"></i> Criar Primeiro Link
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tokens as $token): ?>
                            <?php 
                            $restaurant_name = 'Restaurante não encontrado';
                            foreach ($restaurants as $restaurant) {
                                if ($restaurant['id'] == $token['restaurant_id']) {
                                    $restaurant_name = $restaurant['name'];
                                    break;
                                }
                            }
                            $public_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/kiosk_public.php?token=' . $token['token'];
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card token-card">
                                    <div class="token-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1"><?= htmlspecialchars($token['name']) ?></h5>
                                                <small><?= htmlspecialchars($restaurant_name) ?></small>
                                            </div>
                                            <span class="status-badge <?= $token['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                                <?= $token['is_active'] ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label small text-muted">Link Público:</label>
                                            <div class="token-url">
                                                <?= $public_url ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Criado em</small><br>
                                                <strong><?= date('d/m/Y', strtotime($token['created_at'])) ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Acessos</small><br>
                                                <strong><?= $token['usage_count'] ?></strong>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-primary btn-sm flex-fill" onclick="copyToClipboard('<?= $public_url ?>')">
                                                <i class="fas fa-copy me-1"></i> Copiar
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" onclick="openPreview('<?= $public_url ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="toggle_token">
                                                            <input type="hidden" name="token_id" value="<?= $token['id'] ?>">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fas fa-<?= $token['is_active'] ? 'pause' : 'play' ?> me-2"></i>
                                                                <?= $token['is_active'] ? 'Desativar' : 'Ativar' ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este token?')">
                                                            <input type="hidden" name="action" value="delete_token">
                                                            <input type="hidden" name="token_id" value="<?= $token['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-trash me-2"></i> Excluir
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Novo Token -->
    <div class="modal fade" id="newTokenModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gerar Novo Link do Kiosk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="generate_token">
                        
                        <div class="mb-3">
                            <label for="restaurant_id" class="form-label">Restaurante *</label>
                            <select class="form-select" id="restaurant_id" name="restaurant_id" required>
                                <option value="">Selecione um restaurante</option>
                                <?php foreach ($restaurants as $restaurant): ?>
                                    <option value="<?= $restaurant['id'] ?>"><?= htmlspecialchars($restaurant['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome do Link *</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                   placeholder="Ex: Cardápio Principal, Mesa VIP, etc.">
                            <div class="form-text">Nome para identificar este link internamente</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Como funciona:</strong><br>
                            • O link gerado permitirá que clientes vejam apenas o cardápio do restaurante selecionado<br>
                            • Clientes poderão fazer pedidos sem precisar fazer login<br>
                            • Você pode desativar ou excluir o link a qualquer momento
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Gerar Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Criar toast de sucesso
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed';
                toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check me-2"></i> Link copiado para a área de transferência!
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                // Remover toast após ser ocultado
                toast.addEventListener('hidden.bs.toast', function() {
                    document.body.removeChild(toast);
                });
            }).catch(function(err) {
                alert('Erro ao copiar link: ' + err);
            });
        }
        
        function openPreview(url) {
            window.open(url, '_blank');
        }
    </script>
</body>
</html>