<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptionTables extends Migration
{
    public function up()
    {
        // Plans table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'billing_cycle' => [
                'type' => 'ENUM',
                'constraint' => ['monthly', 'yearly'],
                'default' => 'monthly',
            ],
            'features' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'max_totems' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'max_orders_per_month' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'max_employees' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'has_analytics' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'has_api_access' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'has_custom_branding' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'has_priority_support' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
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
        $this->forge->addKey('slug');
        $this->forge->addKey('is_active');
        $this->forge->createTable('plans');

        // Subscriptions table
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
            'plan_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['trial', 'active', 'past_due', 'canceled', 'suspended'],
                'default' => 'trial',
            ],
            'trial_ends_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'starts_at' => [
                'type' => 'DATETIME',
            ],
            'ends_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'canceled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'payment_gateway_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'last_payment_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'next_payment_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'payment_failures' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'metadata' => [
                'type' => 'JSON',
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
        $this->forge->addKey('plan_id');
        $this->forge->addKey('status');
        $this->forge->addKey('trial_ends_at');
        $this->forge->addKey('next_payment_at');
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('plan_id', 'plans', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('subscriptions');

        // Update restaurants table to add new fields
        $fields = [
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'name'
            ],
            'city' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'address'
            ],
            'state' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'city'
            ],
            'zip_code' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'state'
            ],
            'logo_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'after' => 'email'
            ],
            'website' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'logo_url'
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'website'
            ],
            'cuisine_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'description'
            ],
            'opening_hours' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'cuisine_type'
            ],
            'settings' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'opening_hours'
            ],
            'owner_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'settings'
            ],
            'onboarding_completed' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'owner_id'
            ]
        ];

        $this->forge->addColumn('restaurants', $fields);

        // Add index for slug in restaurants
        $this->forge->addKey('slug');
        $this->forge->processIndexes('restaurants');

        // Payment transactions table for tracking payments
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'subscription_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'restaurant_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 3,
                'default' => 'BRL',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'completed', 'failed', 'refunded'],
                'default' => 'pending',
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'gateway' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'gateway_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'gateway_response' => [
                'type' => 'JSON',
                'null' => true,
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
        $this->forge->addKey('subscription_id');
        $this->forge->addKey('restaurant_id');
        $this->forge->addKey('status');
        $this->forge->addKey('gateway_transaction_id');
        $this->forge->addForeignKey('subscription_id', 'subscriptions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payment_transactions');

        // Usage tracking table
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
            'month' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'unsigned' => true,
            ],
            'year' => [
                'type' => 'YEAR',
            ],
            'orders_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'totems_used' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'employees_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'api_calls' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'storage_used_mb' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'last_updated' => [
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
        $this->forge->addKey(['restaurant_id', 'month', 'year'], false, 'usage_period');
        $this->forge->addKey('restaurant_id');
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('usage_tracking');
    }

    public function down()
    {
        // Drop tables in reverse order
        $this->forge->dropTable('usage_tracking', true);
        $this->forge->dropTable('payment_transactions', true);
        $this->forge->dropTable('subscriptions', true);
        $this->forge->dropTable('plans', true);

        // Remove added columns from restaurants table
        $this->forge->dropColumn('restaurants', [
            'slug',
            'city',
            'state', 
            'zip_code',
            'logo_url',
            'website',
            'description',
            'cuisine_type',
            'opening_hours',
            'settings',
            'owner_id',
            'onboarding_completed'
        ]);
    }
}