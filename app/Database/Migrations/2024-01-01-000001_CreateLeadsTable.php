<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLeadsTable extends Migration
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
            'nome' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
            ],
            'telefone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'restaurante' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'cidade' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'interesse' => [
                'type' => 'ENUM',
                'constraint' => ['estoque', 'whatsapp', 'delivery', 'relatorios', 'completo'],
                'null' => false,
            ],
            'origem' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Origem do lead (Google Ads, Facebook, Direto, etc.)'
            ],
            'ip' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['novo', 'contatado', 'qualificado', 'proposta', 'fechado', 'perdido'],
                'default' => 'novo',
                'null' => false,
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Observações sobre o lead'
            ],
            'data_contato' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data do primeiro contato'
            ],
            'convertido_em' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data da conversão em cliente'
            ],
            'valor_contrato' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Valor do contrato fechado'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        // Definir chave primária
        $this->forge->addPrimaryKey('id');
        
        // Adicionar índices
        $this->forge->addKey('email');
        $this->forge->addKey('status');
        $this->forge->addKey('interesse');
        $this->forge->addKey('origem');
        $this->forge->addKey('created_at');
        $this->forge->addKey(['status', 'created_at']);
        
        // Criar tabela
        $this->forge->createTable('leads');
    }

    public function down()
    {
        $this->forge->dropTable('leads');
    }
}