<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Período de Teste Iniciado - TotemSystem</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .trial-title {
            color: #28a745;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .trial-info {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 25px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        .trial-days {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .trial-end-date {
            font-size: 18px;
            opacity: 0.9;
        }
        .plan-info {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .plan-name {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .features {
            margin: 20px 0;
        }
        .features h3 {
            color: #495057;
            margin-bottom: 15px;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .features li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .cta-button {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 5px;
            text-align: center;
        }
        .cta-button:hover {
            background: #0056b3;
        }
        .cta-success {
            background: #28a745;
        }
        .cta-success:hover {
            background: #1e7e34;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .warning-box h3 {
            color: #856404;
            margin-top: 0;
        }
        .trial-benefits {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .trial-benefits h3 {
            color: #004085;
            margin-top: 0;
        }
        .benefit {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 3px;
            border-left: 3px solid #007bff;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .countdown {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        .countdown-item {
            text-align: center;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .countdown-number {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .countdown-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🍽️ TotemSystem</div>
            <h1 class="trial-title">Período de Teste Iniciado!</h1>
            <p>Olá, <?= esc($restaurant['name']) ?>! Seu período de teste gratuito começou agora.</p>
        </div>

        <div class="trial-info">
            <div class="trial-days">30</div>
            <div>dias de teste gratuito</div>
            <div class="trial-end-date">Válido até <?= $trial_end_date ?></div>
        </div>

        <div class="plan-info">
            <div class="plan-name">Testando: <?= esc($plan['name']) ?></div>
            <p>Durante o período de teste, você terá acesso completo a todos os recursos do plano <?= esc($plan['name']) ?>.</p>
        </div>

        <div class="trial-benefits">
            <h3>🎉 O que você pode fazer durante o teste:</h3>
            
            <div class="benefit">
                <strong>✨ Acesso completo</strong> - Todos os recursos do plano <?= esc($plan['name']) ?> liberados
            </div>
            
            <div class="benefit">
                <strong>🔧 Configuração ilimitada</strong> - Configure quantos totems e funcionários precisar
            </div>
            
            <div class="benefit">
                <strong>📊 Relatórios completos</strong> - Acesse todos os relatórios e análises
            </div>
            
            <div class="benefit">
                <strong>🎯 Suporte prioritário</strong> - Nossa equipe está aqui para ajudar você
            </div>
        </div>

        <div class="features">
            <h3>Recursos inclusos no plano <?= esc($plan['name']) ?>:</h3>
            <ul>
                <?php 
                $features = json_decode($plan['features'], true);
                foreach ($features as $feature): 
                ?>
                <li><?= esc($feature) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= $dashboard_url ?>" class="cta-button cta-success">Começar Agora</a>
            <a href="<?= $billing_url ?>" class="cta-button">Ver Planos</a>
        </div>

        <div class="warning-box">
            <h3>⏰ Importante - Não perca o prazo!</h3>
            <p><strong>Seu teste expira em <?= $trial_end_date ?></strong></p>
            <p>Para continuar usando o TotemSystem após o período de teste, você precisará escolher um plano de assinatura. Não se preocupe - enviaremos lembretes antes do vencimento!</p>
            <p>💡 <strong>Dica:</strong> Configure seu método de pagamento com antecedência para não ter interrupções no serviço.</p>
        </div>

        <div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 20px; margin: 20px 0;">
            <h3 style="color: #0c5460; margin-top: 0;">🚀 Primeiros passos recomendados:</h3>
            <ol style="color: #0c5460; margin: 0; padding-left: 20px;">
                <li><strong>Configure seu restaurante</strong> - Adicione informações básicas, logo e horários</li>
                <li><strong>Cadastre seu cardápio</strong> - Importe ou digite seus pratos e preços</li>
                <li><strong>Configure um totem</strong> - Teste o sistema de autoatendimento</li>
                <li><strong>Faça um pedido teste</strong> - Experimente todo o fluxo do cliente</li>
                <li><strong>Explore os relatórios</strong> - Veja como acompanhar suas vendas</li>
            </ol>
        </div>

        <div class="footer">
            <p><strong>Informações do seu teste:</strong></p>
            <p>Plano: <?= esc($plan['name']) ?> | Início: <?= date('d/m/Y') ?> | Fim: <?= $trial_end_date ?></p>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e9ecef;">
            
            <p>Precisa de ajuda? Nossa equipe está pronta para ajudar!</p>
            <p>📧 Email: suporte@totemsystem.com.br | 📱 WhatsApp: (11) 99999-9999</p>
            
            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                TotemSystem - Sistema de Gestão para Restaurantes<br>
                © <?= date('Y') ?> Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>