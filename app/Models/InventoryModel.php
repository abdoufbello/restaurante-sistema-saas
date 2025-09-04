<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Inventário com Multi-Tenancy
 */
class InventoryModel extends BaseMultiTenantModel
{
    protected $table = 'inventory';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'supplier_id',
        'category_id',
        'item_code',
        'barcode',
        'name',
        'description',
        'brand',
        'type',
        'category',
        'subcategory',
        'unit_of_measure',
        'package_size',
        'current_stock',
        'reserved_stock',
        'available_stock',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'reorder_quantity',
        'cost_per_unit',
        'average_cost',
        'last_cost',
        'selling_price',
        'markup_percentage',
        'tax_rate',
        'location',
        'shelf_life_days',
        'expiration_date',
        'batch_number',
        'lot_number',
        'serial_number',
        'purchase_date',
        'last_purchase_date',
        'last_sale_date',
        'last_count_date',
        'last_received_quantity',
        'last_used_quantity',
        'total_received',
        'total_used',
        'total_sold',
        'total_wasted',
        'total_adjusted',
        'waste_reason',
        'adjustment_reason',
        'is_perishable',
        'is_active',
        'is_tracked',
        'requires_approval',
        'allergens',
        'nutritional_info',
        'storage_conditions',
        'handling_instructions',
        'safety_notes',
        'supplier_item_code',
        'supplier_name',
        'lead_time_days',
        'quality_grade',
        'origin_country',
        'certification',
        'image_url',
        'tags',
        'notes',
        'internal_notes',
        'created_by',
        'updated_by',
        'metadata',
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
        'name' => 'required|min_length[2]|max_length[100]',
        'type' => 'permit_empty|in_list[ingredient,beverage,supply,equipment,cleaning,packaging,other]',
        'unit_of_measure' => 'required|in_list[kg,g,l,ml,pcs,box,pack,bottle,can,bag,dozen,case,lb,oz,qt,gal,cup,tbsp,tsp]',
        'current_stock' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'minimum_stock' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'maximum_stock' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'reorder_point' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'reorder_quantity' => 'permit_empty|decimal|greater_than[0]',
        'cost_per_unit' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'selling_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'markup_percentage' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'tax_rate' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        'shelf_life_days' => 'permit_empty|integer|greater_than[0]',
        'lead_time_days' => 'permit_empty|integer|greater_than_equal_to[0]',
        'expiration_date' => 'permit_empty|valid_date',
        'purchase_date' => 'permit_empty|valid_date'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Nome do item é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome não pode exceder 100 caracteres'
        ],
        'unit_of_measure' => [
            'required' => 'Unidade de medida é obrigatória',
            'in_list' => 'Unidade de medida inválida'
        ],
        'current_stock' => [
            'decimal' => 'Estoque atual deve ser um número decimal válido',
            'greater_than_equal_to' => 'Estoque atual não pode ser negativo'
        ],
        'cost_per_unit' => [
            'decimal' => 'Custo por unidade deve ser um número decimal válido',
            'greater_than_equal_to' => 'Custo por unidade não pode ser negativo'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generateItemCode', 'calculateAvailableStock'];
    protected $beforeUpdate = ['updateTimestamps', 'calculateAvailableStock', 'checkStockLevels'];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['type'])) {
            $data['data']['type'] = 'ingredient';
        }
        
        if (!isset($data['data']['current_stock'])) {
            $data['data']['current_stock'] = 0.00;
        }
        
        if (!isset($data['data']['reserved_stock'])) {
            $data['data']['reserved_stock'] = 0.00;
        }
        
        if (!isset($data['data']['minimum_stock'])) {
            $data['data']['minimum_stock'] = 0.00;
        }
        
        if (!isset($data['data']['maximum_stock'])) {
            $data['data']['maximum_stock'] = 1000.00;
        }
        
        if (!isset($data['data']['reorder_point'])) {
            $data['data']['reorder_point'] = $data['data']['minimum_stock'] ?? 0.00;
        }
        
        if (!isset($data['data']['cost_per_unit'])) {
            $data['data']['cost_per_unit'] = 0.00;
        }
        
        if (!isset($data['data']['average_cost'])) {
            $data['data']['average_cost'] = $data['data']['cost_per_unit'] ?? 0.00;
        }
        
        if (!isset($data['data']['markup_percentage'])) {
            $data['data']['markup_percentage'] = 0.00;
        }
        
        if (!isset($data['data']['tax_rate'])) {
            $data['data']['tax_rate'] = 0.00;
        }
        
        if (!isset($data['data']['is_perishable'])) {
            $data['data']['is_perishable'] = 0;
        }
        
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        
        if (!isset($data['data']['is_tracked'])) {
            $data['data']['is_tracked'] = 1;
        }
        
        if (!isset($data['data']['requires_approval'])) {
            $data['data']['requires_approval'] = 0;
        }
        
        if (!isset($data['data']['total_received'])) {
            $data['data']['total_received'] = 0.00;
        }
        
        if (!isset($data['data']['total_used'])) {
            $data['data']['total_used'] = 0.00;
        }
        
        if (!isset($data['data']['total_sold'])) {
            $data['data']['total_sold'] = 0.00;
        }
        
        if (!isset($data['data']['total_wasted'])) {
            $data['data']['total_wasted'] = 0.00;
        }
        
        if (!isset($data['data']['total_adjusted'])) {
            $data['data']['total_adjusted'] = 0.00;
        }
        
        // Calcular preço de venda se não informado
        if (!isset($data['data']['selling_price']) && isset($data['data']['cost_per_unit']) && isset($data['data']['markup_percentage'])) {
            $cost = $data['data']['cost_per_unit'];
            $markup = $data['data']['markup_percentage'];
            $data['data']['selling_price'] = $cost * (1 + ($markup / 100));
        }
        
        return $data;
    }
    
    /**
     * Gera código único do item
     */
    protected function generateItemCode(array $data): array
    {
        if (!isset($data['data']['item_code']) || empty($data['data']['item_code'])) {
            $restaurantId = $data['data']['restaurant_id'] ?? $this->getCurrentTenantId();
            $type = strtoupper(substr($data['data']['type'] ?? 'ITEM', 0, 3));
            $timestamp = date('ymdHis');
            
            // Busca o último código gerado hoje
            $lastCode = $this->where('restaurant_id', $restaurantId)
                           ->where('DATE(created_at)', date('Y-m-d'))
                           ->orderBy('id', 'DESC')
                           ->first();
            
            $sequence = 1;
            if ($lastCode && !empty($lastCode['item_code'])) {
                $lastSequence = (int) substr($lastCode['item_code'], -3);
                $sequence = $lastSequence + 1;
            }
            
            $data['data']['item_code'] = $type . $timestamp . str_pad($sequence, 3, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }
    
    /**
     * Calcula estoque disponível
     */
    protected function calculateAvailableStock(array $data): array
    {
        if (isset($data['data']['current_stock']) || isset($data['data']['reserved_stock'])) {
            $currentStock = $data['data']['current_stock'] ?? 0;
            $reservedStock = $data['data']['reserved_stock'] ?? 0;
            $data['data']['available_stock'] = max(0, $currentStock - $reservedStock);
        }
        
        return $data;
    }
    
    /**
     * Atualiza timestamps de movimentação
     */
    protected function updateTimestamps(array $data): array
    {
        if (isset($data['data']['current_stock'])) {
            $data['data']['last_count_date'] = date('Y-m-d H:i:s');
        }
        
        return $data;
    }
    
    /**
     * Verifica níveis de estoque
     */
    protected function checkStockLevels(array $data): array
    {
        // Esta função pode ser usada para disparar alertas
        // Por enquanto, apenas registra a verificação
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Busca item por código
     */
    public function findByCode(string $itemCode): ?array
    {
        return $this->where('item_code', $itemCode)->first();
    }
    
    /**
     * Busca item por código de barras
     */
    public function findByBarcode(string $barcode): ?array
    {
        return $this->where('barcode', $barcode)->first();
    }
    
    /**
     * Obtém itens ativos
     */
    public function getActiveItems(): array
    {
        return $this->where('is_active', 1)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém itens por tipo
     */
    public function getItemsByType(string $type): array
    {
        return $this->where('type', $type)
                   ->where('is_active', 1)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém itens por categoria
     */
    public function getItemsByCategory(string $category): array
    {
        return $this->where('category', $category)
                   ->where('is_active', 1)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém itens com estoque baixo
     */
    public function getLowStockItems(): array
    {
        return $this->where('current_stock <=', 'reorder_point', false)
                   ->where('is_active', 1)
                   ->where('is_tracked', 1)
                   ->orderBy('current_stock', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém itens fora de estoque
     */
    public function getOutOfStockItems(): array
    {
        return $this->where('current_stock <=', 0)
                   ->where('is_active', 1)
                   ->where('is_tracked', 1)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém itens com excesso de estoque
     */
    public function getOverstockItems(): array
    {
        return $this->where('current_stock >', 'maximum_stock', false)
                   ->where('is_active', 1)
                   ->where('is_tracked', 1)
                   ->orderBy('current_stock', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém itens próximos ao vencimento
     */
    public function getExpiringItems(int $daysAhead = 7): array
    {
        $expirationDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        
        return $this->where('is_perishable', 1)
                   ->where('expiration_date <=', $expirationDate)
                   ->where('expiration_date >=', date('Y-m-d'))
                   ->where('current_stock >', 0)
                   ->orderBy('expiration_date', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém itens vencidos
     */
    public function getExpiredItems(): array
    {
        return $this->where('is_perishable', 1)
                   ->where('expiration_date <', date('Y-m-d'))
                   ->where('current_stock >', 0)
                   ->orderBy('expiration_date', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém itens por fornecedor
     */
    public function getItemsBySupplier(int $supplierId): array
    {
        return $this->where('supplier_id', $supplierId)
                   ->where('is_active', 1)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Adiciona estoque
     */
    public function addStock(int $itemId, float $quantity, float $costPerUnit = null, array $additionalData = []): bool
    {
        $item = $this->find($itemId);
        if (!$item) {
            return false;
        }
        
        $newStock = $item['current_stock'] + $quantity;
        $updateData = array_merge([
            'current_stock' => $newStock,
            'last_received_quantity' => $quantity,
            'total_received' => $item['total_received'] + $quantity,
            'last_purchase_date' => date('Y-m-d H:i:s')
        ], $additionalData);
        
        // Atualizar custo médio se fornecido
        if ($costPerUnit !== null) {
            $totalValue = ($item['current_stock'] * $item['average_cost']) + ($quantity * $costPerUnit);
            $updateData['average_cost'] = $newStock > 0 ? $totalValue / $newStock : $costPerUnit;
            $updateData['last_cost'] = $costPerUnit;
        }
        
        return $this->update($itemId, $updateData);
    }
    
    /**
     * Remove estoque
     */
    public function removeStock(int $itemId, float $quantity, string $reason = 'usage', array $additionalData = []): bool
    {
        $item = $this->find($itemId);
        if (!$item || $item['current_stock'] < $quantity) {
            return false;
        }
        
        $newStock = $item['current_stock'] - $quantity;
        $updateData = array_merge([
            'current_stock' => $newStock,
            'last_used_quantity' => $quantity,
            'last_sale_date' => date('Y-m-d H:i:s')
        ], $additionalData);
        
        // Atualizar totais baseado na razão
        switch ($reason) {
            case 'sale':
                $updateData['total_sold'] = $item['total_sold'] + $quantity;
                break;
            case 'waste':
                $updateData['total_wasted'] = $item['total_wasted'] + $quantity;
                if (isset($additionalData['waste_reason'])) {
                    $updateData['waste_reason'] = $additionalData['waste_reason'];
                }
                break;
            case 'adjustment':
                $updateData['total_adjusted'] = $item['total_adjusted'] + $quantity;
                if (isset($additionalData['adjustment_reason'])) {
                    $updateData['adjustment_reason'] = $additionalData['adjustment_reason'];
                }
                break;
            default:
                $updateData['total_used'] = $item['total_used'] + $quantity;
        }
        
        return $this->update($itemId, $updateData);
    }
    
    /**
     * Ajusta estoque
     */
    public function adjustStock(int $itemId, float $newQuantity, string $reason = ''): bool
    {
        $item = $this->find($itemId);
        if (!$item) {
            return false;
        }
        
        $difference = $newQuantity - $item['current_stock'];
        
        $updateData = [
            'current_stock' => $newQuantity,
            'total_adjusted' => $item['total_adjusted'] + abs($difference),
            'last_count_date' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($reason)) {
            $updateData['adjustment_reason'] = $reason;
        }
        
        return $this->update($itemId, $updateData);
    }
    
    /**
     * Reserva estoque
     */
    public function reserveStock(int $itemId, float $quantity): bool
    {
        $item = $this->find($itemId);
        if (!$item || $item['available_stock'] < $quantity) {
            return false;
        }
        
        return $this->update($itemId, [
            'reserved_stock' => $item['reserved_stock'] + $quantity
        ]);
    }
    
    /**
     * Libera estoque reservado
     */
    public function releaseReservedStock(int $itemId, float $quantity): bool
    {
        $item = $this->find($itemId);
        if (!$item || $item['reserved_stock'] < $quantity) {
            return false;
        }
        
        return $this->update($itemId, [
            'reserved_stock' => $item['reserved_stock'] - $quantity
        ]);
    }
    
    /**
     * Atualiza preços
     */
    public function updatePricing(int $itemId, float $costPerUnit = null, float $sellingPrice = null, float $markupPercentage = null): bool
    {
        $updateData = [];
        
        if ($costPerUnit !== null) {
            $updateData['cost_per_unit'] = $costPerUnit;
        }
        
        if ($sellingPrice !== null) {
            $updateData['selling_price'] = $sellingPrice;
        }
        
        if ($markupPercentage !== null) {
            $updateData['markup_percentage'] = $markupPercentage;
            
            // Recalcular preço de venda se custo estiver disponível
            if ($costPerUnit !== null) {
                $updateData['selling_price'] = $costPerUnit * (1 + ($markupPercentage / 100));
            } else {
                $item = $this->find($itemId);
                if ($item && $item['cost_per_unit'] > 0) {
                    $updateData['selling_price'] = $item['cost_per_unit'] * (1 + ($markupPercentage / 100));
                }
            }
        }
        
        return !empty($updateData) ? $this->update($itemId, $updateData) : false;
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
                             ->orLike('item_code', $filters['search'])
                             ->orLike('barcode', $filters['search'])
                             ->orLike('description', $filters['search'])
                             ->orLike('brand', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $builder = $builder->whereIn('type', $filters['type']);
            } else {
                $builder = $builder->where('type', $filters['type']);
            }
        }
        
        if (!empty($filters['category'])) {
            $builder = $builder->where('category', $filters['category']);
        }
        
        if (!empty($filters['supplier_id'])) {
            $builder = $builder->where('supplier_id', $filters['supplier_id']);
        }
        
        if (!empty($filters['is_active'])) {
            $builder = $builder->where('is_active', $filters['is_active']);
        }
        
        if (!empty($filters['is_perishable'])) {
            $builder = $builder->where('is_perishable', $filters['is_perishable']);
        }
        
        if (!empty($filters['low_stock'])) {
            $builder = $builder->where('current_stock <=', 'reorder_point', false);
        }
        
        if (!empty($filters['out_of_stock'])) {
            $builder = $builder->where('current_stock <=', 0);
        }
        
        if (!empty($filters['overstock'])) {
            $builder = $builder->where('current_stock >', 'maximum_stock', false);
        }
        
        if (!empty($filters['expiring_soon'])) {
            $days = $filters['expiring_days'] ?? 7;
            $expirationDate = date('Y-m-d', strtotime("+{$days} days"));
            $builder = $builder->where('is_perishable', 1)
                             ->where('expiration_date <=', $expirationDate)
                             ->where('expiration_date >=', date('Y-m-d'));
        }
        
        if (!empty($filters['min_cost'])) {
            $builder = $builder->where('cost_per_unit >=', $filters['min_cost']);
        }
        
        if (!empty($filters['max_cost'])) {
            $builder = $builder->where('cost_per_unit <=', $filters['max_cost']);
        }
        
        $orderBy = $filters['order_by'] ?? 'name';
        $orderDir = $filters['order_dir'] ?? 'ASC';
        
        return $builder->orderBy($orderBy, $orderDir)->findAll();
    }
    
    /**
     * Obtém estatísticas do inventário
     */
    public function getInventoryStats(): array
    {
        $stats = [];
        
        // Total de itens
        $stats['total_items'] = $this->countAllResults();
        $stats['active_items'] = $this->where('is_active', 1)->countAllResults();
        
        // Itens por tipo
        $typeStats = $this->select('type, COUNT(*) as count')
                         ->where('is_active', 1)
                         ->groupBy('type')
                         ->findAll();
        
        $stats['items_by_type'] = [];
        foreach ($typeStats as $type) {
            $stats['items_by_type'][$type['type']] = $type['count'];
        }
        
        // Alertas de estoque
        $stats['low_stock_items'] = $this->where('current_stock <=', 'reorder_point', false)
                                        ->where('is_active', 1)
                                        ->where('is_tracked', 1)
                                        ->countAllResults();
        
        $stats['out_of_stock_items'] = $this->where('current_stock <=', 0)
                                           ->where('is_active', 1)
                                           ->where('is_tracked', 1)
                                           ->countAllResults();
        
        $stats['overstock_items'] = $this->where('current_stock >', 'maximum_stock', false)
                                        ->where('is_active', 1)
                                        ->where('is_tracked', 1)
                                        ->countAllResults();
        
        // Itens perecíveis
        $stats['perishable_items'] = $this->where('is_perishable', 1)
                                         ->where('is_active', 1)
                                         ->countAllResults();
        
        $stats['expiring_items'] = $this->where('is_perishable', 1)
                                       ->where('expiration_date <=', date('Y-m-d', strtotime('+7 days')))
                                       ->where('expiration_date >=', date('Y-m-d'))
                                       ->where('current_stock >', 0)
                                       ->countAllResults();
        
        $stats['expired_items'] = $this->where('is_perishable', 1)
                                      ->where('expiration_date <', date('Y-m-d'))
                                      ->where('current_stock >', 0)
                                      ->countAllResults();
        
        // Valor do inventário
        $valueResult = $this->select('SUM(current_stock * cost_per_unit) as total_value')
                           ->where('is_active', 1)
                           ->first();
        $stats['total_inventory_value'] = $valueResult['total_value'] ?? 0;
        
        // Valor do inventário disponível
        $availableValueResult = $this->select('SUM(available_stock * cost_per_unit) as available_value')
                                    ->where('is_active', 1)
                                    ->first();
        $stats['available_inventory_value'] = $availableValueResult['available_value'] ?? 0;
        
        // Movimentação recente
        $stats['items_received_today'] = $this->where('DATE(last_purchase_date)', date('Y-m-d'))->countAllResults();
        $stats['items_used_today'] = $this->where('DATE(last_sale_date)', date('Y-m-d'))->countAllResults();
        
        return $stats;
    }
    
    /**
     * Exporta inventário para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $items = $this->advancedSearch($filters);
        
        $csv = "Código,Nome,Tipo,Categoria,Unidade,Estoque Atual,Estoque Mínimo,Ponto Reposição,Custo Unitário,Preço Venda,Fornecedor,Localização,Vencimento,Status\n";
        
        foreach ($items as $item) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%.2f,%s,%s,%s,%s\n",
                $item['item_code'],
                $item['name'],
                $item['type'],
                $item['category'] ?? '',
                $item['unit_of_measure'],
                $item['current_stock'],
                $item['minimum_stock'],
                $item['reorder_point'],
                $item['cost_per_unit'],
                $item['selling_price'] ?? 0,
                $item['supplier_name'] ?? '',
                $item['location'] ?? '',
                $item['expiration_date'] ?? '',
                $item['is_active'] ? 'Ativo' : 'Inativo'
            );
        }
        
        return $csv;
    }
    
    /**
     * Obtém relatório de movimentação
     */
    public function getMovementReport(string $startDate, string $endDate): array
    {
        // Esta função requer uma tabela de movimentações separada
        // Por enquanto, retorna dados básicos do inventário
        $items = $this->where('last_purchase_date >=', $startDate)
                     ->where('last_purchase_date <=', $endDate)
                     ->orWhere('last_sale_date >=', $startDate)
                     ->where('last_sale_date <=', $endDate)
                     ->findAll();
        
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'items_moved' => count($items),
            'items' => $items
        ];
    }
    
    /**
     * Obtém itens para reposição
     */
    public function getItemsToReorder(): array
    {
        return $this->where('current_stock <=', 'reorder_point', false)
                   ->where('is_active', 1)
                   ->where('is_tracked', 1)
                   ->orderBy('current_stock', 'ASC')
                   ->findAll();
    }
    
    /**
     * Calcula necessidade de compra
     */
    public function calculatePurchaseNeeds(): array
    {
        $itemsToReorder = $this->getItemsToReorder();
        $purchaseList = [];
        
        foreach ($itemsToReorder as $item) {
            $quantityNeeded = max(
                $item['reorder_quantity'] ?? ($item['maximum_stock'] - $item['current_stock']),
                $item['minimum_stock'] - $item['current_stock']
            );
            
            if ($quantityNeeded > 0) {
                $purchaseList[] = [
                    'item_id' => $item['id'],
                    'item_code' => $item['item_code'],
                    'name' => $item['name'],
                    'current_stock' => $item['current_stock'],
                    'minimum_stock' => $item['minimum_stock'],
                    'reorder_point' => $item['reorder_point'],
                    'quantity_needed' => $quantityNeeded,
                    'estimated_cost' => $quantityNeeded * $item['cost_per_unit'],
                    'supplier_id' => $item['supplier_id'],
                    'supplier_name' => $item['supplier_name'],
                    'lead_time_days' => $item['lead_time_days']
                ];
            }
        }
        
        return $purchaseList;
    }
    
    /**
     * Verifica se código do item já existe
     */
    public function itemCodeExists(string $itemCode, ?int $excludeId = null): bool
    {
        $builder = $this->where('item_code', $itemCode);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
    
    /**
     * Duplica item do inventário
     */
    public function duplicateItem(int $itemId, string $newName = null): ?int
    {
        $item = $this->find($itemId);
        if (!$item) {
            return null;
        }
        
        // Remove campos que não devem ser duplicados
        unset($item['id']);
        unset($item['item_code']);
        unset($item['barcode']);
        unset($item['created_at']);
        unset($item['updated_at']);
        unset($item['deleted_at']);
        
        // Define novo nome se fornecido
        if ($newName) {
            $item['name'] = $newName;
        } else {
            $item['name'] = $item['name'] . ' (Cópia)';
        }
        
        // Zerar estoques e movimentações
        $item['current_stock'] = 0;
        $item['reserved_stock'] = 0;
        $item['available_stock'] = 0;
        $item['total_received'] = 0;
        $item['total_used'] = 0;
        $item['total_sold'] = 0;
        $item['total_wasted'] = 0;
        $item['total_adjusted'] = 0;
        $item['last_purchase_date'] = null;
        $item['last_sale_date'] = null;
        $item['last_count_date'] = null;
        
        return $this->insert($item);
    }
}