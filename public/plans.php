<?php
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['restaurant_id'])) {
    header('Location: simple_auth.php');
    exit;
}

// Função para carregar dados JSON
function loadJsonData($filename) {
    $filepath = '../writable/data/' . $filename;
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        return json_decode($content, true) ?: [];
    }
    return [];
}

// Função para salvar dados JSON
function saveJsonData($filename, $data) {
    $filepath = '../writable/data/' . $filename;
    return file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$restaurants = loadJsonData('restaurants.json');
$currentRestaurant = null;

foreach ($restaurants as $restaurant) {
    if ($restaurant['id'] == $_SESSION['restaurant_id']) {
        $currentRestaurant = $restaurant;
        break;
    }
}

if (!$currentRestaurant) {
    header('Location: simple_auth.php');
    exit;
}

// Definir planos disponíveis
$plans = [
    'trial' => [
        'name' => 'Trial Gratuito',
        'price' => 0,
        'duration' => 30,
        'features' => [
            '1 totem ativo',
            '50 pedidos por mês',
            '10 pratos no cardápio',
            'Suporte por email',
            'Relatórios básicos'
        ],
        'limits' => [
            'totems' => 1,
            'orders_per_month' => 50,
            'dishes' => 10
        ],
        'color' => 'secondary'
    ],
    'starter' => [
        'name' => 'Starter',
        'price' => 99,
        'duration' => 30,
        'features' => [
            '2 totems ativos',
            '200 pedidos por mês',
            '50 pratos no cardápio',
            'Suporte por email e chat',
            'Relatórios avançados',
            'Integração com delivery'
        ],
        'limits' => [
            'totems' => 2,
            'orders_per_month' => 200,
            'dishes' => 50
        ],
        'color' => 'primary'
    ],
    'professional' => [
        'name' => 'Professional',
        'price' => 199,
        'duration' => 30,
        'features' => [
            '5 totems ativos',
            '1000 pedidos por mês',
            '200 pratos no cardápio',
            'Suporte prioritário 24/7',
            'Relatórios completos',
            'Integração com delivery',
            'API personalizada',
            'White label básico'
        ],
        'limits' => [
            'totems' => 5,
            'orders_per_month' => 1000,
            'dishes' => 200
        ],
        'color' => 'success'
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 399,
        'duration' => 30,
        'features' => [
            'Totems ilimitados',
            'Pedidos ilimitados',
            'Pratos ilimitados',
            'Suporte dedicado 24/7',
            'Relatórios personalizados',
            'Todas as integrações',
            'API completa',
            'White label completo',
            'Treinamento personalizado'
        ],
        'limits' => [
            'totems' => -1, // -1 = ilimitado
            'orders_per_month' => -1,
            'dishes' => -1
        ],
        'color' => 'warning'
    ]
];

$message = '';
$messageType = '';

// Processar mudança de plano
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_plan'])) {
    $newPlan = $_POST['new_plan'];
    
    if (isset($plans[$newPlan])) {
        // Atualizar plano do restaurante
        foreach ($restaurants as &$restaurant) {
            if ($restaurant['id'] == $_SESSION['restaurant_id']) {
                $restaurant['subscription_plan'] = $newPlan;
                $restaurant['subscription_expires'] = date('Y-m-d H:i:s', strtotime('+' . $plans[$newPlan]['duration'] . ' days'));
                $restaurant['updated_at'] = date('Y-m-d H:i:s');
                $currentRestaurant = $restaurant;
                break;
            }
        }
        
        saveJsonData('restaurants.json', $restaurants);
        $message = 'Plano alterado com sucesso para ' . $plans[$newPlan]['name'] . '!';
        $messageType = 'success';
    }
}

