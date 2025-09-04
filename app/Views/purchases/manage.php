<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Gestão de Compras<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Compras Registradas</h3>
                    <div class="btn-group">
                        <a href="<?= site_url('purchases/register') ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nova Compra
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportData()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="date_from" name="date_from">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Data Final</label>
                            <input type="date" class="form-control" id="date_to" name="date_to">
                        </div>
                        <div class="col-md-4">
                            <label for="supplier_filter" class="form-label">Fornecedor</label>
                            <select class="form-select" id="supplier_filter" name="supplier_filter">
                                <option value="">Todos os fornecedores</option>
                                <?php foreach($suppliers ?? [] as $supplier): ?>
                                    <option value="<?= $supplier->person_id ?>"><?= $supplier->company_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tabela de Compras -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="purchases-table">
                            <thead class="table-dark">
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>ID</th>
                                    <th>Data/Hora</th>
                                    <th>Fornecedor</th>
                                    <th>Referência</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(isset($purchases) && count($purchases) > 0): ?>
                                    <?php foreach($purchases as $purchase): ?>
                                        <tr>
                                            <td><input type="checkbox" class="purchase-checkbox" value="<?= $purchase->purchase_id ?>"></td>
                                            <td><?= $purchase->purchase_id ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($purchase->purchase_time)) ?></td>
                                            <td><?= $purchase->supplier_name ?? 'N/A' ?></td>
                                            <td><?= $purchase->reference ?></td>
                                            <td>R$ <?= number_format($purchase->total, 2, ',', '.') ?></td>
                                            <td>
                                                <span class="badge bg-success">Concluída</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= site_url('purchases/receipt/' . $purchase->purchase_id) ?>" 
                                                       class="btn btn-outline-primary" title="Ver Recibo">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= site_url('purchases/edit/' . $purchase->purchase_id) ?>" 
                                                       class="btn btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deletePurchase(<?= $purchase->purchase_id ?>)" title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                                <p>Nenhuma compra registrada ainda.</p>
                                                <a href="<?= site_url('purchases/register') ?>" class="btn btn-primary">
                                                    Registrar primeira compra
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if(isset($pagination)): ?>
                        <div class="d-flex justify-content-center mt-3">
                            <?= $pagination ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Ações em lote -->
                    <div class="mt-3" id="bulk-actions" style="display: none;">
                        <div class="alert alert-info">
                            <span id="selected-count">0</span> compra(s) selecionada(s)
                            <div class="btn-group ms-3">
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelected()">
                                    <i class="fas fa-trash"></i> Excluir Selecionadas
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="exportSelected()">
                                    <i class="fas fa-download"></i> Exportar Selecionadas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirm-message">Tem certeza que deseja realizar esta ação?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirm-action">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Configurar DataTable
    $('#purchases-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        order: [[2, 'desc']], // Ordenar por data decrescente
        columnDefs: [
            { orderable: false, targets: [0, 7] } // Desabilitar ordenação para checkbox e ações
        ]
    });
    
    // Gerenciar seleção de checkboxes
    $('#select-all').change(function() {
        $('.purchase-checkbox').prop('checked', this.checked);
        updateBulkActions();
    });
    
    $('.purchase-checkbox').change(function() {
        updateBulkActions();
    });
});

function updateBulkActions() {
    const selected = $('.purchase-checkbox:checked').length;
    $('#selected-count').text(selected);
    
    if (selected > 0) {
        $('#bulk-actions').show();
    } else {
        $('#bulk-actions').hide();
    }
    
    // Atualizar estado do select-all
    const total = $('.purchase-checkbox').length;
    $('#select-all').prop('indeterminate', selected > 0 && selected < total);
    $('#select-all').prop('checked', selected === total && total > 0);
}

function applyFilters() {
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();
    const supplier = $('#supplier_filter').val();
    
    let url = '<?= site_url('purchases') ?>';
    const params = new URLSearchParams();
    
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (supplier) params.append('supplier', supplier);
    
    if (params.toString()) {
        url += '?' + params.toString();
    }
    
    window.location.href = url;
}

function deletePurchase(purchaseId) {
    $('#confirm-message').text('Tem certeza que deseja excluir esta compra? Esta ação não pode ser desfeita.');
    $('#confirm-action').off('click').on('click', function() {
        $.post('<?= site_url('purchases/delete') ?>/' + purchaseId, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao excluir compra: ' + (response.message || 'Erro desconhecido'));
            }
        }).fail(function() {
            alert('Erro de conexão ao excluir compra.');
        });
        $('#confirmModal').modal('hide');
    });
    $('#confirmModal').modal('show');
}

function deleteSelected() {
    const selected = $('.purchase-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selected.length === 0) {
        alert('Selecione pelo menos uma compra para excluir.');
        return;
    }
    
    $('#confirm-message').text(`Tem certeza que deseja excluir ${selected.length} compra(s)? Esta ação não pode ser desfeita.`);
    $('#confirm-action').off('click').on('click', function() {
        $.post('<?= site_url('purchases/delete') ?>', {ids: selected}, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao excluir compras: ' + (response.message || 'Erro desconhecido'));
            }
        }).fail(function() {
            alert('Erro de conexão ao excluir compras.');
        });
        $('#confirmModal').modal('hide');
    });
    $('#confirmModal').modal('show');
}

function exportData() {
    window.open('<?= site_url('purchases/export') ?>', '_blank');
}

function exportSelected() {
    const selected = $('.purchase-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selected.length === 0) {
        alert('Selecione pelo menos uma compra para exportar.');
        return;
    }
    
    const form = $('<form>', {
        method: 'POST',
        action: '<?= site_url('purchases/export') ?>',
        target: '_blank'
    });
    
    selected.forEach(function(id) {
        form.append($('<input>', {
            type: 'hidden',
            name: 'ids[]',
            value: id
        }));
    });
    
    $('body').append(form);
    form.submit();
    form.remove();
}
</script>
<?= $this->endSection() ?>