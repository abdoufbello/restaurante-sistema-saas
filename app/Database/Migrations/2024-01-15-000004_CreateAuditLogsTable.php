<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        // Create audit_logs table for security compliance
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
                'null' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'session_id' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => false,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'resource' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'details' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'severity' => [
                'type' => 'ENUM',
                'constraint' => ['info', 'warning', 'error', 'critical'],
                'default' => 'info',
            ],
            'success' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['restaurant_id', 'created_at']);
        $this->forge->addKey(['action', 'created_at']);
        $this->forge->addKey(['ip_address', 'created_at']);
        $this->forge->addKey('severity');
        
        $this->forge->createTable('audit_logs');
        
        // Create data_consents table for LGPD compliance
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
                'null' => false,
            ],
            'consent_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'consent_given' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'consent_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => false,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'consent_version' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => '1.0',
            ],
            'expires_at' => [
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
        $this->forge->addKey(['restaurant_id', 'consent_type'], false, true); // Unique constraint
        $this->forge->addKey('expires_at');
        
        $this->forge->createTable('data_consents');
        
        // Create data_exports table for LGPD data portability
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
                'null' => false,
            ],
            'request_type' => [
                'type' => 'ENUM',
                'constraint' => ['export', 'deletion'],
                'default' => 'export',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed'],
                'default' => 'pending',
            ],
            'requested_data' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'export_file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'file_size_bytes' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'download_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['restaurant_id', 'status']);
        $this->forge->addKey('expires_at');
        
        $this->forge->createTable('data_exports');
        
        // Create security_settings table
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
                'null' => false,
            ],
            'two_factor_enabled' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'login_notifications' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'session_timeout_minutes' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 480, // 8 hours
            ],
            'allowed_ip_addresses' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'password_policy' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'api_rate_limit' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1000, // requests per hour
            ],
            'data_retention_days' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 730, // 2 years
            ],
            'encryption_enabled' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'audit_level' => [
                'type' => 'ENUM',
                'constraint' => ['basic', 'detailed', 'comprehensive'],
                'default' => 'detailed',
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
        $this->forge->addKey('restaurant_id', false, true); // Unique constraint
        
        $this->forge->createTable('security_settings');
        
        // Add foreign key constraints
        $this->forge->addForeignKey('restaurant_id', 'restaurants', 'id', 'CASCADE', 'CASCADE');
        
        // Add indexes for performance
        $this->db->query('CREATE INDEX idx_audit_logs_timestamp ON audit_logs(created_at DESC)');
        $this->db->query('CREATE INDEX idx_audit_logs_restaurant_action ON audit_logs(restaurant_id, action, created_at DESC)');
        $this->db->query('CREATE INDEX idx_data_consents_restaurant ON data_consents(restaurant_id, consent_type)');
        $this->db->query('CREATE INDEX idx_data_exports_status ON data_exports(status, created_at)');
    }
    
    public function down()
    {
        $this->forge->dropTable('security_settings', true);
        $this->forge->dropTable('data_exports', true);
        $this->forge->dropTable('data_consents', true);
        $this->forge->dropTable('audit_logs', true);
    }
}