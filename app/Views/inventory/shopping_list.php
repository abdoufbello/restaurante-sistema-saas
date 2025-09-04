<?php $this->load->view("partial/header"); ?>

<div class="row" id="title_bar">
	<div class="col-md-10">
		<div class="page-header">
			<h1><i class="ion-bag"></i> <?php echo $controller_name; ?></h1>
		</div>
	</div>
	<div class="col-md-2">
		<div class="buttons-list">
			<div class="pull-right-btn">
				<button class="btn btn-success" onclick="generateList()">
					<i class="ion-refresh"></i> Gerar Lista
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Resumo da Lista -->
<div class="row">
	<div class="col-md-3">
		<div class="tile-stats tile-blue">
			<div class="icon"><i class="ion-cube"></i></div>
			<div class="num" id="total-items"><?php echo count($suggested_items); ?></div>
			<h3>Itens Sugeridos</h3>
			<p>Para reposição</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-green">
			<div class="icon"><i class="ion-cash"></i></div>
			<div class="num" id="total-cost">R$ <?php 
				$total = 0;
				foreach ($suggested_items as $item) {
					$total += $item['estimated_cost'] ?? 0;
				}
				echo number_format($total, 2, ',', '.');
			?></div>
			<h3>Custo Estimado</h3>
			<p>Total da lista</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-red">
			<div class="icon"><i class="ion-alert-circled"></i></div>
			<div class="num" id="urgent-items"><?php 
				$urgent = 0;
				foreach ($suggested_items as $item) {
					if (in_array($item['urgency'], ['critical', 'high'])) $urgent++;
				}
				echo $urgent;
			?></div>
			<h3>Itens Urgentes</h3>
			<p>Prioridade alta</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-orange">
			<div class="icon"><i class="ion-person-stalker"></i></div>
			<div class="num"><?php echo count($suppliers_by_category); ?></div>
			<h3>Fornecedores</h3>
			<p>Disponíveis</p>
		</div>
	</div>
</div>

