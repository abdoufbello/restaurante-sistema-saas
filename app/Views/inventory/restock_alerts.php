<?php $this->load->view("partial/header"); ?>

<div class="row" id="title_bar">
	<div class="col-md-10">
		<div class="page-header">
			<h1><i class="ion-alert-circled"></i> <?php echo $controller_name; ?></h1>
		</div>
	</div>
	<div class="col-md-2">
		<div class="buttons-list">
			<div class="pull-right-btn">
				<button class="btn btn-primary" onclick="refreshAlerts()">
					<i class="ion-refresh"></i> Atualizar
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Resumo dos Alertas -->
<div class="row">
	<div class="col-md-3">
		<div class="tile-stats tile-red">
			<div class="icon"><i class="ion-alert"></i></div>
			<div class="num" id="critical-alerts"><?php echo $stats['critical_alerts']; ?></div>
			<h3>Alertas Críticos</h3>
			<p>Estoque zerado</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-orange">
			<div class="icon"><i class="ion-alert-circled"></i></div>
			<div class="num" id="high-alerts"><?php echo $stats['high_alerts']; ?></div>
			<h3>Alertas Altos</h3>
			<p>Estoque baixo</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-yellow">
			<div class="icon"><i class="ion-clock"></i></div>
			<div class="num" id="expiring-items"><?php echo $stats['expiring_items']; ?></div>
			<h3>Próximos ao Vencimento</h3>
			<p>Próximos 30 dias</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-blue">
			<div class="icon"><i class="ion-cash"></i></div>
			<div class="num">R$ <?php echo number_format($stats['total_value_at_risk'], 0, ',', '.'); ?></div>
			<h3>Valor em Risco</h3>
			<p>Estoque crítico</p>
		</div>
	</div>
</div>

