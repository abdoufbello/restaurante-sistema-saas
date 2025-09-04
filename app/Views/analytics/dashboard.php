<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
                <div class="d-flex align-items-center">
                    <!-- Period Selector -->
                    <div class="dropdown mr-3">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                id="periodDropdown" data-toggle="dropdown">
                            <i class="fas fa-calendar"></i> 
                            <?= $available_periods[$current_period] ?? 'Período' ?>
                        </button>
                        <div class="dropdown-menu">
                            <?php foreach ($available_periods as $key => $label): ?>
                            <a class="dropdown-item <?= $key === $current_period ? 'active' : '' ?>" 
                               href="?period=<?= $key ?>">
                                <?= $label ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Export Button -->
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" 
                                id="exportDropdown" data-toggle="dropdown">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="/analytics/export?period=<?= $current_period ?>&format=csv">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                            <a class="dropdown-item" href="/analytics/export?period=<?= $current_period ?>&format=pdf">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <a class="dropdown-item" href="/analytics/export?period=<?= $current_period ?>&format=json">
                                <i class="fas fa-file-code"></i> JSON
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
    </div>
    <?php else: ?>
    
    <!-- Real-time Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Pedidos Hoje
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="today-orders">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
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
                                Receita Hoje
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="today-revenue">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                                Pedidos Pendentes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pending-orders">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Última Hora
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="last-hour-orders">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Analytics Cards -->
    <div class="row mb-4">
        <!-- Sales Overview -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Vendas - <?= $available_periods[$current_period] ?></h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="refreshSalesChart()">Atualizar</a>
                            <a class="dropdown-item" href="#" onclick="exportChart('sales')">Exportar</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="salesChart" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Summary -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Resumo de Vendas</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($analytics['sales'])): ?>
                    <div class="mb-3">
                        <div class="small text-gray-500">Receita Total</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                            R$ <?= number_format($analytics['sales']['total_revenue'], 2, ',', '.') ?>
                            <?php if ($analytics['sales']['revenue_growth'] != 0): ?>
                            <span class="badge badge-<?= $analytics['sales']['revenue_growth'] > 0 ? 'success' : 'danger' ?> ml-2">
                                <i class="fas fa-arrow-<?= $analytics['sales']['revenue_growth'] > 0 ? 'up' : 'down' ?>"></i>
                                <?= abs($analytics['sales']['revenue_growth']) ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-gray-500">Total de Pedidos</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($analytics['sales']['total_orders']) ?>
                            <?php if ($analytics['sales']['order_growth'] != 0): ?>
                            <span class="badge badge-<?= $analytics['sales']['order_growth'] > 0 ? 'success' : 'danger' ?> ml-2">
                                <i class="fas fa-arrow-<?= $analytics['sales']['order_growth'] > 0 ? 'up' : 'down' ?>"></i>
                                <?= abs($analytics['sales']['order_growth']) ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="small text-gray-500">Ticket Médio</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                            R$ <?= number_format($analytics['sales']['average_order_value'], 2, ',', '.') ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($analytics['sales']['peak_hours'])): ?>
                    <div>
                        <div class="small text-gray-500 mb-2">Horários de Pico</div>
                        <?php foreach (array_slice($analytics['sales']['peak_hours'], 0, 3) as $hour): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?= $hour['hour'] ?>:00h</span>
                            <span class="badge badge-primary"><?= $hour['orders'] ?> pedidos</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Items & Customer Analytics -->
    <div class="row mb-4">
        <!-- Popular Items -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pratos Mais Populares</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($analytics['popular_items']['popular_items']) && !empty($analytics['popular_items']['popular_items'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Prato</th>
                                    <th class="text-center">Vendidos</th>
                                    <th class="text-right">Receita</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['popular_items']['popular_items'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= esc($item['name']) ?></strong>
                                        <br><small class="text-muted">R$ <?= number_format($item['price'], 2, ',', '.') ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $item['total_sold'] ?></span>
                                    </td>
                                    <td class="text-right">
                                        <strong>R$ <?= number_format($item['revenue'], 2, ',', '.') ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-utensils fa-3x mb-3"></i>
                        <p>Nenhum dado de pratos disponível para o período selecionado.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Customer Analytics -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Analytics de Clientes</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($analytics['customers'])): ?>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <div class="h2 mb-0 font-weight-bold text-primary">
                                    <?= $analytics['customers']['total_customers'] ?>
                                </div>
                                <div class="small text-gray-500">Total de Clientes</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <div class="h2 mb-0 font-weight-bold text-success">
                                    <?= $analytics['customers']['customer_retention_rate'] ?>%
                                </div>
                                <div class="small text-gray-500">Taxa de Retenção</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <div class="h4 mb-0 font-weight-bold text-info">
                                    <?= $analytics['customers']['new_customers'] ?>
                                </div>
                                <div class="small text-gray-500">Novos Clientes</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <div class="h4 mb-0 font-weight-bold text-warning">
                                    <?= $analytics['customers']['returning_customers'] ?>
                                </div>
                                <div class="small text-gray-500">Clientes Recorrentes</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($analytics['customers']['order_frequency'])): ?>
                    <div class="mt-3">
                        <div class="small text-gray-500 mb-2">Frequência de Pedidos</div>
                        <?php foreach ($analytics['customers']['order_frequency'] as $freq): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?= $freq['frequency_range'] ?></span>
                            <span class="badge badge-secondary"><?= $freq['customer_count'] ?> clientes</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial & Operational Analytics -->
    <div class="row mb-4">
        <!-- Financial Analytics -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Análise Financeira</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($analytics['financial'])): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-gray-500">Receita Bruta</span>
                            <strong>R$ <?= number_format($analytics['financial']['gross_revenue'], 2, ',', '.') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-gray-500">Impostos</span>
                            <span>R$ <?= number_format($analytics['financial']['tax_revenue'], 2, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-gray-500">Receita Líquida</span>
                            <strong>R$ <?= number_format($analytics['financial']['net_revenue'], 2, ',', '.') ?></strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-gray-500">Custos Estimados</span>
                            <span class="text-danger">R$ <?= number_format($analytics['financial']['total_costs'], 2, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-gray-500">Lucro Estimado</span>
                            <strong class="<?= $analytics['financial']['estimated_profit'] > 0 ? 'text-success' : 'text-danger' ?>">
                                R$ <?= number_format($analytics['financial']['estimated_profit'], 2, ',', '.') ?>
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-gray-500">Margem de Lucro</span>
                            <span class="badge badge-<?= $analytics['financial']['profit_margin'] > 0 ? 'success' : 'danger' ?>">
                                <?= $analytics['financial']['profit_margin'] ?>%
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($analytics['financial']['payment_methods'])): ?>
                    <div>
                        <div class="small text-gray-500 mb-2">Métodos de Pagamento</div>
                        <?php foreach ($analytics['financial']['payment_methods'] as $method): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?= ucfirst($method['payment_method']) ?></span>
                            <div>
                                <span class="badge badge-primary mr-1"><?= $method['count'] ?></span>
                                <small>R$ <?= number_format($method['total'], 2, ',', '.') ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Operational Analytics -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Análise Operacional</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($analytics['operational'])): ?>
                    <div class="mb-3">
                        <div class="small text-gray-500 mb-2">Tempo Médio de Preparo</div>
                        <div class="h4 mb-0 font-weight-bold text-primary">
                            <?= $analytics['operational']['average_prep_time'] ?> min
                        </div>
                    </div>
                    
                    <?php if (!empty($analytics['operational']['order_status_distribution'])): ?>
                    <div class="mb-3">
                        <div class="small text-gray-500 mb-2">Status dos Pedidos</div>
                        <?php foreach ($analytics['operational']['order_status_distribution'] as $status): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?= ucfirst($status['status']) ?></span>
                            <span class="badge badge-secondary"><?= $status['count'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($analytics['operational']['busiest_days'])): ?>
                    <div class="mb-3">
                        <div class="small text-gray-500 mb-2">Dias Mais Movimentados</div>
                        <?php foreach (array_slice($analytics['operational']['busiest_days'], 0, 3) as $day): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?= $day['day_name'] ?></span>
                            <span class="badge badge-primary"><?= $day['orders'] ?> pedidos</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($analytics['operational']['order_sources'])): ?>
                    <div>
                        <div class="small text-gray-500 mb-2">Origem dos Pedidos</div>
                        <?php foreach ($analytics['operational']['order_sources'] as $source): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?= ucfirst($source['order_source']) ?></span>
                            <div>
                                <span class="badge badge-info mr-1"><?= $source['count'] ?></span>
                                <small>R$ <?= number_format($source['revenue'], 2, ',', '.') ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize real-time data updates
    updateRealTimeData();
    setInterval(updateRealTimeData, 30000); // Update every 30 seconds
    
    // Initialize sales chart
    <?php if (isset($analytics['sales']['sales_by_day']) && !empty($analytics['sales']['sales_by_day'])): ?>
    initializeSalesChart();
    <?php endif; ?>
});

