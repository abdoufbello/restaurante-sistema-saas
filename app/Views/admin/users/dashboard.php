<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Gestão de Usuários<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Gestão de Usuários</h1>
            <p class="mb-0 text-muted">Gerencie usuários, roles e permissões do sistema</p>
        </div>
        <?php if (has_permission('users.create')): ?>
        <div>
            <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Novo Usuário
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Usuários
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['total_users'] ?? 0 ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Usuários Ativos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['active_users'] ?? 0 ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total de Roles
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['total_roles'] ?? 0 ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tag fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Logins Hoje
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['logins_today'] ?? 0 ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ações Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (has_permission('users.create')): ?>
                        <div class="col-md-3 mb-3">
                            <a href="<?= base_url('admin/users/create') ?>" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-user-plus mb-2"></i><br>
                                Criar Usuário
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (has_permission('users.manage_roles')): ?>
                        <div class="col-md-3 mb-3">
                            <a href="<?= base_url('admin/roles') ?>" class="btn btn-outline-success btn-block">
                                <i class="fas fa-user-tag mb-2"></i><br>
                                Gerenciar Roles
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (has_permission('users.manage_permissions')): ?>
                        <div class="col-md-3 mb-3">
                            <a href="<?= base_url('admin/permissions') ?>" class="btn btn-outline-info btn-block">
                                <i class="fas fa-key mb-2"></i><br>
                                Gerenciar Permissões
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (has_permission('users.export')): ?>
                        <div class="col-md-3 mb-3">
                            <a href="<?= base_url('admin/users/export') ?>" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-download mb-2"></i><br>
                                Exportar Dados
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Usuários Recentes</h6>
                    <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-primary">Ver Todos</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_users)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Avatar</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>
                                        <?php if ($user['avatar']): ?>
                                            <img src="<?= base_url('uploads/avatars/' . $user['avatar']) ?>" 
                                                 class="rounded-circle" width="40" height="40" alt="Avatar">
                                        <?php else: ?>
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px; color: white; font-weight: bold;">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($user['name']) ?></td>
                                    <td><?= esc($user['email']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= get_role_color($user['role']) ?> badge-pill">
                                            <i class="fas fa-<?= get_role_icon($user['role']) ?>"></i>
                                            <?= format_user_role($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (has_permission('users.read')): ?>
                                            <a href="<?= base_url('admin/users/view/' . $user['id']) ?>" 
                                               class="btn btn-info btn-sm" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (has_permission('users.update')): ?>
                                            <a href="<?= base_url('admin/users/edit/' . $user['id']) ?>" 
                                               class="btn btn-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">Nenhum usuário encontrado</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Role Distribution -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribuição de Roles</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($role_distribution)): ?>
                    <?php foreach ($role_distribution as $role): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="badge badge-<?= get_role_color($role['slug']) ?>">
                                <i class="fas fa-<?= get_role_icon($role['slug']) ?>"></i>
                                <?= esc($role['name']) ?>
                            </span>
                        </div>
                        <div>
                            <span class="font-weight-bold"><?= $role['user_count'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-user-tag fa-2x text-gray-300 mb-2"></i>
                        <p class="text-muted mb-0">Nenhuma role encontrada</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Atividade Recente</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_activity)): ?>
                    <div class="timeline">
                        <?php foreach ($recent_activity as $activity): ?>
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-<?= $activity['type'] === 'login' ? 'success' : 'info' ?> rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 32px; height: 32px;">
                                        <i class="fas fa-<?= $activity['type'] === 'login' ? 'sign-in-alt' : 'user' ?> text-white" style="font-size: 12px;"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="small text-muted"><?= time_ago($activity['created_at']) ?></div>
                                    <div class="fw-bold"><?= esc($activity['user_name']) ?></div>
                                    <div class="small"><?= esc($activity['description']) ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-clock fa-2x text-gray-300 mb-2"></i>
                        <p class="text-muted mb-0">Nenhuma atividade recente</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Setup System Modal -->
<?php if (is_super_admin() && empty($stats['total_roles'])): ?>
<div class="modal fade" id="setupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configuração Inicial</h5>
            </div>
            <div class="modal-body">
                <p>Parece que este é o primeiro acesso ao sistema. Deseja criar as roles e permissões padrão?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Agora Não</button>
                <a href="<?= base_url('admin/users/setup-system') ?>" class="btn btn-primary">Configurar Sistema</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Show setup modal if needed
    <?php if (is_super_admin() && empty($stats['total_roles'])): ?>
    $('#setupModal').modal('show');
    <?php endif; ?>
    
    // Auto refresh stats every 30 seconds
    setInterval(function() {
        $.get('<?= base_url('admin/users/ajax/stats') ?>', function(data) {
            if (data.success) {
                // Update stats cards
                $('.card .h5').each(function(index) {
                    const keys = ['total_users', 'active_users', 'total_roles', 'logins_today'];
                    if (keys[index] && data.stats[keys[index]] !== undefined) {
                        $(this).text(data.stats[keys[index]]);
                    }
                });
            }
        });
    }, 30000);
});

// Helper function for time ago (if not already defined)
function time_ago(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diff = Math.floor((now - past) / 1000);
    
    if (diff < 60) return 'agora mesmo';
    if (diff < 3600) return Math.floor(diff / 60) + ' min atrás';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h atrás';
    return Math.floor(diff / 86400) + 'd atrás';
}
</script>
<?= $this->endSection() ?>