<!-- Lista de Compras -->
<div class="row">
	<div class="col-md-8">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-clipboard"></i> Lista de Compras Sugerida
					<div class="pull-right">
						<button class="btn btn-xs btn-primary" onclick="selectAll()">
							<i class="ion-checkmark"></i> Selecionar Todos
						</button>
						<button class="btn btn-xs btn-default" onclick="deselectAll()">
							<i class="ion-close"></i> Desmarcar Todos
						</button>
					</div>
				</h3>
			</div>
			<div class="panel-body">
				<?php if (empty($suggested_items)): ?>
					<div class="alert alert-success">
						<i class="ion-checkmark-circled"></i> 
						Todos os produtos estão com estoque adequado! 
						<button class="btn btn-sm btn-primary" onclick="generateList()">
							<i class="ion-refresh"></i> Atualizar Lista
						</button>
					</div>
				<?php else: ?>
					<form id="shopping-list-form">
						<div class="table-responsive">
							<table class="table table-hover" id="shopping-table">
								<thead>
									<tr>
										<th width="40">
											<input type="checkbox" id="select-all" onchange="toggleAll()">
										</th>
										<th>Produto</th>
										<th>Estoque Atual</th>
										<th>Qtd. Sugerida</th>
										<th>Urgência</th>
										<th>Fornecedor</th>
										<th>Custo Est.</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($suggested_items as $item): ?>
										<?php
										$urgency_class = [
											'critical' => 'danger',
											'high' => 'warning',
											'medium' => 'info',
											'low' => 'default'
										];
										$urgency_text = [
											'critical' => 'Crítica',
											'high' => 'Alta',
											'medium' => 'Média',
											'low' => 'Baixa'
										];
										?>
										<tr data-item-id="<?php echo $item['item']->item_id; ?>" class="<?php echo in_array($item['urgency'], ['critical', 'high']) ? 'warning' : ''; ?>">
											<td>
												<input type="checkbox" name="selected_items[]" value="<?php echo $item['item']->item_id; ?>" 
													   class="item-checkbox" <?php echo in_array($item['urgency'], ['critical', 'high']) ? 'checked' : ''; ?>>
											</td>
											<td>
												<strong><?php echo $item['item']->name; ?></strong><br>
												<small class="text-muted"><?php echo $item['item']->item_number; ?></small>
											</td>
											<td>
												<span class="badge badge-<?php echo $item['current_stock'] == 0 ? 'danger' : 'warning'; ?>">
													<?php echo number_format($item['current_stock'], 2); ?>
												</span>
											</td>
											<td>
												<input type="number" name="quantities[<?php echo $item['item']->item_id; ?>]" 
													   value="<?php echo $item['suggested_quantity']; ?>" 
													   class="form-control input-sm quantity-input" 
													   style="width: 80px; display: inline-block;" 
													   min="1" step="0.01">
											</td>
											<td>
												<span class="label label-<?php echo $urgency_class[$item['urgency']]; ?>">
													<?php echo $urgency_text[$item['urgency']]; ?>
												</span>
											</td>
											<td>
												<?php if ($item['preferred_supplier']): ?>
													<strong><?php echo $item['preferred_supplier']['company_name']; ?></strong>
												<?php else: ?>
													<span class="text-muted">Não definido</span>
												<?php endif; ?>
											</td>
											<td class="item-cost" data-unit-cost="<?php echo $item['item']->cost_price ?? 0; ?>">
												R$ <?php echo number_format($item['estimated_cost'], 2, ',', '.'); ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
								<tfoot>
									<tr class="info">
										<td colspan="6"><strong>Total Selecionado:</strong></td>
										<td><strong id="selected-total">R$ 0,00</strong></td>
									</tr>
								</tfoot>
							</table>
						</div>
					</form>
				<?php endif; ?>
			</div>
			<?php if (!empty($suggested_items)): ?>
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-6">
						<button class="btn btn-success" onclick="exportList('pdf')">
							<i class="ion-document-text"></i> Exportar PDF
						</button>
						<button class="btn btn-info" onclick="exportList('excel')">
							<i class="ion-document"></i> Exportar Excel
						</button>
					</div>
					<div class="col-md-6 text-right">
						<button class="btn btn-primary" onclick="createPurchaseOrder()">
							<i class="ion-plus-circled"></i> Criar Pedido de Compra
						</button>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Painel de Fornecedores -->
	<div class="col-md-4">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-person-stalker"></i> Enviar via WhatsApp
				</h3>
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label>Selecionar Fornecedor:</label>
					<select id="supplier-select" class="form-control">
						<option value="">Escolha um fornecedor</option>
						<?php foreach ($suppliers_by_category as $category => $suppliers): ?>
							<optgroup label="<?php echo $category; ?>">
								<?php foreach ($suppliers as $supplier): ?>
									<option value="<?php echo $supplier['person_id']; ?>" data-phone="<?php echo $supplier['phone_number']; ?>">
										<?php echo $supplier['company_name']; ?>
									</option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div class="form-group">
					<label>Prévia da Mensagem:</label>
					<textarea id="whatsapp-preview" class="form-control" rows="8" readonly></textarea>
				</div>
				
				<button class="btn btn-success btn-block" id="send-whatsapp" disabled>
					<i class="ion-social-whatsapp"></i> Enviar via WhatsApp
				</button>
				
				<hr>
				
				<div class="alert alert-info">
					<i class="ion-information-circled"></i>
					<strong>Dica:</strong> Selecione os itens desejados na lista e escolha um fornecedor para gerar automaticamente a mensagem do WhatsApp.
				</div>
			</div>
		</div>
		
		<!-- Estatísticas por Categoria -->
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-stats-bars"></i> Resumo por Categoria
				</h3>
			</div>
			<div class="panel-body">
				<?php 
				$categories_summary = [];
				foreach ($suggested_items as $item) {
					$category = $item['item']->category ?? 'Sem categoria';
					if (!isset($categories_summary[$category])) {
						$categories_summary[$category] = ['count' => 0, 'cost' => 0];
					}
					$categories_summary[$category]['count']++;
					$categories_summary[$category]['cost'] += $item['estimated_cost'];
				}
				?>
				<?php if (empty($categories_summary)): ?>
					<p class="text-muted">Nenhum item na lista</p>
				<?php else: ?>
					<?php foreach ($categories_summary as $category => $data): ?>
						<div class="category-item">
							<div class="row">
								<div class="col-xs-8">
									<strong><?php echo $category; ?></strong><br>
									<small><?php echo $data['count']; ?> itens</small>
								</div>
								<div class="col-xs-4 text-right">
									<strong>R$ <?php echo number_format($data['cost'], 2, ',', '.'); ?></strong>
								</div>
							</div>
							<hr style="margin: 10px 0;">
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<script>
function generateList() {
	show_feedback('info', 'Gerando nova lista de compras...', 'Aguarde');
	
	$.ajax({
		url: '<?php echo site_url("inventory/generate_shopping_list"); ?>',
		type: 'POST',
		success: function(response) {
			if (response.success) {
				show_feedback('success', 'Lista atualizada com sucesso!', 'Sucesso');
				setTimeout(function() {
					location.reload();
				}, 1000);
			} else {
				show_feedback('error', response.message || 'Erro ao gerar lista', 'Erro');
			}
		},
		error: function() {
			show_feedback('error', 'Erro de conexão', 'Erro');
		}
	});
}

function selectAll() {
	$('.item-checkbox').prop('checked', true);
	$('#select-all').prop('checked', true);
	updateWhatsAppPreview();
	updateSelectedTotal();
}

function deselectAll() {
	$('.item-checkbox').prop('checked', false);
	$('#select-all').prop('checked', false);
	updateWhatsAppPreview();
	updateSelectedTotal();
}

function toggleAll() {
	var checked = $('#select-all').is(':checked');
	$('.item-checkbox').prop('checked', checked);
	updateWhatsAppPreview();
	updateSelectedTotal();
}

