<?php
session_start();

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: simple_auth.php');
    exit;
}

// Incluir helper de planos
require_once 'plan_helper.php';

// Verificar funcionalidades disponíveis
$has_image_upload = hasFeatureAccess($_SESSION['restaurant_id'], 'image_upload');
$has_prep_time = hasFeatureAccess($_SESSION['restaurant_id'], 'prep_time');
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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_dish') {
        $dishes = loadJsonData('dishes');
        $categories = loadJsonData('categories');
        
        // Validar dados
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $ingredients = trim($_POST['ingredients'] ?? '');
        $prep_time = intval($_POST['prep_time'] ?? 0);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Processar upload de imagem
        $image_url = '';
        if (isset($_FILES['dish_image']) && $_FILES['dish_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/dishes/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['dish_image']['type'];
            $file_size = $_FILES['dish_image']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error = 'Tipo de arquivo não permitido. Use apenas JPEG, PNG, GIF ou WebP.';
            } elseif ($file_size > $max_size) {
                $error = 'Arquivo muito grande. Tamanho máximo: 5MB.';
            } else {
                $file_extension = pathinfo($_FILES['dish_image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('dish_') . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['dish_image']['tmp_name'], $file_path)) {
                    $image_url = $file_path;
                } else {
                    $error = 'Erro ao fazer upload da imagem.';
                }
            }
        }
        
        if (empty($name) || $price <= 0 || $category_id <= 0) {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        } elseif (!isset($error)) {
            // Verificar se a categoria existe e pertence ao restaurante
            $category_exists = false;
            foreach ($categories as $cat) {
                if ($cat['id'] == $category_id && $cat['restaurant_id'] == $_SESSION['restaurant_id']) {
                    $category_exists = true;
                    break;
                }
            }
            
            if (!$category_exists) {
                $error = 'Categoria inválida.';
            } else {
                // Gerar novo ID
                $new_id = 1;
                foreach ($dishes as $dish) {
                    if ($dish['id'] >= $new_id) {
                        $new_id = $dish['id'] + 1;
                    }
                }
                
                // Adicionar novo prato
                $new_dish = [
                    'id' => $new_id,
                    'restaurant_id' => $_SESSION['restaurant_id'],
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'category_id' => $category_id,
                    'ingredients' => $ingredients,
                    'prep_time' => $prep_time,
                    'image_url' => $image_url,
                    'is_available' => $is_available,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $dishes[] = $new_dish;
                
                if (saveJsonData('dishes', $dishes)) {
                    $success = 'Prato adicionado com sucesso!';
                } else {
                    $error = 'Erro ao salvar o prato.';
                }
            }
        }
    }
    
    if ($action === 'toggle_availability') {
        $dishes = loadJsonData('dishes');
        $dish_id = intval($_POST['dish_id'] ?? 0);
        
        foreach ($dishes as &$dish) {
            if ($dish['id'] == $dish_id && $dish['restaurant_id'] == $_SESSION['restaurant_id']) {
                $dish['is_available'] = $dish['is_available'] ? 0 : 1;
                break;
            }
        }
        
        if (saveJsonData('dishes', $dishes)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar disponibilidade']);
        }
        exit;
    }
}

// Carregar dados
$dishes = loadJsonData('dishes');
$categories = loadJsonData('categories');

// Filtrar dados do restaurante atual
$restaurant_dishes = array_filter($dishes, function($dish) {
    return $dish['restaurant_id'] == $_SESSION['restaurant_id'];
});

$restaurant_categories = array_filter($categories, function($cat) {
    return $cat['restaurant_id'] == $_SESSION['restaurant_id'];
});