$currentPlan = $plans[$currentRestaurant['subscription_plan']] ?? $plans['trial'];
$expiresAt = new DateTime($currentRestaurant['subscription_expires']);
$now = new DateTime();
$daysLeft = $now->diff($expiresAt)->days;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos e Assinaturas - Prato Rápido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff6b00;
            --secondary-yellow: #ffc700;
            --dark-gray: #2c3e50;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
        .plan-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
        }
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .plan-card.current {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .plan-price {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .subscription-status {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .bg-primary {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-yellow) 100%) !important;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-utensils me-2"></i>Prato Rápido
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $messageType === 'error' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Status da Assinatura Atual -->
        <div class="subscription-status">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-1">
                        <i class="fas fa-crown me-2"></i>
                        Plano Atual: <?= $currentPlan['name'] ?>
                    </h4>
                    <p class="mb-0">
                        <i class="fas fa-calendar me-2"></i>
                        <?php if ($daysLeft > 0): ?>
                            Expira em <?= $daysLeft ?> dias (<?= $expiresAt->format('d/m/Y') ?>)
                        <?php else: ?>
                            <span class="text-warning">⚠️ Plano expirado!</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="h2 mb-0">
                        <?php if ($currentPlan['price'] > 0): ?>
                            R$ <?= number_format($currentPlan['price'], 2, ',', '.') ?>/mês
                        <?php else: ?>
                            Gratuito
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-center mb-5">
            <i class="fas fa-rocket me-2"></i>
            Escolha o Melhor Plano para seu Restaurante
        </h2>

        <div class="row">
            <?php foreach ($plans as $planKey => $plan): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card plan-card h-100 <?= $planKey === $currentRestaurant['subscription_plan'] ? 'current' : '' ?>">
                        <div class="card-header bg-<?= $plan['color'] ?> text-white text-center">
                            <h5 class="card-title mb-0">
                                <?= $plan['name'] ?>
                                <?php if ($planKey === $currentRestaurant['subscription_plan']): ?>
                                    <span class="badge bg-light text-dark ms-2">Atual</span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="plan-price text-<?= $plan['color'] ?> mb-3">
                                <?php if ($plan['price'] > 0): ?>
                                    R$ <?= number_format($plan['price'], 0, ',', '.') ?>
                                    <small class="text-muted">/mês</small>
                                <?php else: ?>
                                    Grátis
                                    <small class="text-muted">/30 dias</small>
                                <?php endif; ?>
                            </div>
                            
                            <ul class="feature-list text-start">
                                <?php foreach ($plan['features'] as $feature): ?>
                                    <li>
                                        <i class="fas fa-check text-success me-2"></i>
                                        <?= $feature ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <?php if ($planKey !== $currentRestaurant['subscription_plan']): ?>
                                <form method="POST" class="d-grid">
                                    <input type="hidden" name="new_plan" value="<?= $planKey ?>">
                                    <button type="submit" name="change_plan" class="btn btn-<?= $plan['color'] ?>">
                                        <?php if ($plan['price'] > $currentPlan['price']): ?>
                                            <i class="fas fa-arrow-up me-2"></i>Fazer Upgrade
                                        <?php elseif ($plan['price'] < $currentPlan['price']): ?>
                                            <i class="fas fa-arrow-down me-2"></i>Fazer Downgrade
                                        <?php else: ?>
                                            <i class="fas fa-exchange-alt me-2"></i>Alterar Plano
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary w-100" disabled>
                                    <i class="fas fa-check me-2"></i>Plano Atual
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Comparação de Recursos -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="text-center mb-4">
                    <i class="fas fa-table me-2"></i>
                    Comparação Detalhada de Recursos
                </h3>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Recurso</th>
                                <th class="text-center">Trial</th>
                                <th class="text-center">Starter</th>
                                <th class="text-center">Professional</th>
                                <th class="text-center">Enterprise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Totems Ativos</strong></td>
                                <td class="text-center">1</td>
                                <td class="text-center">2</td>
                                <td class="text-center">5</td>
                                <td class="text-center">Ilimitado</td>
                            </tr>
                            <tr>
                                <td><strong>Pedidos por Mês</strong></td>
                                <td class="text-center">50</td>
                                <td class="text-center">200</td>
                                <td class="text-center">1.000</td>
                                <td class="text-center">Ilimitado</td>
                            </tr>
                            <tr>
                                <td><strong>Pratos no Cardápio</strong></td>
                                <td class="text-center">10</td>
                                <td class="text-center">50</td>
                                <td class="text-center">200</td>
                                <td class="text-center">Ilimitado</td>
                            </tr>
                            <tr>
                                <td><strong>Suporte</strong></td>
                                <td class="text-center">Email</td>
                                <td class="text-center">Email + Chat</td>
                                <td class="text-center">24/7 Prioritário</td>
                                <td class="text-center">Dedicado 24/7</td>
                            </tr>
                            <tr>
                                <td><strong>Relatórios</strong></td>
                                <td class="text-center">Básicos</td>
                                <td class="text-center">Avançados</td>
                                <td class="text-center">Completos</td>
                                <td class="text-center">Personalizados</td>
                            </tr>
                            <tr>
                                <td><strong>API</strong></td>
                                <td class="text-center">❌</td>
                                <td class="text-center">❌</td>
                                <td class="text-center">✅</td>
                                <td class="text-center">✅ Completa</td>
                            </tr>
                            <tr>
                                <td><strong>White Label</strong></td>
                                <td class="text-center">❌</td>
                                <td class="text-center">❌</td>
                                <td class="text-center">Básico</td>
                                <td class="text-center">✅ Completo</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="text-center mb-4">
                    <i class="fas fa-question-circle me-2"></i>
                    Perguntas Frequentes
                </h3>
                
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Posso alterar meu plano a qualquer momento?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sim! Você pode fazer upgrade ou downgrade do seu plano a qualquer momento. As alterações entram em vigor imediatamente.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                O que acontece se eu exceder os limites do meu plano?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Você receberá notificações quando estiver próximo dos limites. Se exceder, sugeriremos um upgrade automático ou você pode continuar com taxas adicionais.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oferecemos suporte por email para todos os planos, chat ao vivo para Starter+, e suporte telefônico 24/7 para Professional e Enterprise.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>