function updateSelectedTotal() {
	var total = 0;
	$('.item-checkbox:checked').each(function() {
		var row = $(this).closest('tr');
		var quantity = row.find('.quantity-input').val();
		var unitCost = row.find('.item-cost').data('unit-cost');
		total += quantity * unitCost;
	});
	$('#selected-total').text('R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2}));
}

function updateWhatsAppPreview() {
	var selectedItems = [];
	$('.item-checkbox:checked').each(function() {
		var row = $(this).closest('tr');
		var itemName = row.find('td:nth-child(2) strong').text();
		var quantity = row.find('.quantity-input').val();
		var currentStock = row.find('td:nth-child(3) .badge').text().trim();
		
		selectedItems.push({
			name: itemName,
			quantity: quantity,
			currentStock: currentStock
		});
	});
	
	if (selectedItems.length > 0) {
		var message = "🛒 *LISTA DE COMPRAS*\n\n";
		message += "📅 Data: " + new Date().toLocaleDateString('pt-BR') + "\n\n";
		message += "📦 *ITENS SOLICITADOS:*\n";
		
		selectedItems.forEach(function(item) {
			message += "• " + item.name + "\n";
			message += "  Qtd: " + item.quantity + "\n";
			message += "  Estoque atual: " + item.currentStock + "\n\n";
		});
		
		message += "⚡ Gerado automaticamente pelo Sistema de Gestão de Estoque";
		
		$('#whatsapp-preview').val(message);
		$('#send-whatsapp').prop('disabled', $('#supplier-select').val() === '');
	} else {
		$('#whatsapp-preview').val('Selecione itens para gerar a mensagem');
		$('#send-whatsapp').prop('disabled', true);
	}
}

function exportList(format) {
	var selectedItems = [];
	$('.item-checkbox:checked').each(function() {
		selectedItems.push($(this).val());
	});
	
	if (selectedItems.length === 0) {
		show_feedback('warning', 'Selecione pelo menos um item para exportar', 'Atenção');
		return;
	}
	
	var form = $('<form>', {
		'method': 'POST',
		'action': '<?php echo site_url("inventory/export_shopping_list"); ?>'
	});
	
	form.append($('<input>', {
		'type': 'hidden',
		'name': 'format',
		'value': format
	}));
	
	selectedItems.forEach(function(itemId) {
		form.append($('<input>', {
			'type': 'hidden',
			'name': 'items[]',
			'value': itemId
		}));
	});
	
	$('body').append(form);
	form.submit();
	form.remove();
}

function createPurchaseOrder() {
	var selectedItems = [];
	$('.item-checkbox:checked').each(function() {
		var row = $(this).closest('tr');
		selectedItems.push({
			item_id: $(this).val(),
			quantity: row.find('.quantity-input').val()
		});
	});
	
	if (selectedItems.length === 0) {
		show_feedback('warning', 'Selecione pelo menos um item para criar o pedido', 'Atenção');
		return;
	}
	
	// Redirecionar para a página de compras com os itens selecionados
	var url = '<?php echo site_url("purchases/register"); ?>?items=' + encodeURIComponent(JSON.stringify(selectedItems));
	window.location.href = url;
}

$(document).ready(function() {
	// Eventos para atualizar totais e preview
	$('.item-checkbox').on('change', function() {
		updateWhatsAppPreview();
		updateSelectedTotal();
	});
	
	$('.quantity-input').on('input', function() {
		updateWhatsAppPreview();
		updateSelectedTotal();
	});
	
	$('#supplier-select').on('change', function() {
		updateWhatsAppPreview();
	});
	
	// Enviar WhatsApp
	$('#send-whatsapp').on('click', function() {
		var supplierId = $('#supplier-select').val();
		var selectedItems = [];
		
		$('.item-checkbox:checked').each(function() {
			var row = $(this).closest('tr');
			selectedItems.push({
				item_id: $(this).val(),
				quantity: row.find('.quantity-input').val()
			});
		});
		
		if (selectedItems.length === 0) {
			show_feedback('warning', 'Selecione pelo menos um item', 'Atenção');
			return;
		}
		
		$.ajax({
			url: '<?php echo site_url("inventory/send_whatsapp_list"); ?>',
			type: 'POST',
			data: {
				supplier_id: supplierId,
				items: selectedItems
			},
			success: function(response) {
				if (response.success) {
					window.open(response.whatsapp_url, '_blank');
					show_feedback('success', 'WhatsApp aberto com sucesso!', 'Sucesso');
				} else {
					show_feedback('error', response.message || 'Erro ao enviar WhatsApp', 'Erro');
				}
			},
			error: function() {
				show_feedback('error', 'Erro de conexão', 'Erro');
			}
		});
	});
	
	// Inicializar
	updateWhatsAppPreview();
	updateSelectedTotal();
});
</script>

<?php $this->load->view("partial/footer"); ?>