<?php
$featureNames = [
    'online_ordering' => 'Pedidos Online',
    'basic_analytics' => 'Relatórios Básicos',
    'customer_support' => 'Suporte ao Cliente',
    'advanced_analytics' => 'Analytics Avançado',
    'custom_branding' => 'Marca Personalizada',
    'api_access' => 'Acesso à API',
    'priority_support' => 'Suporte Prioritário',
    'white_label' => 'White Label',
    'custom_integrations' => 'Integrações Personalizadas',
    'dedicated_manager' => 'Gerente Dedicado'
];

echo $featureNames[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
?>