<!-- Filtros -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-body">
				<form class="form-inline" id="filter-form">
					<div class="form-group">
						<label>Prioridade:</label>
						<select name="priority" class="form-control" id="priority-filter">
							<option value="">Todas</option>
							<option value="critical">Crítica</option>
							<option value="high">Alta</option>
							<option value="medium">Média</option>
							<option value="low">Baixa</option>
						</select>
					</div>
					
					<div class="form-group">
						<label>Categoria:</label>
						<select name="category" class="form-control" id="category-filter">
							<option value="">Todas</option>
							<?php foreach ($categories as $category): ?>
								<option value="<?php echo $category; ?>"><?php echo $category; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="form-group">
						<label>Tipo de Alerta:</label>
						<select name="alert_type" class="form-control" id="alert-type-filter">
							<option value="">Todos</option>
							<option value="stock">Estoque Baixo</option>
							<option value="expiry">Próximo ao Vencimento</option>
							<option value="reorder">Ponto de Reposição</option>
						</select>
					</div>
					
					<div class="form-group">
						<input type="text" name="search" class="form-control" placeholder="Buscar produto..." id="search-input">
					</div>
					
					<button type="button" class="btn btn-primary" onclick="applyFilters()">
						<i class="ion-search"></i> Filtrar
					</button>
					
					<button type="button" class="btn btn-default" onclick="clearFilters()">
						<i class="ion-close"></i> Limpar
					</button>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Lista de Alertas -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-alert-circled"></i> Alertas de Reposição
					<span class="badge" id="alerts-count"><?php echo count($alerts); ?></span>
					<div class="pull-right">
						<button class="btn btn-xs btn-success" onclick="markAllAsRead()">
							<i class="ion-checkmark"></i> Marcar Todos como Lidos
						</button>
						<button class="btn btn-xs btn-primary" onclick="addSelectedToShoppingList()">
							<i class="ion-bag"></i> Adicionar à Lista de Compras
						</button>
					</div>
				</h3>
			</div>
			<div class="panel-body">
				<?php if (empty($alerts)): ?>
					<div class="alert alert-success text-center">
						<i class="ion-checkmark-circled" style="font-size: 48px;"></i>
						<h4>Parabéns! Nenhum alerta ativo</h4>
						<p>Todos os produtos estão com estoque adequado.</p>
					</div>
				<?php else: ?>
					<div class="table-responsive">
						<table class="table table-hover" id="alerts-table">
							<thead>
								<tr>
									<th width="40">
										<input type="checkbox" id="select-all-alerts" onchange="toggleAllAlerts()">
									</th>
									<th>Prioridade</th>
									<th>Produto</th>
									<th>Tipo de Alerta</th>
									<th>Estoque Atual</th>
									<th>Ponto de Reposição</th>
									<th>Previsão</th>
									<th>Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($alerts as $alert): ?>
									<?php
									$priority_class = [
										'critical' => 'danger',
										'high' => 'warning', 
										'medium' => 'info',
										'low' => 'default'
									];
									$priority_text = [
										'critical' => 'Crítica',
										'high' => 'Alta',
										'medium' => 'Média', 
										'low' => 'Baixa'
									];
									$alert_type_text = [
										'stock' => 'Estoque Baixo',
										'expiry' => 'Próximo ao Vencimento',
										'reorder' => 'Ponto de Reposição'
									];
									?>
									<tr class="<?php echo $alert['priority'] == 'critical' ? 'danger' : ($alert['priority'] == 'high' ? 'warning' : ''); ?>" 
									    data-item-id="<?php echo $alert['item_id']; ?>" 
									    data-priority="<?php echo $alert['priority']; ?>"
									    data-category="<?php echo $alert['category']; ?>"
									    data-alert-type="<?php echo $alert['alert_type']; ?>">
										<td>
											<input type="checkbox" name="selected_alerts[]" value="<?php echo $alert['item_id']; ?>" class="alert-checkbox">
										</td>
										<td>
											<span class="label label-<?php echo $priority_class[$alert['priority']]; ?>">
												<?php echo $priority_text[$alert['priority']]; ?>
											</span>
										</td>
										<td>
											<strong><?php echo $alert['item_name']; ?></strong><br>
											<small class="text-muted">
												<?php echo $alert['item_number']; ?>
												<?php if ($alert['category']): ?>
													| <?php echo $alert['category']; ?>
												<?php endif; ?>
											</small>
										</td>
										<td>
											<span class="label label-info">
												<?php echo $alert_type_text[$alert['alert_type']]; ?>
											</span>
											<?php if ($alert['alert_type'] == 'expiry' && $alert['expiry_date']): ?>
												<br><small>Vence: <?php echo date('d/m/Y', strtotime($alert['expiry_date'])); ?></small>
											<?php endif; ?>
										</td>
										<td>
											<span class="badge badge-<?php echo $alert['current_stock'] == 0 ? 'danger' : 'warning'; ?>">
												<?php echo number_format($alert['current_stock'], 2); ?>
											</span>
											<?php if ($alert['unit']): ?>
												<small><?php echo $alert['unit']; ?></small>
											<?php endif; ?>
										</td>
										<td>
											<span class="badge badge-info">
												<?php echo number_format($alert['reorder_point'], 2); ?>
											</span>
											<?php if ($alert['unit']): ?>
												<small><?php echo $alert['unit']; ?></small>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($alert['days_until_stockout']): ?>
												<?php if ($alert['days_until_stockout'] <= 0): ?>
													<span class="label label-danger">Esgotado</span>
												<?php elseif ($alert['days_until_stockout'] <= 7): ?>
													<span class="label label-danger"><?php echo $alert['days_until_stockout']; ?> dias</span>
												<?php elseif ($alert['days_until_stockout'] <= 15): ?>
													<span class="label label-warning"><?php echo $alert['days_until_stockout']; ?> dias</span>
												<?php else: ?>
													<span class="label label-info"><?php echo $alert['days_until_stockout']; ?> dias</span>
												<?php endif; ?>
											<?php else: ?>
												<span class="text-muted">-</span>
											<?php endif; ?>
										</td>
										<td>
											<div class="btn-group btn-group-xs">
												<button class="btn btn-primary" onclick="addToShoppingList(<?php echo $alert['item_id']; ?>)" title="Adicionar à Lista de Compras">
													<i class="ion-bag"></i>
												</button>
												<button class="btn btn-info" onclick="adjustStock(<?php echo $alert['item_id']; ?>)" title="Ajustar Estoque">
													<i class="ion-edit"></i>
												</button>
												<button class="btn btn-success" onclick="markAsRead(<?php echo $alert['item_id']; ?>)" title="Marcar como Lido">
													<i class="ion-checkmark"></i>
												</button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<!-- Modal para Ajuste de Estoque -->
