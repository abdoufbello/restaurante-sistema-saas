<?php

// Script para configurar SQLite como banco de dados temporário
echo "Configurando SQLite como banco de dados...\n";

try {
    // Criar diretório para o banco SQLite
    $dbDir = __DIR__ . '/writable/database';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
        echo "Diretório do banco criado: {$dbDir}\n";
    }
    
    // Caminho do banco SQLite
    $dbPath = $dbDir . '/restaurant_system.db';
    
    // Conectar ao SQLite
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado ao SQLite com sucesso!\n";
    
    // Criar tabela de migrações
    $createMigrationTable = "
        CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            version VARCHAR(255) NOT NULL,
            class VARCHAR(255) NOT NULL,
            `group` VARCHAR(255) NOT NULL,
            namespace VARCHAR(255) NOT NULL,
            time INTEGER NOT NULL,
            batch INTEGER NOT NULL
        );
    ";
    
    $pdo->exec($createMigrationTable);
    echo "Tabela de migrações criada/verificada.\n";
    
    // SQL das migrações adaptadas para SQLite
    $migrations = [
        '2024-01-01-000001' => [
            'name' => 'CreateRestaurantsTable',
            'sql' => "
                CREATE TABLE IF NOT EXISTS restaurants (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    cnpj VARCHAR(18) NOT NULL UNIQUE,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    phone VARCHAR(20),
                    address TEXT,
                    city VARCHAR(100),
                    state VARCHAR(2),
                    zip_code VARCHAR(10),
                    subscription_plan VARCHAR(20) DEFAULT 'basic',
                    subscription_expires_at DATETIME,
                    status INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME
                );
            "
        ],
        '2024-01-01-000002' => [
            'name' => 'CreateEmployeesTable',
            'sql' => "
                CREATE TABLE IF NOT EXISTS employees (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255),
                    phone VARCHAR(20),
                    role VARCHAR(20) DEFAULT 'operator',
                    permissions TEXT,
                    status INTEGER DEFAULT 1,
                    last_login_at DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
                );
            "
        ],
        '2024-01-01-000003' => [
            'name' => 'CreateCategoriesTable', 
            'sql' => "
                CREATE TABLE IF NOT EXISTS categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    image VARCHAR(500),
                    icon VARCHAR(100),
                    color VARCHAR(7) DEFAULT '#000000',
                    status INTEGER DEFAULT 1,
                    sort_order INTEGER DEFAULT 0,
                    is_visible_kiosk INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
                );
            "
        ],
        '2024-01-01-000004' => [
            'name' => 'CreateDishesTable',
            'sql' => "
                CREATE TABLE IF NOT EXISTS dishes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    category_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    price DECIMAL(10,2) NOT NULL,
                    cost_price DECIMAL(10,2),
                    image VARCHAR(500),
                    ingredients TEXT,
                    allergens TEXT,
                    nutritional_info TEXT,
                    preparation_time INTEGER,
                    calories INTEGER,
                    status INTEGER DEFAULT 1,
                    is_featured INTEGER DEFAULT 0,
                    is_available INTEGER DEFAULT 1,
                    stock_quantity INTEGER,
                    min_stock_alert INTEGER,
                    sort_order INTEGER DEFAULT 0,
                    tags TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                );
            "
        ],
        '2024-01-01-000005' => [
            'name' => 'CreateOrdersTable',
            'sql' => "
                CREATE TABLE IF NOT EXISTS orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    order_number VARCHAR(50) NOT NULL UNIQUE,
                    customer_name VARCHAR(255),
                    customer_phone VARCHAR(20),
                    customer_email VARCHAR(255),
                    order_type VARCHAR(20) DEFAULT 'dine_in',
                    table_number VARCHAR(10),
                    items TEXT NOT NULL,
                    subtotal DECIMAL(10,2) NOT NULL,
                    tax_amount DECIMAL(10,2) DEFAULT 0.00,
                    service_fee DECIMAL(10,2) DEFAULT 0.00,
                    discount_amount DECIMAL(10,2) DEFAULT 0.00,
                    total_amount DECIMAL(10,2) NOT NULL,
                    payment_method VARCHAR(20),
                    payment_status VARCHAR(20) DEFAULT 'pending',
                    status VARCHAR(20) DEFAULT 'pending',
                    notes TEXT,
                    estimated_ready_at DATETIME,
                    prepared_at DATETIME,
                    completed_at DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
                );
            "
        ]
    ];
    
    // Executar cada migração
    foreach ($migrations as $version => $migration) {
        // Verificar se já foi executada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE version = ?");
        $stmt->execute([$version]);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            echo "Executando migração: {$migration['name']}...\n";
            
            // Executar SQL
            $pdo->exec($migration['sql']);
            
            // Registrar migração
            $stmt = $pdo->prepare(
                "INSERT INTO migrations (version, class, `group`, namespace, time, batch) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$version, $migration['name'], 'default', 'App\\Database\\Migrations', time(), 1]);
            
            echo "Migração {$migration['name']} executada com sucesso!\n";
        } else {
            echo "Migração {$migration['name']} já foi executada.\n";
        }
    }
    
    // Inserir dados de exemplo
    echo "\nInserindo dados de exemplo...\n";
    
    // Restaurante de exemplo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM restaurants WHERE cnpj = ?");
    $stmt->execute(['12.345.678/0001-90']);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO restaurants (cnpj, name, email, phone, address, city, state, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            '12.345.678/0001-90',
            'Restaurante Demo',
            'demo@restaurante.com',
            '(11) 99999-9999',
            'Rua das Flores, 123',
            'São Paulo',
            'SP',
            '01234-567'
        ]);
        echo "Restaurante demo criado!\n";
    }
    
    // Usuário admin de exemplo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO employees (restaurant_id, username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            1,
            'admin',
            password_hash('123456', PASSWORD_DEFAULT),
            'Administrador',
            'admin@restaurante.com',
            'admin'
        ]);
        echo "Usuário admin criado (username: admin, password: 123456)!\n";
    }
    
    // Categorias de exemplo
    $categories = [
        ['Entradas', 'Pratos para começar a refeição'],
        ['Pratos Principais', 'Pratos principais do cardápio'],
        ['Sobremesas', 'Doces e sobremesas'],
        ['Bebidas', 'Bebidas diversas']
    ];
    
    foreach ($categories as $index => $category) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE restaurant_id = ? AND name = ?");
        $stmt->execute([1, $category[0]]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO categories (restaurant_id, name, description, sort_order) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([1, $category[0], $category[1], $index + 1]);
            echo "Categoria '{$category[0]}' criada!\n";
        }
    }
    
    echo "\nBanco de dados SQLite configurado com sucesso!\n";
    echo "Localização: {$dbPath}\n";
    echo "\nPróximos passos:\n";
    echo "1. Atualizar app/Config/Database.php para usar SQLite\n";
    echo "2. Configurar as rotas e controllers\n";
    echo "3. Criar as views do sistema\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}