<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('title') ?>Minha Assinatura<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Minha Assinatura</h1>
                    <p class="text-muted">Gerencie sua assinatura e monitore o uso</p>
                </div>
                <div>
                    <a href="<?= base_url('subscription/plans') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-2"></i>Ver Planos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if ($subscription['status'] === 'active'): ?>
                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-check text-white fa-2x"></i>
                                        </div>
                                    <?php elseif ($subscription['status'] === 'trial'): ?>
                                        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-clock text-white fa-2x"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-exclamation text-white fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="mb-1"><?= $subscription['plan_name'] ?></h4>
                                    <p class="text-muted mb-0">
                                        Status: 
                                        <span class="badge bg-<?= $subscription['status'] === 'active' ? 'success' : ($subscription['status'] === 'trial' ? 'info' : 'warning') ?>">
                                            <?= ucfirst($subscription['status']) ?>
                                        </span>
                                    </p>
                                    <?php if ($subscription['expires_at']): ?>
                                        <small class="text-muted">
                                            <?php if ($subscription['status'] === 'trial'): ?>
                                                Trial expira em: <?= date('d/m/Y', strtotime($subscription['expires_at'])) ?>
                                            <?php else: ?>
                                                Próxima cobrança: <?= date('d/m/Y', strtotime($subscription['next_payment'])) ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($subscription['status'] === 'trial'): ?>
                                <a href="<?= base_url('subscription/plans') ?>" class="btn btn-primary">
                                    <i class="fas fa-credit-card me-2"></i>Assinar Agora
                                </a>
                            <?php elseif ($subscription['status'] === 'active'): ?>
                                <div class="btn-group">
                                    <a href="<?= base_url('subscription/plans') ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-exchange-alt me-2"></i>Alterar Plano
                                    </a>
                                    <button class="btn btn-outline-danger" onclick="cancelSubscription()">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </button>
                                </div>
                            <?php else: ?>
                                <a href="<?= base_url('subscription/plans') ?>" class="btn btn-primary">
                                    <i class="fas fa-play me-2"></i>Ativar Plano
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Overview -->
    <?php if (isset($usage)): ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-shopping-cart fa-3x text-primary"></i>
                        </div>
                        <h4 class="text-primary"><?= number_format($usage['orders_count']) ?></h4>
                        <p class="text-muted mb-0">Pedidos este mês</p>
                        <?php if (isset($limits['max_orders_per_month']) && $limits['max_orders_per_month'] > 0): ?>
                            <div class="progress mt-2">
                                <?php $percentage = min(100, ($usage['orders_count'] / $limits['max_orders_per_month']) * 100); ?>
                                <div class="progress-bar <?= $percentage > 80 ? 'bg-warning' : 'bg-primary' ?>" 
                                     style="width: <?= $percentage ?>%"></div>
                            </div>
                            <small class="text-muted">de <?= number_format($limits['max_orders_per_month']) ?></small>
                        <?php else: ?>
                            <small class="text-success">Ilimitado</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-desktop fa-3x text-primary"></i>
                        </div>
                        <h4 class="text-primary"><?= $usage['totems_used'] ?></h4>
                        <p class="text-muted mb-0">Totems ativos</p>
                        <?php if (isset($limits['max_totems']) && $limits['max_totems'] > 0): ?>
                            <div class="progress mt-2">
                                <?php $percentage = min(100, ($usage['totems_used'] / $limits['max_totems']) * 100); ?>
                                <div class="progress-bar <?= $percentage > 80 ? 'bg-warning' : 'bg-primary' ?>" 
                                     style="width: <?= $percentage ?>%"></div>
                            </div>
                            <small class="text-muted">de <?= $limits['max_totems'] ?></small>
                        <?php else: ?>
                            <small class="text-success">Ilimitado</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-users fa-3x text-primary"></i>
                        </div>
                        <h4 class="text-primary"><?= $usage['employees_count'] ?></h4>
                        <p class="text-muted mb-0">Funcionários</p>
                        <?php if (isset($limits['max_employees']) && $limits['max_employees'] > 0): ?>
                            <div class="progress mt-2">
                                <?php $percentage = min(100, ($usage['employees_count'] / $limits['max_employees']) * 100); ?>
                                <div class="progress-bar <?= $percentage > 80 ? 'bg-warning' : 'bg-primary' ?>" 
                                     style="width: <?= $percentage ?>%"></div>
                            </div>
                            <small class="text-muted">de <?= $limits['max_employees'] ?></small>
                        <?php else: ?>
                            <small class="text-success">Ilimitado</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Usage History -->
    <?php if (isset($usageHistory) && !empty($usageHistory)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Histórico de Uso
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Período</th>
                                        <th>Pedidos</th>
                                        <th>Totems</th>
                                        <th>Funcionários</th>
                                        <th>Chamadas API</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usageHistory as $history): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $months = [
                                                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                                ];
                                                echo $months[$history['month']] . ' ' . $history['year'];
                                                ?>
                                            </td>
                                            <td><?= number_format($history['orders_count']) ?></td>
                                            <td><?= $history['totems_used'] ?></td>
                                            <td><?= $history['employees_count'] ?></td>
                                            <td><?= number_format($history['api_calls']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Subscription Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Assinatura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja cancelar sua assinatura?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atenção:</strong> Sua assinatura permanecerá ativa até o final do período pago, 
                    mas não será renovada automaticamente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Manter Assinatura</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancel()">Cancelar Assinatura</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function cancelSubscription() {
    $('#cancelModal').modal('show');
}

function confirmCancel() {
    $('#cancelModal').modal('hide');
    
    // Show loading
    const btn = document.querySelector('[onclick="confirmCancel()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cancelando...';
    btn.disabled = true;
    
    // Submit cancellation
    fetch('<?= base_url('subscription/cancel') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erro ao cancelar assinatura: ' + data.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        alert('Erro ao cancelar assinatura. Tente novamente.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>
<?= $this->endSection() ?>