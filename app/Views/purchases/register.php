<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Registrar Compra<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <!-- Painel de Seleção de Produtos -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Adicionar Produtos</h4>
                </div>
                <div class="card-body">
                    <!-- Busca de Produtos -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="item_search" class="form-label">Buscar Produto</label>
                            <input type="text" class="form-control" id="item_search" 
                                   placeholder="Digite o nome ou código do produto...">
                        </div>
                        <div class="col-md-3">
                            <label for="category_filter" class="form-label">Categoria</label>
                            <select class="form-select" id="category_filter">
                                <option value="">Todas as categorias</option>
                                <option value="food">Alimentos</option>
                                <option value="beverage">Bebidas</option>
                                <option value="cleaning">Limpeza</option>
                                <option value="packaging">Embalagens</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="barcode_scan" class="form-label">Código de Barras</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="barcode_scan" 
                                       placeholder="Escaneie ou digite...">
                                <button class="btn btn-outline-secondary" type="button" onclick="startBarcodeScanner()">
                                    <i class="fas fa-barcode"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Produtos Disponíveis -->
                    <div class="row" id="products-grid">
                        <!-- Produtos serão carregados via AJAX -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Painel do Carrinho -->
        <div class="col-lg-4">
            <div class="card sticky-top">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Carrinho de Compras</h4>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearCart()">
                        <i class="fas fa-trash"></i> Limpar
                    </button>
                </div>
                <div class="card-body">
                    <!-- Informações do Fornecedor -->
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Fornecedor *</label>
                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                            <option value="">Selecione um fornecedor</option>
                            <?php foreach($suppliers ?? [] as $supplier): ?>
                                <option value="<?= $supplier->person_id ?>"><?= $supplier->company_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Referência da Compra -->
                    <div class="mb-3">
                        <label for="reference" class="form-label">Referência</label>
                        <input type="text" class="form-control" id="reference" name="reference" 
                               placeholder="Número da nota fiscal, pedido, etc.">
                    </div>
                    
                    <!-- Itens do Carrinho -->
                    <div class="mb-3">
                        <h6>Itens Selecionados</h6>
                        <div id="cart-items" class="border rounded p-2" style="min-height: 200px; max-height: 300px; overflow-y: auto;">
                            <div class="text-muted text-center py-4">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <p>Carrinho vazio</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resumo Financeiro -->
                    <div class="mb-3">
                        <div class="bg-light p-3 rounded">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal">R$ 0,00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Desconto:</span>
                                <span id="discount">R$ 0,00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span id="total" class="text-primary">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observações -->
                    <div class="mb-3">
                        <label for="comment" class="form-label">Observações</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" 
                                  placeholder="Observações sobre a compra..."></textarea>
                    </div>
                    
                    <!-- Botões de Ação -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg" onclick="completePurchase()">
                            <i class="fas fa-check"></i> Finalizar Compra
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                            <i class="fas fa-save"></i> Salvar Rascunho
                        </button>
                        <a href="<?= site_url('purchases') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Adicionar Item -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Item ao Carrinho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-item-form">
                    <input type="hidden" id="modal_item_id" name="item_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <p id="modal_item_name" class="form-control-plaintext fw-bold"></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="modal_quantity" class="form-label">Quantidade *</label>
                            <input type="number" class="form-control" id="modal_quantity" name="quantity" 
                                   min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_unit" class="form-label">Unidade</label>
                            <input type="text" class="form-control" id="modal_unit" name="unit" readonly>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="modal_cost_price" class="form-label">Preço de Custo *</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="modal_cost_price" name="cost_price" 
                                       min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_discount" class="form-label">Desconto (%)</label>
                            <input type="number" class="form-control" id="modal_discount" name="discount" 
                                   min="0" max="100" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="modal_total_item" class="form-label">Total do Item</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="modal_total_item" readonly>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="addItemToCart()">Adicionar ao Carrinho</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Scanner de Código de Barras -->
<div class="modal fade" id="barcodeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scanner de Código de Barras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="barcode-scanner" style="width: 100%; height: 300px;"></div>
                <div class="mt-3">
                    <p class="text-muted">Posicione o código de barras na frente da câmera</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let cart = [];
let products = [];

$(document).ready(function() {
    loadProducts();
    
    // Configurar busca de produtos
    $('#item_search').on('input', function() {
        filterProducts();
    });
    
    $('#category_filter').change(function() {
        filterProducts();
    });
    
    // Configurar scanner de código de barras
    $('#barcode_scan').on('input', function() {
        const barcode = $(this).val();
        if (barcode.length >= 8) {
            searchProductByBarcode(barcode);
        }
    });
    
    // Calcular total do item no modal
    $('#modal_quantity, #modal_cost_price, #modal_discount').on('input', function() {
        calculateItemTotal();
    });
});

function loadProducts() {
    $.get('<?= site_url('items/get_all_json') ?>', function(data) {
        products = data;
        displayProducts(products);
    }).fail(function() {
        console.error('Erro ao carregar produtos');
    });
}

