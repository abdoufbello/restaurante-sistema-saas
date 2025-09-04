<?php $this->load->view("partial/header"); ?>

<div class="row" id="title_bar">
	<div class="col-md-10">
		<div class="page-header">
			<h1><i class="ion-stats-bars"></i> <?php echo $controller_name; ?></h1>
		</div>
	</div>
</div>

<!-- Estatísticas Gerais -->
<div class="row">
	<div class="col-md-3">
		<div class="tile-stats tile-red">
			<div class="icon"><i class="ion-cube"></i></div>
			<div class="num" data-start="0" data-end="<?php echo $stats['total_items']; ?>" data-postfix="" data-duration="1500" data-delay="0">0</div>
			<h3>Total de Itens</h3>
			<p>Produtos cadastrados</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-green">
			<div class="icon"><i class="ion-cash"></i></div>
			<div class="num">R$ <?php echo number_format($stats['total_value'], 2, ',', '.'); ?></div>
			<h3>Valor do Estoque</h3>
			<p>Valor total em estoque</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-orange">
			<div class="icon"><i class="ion-alert-circled"></i></div>
			<div class="num" data-start="0" data-end="<?php echo $stats['low_stock_count']; ?>" data-postfix="" data-duration="1500" data-delay="600">0</div>
			<h3>Estoque Baixo</h3>
			<p>Itens para reposição</p>
		</div>
	</div>
	<div class="col-md-3">
		<div class="tile-stats tile-blue">
			<div class="icon"><i class="ion-arrow-graph-up-right"></i></div>
			<div class="num">R$ <?php echo number_format($stats['monthly_movement']['sales'] ?? 0, 2, ',', '.'); ?></div>
			<h3>Vendas do Mês</h3>
			<p>Movimentação mensal</p>
		</div>
	</div>
</div>

<div class="row">
	<!-- Alertas de Reposição -->
	<div class="col-md-6">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-alert-circled"></i> Alertas de Reposição
					<span class="badge badge-danger"><?php echo count($restock_predictions); ?></span>
				</h3>
			</div>
			<div class="panel-body">
				<?php if (empty($restock_predictions)): ?>
					<div class="alert alert-success">
						<i class="ion-checkmark-circled"></i> Todos os produtos estão com estoque adequado!
					</div>
				<?php else: ?>
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>Produto</th>
									<th>Estoque</th>
									<th>Dias p/ Acabar</th>
									<th>Prioridade</th>
									<th>Ação</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($restock_predictions as $prediction): ?>
									<tr>
										<td>
											<strong><?php echo $prediction['item']->name; ?></strong><br>
											<small class="text-muted"><?php echo $prediction['item']->item_number; ?></small>
										</td>
										<td>
											<span class="badge <?php echo $prediction['item']->quantity == 0 ? 'badge-danger' : 'badge-warning'; ?>">
												<?php echo $prediction['item']->quantity; ?>
											</span>
										</td>
										<td>
											<?php if ($prediction['days_until_stockout'] > 365): ?>
												<span class="text-muted">Baixa demanda</span>
											<?php else: ?>
												<?php echo $prediction['days_until_stockout']; ?> dias
											<?php endif; ?>
										</td>
										<td>
											<?php 
											$priority_class = [
												'high' => 'label-danger',
												'medium' => 'label-warning',
												'low' => 'label-info'
											];
											$priority_text = [
												'high' => 'Alta',
												'medium' => 'Média',
												'low' => 'Baixa'
											];
											?>
											<span class="label <?php echo $priority_class[$prediction['priority']]; ?>">
												<?php echo $priority_text[$prediction['priority']]; ?>
											</span>
										</td>
										<td>
											<button class="btn btn-xs btn-primary" onclick="addToShoppingList(<?php echo $prediction['item']->item_id; ?>, <?php echo $prediction['suggested_order_quantity']; ?>)">
												<i class="ion-plus"></i> Comprar
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div class="panel-footer text-right">
						<a href="<?php echo site_url('inventory/restock_alerts'); ?>" class="btn btn-primary">
							Ver Todos os Alertas <i class="ion-arrow-right-c"></i>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Produtos Mais Vendidos -->
	<div class="col-md-6">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-trophy"></i> Top Produtos (30 dias)
				</h3>
			</div>
			<div class="panel-body">
				<?php if (empty($top_selling_items)): ?>
					<div class="alert alert-info">
						<i class="ion-information-circled"></i> Nenhuma venda registrada nos últimos 30 dias.
					</div>
				<?php else: ?>
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>#</th>
									<th>Produto</th>
									<th>Vendas</th>
									<th>Receita</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($top_selling_items as $index => $item): ?>
									<tr>
										<td>
											<span class="badge badge-<?php echo $index < 3 ? 'success' : 'default'; ?>">
												<?php echo $index + 1; ?>
											</span>
										</td>
										<td>
											<strong><?php echo $item['name']; ?></strong><br>
											<small class="text-muted"><?php echo $item['category']; ?></small>
										</td>
										<td><?php echo $item['total_quantity']; ?></td>
										<td>R$ <?php echo number_format($item['total_revenue'], 2, ',', '.'); ?></td>
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

<!-- Ações Rápidas -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-flash"></i> Ações Rápidas
				</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-3">
						<a href="<?php echo site_url('inventory/stock_levels'); ?>" class="btn btn-block btn-lg btn-primary">
							<i class="ion-levels"></i><br>
							Níveis de Estoque
						</a>
					</div>
					<div class="col-md-3">
						<a href="<?php echo site_url('inventory/shopping_list'); ?>" class="btn btn-block btn-lg btn-success">
							<i class="ion-bag"></i><br>
							Lista de Compras
						</a>
					</div>
					<div class="col-md-3">
						<a href="<?php echo site_url('purchases/register'); ?>" class="btn btn-block btn-lg btn-warning">
							<i class="ion-plus-circled"></i><br>
							Nova Compra
						</a>
					</div>
					<div class="col-md-3">
						<a href="<?php echo site_url('inventory/analytics'); ?>" class="btn btn-block btn-lg btn-info">
							<i class="ion-stats-bars"></i><br>
							Relatórios
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
function addToShoppingList(itemId, suggestedQuantity) {
	// Implementar adição à lista de compras
	if (confirm('Adicionar ' + suggestedQuantity + ' unidades à lista de compras?')) {
		$.ajax({
			url: '<?php echo site_url("inventory/add_to_shopping_list"); ?>',
			type: 'POST',
			data: {
				item_id: itemId,
				quantity: suggestedQuantity
			},
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
	// Animar números nas estatísticas
	$('.tile-stats .num').each(function() {
		var $this = $(this);
		var countTo = $this.attr('data-end');
		var duration = parseInt($this.attr('data-duration')) || 1500;
		var delay = parseInt($this.attr('data-delay')) || 0;
		
		setTimeout(function() {
			$({ countNum: $this.text() }).animate({
				countNum: countTo
			}, {
				duration: duration,
				easing: 'linear',
				step: function() {
					$this.text(Math.floor(this.countNum));
				},
				complete: function() {
					$this.text(this.countNum);
				}
			});
		}, delay);
	});
});
</script>

<?php $this->load->view("partial/footer"); ?>