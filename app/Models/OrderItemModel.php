<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Itens de Pedidos com Multi-Tenancy
 */
class OrderItemModel extends BaseMultiTenantModel
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'order_id',
        'dish_id',
        'name',
        'description',
        'price',
        'cost_price',
        'quantity',
        'unit_price',
        'total_price',
        'discount_amount',
        'tax_amount',
        'customizations',
        'special_instructions',
        'status',
        'preparation_time',
        'category_name',
        'image_url',
        'sku',
        'weight',
        'calories',
        'allergens',
        'ingredients',
        'nutritional_info',
        'modifiers',
        'combo_items',
        'is_combo',
        'parent_item_id',
        'sort_order',
        'settings'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'order_id' => 'required|integer',
        'dish_id' => 'permit_empty|integer',
        'name' => 'required|max_length[255]',
        'description' => 'permit_empty|max_length[1000]',
        'price' => 'required|decimal|greater_than[0]',
        'cost_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'quantity' => 'required|integer|greater_than[0]',
        'unit_price' => 'required|decimal|greater_than[0]',
        'total_price' => 'required|decimal|greater_than[0]',
        'discount_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'tax_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'status' => 'permit_empty|in_list[pending,preparing,ready,served,cancelled]',
        'preparation_time' => 'permit_empty|integer|greater_than[0]',
        'category_name' => 'permit_empty|max_length[255]',
        'image_url' => 'permit_empty|max_length[500]',
        'sku' => 'permit_empty|max_length[100]',
        'weight' => 'permit_empty|decimal|greater_than[0]',
        'calories' => 'permit_empty|integer|greater_than_equal_to[0]',
        'is_combo' => 'permit_empty|in_list[0,1]',
        'parent_item_id' => 'permit_empty|integer',
        'sort_order' => 'permit_empty|integer|greater_than_equal_to[0]'
    ];
    
    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'integer' => 'ID do restaurante deve ser um número inteiro'
        ],
        'order_id' => [
            'required' => 'ID do pedido é obrigatório',
            'integer' => 'ID do pedido deve ser um número inteiro'
        ],
        'dish_id' => [
            'integer' => 'ID do prato deve ser um número inteiro'
        ],
        'name' => [
            'required' => 'Nome do item é obrigatório',
            'max_length' => 'Nome do item deve ter no máximo 255 caracteres'
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
        'quantity' => [
            'required' => 'Quantidade é obrigatória',
            'integer' => 'Quantidade deve ser um número inteiro',
            'greater_than' => 'Quantidade deve ser maior que zero'
        ],
        'unit_price' => [
            'required' => 'Preço unitário é obrigatório',
            'decimal' => 'Preço unitário deve ser um valor decimal válido',
            'greater_than' => 'Preço unitário deve ser maior que zero'
        ],
        'total_price' => [
            'required' => 'Preço total é obrigatório',
            'decimal' => 'Preço total deve ser um valor decimal válido',
            'greater_than' => 'Preço total deve ser maior que zero'
        ],
        'discount_amount' => [
            'decimal' => 'Valor do desconto deve ser um valor decimal válido',
            'greater_than_equal_to' => 'Valor do desconto deve ser maior ou igual a zero'
        ],
        'tax_amount' => [
            'decimal' => 'Valor do imposto deve ser um valor decimal válido',
            'greater_than_equal_to' => 'Valor do imposto deve ser maior ou igual a zero'
        ],
        'status' => [
            'in_list' => 'Status deve ser: pending, preparing, ready, served ou cancelled'
        ],
        'preparation_time' => [
            'integer' => 'Tempo de preparo deve ser um número inteiro',
            'greater_than' => 'Tempo de preparo deve ser maior que zero'
        ],
        'category_name' => [
            'max_length' => 'Nome da categoria deve ter no máximo 255 caracteres'
        ],
        'image_url' => [
            'max_length' => 'URL da imagem deve ter no máximo 500 caracteres'
        ],
        'sku' => [
            'max_length' => 'SKU deve ter no máximo 100 caracteres'
        ],
        'weight' => [
            'decimal' => 'Peso deve ser um valor decimal válido',
            'greater_than' => 'Peso deve ser maior que zero'
        ],
        'calories' => [
            'integer' => 'Calorias deve ser um número inteiro',
            'greater_than_equal_to' => 'Calorias deve ser maior ou igual a zero'
        ],
        'is_combo' => [
            'in_list' => 'Is combo deve ser 0 ou 1'
        ],
        'parent_item_id' => [
            'integer' => 'ID do item pai deve ser um número inteiro'
        ],
        'sort_order' => [
            'integer' => 'Ordem de classificação deve ser um número inteiro',
            'greater_than_equal_to' => 'Ordem de classificação deve ser maior ou igual a zero'
        ]
    ];
    
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    
    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setDefaults', 'calculateTotals'];
    protected $beforeUpdate = ['calculateTotals'];
    
    /**
     * Define valores padrão
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'pending';
        }
        if (!isset($data['data']['is_combo'])) {
            $data['data']['is_combo'] = 0;
        }
        if (!isset($data['data']['sort_order'])) {
            $data['data']['sort_order'] = 0;
        }
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = json_encode([]);
        }
        if (!isset($data['data']['customizations'])) {
            $data['data']['customizations'] = json_encode([]);
        }
        if (!isset($data['data']['modifiers'])) {
            $data['data']['modifiers'] = json_encode([]);
        }
        if (!isset($data['data']['combo_items'])) {
            $data['data']['combo_items'] = json_encode([]);
        }
        if (!isset($data['data']['allergens'])) {
            $data['data']['allergens'] = json_encode([]);
        }
        if (!isset($data['data']['ingredients'])) {
            $data['data']['ingredients'] = json_encode([]);
        }
        if (!isset($data['data']['nutritional_info'])) {
            $data['data']['nutritional_info'] = json_encode([]);
        }
        return $data;
    }
    
    /**
     * Calcula totais automaticamente
     */
    protected function calculateTotals(array $data)
    {
        if (isset($data['data']['quantity']) && isset($data['data']['unit_price'])) {
            $quantity = (float) $data['data']['quantity'];
            $unitPrice = (float) $data['data']['unit_price'];
            $discountAmount = (float) ($data['data']['discount_amount'] ?? 0);
            $taxAmount = (float) ($data['data']['tax_amount'] ?? 0);
            
            $subtotal = $quantity * $unitPrice;
            $data['data']['total_price'] = $subtotal - $discountAmount + $taxAmount;
        }
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Obtém itens por pedido
     */
    public function getItemsByOrder(int $orderId): array
    {
        return $this->where('order_id', $orderId)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém itens por prato
     */
    public function getItemsByDish(int $dishId): array
    {
        return $this->where('dish_id', $dishId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Obtém itens por status
     */
    public function getItemsByStatus(string $status): array
    {
        return $this->where('status', $status)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém itens em preparo
     */
    public function getItemsInPreparation(): array
    {
        return $this->whereIn('status', ['preparing'])
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém itens prontos
     */
    public function getReadyItems(): array
    {
        return $this->where('status', 'ready')
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém itens de combo
     */
    public function getComboItems(): array
    {
        return $this->where('is_combo', 1)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Obtém itens filhos de um combo
     */
    public function getComboChildItems(int $parentItemId): array
    {
        return $this->where('parent_item_id', $parentItemId)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();
    }
    
    /**
     * Atualiza status do item
     */
    public function updateItemStatus(int $itemId, string $status): bool
    {
        return $this->update($itemId, ['status' => $status]);
    }
    
    /**
     * Atualiza status de múltiplos itens
     */
    public function updateMultipleItemsStatus(array $itemIds, string $status): bool
    {
        return $this->whereIn('id', $itemIds)
                    ->set(['status' => $status])
                    ->update();
    }
    
    /**
     * Calcula total do pedido pelos itens
     */
    public function calculateOrderTotal(int $orderId): float
    {
        $result = $this->selectSum('total_price', 'order_total')
                      ->where('order_id', $orderId)
                      ->first();
        
        return (float) ($result['order_total'] ?? 0);
    }
    
    /**
     * Calcula subtotal do pedido (sem impostos e descontos)
     */
    public function calculateOrderSubtotal(int $orderId): float
    {
        $result = $this->select('SUM(quantity * unit_price) as subtotal')
                      ->where('order_id', $orderId)
                      ->first();
        
        return (float) ($result['subtotal'] ?? 0);
    }
    
    /**
     * Calcula total de descontos do pedido
     */
    public function calculateOrderDiscounts(int $orderId): float
    {
        $result = $this->selectSum('discount_amount', 'total_discounts')
                      ->where('order_id', $orderId)
                      ->first();
        
        return (float) ($result['total_discounts'] ?? 0);
    }
    
    /**
     * Calcula total de impostos do pedido
     */
    public function calculateOrderTaxes(int $orderId): float
    {
        $result = $this->selectSum('tax_amount', 'total_taxes')
                      ->where('order_id', $orderId)
                      ->first();
        
        return (float) ($result['total_taxes'] ?? 0);
    }
    
    /**
     * Obtém itens mais vendidos
     */
    public function getTopSellingItems(int $limit = 10): array
    {
        return $this->select('dish_id, name, SUM(quantity) as total_sold, COUNT(*) as order_count')
                    ->where('dish_id IS NOT NULL')
                    ->groupBy('dish_id, name')
                    ->orderBy('total_sold', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * Obtém receita por item
     */
    public function getRevenueByItem(int $limit = 10): array
    {
        return $this->select('dish_id, name, SUM(total_price) as total_revenue, SUM(quantity) as total_sold')
                    ->where('dish_id IS NOT NULL')
                    ->groupBy('dish_id, name')
                    ->orderBy('total_revenue', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * Obtém itens por categoria
     */
    public function getItemsByCategory(string $categoryName): array
    {
        return $this->where('category_name', $categoryName)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Busca itens por nome
     */
    public function searchItemsByName(string $search): array
    {
        return $this->like('name', $search)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém itens com alergênicos específicos
     */
    public function getItemsWithAllergens(array $allergens): array
    {
        $builder = $this;
        
        foreach ($allergens as $allergen) {
            $builder = $builder->like('allergens', $allergen);
        }
        
        return $builder->findAll();
    }
    
    /**
     * Obtém itens por faixa de calorias
     */
    public function getItemsByCalorieRange(int $minCalories, int $maxCalories): array
    {
        return $this->where('calories >=', $minCalories)
                    ->where('calories <=', $maxCalories)
                    ->orderBy('calories', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém estatísticas de itens
     */
    public function getItemStats(): array
    {
        return [
            'total_items' => $this->countAllResults(),
            'items_today' => $this->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
            'pending_items' => $this->where('status', 'pending')->countAllResults(),
            'preparing_items' => $this->where('status', 'preparing')->countAllResults(),
            'ready_items' => $this->where('status', 'ready')->countAllResults(),
            'served_items' => $this->where('status', 'served')->countAllResults(),
            'cancelled_items' => $this->where('status', 'cancelled')->countAllResults(),
            'combo_items' => $this->where('is_combo', 1)->countAllResults(),
            'avg_item_price' => $this->getAverageItemPrice(),
            'total_revenue' => $this->getTotalRevenue(),
            'created_today' => $this->getCreatedToday(),
            'created_this_week' => $this->getCreatedThisWeek(),
            'created_this_month' => $this->getCreatedThisMonth()
        ];
    }
    
    /**
     * Obtém preço médio dos itens
     */
    public function getAverageItemPrice(): float
    {
        $result = $this->selectAvg('unit_price', 'avg_price')
                      ->first();
        
        return (float) ($result['avg_price'] ?? 0);
    }
    
    /**
     * Obtém receita total dos itens
     */
    public function getTotalRevenue(): float
    {
        $result = $this->selectSum('total_price', 'total_revenue')
                      ->first();
        
        return (float) ($result['total_revenue'] ?? 0);
    }
    
    /**
     * Duplica item
     */
    public function duplicateItem(int $itemId): ?int
    {
        $item = $this->find($itemId);
        
        if (!$item) {
            return null;
        }
        
        // Remove campos únicos
        unset($item['id'], $item['created_at'], $item['updated_at'], $item['deleted_at']);
        
        // Adiciona sufixo ao nome
        $item['name'] = $item['name'] . ' (Cópia)';
        
        return $this->insert($item);
    }
    
    /**
     * Busca avançada de itens
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                              ->like('name', $filters['search'])
                              ->orLike('description', $filters['search'])
                              ->orLike('sku', $filters['search'])
                              ->groupEnd();
        }
        
        if (!empty($filters['order_id'])) {
            $builder = $builder->where('order_id', $filters['order_id']);
        }
        
        if (!empty($filters['dish_id'])) {
            $builder = $builder->where('dish_id', $filters['dish_id']);
        }
        
        if (!empty($filters['status'])) {
            $builder = $builder->where('status', $filters['status']);
        }
        
        if (!empty($filters['category_name'])) {
            $builder = $builder->where('category_name', $filters['category_name']);
        }
        
        if (!empty($filters['is_combo'])) {
            $builder = $builder->where('is_combo', $filters['is_combo']);
        }
        
        if (!empty($filters['min_price'])) {
            $builder = $builder->where('unit_price >=', $filters['min_price']);
        }
        
        if (!empty($filters['max_price'])) {
            $builder = $builder->where('unit_price <=', $filters['max_price']);
        }
        
        if (!empty($filters['min_calories'])) {
            $builder = $builder->where('calories >=', $filters['min_calories']);
        }
        
        if (!empty($filters['max_calories'])) {
            $builder = $builder->where('calories <=', $filters['max_calories']);
        }
        
        if (!empty($filters['start_date'])) {
            $builder = $builder->where('DATE(created_at) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder = $builder->where('DATE(created_at) <=', $filters['end_date']);
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $builder = $builder->orderBy($orderBy, $orderDir);
        
        return $builder->findAll();
    }
    
    /**
     * Exporta itens para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $items = $this->advancedSearch($filters);
        
        $csv = "ID,Pedido,Prato,Nome,Descrição,Preço Unitário,Quantidade,Preço Total,Desconto,Imposto,Status,Categoria,SKU,Peso,Calorias,Criado em\n";
        
        foreach ($items as $item) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",%.2f,%d,%.2f,%.2f,%.2f,\"%s\",\"%s\",\"%s\",%.2f,%d,\"%s\"\n",
                $item['id'],
                $item['order_id'],
                $item['dish_id'] ?? '',
                $item['name'],
                $item['description'] ?? '',
                $item['unit_price'],
                $item['quantity'],
                $item['total_price'],
                $item['discount_amount'] ?? 0,
                $item['tax_amount'] ?? 0,
                $item['status'],
                $item['category_name'] ?? '',
                $item['sku'] ?? '',
                $item['weight'] ?? 0,
                $item['calories'] ?? 0,
                $item['created_at']
            );
        }
        
        return $csv;
    }
}