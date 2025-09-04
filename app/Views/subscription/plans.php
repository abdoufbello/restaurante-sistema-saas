<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('title') ?>Planos e Assinaturas<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Planos e Assinaturas</h1>
                    <p class="text-muted">Escolha o plano ideal para o seu restaurante</p>
                </div>
                <?php if (isset($currentSubscription) && $currentSubscription['status'] !== 'none'): ?>
                    <div class="text-end">
                        <span class="badge bg-<?= $currentSubscription['status'] === 'active' ? 'success' : ($currentSubscription['status'] === 'trial' ? 'info' : 'warning') ?> fs-6">
                            <?= ucfirst($currentSubscription['status']) ?>
                        </span>
                        <div class="small text-muted mt-1">
                            <?php if ($currentSubscription['days_remaining'] > 0): ?>
                                <?= $currentSubscription['days_remaining'] ?> dias restantes
                            <?php else: ?>
                                Expirado
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Current Usage Alert -->
    <?php if (isset($usageWarnings) && !empty($usageWarnings)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <?php foreach ($usageWarnings as $warning): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $warning['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Limit Exceeded Alert -->
    <?php if (session()->getFlashdata('limit_exceeded')): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-ban me-2"></i>
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Plans Comparison -->
    <div class="row">
        <?php foreach ($plans as $index => $plan): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 <?= $plan['popular'] ? 'border-primary' : '' ?> position-relative">
                    <?php if ($plan['popular']): ?>
                        <div class="position-absolute top-0 start-50 translate-middle">
                            <span class="badge bg-primary px-3 py-2">Mais Popular</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-header text-center bg-light">
                        <h4 class="card-title mb-0"><?= $plan['name'] ?></h4>
                        <div class="mt-3">
                            <span class="h2 text-primary">R$ <?= number_format($plan['price'], 2, ',', '.') ?></span>
                            <span class="text-muted">/mês</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Limits -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">Limites Inclusos:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-desktop text-primary me-2"></i>
                                    <?= $plan['max_totems'] === -1 ? 'Totems ilimitados' : $plan['max_totems'] . ' totems' ?>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                                    <?= $plan['max_orders_per_month'] === -1 ? 'Pedidos ilimitados' : number_format($plan['max_orders_per_month']) . ' pedidos/mês' ?>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <?= $plan['max_employees'] === -1 ? 'Funcionários ilimitados' : $plan['max_employees'] . ' funcionários' ?>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Features -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">Recursos:</h6>
                            <ul class="list-unstyled">
                                <?php foreach ($plan['features'] as $feature => $enabled): ?>
                                    <li class="mb-2">
                                        <?php if ($enabled): ?>
                                            <i class="fas fa-check text-success me-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times text-muted me-2"></i>
                                        <?php endif; ?>
                                        <span class="<?= $enabled ? '' : 'text-muted' ?>">
                                            <?= $this->include('subscription/partials/feature_name', ['feature' => $feature]) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card-footer text-center">
                        <?php if (isset($currentSubscription) && $currentSubscription['plan_slug'] === $plan['slug']): ?>
                            <button class="btn btn-outline-primary" disabled>
                                <i class="fas fa-check me-2"></i>Plano Atual
                            </button>
                        <?php elseif (!isset($currentSubscription) || $currentSubscription['status'] === 'none'): ?>
                            <?php if ($index === 0): ?>
                                <button class="btn btn-outline-primary" onclick="startTrial()">
                                    <i class="fas fa-play me-2"></i>Iniciar Trial Gratuito
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary" onclick="subscribeToPlan('<?= $plan['slug'] ?>')">
                                    <i class="fas fa-credit-card me-2"></i>Assinar Agora
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-primary" onclick="changePlan('<?= $plan['slug'] ?>')">
                                <?php if ($plan['price'] > ($currentPlan['price'] ?? 0)): ?>
                                    <i class="fas fa-arrow-up me-2"></i>Fazer Upgrade
                                <?php else: ?>
                                    <i class="fas fa-arrow-down me-2"></i>Fazer Downgrade
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Current Usage Stats -->
    <?php if (isset($currentUsage)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Uso Atual (<?= date('F Y') ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6 class="text-muted">Pedidos</h6>
                                    <div class="h4 text-primary"><?= number_format($currentUsage['orders_count']) ?></div>
                                    <?php if (isset($planLimits['max_orders_per_month']) && $planLimits['max_orders_per_month'] > 0): ?>
                                        <div class="progress mt-2">
                                            <?php $percentage = min(100, ($currentUsage['orders_count'] / $planLimits['max_orders_per_month']) * 100); ?>
                                            <div class="progress-bar <?= $percentage > 80 ? 'bg-warning' : 'bg-primary' ?>" 
                                                 style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= number_format($planLimits['max_orders_per_month']) ?> limite</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6 class="text-muted">Totems Ativos</h6>
                                    <div class="h4 text-primary"><?= $currentUsage['totems_used'] ?></div>
                                    <?php if (isset($planLimits['max_totems']) && $planLimits['max_totems'] > 0): ?>
                                        <div class="progress mt-2">
                                            <?php $percentage = min(100, ($currentUsage['totems_used'] / $planLimits['max_totems']) * 100); ?>
                                            <div class="progress-bar <?= $percentage > 80 ? 'bg-warning' : 'bg-primary' ?>" 
                                                 style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $planLimits['max_totems'] ?> limite</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6 class="text-muted">Funcionários</h6>
                                    <div class="h4 text-primary"><?= $currentUsage['employees_count'] ?></div>
                                    <?php if (isset($planLimits['max_employees']) && $planLimits['max_employees'] > 0): ?>
                                        <div class="progress mt-2">
                                            <?php $percentage = min(100, ($currentUsage['employees_count'] / $planLimits['max_employees']) * 100); ?>
                                            <div class="progress-bar <?= $percentage > 80 ? 'bg-warning' : 'bg-primary' ?>" 
                                                 style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $planLimits['max_employees'] ?> limite</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modals -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function startTrial() {
    showConfirm(
        'Deseja iniciar o trial gratuito de 30 dias do plano Professional?',
        function() {
            window.location.href = '<?= base_url('subscription/start-trial') ?>';
        }
    );
}

function subscribeToPlan(planSlug) {
    showConfirm(
        'Deseja assinar este plano? Você será redirecionado para o pagamento.',
        function() {
            window.location.href = '<?= base_url('subscription/subscribe') ?>/' + planSlug;
        }
    );
}

function changePlan(planSlug) {
    showConfirm(
        'Deseja alterar seu plano? As mudanças entrarão em vigor no próximo ciclo de cobrança.',
        function() {
            window.location.href = '<?= base_url('subscription/change-plan') ?>/' + planSlug;
        }
    );
}

function showConfirm(message, callback) {
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmAction').onclick = function() {
        $('#confirmModal').modal('hide');
        callback();
    };
    $('#confirmModal').modal('show');
}
</script>
<?= $this->endSection() ?>