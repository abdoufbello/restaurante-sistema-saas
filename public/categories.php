<?php
session_start();

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: simple_auth.php');
    exit;
}

// Incluir helper de planos
require_once 'plan_helper.php';

// Verificar se tem acesso ao gerenciamento de categorias
if (!hasFeatureAccess($_SESSION['restaurant_id'], 'categories_management')) {
    $upgrade_message = getUpgradeMessage('categories_management');
    $current_plan = getCurrentPlanName($_SESSION['restaurant_id']);
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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $categories = loadJsonData('categories');
        
        // Validar dados
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#007bff');
        $icon = trim($_POST['icon'] ?? 'fas fa-utensils');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'O nome da categoria é obrigatório.';
        } else {
            // Verificar se já existe uma categoria com o mesmo nome no restaurante
            $name_exists = false;
            foreach ($categories as $cat) {
                if ($cat['restaurant_id'] == $_SESSION['restaurant_id'] && 
                    strtolower($cat['name']) === strtolower($name)) {
                    $name_exists = true;
                    break;
                }
            }
            
            if ($name_exists) {
                $error = 'Já existe uma categoria com este nome.';
            } else {
                // Gerar novo ID
                $new_id = 1;
                foreach ($categories as $cat) {
                    if ($cat['id'] >= $new_id) {
                        $new_id = $cat['id'] + 1;
                    }
                }
                
                // Adicionar nova categoria
                $new_category = [
                    'id' => $new_id,
                    'restaurant_id' => $_SESSION['restaurant_id'],
                    'name' => $name,
                    'description' => $description,
                    'color' => $color,
                    'icon' => $icon,
                    'is_active' => $is_active,
                    'sort_order' => count($categories) + 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $categories[] = $new_category;
                
                if (saveJsonData('categories', $categories)) {
                    $success = 'Categoria adicionada com sucesso!';
                } else {
                    $error = 'Erro ao salvar a categoria.';
                }
            }
        }
    }
    
    if ($action === 'edit_category') {
        $categories = loadJsonData('categories');
        
        // Validar dados
        $category_id = intval($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#007bff');
        $icon = trim($_POST['icon'] ?? 'fas fa-utensils');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'O nome da categoria é obrigatório.';
        } else {
            // Verificar se já existe uma categoria com o mesmo nome no restaurante (exceto a atual)
            $name_exists = false;
            foreach ($categories as $cat) {
                if ($cat['restaurant_id'] == $_SESSION['restaurant_id'] && 
                    $cat['id'] != $category_id &&
                    strtolower($cat['name']) === strtolower($name)) {
                    $name_exists = true;
                    break;
                }
            }
            
            if ($name_exists) {
                $error = 'Já existe uma categoria com este nome.';
            } else {
                // Atualizar categoria
                foreach ($categories as &$category) {
                    if ($category['id'] == $category_id && $category['restaurant_id'] == $_SESSION['restaurant_id']) {
                        $category['name'] = $name;
                        $category['description'] = $description;
                        $category['color'] = $color;
                        $category['icon'] = $icon;
                        $category['is_active'] = $is_active;
                        $category['updated_at'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                
                if (saveJsonData('categories', $categories)) {
                    $success = 'Categoria atualizada com sucesso!';
                } else {
                    $error = 'Erro ao salvar a categoria.';
                }
            }
        }
    }
    
    if ($action === 'toggle_status') {
        $categories = loadJsonData('categories');
        $category_id = intval($_POST['category_id'] ?? 0);
        
        foreach ($categories as &$category) {
            if ($category['id'] == $category_id && $category['restaurant_id'] == $_SESSION['restaurant_id']) {
                $category['is_active'] = $category['is_active'] ? 0 : 1;
                break;
            }
        }
        
        if (saveJsonData('categories', $categories)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status']);
        }
        exit;
    }
    
    if ($action === 'delete_category') {
        $categories = loadJsonData('categories');
        $dishes = loadJsonData('dishes');
        $category_id = intval($_POST['category_id'] ?? 0);
        
        // Verificar se existem pratos usando esta categoria
        $has_dishes = false;
        foreach ($dishes as $dish) {
            if ($dish['category_id'] == $category_id && $dish['restaurant_id'] == $_SESSION['restaurant_id']) {
                $has_dishes = true;
                break;
            }
        }
        
        if ($has_dishes) {
            echo json_encode(['success' => false, 'message' => 'Não é possível excluir uma categoria que possui pratos cadastrados.']);
        } else {
            // Remover categoria
            $categories = array_filter($categories, function($cat) use ($category_id) {
                return !($cat['id'] == $category_id && $cat['restaurant_id'] == $_SESSION['restaurant_id']);
            });
            
            if (saveJsonData('categories', array_values($categories))) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir categoria']);
            }
        }
        exit;
    }
}

// Carregar dados
$categories = loadJsonData('categories');
$dishes = loadJsonData('dishes');

// Filtrar categorias do restaurante atual
$restaurant_categories = array_filter($categories, function($cat) {
    return $cat['restaurant_id'] == $_SESSION['restaurant_id'];
});

// Contar pratos por categoria
$dishes_count = [];
foreach ($dishes as $dish) {
    if ($dish['restaurant_id'] == $_SESSION['restaurant_id']) {
        $cat_id = $dish['category_id'];
        $dishes_count[$cat_id] = ($dishes_count[$cat_id] ?? 0) + 1;
    }
}

// Ícones disponíveis
$available_icons = [
    'fas fa-utensils' => 'Utensílios',
    'fas fa-hamburger' => 'Hambúrguer',
    'fas fa-pizza-slice' => 'Pizza',
    'fas fa-coffee' => 'Café',
    'fas fa-wine-glass' => 'Bebidas',
    'fas fa-ice-cream' => 'Sobremesas',
    'fas fa-fish' => 'Peixes',
    'fas fa-drumstick-bite' => 'Carnes',
    'fas fa-leaf' => 'Vegetariano',
    'fas fa-birthday-cake' => 'Bolos',
    'fas fa-cookie-bite' => 'Lanches',
    'fas fa-apple-alt' => 'Frutas'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - Prato Rápido | <?= htmlspecialchars($_SESSION['restaurant_name']) ?></title>
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
        .category-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            overflow: hidden;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .category-header {
            padding: 1.5rem;
            color: white;
            position: relative;
        }
        .category-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .icon-preview {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 0.5rem;
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
                        <a class="nav-link active" href="categories.php">
                            <i class="fas fa-tags me-2"></i>Categorias
                        </a>
                        <a class="nav-link" href="dishes.php">
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
                            <h2 class="mb-0">Gerenciar Categorias</h2>
                        </div>
                        <?php if (hasFeatureAccess($_SESSION['restaurant_id'], 'categories_management')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Nova Categoria
                        </button>
                        <?php else: ?>
                        <a href="plans.php" class="btn btn-warning">
                            <i class="fas fa-crown me-2"></i>Fazer Upgrade
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Aviso de Plano -->
                    <?php if (!hasFeatureAccess($_SESSION['restaurant_id'], 'categories_management')): ?>
                    <div class="alert alert-warning" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-crown fa-2x me-3"></i>
                            <div>
                                <h5 class="alert-heading mb-1">Funcionalidade Premium</h5>
                                <p class="mb-2"><?= htmlspecialchars($upgrade_message) ?></p>
                                <p class="mb-0"><strong>Plano Atual:</strong> <?= htmlspecialchars($current_plan) ?></p>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Faça upgrade para ter acesso completo ao gerenciamento de categorias.</span>
                            <a href="plans.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-arrow-up me-1"></i>Ver Planos
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
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
                    
                    <!-- Lista de Categorias -->
                    <div class="row">
                        <?php if (empty($restaurant_categories)): ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Nenhuma categoria cadastrada</h4>
                                    <p class="text-muted">Organize seu cardápio criando categorias para seus pratos.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <i class="fas fa-plus me-2"></i>Criar Primeira Categoria
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($restaurant_categories as $category): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card category-card">
                                        <div class="category-header" style="background: <?= htmlspecialchars($category['color']) ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="category-icon">
                                                        <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                                                    </div>
                                                    <h5 class="mb-1"><?= htmlspecialchars($category['name']) ?></h5>
                                                    <small class="opacity-75"><?= $dishes_count[$category['id']] ?? 0 ?> pratos</small>
                                                </div>
                                                <div>
                                                    <?php if ($category['is_active']): ?>
                                                        <span class="badge bg-light text-dark">Ativa</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inativa</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body">
                                            <?php if (!empty($category['description'])): ?>
                                                <p class="card-text text-muted small"><?= htmlspecialchars($category['description']) ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary flex-fill" 
                                                        onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', '<?= htmlspecialchars($category['description']) ?>', '<?= htmlspecialchars($category['icon']) ?>', '<?= htmlspecialchars($category['color']) ?>', <?= $category['is_active'] ?>)">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </button>
                                                <button class="btn btn-sm <?= $category['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" 
                                                        onclick="toggleStatus(<?= $category['id'] ?>)">
                                                    <i class="fas fa-<?= $category['is_active'] ? 'eye-slash' : 'eye' ?> me-1"></i>
                                                    <?= $category['is_active'] ? 'Desativar' : 'Ativar' ?>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                                    <i class="fas fa-trash me-1"></i>
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
    
    <!-- Modal Adicionar Categoria -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Nova Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_category">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="icon" class="form-label">Ícone</label>
                                <select class="form-select" id="icon" name="icon" onchange="updateIconPreview()">
                                    <?php foreach ($available_icons as $icon_class => $icon_name): ?>
                                        <option value="<?= $icon_class ?>"><?= $icon_name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Cor</label>
                                <input type="color" class="form-control form-control-color" id="color" name="color" value="#007bff" onchange="updateIconPreview()">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pré-visualização</label>
                            <div class="d-flex align-items-center">
                                <div id="iconPreview" class="icon-preview" style="background-color: #007bff;">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <span id="namePreview">Nome da Categoria</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Categoria ativa
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Categoria
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Categoria -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_category">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nome da Categoria *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_icon" class="form-label">Ícone</label>
                                <select class="form-select" id="edit_icon" name="icon" onchange="updateEditIconPreview()">
                                    <?php foreach ($available_icons as $icon_class => $icon_name): ?>
                                        <option value="<?= $icon_class ?>"><?= $icon_name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_color" class="form-label">Cor</label>
                                <input type="color" class="form-control form-control-color" id="edit_color" name="color" value="#007bff" onchange="updateEditIconPreview()">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pré-visualização</label>
                            <div class="d-flex align-items-center">
                                <div id="editIconPreview" class="icon-preview" style="background-color: #007bff;">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <span id="editNamePreview">Nome da Categoria</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Categoria ativa
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateIconPreview() {
            const iconSelect = document.getElementById('icon');
            const colorInput = document.getElementById('color');
            const nameInput = document.getElementById('name');
            const iconPreview = document.getElementById('iconPreview');
            const namePreview = document.getElementById('namePreview');
            
            const selectedIcon = iconSelect.value;
            const selectedColor = colorInput.value;
            const categoryName = nameInput.value || 'Nome da Categoria';
            
            iconPreview.style.backgroundColor = selectedColor;
            iconPreview.innerHTML = `<i class="${selectedIcon}"></i>`;
            namePreview.textContent = categoryName;
        }
        
        // Atualizar preview quando o nome mudar
        document.getElementById('name').addEventListener('input', updateIconPreview);
        
        function toggleStatus(categoryId) {
            if (confirm('Deseja alterar o status desta categoria?')) {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('category_id', categoryId);
                
                fetch('categories.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao atualizar status');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao processar solicitação');
                });
            }
        }
        
        function editCategory(categoryId, name, description, icon, color, isActive) {
            // Preencher os campos do modal de edição
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_icon').value = icon;
            document.getElementById('edit_color').value = color;
            document.getElementById('edit_is_active').checked = isActive == 1;
            
            // Atualizar preview
            updateEditIconPreview();
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        }
        
        function updateEditIconPreview() {
            const iconSelect = document.getElementById('edit_icon');
            const colorInput = document.getElementById('edit_color');
            const nameInput = document.getElementById('edit_name');
            const iconPreview = document.getElementById('editIconPreview');
            const namePreview = document.getElementById('editNamePreview');
            
            const selectedIcon = iconSelect.value;
            const selectedColor = colorInput.value;
            const categoryName = nameInput.value || 'Nome da Categoria';
            
            iconPreview.style.backgroundColor = selectedColor;
            iconPreview.innerHTML = `<i class="${selectedIcon}"></i>`;
            namePreview.textContent = categoryName;
        }
        
        // Atualizar preview quando o nome mudar no modal de edição
        document.getElementById('edit_name').addEventListener('input', updateEditIconPreview);
        
        function deleteCategory(categoryId, categoryName) {
            if (confirm(`Tem certeza que deseja excluir a categoria "${categoryName}"?\n\nEsta ação não pode ser desfeita.`)) {
                const formData = new FormData();
                formData.append('action', 'delete_category');
                formData.append('category_id', categoryId);
                
                fetch('categories.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao excluir categoria');
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