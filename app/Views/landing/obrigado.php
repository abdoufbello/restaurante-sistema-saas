<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado! Seu acesso foi enviado - Sistema Restaurante</title>
    <meta name="description" content="Parabéns! Seu acesso gratuito foi enviado. Verifique seu email e comece a revolucionar seu restaurante hoje mesmo.">
    <meta name="robots" content="noindex, nofollow">
    
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
            background: linear-gradient(135deg, var(--success-color) 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .thank-you-container {
            background: white;
            padding: 4rem;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 600px;
            margin: 2rem;
        }
        
        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-30px);
            }
            60% {
                transform: translateY(-15px);
            }
        }
        
        .thank-you-container h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--success-color);
            margin-bottom: 1.5rem;
        }
        
        .thank-you-container p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: #6b7280;
        }
        
        .next-steps {
            background: var(--light-color);
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            text-align: left;
        }
        
        .next-steps h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .step-number {
            background: var(--primary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .btn-demo {
            background: var(--primary-color);
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 1rem 0.5rem;
        }
        
        .btn-demo:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }
        
        .btn-whatsapp {
            background: #25d366;
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 1rem 0.5rem;
        }
        
        .btn-whatsapp:hover {
            background: #128c7e;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
            color: white;
        }
        
        .contact-info {
            background: #f3f4f6;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        .contact-info h4 {
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .contact-item i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            width: 20px;
        }
        
        .social-links {
            margin-top: 2rem;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 0.5rem;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            text-align: center;
            line-height: 25px;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }
        
        .countdown {
            background: linear-gradient(135deg, var(--warning-color) 0%, #b45309 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin: 2rem 0;
        }
        
        .countdown h4 {
            margin-bottom: 1rem;
        }
        
        .countdown-timer {
            font-size: 2rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        
        @media (max-width: 768px) {
            .thank-you-container {
                padding: 2rem;
                margin: 1rem;
            }
            
            .thank-you-container h1 {
                font-size: 2rem;
            }
            
            .success-icon {
                font-size: 4rem;
            }
            
            .btn-demo,
            .btn-whatsapp {
                display: block;
                margin: 1rem 0;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1>🎉 Parabéns, <?= esc($nome ?? 'Futuro Parceiro') ?>!</h1>
        
        <p><strong>Seu acesso gratuito foi enviado com sucesso!</strong></p>
        
        <p>Verifique seu email <strong><?= esc($email ?? '') ?></strong> (incluindo a caixa de spam) para acessar sua conta e começar a revolucionar seu restaurante hoje mesmo.</p>
        
        <!-- Countdown Timer -->
        <div class="countdown">
            <h4>⏰ Oferta Especial Expira em:</h4>
            <div class="countdown-timer" id="countdown">
                <span id="hours">23</span>h 
                <span id="minutes">59</span>m 
                <span id="seconds">59</span>s
            </div>
            <p><strong>50% OFF</strong> no primeiro mês para os primeiros 100 restaurantes!</p>
        </div>
        
        <!-- Next Steps -->
        <div class="next-steps">
            <h3>📋 Próximos Passos:</h3>
            
            <div class="step">
                <div class="step-number">1</div>
                <div>
                    <strong>Verifique seu email</strong><br>
                    <small>Acesse o link que enviamos para ativar sua conta</small>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div>
                    <strong>Configure seu restaurante</strong><br>
                    <small>Adicione seus dados básicos (5 minutos)</small>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div>
                    <strong>Importe seu cardápio</strong><br>
                    <small>Cole seus pratos ou use nossa IA para criar</small>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div>
                    <strong>Conecte seu WhatsApp</strong><br>
                    <small>1 clique e seus pedidos ficam automáticos</small>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="text-center">
            <a href="<?= base_url('/demo') ?>" class="btn-demo">
                <i class="fas fa-play"></i> Ver Demonstração
            </a>
            
            <a href="https://wa.me/5511999999999?text=Olá! Acabei de me cadastrar e quero acelerar minha configuração" class="btn-whatsapp" target="_blank">
                <i class="fab fa-whatsapp"></i> Falar no WhatsApp
            </a>
        </div>
        
        <!-- Contact Info -->
        <div class="contact-info">
            <h4>📞 Precisa de Ajuda?</h4>
            
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <span>suporte@seu-dominio.com</span>
            </div>
            
            <div class="contact-item">
                <i class="fab fa-whatsapp"></i>
                <span>(11) 99999-9999</span>
            </div>
            
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <span>Atendimento: Seg-Sex 8h às 18h</span>
            </div>
        </div>
        
        <!-- Social Links -->
        <div class="social-links">
            <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="#" target="_blank"><i class="fab fa-linkedin-in"></i></a>
            <a href="#" target="_blank"><i class="fab fa-youtube"></i></a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                <i class="fas fa-shield-alt"></i>
                Seus dados estão seguros conosco. Política de Privacidade LGPD.
            </small>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown Timer
        function updateCountdown() {
            const now = new Date().getTime();
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(0, 0, 0, 0);
            
            const distance = tomorrow.getTime() - now;
            
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            
            if (distance < 0) {
                document.getElementById('countdown').innerHTML = 'Oferta Expirada!';
            }
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown();
        
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
        
        // Track page view as conversion
        trackEvent('Lead', {
            event_category: 'conversion',
            event_label: 'thank_you_page',
            value: 1
        });
        
        // Track button clicks
        document.querySelectorAll('.btn-demo, .btn-whatsapp').forEach(btn => {
            btn.addEventListener('click', function() {
                trackEvent('thank_you_action', {
                    button_type: this.classList.contains('btn-demo') ? 'demo' : 'whatsapp',
                    button_text: this.textContent.trim()
                });
            });
        });
        
        // Auto-redirect after 5 minutes (optional)
        setTimeout(function() {
            if (confirm('Gostaria de ver uma demonstração do sistema agora?')) {
                window.location.href = '<?= base_url('/demo') ?>';
            }
        }, 300000); // 5 minutes
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
        fbq('track', 'Lead');
    </script>
</body>
</html>