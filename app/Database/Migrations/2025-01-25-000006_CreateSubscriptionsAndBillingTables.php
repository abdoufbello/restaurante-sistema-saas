<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptionsAndBillingTables extends Migration
{
    public function up()
    {
        // Tabela de planos de assinatura
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'max_totems' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'max_orders_per_month' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'features' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->createTable('subscription_plans');
        
        // Tabela de histórico de pagamentos
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'restaurant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'subscription_plan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'default'    => 'BRL',
            ],
            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'payment_gateway' => [
                'type'       => 'ENUM',
                'constraint' => ['pagseguro', 'mercadopago', 'stripe', 'manual'],
            ],
            'gateway_transaction_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'paid', 'failed', 'cancelled', 'refunded'],
                'default'    => 'pending',
            ],
            'due_date' => [
                'type' => 'DATE',
            ],
            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'invoice_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('restaurant_id');
        $this->forge->addKey('subscription_plan_id');
        $this->forge->addKey('status');
        $this->forge->addKey('due_date');
        $this->forge->addKey(['restaurant_id', 'status']);
        
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('subscription_plan_id', 'subscription_plans', 'id', 'RESTRICT', 'RESTRICT');
        
        $this->forge->createTable('billing_history');
        
        // Tabela de uso mensal (para controle de limites)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'restaurant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'year' => [
                'type'       => 'INT',
                'constraint' => 4,
            ],
            'month' => [
                'type'       => 'INT',
                'constraint' => 2,
            ],
            'orders_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'totems_used' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('restaurant_id');
        $this->forge->addKey(['restaurant_id', 'year', 'month'], false, 'unique_usage_month');
        
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('monthly_usage');
    }

    public function down()
    {
        $this->forge->dropTable('monthly_usage');
        $this->forge->dropTable('billing_history');
        $this->forge->dropTable('subscription_plans');
    }
}