function displayProducts(productsToShow) {
    const grid = $('#products-grid');
    grid.empty();
    
    if (productsToShow.length === 0) {
        grid.html('<div class="col-12 text-center py-4"><p class="text-muted">Nenhum produto encontrado</p></div>');
        return;
    }
    
    productsToShow.forEach(function(product) {
        const productCard = `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 product-card" onclick="openAddItemModal(${product.item_id})">
                    <div class="card-body">
                        <h6 class="card-title">${product.name}</h6>
                        <p class="card-text text-muted small">${product.category || 'Sem categoria'}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Estoque: ${product.quantity || 0}</small>
                            <span class="badge bg-primary">Adicionar</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        grid.append(productCard);
    });
}

function filterProducts() {
    const search = $('#item_search').val().toLowerCase();
    const category = $('#category_filter').val();
    
    let filtered = products.filter(function(product) {
        const matchesSearch = !search || 
            product.name.toLowerCase().includes(search) ||
            (product.item_number && product.item_number.toLowerCase().includes(search));
        
        const matchesCategory = !category || product.category === category;
        
        return matchesSearch && matchesCategory;
    });
    
    displayProducts(filtered);
}

function searchProductByBarcode(barcode) {
    const product = products.find(p => p.item_number === barcode);
    if (product) {
        openAddItemModal(product.item_id);
        $('#barcode_scan').val('');
    } else {
        alert('Produto não encontrado para o código: ' + barcode);
    }
}

function openAddItemModal(itemId) {
    const product = products.find(p => p.item_id == itemId);
    if (!product) return;
    
    $('#modal_item_id').val(product.item_id);
    $('#modal_item_name').text(product.name);
    $('#modal_unit').val(product.unit_type || 'UN');
    $('#modal_quantity').val(1);
    $('#modal_cost_price').val(product.cost_price || '');
    $('#modal_discount').val(0);
    
    calculateItemTotal();
    $('#addItemModal').modal('show');
}

function calculateItemTotal() {
    const quantity = parseFloat($('#modal_quantity').val()) || 0;
    const costPrice = parseFloat($('#modal_cost_price').val()) || 0;
    const discount = parseFloat($('#modal_discount').val()) || 0;
    
    const subtotal = quantity * costPrice;
    const discountAmount = subtotal * (discount / 100);
    const total = subtotal - discountAmount;
    
    $('#modal_total_item').val(total.toFixed(2));
}

function addItemToCart() {
    const itemId = $('#modal_item_id').val();
    const quantity = parseFloat($('#modal_quantity').val());
    const costPrice = parseFloat($('#modal_cost_price').val());
    const discount = parseFloat($('#modal_discount').val()) || 0;
    
    if (!itemId || !quantity || !costPrice) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    const product = products.find(p => p.item_id == itemId);
    const subtotal = quantity * costPrice;
    const discountAmount = subtotal * (discount / 100);
    const total = subtotal - discountAmount;
    
    // Verificar se o item já está no carrinho
    const existingIndex = cart.findIndex(item => item.item_id == itemId);
    
    if (existingIndex >= 0) {
        // Atualizar item existente
        cart[existingIndex].quantity += quantity;
        cart[existingIndex].total += total;
    } else {
        // Adicionar novo item
        cart.push({
            item_id: itemId,
            name: product.name,
            quantity: quantity,
            cost_price: costPrice,
            discount: discount,
            total: total
        });
    }
    
    updateCartDisplay();
    $('#addItemModal').modal('hide');
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

function updateCartDisplay() {
    const cartContainer = $('#cart-items');
    
    if (cart.length === 0) {
        cartContainer.html(`
            <div class="text-muted text-center py-4">
                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                <p>Carrinho vazio</p>
            </div>
        `);
    } else {
        let html = '';
        cart.forEach(function(item, index) {
            html += `
                <div class="cart-item mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${item.name}</h6>
                            <small class="text-muted">
                                ${item.quantity} x R$ ${item.cost_price.toFixed(2)}
                                ${item.discount > 0 ? ` (-${item.discount}%)` : ''}
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">R$ ${item.total.toFixed(2)}</div>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="removeFromCart(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        cartContainer.html(html);
    }
    
    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.cost_price), 0);
    const totalDiscount = cart.reduce((sum, item) => sum + (item.quantity * item.cost_price * item.discount / 100), 0);
    const total = subtotal - totalDiscount;
    
    $('#subtotal').text('R$ ' + subtotal.toFixed(2).replace('.', ','));
    $('#discount').text('R$ ' + totalDiscount.toFixed(2).replace('.', ','));
    $('#total').text('R$ ' + total.toFixed(2).replace('.', ','));
}

function clearCart() {
    if (cart.length > 0 && confirm('Tem certeza que deseja limpar o carrinho?')) {
        cart = [];
        updateCartDisplay();
    }
}

function completePurchase() {
    if (cart.length === 0) {
        alert('Adicione pelo menos um item ao carrinho');
        return;
    }
    
    const supplierId = $('#supplier_id').val();
    if (!supplierId) {
        alert('Selecione um fornecedor');
        return;
    }
    
    const purchaseData = {
        supplier_id: supplierId,
        reference: $('#reference').val(),
        comment: $('#comment').val(),
        items: cart
    };
    
    $.post('<?= site_url('purchases/complete') ?>', purchaseData, function(response) {
        if (response.success) {
            alert('Compra registrada com sucesso!');
            window.location.href = '<?= site_url('purchases/receipt') ?>/' + response.purchase_id;
        } else {
            alert('Erro ao registrar compra: ' + (response.message || 'Erro desconhecido'));
        }
    }).fail(function() {
        alert('Erro de conexão ao registrar compra');
    });
}

function saveDraft() {
    // Implementar salvamento de rascunho
    localStorage.setItem('purchase_draft', JSON.stringify({
        cart: cart,
        supplier_id: $('#supplier_id').val(),
        reference: $('#reference').val(),
        comment: $('#comment').val()
    }));
    
    alert('Rascunho salvo com sucesso!');
}

function startBarcodeScanner() {
    $('#barcodeModal').modal('show');
    // Implementar scanner de código de barras usando QuaggaJS ou similar
}
</script>
<?= $this->endSection() ?>