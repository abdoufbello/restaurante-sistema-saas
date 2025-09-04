<?php

namespace App\Models;

use CodeIgniter\Model;

class JsonModel extends Model
{
    protected $dataDir;
    protected $tableName;
    protected $primaryKey = 'id';
    
    public function __construct($tableName = null)
    {
        parent::__construct();
        $this->dataDir = WRITEPATH . 'data';
        if ($tableName) {
            $this->tableName = $tableName;
        }
    }
    
    /**
     * Obter caminho do arquivo da tabela
     */
    protected function getFilePath()
    {
        return $this->dataDir . '/' . $this->tableName . '.json';
    }
    
    /**
     * Carregar dados da tabela
     */
    protected function loadData()
    {
        $filePath = $this->getFilePath();
        if (!file_exists($filePath)) {
            return ['structure' => [], 'data' => []];
        }
        
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: ['structure' => [], 'data' => []];
    }
    
    /**
     * Salvar dados na tabela
     */
    protected function saveData($data)
    {
        $filePath = $this->getFilePath();
        return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Obter próximo ID
     */
    protected function getNextId($data)
    {
        if (empty($data['data'])) {
            return 1;
        }
        
        $maxId = 0;
        foreach ($data['data'] as $record) {
            if (isset($record[$this->primaryKey]) && $record[$this->primaryKey] > $maxId) {
                $maxId = $record[$this->primaryKey];
            }
        }
        
        return $maxId + 1;
    }
    
    /**
     * Buscar todos os registros
     */
    public function findAll()
    {
        $data = $this->loadData();
        return array_filter($data['data'], function($record) {
            return !isset($record['deleted_at']) || $record['deleted_at'] === null;
        });
    }
    
    /**
     * Buscar registro por ID
     */
    public function find($id)
    {
        $data = $this->loadData();
        foreach ($data['data'] as $record) {
            if ($record[$this->primaryKey] == $id && (!isset($record['deleted_at']) || $record['deleted_at'] === null)) {
                return $record;
            }
        }
        return null;
    }
    
    /**
     * Buscar registros com condições
     */
    public function where($field, $value)
    {
        $data = $this->loadData();
        return array_filter($data['data'], function($record) use ($field, $value) {
            return isset($record[$field]) && $record[$field] == $value && 
                   (!isset($record['deleted_at']) || $record['deleted_at'] === null);
        });
    }
    
    /**
     * Buscar primeiro registro com condição
     */
    public function first($field, $value)
    {
        $results = $this->where($field, $value);
        return !empty($results) ? reset($results) : null;
    }
    
    /**
     * Inserir novo registro
     */
    public function insert($recordData)
    {
        $data = $this->loadData();
        
        // Adicionar ID se não existir
        if (!isset($recordData[$this->primaryKey])) {
            $recordData[$this->primaryKey] = $this->getNextId($data);
        }
        
        // Adicionar timestamps
        $now = date('Y-m-d H:i:s');
        if (!isset($recordData['created_at'])) {
            $recordData['created_at'] = $now;
        }
        if (!isset($recordData['updated_at'])) {
            $recordData['updated_at'] = $now;
        }
        
        $data['data'][] = $recordData;
        
        if ($this->saveData($data)) {
            return $recordData[$this->primaryKey];
        }
        
        return false;
    }
    
    /**
     * Atualizar registro
     */
    public function update($id, $updateData)
    {
        $data = $this->loadData();
        
        foreach ($data['data'] as $index => $record) {
            if ($record[$this->primaryKey] == $id) {
                // Manter dados existentes e atualizar apenas os fornecidos
                $data['data'][$index] = array_merge($record, $updateData);
                $data['data'][$index]['updated_at'] = date('Y-m-d H:i:s');
                
                return $this->saveData($data);
            }
        }
        
        return false;
    }
    
    /**
     * Excluir registro (soft delete)
     */
    public function delete($id)
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Excluir registro permanentemente
     */
    public function purgeDeleted($id = null)
    {
        $data = $this->loadData();
        
        if ($id) {
            // Remover registro específico
            $data['data'] = array_filter($data['data'], function($record) use ($id) {
                return $record[$this->primaryKey] != $id;
            });
        } else {
            // Remover todos os registros marcados como deletados
            $data['data'] = array_filter($data['data'], function($record) {
                return !isset($record['deleted_at']) || $record['deleted_at'] === null;
            });
        }
        
        // Reindexar array
        $data['data'] = array_values($data['data']);
        
        return $this->saveData($data);
    }
    
    /**
     * Contar registros
     */
    public function countAll()
    {
        return count($this->findAll());
    }
    
    /**
     * Validar dados antes de inserir/atualizar
     */
    protected function validate($data)
    {
        // Implementar validação específica em cada modelo
        return true;
    }
}