function updateRealTimeData() {
    fetch('/analytics/real-time')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('today-orders').textContent = data.data.today_orders;
                document.getElementById('today-revenue').textContent = 'R$ ' + 
                    new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(data.data.today_revenue);
                document.getElementById('pending-orders').textContent = data.data.pending_orders;
                document.getElementById('last-hour-orders').textContent = data.data.last_hour_orders;
            }
        })
        .catch(error => {
            console.error('Error updating real-time data:', error);
            // Show fallback values
            document.getElementById('today-orders').textContent = '0';
            document.getElementById('today-revenue').textContent = 'R$ 0,00';
            document.getElementById('pending-orders').textContent = '0';
            document.getElementById('last-hour-orders').textContent = '0';
        });
}

<?php if (isset($analytics['sales']['sales_by_day']) && !empty($analytics['sales']['sales_by_day'])): ?>
function initializeSalesChart() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesData = <?= json_encode($analytics['sales']['sales_by_day']) ?>;
    
    const labels = salesData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    });
    
    const revenues = salesData.map(item => parseFloat(item.total));
    const orders = salesData.map(item => parseInt(item.orders));
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Receita (R$)',
                data: revenues,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                yAxisID: 'y'
            }, {
                label: 'Pedidos',
                data: orders,
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Data'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Receita (R$)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + new Intl.NumberFormat('pt-BR').format(value);
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Pedidos'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            elements: {
                point: {
                    radius: 3,
                    hoverRadius: 6
                }
            }
        }
    });
}
<?php endif; ?>

function refreshSalesChart() {
    // Reload the page with current period
    window.location.reload();
}

function exportChart(type) {
    // Implementation for chart export
    console.log('Exporting chart:', type);
}
</script>
<?= $this->endSection() ?>