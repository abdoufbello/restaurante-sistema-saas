<?php $this->load->view("partial/header"); ?>

<div class="row" id="title_bar">
	<div class="col-md-10">
		<div class="page-header">
			<h1><i class="ion-stats-bars"></i> <?php echo $controller_name; ?></h1>
		</div>
	</div>
	<div class="col-md-2">
		<div class="buttons-list">
			<div class="pull-right-btn">
				<button class="btn btn-primary" onclick="refreshAnalytics()">
					<i class="ion-refresh"></i> Atualizar
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Filtros de Período -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-body">
				<form class="form-inline" id="period-form">
					<div class="form-group">
						<label>Período:</label>
						<select name="period" class="form-control" id="period-select">
							<option value="7">Últimos 7 dias</option>
							<option value="30" selected>Últimos 30 dias</option>
							<option value="90">Últimos 90 dias</option>
							<option value="365">Último ano</option>
							<option value="custom">Período personalizado</option>
						</select>
					</div>
					
					<div class="form-group" id="custom-dates" style="display: none;">
						<label>De:</label>
						<input type="date" name="start_date" class="form-control" id="start-date">
						<label>Até:</label>
						<input type="date" name="end_date" class="form-control" id="end-date">
					</div>
					
					<button type="button" class="btn btn-primary" onclick="loadAnalytics()">
						<i class="ion-search"></i> Aplicar
					</button>
					
					<button type="button" class="btn btn-success" onclick="exportReport()">
						<i class="ion-document-text"></i> Exportar Relatório
					</button>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- KPIs Principais -->
<div class="row">
	<div class="col-md-3">
		<div class="tile-stats tile-blue">
			<div class="icon"><i class="ion-cash"></i></div>
			<div class="num" id="total-inventory-value">R$ <?php echo number_format($kpis['total_inventory_value'], 0, ',', '.'); ?></div>
			<h3>Valor Total do Estoque</h3>
			<p>Investimento atual</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-green">
			<div class="icon"><i class="ion-arrow-graph-up-right"></i></div>
			<div class="num" id="inventory-turnover"><?php echo number_format($kpis['inventory_turnover'], 1); ?>x</div>
			<h3>Giro de Estoque</h3>
			<p>Rotatividade média</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-orange">
			<div class="icon"><i class="ion-alert-circled"></i></div>
			<div class="num" id="stockout-rate"><?php echo number_format($kpis['stockout_rate'], 1); ?>%</div>
			<h3>Taxa de Ruptura</h3>
			<p>Produtos em falta</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-red">
			<div class="icon"><i class="ion-trash-b"></i></div>
			<div class="num" id="waste-rate">R$ <?php echo number_format($kpis['waste_value'], 0, ',', '.'); ?></div>
			<h3>Desperdício</h3>
			<p>Valor perdido</p>
		</div>
	</div>
</div>

<!-- Gráficos -->
<div class="row">
	<!-- Gráfico de Movimentação de Estoque -->
	<div class="col-md-6">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-stats-bars"></i> Movimentação de Estoque
				</h3>
			</div>
			<div class="panel-body">
				<canvas id="stock-movement-chart" height="300"></canvas>
			</div>
		</div>
	</div>
	
	<!-- Gráfico de Análise ABC -->
	<div class="col-md-6">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-pie-graph"></i> Análise ABC
				</h3>
			</div>
			<div class="panel-body">
				<canvas id="abc-analysis-chart" height="300"></canvas>
			</div>
			</div>
	</div>
</div>

<div class="row">
	<!-- Gráfico de Giro por Categoria -->
	<div class="col-md-6">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-android-list"></i> Giro por Categoria
				</h3>
			</div>
			<div class="panel-body">
				<canvas id="category-turnover-chart" height="300"></canvas>
			</div>
		</div>
	</div>
	
	<!-- Gráfico de Previsão de Demanda -->
	<div class="col-md-6">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-arrow-graph-up-right"></i> Previsão de Demanda
				</h3>
			</div>
			<div class="panel-body">
				<canvas id="demand-forecast-chart" height="300"></canvas>
			</div>
		</div>
	</div>
</div>

