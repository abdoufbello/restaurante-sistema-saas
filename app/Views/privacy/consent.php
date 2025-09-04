<?= $this->extend('layouts/privacy') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
                <div class="text-muted">
                    <small>Versão <?= $privacy_policy_version ?> • Atualizada em <?= date('d/m/Y', strtotime($last_updated)) ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Privacy Policy Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shield-alt"></i> Resumo da Política de Privacidade
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Seus Direitos LGPD</h6>
                        <p class="mb-2">De acordo com a Lei Geral de Proteção de Dados (LGPD), você tem os seguintes direitos:</p>
                        <ul class="mb-0">
                            <li><strong>Acesso:</strong> Saber quais dados pessoais temos sobre você</li>
                            <li><strong>Correção:</strong> Corrigir dados incompletos, inexatos ou desatualizados</li>
                            <li><strong>Exclusão:</strong> Solicitar a exclusão de dados desnecessários ou tratados em desconformidade</li>
                            <li><strong>Portabilidade:</strong> Receber seus dados em formato estruturado</li>
                            <li><strong>Revogação:</strong> Retirar seu consentimento a qualquer momento</li>
                        </ul>
                    </div>

                    <h6>Como Utilizamos Seus Dados</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="border-left-primary p-3 mb-3">
                                <h6 class="text-primary mb-2">Dados Essenciais</h6>
                                <p class="small mb-0">Necessários para o funcionamento da plataforma (cadastro, pedidos, pagamentos)</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border-left-success p-3 mb-3">
                                <h6 class="text-success mb-2">Dados Opcionais</h6>
                                <p class="small mb-0">Utilizados para melhorar sua experiência (analytics, marketing personalizado)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consent Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-check-circle"></i> Gerenciar Consentimentos
                    </h6>
                </div>
                <div class="card-body">
                    <form id="consentForm">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Importante:</strong> Alguns consentimentos são obrigatórios para o funcionamento da plataforma.
                        </div>

                        <!-- Data Processing Consent -->
                        <div class="consent-item mb-4 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">
                                        <i class="fas fa-database text-primary"></i>
                                        Processamento de Dados Pessoais
                                        <span class="badge badge-danger ml-2">Obrigatório</span>
                                    </h6>
                                    <p class="text-muted mb-2">
                                        Consentimento para processar seus dados pessoais básicos (nome, email, telefone) 
                                        necessários para o funcionamento da conta e prestação dos serviços.
                                    </p>
                                    <small class="text-muted">
                                        <strong>Base legal:</strong> Execução de contrato e consentimento
                                    </small>
                                </div>
                                <div class="custom-control custom-switch ml-3">
                                    <input type="checkbox" class="custom-control-input" id="consent_data_processing" 
                                           name="consents[data_processing]" value="1" 
                                           <?= isset($current_consents['data_processing']) && $current_consents['data_processing']['consent_given'] ? 'checked' : '' ?>
                                           required>
                                    <label class="custom-control-label" for="consent_data_processing"></label>
                                </div>
                            </div>
                            <?php if (isset($current_consents['data_processing'])): ?>
                                <div class="mt-2">
                                    <small class="text-success">
                                        <i class="fas fa-check"></i>
                                        Consentimento dado em <?= date('d/m/Y H:i', strtotime($current_consents['data_processing']['created_at'])) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Marketing Consent -->
                        <div class="consent-item mb-4 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">
                                        <i class="fas fa-envelope text-info"></i>
                                        Comunicações de Marketing
                                        <span class="badge badge-secondary ml-2">Opcional</span>
                                    </h6>
                                    <p class="text-muted mb-2">
                                        Receber emails promocionais, newsletters e comunicações sobre novos recursos, 
                                        ofertas especiais e conteúdo relevante para seu negócio.
                                    </p>
                                    <small class="text-muted">
                                        <strong>Base legal:</strong> Consentimento • <strong>Pode ser revogado a qualquer momento</strong>
                                    </small>
                                </div>
                                <div class="custom-control custom-switch ml-3">
                                    <input type="checkbox" class="custom-control-input" id="consent_marketing" 
                                           name="consents[marketing]" value="1"
                                           <?= isset($current_consents['marketing']) && $current_consents['marketing']['consent_given'] ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="consent_marketing"></label>
                                </div>
                            </div>
                            <?php if (isset($current_consents['marketing'])): ?>
                                <div class="mt-2">
                                    <small class="<?= $current_consents['marketing']['consent_given'] ? 'text-success' : 'text-warning' ?>">
                                        <i class="fas fa-<?= $current_consents['marketing']['consent_given'] ? 'check' : 'times' ?>"></i>
                                        <?= $current_consents['marketing']['consent_given'] ? 'Consentimento ativo' : 'Consentimento retirado' ?> 
                                        em <?= date('d/m/Y H:i', strtotime($current_consents['marketing']['updated_at'])) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Analytics Consent -->
                        <div class="consent-item mb-4 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">
                                        <i class="fas fa-chart-line text-success"></i>
                                        Análise de Dados e Métricas
                                        <span class="badge badge-secondary ml-2">Opcional</span>
                                    </h6>
                                    <p class="text-muted mb-2">
                                        Permitir análise de como você usa a plataforma para melhorar nossos serviços, 
                                        gerar relatórios de uso e desenvolver novos recursos.
                                    </p>
                                    <small class="text-muted">
                                        <strong>Base legal:</strong> Consentimento e interesse legítimo
                                    </small>
                                </div>
                                <div class="custom-control custom-switch ml-3">
                                    <input type="checkbox" class="custom-control-input" id="consent_analytics" 
                                           name="consents[analytics]" value="1"
                                           <?= isset($current_consents['analytics']) && $current_consents['analytics']['consent_given'] ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="consent_analytics"></label>
                                </div>
                            </div>
                        </div>

                        <!-- Cookies Consent -->
                        <div class="consent-item mb-4 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">
                                        <i class="fas fa-cookie-bite text-warning"></i>
                                        Cookies e Tecnologias Similares
                                        <span class="badge badge-danger ml-2">Obrigatório</span>
                                    </h6>
                                    <p class="text-muted mb-2">
                                        Uso de cookies essenciais para funcionamento da plataforma, autenticação, 
                                        preferências e segurança. Cookies opcionais para analytics e marketing.
                                    </p>
                                    <small class="text-muted">
                                        <strong>Tipos:</strong> Essenciais (sempre ativos), Funcionais, Analytics, Marketing
                                    </small>
                                </div>
                                <div class="custom-control custom-switch ml-3">
                                    <input type="checkbox" class="custom-control-input" id="consent_cookies" 
                                           name="consents[cookies]" value="1"
                                           <?= isset($current_consents['cookies']) && $current_consents['cookies']['consent_given'] ? 'checked' : '' ?>
                                           required>
                                    <label class="custom-control-label" for="consent_cookies"></label>
                                </div>
                            </div>
                        </div>

                        <!-- Third Party Consent -->
                        <div class="consent-item mb-4 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">
                                        <i class="fas fa-share-alt text-secondary"></i>
                                        Compartilhamento com Terceiros
                                        <span class="badge badge-secondary ml-2">Opcional</span>
                                    </h6>
                                    <p class="text-muted mb-2">
                                        Compartilhamento de dados com parceiros para integração de serviços 
                                        (gateways de pagamento, serviços de entrega, analytics).
                                    </p>
                                    <small class="text-muted">
                                        <strong>Parceiros:</strong> PagSeguro, Mercado Pago, Google Analytics, serviços de entrega
                                    </small>
                                </div>
                                <div class="custom-control custom-switch ml-3">
                                    <input type="checkbox" class="custom-control-input" id="consent_third_party" 
                                           name="consents[third_party]" value="1"
                                           <?= isset($current_consents['third_party']) && $current_consents['third_party']['consent_given'] ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="consent_third_party"></label>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save"></i> Salvar Consentimentos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tools"></i> Ações Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/privacy/data-export" class="list-group-item list-group-item-action">
                            <i class="fas fa-download text-info"></i>
                            <strong>Exportar Meus Dados</strong>
                            <small class="d-block text-muted">Baixar todos os seus dados em formato JSON</small>
                        </a>
                        <a href="/privacy/data-deletion" class="list-group-item list-group-item-action">
                            <i class="fas fa-trash text-danger"></i>
                            <strong>Solicitar Exclusão</strong>
                            <small class="d-block text-muted">Excluir dados opcionais conforme LGPD</small>
                        </a>
                        <a href="/privacy/policy" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt text-secondary"></i>
                            <strong>Política Completa</strong>
                            <small class="d-block text-muted">Ler a política de privacidade completa</small>
                        </a>
                        <a href="mailto:privacidade@totemtouchsystem.com" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope text-primary"></i>
                            <strong>Contatar DPO</strong>
                            <small class="d-block text-muted">Falar com nosso Encarregado de Dados</small>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Data Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Resumo dos Seus Dados
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-primary mb-1"><?= count($current_consents) ?></h4>
                                <small class="text-muted">Consentimentos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success mb-1">
                                <?= array_sum(array_column($current_consents, 'consent_given')) ?>
                            </h4>
                            <small class="text-muted">Ativos</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i>
                            Última atualização: 
                            <?php 
                            $lastUpdate = '2024-01-15';
                            foreach ($current_consents as $consent) {
                                if ($consent['updated_at'] > $lastUpdate) {
                                    $lastUpdate = $consent['updated_at'];
                                }
                            }
                            echo date('d/m/Y H:i', strtotime($lastUpdate));
                            ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-headset"></i> Precisa de Ajuda?
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Nossa equipe de privacidade está disponível para esclarecer dúvidas sobre 
                        o tratamento dos seus dados.
                    </p>
                    <div class="contact-info">
                        <p class="small mb-2">
                            <i class="fas fa-envelope text-primary"></i>
                            <strong>Email:</strong> privacidade@totemtouchsystem.com
                        </p>
                        <p class="small mb-2">
                            <i class="fas fa-phone text-success"></i>
                            <strong>Telefone:</strong> (11) 9999-9999
                        </p>
                        <p class="small mb-0">
                            <i class="fas fa-clock text-info"></i>
                            <strong>Horário:</strong> Seg-Sex, 9h às 18h
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const consentForm = document.getElementById('consentForm');
    
    consentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(consentForm);
        const submitBtn = consentForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        
        fetch('/privacy/process-consent', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('success', data.message);
                
                // Update consent timestamps
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Erro ao salvar consentimentos. Tente novamente.');
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    
    // Add tooltips to consent switches
    const switches = document.querySelectorAll('.custom-control-input');
    switches.forEach(switchEl => {
        switchEl.addEventListener('change', function() {
            const label = this.closest('.consent-item').querySelector('h6');
            if (this.checked) {
                label.classList.add('text-success');
            } else {
                label.classList.remove('text-success');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>