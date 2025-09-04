<?php

// Script para executar migrações usando SQLite
echo "Executando migrações do banco de dados SQLite...\n";

try {
    // Criar diretório do banco se não existir
    $dbDir = __DIR__ . '/writable/database';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
        echo "Diretório do banco criado: {$dbDir}\n";
    }
    
    // Conectar ao SQLite
    $dbPath = $dbDir . '/ospos_saas.db';
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado ao banco SQLite: {$dbPath}\n";
    
    // Criar tabela de migrações
    $createMigrationTable = "
        CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            version VARCHAR(255) NOT NULL,
            class VARCHAR(255) NOT NULL,
            namespace VARCHAR(255) NOT NULL,
            time INTEGER NOT NULL,
            batch INTEGER NOT NULL
        );
    ";
    
    $pdo->exec($createMigrationTable);
    echo "Tabela de migrações criada/verificada.\n";
    
    // SQL das migrações
    $migrations = [
        '2025-01-25-000001' => [
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
                    subscription_plan_id INTEGER,
                    subscription_status VARCHAR(20) DEFAULT 'trial',
                    subscription_expires_at DATETIME,
                    trial_ends_at DATETIME,
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            "
        ],
        '2025-01-25-000002' => [
            'name' => 'CreateEmployeesTable',
            'sql' => "
                CREATE TABLE IF NOT EXISTS employees (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    role VARCHAR(20) DEFAULT 'employee',
                    permissions TEXT,
                    is_active BOOLEAN DEFAULT 1,
                    last_login DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    UNIQUE(restaurant_id, username),
                    UNIQUE(restaurant_id, email)
                );
            "
        ],
        '2025-01-25-000003' => [
            'name' => 'CreateCategoriesTable',
            'sql' => "
                CREATE TABLE IF NOT EXISTS categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    color VARCHAR(7) DEFAULT '#007bff',
                    icon VARCHAR(50),
                    is_active BOOLEAN DEFAULT 1,
                    is_visible_kiosk BOOLEAN DEFAULT 1,
                    sort_order INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
                );
            "
        ],
        '2025-01-25-000004' => [
            'name' => 'CreateDishesTable',
            'sql' => "
                CREATE TABLE IF NOT EXISTS dishes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    category_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    price DECIMAL(10,2) NOT NULL,
                    image VARCHAR(255),
                    image_url VARCHAR(500),
                    ingredients TEXT,
                    allergens TEXT,
                    calories INTEGER,
                    preparation_time INTEGER DEFAULT 15,
                    is_available BOOLEAN DEFAULT 1,
                    is_featured BOOLEAN DEFAULT 0,
                    sort_order INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                );
            "
        ],
        '2025-01-25-000005' => [
            'name' => 'CreateOrdersTable',
            'sql' => "
                CREATE TABLE IF NOT EXISTS orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    order_number VARCHAR(20) NOT NULL,
                    customer_name VARCHAR(255),
                    customer_email VARCHAR(255),
                    customer_phone VARCHAR(20),
                    items TEXT NOT NULL,
                    subtotal DECIMAL(10,2) NOT NULL,
                    tax DECIMAL(10,2) DEFAULT 0,
                    discount DECIMAL(10,2) DEFAULT 0,
                    total DECIMAL(10,2) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    payment_method VARCHAR(50),
                    payment_status VARCHAR(20) DEFAULT 'pending',
                    payment_id VARCHAR(255),
                    kiosk_id VARCHAR(50),
                    table_number INTEGER,
                    notes TEXT,
                    estimated_time INTEGER DEFAULT 30,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    UNIQUE(restaurant_id, order_number)
                );
            "
        ],
        '2025-01-25-000006' => [
            'name' => 'CreateSubscriptionsAndBillingTables',
            'sql' => "
                CREATE TABLE IF NOT EXISTS subscription_plans (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    price DECIMAL(10,2) NOT NULL,
                    max_totems INTEGER NOT NULL,
                    max_orders_per_month INTEGER NOT NULL,
                    features TEXT,
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                
                CREATE TABLE IF NOT EXISTS billing_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    subscription_plan_id INTEGER NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'BRL',
                    payment_method VARCHAR(50),
                    payment_gateway VARCHAR(50),
                    gateway_transaction_id VARCHAR(255),
                    status VARCHAR(20) DEFAULT 'pending',
                    due_date DATE NOT NULL,
                    paid_at DATETIME,
                    invoice_url VARCHAR(500),
                    notes TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans(id)
                );
                
                CREATE TABLE IF NOT EXISTS monthly_usage (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    restaurant_id INTEGER NOT NULL,
                    year INTEGER NOT NULL,
                    month INTEGER NOT NULL,
                    orders_count INTEGER DEFAULT 0,
                    totems_used INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    UNIQUE(restaurant_id, year, month)
                );
            "
        ]
    ];
    
    // Executar migrações
    foreach ($migrations as $version => $migration) {
        // Verificar se a migração já foi executada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE version = ?");
        $stmt->execute([$version]);
        
        if ($stmt->fetchColumn() == 0) {
            echo "Executando migração: {$migration['name']}...\n";
            
            // Executar SQL da migração
            $pdo->exec($migration['sql']);
            
            // Registrar migração como executada
            $stmt = $pdo->prepare("
                INSERT INTO migrations (version, class, namespace, time, batch) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $version,
                $migration['name'],
                'App\\Database\\Migrations',
                time()
            ]);
            
            echo "Migração {$migration['name']} executada com sucesso!\n";
        } else {
            echo "Migração {$migration['name']} já foi executada.\n";
        }
    }
    
    // Inserir planos de assinatura padrão
    echo "\nInserindo planos de assinatura padrão...\n";
    
    $plans = [
        ['Starter', 99.00, 2, 500],
        ['Professional', 199.00, 5, 2000],
        ['Enterprise', 399.00, 15, 10000]
    ];
    
    foreach ($plans as $plan) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscription_plans WHERE name = ?");
        $stmt->execute([$plan[0]]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO subscription_plans (name, price, max_totems, max_orders_per_month, features) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $features = json_encode([
                'dashboard' => true,
                'kiosk' => true,
                'reports' => $plan[0] !== 'Starter',
                'api_access' => $plan[0] === 'Enterprise',
                'priority_support' => $plan[0] === 'Enterprise'
            ]);
            $stmt->execute([$plan[0], $plan[1], $plan[2], $plan[3], $features]);
            echo "Plano {$plan[0]} criado!\n";
        } else {
            echo "Plano {$plan[0]} já existe.\n";
        }
    }
    
    echo "\n✅ Todas as migrações foram executadas com sucesso!\n";
    echo "📊 Banco de dados SQLite criado em: {$dbPath}\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}