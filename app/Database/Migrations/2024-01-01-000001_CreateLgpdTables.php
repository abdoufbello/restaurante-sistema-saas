<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para criar tabelas LGPD
 */
class CreateLgpdTables extends Migration
{
    public function up()
    {
        // Tabela de consentimentos
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'data_subject_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Identificador do titular dos dados (email, CPF, etc.)'
            ],
            'consent_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Tipo de consentimento (cookies, marketing, etc.)'
            ],
            'consent_given' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'comment' => 'Se o consentimento foi dado'
            ],
            'consent_text' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Texto do consentimento apresentado'
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => false
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'consent_version' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => '1.0',
                'comment' => 'Versão do consentimento'
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados adicionais do consentimento'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['data_subject_id', 'consent_type']);
        $this->forge->addKey('consent_given');
        $this->forge->addKey('created_at');
        $this->forge->createTable('lgpd_consents');
        
        // Tabela de logs de consentimentos
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'consent_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID do consentimento relacionado'
            ],
            'data_subject_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Identificador do titular dos dados'
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Ação realizada (granted, revoked, updated)'
            ],
            'old_value' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Valor anterior'
            ],
            'new_value' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Novo valor'
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => false
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados adicionais do log'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('consent_id');
        $this->forge->addKey('data_subject_id');
        $this->forge->addKey('action');
        $this->forge->addKey('created_at');
        $this->forge->createTable('lgpd_consent_logs');
        
        // Tabela de logs de auditoria
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'Tipo de evento (data_access, consent_granted, etc.)'
            ],
            'data_subject' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Titular dos dados afetado'
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Usuário que executou a ação'
            ],
            'data_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Tipo de dados pessoais envolvidos'
            ],
            'operation' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Operação realizada (create, read, update, delete)'
            ],
            'description' => [
                'type' => 'TEXT',
                'comment' => 'Descrição detalhada do evento'
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'request_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados da requisição'
            ],
            'response_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados da resposta'
            ],
            'severity' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default' => 'medium'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_type');
        $this->forge->addKey('data_subject');
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->addKey('severity');
        $this->forge->createTable('lgpd_audit_logs');
        
        // Tabela de políticas de privacidade
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'policy_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'Tipo da política (general, cookies, terms_of_use)'
            ],
            'version' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'comment' => 'Versão da política'
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Título da política'
            ],
            'content' => [
                'type' => 'LONGTEXT',
                'comment' => 'Conteúdo da política'
            ],
            'language' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'pt-BR',
                'comment' => 'Idioma da política'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'active', 'archived'],
                'default' => 'draft'
            ],
            'effective_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data de vigência'
            ],
            'expiry_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data de expiração'
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Usuário que criou a política'
            ],
            'approved_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Usuário que aprovou a política'
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Metadados da política'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['policy_type', 'status']);
        $this->forge->addKey('version');
        $this->forge->addKey('effective_date');
        $this->forge->createTable('lgpd_privacy_policies');
        
        // Tabela de aceites de políticas
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'policy_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'ID da política aceita'
            ],
            'data_subject' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Identificador do titular dos dados'
            ],
            'acceptance_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Método de aceite (click, api, form)'
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'accepted_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Dados adicionais do aceite'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('policy_id');
        $this->forge->addKey('data_subject');
        $this->forge->addKey('accepted_at');
        $this->forge->addForeignKey('policy_id', 'lgpd_privacy_policies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('lgpd_policy_acceptances');
        
        // Tabela de dados pessoais (para controle de retenção)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'data_subject' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Identificador do titular dos dados'
            ],
            'data_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'Tipo de dados pessoais'
            ],
            'table_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'Tabela onde os dados estão armazenados'
            ],
            'column_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'Coluna onde os dados estão armazenados'
            ],
            'record_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'ID do registro'
            ],
            'purpose' => [
                'type' => 'TEXT',
                'comment' => 'Finalidade do tratamento'
            ],
            'legal_basis' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Base legal'
            ],
            'retention_period' => [
                'type' => 'INT',
                'constraint' => 11,
                'comment' => 'Período de retenção em dias'
            ],
            'collected_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'comment' => 'Data de coleta dos dados'
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data de expiração dos dados'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'anonymized', 'deleted'],
                'default' => 'active'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['data_subject', 'data_type']);
        $this->forge->addKey('expires_at');
        $this->forge->addKey('status');
        $this->forge->createTable('lgpd_personal_data_inventory');
        
        // Tabela de violações de dados
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'incident_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true,
                'comment' => 'Identificador único do incidente'
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'Tipo de violação'
            ],
            'severity' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default' => 'medium'
            ],
            'description' => [
                'type' => 'TEXT',
                'comment' => 'Descrição da violação'
            ],
            'affected_data_types' => [
                'type' => 'JSON',
                'comment' => 'Tipos de dados afetados'
            ],
            'affected_subjects_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Número de titulares afetados'
            ],
            'detected_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'comment' => 'Data de detecção'
            ],
            'reported_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data de comunicação à ANPD'
            ],
            'resolved_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data de resolução'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['detected', 'investigating', 'reported', 'resolved'],
                'default' => 'detected'
            ],
            'mitigation_actions' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Ações de mitigação tomadas'
            ],
            'reported_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Usuário que reportou'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('incident_id');
        $this->forge->addKey('severity');
        $this->forge->addKey('status');
        $this->forge->addKey('detected_at');
        $this->forge->createTable('lgpd_data_breaches');
    }
    
    public function down()
    {
        $this->forge->dropTable('lgpd_data_breaches', true);
        $this->forge->dropTable('lgpd_personal_data_inventory', true);
        $this->forge->dropTable('lgpd_policy_acceptances', true);
        $this->forge->dropTable('lgpd_privacy_policies', true);
        $this->forge->dropTable('lgpd_audit_logs', true);
        $this->forge->dropTable('lgpd_consent_logs', true);
        $this->forge->dropTable('lgpd_consents', true);
    }
}