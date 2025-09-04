<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Plano ideal para pequenos restaurantes que estão começando',
                'price' => 99.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'Até 2 totems de autoatendimento',
                    'Até 500 pedidos por mês',
                    'Até 5 funcionários',
                    'Relatórios básicos',
                    'Suporte por email',
                    'Cardápio digital',
                    'Gestão de pedidos',
                    'Controle de estoque básico'
                ]),
                'max_totems' => 2,
                'max_orders_per_month' => 500,
                'max_employees' => 5,
                'has_analytics' => 0,
                'has_api_access' => 0,
                'has_custom_branding' => 0,
                'has_priority_support' => 0,
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Plano completo para restaurantes em crescimento',
                'price' => 199.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'Até 5 totems de autoatendimento',
                    'Até 2000 pedidos por mês',
                    'Até 15 funcionários',
                    'Analytics avançado',
                    'Acesso à API',
                    'Suporte prioritário',
                    'Cardápio digital avançado',
                    'Gestão completa de estoque',
                    'Relatórios financeiros',
                    'Integração com delivery',
                    'Programa de fidelidade',
                    'Cupons e promoções'
                ]),
                'max_totems' => 5,
                'max_orders_per_month' => 2000,
                'max_employees' => 15,
                'has_analytics' => 1,
                'has_api_access' => 1,
                'has_custom_branding' => 0,
                'has_priority_support' => 1,
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solução empresarial para grandes redes de restaurantes',
                'price' => 399.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'Totems ilimitados',
                    'Pedidos ilimitados',
                    'Funcionários ilimitados',
                    'Analytics completo',
                    'Acesso total à API',
                    'Marca personalizada',
                    'Suporte dedicado 24/7',
                    'Integração personalizada',
                    'Multi-localização',
                    'Dashboard executivo',
                    'Relatórios personalizados',
                    'Backup automático',
                    'Treinamento especializado',
                    'Consultoria estratégica',
                    'SLA garantido'
                ]),
                'max_totems' => null,
                'max_orders_per_month' => null,
                'max_employees' => null,
                'has_analytics' => 1,
                'has_api_access' => 1,
                'has_custom_branding' => 1,
                'has_priority_support' => 1,
                'is_active' => 1,
                'sort_order' => 3,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Insert plans
        foreach ($plans as $plan) {
            $this->db->table('plans')->insert($plan);
        }

        echo "Plans seeded successfully!\n";
    }
}