<div class="modal fade" id="adjust-stock-modal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Ajustar Estoque</h4>
			</div>
			<div class="modal-body">
				<form id="adjust-stock-form">
					<input type="hidden" id="adjust-item-id" name="item_id">
					
					<div class="form-group">
						<label>Produto:</label>
						<p id="adjust-item-name" class="form-control-static"></p>
					</div>
					
					<div class="form-group">
						<label>Estoque Atual:</label>
						<p id="adjust-current-stock" class="form-control-static"></p>
					</div>
					
					<div class="form-group">
						<label>Tipo de Ajuste:</label>
						<select name="adjustment_type" class="form-control" id="adjustment-type">
							<option value="add">Adicionar ao Estoque</option>
							<option value="subtract">Remover do Estoque</option>
							<option value="set">Definir Quantidade Exata</option>
						</select>
					</div>
					
					<div class="form-group">
						<label>Quantidade:</label>
						<input type="number" name="quantity" class="form-control" id="adjustment-quantity" 
							   min="0" step="0.01" required>
					</div>
					
					<div class="form-group">
						<label>Motivo:</label>
						<select name="reason" class="form-control">
							<option value="purchase">Compra</option>
							<option value="sale">Venda</option>
							<option value="loss">Perda/Desperdício</option>
							<option value="correction">Correção de Inventário</option>
							<option value="transfer">Transferência</option>
							<option value="other">Outro</option>
						</select>
					</div>
					
					<div class="form-group">
						<label>Observações:</label>
						<textarea name="notes" class="form-control" rows="3" placeholder="Observações sobre o ajuste..."></textarea>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary" onclick="saveStockAdjustment()">Salvar Ajuste</button>
			</div>
		</div>
	</div>
</div>

<script>
function refreshAlerts() {
	show_feedback('info', 'Atualizando alertas...', 'Aguarde');
	location.reload();
}

function applyFilters() {
	var priority = $('#priority-filter').val();
	var category = $('#category-filter').val();
	var alertType = $('#alert-type-filter').val();
	var search = $('#search-input').val().toLowerCase();
	
	var visibleCount = 0;
	
	$('#alerts-table tbody tr').each(function() {
		var row = $(this);
		var show = true;
		
		// Filtro por prioridade
		if (priority && row.data('priority') !== priority) {
			show = false;
		}
		
		// Filtro por categoria
		if (category && row.data('category') !== category) {
			show = false;
		}
		
		// Filtro por tipo de alerta
		if (alertType && row.data('alert-type') !== alertType) {
			show = false;
		}
		
		// Filtro por busca
		if (search) {
			var itemName = row.find('td:nth-child(3)').text().toLowerCase();
			if (itemName.indexOf(search) === -1) {
				show = false;
			}
		}
		
		if (show) {
			row.show();
			visibleCount++;
		} else {
			row.hide();
		}
	});
	
	$('#alerts-count').text(visibleCount);
}

function clearFilters() {
	$('#filter-form')[0].reset();
	$('#alerts-table tbody tr').show();
	$('#alerts-count').text($('#alerts-table tbody tr').length);
}

function toggleAllAlerts() {
	var checked = $('#select-all-alerts').is(':checked');
	$('.alert-checkbox:visible').prop('checked', checked);
}

function markAllAsRead() {
	var alertIds = [];
	$('.alert-checkbox:checked').each(function() {
		alertIds.push($(this).val());
	});
	
	if (alertIds.length === 0) {
		show_feedback('warning', 'Selecione pelo menos um alerta', 'Atenção');
		return;
	}
	
	$.ajax({
		url: '<?php echo site_url("inventory/mark_alerts_as_read"); ?>',
		type: 'POST',
		data: { alert_ids: alertIds },
		success: function(response) {
			if (response.success) {
				show_feedback('success', 'Alertas marcados como lidos', 'Sucesso');
				// Remover linhas marcadas
				alertIds.forEach(function(id) {
					$('tr[data-item-id="' + id + '"]').fadeOut();
				});
			} else {
				show_feedback('error', response.message || 'Erro ao marcar alertas', 'Erro');
			}
		},
		error: function() {
			show_feedback('error', 'Erro de conexão', 'Erro');
		}
	});
}

