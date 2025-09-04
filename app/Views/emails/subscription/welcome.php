<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao TotemSystem</title>
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
        .welcome-title {
            color: #28a745;
            font-size: 24px;
            margin-bottom: 10px;
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
        .plan-price {
            font-size: 18px;
            color: #28a745;
            font-weight: bold;
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
        .cta-secondary {
            background: #6c757d;
        }
        .cta-secondary:hover {
            background: #545b62;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .next-steps {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #004085;
            margin-top: 0;
        }
        .step {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 3px;
        }
        .step-number {
            display: inline-block;
            background: #007bff;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            text-align: center;
            line-height: 25px;
            margin-right: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🍽️ TotemSystem</div>
            <h1 class="welcome-title">Bem-vindo, <?= esc($restaurant['name']) ?>!</h1>
            <p>Sua assinatura está ativa e você já pode começar a usar o sistema.</p>
        </div>

        <div class="plan-info">
            <div class="plan-name"><?= esc($plan['name']) ?></div>
            <div class="plan-price">R$ <?= number_format($plan['price'], 2, ',', '.') ?>/<?= $plan['billing_cycle'] === 'monthly' ? 'mês' : ($plan['billing_cycle'] === 'yearly' ? 'ano' : 'trimestre') ?></div>
        </div>

        <div class="features">
            <h3>Recursos inclusos no seu plano:</h3>
            <ul>
                <?php 
                $features = json_decode($plan['features'], true);
                foreach ($features as $feature): 
                ?>
                <li><?= esc($feature) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="next-steps">
            <h3>Próximos passos para começar:</h3>
            
            <div class="step">
                <span class="step-number">1</span>
                <strong>Acesse seu dashboard</strong> - Configure as informações básicas do seu restaurante
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <strong>Configure seus totems</strong> - Adicione e configure os totems de autoatendimento
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <strong>Cadastre seu cardápio</strong> - Adicione pratos, preços e categorias
            </div>
            
            <div class="step">
                <span class="step-number">4</span>
                <strong>Adicione funcionários</strong> - Convide sua equipe para usar o sistema
            </div>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= $dashboard_url ?>" class="cta-button">Acessar Dashboard</a>
            <a href="<?= $support_url ?>" class="cta-button cta-secondary">Central de Ajuda</a>
        </div>

        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
            <strong>💡 Dica:</strong> Recomendamos começar configurando as informações básicas do restaurante e depois partir para a configuração dos totems. Nossa equipe de suporte está disponível para ajudar!
        </div>

        <div class="footer">
            <p><strong>Informações da sua assinatura:</strong></p>
            <p>Plano: <?= esc($plan['name']) ?> | Início: <?= date('d/m/Y', strtotime($subscription['start_date'])) ?></p>
            <p>Próxima cobrança: <?= date('d/m/Y', strtotime($subscription['next_billing_date'])) ?></p>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e9ecef;">
            
            <p>Este email foi enviado para <?= esc($restaurant['email']) ?></p>
            <p>Se você tiver dúvidas, entre em contato conosco através do <a href="<?= $support_url ?>">suporte</a>.</p>
            
            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                TotemSystem - Sistema de Gestão para Restaurantes<br>
                © <?= date('Y') ?> Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>