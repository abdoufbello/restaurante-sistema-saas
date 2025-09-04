<?php $this->load->view("partial/header"); ?>

<div class="row" id="title_bar">
	<div class="col-md-10">
		<div class="page-header">
			<h1><i class="ion-levels"></i> <?php echo $controller_name; ?></h1>
		</div>
	</div>
	<div class="col-md-2">
		<div class="buttons-list">
			<div class="pull-right-btn">
				<button class="btn btn-primary" data-toggle="modal" data-target="#adjustStockModal">
					<i class="ion-edit"></i> Ajustar Estoque
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Filtros -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-funnel"></i> Filtros
				</h3>
			</div>
			<div class="panel-body">
				<form method="GET" id="filter-form">
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label>Categoria:</label>
								<select name="category" class="form-control">
									<option value="">Todas as categorias</option>
									<?php foreach ($categories as $category): ?>
										<option value="<?php echo $category; ?>" <?php echo $this->input->get('category') == $category ? 'selected' : ''; ?>>
											<?php echo $category; ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Status do Estoque:</label>
								<select name="status" class="form-control">
									<option value="">Todos os status</option>
									<option value="out_of_stock" <?php echo $this->input->get('status') == 'out_of_stock' ? 'selected' : ''; ?>>Sem estoque</option>
									<option value="low" <?php echo $this->input->get('status') == 'low' ? 'selected' : ''; ?>>Estoque baixo</option>
									<option value="normal" <?php echo $this->input->get('status') == 'normal' ? 'selected' : ''; ?>>Estoque normal</option>
									<option value="high" <?php echo $this->input->get('status') == 'high' ? 'selected' : ''; ?>>Estoque alto</option>
								</select>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label>Buscar:</label>
								<input type="text" name="search" class="form-control" placeholder="Nome do produto, código..." value="<?php echo $this->input->get('search'); ?>">
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>&nbsp;</label><br>
								<button type="submit" class="btn btn-primary">
									<i class="ion-search"></i> Filtrar
								</button>
								<a href="<?php echo site_url('inventory/stock_levels'); ?>" class="btn btn-default">
									<i class="ion-refresh"></i>
								</a>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Lista de Produtos -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-cube"></i> Produtos em Estoque
					<span class="badge"><?php echo count($items); ?></span>
				</h3>
			</div>
			<div class="panel-body">
				<?php if (empty($items)): ?>
					<div class="alert alert-info">
						<i class="ion-information-circled"></i> Nenhum produto encontrado com os filtros aplicados.
					</div>
				<?php else: ?>
					<div class="table-responsive">
						<table class="table table-hover" id="stock-table">
							<thead>
								<tr>
									<th>Produto</th>
									<th>Categoria</th>
									<th>Estoque Atual</th>
									<th>Ponto de Reposição</th>
									<th>Status</th>
									<th>Valor Unitário</th>
									<th>Valor Total</th>
									<th>Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($items as $item): ?>
									<?php
									$current_qty = $item['current_quantity'] ?? 0;
									$reorder_point = $item['reorder_point'] ?? 0;
									$cost_price = $item['cost_price'] ?? 0;
									$total_value = $current_qty * $cost_price;
									
									// Determinar status
									if ($current_qty == 0) {
										$status = 'out_of_stock';
										$status_class = 'danger';
										$status_text = 'Sem Estoque';
										$status_icon = 'ion-close-circled';
									} elseif ($current_qty <= $reorder_point) {
										$status = 'low';
										$status_class = 'warning';
										$status_text = 'Estoque Baixo';
										$status_icon = 'ion-alert-circled';
									} elseif ($current_qty >= ($item['max_stock_level'] ?? 999999)) {
										$status = 'high';
										$status_class = 'info';
										$status_text = 'Estoque Alto';
										$status_icon = 'ion-arrow-up-c';
									} else {
										$status = 'normal';
										$status_class = 'success';
										$status_text = 'Normal';
										$status_icon = 'ion-checkmark-circled';
									}
									?>
									<tr data-item-id="<?php echo $item['item_id']; ?>">
										<td>
											<strong><?php echo $item['item_name']; ?></strong><br>
											<small class="text-muted"><?php echo $item['item_number']; ?></small>
										</td>
										<td>
											<span class="label label-default"><?php echo $item['category']; ?></span>
										</td>
										<td>
											<span class="badge badge-<?php echo $status_class; ?>" style="font-size: 14px;">
												<?php echo number_format($current_qty, 2); ?>
											</span>
										</td>
										<td><?php echo number_format($reorder_point, 2); ?></td>
										<td>
											<span class="label label-<?php echo $status_class; ?>">
												<i class="<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
											</span>
										</td>
										<td>R$ <?php echo number_format($cost_price, 2, ',', '.'); ?></td>
										<td>R$ <?php echo number_format($total_value, 2, ',', '.'); ?></td>
										<td>
											<div class="btn-group">
												<button class="btn btn-xs btn-primary" onclick="adjustStock(<?php echo $item['item_id']; ?>, '<?php echo addslashes($item['item_name']); ?>', <?php echo $current_qty; ?>)">
													<i class="ion-edit"></i>
												</button>
												<button class="btn btn-xs btn-info" onclick="viewHistory(<?php echo $item['item_id']; ?>)">
													<i class="ion-clock"></i>
												</button>
												<?php if ($status == 'low' || $status == 'out_of_stock'): ?>
													<button class="btn btn-xs btn-success" onclick="addToShoppingList(<?php echo $item['item_id']; ?>)">
														<i class="ion-bag"></i>
													</button>
												<?php endif; ?>
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

