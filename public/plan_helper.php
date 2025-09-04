<?php

/**
 * Helper para verificação de funcionalidades baseadas no plano de assinatura
 */

// Função para carregar dados do restaurante
function getRestaurantData($restaurant_id) {
    $file = '../writable/data/restaurants.json';
    if (file_exists($file)) {
        $restaurants = json_decode(file_get_contents($file), true);
        foreach ($restaurants as $restaurant) {
            if ($restaurant['id'] == $restaurant_id) {
                return $restaurant;
            }
        }
    }
    return null;
}

// Definir funcionalidades por plano
function getPlanFeatures() {
    return [
        'trial' => [
            'categories_management' => false,
            'advanced_dishes' => false,
            'advanced_orders' => false,
            'image_upload' => false,
            'prep_time' => false,
            'order_origin' => false,
            'order_time_tracking' => false,
            'category_editing' => false
        ],
        'starter' => [
            'categories_management' => true,
            'advanced_dishes' => false,
            'advanced_orders' => false,
            'image_upload' => false,
            'prep_time' => false,
            'order_origin' => false,
            'order_time_tracking' => false,
            'category_editing' => true
        ],
        'professional' => [
            'categories_management' => true,
            'advanced_dishes' => true,
            'advanced_orders' => true,
            'image_upload' => true,
            'prep_time' => true,
            'order_origin' => true,
            'order_time_tracking' => true,
            'category_editing' => true
        ],
        'enterprise' => [
            'categories_management' => true,
            'advanced_dishes' => true,
            'advanced_orders' => true,
            'image_upload' => true,
            'prep_time' => true,
            'order_origin' => true,
            'order_time_tracking' => true,
            'category_editing' => true
        ]
    ];
}

// Verificar se o plano tem acesso a uma funcionalidade
function hasFeatureAccess($restaurant_id, $feature) {
    $restaurant = getRestaurantData($restaurant_id);
    if (!$restaurant) {
        return false;
    }
    
    $plan = $restaurant['subscription_plan'] ?? 'trial';
    $features = getPlanFeatures();
    
    return $features[$plan][$feature] ?? false;
}

// Verificar se a assinatura está ativa
function isSubscriptionActive($restaurant_id) {
    $restaurant = getRestaurantData($restaurant_id);
    if (!$restaurant) {
        return false;
    }
    
    $expires_at = $restaurant['subscription_expires'] ?? null;
    if (!$expires_at) {
        return false;
    }
    
    return strtotime($expires_at) > time();
}

// Obter nome do plano atual
function getCurrentPlanName($restaurant_id) {
    $restaurant = getRestaurantData($restaurant_id);
    if (!$restaurant) {
        return 'Trial';
    }
    
    $plan = $restaurant['subscription_plan'] ?? 'trial';
    $plan_names = [
        'trial' => 'Trial Gratuito',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise'
    ];
    
    return $plan_names[$plan] ?? 'Trial Gratuito';
}

// Gerar mensagem de upgrade
function getUpgradeMessage($feature) {
    $messages = [
        'categories_management' => 'O gerenciamento completo de categorias está disponível a partir do plano Starter.',
        'advanced_dishes' => 'Funcionalidades avançadas de pratos (upload de imagem, tempo de preparo) estão disponíveis no plano Professional.',
        'advanced_orders' => 'Funcionalidades avançadas de pedidos (origem, tempo de criação) estão disponíveis no plano Professional.',
        'image_upload' => 'Upload de imagens está disponível no plano Professional.',
        'prep_time' => 'Tempo de preparo está disponível no plano Professional.',
        'order_origin' => 'Rastreamento de origem dos pedidos está disponível no plano Professional.',
        'order_time_tracking' => 'Rastreamento de tempo dos pedidos está disponível no plano Professional.',
        'category_editing' => 'Edição de categorias está disponível a partir do plano Starter.'
    ];
    
    return $messages[$feature] ?? 'Esta funcionalidade requer um plano superior.';
}

?>