<!-- Tabelas de Análise -->
<div class="row">
	<!-- Top Produtos por Valor -->
	<div class="col-md-6">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-trophy"></i> Top 10 - Maior Valor em Estoque
				</h3>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped">
						<thead>
							<tr>
								<th>Produto</th>
								<th>Qtd</th>
								<th>Valor Unit.</th>
								<th>Valor Total</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($top_products_by_value as $product): ?>
								<tr>
									<td>
										<strong><?php echo $product['name']; ?></strong><br>
										<small class="text-muted"><?php echo $product['item_number']; ?></small>
									</td>
									<td><?php echo number_format($product['quantity'], 2); ?></td>
									<td>R$ <?php echo number_format($product['unit_price'], 2, ',', '.'); ?></td>
									<td><strong>R$ <?php echo number_format($product['total_value'], 2, ',', '.'); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Produtos com Baixa Rotatividade -->
	<div class="col-md-6">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-pause"></i> Produtos com Baixa Rotatividade
				</h3>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped">
						<thead>
							<tr>
								<th>Produto</th>
								<th>Dias sem Movimento</th>
								<th>Qtd em Estoque</th>
								<th>Valor Parado</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($slow_moving_products as $product): ?>
								<tr>
									<td>
										<strong><?php echo $product['name']; ?></strong><br>
										<small class="text-muted"><?php echo $product['item_number']; ?></small>
									</td>
									<td>
										<span class="label label-<?php echo $product['days_without_movement'] > 90 ? 'danger' : ($product['days_without_movement'] > 60 ? 'warning' : 'info'); ?>">
											<?php echo $product['days_without_movement']; ?> dias
										</span>
									</td>
									<td><?php echo number_format($product['quantity'], 2); ?></td>
									<td>R$ <?php echo number_format($product['stuck_value'], 2, ',', '.'); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Análise de Fornecedores -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-person-stalker"></i> Análise de Fornecedores
				</h3>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped" id="suppliers-analysis-table">
						<thead>
							<tr>
								<th>Fornecedor</th>
								<th>Total de Compras</th>
								<th>Valor Total</th>
								<th>Produtos Fornecidos</th>
								<th>Última Compra</th>
								<th>Avaliação</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($suppliers_analysis as $supplier): ?>
								<tr>
									<td>
										<strong><?php echo $supplier['company_name']; ?></strong><br>
										<small class="text-muted"><?php echo $supplier['contact_person']; ?></small>
									</td>
									<td><?php echo $supplier['total_purchases']; ?></td>
									<td>R$ <?php echo number_format($supplier['total_value'], 2, ',', '.'); ?></td>
									<td><?php echo $supplier['products_count']; ?></td>
									<td><?php echo $supplier['last_purchase'] ? date('d/m/Y', strtotime($supplier['last_purchase'])) : 'Nunca'; ?></td>
									<td>
										<?php 
										$rating = $supplier['rating'] ?? 0;
										for ($i = 1; $i <= 5; $i++) {
											echo $i <= $rating ? '<i class="ion-star" style="color: #f39c12;"></i>' : '<i class="ion-star" style="color: #ddd;"></i>';
										}
										?>
										<small>(<?php echo number_format($rating, 1); ?>)</small>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Recomendações Inteligentes -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-lightbulb"></i> Recomendações Inteligentes
				</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<?php foreach ($recommendations as $recommendation): ?>
						<div class="col-md-4">
							<div class="alert alert-<?php echo $recommendation['type']; ?>">
								<h4><i class="<?php echo $recommendation['icon']; ?>"></i> <?php echo $recommendation['title']; ?></h4>
								<p><?php echo $recommendation['description']; ?></p>
								<?php if ($recommendation['action']): ?>
									<button class="btn btn-sm btn-<?php echo $recommendation['type']; ?>" onclick="<?php echo $recommendation['action']; ?>">
										<?php echo $recommendation['action_text']; ?>
									</button>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Dados dos gráficos (normalmente viriam do PHP)
const stockMovementData = <?php echo json_encode($stock_movement_data); ?>;
const abcAnalysisData = <?php echo json_encode($abc_analysis_data); ?>;
const categoryTurnoverData = <?php echo json_encode($category_turnover_data); ?>;
const demandForecastData = <?php echo json_encode($demand_forecast_data); ?>;

// Configurações dos gráficos
Chart.defaults.font.family = 'Arial, sans-serif';
Chart.defaults.font.size = 12;

// Gráfico de Movimentação de Estoque
const stockMovementCtx = document.getElementById('stock-movement-chart').getContext('2d');
const stockMovementChart = new Chart(stockMovementCtx, {
	type: 'line',
	data: {
		labels: stockMovementData.labels,
		datasets: [{
			label: 'Entradas',
			data: stockMovementData.entries,
			borderColor: '#28a745',
			backgroundColor: 'rgba(40, 167, 69, 0.1)',
			fill: true
		}, {
			label: 'Saídas',
			data: stockMovementData.exits,
			borderColor: '#dc3545',
			backgroundColor: 'rgba(220, 53, 69, 0.1)',
			fill: true
		}]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		scales: {
			y: {
				beginAtZero: true
			}
		}
	}
});

// Gráfico de Análise ABC
const abcAnalysisCtx = document.getElementById('abc-analysis-chart').getContext('2d');
const abcAnalysisChart = new Chart(abcAnalysisCtx, {
	type: 'doughnut',
	data: {
		labels: ['Classe A (80%)', 'Classe B (15%)', 'Classe C (5%)'],
		datasets: [{
			data: [abcAnalysisData.classA, abcAnalysisData.classB, abcAnalysisData.classC],
			backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
			borderWidth: 2
		}]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		plugins: {
			legend: {
				position: 'bottom'
			}
		}
	}
});

// Gráfico de Giro por Categoria
const categoryTurnoverCtx = document.getElementById('category-turnover-chart').getContext('2d');
const categoryTurnoverChart = new Chart(categoryTurnoverCtx, {
	type: 'bar',
	data: {
		labels: categoryTurnoverData.categories,
		datasets: [{
			label: 'Giro de Estoque',
			data: categoryTurnoverData.turnover,
			backgroundColor: '#007bff',
			borderColor: '#0056b3',
			borderWidth: 1
		}]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		scales: {
			y: {
				beginAtZero: true
			}
		}
	}
});