function markAsRead(itemId) {
	$.ajax({
		url: '<?php echo site_url("inventory/mark_alerts_as_read"); ?>',
		type: 'POST',
		data: { alert_ids: [itemId] },
		success: function(response) {
			if (response.success) {
				show_feedback('success', 'Alerta marcado como lido', 'Sucesso');
				$('tr[data-item-id="' + itemId + '"]').fadeOut();
			} else {
				show_feedback('error', response.message || 'Erro ao marcar alerta', 'Erro');
			}
		},
		error: function() {
			show_feedback('error', 'Erro de conexão', 'Erro');
		}
	});
}

function addToShoppingList(itemId) {
	$.ajax({
		url: '<?php echo site_url("inventory/add_to_shopping_list"); ?>',
		type: 'POST',
		data: { item_id: itemId },
		success: function(response) {
			if (response.success) {
				show_feedback('success', 'Item adicionado à lista de compras', 'Sucesso');
			} else {
				show_feedback('error', response.message || 'Erro ao adicionar item', 'Erro');
			}
		},
		error: function() {
			show_feedback('error', 'Erro de conexão', 'Erro');
		}
	});
}

function addSelectedToShoppingList() {
	var itemIds = [];
	$('.alert-checkbox:checked').each(function() {
		itemIds.push($(this).val());
	});
	
	if (itemIds.length === 0) {
		show_feedback('warning', 'Selecione pelo menos um item', 'Atenção');
		return;
	}
	
	$.ajax({
		url: '<?php echo site_url("inventory/add_multiple_to_shopping_list"); ?>',
		type: 'POST',
		data: { item_ids: itemIds },
		success: function(response) {
			if (response.success) {
				show_feedback('success', itemIds.length + ' itens adicionados à lista de compras', 'Sucesso');
			} else {
				show_feedback('error', response.message || 'Erro ao adicionar itens', 'Erro');
			}
		},
		error: function() {
			show_feedback('error', 'Erro de conexão', 'Erro');
		}
	});
}

function adjustStock(itemId) {
	// Buscar informações do item
	var row = $('tr[data-item-id="' + itemId + '"]');
	var itemName = row.find('td:nth-child(3) strong').text();
	var currentStock = row.find('td:nth-child(5) .badge').text().trim();
	
	$('#adjust-item-id').val(itemId);
	$('#adjust-item-name').text(itemName);
	$('#adjust-current-stock').text(currentStock);
	$('#adjustment-quantity').val('');
	$('#adjust-stock-form')[0].reset();
	$('#adjust-item-id').val(itemId); // Manter o ID após reset
	
	$('#adjust-stock-modal').modal('show');
}

function saveStockAdjustment() {
	var formData = $('#adjust-stock-form').serialize();
	
	$.ajax({
		url: '<?php echo site_url("inventory/adjust_stock"); ?>',
		type: 'POST',
		data: formData,
		success: function(response) {
			if (response.success) {
				show_feedback('success', 'Estoque ajustado com sucesso', 'Sucesso');
				$('#adjust-stock-modal').modal('hide');
				// Atualizar a linha da tabela
				var itemId = $('#adjust-item-id').val();
				var row = $('tr[data-item-id="' + itemId + '"]');
				row.find('td:nth-child(5) .badge').text(response.new_stock);
				
				// Se o estoque foi corrigido, remover o alerta
				if (response.alert_resolved) {
					row.fadeOut();
				}
			} else {
				show_feedback('error', response.message || 'Erro ao ajustar estoque', 'Erro');
			}
		},
		error: function() {
			show_feedback('error', 'Erro de conexão', 'Erro');
		}
	});
}

$(document).ready(function() {
	// Aplicar filtros em tempo real
	$('#search-input').on('input', function() {
		applyFilters();
	});
	
	$('#priority-filter, #category-filter, #alert-type-filter').on('change', function() {
		applyFilters();
	});
	
	// Inicializar DataTable se disponível
	if (typeof $.fn.DataTable !== 'undefined') {
		$('#alerts-table').DataTable({
			"pageLength": 25,
			"order": [[ 1, "desc" ]], // Ordenar por prioridade
			"language": {
				"url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
			}
		});
	}
});
</script>

<?php $this->load->view("partial/footer"); ?>