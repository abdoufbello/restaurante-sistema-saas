<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Restaurant Seeder
 * Creates sample restaurant data for testing
 */
class RestaurantSeeder extends Seeder
{
    public function run()
    {
        // Sample restaurant data
        $restaurantData = [
            'cnpj' => '12345678000195',
            'name' => 'Restaurante Exemplo Ltda',
            'trade_name' => 'Sabores do Brasil',
            'email' => 'contato@saboresdobrasil.com.br',
            'phone' => '11987654321',
            'address' => 'Rua das Flores, 123, Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234567',
            'status' => 'active',
            'subscription_plan' => 'premium',
            'subscription_expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'settings' => json_encode([
                'currency' => 'BRL',
                'timezone' => 'America/Sao_Paulo',
                'language' => 'pt-BR',
                'tax_rate' => 0.00,
                'service_fee' => 10.00,
                'kiosk_theme' => 'default',
                'printer_enabled' => true,
                'scanner_enabled' => true,
                'payment_methods' => [
                    'pix' => true,
                    'credit_card' => true,
                    'debit_card' => true,
                    'cash' => true
                ]
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Insert restaurant
        $this->db->table('restaurants')->insert($restaurantData);
        $restaurantId = $this->db->insertID();

        // Create admin employee
        $adminData = [
            'restaurant_id' => $restaurantId,
            'username' => 'admin',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'full_name' => 'Administrador do Sistema',
            'email' => 'admin@saboresdobrasil.com.br',
            'phone' => '11987654321',
            'role' => 'admin',
            'permissions' => json_encode([
                'dashboard' => true,
                'dishes' => ['create', 'read', 'update', 'delete'],
                'categories' => ['create', 'read', 'update', 'delete'],
                'orders' => ['read', 'update', 'delete'],
                'employees' => ['create', 'read', 'update', 'delete'],
                'reports' => ['read'],
                'settings' => ['read', 'update'],
                'kiosk' => ['read']
            ]),
            'status' => 'active',
            'login_attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->table('employees')->insert($adminData);

        // Create sample categories
        $categories = [
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Entradas',
                'description' => 'Pratos para começar a refeição',
                'icon' => 'utensils',
                'color' => '#FF6B6B',
                'status' => 'active',
                'sort_order' => 1,
                'is_visible_kiosk' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Pratos Principais',
                'description' => 'Pratos principais do cardápio',
                'icon' => 'drumstick-bite',
                'color' => '#4ECDC4',
                'status' => 'active',
                'sort_order' => 2,
                'is_visible_kiosk' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Sobremesas',
                'description' => 'Doces e sobremesas',
                'icon' => 'ice-cream',
                'color' => '#FFEAA7',
                'status' => 'active',
                'sort_order' => 3,
                'is_visible_kiosk' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Bebidas',
                'description' => 'Bebidas e sucos',
                'icon' => 'glass-cheers',
                'color' => '#45B7D1',
                'status' => 'active',
                'sort_order' => 4,
                'is_visible_kiosk' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('categories')->insertBatch($categories);

        // Get category IDs
        $categoryIds = [];
        $categoriesResult = $this->db->table('categories')
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->getResultArray();
        
        foreach ($categoriesResult as $category) {
            $categoryIds[$category['name']] = $category['id'];
        }

        // Create sample dishes
        $dishes = [
            // Entradas
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryIds['Entradas'],
                'name' => 'Bruschetta Italiana',
                'description' => 'Pão italiano tostado com tomate, manjericão e azeite extra virgem',
                'price' => 18.90,
                'cost_price' => 8.50,
                'ingredients' => json_encode(['Pão italiano', 'Tomate', 'Manjericão', 'Azeite', 'Alho']),
                'allergens' => json_encode(['Glúten']),
                'preparation_time' => 10,
                'calories' => 180,
                'status' => 'active',
                'is_featured' => true,
                'is_available' => true,
                'sort_order' => 1,
                'tags' => json_encode(['vegetariano', 'italiano']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryIds['Entradas'],
                'name' => 'Coxinha de Frango',
                'description' => 'Tradicional coxinha brasileira com frango desfiado',
                'price' => 8.50,
                'cost_price' => 3.20,
                'ingredients' => json_encode(['Massa de batata', 'Frango', 'Temperos', 'Farinha de rosca']),
                'allergens' => json_encode(['Glúten', 'Ovos']),
                'preparation_time' => 15,
                'calories' => 220,
                'status' => 'active',
                'is_featured' => false,
                'is_available' => true,
                'sort_order' => 2,
                'tags' => json_encode(['brasileiro', 'frito']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            // Pratos Principais
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryIds['Pratos Principais'],
                'name' => 'Feijoada Completa',
                'description' => 'Feijoada tradicional com linguiça, bacon, carne seca e acompanhamentos',
                'price' => 45.90,
                'cost_price' => 22.00,
                'ingredients' => json_encode(['Feijão preto', 'Linguiça', 'Bacon', 'Carne seca', 'Arroz', 'Couve', 'Farofa', 'Laranja']),
                'allergens' => json_encode([]),
                'preparation_time' => 25,
                'calories' => 680,
                'status' => 'active',
                'is_featured' => true,
                'is_available' => true,
                'sort_order' => 1,
                'tags' => json_encode(['brasileiro', 'tradicional']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryIds['Pratos Principais'],
                'name' => 'Salmão Grelhado',
                'description' => 'Filé de salmão grelhado com legumes e arroz integral',
                'price' => 52.90,
                'cost_price' => 28.00,
                'ingredients' => json_encode(['Salmão', 'Arroz integral', 'Brócolis', 'Cenoura', 'Abobrinha', 'Temperos']),
                'allergens' => json_encode(['Peixe']),
                'preparation_time' => 20,
                'calories' => 420,
                'status' => 'active',
                'is_featured' => true,
                'is_available' => true,
                'sort_order' => 2,
                'tags' => json_encode(['saudável', 'grelhado', 'peixe']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            // Sobremesas
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryIds['Sobremesas'],
                'name' => 'Brigadeiro Gourmet',
                'description' => 'Brigadeiro artesanal com chocolate belga',
                'price' => 12.90,
                'cost_price' => 4.50,
                'ingredients' => json_encode(['Chocolate belga', 'Leite condensado', 'Manteiga', 'Granulado']),
                'allergens' => json_encode(['Leite', 'Glúten']),
                'preparation_time' => 5,
                'calories' => 180,
                'status' => 'active',
                'is_featured' => false,
                'is_available' => true,
                'sort_order' => 1,
                'tags' => json_encode(['doce', 'brasileiro', 'chocolate']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            // Bebidas
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryIds['Bebidas'],
                'name' => 'Suco de Laranja Natural',
                'description' => 'Suco de laranja natural sem açúcar',
                'price' => 8.90,
                'cost_price' => 3.00,
                'ingredients' => json_encode(['Laranja']),
                'allergens' => json_encode([]),
                'preparation_time' => 3,
                'calories' => 110,
                'status' => 'active',
                'is_featured' => false,
                'is_available' => true,
                'sort_order' => 1,
                'tags' => json_encode(['natural', 'saudável', 'vitamina C']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'restaurant_id' => $restaurantId,
                'category_id' => $categoryIds['Bebidas'],
                'name' => 'Caipirinha',
                'description' => 'Caipirinha tradicional com cachaça e limão',
                'price' => 15.90,
                'cost_price' => 6.00,
                'ingredients' => json_encode(['Cachaça', 'Limão', 'Açúcar', 'Gelo']),
                'allergens' => json_encode([]),
                'preparation_time' => 5,
                'calories' => 200,
                'status' => 'active',
                'is_featured' => true,
                'is_available' => true,
                'sort_order' => 2,
                'tags' => json_encode(['alcoólica', 'brasileiro', 'tradicional']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('dishes')->insertBatch($dishes);

        echo "Dados de exemplo criados com sucesso!\n";
        echo "Restaurante: Sabores do Brasil\n";
        echo "CNPJ: 12.345.678/0001-95\n";
        echo "Usuário admin: admin\n";
        echo "Senha admin: 123456\n";
    }
}