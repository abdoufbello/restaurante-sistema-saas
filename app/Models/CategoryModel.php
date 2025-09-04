<?php

namespace App\Models;

use App\Models\TenantModel;

/**
 * Modelo para Categorias com Multi-Tenancy
 */
class CategoryModel extends TenantModel
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'name',
        'description',
        'image_url',
        'sort_order',
        'is_active'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'name' => 'required|min_length[2]|max_length[255]',
        'description' => 'max_length[1000]',
        'image_url' => 'max_length[500]',
        'sort_order' => 'integer',
        'is_active' => 'in_list[0,1]'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Nome da categoria é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome deve ter no máximo 255 caracteres'
        ],
        'description' => [
            'max_length' => 'Descrição deve ter no máximo 1000 caracteres'
        ]
    ];
    
    /**
     * Obtém categorias ativas ordenadas
     */
    public function getActiveCategories()
    {
        return $this->where('is_active', 1)
                   ->orderBy('sort_order', 'ASC')
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Busca categoria por nome
     */
    public function findByName(string $name)
    {
        return $this->where('name', $name)
                   ->first();
    }
    
    /**
     * Verifica se nome da categoria já existe
     */
    public function nameExists(string $name, int $excludeId = null): bool
    {
        $query = $this->where('name', $name);
        
        if ($excludeId) {
            $query->where('id !=', $excludeId);
        }
        
        return $query->countAllResults() > 0;
    }
    
    /**
     * Obtém próximo número de ordenação
     */
    public function getNextSortOrder(): int
    {
        $maxOrder = $this->selectMax('sort_order')->first();
        return ($maxOrder['sort_order'] ?? 0) + 1;
    }
    
    /**
     * Reordena categorias
     */
    public function reorderCategories(array $categoryIds): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            foreach ($categoryIds as $index => $categoryId) {
                $this->update($categoryId, ['sort_order' => $index + 1]);
            }
            
            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            return false;
        }
    }
    
    /**
     * Ativa categoria
     */
    public function activate(int $categoryId): bool
    {
        return $this->update($categoryId, ['is_active' => 1]);
    }
    
    /**
     * Desativa categoria
     */
    public function deactivate(int $categoryId): bool
    {
        return $this->update($categoryId, ['is_active' => 0]);
    }
    
    /**
     * Conta categorias ativas
     */
    public function countActive(): int
    {
        return $this->where('is_active', 1)->countAllResults();
    }
    
    /**
     * Obtém categorias com contagem de pratos
     */
    public function getCategoriesWithDishCount()
    {
        return $this->select('categories.*, COUNT(dishes.id) as dish_count')
                   ->join('dishes', 'dishes.category_id = categories.id AND dishes.restaurant_id = categories.restaurant_id', 'left')
                   ->where('categories.is_active', 1)
                   ->groupBy('categories.id')
                   ->orderBy('categories.sort_order', 'ASC')
                   ->orderBy('categories.name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Verifica se categoria pode ser deletada (não tem pratos)
     */
    public function canDelete(int $categoryId): bool
    {
        $dishModel = new \App\Models\DishModel();
        $dishCount = $dishModel->where('category_id', $categoryId)->countAllResults();
        return $dishCount === 0;
    }
    
    /**
     * Obtém estatísticas de categorias
     */
    public function getStats(): array
    {
        return [
            'total' => $this->countAllResults(),
            'active' => $this->where('is_active', 1)->countAllResults(),
            'inactive' => $this->where('is_active', 0)->countAllResults()
        ];
    }
    
    /**
     * Busca categorias por termo
     */
    public function searchCategories(string $term)
    {
        return $this->like('name', $term)
                   ->orLike('description', $term)
                   ->where('is_active', 1)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Override do método delete para verificar dependências
     */
    public function delete($id = null, bool $purge = false)
    {
        if (!$this->canDelete($id)) {
            throw new \RuntimeException('Não é possível deletar categoria que possui pratos associados');
        }
        
        return parent::delete($id, $purge);
    }
}