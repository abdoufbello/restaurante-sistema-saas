// Dashboard Functions - Sistema de Gestão de Restaurante

// Função para mostrar seções
function showSection(section, element = null) {
    // Remove active class from all nav links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to clicked link
    if (element && element.classList && element.classList.contains('nav-link')) {
        element.classList.add('active');
    } else {
        // Find nav link by section id
        const navLink = document.querySelector(`[onclick*="showSection('${section}')"]`);
        if (navLink) {
            navLink.classList.add('active');
        }
    }
    
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(sec => {
        sec.style.display = 'none';
    });
    
    // Show selected section
    const targetSection = document.getElementById(section + '-section');
    if (targetSection) {
        targetSection.style.display = 'block';
    }
    
    // Update page title
    const titles = {
        'dashboard': 'Dashboard',
        'funcionarios': 'Funcionários',
        'categorias': 'Categorias', 
        'pratos': 'Pratos',
        'pedidos': 'Pedidos'
    };
    
    const titleElement = document.querySelector('.main-content h2');
    if (titleElement) {
        titleElement.textContent = titles[section] || 'Dashboard';
    }
}

// Funções para Funcionários
function showAddEmployeeForm() {
    const modalHtml = `
        <div class="modal fade" id="employeeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Funcionário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="employeeForm">
                            <div class="mb-3">
                                <label for="employeeName" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="employeeName" required>
                            </div>
                            <div class="mb-3">
                                <label for="employeeUsername" class="form-label">Usuário</label>
                                <input type="text" class="form-control" id="employeeUsername" required>
                            </div>
                            <div class="mb-3">
                                <label for="employeeEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="employeeEmail" required>
                            </div>
                            <div class="mb-3">
                                <label for="employeePassword" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="employeePassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="employeeRole" class="form-label">Cargo</label>
                                <select class="form-control" id="employeeRole" required>
                                    <option value="employee">Funcionário</option>
                                    <option value="cashier">Caixa</option>
                                    <option value="kitchen">Cozinha</option>
                                    <option value="manager">Gerente</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="saveEmployee()">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    showModal(modalHtml);
}

function editEmployee(id) {
    alert('Função de editar funcionário será implementada em breve. ID: ' + id);
}

function deleteEmployee(id) {
    if (confirm('Tem certeza que deseja excluir este funcionário?')) {
        alert('Função de excluir funcionário será implementada em breve. ID: ' + id);
    }
}

function saveEmployee() {
    const form = document.getElementById('employeeForm');
    if (form.checkValidity()) {
        alert('Funcionário salvo com sucesso! (Função será implementada)');
        bootstrap.Modal.getInstance(document.getElementById('employeeModal')).hide();
    } else {
        form.reportValidity();
    }
}

// Funções para Categorias
function showAddCategoryForm() {
    const modalHtml = `
        <div class="modal fade" id="categoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nova Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="categoryForm">
                            <div class="mb-3">
                                <label for="categoryName" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="categoryName" required>
                            </div>
                            <div class="mb-3">
                                <label for="categoryDescription" class="form-label">Descrição</label>
                                <textarea class="form-control" id="categoryDescription" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" onclick="saveCategory()">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    showModal(modalHtml);
}

function editCategory(id) {
    alert('Função de editar categoria será implementada em breve. ID: ' + id);
}

function deleteCategory(id) {
    if (confirm('Tem certeza que deseja excluir esta categoria?')) {
        alert('Função de excluir categoria será implementada em breve. ID: ' + id);
    }
}

function saveCategory() {
    const form = document.getElementById('categoryForm');
    if (form.checkValidity()) {
        alert('Categoria salva com sucesso! (Função será implementada)');
        bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
    } else {
        form.reportValidity();
    }
}

// Funções para Pratos
function showAddDishForm() {
    const modalHtml = `
        <div class="modal fade" id="dishModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Prato</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="dishForm">
                            <div class="mb-3">
                                <label for="dishName" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="dishName" required>
                            </div>
                            <div class="mb-3">
                                <label for="dishDescription" class="form-label">Descrição</label>
                                <textarea class="form-control" id="dishDescription" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="dishPrice" class="form-label">Preço</label>
                                <input type="number" class="form-control" id="dishPrice" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="dishCategory" class="form-label">Categoria</label>
                                <select class="form-control" id="dishCategory" required>
                                    <option value="">Selecione uma categoria</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="dishAvailable" checked>
                                    <label class="form-check-label" for="dishAvailable">
                                        Disponível
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-warning" onclick="saveDish()">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    showModal(modalHtml);
}

function editDish(id) {
    alert('Função de editar prato será implementada em breve. ID: ' + id);
}

function deleteDish(id) {
    if (confirm('Tem certeza que deseja excluir este prato?')) {
        alert('Função de excluir prato será implementada em breve. ID: ' + id);
    }
}

function saveDish() {
    const form = document.getElementById('dishForm');
    if (form.checkValidity()) {
        alert('Prato salvo com sucesso! (Função será implementada)');
        bootstrap.Modal.getInstance(document.getElementById('dishModal')).hide();
    } else {
        form.reportValidity();
    }
}

// Funções para Pedidos
function viewOrder(id) {
    alert('Visualizar pedido ID: ' + id + ' (Função será implementada)');
}

function updateOrderStatus(id) {
    const statusOptions = {
        'pending': 'Preparando',
        'preparing': 'Pronto',
        'ready': 'Entregue',
        'delivered': 'Finalizado'
    };
    
    alert('Atualizar status do pedido ID: ' + id + ' (Função será implementada)');
}

// Função para gerar link público
function generatePublicLink() {
    // Esta função já existe no dashboard.php, mas vamos garantir que funcione
    const restaurantId = window.restaurantId || 1; // Fallback para ID 1
    const publicUrl = window.location.origin + window.location.pathname.replace('dashboard.php', 'kiosk_public.php') + '?restaurant_id=' + restaurantId;
    
    const modalHtml = `
        <div class="modal fade" id="publicLinkModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Link Público do Kiosk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Compartilhe este link com seus clientes para que eles possam fazer pedidos online:</p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="publicLinkInput" value="${publicUrl}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyPublicLink()">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Dica:</strong> Seus clientes podem acessar este link diretamente para fazer pedidos sem precisar de login.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <a href="${publicUrl}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt me-2"></i>Testar Link
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    showModal(modalHtml);
}

// Função para copiar link público
function copyPublicLink() {
    const input = document.getElementById('publicLinkInput');
    input.select();
    document.execCommand('copy');
    
    // Feedback visual
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copiado!';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

// Função auxiliar para mostrar modais
function showModal(modalHtml) {
    // Remover modal existente se houver
    const existingModal = document.querySelector('.modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Adicionar novo modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = document.querySelector('.modal');
    new bootstrap.Modal(modal).show();
    
    // Remover modal do DOM quando fechado
    modal.addEventListener('hidden.bs.modal', function () {
        modal.remove();
    });
}

// Inicialização quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    showSection('dashboard');
    
    // Definir ID do restaurante globalmente se disponível
    const restaurantIdElement = document.querySelector('[data-restaurant-id]');
    if (restaurantIdElement) {
        window.restaurantId = restaurantIdElement.getAttribute('data-restaurant-id');
    }
});

// Função para notificações (placeholder)
function showNotification(message, type = 'info') {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass[type]} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}