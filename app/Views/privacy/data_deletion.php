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
                        <li class="breadcrumb-item active">Exclusão de Dados</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Warning Alert -->
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> Atenção - Ação Irreversível</h5>
                <p class="mb-0">
                    A exclusão de dados é uma ação permanente e irreversível. Certifique-se de que realmente 
                    deseja prosseguir antes de confirmar a solicitação.
                </p>
            </div>

            <!-- Data Deletion Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-trash"></i> Solicitar Exclusão de Dados
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Direito ao Esquecimento - LGPD</h6>
                        <p class="mb-0">
                            Você tem o direito de solicitar a exclusão dos seus dados pessoais quando não 
                            houver mais necessidade para o tratamento. Alguns dados essenciais podem ser 
                            mantidos por obrigações legais.
                        </p>
                    </div>

                    <form id="deletionForm">
                        <div class="form-group">
                            <label class="font-weight-bold mb-3">Selecione os tipos de dados que deseja excluir:</label>
                            
                            <div class="row">
                                <?php foreach ($deletion_types as $key => $data): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" 
                                               id="delete_<?= $key ?>" name="data_types[]" value="<?= $key ?>"
                                               <?= $data['required'] ? 'disabled title="Dados obrigatórios não podem ser excluídos"' : '' ?>>
                                        <label class="custom-control-label <?= $data['required'] ? 'text-muted' : '' ?>" 
                                               for="delete_<?= $key ?>">
                                            <strong><?= $data['label'] ?></strong>
                                            <?php if ($data['required']): ?>
                                                <span class="badge badge-secondary ml-2">Obrigatório</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning ml-2">Opcional</span>
                                            <?php endif; ?>
                                            <small class="d-block text-muted">
                                                <?= $data['description'] ?>
                                            </small>
                                            <?php if ($data['required']): ?>
                                                <small class="d-block text-danger">
                                                    <i class="fas fa-lock"></i> <?= $data['retention_reason'] ?>
                                                </small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="deletion_reason" class="font-weight-bold">Motivo da solicitação:</label>
                            <select class="form-control" id="deletion_reason" name="reason" required>
                                <option value="">Selecione um motivo</option>
                                <option value="no_longer_needed">Os dados não são mais necessários</option>
                                <option value="withdraw_consent">Retirada do consentimento</option>
                                <option value="unlawful_processing">Tratamento ilícito</option>
                                <option value="legal_obligation">Obrigação legal de exclusão</option>
                                <option value="other">Outro motivo</option>
                            </select>
                        </div>

                        <div class="form-group" id="other_reason_group" style="display: none;">
                            <label for="other_reason" class="font-weight-bold">Especifique o motivo:</label>
                            <textarea class="form-control" id="other_reason" name="other_reason" 
                                      rows="3" placeholder="Descreva o motivo da solicitação..."></textarea>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="understand_consequences" required>
                                <label class="custom-control-label" for="understand_consequences">
                                    <strong>Entendo as consequências da exclusão:</strong>
                                    <ul class="mt-2 mb-0">
                                        <li>A exclusão é permanente e irreversível</li>
                                        <li>Alguns dados podem ser mantidos por obrigações legais</li>
                                        <li>Posso perder acesso a funcionalidades do sistema</li>
                                        <li>Histórico de transações pode ser mantido para fins fiscais</li>
                                    </ul>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="confirm_identity" required>
                                <label class="custom-control-label" for="confirm_identity">
                                    Confirmo que sou o titular dos dados e tenho autoridade para solicitar esta exclusão.
                                </label>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-danger btn-lg px-5">
                                <i class="fas fa-trash"></i> Solicitar Exclusão
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Deletion Status -->
            <?php if (!empty($pending_deletions)): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-clock"></i> Solicitações de Exclusão
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($pending_deletions as $deletion): ?>
                    <div class="deletion-item mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    Exclusão #<?= $deletion['id'] ?>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'under_review' => 'info',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'completed' => 'dark'
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Pendente',
                                        'under_review' => 'Em Análise',
                                        'approved' => 'Aprovada',
                                        'rejected' => 'Rejeitada',
                                        'completed' => 'Concluída'
                                    ];
                                    ?>
                                    <span class="badge badge-<?= $statusColors[$deletion['status']] ?>">
                                        <?= $statusLabels[$deletion['status']] ?>
                                    </span>
                                </h6>
                                <small class="text-muted">
                                    Solicitada em <?= date('d/m/Y H:i', strtotime($deletion['created_at'])) ?>
                                </small>
                                <?php if (!empty($deletion['admin_notes'])): ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <strong>Observações:</strong> <?= esc($deletion['admin_notes']) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                <?php if ($deletion['status'] === 'approved'): ?>
                                <div class="mt-2">
                                    <small class="text-success">
                                        <i class="fas fa-check"></i> 
                                        Exclusão será processada em até 30 dias
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
                            As solicitações são analisadas em até 15 dias. A exclusão efetiva 
                            pode levar até 30 dias após aprovação.
                        </p>
                    </div>

                    <div class="info-item mb-3">
                        <h6 class="text-warning mb-2">
                            <i class="fas fa-shield-alt"></i> Dados Obrigatórios
                        </h6>
                        <p class="small text-muted mb-0">
                            Alguns dados são mantidos por obrigações legais (fiscais, trabalhistas) 
                            e não podem ser excluídos imediatamente.
                        </p>
                    </div>

                    <div class="info-item mb-3">
                        <h6 class="text-danger mb-2">
                            <i class="fas fa-exclamation-triangle"></i> Consequências
                        </h6>
                        <p class="small text-muted mb-0">
                            A exclusão pode resultar na perda de acesso ao sistema e 
                            funcionalidades. Esta ação é irreversível.
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

            <!-- Legal Information -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">
                        <i class="fas fa-balance-scale"></i> Base Legal
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small text-muted">
                        <p class="mb-2">
                            <strong>LGPD Art. 18, VI:</strong> Direito à eliminação dos dados pessoais 
                            tratados com o consentimento do titular.
                        </p>
                        <p class="mb-2">
                            <strong>Prazo de Resposta:</strong> 15 dias corridos conforme Art. 19 da LGPD.
                        </p>
                        <p class="mb-0">
                            <strong>Retenção Legal:</strong> Alguns dados podem ser mantidos por 
                            obrigações fiscais (5 anos) e trabalhistas (30 anos).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deletionForm = document.getElementById('deletionForm');
    const reasonSelect = document.getElementById('deletion_reason');
    const otherReasonGroup = document.getElementById('other_reason_group');
    const checkboxes = deletionForm.querySelectorAll('input[name="data_types[]"]');
    
    // Show/hide other reason field
    reasonSelect.addEventListener('change', function() {
        if (this.value === 'other') {
            otherReasonGroup.style.display = 'block';
            document.getElementById('other_reason').required = true;
        } else {
            otherReasonGroup.style.display = 'none';
            document.getElementById('other_reason').required = false;
        }
    });
    
    // Add select optional button
    const selectOptionalBtn = document.createElement('button');
    selectOptionalBtn.type = 'button';
    selectOptionalBtn.className = 'btn btn-sm btn-outline-warning mb-3';
    selectOptionalBtn.innerHTML = '<i class="fas fa-check"></i> Selecionar Dados Opcionais';
    
    const firstCheckbox = checkboxes[0].closest('.col-md-6');
    firstCheckbox.parentNode.insertBefore(selectOptionalBtn, firstCheckbox);
    
    selectOptionalBtn.addEventListener('click', function() {
        const optionalCheckboxes = Array.from(checkboxes).filter(cb => !cb.disabled);
        const allOptionalChecked = optionalCheckboxes.every(cb => cb.checked);
        
        optionalCheckboxes.forEach(cb => cb.checked = !allOptionalChecked);
        
        this.innerHTML = allOptionalChecked ? 
            '<i class="fas fa-check"></i> Selecionar Dados Opcionais' : 
            '<i class="fas fa-times"></i> Desmarcar Opcionais';
        
        updateCounter();
    });
    
    deletionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedTypes = Array.from(checkboxes).filter(cb => cb.checked && !cb.disabled);
        
        if (selectedTypes.length === 0) {
            showAlert('warning', 'Selecione pelo menos um tipo de dados para excluir.');
            return;
        }
        
        // Show confirmation modal
        showConfirmationModal(selectedTypes);
    });
    
    function showConfirmationModal(selectedTypes) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Confirmar Exclusão de Dados
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-warning"></i> Esta ação é irreversível!</h6>
                            <p class="mb-0">
                                Você está prestes a solicitar a exclusão permanente dos seguintes dados:
                            </p>
                        </div>
                        
                        <ul class="list-group mb-3">
                            ${selectedTypes.map(cb => {
                                const label = cb.closest('.custom-control').querySelector('strong').textContent;
                                return `<li class="list-group-item">
                                    <i class="fas fa-trash text-danger"></i> ${label}
                                </li>`;
                            }).join('')}
                        </ul>
                        
                        <div class="alert alert-warning">
                            <h6>Consequências da exclusão:</h6>
                            <ul class="mb-0">
                                <li>Os dados serão permanentemente removidos</li>
                                <li>Você pode perder acesso a funcionalidades</li>
                                <li>Relatórios históricos podem ser afetados</li>
                                <li>Esta ação não pode ser desfeita</li>
                            </ul>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="final_confirmation">
                                <label class="custom-control-label" for="final_confirmation">
                                    <strong>Confirmo que entendo as consequências e desejo prosseguir com a exclusão</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" id="confirm-deletion">
                            <i class="fas fa-trash"></i> Confirmar Exclusão
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        $(modal).modal('show');
        
        // Handle confirmation
        modal.querySelector('#confirm-deletion').addEventListener('click', function() {
            const finalConfirmation = modal.querySelector('#final_confirmation').checked;
            
            if (!finalConfirmation) {
                showAlert('warning', 'Você deve confirmar que entende as consequências.');
                return;
            }
            
            $(modal).modal('hide');
            submitDeletionRequest();
        });
        
        // Clean up modal after hide
        $(modal).on('hidden.bs.modal', function() {
            modal.remove();
        });
    }
    
    function submitDeletionRequest() {
        const formData = new FormData(deletionForm);
        const submitBtn = deletionForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        
        fetch('/privacy/request-deletion', {
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
                deletionForm.reset();
                updateCounter();
                
                // Reload page after delay to show new request
                setTimeout(() => {
                    location.reload();
                }, 3000);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Erro ao solicitar exclusão. Tente novamente.');
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
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
        const selectedCount = Array.from(checkboxes).filter(cb => cb.checked && !cb.disabled).length;
        const optionalCount = Array.from(checkboxes).filter(cb => !cb.disabled).length;
        
        let counterEl = document.getElementById('selection-counter');
        if (!counterEl) {
            counterEl = document.createElement('small');
            counterEl.id = 'selection-counter';
            counterEl.className = 'text-muted d-block mt-1';
            selectOptionalBtn.parentNode.insertBefore(counterEl, selectOptionalBtn.nextSibling);
        }
        
        counterEl.textContent = `${selectedCount} de ${optionalCount} tipos opcionais selecionados`;
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCounter);
    });
    
    updateCounter();
});
</script>
<?= $this->endSection() ?>