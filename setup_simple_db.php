<?php

// Script para criar um sistema de banco de dados simples usando arquivos JSON
echo "Configurando sistema de banco de dados simples...\n";

try {
    // Criar diretório para os dados
    $dataDir = __DIR__ . '/writable/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
        echo "Diretório de dados criado: {$dataDir}\n";
    }
    
    // Estrutura das tabelas
    $tables = [
        'restaurants' => [
            'structure' => [
                'id' => 'auto_increment',
                'cnpj' => 'string',
                'name' => 'string',
                'email' => 'string',
                'phone' => 'string',
                'address' => 'text',
                'city' => 'string',
                'state' => 'string',
                'zip_code' => 'string',
                'subscription_plan' => 'string',
                'subscription_expires_at' => 'datetime',
                'status' => 'integer',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime'
            ],
            'data' => []
        ],
        'employees' => [
            'structure' => [
                'id' => 'auto_increment',
                'restaurant_id' => 'integer',
                'username' => 'string',
                'password' => 'string',
                'full_name' => 'string',
                'email' => 'string',
                'phone' => 'string',
                'role' => 'string',
                'permissions' => 'json',
                'status' => 'integer',
                'last_login_at' => 'datetime',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime'
            ],
            'data' => []
        ],
        'categories' => [
            'structure' => [
                'id' => 'auto_increment',
                'restaurant_id' => 'integer',
                'name' => 'string',
                'description' => 'text',
                'image' => 'string',
                'icon' => 'string',
                'color' => 'string',
                'status' => 'integer',
                'sort_order' => 'integer',
                'is_visible_kiosk' => 'integer',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime'
            ],
            'data' => []
        ],
        'dishes' => [
            'structure' => [
                'id' => 'auto_increment',
                'restaurant_id' => 'integer',
                'category_id' => 'integer',
                'name' => 'string',
                'description' => 'text',
                'price' => 'decimal',
                'cost_price' => 'decimal',
                'image' => 'string',
                'ingredients' => 'text',
                'allergens' => 'text',
                'nutritional_info' => 'json',
                'preparation_time' => 'integer',
                'calories' => 'integer',
                'status' => 'integer',
                'is_featured' => 'integer',
                'is_available' => 'integer',
                'stock_quantity' => 'integer',
                'min_stock_alert' => 'integer',
                'sort_order' => 'integer',
                'tags' => 'json',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime'
            ],
            'data' => []
        ],
        'orders' => [
            'structure' => [
                'id' => 'auto_increment',
                'restaurant_id' => 'integer',
                'order_number' => 'string',
                'customer_name' => 'string',
                'customer_phone' => 'string',
                'customer_email' => 'string',
                'order_type' => 'string',
                'table_number' => 'string',
                'items' => 'json',
                'subtotal' => 'decimal',
                'tax_amount' => 'decimal',
                'service_fee' => 'decimal',
                'discount_amount' => 'decimal',
                'total_amount' => 'decimal',
                'payment_method' => 'string',
                'payment_status' => 'string',
                'status' => 'string',
                'notes' => 'text',
                'estimated_ready_at' => 'datetime',
                'prepared_at' => 'datetime',
                'completed_at' => 'datetime',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime'
            ],
            'data' => []
        ]
    ];
    
    // Criar arquivos das tabelas
    foreach ($tables as $tableName => $tableData) {
        $filePath = $dataDir . '/' . $tableName . '.json';
        
        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode($tableData, JSON_PRETTY_PRINT));
            echo "Tabela '{$tableName}' criada!\n";
        } else {
            echo "Tabela '{$tableName}' já existe.\n";
        }
    }
    
    // Inserir dados de exemplo
    echo "\nInserindo dados de exemplo...\n";
    
    // Restaurante de exemplo
    $restaurantsFile = $dataDir . '/restaurants.json';
    $restaurants = json_decode(file_get_contents($restaurantsFile), true);
    
    if (empty($restaurants['data'])) {
        $restaurants['data'][] = [
            'id' => 1,
            'cnpj' => '12.345.678/0001-90',
            'name' => 'Restaurante Demo',
            'email' => 'demo@restaurante.com',
            'phone' => '(11) 99999-9999',
            'address' => 'Rua das Flores, 123',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234-567',
            'subscription_plan' => 'basic',
            'subscription_expires_at' => null,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null
        ];
        file_put_contents($restaurantsFile, json_encode($restaurants, JSON_PRETTY_PRINT));
        echo "Restaurante demo criado!\n";
    }
    
    // Usuário admin de exemplo
    $employeesFile = $dataDir . '/employees.json';
    $employees = json_decode(file_get_contents($employeesFile), true);
    
    if (empty($employees['data'])) {
        $employees['data'][] = [
            'id' => 1,
            'restaurant_id' => 1,
            'username' => 'admin',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'full_name' => 'Administrador',
            'email' => 'admin@restaurante.com',
            'phone' => null,
            'role' => 'admin',
            'permissions' => json_encode(['all']),
            'status' => 1,
            'last_login_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null
        ];
        file_put_contents($employeesFile, json_encode($employees, JSON_PRETTY_PRINT));
        echo "Usuário admin criado (username: admin, password: 123456)!\n";
    }
    
    // Categorias de exemplo
    $categoriesFile = $dataDir . '/categories.json';
    $categories = json_decode(file_get_contents($categoriesFile), true);
    
    if (empty($categories['data'])) {
        $categoryList = [
            ['Entradas', 'Pratos para começar a refeição'],
            ['Pratos Principais', 'Pratos principais do cardápio'],
            ['Sobremesas', 'Doces e sobremesas'],
            ['Bebidas', 'Bebidas diversas']
        ];
        
        foreach ($categoryList as $index => $category) {
            $categories['data'][] = [
                'id' => $index + 1,
                'restaurant_id' => 1,
                'name' => $category[0],
                'description' => $category[1],
                'image' => null,
                'icon' => null,
                'color' => '#000000',
                'status' => 1,
                'sort_order' => $index + 1,
                'is_visible_kiosk' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => null
            ];
        }
        
        file_put_contents($categoriesFile, json_encode($categories, JSON_PRETTY_PRINT));
        echo "Categorias de exemplo criadas!\n";
    }
    
    // Pratos de exemplo
    $dishesFile = $dataDir . '/dishes.json';
    $dishes = json_decode(file_get_contents($dishesFile), true);
    
    if (empty($dishes['data'])) {
        $dishList = [
            ['Salada Caesar', 'Salada com alface, croutons e molho caesar', 18.90, 1],
            ['Hambúrguer Artesanal', 'Hambúrguer 180g com batata rústica', 32.90, 2],
            ['Salmão Grelhado', 'Salmão grelhado com legumes', 45.90, 2],
            ['Pudim de Leite', 'Pudim caseiro com calda de caramelo', 12.90, 3],
            ['Suco Natural', 'Suco de frutas naturais', 8.90, 4]
        ];
        
        foreach ($dishList as $index => $dish) {
            $dishes['data'][] = [
                'id' => $index + 1,
                'restaurant_id' => 1,
                'category_id' => $dish[3],
                'name' => $dish[0],
                'description' => $dish[1],
                'price' => $dish[2],
                'cost_price' => $dish[2] * 0.6,
                'image' => null,
                'ingredients' => null,
                'allergens' => null,
                'nutritional_info' => null,
                'preparation_time' => 15,
                'calories' => null,
                'status' => 1,
                'is_featured' => 0,
                'is_available' => 1,
                'stock_quantity' => null,
                'min_stock_alert' => null,
                'sort_order' => $index + 1,
                'tags' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => null
            ];
        }
        
        file_put_contents($dishesFile, json_encode($dishes, JSON_PRETTY_PRINT));
        echo "Pratos de exemplo criados!\n";
    }
    
    echo "\nSistema de banco de dados simples configurado com sucesso!\n";
    echo "Localização dos dados: {$dataDir}\n";
    echo "\nPróximos passos:\n";
    echo "1. Criar classe de modelo para gerenciar os dados JSON\n";
    echo "2. Configurar as rotas e controllers\n";
    echo "3. Criar as views do sistema\n";
    echo "4. Implementar sistema de autenticação\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}