// Criar array de categorias por ID para facilitar a busca
$categories_by_id = [];
foreach ($restaurant_categories as $cat) {
    $categories_by_id[$cat['id']] = $cat;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pratos - Prato Rápido | <?= htmlspecialchars($_SESSION['restaurant_name']) ?></title>
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
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
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
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .dish-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .dish-card:hover {
            transform: translateY(-5px);
        }
        .dish-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 15px 15px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        .price-tag {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: bold;
        }
        .availability-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
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
                        <h4 class="text-white mb-1">Prato Rápido</h4>
                        <h6 class="text-white"><?= htmlspecialchars($_SESSION['restaurant_name']) ?></h6>
                        <small class="text-white-50"><?= htmlspecialchars($_SESSION['employee_name']) ?></small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-users me-2"></i>Funcionários
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-2"></i>Categorias
                        </a>
                        <a class="nav-link active" href="dishes.php">
                            <i class="fas fa-utensils me-2"></i>Pratos
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-shopping-cart me-2"></i>Pedidos
                        </a>
                        <hr class="text-white-50">
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
                        <div class="d-flex align-items-center">
                            <a href="dashboard.html" class="btn btn-outline-secondary me-3">
                                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                            </a>
                            <h2 class="mb-0">Gerenciar Pratos</h2>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDishModal">
                            <i class="fas fa-plus me-2"></i>Novo Prato
                        </button>
                    </div>
                    
                    <!-- Mensagens -->
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Lista de Pratos -->
                    <div class="row">
                        <?php if (empty($restaurant_dishes)): ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-utensils fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Nenhum prato cadastrado</h4>
                                    <p class="text-muted">Comece adicionando seu primeiro prato ao cardápio.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDishModal">
                                        <i class="fas fa-plus me-2"></i>Adicionar Primeiro Prato
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($restaurant_dishes as $dish): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card dish-card position-relative">
                                        <div class="availability-badge">
                                            <?php if ($dish['is_available']): ?>
                                                <span class="badge bg-success">Disponível</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Indisponível</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="dish-image">
                            <?php if (!empty($dish['image_url']) && file_exists($dish['image_url'])): ?>
                                <img src="<?= htmlspecialchars($dish['image_url']) ?>" alt="<?= htmlspecialchars($dish['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 15px 15px 0 0;">
                            <?php else: ?>
                                <i class="fas fa-utensils fa-3x"></i>
                            <?php endif; ?>
                        </div>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($dish['name']) ?></h5>
                                            <p class="card-text text-muted small"><?= htmlspecialchars($dish['description']) ?></p>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="price-tag">R$ <?= number_format($dish['price'], 2, ',', '.') ?></span>
                                                <small class="text-muted">
                                                    <?= isset($categories_by_id[$dish['category_id']]) ? htmlspecialchars($categories_by_id[$dish['category_id']]['name']) : 'Sem categoria' ?>
                                                </small>
                                            </div>
                                            
                                            <?php if (!empty($dish['ingredients'])): ?>
                                                <p class="card-text"><small class="text-muted"><strong>Ingredientes:</strong> <?= htmlspecialchars($dish['ingredients']) ?></small></p>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($dish['prep_time']) && $dish['prep_time'] > 0): ?>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-clock text-muted me-2"></i>
                                                    <small class="text-muted"><strong>Tempo de preparo:</strong> <?= $dish['prep_time'] ?> min</small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary flex-fill">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </button>
                                                <button class="btn btn-sm <?= $dish['is_available'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" 
                                                        onclick="toggleAvailability(<?= $dish['id'] ?>)">
                                                    <i class="fas fa-<?= $dish['is_available'] ? 'eye-slash' : 'eye' ?> me-1"></i>
                                                    <?= $dish['is_available'] ? 'Ocultar' : 'Mostrar' ?>
                                                </button>
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
    </div>
    
    <!-- Modal Adicionar Prato -->
    <div class="modal fade" id="addDishModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Novo Prato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_dish">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nome do Prato *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Preço (R$) *</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Categoria *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($restaurant_categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($restaurant_categories)): ?>
                                    <div class="form-text text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Você precisa criar pelo menos uma categoria primeiro.
                                        <a href="categories.php" class="text-decoration-none">Criar categoria</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <?php if ($has_prep_time): ?>
                                    <label for="prep_time" class="form-label">Tempo de Preparo (minutos)</label>
                                    <input type="number" class="form-control" id="prep_time" name="prep_time" min="0" max="999" placeholder="Ex: 15">
                                    <div class="form-text">Tempo estimado para preparar o prato</div>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-lock me-2"></i>
                                        <strong>Tempo de Preparo</strong> disponível no plano Professional ou superior.
                                        <br><small>Plano atual: <?= $current_plan ?></small>
                                        <br><a href="plans.php" class="btn btn-sm btn-outline-primary mt-2">Fazer Upgrade</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Disponibilidade</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_available" name="is_available" checked>
                                <label class="form-check-label" for="is_available">
                                    Disponível para pedidos
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ingredients" class="form-label">Ingredientes</label>
                            <textarea class="form-control" id="ingredients" name="ingredients" rows="2" placeholder="Ex: Tomate, alface, queijo, hambúrguer..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <?php if ($has_image_upload): ?>
                                <label for="dish_image" class="form-label">Imagem do Prato</label>
                                <input type="file" class="form-control" id="dish_image" name="dish_image" accept="image/*">
                                <div class="form-text">Formatos aceitos: JPEG, PNG, GIF, WebP. Tamanho máximo: 5MB.</div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-lock me-2"></i>
                                    <strong>Upload de Imagens</strong> disponível no plano Starter ou superior.
                                    <br><small>Plano atual: <?= $current_plan ?></small>
                                    <br><a href="plans.php" class="btn btn-sm btn-outline-primary mt-2">Fazer Upgrade</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" <?= empty($restaurant_categories) ? 'disabled' : '' ?>>
                            <i class="fas fa-save me-2"></i>Salvar Prato
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAvailability(dishId) {
            if (confirm('Deseja alterar a disponibilidade deste prato?')) {
                const formData = new FormData();
                formData.append('action', 'toggle_availability');
                formData.append('dish_id', dishId);
                
                fetch('dishes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao atualizar disponibilidade');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao processar solicitação');
                });
            }
        }
    </script>
</body>
</html>