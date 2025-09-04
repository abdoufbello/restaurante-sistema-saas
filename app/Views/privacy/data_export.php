<?= $this->extend('layouts/privacy') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/privacy/consent">Privacidade</a></li>
                        <li class="breadcrumb-item active">Exportação de Dados</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Export Request Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-download"></i> Solicitar Exportação de Dados
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Direito à Portabilidade - LGPD</h6>
                        <p class="mb-0">
                            Você tem o direito de receber seus dados pessoais em formato estruturado e legível por máquina. 
                            O arquivo será disponibilizado em formato JSON e ficará disponível para download por 30 dias.
                        </p>
                    </div>

                    <form id="exportForm">
                        <div class="form-group">
                            <label class="font-weight-bold mb-3">Selecione os tipos de dados que deseja exportar:</label>
                            
                            <div class="row">
                                <?php foreach ($export_types as $key => $label): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" 
                                               id="export_<?= $key ?>" name="data_types[]" value="<?= $key ?>">
                                        <label class="custom-control-label" for="export_<?= $key ?>">
                                            <strong><?= $label ?></strong>
                                            <small class="d-block text-muted">
                                                <?php
                                                $descriptions = [
                                                    'restaurant_data' => 'Nome, endereço, configurações, informações de contato',
                                                    'menu_data' => 'Pratos, categorias, preços, ingredientes',
                                                    'order_data' => 'Histórico de pedidos dos últimos 2 anos',
                                                    'customer_data' => 'Dados de clientes cadastrados',
                                                    'financial_data' => 'Transações, faturas, relatórios financeiros',
                                                    'usage_data' => 'Estatísticas de uso da plataforma',
                                                    'audit_logs' => 'Logs de atividade e segurança'
                                                ];
                                                echo $descriptions[$key] ?? '';
                                                ?>
                                            </small>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="accept_terms" required>
                                <label class="custom-control-label" for="accept_terms">
                                    Confirmo que estou solicitando a exportação dos meus próprios dados e 
                                    entendo que o arquivo conterá informações sensíveis que devem ser protegidas.
                                </label>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-file-export"></i> Solicitar Exportação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Export Status -->
            <?php if (!empty($pending_exports)): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock"></i> Solicitações Pendentes
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($pending_exports as $export): ?>
                    <div class="export-item mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    Exportação #<?= $export['id'] ?>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'failed' => 'danger'
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Pendente',
                                        'processing' => 'Processando',
                                        'completed' => 'Concluída',
                                        'failed' => 'Falhou'
                                    ];
                                    ?>
                                    <span class="badge badge-<?= $statusColors[$export['status']] ?>">
                                        <?= $statusLabels[$export['status']] ?>
                                    </span>
                                </h6>
                                <small class="text-muted">
                                    Solicitada em <?= date('d/m/Y H:i', strtotime($export['created_at'])) ?>
                                </small>
                                <?php if ($export['status'] === 'completed'): ?>
                                <div class="mt-2">
                                    <a href="/privacy/download-export/<?= $export['id'] ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-download"></i> Baixar
                                    </a>
                                    <small class="d-block text-muted mt-1">
                                        <?= number_format($export['file_size_bytes'] / 1024, 1) ?> KB • 
                                        Expira em <?= date('d/m/Y', strtotime($export['expires_at'])) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Information Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Informações Importantes
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <h6 class="text-primary mb-2">
                            <i class="fas fa-clock"></i> Tempo de Processamento
                        </h6>
                        <p class="small text-muted mb-0">
                            As solicitações são processadas em até 72 horas. Você receberá um email 
                            quando a exportação estiver pronta para download.
                        </p>
                    </div>

                    <div class="info-item mb-3">
                        <h6 class="text-success mb-2">
                            <i class="fas fa-file-alt"></i> Formato do Arquivo
                        </h6>
                        <p class="small text-muted mb-0">
                            Os dados são exportados em formato JSON estruturado, legível por máquina 
                            e compatível com a maioria dos sistemas.
                        </p>
                    </div>

                    <div class="info-item mb-3">
                        <h6 class="text-warning mb-2">
                            <i class="fas fa-shield-alt"></i> Segurança
                        </h6>
                        <p class="small text-muted mb-0">
                            O arquivo contém dados sensíveis. Mantenha-o seguro e exclua após o uso. 
                            O link de download expira em 30 dias.
                        </p>
                    </div>

                    <div class="info-item">
                        <h6 class="text-info mb-2">
                            <i class="fas fa-question-circle"></i> Dúvidas?
                        </h6>
                        <p class="small text-muted mb-2">
                            Entre em contato com nossa equipe de privacidade:
                        </p>
                        <p class="small mb-0">
                            <i class="fas fa-envelope"></i> privacidade@totemtouchsystem.com<br>
                            <i class="fas fa-phone"></i> (11) 9999-9999
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tools"></i> Outras Ações
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/privacy/consent" class="list-group-item list-group-item-action">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Gerenciar Consentimentos</strong>
                            <small class="d-block text-muted">Atualizar preferências de privacidade</small>
                        </a>
                        <a href="/privacy/data-deletion" class="list-group-item list-group-item-action">
                            <i class="fas fa-trash text-danger"></i>
                            <strong>Solicitar Exclusão</strong>
                            <small class="d-block text-muted">Excluir dados opcionais</small>
                        </a>
                        <a href="/privacy/policy" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt text-secondary"></i>
                            <strong>Política de Privacidade</strong>
                            <small class="d-block text-muted">Ler política completa</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportForm = document.getElementById('exportForm');
    const checkboxes = exportForm.querySelectorAll('input[name="data_types[]"]');
    const selectAllBtn = document.createElement('button');
    
    // Add select all button
    selectAllBtn.type = 'button';
    selectAllBtn.className = 'btn btn-sm btn-outline-primary mb-3';
    selectAllBtn.innerHTML = '<i class="fas fa-check-double"></i> Selecionar Todos';
    
    const firstCheckbox = checkboxes[0].closest('.col-md-6');
    firstCheckbox.parentNode.insertBefore(selectAllBtn, firstCheckbox);
    
    selectAllBtn.addEventListener('click', function() {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        this.innerHTML = allChecked ? 
            '<i class="fas fa-check-double"></i> Selecionar Todos' : 
            '<i class="fas fa-times"></i> Desmarcar Todos';
    });
    
    exportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedTypes = Array.from(checkboxes).filter(cb => cb.checked);
        
        if (selectedTypes.length === 0) {
            showAlert('warning', 'Selecione pelo menos um tipo de dados para exportar.');
            return;
        }
        
        const acceptTerms = document.getElementById('accept_terms').checked;
        if (!acceptTerms) {
            showAlert('warning', 'Você deve aceitar os termos para continuar.');
            return;
        }
        
        const formData = new FormData(exportForm);
        const submitBtn = exportForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        
        fetch('/privacy/request-export', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                
                // Reset form
                exportForm.reset();
                
                // Reload page after delay to show new request
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Erro ao solicitar exportação. Tente novamente.');
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    
    // Update checkbox counter
    function updateCounter() {
        const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const totalCount = checkboxes.length;
        
        let counterEl = document.getElementById('selection-counter');
        if (!counterEl) {
            counterEl = document.createElement('small');
            counterEl.id = 'selection-counter';
            counterEl.className = 'text-muted';
            selectAllBtn.parentNode.insertBefore(counterEl, selectAllBtn.nextSibling);
        }
        
        counterEl.textContent = `${selectedCount} de ${totalCount} tipos selecionados`;
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCounter);
    });
    
    updateCounter();
});
</script>
<?= $this->endSection() ?>