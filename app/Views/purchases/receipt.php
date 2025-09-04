<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Recibo de Compra<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Recibo de Compra</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="exportPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <a href="<?= site_url('purchases') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
                
                <div class="card-body" id="receipt-content">
                    <?php if(isset($purchase_info)): ?>
                        <!-- Cabeçalho da Empresa -->
                        <div class="text-center mb-4">
                            <h2 class="company-name"><?= $config['company_name'] ?? 'Sua Empresa' ?></h2>
                            <p class="text-muted">
                                <?= $config['company_address'] ?? '' ?><br>
                                <?= $config['company_phone'] ?? '' ?> | <?= $config['company_email'] ?? '' ?>
                            </p>
                            <hr>
                        </div>
                        
                        <!-- Informações da Compra -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Informações da Compra</h5>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Número:</strong></td>
                                        <td>#<?= str_pad($purchase_info->purchase_id, 6, '0', STR_PAD_LEFT) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Data/Hora:</strong></td>
                                        <td><?= date('d/m/Y H:i:s', strtotime($purchase_info->purchase_time)) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Referência:</strong></td>
                                        <td><?= $purchase_info->reference ?: 'N/A' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Funcionário:</strong></td>
                                        <td><?= ($purchase_info->first_name ?? '') . ' ' . ($purchase_info->last_name ?? '') ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Fornecedor</h5>
                                <div class="border p-3 rounded">
                                    <h6><?= $purchase_info->supplier_name ?? 'Fornecedor não informado' ?></h6>
                                    <?php if(isset($supplier_details)): ?>
                                        <p class="mb-1"><?= $supplier_details->first_name . ' ' . $supplier_details->last_name ?></p>
                                        <?php if($supplier_details->phone_number): ?>
                                            <p class="mb-1"><i class="fas fa-phone"></i> <?= $supplier_details->phone_number ?></p>
                                        <?php endif; ?>
                                        <?php if($supplier_details->email): ?>
                                            <p class="mb-1"><i class="fas fa-envelope"></i> <?= $supplier_details->email ?></p>
                                        <?php endif; ?>
                                        <?php if($supplier_details->address_1): ?>
                                            <p class="mb-0"><i class="fas fa-map-marker-alt"></i> 
                                                <?= $supplier_details->address_1 ?>
                                                <?= $supplier_details->city ? ', ' . $supplier_details->city : '' ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Itens da Compra -->
                        <div class="mb-4">
                            <h5>Itens Comprados</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Item</th>
                                            <th class="text-center">Qtd</th>
                                            <th class="text-end">Preço Unit.</th>
                                            <th class="text-center">Desc. %</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $subtotal = 0;
                                        $total_discount = 0;
                                        if(isset($purchase_items) && count($purchase_items) > 0): 
                                        ?>
                                            <?php foreach($purchase_items as $item): 
                                                $item_subtotal = $item->quantity_purchased * $item->item_cost_price;
                                                $item_discount = $item_subtotal * ($item->discount_percent / 100);
                                                $item_total = $item_subtotal - $item_discount;
                                                $subtotal += $item_subtotal;
                                                $total_discount += $item_discount;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= $item->item_name ?></strong>
                                                        <?php if($item->item_number): ?>
                                                            <br><small class="text-muted">Cód: <?= $item->item_number ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center"><?= number_format($item->quantity_purchased, 2, ',', '.') ?></td>
                                                    <td class="text-end">R$ <?= number_format($item->item_cost_price, 2, ',', '.') ?></td>
                                                    <td class="text-center"><?= number_format($item->discount_percent, 1, ',', '.') ?>%</td>
                                                    <td class="text-end">R$ <?= number_format($item_total, 2, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-box-open fa-2x mb-2"></i>
                                                        <p>Nenhum item encontrado nesta compra</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Resumo Financeiro -->
                        <div class="row">
                            <div class="col-md-6">
                                <?php if($purchase_info->comment): ?>
                                    <h6>Observações</h6>
                                    <div class="border p-3 rounded bg-light">
                                        <?= nl2br(esc($purchase_info->comment)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Resumo Financeiro</h6>
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td>Subtotal:</td>
                                                <td class="text-end">R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                                            </tr>
                                            <?php if($total_discount > 0): ?>
                                                <tr>
                                                    <td>Desconto:</td>
                                                    <td class="text-end text-success">- R$ <?= number_format($total_discount, 2, ',', '.') ?></td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr class="border-top">
                                                <td><strong>Total Geral:</strong></td>
                                                <td class="text-end"><strong class="text-primary fs-5">R$ <?= number_format($purchase_info->total, 2, ',', '.') ?></strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informações Adicionais -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="row text-center text-muted small">
                                <div class="col-md-4">
                                    <p><strong>Status:</strong><br>
                                    <span class="badge bg-success">Compra Finalizada</span></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Método de Pagamento:</strong><br>
                                    <?= $purchase_info->payment_type ?? 'A definir' ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Nota Fiscal:</strong><br>
                                    <?= $purchase_info->invoice_number ?? 'Não informada' ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rodapé -->
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="text-muted small">
                                Recibo gerado automaticamente em <?= date('d/m/Y H:i:s') ?><br>
                                Sistema de Gestão de Estoque - <?= base_url() ?>
                            </p>
                        </div>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                <h4>Compra não encontrada</h4>
                                <p>A compra solicitada não foi encontrada ou foi removida do sistema.</p>
                                <a href="<?= site_url('purchases') ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Voltar para Compras
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
@media print {
    .btn-group,
    .card-header .btn-group {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-body {
        padding: 0 !important;
    }
    
    body {
        font-size: 12px;
    }
    
    .company-name {
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .table {
        font-size: 11px;
    }
    
    .page-break {
        page-break-before: always;
    }
}

.receipt-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px 10px 0 0;
}

.company-name {
    font-weight: 700;
    letter-spacing: 1px;
}

.purchase-number {
    font-size: 1.2em;
    font-weight: 600;
    color: #007bff;
}

.status-badge {
    font-size: 0.9em;
    padding: 8px 16px;
}

.financial-summary {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
}

.total-amount {
    font-size: 1.3em;
    font-weight: 700;
    color: #28a745;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function exportPDF() {
    // Implementar exportação para PDF usando jsPDF ou similar
    const element = document.getElementById('receipt-content');
    const opt = {
        margin: 1,
        filename: 'compra_<?= $purchase_info->purchase_id ?? 'recibo' ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    
    // Verificar se html2pdf está disponível
    if (typeof html2pdf !== 'undefined') {
        html2pdf().set(opt).from(element).save();
    } else {
        alert('Funcionalidade de PDF não disponível. Use a opção Imprimir.');
    }
}

// Configurar impressão
window.addEventListener('beforeprint', function() {
    document.title = 'Compra #<?= str_pad($purchase_info->purchase_id ?? 0, 6, "0", STR_PAD_LEFT) ?> - Recibo';
});

window.addEventListener('afterprint', function() {
    document.title = 'Recibo de Compra';
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl+P para imprimir
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
    
    // Ctrl+S para salvar PDF
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        exportPDF();
    }
});
</script>
<?= $this->endSection() ?>