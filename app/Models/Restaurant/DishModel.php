<?php

namespace App\Models\Restaurant;

use CodeIgniter\Model;

/**
 * Dish Model
 * Manages dish/menu items for the Restaurant Kiosk system
 */
class DishModel extends Model
{
    protected $table = 'dishes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'cost_price',
        'image',
        'ingredients',
        'allergens',
        'nutritional_info',
        'preparation_time',
        'calories',
        'status',
        'is_featured',
        'is_available',
        'stock_quantity',
        'min_stock_alert',
        'sort_order',
        'tags'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|is_natural_no_zero',
        'category_id' => 'required|is_natural_no_zero',
        'name' => 'required|min_length[3]|max_length[255]',
        'description' => 'permit_empty|max_length[1000]',
        'price' => 'required|decimal|greater_than[0]',
        'cost_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'preparation_time' => 'permit_empty|is_natural',
        'calories' => 'permit_empty|is_natural',
        'status' => 'required|in_list[active,inactive,out_of_stock]',
        'stock_quantity' => 'permit_empty|is_natural',
        'min_stock_alert' => 'permit_empty|is_natural',
        'sort_order' => 'permit_empty|is_natural'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'is_natural_no_zero' => 'ID do restaurante deve ser um número válido'
        ],
        'category_id' => [
            'required' => 'Categoria é obrigatória',
            'is_natural_no_zero' => 'ID da categoria deve ser um número válido'
        ],
        'name' => [
            'required' => 'Nome do prato é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 3 caracteres',
            'max_length' => 'Nome deve ter no máximo 255 caracteres'
        ],
        'description' => [
            'max_length' => 'Descrição deve ter no máximo 1000 caracteres'
        ],
        'price' => [
            'required' => 'Preço é obrigatório',
            'decimal' => 'Preço deve ser um valor decimal válido',
            'greater_than' => 'Preço deve ser maior que zero'
        ],
        'cost_price' => [
            'decimal' => 'Preço de custo deve ser um valor decimal válido',
            'greater_than_equal_to' => 'Preço de custo deve ser maior ou igual a zero'
        ],
        'preparation_time' => [
            'is_natural' => 'Tempo de preparo deve ser um número inteiro'
        ],
        'calories' => [
            'is_natural' => 'Calorias deve ser um número inteiro'
        ],
        'status' => [
            'required' => 'Status é obrigatório',
            'in_list' => 'Status deve ser: active, inactive ou out_of_stock'
        ],
        'stock_quantity' => [
            'is_natural' => 'Quantidade em estoque deve ser um número inteiro'
        ],
        'min_stock_alert' => [
            'is_natural' => 'Alerta de estoque mínimo deve ser um número inteiro'
        ],
        'sort_order' => [
            'is_natural' => 'Ordem de classificação deve ser um número inteiro'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['beforeInsert'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['beforeUpdate'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['afterFind'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Before insert callback
     */
    protected function beforeInsert(array $data)
    {
        // Set default values
        $data['data']['is_featured'] = $data['data']['is_featured'] ?? false;
        $data['data']['is_available'] = $data['data']['is_available'] ?? true;
        $data['data']['sort_order'] = $data['data']['sort_order'] ?? 0;
        
        // Process JSON fields
        if (isset($data['data']['ingredients']) && is_array($data['data']['ingredients'])) {
            $data['data']['ingredients'] = json_encode($data['data']['ingredients']);
        }
        
        if (isset($data['data']['allergens']) && is_array($data['data']['allergens'])) {
            $data['data']['allergens'] = json_encode($data['data']['allergens']);
        }
        
        if (isset($data['data']['nutritional_info']) && is_array($data['data']['nutritional_info'])) {
            $data['data']['nutritional_info'] = json_encode($data['data']['nutritional_info']);
        }
        
        if (isset($data['data']['tags']) && is_array($data['data']['tags'])) {
            $data['data']['tags'] = json_encode($data['data']['tags']);
        }

        return $data;
    }

    /**
     * Before update callback
     */
    protected function beforeUpdate(array $data)
    {
        // Process JSON fields
        if (isset($data['data']['ingredients']) && is_array($data['data']['ingredients'])) {
            $data['data']['ingredients'] = json_encode($data['data']['ingredients']);
        }
        
        if (isset($data['data']['allergens']) && is_array($data['data']['allergens'])) {
            $data['data']['allergens'] = json_encode($data['data']['allergens']);
        }
        
        if (isset($data['data']['nutritional_info']) && is_array($data['data']['nutritional_info'])) {
            $data['data']['nutritional_info'] = json_encode($data['data']['nutritional_info']);
        }
        
        if (isset($data['data']['tags']) && is_array($data['data']['tags'])) {
            $data['data']['tags'] = json_encode($data['data']['tags']);
        }

        return $data;
    }

    /**
     * After find callback
     */
    protected function afterFind(array $data)
    {
        if (isset($data['data'])) {
            // Single record
            $data['data'] = $this->processJsonFields($data['data']);
        } else {
            // Multiple records
            foreach ($data as &$record) {
                $record = $this->processJsonFields($record);
            }
        }

        return $data;
    }

    /**
     * Process JSON fields
     */
    private function processJsonFields($record)
    {
        $jsonFields = ['ingredients', 'allergens', 'nutritional_info', 'tags'];
        
        foreach ($jsonFields as $field) {
            if (isset($record[$field]) && is_string($record[$field])) {
                $record[$field] = json_decode($record[$field], true) ?? [];
            }
        }
        
        return $record;
    }

    /**
     * Get dishes by restaurant
     */
    public function getByRestaurant($restaurantId, $status = null)
    {
        $builder = $this->select('dishes.*, categories.name as category_name')
                        ->join('categories', 'categories.id = dishes.category_id', 'left')
                        ->where('dishes.restaurant_id', $restaurantId)
                        ->orderBy('dishes.sort_order', 'ASC')
                        ->orderBy('dishes.name', 'ASC');
        
        if ($status) {
            $builder->where('dishes.status', $status);
        }
        
        return $builder->findAll();
    }

    /**
     * Get active dishes by restaurant
     */
    public function getActiveByRestaurant($restaurantId)
    {
        return $this->getByRestaurant($restaurantId, 'active');
    }

    /**
     * Get available dishes for kiosk
     */
    public function getAvailableForKiosk($restaurantId)
    {
        return $this->select('dishes.*, categories.name as category_name')
                    ->join('categories', 'categories.id = dishes.category_id', 'left')
                    ->where('dishes.restaurant_id', $restaurantId)
                    ->where('dishes.status', 'active')
                    ->where('dishes.is_available', true)
                    ->orderBy('dishes.sort_order', 'ASC')
                    ->orderBy('dishes.name', 'ASC')
                    ->findAll();
    }

    /**
     * Get dishes by category
     */
    public function getByCategory($categoryId, $restaurantId = null)
    {
        $builder = $this->where('category_id', $categoryId)
                        ->where('status', 'active')
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('name', 'ASC');
        
        if ($restaurantId) {
            $builder->where('restaurant_id', $restaurantId);
        }
        
        return $builder->findAll();
    }

    /**
     * Get featured dishes
     */
    public function getFeatured($restaurantId, $limit = 10)
    {
        return $this->select('dishes.*, categories.name as category_name')
                    ->join('categories', 'categories.id = dishes.category_id', 'left')
                    ->where('dishes.restaurant_id', $restaurantId)
                    ->where('dishes.status', 'active')
                    ->where('dishes.is_featured', true)
                    ->where('dishes.is_available', true)
                    ->orderBy('dishes.sort_order', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Search dishes
     */
    public function search($restaurantId, $query, $categoryId = null)
    {
        $builder = $this->select('dishes.*, categories.name as category_name')
                        ->join('categories', 'categories.id = dishes.category_id', 'left')
                        ->where('dishes.restaurant_id', $restaurantId)
                        ->where('dishes.status', 'active')
                        ->where('dishes.is_available', true)
                        ->groupStart()
                            ->like('dishes.name', $query)
                            ->orLike('dishes.description', $query)
                            ->orLike('dishes.tags', $query)
                        ->groupEnd()
                        ->orderBy('dishes.name', 'ASC');
        
        if ($categoryId) {
            $builder->where('dishes.category_id', $categoryId);
        }
        
        return $builder->findAll();
    }

    /**
     * Get dishes with low stock
     */
    public function getLowStock($restaurantId)
    {
        return $this->select('dishes.*, categories.name as category_name')
                    ->join('categories', 'categories.id = dishes.category_id', 'left')
                    ->where('dishes.restaurant_id', $restaurantId)
                    ->where('dishes.stock_quantity IS NOT NULL')
                    ->where('dishes.min_stock_alert IS NOT NULL')
                    ->where('dishes.stock_quantity <= dishes.min_stock_alert')
                    ->orderBy('dishes.name', 'ASC')
                    ->findAll();
    }

    /**
     * Update stock quantity
     */
    public function updateStock($dishId, $quantity, $operation = 'subtract')
    {
        $dish = $this->find($dishId);
        
        if (!$dish || $dish['stock_quantity'] === null) {
            return false;
        }
        
        $newQuantity = $operation === 'add' 
            ? $dish['stock_quantity'] + $quantity 
            : $dish['stock_quantity'] - $quantity;
        
        // Ensure stock doesn't go negative
        $newQuantity = max(0, $newQuantity);
        
        $updateData = ['stock_quantity' => $newQuantity];
        
        // Mark as out of stock if quantity is 0
        if ($newQuantity === 0) {
            $updateData['is_available'] = false;
            $updateData['status'] = 'out_of_stock';
        }
        
        return $this->update($dishId, $updateData);
    }

    /**
     * Get dish statistics
     */
    public function getStatistics($restaurantId)
    {
        $total = $this->where('restaurant_id', $restaurantId)->countAllResults(false);
        $active = $this->where('status', 'active')->countAllResults(false);
        $featured = $this->where('is_featured', true)->countAllResults(false);
        $outOfStock = $this->where('status', 'out_of_stock')->countAllResults();
        
        return [
            'total' => $total,
            'active' => $active,
            'featured' => $featured,
            'out_of_stock' => $outOfStock
        ];
    }

    /**
     * Toggle dish availability
     */
    public function toggleAvailability($dishId)
    {
        $dish = $this->find($dishId);
        
        if (!$dish) {
            return false;
        }
        
        return $this->update($dishId, ['is_available' => !$dish['is_available']]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured($dishId)
    {
        $dish = $this->find($dishId);
        
        if (!$dish) {
            return false;
        }
        
        return $this->update($dishId, ['is_featured' => !$dish['is_featured']]);
    }

    /**
     * Duplicate dish
     */
    public function duplicate($dishId, $newName = null)
    {
        $dish = $this->find($dishId);
        
        if (!$dish) {
            return false;
        }
        
        // Remove ID and timestamps
        unset($dish['id'], $dish['created_at'], $dish['updated_at'], $dish['deleted_at']);
        
        // Set new name
        $dish['name'] = $newName ?? $dish['name'] . ' (Cópia)';
        
        // Reset some fields
        $dish['is_featured'] = false;
        $dish['sort_order'] = 0;
        
        return $this->insert($dish);
    }
}