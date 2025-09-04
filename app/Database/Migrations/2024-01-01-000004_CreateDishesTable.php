<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to create dishes table
 */
class CreateDishesTable extends Migration
{
    public function up()
    {
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
            'category_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'cost_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'image' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ingredients' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'allergens' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'nutritional_info' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'preparation_time' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'Preparation time in minutes',
            ],
            'calories' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive', 'out_of_stock'],
                'default' => 'active',
            ],
            'is_featured' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'is_available' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'stock_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'min_stock_alert' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'tags' => [
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey(['restaurant_id', 'status']);
        $this->forge->addKey(['restaurant_id', 'category_id']);
        $this->forge->addKey(['restaurant_id', 'is_featured']);
        $this->forge->addKey(['restaurant_id', 'is_available']);
        $this->forge->addKey(['restaurant_id', 'sort_order']);
        $this->forge->addKey('price');
        $this->forge->addKey('created_at');
        $this->forge->addKey('deleted_at');
        
        $this->forge->createTable('dishes');
    }

    public function down()
    {
        $this->forge->dropTable('dishes');
    }
}