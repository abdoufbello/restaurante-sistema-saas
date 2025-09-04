<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRestaurantsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 18,
                'unique'     => true,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'unique'     => true,
            ],
            'subscription_plan' => [
                'type'       => 'ENUM',
                'constraint' => ['trial', 'starter', 'professional', 'enterprise'],
                'default'    => 'trial',
            ],
            'subscription_expires' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'subscription_status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'expired', 'cancelled', 'suspended'],
                'default'    => 'active',
            ],
            'max_totems' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'max_orders_per_month' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 100,
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
        $this->forge->addKey('cnpj');
        $this->forge->addKey('email');
        $this->forge->addKey('subscription_plan');
        $this->forge->addKey('subscription_status');
        
        $this->forge->createTable('restaurants');
    }

    public function down()
    {
        $this->forge->dropTable('restaurants');
    }
}