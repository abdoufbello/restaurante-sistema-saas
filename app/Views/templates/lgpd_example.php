<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exemplo - Sistema LGPD</title>
    
    <!-- Bootstrap CSS (opcional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- LGPD Consent CSS -->
    <link rel="stylesheet" href="/assets/css/lgpd-consent.css">
    
    <style>
        body {
            padding-top: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .feature-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .privacy-controls {
            background: #f8f9fa;
            padding: 40px 0;
        }
        
        .consent-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .consent-status.granted {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .consent-status.denied {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .consent-status.partial {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-dot.green { background: #28a745; }
        .status-dot.red { background: #dc3545; }
        .status-dot.yellow { background: #ffc107; }
        
        .demo-section {
            padding: 60px 0;
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        
        .api-endpoint {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .footer {
            background: #343a40;
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">🍕 Sistema LGPD</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#demo">Demo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#api">API</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#privacy">Privacidade</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm" onclick="lgpdConsent.showPreferences()">
                            ⚙️ Preferências de Cookies
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 mb-4">Sistema de Compliance LGPD</h1>
            <p class="lead mb-4">
                Demonstração completa do sistema de gerenciamento de consentimentos,
                proteção de dados e compliance com a Lei Geral de Proteção de Dados.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <button class="btn btn-light btn-lg" onclick="lgpdConsent.showPreferences()">
                    🍪 Gerenciar Cookies
                </button>
                <button class="btn btn-outline-light btn-lg" onclick="showPrivacyPolicy()">
                    📋 Política de Privacidade
                </button>
                <button class="btn btn-outline-light btn-lg" onclick="showDataRights()">
                    🔒 Meus Direitos
                </button>
            </div>
        </div>
    </section>

    <!-- Status de Consentimento -->
    <section class="privacy-controls">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3>Status do Consentimento</h3>
                    <p class="text-muted mb-0">
                        Acompanhe suas preferências de privacidade e cookies em tempo real.
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div id="consent-status-display">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <div class="status-dot green mb-2"></div>
                            <h6 class="card-title">Necessários</h6>
                            <p class="card-text small text-muted">Sempre ativos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <div id="analytics-status" class="status-dot red mb-2"></div>
                            <h6 class="card-title">Análise</h6>
                            <p class="card-text small text-muted" id="analytics-text">Desabilitado</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <div id="marketing-status" class="status-dot red mb-2"></div>
                            <h6 class="card-title">Marketing</h6>
                            <p class="card-text small text-muted" id="marketing-text">Desabilitado</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <div id="personalization-status" class="status-dot red mb-2"></div>
                            <h6 class="card-title">Personalização</h6>
                            <p class="card-text small text-muted" id="personalization-text">Desabilitado</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="demo-section">
        <div class="container">
            <h2 class="text-center mb-5">Demonstração Interativa</h2>
            
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card feature-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">🍪 Gerenciamento de Cookies</h5>
                        </div>
                        <div class="card-body">
                            <p>Teste as funcionalidades de consentimento de cookies:</p>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" onclick="lgpdConsent.showBanner()">
                                    Mostrar Banner de Cookies
                                </button>
                                <button class="btn btn-outline-primary" onclick="lgpdConsent.showPreferences()">
                                    Abrir Preferências
                                </button>
                                <button class="btn btn-outline-success" onclick="lgpdConsent.acceptAll()">
                                    Aceitar Todos
                                </button>
                                <button class="btn btn-outline-danger" onclick="lgpdConsent.rejectAll()">
                                    Rejeitar Opcionais
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card feature-card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">🔒 Direitos do Titular</h5>
                        </div>
                        <div class="card-body">
                            <p>Exercite seus direitos sob a LGPD:</p>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-info" onclick="requestDataPortability()">
                                    Portabilidade de Dados
                                </button>
                                <button class="btn btn-outline-warning" onclick="requestDataDeletion()">
                                    Apagar Meus Dados
                                </button>
                                <button class="btn btn-outline-secondary" onclick="requestDataAccess()">
                                    Acessar Meus Dados
                                </button>
                                <button class="btn btn-outline-primary" onclick="updateConsent()">
                                    Atualizar Consentimento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card feature-card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">📊 Log de Eventos</h5>
                        </div>
                        <div class="card-body">
                            <div id="event-log" class="code-block" style="height: 200px; overflow-y: auto;">
                                <div class="text-muted">Os eventos de consentimento aparecerão aqui...</div>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" onclick="clearEventLog()">
                                Limpar Log
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- API Documentation -->
    <section id="api" class="demo-section bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Documentação da API</h2>
            
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h4>Endpoints de Consentimento</h4>
                    
                    <div class="api-endpoint">
                        <strong>POST</strong> /api/lgpd/consent
                        <div class="small text-muted mt-1">Registrar novo consentimento</div>
                    </div>
                    
                    <div class="api-endpoint">
                        <strong>GET</strong> /api/lgpd/consent/{dataSubject}
                        <div class="small text-muted mt-1">Verificar consentimento existente</div>
                    </div>
                    
                    <div class="api-endpoint">
                        <strong>PUT</strong> /api/lgpd/consent/{dataSubject}
                        <div class="small text-muted mt-1">Atualizar consentimento</div>
                    </div>
                    
                    <div class="api-endpoint">
                        <strong>DELETE</strong> /api/lgpd/consent/{dataSubject}
                        <div class="small text-muted mt-1">Revogar consentimento</div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <h4>Endpoints de Direitos</h4>
                    
                    <div class="api-endpoint">
                        <strong>POST</strong> /api/lgpd/data/portability
                        <div class="small text-muted mt-1">Solicitar portabilidade de dados</div>
                    </div>
                    
                    <div class="api-endpoint">
                        <strong>POST</strong> /api/lgpd/data/deletion
                        <div class="small text-muted mt-1">Solicitar apagamento de dados</div>
                    </div>
                    
                    <div class="api-endpoint">
                        <strong>GET</strong> /api/lgpd/data/access/{dataSubject}
                        <div class="small text-muted mt-1">Verificar acesso aos dados</div>
                    </div>
                    
                    <div class="api-endpoint">
                        <strong>GET</strong> /api/lgpd/privacy-policy
                        <div class="small text-muted mt-1">Obter política de privacidade atual</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <h4>Exemplo de Uso</h4>
                    <div class="code-block">
// Registrar consentimento
fetch('/api/lgpd/consent', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        data_subject: 'user@example.com',
        consent_type: 'cookies',
        purpose: 'Cookies e rastreamento do website',
        legal_basis: 'consent',
        metadata: {
            necessary: true,
            analytics: true,
            marketing: false,
            personalization: true
        }
    })
})
.then(response => response.json())
.then(data => console.log('Consentimento registrado:', data));
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Privacy Section -->
    <section id="privacy" class="demo-section">
        <div class="container">
            <h2 class="text-center mb-5">Informações de Privacidade</h2>
            
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <div class="display-4 text-primary mb-3">🔒</div>
                            <h5 class="card-title">Dados Protegidos</h5>
                            <p class="card-text">
                                Seus dados pessoais são criptografados e protegidos
                                seguindo as melhores práticas de segurança.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <div class="display-4 text-success mb-3">✅</div>
                            <h5 class="card-title">Compliance LGPD</h5>
                            <p class="card-text">
                                Sistema totalmente em conformidade com a
                                Lei Geral de Proteção de Dados Pessoais.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <div class="display-4 text-info mb-3">📋</div>
                            <h5 class="card-title">Transparência</h5>
                            <p class="card-text">
                                Informações claras sobre como coletamos,
                                usamos e protegemos seus dados pessoais.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h5>Sistema de Compliance LGPD</h5>
                    <p class="text-muted">
                        Solução completa para gerenciamento de consentimentos,
                        proteção de dados e compliance com a LGPD.
                    </p>
                </div>
                <div class="col-lg-6">
                    <h6>Links Importantes</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted" onclick="lgpdConsent.showPrivacyPolicy()">Política de Privacidade</a></li>
                        <li><a href="#" class="text-muted" onclick="lgpdConsent.showPreferences()">Preferências de Cookies</a></li>
                        <li><a href="#" class="text-muted" onclick="showDataRights()">Meus Direitos</a></li>
                        <li><a href="#" class="text-muted">Contato DPO</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2024 Sistema LGPD. Todos os direitos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <button class="btn btn-outline-light btn-sm" onclick="lgpdConsent.showPreferences()">
                        ⚙️ Gerenciar Cookies
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- LGPD Consent System -->
    <script src="/assets/js/lgpd-consent.js"></script>
    
    <script>
        // Configuração do sistema LGPD
        window.GA_MEASUREMENT_ID = 'GA_MEASUREMENT_ID'; // Substitua pelo seu ID do Google Analytics
        
        // Inicialização personalizada (opcional)
        window.lgpdConsent = new LGPDConsentManager({
            apiBaseUrl: '<?= base_url('api/lgpd') ?>',
            position: 'bottom',
            theme: 'light',
            language: 'pt-BR',
            showBanner: true,
            autoShow: true
        });
        
        // Event listeners para demonstração
        document.addEventListener('lgpdConsent', function(e) {
            const { action, consent } = e.detail;
            logEvent(`Evento: ${action}`, consent);
            updateConsentStatus();
        });
        
        // Atualiza o status visual do consentimento
        function updateConsentStatus() {
            const consent = lgpdConsent.getConsent();
            
            if (!consent) {
                document.getElementById('consent-status-display').innerHTML = 
                    '<span class="consent-status denied"><span class="status-dot red"></span>Nenhum consentimento</span>';
                return;
            }
            
            // Atualiza indicadores individuais
            updateStatusIndicator('analytics', consent.analytics);
            updateStatusIndicator('marketing', consent.marketing);
            updateStatusIndicator('personalization', consent.personalization);
            
            // Status geral
            const activeCount = [consent.analytics, consent.marketing, consent.personalization].filter(Boolean).length;
            let statusClass, statusText;
            
            if (activeCount === 3) {
                statusClass = 'granted';
                statusText = 'Todos aceitos';
            } else if (activeCount === 0) {
                statusClass = 'denied';
                statusText = 'Apenas necessários';
            } else {
                statusClass = 'partial';
                statusText = `${activeCount} de 3 aceitos`;
            }
            
            document.getElementById('consent-status-display').innerHTML = 
                `<span class="consent-status ${statusClass}"><span class="status-dot ${statusClass === 'granted' ? 'green' : statusClass === 'denied' ? 'red' : 'yellow'}"></span>${statusText}</span>`;
        }
        
        function updateStatusIndicator(type, enabled) {
            const statusEl = document.getElementById(`${type}-status`);
            const textEl = document.getElementById(`${type}-text`);
            
            if (enabled) {
                statusEl.className = 'status-dot green mb-2';
                textEl.textContent = 'Habilitado';
            } else {
                statusEl.className = 'status-dot red mb-2';
                textEl.textContent = 'Desabilitado';
            }
        }
        
        // Funções de demonstração
        function showPrivacyPolicy() {
            lgpdConsent.showPrivacyPolicy();
        }
        
        function showDataRights() {
            alert('Funcionalidade de direitos do titular será implementada em breve!');
        }
        
        async function requestDataPortability() {
            try {
                const response = await fetch('/api/lgpd/data/portability', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        data_subject: lgpdConsent.getDataSubject(),
                        format: 'json'
                    })
                });
                
                const data = await response.json();
                logEvent('Portabilidade solicitada', data);
                
                if (data.success) {
                    showNotification('Solicitação de portabilidade enviada com sucesso!', 'success');
                } else {
                    showNotification('Erro ao solicitar portabilidade: ' + data.message, 'error');
                }
            } catch (error) {
                logEvent('Erro na portabilidade', error);
                showNotification('Erro ao solicitar portabilidade', 'error');
            }
        }
        
        async function requestDataDeletion() {
            if (!confirm('Tem certeza que deseja apagar todos os seus dados? Esta ação não pode ser desfeita.')) {
                return;
            }
            
            try {
                const response = await fetch('/api/lgpd/data/deletion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        data_subject: lgpdConsent.getDataSubject(),
                        reason: 'Solicitação do titular'
                    })
                });
                
                const data = await response.json();
                logEvent('Apagamento solicitado', data);
                
                if (data.success) {
                    showNotification('Solicitação de apagamento enviada com sucesso!', 'success');
                } else {
                    showNotification('Erro ao solicitar apagamento: ' + data.message, 'error');
                }
            } catch (error) {
                logEvent('Erro no apagamento', error);
                showNotification('Erro ao solicitar apagamento', 'error');
            }
        }
        
        async function requestDataAccess() {
            try {
                const dataSubject = lgpdConsent.getDataSubject();
                const response = await fetch(`/api/lgpd/data/access/${encodeURIComponent(dataSubject)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                logEvent('Acesso aos dados verificado', data);
                
                if (data.success) {
                    showNotification('Informações de acesso obtidas com sucesso!', 'success');
                } else {
                    showNotification('Erro ao verificar acesso: ' + data.message, 'error');
                }
            } catch (error) {
                logEvent('Erro no acesso', error);
                showNotification('Erro ao verificar acesso aos dados', 'error');
            }
        }
        
        function updateConsent() {
            lgpdConsent.showPreferences();
        }
        
        // Utilitários
        function logEvent(message, data = null) {
            const logEl = document.getElementById('event-log');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
            
            if (data) {
                const dataEl = document.createElement('pre');
                dataEl.style.fontSize = '12px';
                dataEl.style.color = '#666';
                dataEl.style.marginTop = '5px';
                dataEl.textContent = JSON.stringify(data, null, 2);
                logEntry.appendChild(dataEl);
            }
            
            logEl.appendChild(logEntry);
            logEl.scrollTop = logEl.scrollHeight;
        }
        
        function clearEventLog() {
            document.getElementById('event-log').innerHTML = 
                '<div class="text-muted">Log limpo...</div>';
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `lgpd-notification ${type} show`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            updateConsentStatus();
            logEvent('Página carregada', { url: window.location.href });
        });
    </script>
</body>
</html>