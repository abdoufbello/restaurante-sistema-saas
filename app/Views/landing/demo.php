<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demonstração do Sistema - Veja Como Funciona</title>
    <meta name="description" content="Veja uma demonstração completa do nosso sistema de gestão para restaurantes. Teste todas as funcionalidades gratuitamente.">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background: var(--light-color);
        }
        
        .demo-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .demo-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .demo-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .demo-nav {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .demo-nav .nav-link {
            color: var(--dark-color);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .demo-nav .nav-link:hover,
        .demo-nav .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .demo-section {
            padding: 60px 0;
            min-height: 80vh;
        }
        
        .demo-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .demo-card-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .demo-card-body {
            padding: 2rem;
        }
        
        .feature-demo {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        .feature-demo:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .demo-screenshot {
            width: 100%;
            height: 300px;
            background: #f3f4f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem 0;
            border: 2px dashed #d1d5db;
        }
        
        .btn-try {
            background: var(--success-color);
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0.5rem;
        }
        
        .btn-try:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--success-color) 0%, #047857 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .btn-cta-large {
            background: white;
            color: var(--success-color);
            border: none;
            padding: 20px 50px;
            font-size: 1.3rem;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-cta-large:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255,255,255,0.3);
        }
        
        .whatsapp-float {
            position: fixed;
            width: 60px;
            height: 60px;
            bottom: 40px;
            right: 40px;
            background-color: #25d366;
            color: white;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 2px 2px 3px #999;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .whatsapp-float:hover {
            background-color: #128c7e;
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .demo-header h1 {
                font-size: 2rem;
            }
            
            .demo-nav .nav-link {
                font-size: 0.9rem;
                padding: 0.4rem 0.8rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Demo Header -->
    <section class="demo-header">
        <div class="container">
            <h1>🚀 Demonstração Interativa</h1>
            <p>Explore todas as funcionalidades do nosso sistema</p>
        </div>
    </section>

    <!-- Demo Navigation -->
    <nav class="demo-nav">
        <div class="container">
            <ul class="nav nav-pills justify-content-center">
                <li class="nav-item">
                    <a class="nav-link active" href="#dashboard">📊 Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#estoque">📦 Estoque IA</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#whatsapp">💬 WhatsApp</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#pedidos">🛒 Pedidos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#financeiro">💰 Financeiro</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Dashboard Demo -->
    <section id="dashboard" class="demo-section">
        <div class="container">
            <div class="demo-card">
                <div class="demo-card-header">
                    <h2>📊 Dashboard Principal</h2>
                    <p>Visão completa do seu restaurante em tempo real</p>
                </div>
                <div class="demo-card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">R$ 12.450</div>
                            <div class="stat-label">Vendas Hoje</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">87</div>
                            <div class="stat-label">Pedidos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">23</div>
                            <div class="stat-label">Itens em Falta</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">4.8★</div>
                            <div class="stat-label">Avaliação</div>
                        </div>
                    </div>
                    
                    <div class="demo-screenshot">
                        <div class="text-center">
                            <i class="fas fa-chart-line" style="font-size: 4rem; color: #d1d5db;"></i>
                            <p class="mt-2 text-muted">Gráficos de vendas em tempo real</p>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="<?= base_url('/dashboard') ?>" class="btn-try">
                            <i class="fas fa-eye"></i> Ver Dashboard Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Estoque IA Demo -->
    <section id="estoque" class="demo-section">
        <div class="container">
            <div class="demo-card">
                <div class="demo-card-header">
                    <h2>🤖 Estoque Inteligente com IA</h2>
                    <p>Previsão automática de demanda e reposição</p>
                </div>
                <div class="demo-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="feature-demo">
                                <div class="feature-icon">
                                    <i class="fas fa-brain"></i>
                                </div>
                                <h4>Previsão de Demanda</h4>
                                <p>IA analisa histórico e prevê o que você vai vender</p>
                                <div class="alert alert-info">
                                    <strong>Previsão para amanhã:</strong><br>
                                    🍕 Pizza Margherita: 45 unidades<br>
                                    🍔 Hambúrguer: 32 unidades<br>
                                    🥤 Refrigerante: 78 unidades
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-demo">
                                <div class="feature-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <h4>Alertas Automáticos</h4>
                                <p>Notificações quando produtos estão acabando</p>
                                <div class="alert alert-warning">
                                    <strong>⚠️ Alertas Ativos:</strong><br>
                                    • Tomate: Repor em 2 dias<br>
                                    • Queijo: Estoque crítico<br>
                                    • Carne: Comprar hoje
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="<?= base_url('/estoque') ?>" class="btn-try">
                            <i class="fas fa-robot"></i> Testar IA do Estoque
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- WhatsApp Demo -->
    <section id="whatsapp" class="demo-section">
        <div class="container">
            <div class="demo-card">
                <div class="demo-card-header">
                    <h2>💬 WhatsApp Business Automático</h2>
                    <p>Receba e processe pedidos automaticamente</p>
                </div>
                <div class="demo-card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="demo-screenshot" style="height: 400px; background: #e5f3ff;">
                                <div class="text-center">
                                    <i class="fab fa-whatsapp" style="font-size: 4rem; color: #25d366;"></i>
                                    <p class="mt-2"><strong>Simulação de Conversa:</strong></p>
                                    <div class="text-start" style="max-width: 300px;">
                                        <div class="bg-light p-2 rounded mb-2">
                                            <small>Cliente:</small><br>
                                            "Oi, quero uma pizza margherita"
                                        </div>
                                        <div class="bg-primary text-white p-2 rounded mb-2">
                                            <small>Bot:</small><br>
                                            "Olá! Pizza Margherita R$ 35,00. Confirma?"
                                        </div>
                                        <div class="bg-light p-2 rounded">
                                            <small>Cliente:</small><br>
                                            "Sim, confirmo!"
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-demo">
                                <h5>✅ Funcionalidades:</h5>
                                <ul class="list-unstyled">
                                    <li>✓ Cardápio automático</li>
                                    <li>✓ Cálculo de preços</li>
                                    <li>✓ Confirmação de pedidos</li>
                                    <li>✓ Status de entrega</li>
                                    <li>✓ Pagamento online</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="<?= base_url('/whatsapp') ?>" class="btn-try">
                            <i class="fab fa-whatsapp"></i> Configurar WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pedidos Demo -->
    <section id="pedidos" class="demo-section">
        <div class="container">
            <div class="demo-card">
                <div class="demo-card-header">
                    <h2>🛒 Gestão de Pedidos</h2>
                    <p>Centralize todos os pedidos em um só lugar</p>
                </div>
                <div class="demo-card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Itens</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Origem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#1234</td>
                                    <td>João Silva</td>
                                    <td>2x Pizza, 1x Refrigerante</td>
                                    <td>R$ 75,00</td>
                                    <td><span class="badge bg-warning">Preparando</span></td>
                                    <td><i class="fab fa-whatsapp text-success"></i> WhatsApp</td>
                                </tr>
                                <tr>
                                    <td>#1235</td>
                                    <td>Maria Santos</td>
                                    <td>1x Hambúrguer, 1x Batata</td>
                                    <td>R$ 45,00</td>
                                    <td><span class="badge bg-success">Pronto</span></td>
                                    <td><i class="fas fa-motorcycle text-danger"></i> iFood</td>
                                </tr>
                                <tr>
                                    <td>#1236</td>
                                    <td>Pedro Costa</td>
                                    <td>3x Pastel, 1x Suco</td>
                                    <td>R$ 28,00</td>
                                    <td><span class="badge bg-info">Entregando</span></td>
                                    <td><i class="fas fa-utensils text-primary"></i> Balcão</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center">
                        <a href="<?= base_url('/pedidos') ?>" class="btn-try">
                            <i class="fas fa-list"></i> Ver Todos os Pedidos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Financeiro Demo -->
    <section id="financeiro" class="demo-section">
        <div class="container">
            <div class="demo-card">
                <div class="demo-card-header">
                    <h2>💰 Relatórios Financeiros</h2>
                    <p>Análises completas de vendas e lucro</p>
                </div>
                <div class="demo-card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number text-success">R$ 45.230</div>
                                <div class="stat-label">Vendas do Mês</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number text-primary">R$ 18.920</div>
                                <div class="stat-label">Lucro Líquido</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number text-warning">R$ 12.450</div>
                                <div class="stat-label">Custos</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number text-info">42%</div>
                                <div class="stat-label">Margem</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="demo-screenshot">
                        <div class="text-center">
                            <i class="fas fa-chart-pie" style="font-size: 4rem; color: #d1d5db;"></i>
                            <p class="mt-2 text-muted">Gráficos de vendas por período, produto e canal</p>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="<?= base_url('/relatorios') ?>" class="btn-try">
                            <i class="fas fa-chart-bar"></i> Ver Relatórios Completos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>🎯 Pronto para Revolucionar seu Restaurante?</h2>
            <p class="lead mb-4">Comece gratuitamente hoje mesmo e veja a diferença em 24 horas!</p>
            
            <a href="<?= base_url('/') ?>" class="btn btn-cta-large">
                🚀 COMEÇAR AGORA GRÁTIS
            </a>
            
            <div class="mt-4">
                <small>✅ 30 dias grátis • ✅ Sem cartão • ✅ Configuração em 5 minutos</small>
            </div>
        </div>
    </section>

    <!-- WhatsApp Float Button -->
    <a href="https://wa.me/5511999999999?text=Olá! Vi a demonstração e quero começar a usar o sistema" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Smooth scroll to section
                const targetId = this.getAttribute('href');
                const targetSection = document.querySelector(targetId);
                
                if (targetSection) {
                    targetSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Update active nav on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.demo-section');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                if (window.pageYOffset >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
        
        // Analytics tracking
        function trackEvent(event, data) {
            if (typeof gtag !== 'undefined') {
                gtag('event', event, data);
            }
            if (typeof fbq !== 'undefined') {
                fbq('track', event, data);
            }
        }
        
        // Track demo interactions
        document.querySelectorAll('.btn-try').forEach(btn => {
            btn.addEventListener('click', function() {
                trackEvent('demo_interaction', {
                    button_text: this.textContent.trim(),
                    section: this.closest('.demo-section').id
                });
            });
        });
        
        // Track time spent on demo
        let startTime = Date.now();
        window.addEventListener('beforeunload', function() {
            const timeSpent = Math.round((Date.now() - startTime) / 1000);
            trackEvent('demo_time_spent', {
                seconds: timeSpent
            });
        });
    </script>
    
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_TRACKING_ID"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'GA_TRACKING_ID');
    </script>
</body>
</html>