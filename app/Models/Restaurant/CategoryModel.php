<?php

namespace App\Models\Restaurant;

use CodeIgniter\Model;

/**
 * Category Model
 * Manages dish categories for the Restaurant Kiosk system
 */
class CategoryModel extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'name',
        'description',
        'image',
        'icon',
        'color',
        'status',
        'sort_order',
        'is_visible_kiosk'
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
        'name' => 'required|min_length[2]|max_length[100]',
        'description' => 'permit_empty|max_length[500]',
        'color' => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        'status' => 'required|in_list[active,inactive]',
        'sort_order' => 'permit_empty|is_natural'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'is_natural_no_zero' => 'ID do restaurante deve ser um número válido'
        ],
        'name' => [
            'required' => 'Nome da categoria é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome deve ter no máximo 100 caracteres'
        ],
        'description' => [
            'max_length' => 'Descrição deve ter no máximo 500 caracteres'
        ],
        'color' => [
            'regex_match' => 'Cor deve estar no formato hexadecimal (#RRGGBB)'
        ],
        'status' => [
            'required' => 'Status é obrigatório',
            'in_list' => 'Status deve ser: active ou inactive'
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
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = ['beforeDelete'];
    protected $afterDelete = [];

    /**
     * Before insert callback
     */
    protected function beforeInsert(array $data)
    {
        // Set default values
        $data['data']['is_visible_kiosk'] = $data['data']['is_visible_kiosk'] ?? true;
        $data['data']['sort_order'] = $data['data']['sort_order'] ?? 0;
        
        // Set default color if not provided
        if (empty($data['data']['color'])) {
            $data['data']['color'] = $this->getRandomColor();
        }

        return $data;
    }

    /**
     * Before delete callback
     */
    protected function beforeDelete(array $data)
    {
        // Check if category has dishes
        $dishModel = new DishModel();
        $dishCount = $dishModel->where('category_id', $data['id'][0])->countAllResults();
        
        if ($dishCount > 0) {
            throw new \Exception('Não é possível excluir uma categoria que possui pratos. Mova os pratos para outra categoria primeiro.');
        }

        return $data;
    }

    /**
     * Get random color for category
     */
    private function getRandomColor()
    {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
            '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
            '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#D7BDE2'
        ];
        
        return $colors[array_rand($colors)];
    }

    /**
     * Get categories by restaurant
     */
    public function getByRestaurant($restaurantId, $status = null)
    {
        $builder = $this->where('restaurant_id', $restaurantId)
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('name', 'ASC');
        
        if ($status) {
            $builder->where('status', $status);
        }
        
        return $builder->findAll();
    }

    /**
     * Get active categories by restaurant
     */
    public function getActiveByRestaurant($restaurantId)
    {
        return $this->getByRestaurant($restaurantId, 'active');
    }

    /**
     * Get categories visible in kiosk
     */
    public function getVisibleInKiosk($restaurantId)
    {
        return $this->where('restaurant_id', $restaurantId)
                    ->where('status', 'active')
                    ->where('is_visible_kiosk', true)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    /**
     * Get categories with dish count
     */
    public function getWithDishCount($restaurantId)
    {
        return $this->select('categories.*, COUNT(dishes.id) as dish_count')
                    ->join('dishes', 'dishes.category_id = categories.id AND dishes.deleted_at IS NULL', 'left')
                    ->where('categories.restaurant_id', $restaurantId)
                    ->groupBy('categories.id')
                    ->orderBy('categories.sort_order', 'ASC')
                    ->orderBy('categories.name', 'ASC')
                    ->findAll();
    }

    /**
     * Get categories with active dish count
     */
    public function getWithActiveDishCount($restaurantId)
    {
        return $this->select('categories.*, COUNT(dishes.id) as dish_count')
                    ->join('dishes', 'dishes.category_id = categories.id AND dishes.deleted_at IS NULL AND dishes.status = "active"', 'left')
                    ->where('categories.restaurant_id', $restaurantId)
                    ->groupBy('categories.id')
                    ->orderBy('categories.sort_order', 'ASC')
                    ->orderBy('categories.name', 'ASC')
                    ->findAll();
    }

    /**
     * Update sort order
     */
    public function updateSortOrder($categoryId, $sortOrder)
    {
        return $this->update($categoryId, ['sort_order' => $sortOrder]);
    }

    /**
     * Reorder categories
     */
    public function reorderCategories($restaurantId, $categoryIds)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        foreach ($categoryIds as $index => $categoryId) {
            $this->update($categoryId, ['sort_order' => $index + 1]);
        }
        
        $db->transComplete();
        
        return $db->transStatus();
    }

    /**
     * Toggle kiosk visibility
     */
    public function toggleKioskVisibility($categoryId)
    {
        $category = $this->find($categoryId);
        
        if (!$category) {
            return false;
        }
        
        return $this->update($categoryId, ['is_visible_kiosk' => !$category['is_visible_kiosk']]);
    }

    /**
     * Get category statistics
     */
    public function getStatistics($restaurantId)
    {
        $total = $this->where('restaurant_id', $restaurantId)->countAllResults(false);
        $active = $this->where('status', 'active')->countAllResults(false);
        $visibleKiosk = $this->where('is_visible_kiosk', true)->countAllResults();
        
        return [
            'total' => $total,
            'active' => $active,
            'visible_kiosk' => $visibleKiosk,
            'inactive' => $total - $active
        ];
    }

    /**
     * Create default categories for new restaurant
     */
    public function createDefaultCategories($restaurantId)
    {
        $defaultCategories = [
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Entradas',
                'description' => 'Pratos para começar a refeição',
                'icon' => 'utensils',
                'color' => '#FF6B6B',
                'status' => 'active',
                'sort_order' => 1,
                'is_visible_kiosk' => true
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Pratos Principais',
                'description' => 'Pratos principais do cardápio',
                'icon' => 'drumstick-bite',
                'color' => '#4ECDC4',
                'status' => 'active',
                'sort_order' => 2,
                'is_visible_kiosk' => true
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Sobremesas',
                'description' => 'Doces e sobremesas',
                'icon' => 'ice-cream',
                'color' => '#FFEAA7',
                'status' => 'active',
                'sort_order' => 3,
                'is_visible_kiosk' => true
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Bebidas',
                'description' => 'Bebidas e sucos',
                'icon' => 'glass-cheers',
                'color' => '#45B7D1',
                'status' => 'active',
                'sort_order' => 4,
                'is_visible_kiosk' => true
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Lanches',
                'description' => 'Sanduíches e lanches rápidos',
                'icon' => 'hamburger',
                'color' => '#96CEB4',
                'status' => 'active',
                'sort_order' => 5,
                'is_visible_kiosk' => true
            ]
        ];
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        foreach ($defaultCategories as $category) {
            $this->insert($category);
        }
        
        $db->transComplete();
        
        return $db->transStatus();
    }

    /**
     * Move dishes to another category
     */
    public function moveDishesToCategory($fromCategoryId, $toCategoryId)
    {
        $dishModel = new DishModel();
        
        return $dishModel->where('category_id', $fromCategoryId)
                        ->set(['category_id' => $toCategoryId])
                        ->update();
    }

    /**
     * Get most popular categories (by dish count)
     */
    public function getMostPopular($restaurantId, $limit = 5)
    {
        return $this->select('categories.*, COUNT(dishes.id) as dish_count')
                    ->join('dishes', 'dishes.category_id = categories.id AND dishes.deleted_at IS NULL AND dishes.status = "active"', 'left')
                    ->where('categories.restaurant_id', $restaurantId)
                    ->where('categories.status', 'active')
                    ->groupBy('categories.id')
                    ->orderBy('dish_count', 'DESC')
                    ->orderBy('categories.name', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Search categories
     */
    public function search($restaurantId, $query)
    {
        return $this->where('restaurant_id', $restaurantId)
                    ->where('status', 'active')
                    ->groupStart()
                        ->like('name', $query)
                        ->orLike('description', $query)
                    ->groupEnd()
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    /**
     * Duplicate category
     */
    public function duplicate($categoryId, $newName = null)
    {
        $category = $this->find($categoryId);
        
        if (!$category) {
            return false;
        }
        
        // Remove ID and timestamps
        unset($category['id'], $category['created_at'], $category['updated_at'], $category['deleted_at']);
        
        // Set new name
        $category['name'] = $newName ?? $category['name'] . ' (Cópia)';
        
        // Reset sort order
        $category['sort_order'] = 0;
        
        return $this->insert($category);
    }
}