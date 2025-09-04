<?php

namespace App\Models;

use App\Models\TenantModel;

/**
 * Modelo para Pratos com Multi-Tenancy
 */
class DishModel extends TenantModel
{
    protected $table = 'dishes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'image_url',
        'ingredients',
        'allergens',
        'nutritional_info',
        'preparation_time',
        'is_available',
        'is_featured',
        'sort_order'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'category_id' => 'required|integer',
        'name' => 'required|min_length[2]|max_length[255]',
        'description' => 'max_length[1000]',
        'price' => 'required|decimal|greater_than[0]',
        'image_url' => 'max_length[500]',
        'preparation_time' => 'integer|greater_than_equal_to[0]',
        'is_available' => 'in_list[0,1]',
        'is_featured' => 'in_list[0,1]',
        'sort_order' => 'integer'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Nome do prato é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome deve ter no máximo 255 caracteres'
        ],
        'price' => [
            'required' => 'Preço é obrigatório',
            'decimal' => 'Preço deve ser um valor decimal válido',
            'greater_than' => 'Preço deve ser maior que zero'
        ],
        'category_id' => [
            'required' => 'Categoria é obrigatória',
            'integer' => 'ID da categoria deve ser um número inteiro'
        ]
    ];
    
    /**
     * Obtém pratos disponíveis
     */
    public function getAvailableDishes()
    {
        return $this->select('dishes.*, categories.name as category_name')
                   ->join('categories', 'categories.id = dishes.category_id AND categories.restaurant_id = dishes.restaurant_id')
                   ->where('dishes.is_available', 1)
                   ->where('categories.is_active', 1)
                   ->orderBy('dishes.sort_order', 'ASC')
                   ->orderBy('dishes.name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém pratos por categoria
     */
    public function getDishesByCategory(int $categoryId)
    {
        return $this->where('category_id', $categoryId)
                   ->where('is_available', 1)
                   ->orderBy('sort_order', 'ASC')
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém pratos em destaque
     */
    public function getFeaturedDishes(int $limit = 10)
    {
        return $this->select('dishes.*, categories.name as category_name')
                   ->join('categories', 'categories.id = dishes.category_id AND categories.restaurant_id = dishes.restaurant_id')
                   ->where('dishes.is_featured', 1)
                   ->where('dishes.is_available', 1)
                   ->where('categories.is_active', 1)
                   ->orderBy('dishes.sort_order', 'ASC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Busca pratos por termo
     */
    public function searchDishes(string $term)
    {
        return $this->select('dishes.*, categories.name as category_name')
                   ->join('categories', 'categories.id = dishes.category_id AND categories.restaurant_id = dishes.restaurant_id')
                   ->groupStart()
                       ->like('dishes.name', $term)
                       ->orLike('dishes.description', $term)
                       ->orLike('dishes.ingredients', $term)
                   ->groupEnd()
                   ->where('dishes.is_available', 1)
                   ->where('categories.is_active', 1)
                   ->orderBy('dishes.name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Verifica se nome do prato já existe na categoria
     */
    public function nameExistsInCategory(string $name, int $categoryId, int $excludeId = null): bool
    {
        $query = $this->where('name', $name)
                     ->where('category_id', $categoryId);
        
        if ($excludeId) {
            $query->where('id !=', $excludeId);
        }
        
        return $query->countAllResults() > 0;
    }
    
    /**
     * Obtém próximo número de ordenação na categoria
     */
    public function getNextSortOrderInCategory(int $categoryId): int
    {
        $maxOrder = $this->where('category_id', $categoryId)
                        ->selectMax('sort_order')
                        ->first();
        return ($maxOrder['sort_order'] ?? 0) + 1;
    }
    
    /**
     * Reordena pratos na categoria
     */
    public function reorderDishesInCategory(int $categoryId, array $dishIds): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            foreach ($dishIds as $index => $dishId) {
                // Verificar se o prato pertence à categoria
                $dish = $this->find($dishId);
                if ($dish && $dish['category_id'] == $categoryId) {
                    $this->update($dishId, ['sort_order' => $index + 1]);
                }
            }
            
            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            return false;
        }
    }
    
    /**
     * Marca prato como disponível
     */
    public function markAsAvailable(int $dishId): bool
    {
        return $this->update($dishId, ['is_available' => 1]);
    }
    
    /**
     * Marca prato como indisponível
     */
    public function markAsUnavailable(int $dishId): bool
    {
        return $this->update($dishId, ['is_available' => 0]);
    }
    
    /**
     * Define prato como destaque
     */
    public function setAsFeatured(int $dishId): bool
    {
        return $this->update($dishId, ['is_featured' => 1]);
    }
    
    /**
     * Remove prato do destaque
     */
    public function removeFromFeatured(int $dishId): bool
    {
        return $this->update($dishId, ['is_featured' => 0]);
    }
    
    /**
     * Obtém pratos mais vendidos (requer integração com pedidos)
     */
    public function getBestSellers(int $limit = 10)
    {
        return $this->select('dishes.*, categories.name as category_name, COUNT(order_items.dish_id) as total_orders')
                   ->join('categories', 'categories.id = dishes.category_id AND categories.restaurant_id = dishes.restaurant_id')
                   ->join('order_items', 'order_items.dish_id = dishes.id', 'left')
                   ->join('orders', 'orders.id = order_items.order_id AND orders.restaurant_id = dishes.restaurant_id', 'left')
                   ->where('dishes.is_available', 1)
                   ->where('categories.is_active', 1)
                   ->groupBy('dishes.id')
                   ->orderBy('total_orders', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém pratos por faixa de preço
     */
    public function getDishesByPriceRange(float $minPrice, float $maxPrice)
    {
        return $this->select('dishes.*, categories.name as category_name')
                   ->join('categories', 'categories.id = dishes.category_id AND categories.restaurant_id = dishes.restaurant_id')
                   ->where('dishes.price >=', $minPrice)
                   ->where('dishes.price <=', $maxPrice)
                   ->where('dishes.is_available', 1)
                   ->where('categories.is_active', 1)
                   ->orderBy('dishes.price', 'ASC')
                   ->findAll();
    }
    
    /**
     * Conta pratos disponíveis
     */
    public function countAvailable(): int
    {
        return $this->where('is_available', 1)->countAllResults();
    }
    
    /**
     * Conta pratos em destaque
     */
    public function countFeatured(): int
    {
        return $this->where('is_featured', 1)
                   ->where('is_available', 1)
                   ->countAllResults();
    }
    
    /**
     * Obtém estatísticas de pratos
     */
    public function getStats(): array
    {
        return [
            'total' => $this->countAllResults(),
            'available' => $this->where('is_available', 1)->countAllResults(),
            'unavailable' => $this->where('is_available', 0)->countAllResults(),
            'featured' => $this->countFeatured(),
            'avg_price' => $this->selectAvg('price')->first()['price'] ?? 0,
            'min_price' => $this->selectMin('price')->first()['price'] ?? 0,
            'max_price' => $this->selectMax('price')->first()['price'] ?? 0
        ];
    }
    
    /**
     * Obtém pratos com informações nutricionais
     */
    public function getDishesWithNutrition()
    {
        return $this->select('dishes.*, categories.name as category_name')
                   ->join('categories', 'categories.id = dishes.category_id AND categories.restaurant_id = dishes.restaurant_id')
                   ->where('dishes.nutritional_info IS NOT NULL')
                   ->where('dishes.nutritional_info !=', '')
                   ->where('dishes.is_available', 1)
                   ->where('categories.is_active', 1)
                   ->orderBy('dishes.name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Filtra pratos por alérgenos
     */
    public function getDishesByAllergens(array $allergens, bool $exclude = true)
    {
        $query = $this->select('dishes.*, categories.name as category_name')
                     ->join('categories', 'categories.id = dishes.category_id AND categories.restaurant_id = dishes.restaurant_id')
                     ->where('dishes.is_available', 1)
                     ->where('categories.is_active', 1);
        
        foreach ($allergens as $allergen) {
            if ($exclude) {
                $query->where('dishes.allergens NOT LIKE', "%{$allergen}%");
            } else {
                $query->orLike('dishes.allergens', $allergen);
            }
        }
        
        return $query->orderBy('dishes.name', 'ASC')->findAll();
    }
}