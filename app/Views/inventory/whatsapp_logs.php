<?php $this->load->view('partial/header'); ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="fab fa-whatsapp text-success me-2"></i>
                    Logs do WhatsApp Business
                </h4>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshLogs()">
                        <i class="fas fa-sync-alt"></i> Atualizar
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="exportLogs()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="showStatistics()">
                        <i class="fas fa-chart-bar"></i> Estatísticas
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Estatísticas Resumidas -->
                <div class="row mb-4" id="stats-summary">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-1" id="total-messages">0</h3>
                                <p class="mb-0">Total de Mensagens</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-1" id="success-rate">0%</h3>
                                <p class="mb-0">Taxa de Sucesso</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-1" id="today-messages">0</h3>
                                <p class="mb-0">Mensagens Hoje</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-1" id="failed-messages">0</h3>
                                <p class="mb-0">Mensagens Falharam</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="filter-form" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Período</label>
                                <select class="form-select" name="period" id="period-filter">
                                    <option value="today">Hoje</option>
                                    <option value="yesterday">Ontem</option>
                                    <option value="week" selected>Última Semana</option>
                                    <option value="month">Último Mês</option>
                                    <option value="custom">Personalizado</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2" id="date-from-group" style="display: none;">
                                <label class="form-label">Data Inicial</label>
                                <input type="date" class="form-control" name="date_from" id="date-from">
                            </div>
                            
                            <div class="col-md-2" id="date-to-group" style="display: none;">
                                <label class="form-label">Data Final</label>
                                <input type="date" class="form-control" name="date_to" id="date-to">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="message_type">
                                    <option value="">Todos</option>
                                    <option value="outbound">Enviadas</option>
                                    <option value="inbound">Recebidas</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">Todos</option>
                                    <option value="pending">Pendente</option>
                                    <option value="sent">Enviada</option>
                                    <option value="delivered">Entregue</option>
                                    <option value="read">Lida</option>
                                    <option value="failed">Falhou</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Fornecedor</label>
                                <select class="form-select" name="supplier_id">
                                    <option value="">Todos os Fornecedores</option>
                                    <?php if (isset($suppliers)): ?>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['person_id'] ?>">
                                                <?= htmlspecialchars($supplier['company_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Buscar</label>
                                <input type="text" class="form-control" name="search" placeholder="Telefone, mensagem...">
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabela de Logs -->
                <div class="table-responsive">
                    <table class="table table-striped" id="logs-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora</th>
                                <th>Telefone</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Fornecedor</th>
                                <th>Compra</th>
                                <th>Mensagem</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="logs-tbody">
                            <!-- Dados carregados via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span id="pagination-info">Mostrando 0 de 0 registros</span>
                    </div>
                    <nav>
                        <ul class="pagination mb-0" id="pagination-controls">
                            <!-- Controles de paginação carregados via AJAX -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes da Mensagem -->
<div class="modal fade" id="messageDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Mensagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="message-details-content">
                <!-- Conteúdo carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="resendMessage()" id="resend-btn" style="display: none;">
                    <i class="fab fa-whatsapp"></i> Reenviar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Estatísticas -->
<div class="modal fade" id="statisticsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Estatísticas do WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="messagesChart" width="400" height="200"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6>Top Fornecedores por Mensagens</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="top-suppliers-table">
                                <thead>
                                    <tr>
                                        <th>Fornecedor</th>
                                        <th>Total de Mensagens</th>
                                    </tr>
                                </thead>
                                <tbody id="top-suppliers-tbody">
                                    <!-- Dados carregados via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentFilters = {};
let selectedMessageId = null;

$(document).ready(function() {
    loadLogs();
    loadStatsSummary();
    
    // Event listeners
    $('#period-filter').change(function() {
        if ($(this).val() === 'custom') {
            $('#date-from-group, #date-to-group').show();
        } else {
            $('#date-from-group, #date-to-group').hide();
        }
    });
    
    $('#filter-form').submit(function(e) {
        e.preventDefault();
        currentPage = 1;
        loadLogs();
    });
});

/**
 * Carrega os logs de mensagens
 */
function loadLogs(page = 1) {
    currentPage = page;
    const formData = new FormData($('#filter-form')[0]);
    formData.append('page', page);
    
    // Converter FormData para objeto
    currentFilters = {};
    for (let [key, value] of formData.entries()) {
        if (value) currentFilters[key] = value;
    }
    
    $.ajax({
        url: '<?= base_url('inventory/get_whatsapp_logs') ?>',
        method: 'POST',
        data: currentFilters,
        dataType: 'json',
        beforeSend: function() {
            $('#logs-tbody').html('<tr><td colspan="9" class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>');
        },
        success: function(response) {
            if (response.success) {
                renderLogsTable(response.data.logs);
                renderPagination(response.data.pagination);
            } else {
                showAlert('Erro ao carregar logs: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Erro ao carregar logs', 'danger');
        }
    });
}

/**
 * Renderiza a tabela de logs
 */
function renderLogsTable(logs) {
    let html = '';
    
    if (logs.length === 0) {
        html = '<tr><td colspan="9" class="text-center text-muted">Nenhum log encontrado</td></tr>';
    } else {
        logs.forEach(function(log) {
            const statusBadge = getStatusBadge(log.status);
            const typeBadge = getTypeBadge(log.message_type);
            const messagePreview = log.message.length > 50 ? log.message.substring(0, 50) + '...' : log.message;
            
            html += `
                <tr>
                    <td>${log.id}</td>
                    <td>${formatDateTime(log.created_at)}</td>
                    <td>
                        <a href="https://wa.me/${log.phone_number}" target="_blank" class="text-decoration-none">
                            <i class="fab fa-whatsapp text-success"></i> ${formatPhoneNumber(log.phone_number)}
                        </a>
                    </td>
                    <td>${typeBadge}</td>
                    <td>${statusBadge}</td>
                    <td>${log.supplier_name || '-'}</td>
                    <td>${log.purchase_reference || '-'}</td>
                    <td>
                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="${log.message}">
                            ${messagePreview}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="showMessageDetails(${log.id})" title="Ver Detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${log.status === 'failed' ? `
                            <button class="btn btn-sm btn-outline-success" onclick="resendMessage(${log.id})" title="Reenviar">
                                <i class="fas fa-redo"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
        });
    }
    
    $('#logs-tbody').html(html);
}

/**
 * Renderiza os controles de paginação
 */
function renderPagination(pagination) {
    $('#pagination-info').text(`Mostrando ${pagination.showing_from} a ${pagination.showing_to} de ${pagination.total_records} registros`);
    
    let html = '';
    
    // Botão Anterior
    if (pagination.current_page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogs(${pagination.current_page - 1})">Anterior</a></li>`;
    }
    
    // Páginas
    for (let i = pagination.start_page; i <= pagination.end_page; i++) {
        const active = i === pagination.current_page ? 'active' : '';
        html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadLogs(${i})">${i}</a></li>`;
    }
    
    // Botão Próximo
    if (pagination.current_page < pagination.total_pages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogs(${pagination.current_page + 1})">Próximo</a></li>`;
    }
    
    $('#pagination-controls').html(html);
}

/**
 * Carrega estatísticas resumidas
 */
function loadStatsSummary() {
    $.ajax({
        url: '<?= base_url('inventory/get_whatsapp_stats_summary') ?>',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#total-messages').text(stats.total_messages || 0);
                $('#success-rate').text((stats.success_rate || 0) + '%');
                $('#today-messages').text(stats.today_messages || 0);
                $('#failed-messages').text(stats.failed_messages || 0);
            }
        }
    });
}

/**
 * Mostra detalhes da mensagem
 */
function showMessageDetails(logId) {
    selectedMessageId = logId;
    
    $.ajax({
        url: '<?= base_url('inventory/get_whatsapp_log_details') ?>',
        method: 'POST',
        data: { log_id: logId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderMessageDetails(response.data);
                $('#messageDetailsModal').modal('show');
            } else {
                showAlert('Erro ao carregar detalhes: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Erro ao carregar detalhes da mensagem', 'danger');
        }
    });
}

/**
 * Renderiza os detalhes da mensagem
 */
function renderMessageDetails(log) {
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Informações Gerais</h6>
                <table class="table table-sm">
                    <tr><td><strong>ID:</strong></td><td>${log.id}</td></tr>
                    <tr><td><strong>Telefone:</strong></td><td>${formatPhoneNumber(log.phone_number)}</td></tr>
                    <tr><td><strong>Tipo:</strong></td><td>${getTypeBadge(log.message_type)}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>${getStatusBadge(log.status)}</td></tr>
                    <tr><td><strong>Fornecedor:</strong></td><td>${log.supplier_name || '-'}</td></tr>
                    <tr><td><strong>Compra:</strong></td><td>${log.purchase_reference || '-'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Timestamps</h6>
                <table class="table table-sm">
                    <tr><td><strong>Criado em:</strong></td><td>${formatDateTime(log.created_at)}</td></tr>
                    <tr><td><strong>Enviado em:</strong></td><td>${log.sent_at ? formatDateTime(log.sent_at) : '-'}</td></tr>
                    <tr><td><strong>Entregue em:</strong></td><td>${log.delivered_at ? formatDateTime(log.delivered_at) : '-'}</td></tr>
                    <tr><td><strong>Lido em:</strong></td><td>${log.read_at ? formatDateTime(log.read_at) : '-'}</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <h6>Mensagem</h6>
                <div class="border p-3 bg-light rounded">
                    <pre class="mb-0">${log.message}</pre>
                </div>
            </div>
        </div>
        ${log.error_message ? `
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6 class="text-danger">Erro</h6>
                    <div class="alert alert-danger">
                        ${log.error_message}
                    </div>
                </div>
            </div>
        ` : ''}
        ${log.metadata ? `
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6>Metadados</h6>
                    <pre class="bg-light p-2 rounded">${JSON.stringify(JSON.parse(log.metadata), null, 2)}</pre>
                </div>
            </div>
        ` : ''}
    `;
    
    $('#message-details-content').html(html);
    
    // Mostrar botão de reenvio se a mensagem falhou
    if (log.status === 'failed') {
        $('#resend-btn').show();
    } else {
        $('#resend-btn').hide();
    }
}

/**
 * Reenvia uma mensagem
 */
function resendMessage(logId = null) {
    const messageId = logId || selectedMessageId;
    
    if (!messageId) {
        showAlert('ID da mensagem não encontrado', 'danger');
        return;
    }
    
    if (!confirm('Deseja realmente reenviar esta mensagem?')) {
        return;
    }
    
    $.ajax({
        url: '<?= base_url('inventory/resend_whatsapp_message') ?>',
        method: 'POST',
        data: { log_id: messageId },
        dataType: 'json',
        beforeSend: function() {
            $('#resend-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Reenviando...');
        },
        success: function(response) {
            if (response.success) {
                showAlert('Mensagem reenviada com sucesso!', 'success');
                $('#messageDetailsModal').modal('hide');
                loadLogs(currentPage);
            } else {
                showAlert('Erro ao reenviar mensagem: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Erro ao reenviar mensagem', 'danger');
        },
        complete: function() {
            $('#resend-btn').prop('disabled', false).html('<i class="fab fa-whatsapp"></i> Reenviar');
        }
    });
}

/**
 * Mostra estatísticas detalhadas
 */
function showStatistics() {
    $.ajax({
        url: '<?= base_url('inventory/get_whatsapp_statistics') ?>',
        method: 'POST',
        data: currentFilters,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderStatistics(response.data);
                $('#statisticsModal').modal('show');
            } else {
                showAlert('Erro ao carregar estatísticas: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Erro ao carregar estatísticas', 'danger');
        }
    });
}

/**
 * Renderiza as estatísticas
 */
function renderStatistics(stats) {
    // Gráfico de mensagens por dia
    const messagesCtx = document.getElementById('messagesChart').getContext('2d');
    new Chart(messagesCtx, {
        type: 'line',
        data: {
            labels: stats.daily.map(d => d.date),
            datasets: [{
                label: 'Total de Mensagens',
                data: stats.daily.map(d => d.total),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Mensagens Bem-sucedidas',
                data: stats.daily.map(d => d.successful),
                borderColor: 'rgb(54, 162, 235)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Mensagens por Dia'
                }
            }
        }
    });
    
    // Gráfico de status
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Enviadas', 'Entregues', 'Lidas', 'Falharam'],
            datasets: [{
                data: [
                    stats.general.sent_count,
                    stats.general.delivered_count,
                    stats.general.read_count,
                    stats.general.failed_count
                ],
                backgroundColor: [
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 99, 132)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição por Status'
                }
            }
        }
    });
    
    // Tabela de top fornecedores
    let suppliersHtml = '';
    stats.top_suppliers.forEach(function(supplier) {
        suppliersHtml += `
            <tr>
                <td>${supplier.company_name}</td>
                <td>${supplier.message_count}</td>
            </tr>
        `;
    });
    $('#top-suppliers-tbody').html(suppliersHtml);
}

/**
 * Exporta logs
 */
function exportLogs() {
    const params = new URLSearchParams(currentFilters);
    window.open('<?= base_url('inventory/export_whatsapp_logs') ?>?' + params.toString(), '_blank');
}

/**
 * Atualiza os logs
 */
function refreshLogs() {
    loadLogs(currentPage);
    loadStatsSummary();
}

/**
 * Limpa os filtros
 */
function clearFilters() {
    $('#filter-form')[0].reset();
    $('#date-from-group, #date-to-group').hide();
    currentPage = 1;
    loadLogs();
}

/**
 * Retorna badge do status
 */
function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning">Pendente</span>',
        'sent': '<span class="badge bg-info">Enviada</span>',
        'delivered': '<span class="badge bg-success">Entregue</span>',
        'read': '<span class="badge bg-primary">Lida</span>',
        'failed': '<span class="badge bg-danger">Falhou</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Desconhecido</span>';
}

/**
 * Retorna badge do tipo
 */
function getTypeBadge(type) {
    const badges = {
        'outbound': '<span class="badge bg-primary">Enviada</span>',
        'inbound': '<span class="badge bg-success">Recebida</span>'
    };
    return badges[type] || '<span class="badge bg-secondary">Desconhecido</span>';
}

/**
 * Formata data e hora
 */
function formatDateTime(datetime) {
    if (!datetime) return '-';
    const date = new Date(datetime);
    return date.toLocaleString('pt-BR');
}

/**
 * Formata número de telefone
 */
function formatPhoneNumber(phone) {
    if (!phone) return '-';
    // Formato brasileiro: +55 (11) 99999-9999
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 13 && cleaned.startsWith('55')) {
        return `+55 (${cleaned.substr(2, 2)}) ${cleaned.substr(4, 5)}-${cleaned.substr(9, 4)}`;
    }
    return phone;
}

/**
 * Mostra alerta
 */
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove alertas existentes
    $('.alert').remove();
    
    // Adiciona novo alerta
    $('.card-body').first().prepend(alertHtml);
    
    // Remove automaticamente após 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>

<?php $this->load->view('partial/footer'); ?>