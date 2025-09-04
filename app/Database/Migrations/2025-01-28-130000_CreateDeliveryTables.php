<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeliveryTables extends Migration
{
    public function up()
    {
        // Tabela de integrações com plataformas de delivery
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
            'platform' => [
                'type' => 'ENUM',
                'constraint' => ['ifood', 'ubereats', 'rappi', '99food'],
            ],
            'credentials' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Credenciais criptografadas da API'
            ],
            'settings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Configurações específicas da plataforma'
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'last_sync_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_sync_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados da última sincronização'
            ],
            'last_test_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_test_result' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Resultado do último teste de conexão'
            ],
            'webhook_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
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
        $this->forge->addKey(['restaurant_id', 'platform']);
        $this->forge->addKey('is_active');
        $this->forge->addKey('last_sync_at');
        $this->forge->createTable('delivery_integrations');

        // Tabela de pedidos de delivery
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
            'integration_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'platform' => [
                'type' => 'ENUM',
                'constraint' => ['ifood', 'ubereats', 'rappi', '99food'],
            ],
            'platform_order_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'ID do pedido na plataforma'
            ],
            'order_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Número do pedido exibido ao cliente'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'confirmed', 'preparing', 'ready', 'dispatched', 'delivered', 'cancelled'],
                'default' => 'pending',
            ],
            'customer_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados do cliente'
            ],
            'delivery_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados de entrega (endereço, entregador, etc.)'
            ],
            'items' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Itens do pedido'
            ],
            'subtotal' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'delivery_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'service_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'discount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'total' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'commission' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
                'comment' => 'Comissão da plataforma'
            ],
            'net_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Valor líquido após comissão'
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'payment_status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'paid', 'failed', 'refunded'],
                'default' => 'pending',
            ],
            'estimated_delivery_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'actual_delivery_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'preparation_time' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'Tempo de preparo em minutos'
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Observações do pedido'
            ],
            'platform_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados completos da plataforma'
            ],
            'webhook_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados do webhook recebido'
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
        $this->forge->addKey('integration_id');
        $this->forge->addKey(['restaurant_id', 'platform']);
        $this->forge->addKey(['platform', 'platform_order_id']);
        $this->forge->addKey('status');
        $this->forge->addKey('payment_status');
        $this->forge->addKey('created_at');
        $this->forge->addKey('updated_at');
        $this->forge->createTable('delivery_orders');

        // Tabela de webhooks de delivery
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
            'integration_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'platform' => [
                'type' => 'ENUM',
                'constraint' => ['ifood', 'ubereats', 'rappi', '99food'],
            ],
            'order_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID do pedido na tabela delivery_orders'
            ],
            'platform_order_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'ID do pedido na plataforma'
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'Tipo do evento (order.created, order.confirmed, etc.)'
            ],
            'payload' => [
                'type' => 'JSON',
                'comment' => 'Dados completos do webhook'
            ],
            'headers' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Headers HTTP do webhook'
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
            'retry_count' => [
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
        $this->forge->addKey('restaurant_id');
        $this->forge->addKey('integration_id');
        $this->forge->addKey('order_id');
        $this->forge->addKey(['platform', 'platform_order_id']);
        $this->forge->addKey('event_type');
        $this->forge->addKey('processed');
        $this->forge->addKey('created_at');
        $this->forge->createTable('delivery_webhooks');

        // Tabela de sincronização de cardápio
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
            'integration_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'platform' => [
                'type' => 'ENUM',
                'constraint' => ['ifood', 'ubereats', 'rappi', '99food'],
            ],
            'sync_type' => [
                'type' => 'ENUM',
                'constraint' => ['full', 'partial', 'products', 'categories', 'prices', 'availability'],
                'default' => 'full',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed'],
                'default' => 'pending',
            ],
            'items_total' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Total de itens para sincronizar'
            ],
            'items_processed' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Itens já processados'
            ],
            'items_success' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Itens sincronizados com sucesso'
            ],
            'items_failed' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Itens que falharam na sincronização'
            ],
            'sync_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados da sincronização'
            ],
            'error_log' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Log de erros da sincronização'
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
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
        $this->forge->addKey('integration_id');
        $this->forge->addKey(['restaurant_id', 'platform']);
        $this->forge->addKey('status');
        $this->forge->addKey('sync_type');
        $this->forge->addKey('created_at');
        $this->forge->createTable('delivery_menu_syncs');

        // Adicionar foreign keys
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE', 'fk_delivery_integrations_restaurant');
        $this->db->query('ALTER TABLE delivery_integrations ADD CONSTRAINT fk_delivery_integrations_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE ON UPDATE CASCADE');
        
        $this->db->query('ALTER TABLE delivery_orders ADD CONSTRAINT fk_delivery_orders_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE delivery_orders ADD CONSTRAINT fk_delivery_orders_integration FOREIGN KEY (integration_id) REFERENCES delivery_integrations(id) ON DELETE CASCADE ON UPDATE CASCADE');
        
        $this->db->query('ALTER TABLE delivery_webhooks ADD CONSTRAINT fk_delivery_webhooks_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE delivery_webhooks ADD CONSTRAINT fk_delivery_webhooks_integration FOREIGN KEY (integration_id) REFERENCES delivery_integrations(id) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE delivery_webhooks ADD CONSTRAINT fk_delivery_webhooks_order FOREIGN KEY (order_id) REFERENCES delivery_orders(id) ON DELETE SET NULL ON UPDATE CASCADE');
        
        $this->db->query('ALTER TABLE delivery_menu_syncs ADD CONSTRAINT fk_delivery_menu_syncs_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE delivery_menu_syncs ADD CONSTRAINT fk_delivery_menu_syncs_integration FOREIGN KEY (integration_id) REFERENCES delivery_integrations(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        // Remover foreign keys primeiro
        $this->forge->dropForeignKey('delivery_integrations', 'fk_delivery_integrations_restaurant');
        $this->forge->dropForeignKey('delivery_orders', 'fk_delivery_orders_restaurant');
        $this->forge->dropForeignKey('delivery_orders', 'fk_delivery_orders_integration');
        $this->forge->dropForeignKey('delivery_webhooks', 'fk_delivery_webhooks_restaurant');
        $this->forge->dropForeignKey('delivery_webhooks', 'fk_delivery_webhooks_integration');
        $this->forge->dropForeignKey('delivery_webhooks', 'fk_delivery_webhooks_order');
        $this->forge->dropForeignKey('delivery_menu_syncs', 'fk_delivery_menu_syncs_restaurant');
        $this->forge->dropForeignKey('delivery_menu_syncs', 'fk_delivery_menu_syncs_integration');
        
        // Remover tabelas
        $this->forge->dropTable('delivery_menu_syncs');
        $this->forge->dropTable('delivery_webhooks');
        $this->forge->dropTable('delivery_orders');
        $this->forge->dropTable('delivery_integrations');
    }
}