<!-- Modal de Ajuste de Estoque -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">
					<i class="ion-edit"></i> Ajustar Estoque
				</h4>
			</div>
			<form id="adjust-stock-form">
				<div class="modal-body">
					<input type="hidden" id="adjust-item-id" name="item_id">
					
					<div class="form-group">
						<label>Produto:</label>
						<p id="adjust-item-name" class="form-control-static"></p>
					</div>
					
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Estoque Atual:</label>
								<p id="current-stock" class="form-control-static"></p>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Nova Quantidade:</label>
								<input type="number" id="new-quantity" name="quantity" class="form-control" step="0.01" min="0" required>
							</div>
						</div>
					</div>
					
					<div class="form-group">
						<label>Motivo do Ajuste:</label>
						<select name="reason" class="form-control" required>
							<option value="">Selecione o motivo</option>
							<option value="inventory_count">Contagem de inventário</option>
							<option value="damaged">Produto danificado</option>
							<option value="expired">Produto vencido</option>
							<option value="lost">Produto perdido</option>
							<option value="found">Produto encontrado</option>
							<option value="correction">Correção de erro</option>
							<option value="other">Outro motivo</option>
						</select>
					</div>
					
					<div class="form-group">
						<label>Observações:</label>
						<textarea name="notes" class="form-control" rows="3" placeholder="Observações adicionais (opcional)"></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary">
						<i class="ion-checkmark"></i> Confirmar Ajuste
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal de Histórico -->
<div class="modal fade" id="historyModal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">
					<i class="ion-clock"></i> Histórico de Movimentações
				</h4>
			</div>
			<div class="modal-body">
				<div id="history-content">
					<div class="text-center">
						<i class="ion-loading-c fa-spin fa-2x"></i>
						<p>Carregando histórico...</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
function adjustStock(itemId, itemName, currentStock) {
	$('#adjust-item-id').val(itemId);
	$('#adjust-item-name').text(itemName);
	$('#current-stock').text(currentStock);
	$('#new-quantity').val(currentStock);
	$('#adjustStockModal').modal('show');
}

function viewHistory(itemId) {
	$('#historyModal').modal('show');
	
	$.ajax({
		url: '<?php echo site_url("inventory/get_stock_history"); ?>',
		type: 'GET',
		data: { item_id: itemId },
		success: function(response) {
			$('#history-content').html(response);
		},
		error: function() {
			$('#history-content').html('<div class="alert alert-danger">Erro ao carregar histórico</div>');
		}
	});
}

function addToShoppingList(itemId) {
	if (confirm('Adicionar este item à lista de compras?')) {
		$.ajax({
			url: '<?php echo site_url("inventory/add_to_shopping_list"); ?>',
			type: 'POST',
			data: { item_id: itemId },
			success: function(response) {
				if (response.success) {
					show_feedback('success', 'Item adicionado à lista de compras!', 'Sucesso');
				} else {
					show_feedback('error', response.message || 'Erro ao adicionar item', 'Erro');
				}
			},
			error: function() {
				show_feedback('error', 'Erro de conexão', 'Erro');
			}
		});
	}
}

$(document).ready(function() {
	// Inicializar DataTable
	$('#stock-table').DataTable({
		"language": {
			"url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
		},
		"pageLength": 25,
		"order": [[4, "desc"]], // Ordenar por status (críticos primeiro)
		"columnDefs": [
			{ "orderable": false, "targets": [7] } // Desabilitar ordenação na coluna de ações
		]
	});
	
	// Submissão do formulário de ajuste
	$('#adjust-stock-form').on('submit', function(e) {
		e.preventDefault();
		
		$.ajax({
			url: '<?php echo site_url("inventory/update_stock_level"); ?>',
			type: 'POST',
			data: $(this).serialize(),
			success: function(response) {
				if (response.success) {
					$('#adjustStockModal').modal('hide');
					show_feedback('success', 'Estoque atualizado com sucesso!', 'Sucesso');
					// Recarregar a página após 1 segundo
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					show_feedback('error', response.message || 'Erro ao atualizar estoque', 'Erro');
				}
			},
			error: function() {
				show_feedback('error', 'Erro de conexão', 'Erro');
			}
		});
	});
	
	// Limpar formulário ao fechar modal
	$('#adjustStockModal').on('hidden.bs.modal', function() {
		$('#adjust-stock-form')[0].reset();
	});
});
</script>

<?php $this->load->view("partial/footer"); ?>