<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sistema Completo para Restaurantes' ?></title>
    <meta name="description" content="<?= $description ?? 'Sistema completo de gestão para restaurantes com IA, WhatsApp automático e controle de estoque inteligente.' ?>">
    <meta name="keywords" content="<?= $keywords ?? 'sistema restaurante, controle estoque, whatsapp delivery' ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $title ?? 'Sistema Completo para Restaurantes' ?>">
    <meta property="og:description" content="<?= $description ?? 'Sistema completo de gestão para restaurantes' ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:image" content="<?= base_url('assets/images/og-image.jpg') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/images/favicon.ico') ?>">
    
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
        }
        
        .hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn-cta {
            background: var(--success-color);
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-cta:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
        }
        
        .problems {
            padding: 80px 0;
            background: var(--light-color);
        }
        
        .problems h2 {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            color: var(--danger-color);
        }
        
        .problem-item {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border-left: 5px solid var(--danger-color);
        }
        
        .problem-item i {
            font-size: 2rem;
            color: var(--danger-color);
            margin-right: 1rem;
        }
        
        .solution {
            padding: 80px 0;
        }
        
        .solution h2 {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            color: var(--success-color);
        }
        
        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
            border-top: 5px solid var(--primary-color);
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-card i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .feature-card h4 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .lead-form {
            background: linear-gradient(135deg, var(--success-color) 0%, #047857 100%);
            color: white;
            padding: 80px 0;
        }
        
        .lead-form h2 {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .form-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            color: var(--dark-color);
        }
        
        .form-control {
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-submit {
            background: var(--primary-color);
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-submit:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .social-proof {
            padding: 80px 0;
            background: var(--light-color);
        }
        
        .testimonial {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .testimonial .stars {
            color: #fbbf24;
            margin-bottom: 1rem;
        }
        
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 40px 0;
            text-align: center;
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
            z-index: 100;
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
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .problems h2,
            .solution h2,
            .lead-form h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h1>🚀 Revolucione Seu Restaurante com IA</h1>
                    <p>Sistema completo de gestão com WhatsApp automático, controle de estoque inteligente e previsão de demanda por IA</p>
                    <a href="#formulario" class="btn btn-cta btn-lg">TESTE GRÁTIS POR 30 DIAS</a>
                    <div class="mt-4">
                        <small>✅ Sem cartão de crédito • ✅ Configuração em 5 minutos • ✅ Suporte completo</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Problems Section -->
    <section class="problems">
        <div class="container">
            <h2>❌ Pare de Perder Dinheiro com:</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="problem-item">
                        <i class="fas fa-box-open"></i>
                        <div>
                            <h4>Estoque Parado ou em Falta</h4>
                            <p>Produtos vencendo na geladeira enquanto outros acabam na hora do rush</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="problem-item">
                        <i class="fas fa-mobile-alt"></i>
                        <div>
                            <h4>Pedidos Perdidos no WhatsApp</h4>
                            <p>Mensagens não respondidas, pedidos esquecidos e clientes irritados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="problem-item">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <h4>Relatórios Manuais Demorados</h4>
                            <p>Horas perdidas fazendo contas que poderiam ser automáticas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="problem-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <h4>Funcionários Sobrecarregados</h4>
                            <p>Equipe estressada tentando gerenciar tudo manualmente</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Solution Section -->
    <section class="solution">
        <div class="container">
            <h2>✅ Nossa Solução Completa:</h2>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-robot"></i>
                        <h4>IA Prevê Demanda</h4>
                        <p>Algoritmo inteligente prevê o que você vai vender e quando reabastecer</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fab fa-whatsapp"></i>
                        <h4>WhatsApp Automático</h4>
                        <p>Receba e processe pedidos automaticamente, sem perder nenhum cliente</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-chart-bar"></i>
                        <h4>Relatórios em Tempo Real</h4>
                        <p>Veja vendas, estoque e lucro atualizados a cada segundo</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-sync-alt"></i>
                        <h4>Integração Delivery</h4>
                        <p>Conecte com iFood, Uber Eats e outros em um só lugar</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Lead Form Section -->
    <section class="lead-form" id="formulario">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h2>🎯 Comece Hoje Mesmo - GRÁTIS</h2>
                    <div class="form-container">
                        <form id="leadForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" name="nome" class="form-control" placeholder="Seu nome completo *" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="email" name="email" class="form-control" placeholder="Seu melhor email *" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="tel" name="telefone" class="form-control" placeholder="WhatsApp (11) 99999-9999 *" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="restaurante" class="form-control" placeholder="Nome do seu restaurante *" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" name="cidade" class="form-control" placeholder="Sua cidade *" required>
                                </div>
                                <div class="col-md-6">
                                    <select name="interesse" class="form-control" required>
                                        <option value="">Maior interesse *</option>
                                        <option value="estoque">🎯 Controle de Estoque Inteligente</option>
                                        <option value="whatsapp">📱 Automação WhatsApp</option>
                                        <option value="delivery">🚚 Integração Delivery</option>
                                        <option value="relatorios">📊 Relatórios Avançados</option>
                                        <option value="completo">🚀 Sistema Completo</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-submit">🎉 QUERO MEU ACESSO GRÁTIS AGORA</button>
                            <div class="text-center mt-3">
                                <small class="text-muted">🔒 Seus dados estão seguros conosco • Política de Privacidade</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof Section -->
    <section class="social-proof">
        <div class="container">
            <h2 class="text-center mb-5">🏆 Restaurantes que já usam nosso sistema:</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Reduzi 40% do desperdício em apenas 2 semanas. O sistema prevê exatamente o que vou vender!"</p>
                        <strong>- Maria Silva, Pizzaria Bella Vista</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"O WhatsApp automático triplicou meus pedidos. Agora não perco mais nenhum cliente!"</p>
                        <strong>- João Santos, Hamburgueria do João</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Economizo 3 horas por dia que gastava fazendo relatórios. Agora foco no que importa!"</p>
                        <strong>- Ana Costa, Restaurante Sabor Caseiro</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Sistema Restaurante. Todos os direitos reservados.</p>
            <p>
                <a href="/privacy" class="text-white-50">Política de Privacidade</a> |
                <a href="/terms" class="text-white-50">Termos de Uso</a> |
                <a href="mailto:contato@seu-dominio.com" class="text-white-50">Contato</a>
            </p>
        </div>
    </footer>

    <!-- WhatsApp Float Button -->
    <a href="https://wa.me/5511999999999?text=Olá! Quero saber mais sobre o sistema para restaurantes" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Form submission
        document.getElementById('leadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Loading state
            submitBtn.innerHTML = '⏳ Processando...';
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(this);
                
                const response = await fetch('/capturar-lead', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Success
                    submitBtn.innerHTML = '✅ Acesso Enviado!';
                    submitBtn.style.background = '#059669';
                    
                    // Show success message
                    alert('🎉 Parabéns! Seu acesso foi enviado para seu email. Verifique também a caixa de spam.');
                    
                    // Redirect if provided
                    if (result.redirect) {
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 2000);
                    }
                    
                    // Reset form
                    this.reset();
                    
                } else {
                    // Error
                    alert('❌ ' + result.message);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
                
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao enviar formulário. Tente novamente.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Phone mask
        document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 7) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            e.target.value = value;
        });

        // Analytics tracking
        function trackEvent(event, data) {
            // Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', event, data);
            }
            
            // Facebook Pixel
            if (typeof fbq !== 'undefined') {
                fbq('track', event, data);
            }
        }

        // Track form interactions
        document.getElementById('leadForm').addEventListener('focus', function() {
            trackEvent('form_start', { form_name: 'lead_form' });
        }, true);

        // Track CTA clicks
        document.querySelectorAll('.btn-cta').forEach(btn => {
            btn.addEventListener('click', function() {
                trackEvent('cta_click', { button_text: this.textContent });
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
    
    <!-- Facebook Pixel -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', 'YOUR_PIXEL_ID');
        fbq('track', 'PageView');
    </script>
</body>
</html>