<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentTables extends Migration
{
    public function up()
    {
        // Tabela de gateways de pagamento
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'restaurant_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'gateway_type' => [
                'type' => 'ENUM',
                'constraint' => ['pix', 'stripe', 'pagseguro', 'mercadopago', 'paypal', 'picpay', 'nubank'],
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'credentials' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON encrypted credentials',
            ],
            'webhook_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Gateway specific settings',
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
        $this->forge->addKey(['restaurant_id', 'gateway_type']);
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payment_gateways');

        // Tabela de transações de pagamento
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'restaurant_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'order_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'gateway_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'gateway_type' => [
                'type' => 'ENUM',
                'constraint' => ['pix', 'stripe', 'pagseguro', 'mercadopago', 'paypal', 'picpay', 'nubank'],
                'null' => false,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Gateway transaction ID',
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'External reference ID',
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 3,
                'default' => 'BRL',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded', 'partially_refunded'],
                'default' => 'pending',
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Credit card, debit, pix, etc',
            ],
            'gateway_response' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Full gateway response',
            ],
            'fees' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
                'comment' => 'Gateway fees',
            ],
            'net_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Amount after fees',
            ],
            'processed_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('order_id');
        $this->forge->addKey('transaction_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('gateway_id', 'payment_gateways', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payment_transactions');

        // Tabela de webhooks de pagamento
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'restaurant_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'gateway_type' => [
                'type' => 'ENUM',
                'constraint' => ['pix', 'stripe', 'pagseguro', 'mercadopago', 'paypal', 'picpay', 'nubank'],
                'null' => false,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'payload' => [
                'type' => 'JSON',
                'null' => false,
                'comment' => 'Webhook payload',
            ],
            'headers' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Request headers',
            ],
            'processed' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'processed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('restaurant_id');
        $this->forge->addKey('gateway_type');
        $this->forge->addKey('processed');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payment_webhooks');
    }

    public function down()
    {
        $this->forge->dropTable('payment_webhooks');
        $this->forge->dropTable('payment_transactions');
        $this->forge->dropTable('payment_gateways');
    }
}