// Gráfico de Previsão de Demanda
const demandForecastCtx = document.getElementById('demand-forecast-chart').getContext('2d');
const demandForecastChart = new Chart(demandForecastCtx, {
	type: 'line',
	data: {
		labels: demandForecastData.labels,
		datasets: [{
			label: 'Demanda Real',
			data: demandForecastData.actual,
			borderColor: '#007bff',
			backgroundColor: 'rgba(0, 123, 255, 0.1)',
			fill: false
		}, {
			label: 'Previsão',
			data: demandForecastData.forecast,
			borderColor: '#28a745',
			backgroundColor: 'rgba(40, 167, 69, 0.1)',
			borderDash: [5, 5],
			fill: false
		}]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		scales: {
			y: {
				beginAtZero: true
			}
		}
	}
});

// Funções
function refreshAnalytics() {
	show_feedback('info', 'Atualizando análises...', 'Aguarde');
	location.reload();
}

function loadAnalytics() {
	var period = $('#period-select').val();
	var startDate = $('#start-date').val();
	var endDate = $('#end-date').val();
	
	if (period === 'custom' && (!startDate || !endDate)) {
		show_feedback('warning', 'Selecione as datas de início e fim', 'Atenção');
		return;
	}
	
	show_feedback('info', 'Carregando análises...', 'Aguarde');
	
	$.ajax({
		url: '<?php echo site_url("inventory/load_analytics"); ?>',
		type: 'POST',
		data: {
			period: period,
			start_date: startDate,
			end_date: endDate
		},
		success: function(response) {
			if (response.success) {
				// Atualizar KPIs
				$('#total-inventory-value').text('R$ ' + response.kpis.total_inventory_value.toLocaleString('pt-BR'));
				$('#inventory-turnover').text(response.kpis.inventory_turnover.toFixed(1) + 'x');
				$('#stockout-rate').text(response.kpis.stockout_rate.toFixed(1) + '%');
				$('#waste-rate').text('R$ ' + response.kpis.waste_value.toLocaleString('pt-BR'));
				
				// Atualizar gráficos
				updateCharts(response.charts);
				
				show_feedback('success', 'Análises atualizadas com sucesso', 'Sucesso');
			} else {
				show_feedback('error', response.message || 'Erro ao carregar análises', 'Erro');
			}
		},
		error: function() {
			show_feedback('error', 'Erro de conexão', 'Erro');
		}
	});
}

function updateCharts(chartsData) {
	// Atualizar dados dos gráficos
	stockMovementChart.data.labels = chartsData.stock_movement.labels;
	stockMovementChart.data.datasets[0].data = chartsData.stock_movement.entries;
	stockMovementChart.data.datasets[1].data = chartsData.stock_movement.exits;
	stockMovementChart.update();
	
	abcAnalysisChart.data.datasets[0].data = [chartsData.abc_analysis.classA, chartsData.abc_analysis.classB, chartsData.abc_analysis.classC];
	abcAnalysisChart.update();
	
	categoryTurnoverChart.data.labels = chartsData.category_turnover.categories;
	categoryTurnoverChart.data.datasets[0].data = chartsData.category_turnover.turnover;
	categoryTurnoverChart.update();
	
	demandForecastChart.data.labels = chartsData.demand_forecast.labels;
	demandForecastChart.data.datasets[0].data = chartsData.demand_forecast.actual;
	demandForecastChart.data.datasets[1].data = chartsData.demand_forecast.forecast;
	demandForecastChart.update();
}

function exportReport() {
	var period = $('#period-select').val();
	var startDate = $('#start-date').val();
	var endDate = $('#end-date').val();
	
	var form = $('<form>', {
		'method': 'POST',
		'action': '<?php echo site_url("inventory/export_analytics_report"); ?>'
	});
	
	form.append($('<input>', {
		'type': 'hidden',
		'name': 'period',
		'value': period
	}));
	
	if (period === 'custom') {
		form.append($('<input>', {
			'type': 'hidden',
			'name': 'start_date',
			'value': startDate
		}));
		
		form.append($('<input>', {
			'type': 'hidden',
			'name': 'end_date',
			'value': endDate
		}));
	}
	
	$('body').append(form);
	form.submit();
	form.remove();
}

$(document).ready(function() {
	// Mostrar/ocultar campos de data personalizada
	$('#period-select').on('change', function() {
		if ($(this).val() === 'custom') {
			$('#custom-dates').show();
		} else {
			$('#custom-dates').hide();
		}
	});
	
	// Inicializar DataTable para análise de fornecedores
	if (typeof $.fn.DataTable !== 'undefined') {
		$('#suppliers-analysis-table').DataTable({
			"pageLength": 10,
			"order": [[ 2, "desc" ]], // Ordenar por valor total
			"language": {
				"url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
			}
		});
	}
});
</script>

<?php $this->load->view("partial/footer"); ?>