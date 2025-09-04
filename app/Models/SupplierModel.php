<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Fornecedores com Multi-Tenancy
 */
class SupplierModel extends BaseMultiTenantModel
{
    protected $table = 'suppliers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'supplier_code',
        'company_name',
        'trade_name',
        'contact_name',
        'email',
        'phone',
        'mobile',
        'website',
        'tax_id',
        'state_registration',
        'municipal_registration',
        'address_street',
        'address_number',
        'address_complement',
        'address_neighborhood',
        'address_city',
        'address_state',
        'address_zipcode',
        'address_country',
        'billing_address_street',
        'billing_address_number',
        'billing_address_complement',
        'billing_address_neighborhood',
        'billing_address_city',
        'billing_address_state',
        'billing_address_zipcode',
        'billing_address_country',
        'category',
        'subcategory',
        'type',
        'status',
        'rating',
        'credit_limit',
        'payment_terms',
        'delivery_days',
        'minimum_order',
        'delivery_fee',
        'discount_percentage',
        'contract_start_date',
        'contract_end_date',
        'bank_name',
        'bank_agency',
        'bank_account',
        'pix_key',
        'pix_type',
        'total_orders',
        'total_purchases',
        'last_order_date',
        'last_payment_date',
        'average_delivery_time',
        'on_time_delivery_rate',
        'quality_score',
        'service_score',
        'price_competitiveness',
        'is_active',
        'is_preferred',
        'is_certified',
        'requires_approval',
        'certifications',
        'specialties',
        'products_supplied',
        'delivery_areas',
        'operating_hours',
        'emergency_contact',
        'notes',
        'internal_notes',
        'tags',
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
        'company_name' => 'required|min_length[2]|max_length[100]',
        'email' => 'permit_empty|valid_email|max_length[100]',
        'phone' => 'permit_empty|min_length[10]|max_length[20]',
        'tax_id' => 'permit_empty|min_length[11]|max_length[18]',
        'category' => 'permit_empty|in_list[food,beverage,equipment,cleaning,packaging,service,maintenance,other]',
        'type' => 'permit_empty|in_list[manufacturer,distributor,wholesaler,retailer,service_provider,other]',
        'status' => 'permit_empty|in_list[active,inactive,suspended,pending,blacklisted]',
        'rating' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
        'credit_limit' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'minimum_order' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'delivery_fee' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'discount_percentage' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        'contract_start_date' => 'permit_empty|valid_date',
        'contract_end_date' => 'permit_empty|valid_date',
        'quality_score' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[10]',
        'service_score' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[10]',
        'price_competitiveness' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[10]'
    ];
    
    protected $validationMessages = [
        'company_name' => [
            'required' => 'Nome da empresa é obrigatório',
            'min_length' => 'Nome da empresa deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome da empresa não pode exceder 100 caracteres'
        ],
        'email' => [
            'valid_email' => 'Email deve ter um formato válido',
            'max_length' => 'Email não pode exceder 100 caracteres'
        ],
        'phone' => [
            'min_length' => 'Telefone deve ter pelo menos 10 dígitos',
            'max_length' => 'Telefone não pode exceder 20 caracteres'
        ],
        'tax_id' => [
            'min_length' => 'CNPJ/CPF deve ter pelo menos 11 dígitos',
            'max_length' => 'CNPJ/CPF não pode exceder 18 caracteres'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generateSupplierCode'];
    protected $beforeUpdate = ['updateTimestamps', 'validateStatusChange'];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'active';
        }
        
        if (!isset($data['data']['type'])) {
            $data['data']['type'] = 'distributor';
        }
        
        if (!isset($data['data']['category'])) {
            $data['data']['category'] = 'food';
        }
        
        if (!isset($data['data']['rating'])) {
            $data['data']['rating'] = 3;
        }
        
        if (!isset($data['data']['credit_limit'])) {
            $data['data']['credit_limit'] = 0.00;
        }
        
        if (!isset($data['data']['minimum_order'])) {
            $data['data']['minimum_order'] = 0.00;
        }
        
        if (!isset($data['data']['delivery_fee'])) {
            $data['data']['delivery_fee'] = 0.00;
        }
        
        if (!isset($data['data']['discount_percentage'])) {
            $data['data']['discount_percentage'] = 0.00;
        }
        
        if (!isset($data['data']['delivery_days'])) {
            $data['data']['delivery_days'] = 7;
        }
        
        if (!isset($data['data']['payment_terms'])) {
            $data['data']['payment_terms'] = '30 dias';
        }
        
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        
        if (!isset($data['data']['is_preferred'])) {
            $data['data']['is_preferred'] = 0;
        }
        
        if (!isset($data['data']['is_certified'])) {
            $data['data']['is_certified'] = 0;
        }
        
        if (!isset($data['data']['requires_approval'])) {
            $data['data']['requires_approval'] = 0;
        }
        
        if (!isset($data['data']['total_orders'])) {
            $data['data']['total_orders'] = 0;
        }
        
        if (!isset($data['data']['total_purchases'])) {
            $data['data']['total_purchases'] = 0.00;
        }
        
        if (!isset($data['data']['average_delivery_time'])) {
            $data['data']['average_delivery_time'] = 0;
        }
        
        if (!isset($data['data']['on_time_delivery_rate'])) {
            $data['data']['on_time_delivery_rate'] = 0.00;
        }
        
        if (!isset($data['data']['quality_score'])) {
            $data['data']['quality_score'] = 5.0;
        }
        
        if (!isset($data['data']['service_score'])) {
            $data['data']['service_score'] = 5.0;
        }
        
        if (!isset($data['data']['price_competitiveness'])) {
            $data['data']['price_competitiveness'] = 5.0;
        }
        
        if (!isset($data['data']['address_country'])) {
            $data['data']['address_country'] = 'Brasil';
        }
        
        if (!isset($data['data']['billing_address_country'])) {
            $data['data']['billing_address_country'] = 'Brasil';
        }
        
        return $data;
    }
    
    /**
     * Gera código único do fornecedor
     */
    protected function generateSupplierCode(array $data): array
    {
        if (!isset($data['data']['supplier_code']) || empty($data['data']['supplier_code'])) {
            $restaurantId = $data['data']['restaurant_id'] ?? $this->getCurrentTenantId();
            $category = strtoupper(substr($data['data']['category'] ?? 'SUP', 0, 3));
            $timestamp = date('ymdHis');
            
            // Busca o último código gerado hoje
            $lastCode = $this->where('restaurant_id', $restaurantId)
                           ->where('DATE(created_at)', date('Y-m-d'))
                           ->orderBy('id', 'DESC')
                           ->first();
            
            $sequence = 1;
            if ($lastCode && !empty($lastCode['supplier_code'])) {
                $lastSequence = (int) substr($lastCode['supplier_code'], -3);
                $sequence = $lastSequence + 1;
            }
            
            $data['data']['supplier_code'] = $category . $timestamp . str_pad($sequence, 3, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }
    
    /**
     * Atualiza timestamps de atividade
     */
    protected function updateTimestamps(array $data): array
    {
        // Pode ser usado para registrar alterações importantes
        return $data;
    }
    
    /**
     * Valida mudanças de status
     */
    protected function validateStatusChange(array $data): array
    {
        // Implementar lógica de validação de mudança de status se necessário
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Busca fornecedor por código
     */
    public function findByCode(string $supplierCode): ?array
    {
        return $this->where('supplier_code', $supplierCode)->first();
    }
    
    /**
     * Busca fornecedor por CNPJ/CPF
     */
    public function findByTaxId(string $taxId): ?array
    {
        return $this->where('tax_id', $taxId)->first();
    }
    
    /**
     * Busca fornecedor por email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
    
    /**
     * Obtém fornecedores ativos
     */
    public function getActiveSuppliers(): array
    {
        return $this->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores por categoria
     */
    public function getSuppliersByCategory(string $category): array
    {
        return $this->where('category', $category)
                   ->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores por tipo
     */
    public function getSuppliersByType(string $type): array
    {
        return $this->where('type', $type)
                   ->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores preferenciais
     */
    public function getPreferredSuppliers(): array
    {
        return $this->where('is_preferred', 1)
                   ->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('rating', 'DESC')
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores certificados
     */
    public function getCertifiedSuppliers(): array
    {
        return $this->where('is_certified', 1)
                   ->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores por status
     */
    public function getSuppliersByStatus(string $status): array
    {
        return $this->where('status', $status)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores por avaliação
     */
    public function getSuppliersByRating(int $minRating = 1, int $maxRating = 5): array
    {
        return $this->where('rating >=', $minRating)
                   ->where('rating <=', $maxRating)
                   ->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('rating', 'DESC')
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores por cidade
     */
    public function getSuppliersByCity(string $city): array
    {
        return $this->where('address_city', $city)
                   ->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores por estado
     */
    public function getSuppliersByState(string $state): array
    {
        return $this->where('address_state', $state)
                   ->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores com contratos vencendo
     */
    public function getSuppliersWithExpiringContracts(int $daysAhead = 30): array
    {
        $expirationDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        
        return $this->where('contract_end_date <=', $expirationDate)
                   ->where('contract_end_date >=', date('Y-m-d'))
                   ->where('status', 'active')
                   ->orderBy('contract_end_date', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores com contratos vencidos
     */
    public function getSuppliersWithExpiredContracts(): array
    {
        return $this->where('contract_end_date <', date('Y-m-d'))
                   ->where('status', 'active')
                   ->orderBy('contract_end_date', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém top fornecedores por volume de compras
     */
    public function getTopSuppliersByPurchases(int $limit = 10): array
    {
        return $this->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('total_purchases', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém fornecedores com melhor desempenho de entrega
     */
    public function getBestPerformingSuppliers(int $limit = 10): array
    {
        return $this->where('status', 'active')
                   ->where('is_active', 1)
                   ->where('total_orders >', 0)
                   ->orderBy('on_time_delivery_rate', 'DESC')
                   ->orderBy('quality_score', 'DESC')
                   ->orderBy('service_score', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Atualiza estatísticas do fornecedor
     */
    public function updateSupplierStats(int $supplierId, array $stats): bool
    {
        $updateData = [];
        
        if (isset($stats['total_orders'])) {
            $updateData['total_orders'] = $stats['total_orders'];
        }
        
        if (isset($stats['total_purchases'])) {
            $updateData['total_purchases'] = $stats['total_purchases'];
        }
        
        if (isset($stats['last_order_date'])) {
            $updateData['last_order_date'] = $stats['last_order_date'];
        }
        
        if (isset($stats['last_payment_date'])) {
            $updateData['last_payment_date'] = $stats['last_payment_date'];
        }
        
        if (isset($stats['average_delivery_time'])) {
            $updateData['average_delivery_time'] = $stats['average_delivery_time'];
        }
        
        if (isset($stats['on_time_delivery_rate'])) {
            $updateData['on_time_delivery_rate'] = $stats['on_time_delivery_rate'];
        }
        
        return !empty($updateData) ? $this->update($supplierId, $updateData) : false;
    }
    
    /**
     * Atualiza avaliação do fornecedor
     */
    public function updateSupplierRating(int $supplierId, int $rating, float $qualityScore = null, float $serviceScore = null, float $priceScore = null): bool
    {
        $updateData = ['rating' => $rating];
        
        if ($qualityScore !== null) {
            $updateData['quality_score'] = $qualityScore;
        }
        
        if ($serviceScore !== null) {
            $updateData['service_score'] = $serviceScore;
        }
        
        if ($priceScore !== null) {
            $updateData['price_competitiveness'] = $priceScore;
        }
        
        return $this->update($supplierId, $updateData);
    }
    
    /**
     * Ativa fornecedor
     */
    public function activateSupplier(int $supplierId): bool
    {
        return $this->update($supplierId, [
            'status' => 'active',
            'is_active' => 1
        ]);
    }
    
    /**
     * Desativa fornecedor
     */
    public function deactivateSupplier(int $supplierId, string $reason = ''): bool
    {
        $updateData = [
            'status' => 'inactive',
            'is_active' => 0
        ];
        
        if (!empty($reason)) {
            $updateData['internal_notes'] = $reason;
        }
        
        return $this->update($supplierId, $updateData);
    }
    
    /**
     * Suspende fornecedor
     */
    public function suspendSupplier(int $supplierId, string $reason = ''): bool
    {
        $updateData = [
            'status' => 'suspended',
            'is_active' => 0
        ];
        
        if (!empty($reason)) {
            $updateData['internal_notes'] = $reason;
        }
        
        return $this->update($supplierId, $updateData);
    }
    
    /**
     * Marca fornecedor como preferencial
     */
    public function setAsPreferred(int $supplierId, bool $preferred = true): bool
    {
        return $this->update($supplierId, ['is_preferred' => $preferred ? 1 : 0]);
    }
    
    /**
     * Busca avançada de fornecedores
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('company_name', $filters['search'])
                             ->orLike('trade_name', $filters['search'])
                             ->orLike('supplier_code', $filters['search'])
                             ->orLike('contact_name', $filters['search'])
                             ->orLike('email', $filters['search'])
                             ->orLike('tax_id', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['category'])) {
            if (is_array($filters['category'])) {
                $builder = $builder->whereIn('category', $filters['category']);
            } else {
                $builder = $builder->where('category', $filters['category']);
            }
        }
        
        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $builder = $builder->whereIn('type', $filters['type']);
            } else {
                $builder = $builder->where('type', $filters['type']);
            }
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder = $builder->whereIn('status', $filters['status']);
            } else {
                $builder = $builder->where('status', $filters['status']);
            }
        }
        
        if (!empty($filters['city'])) {
            $builder = $builder->where('address_city', $filters['city']);
        }
        
        if (!empty($filters['state'])) {
            $builder = $builder->where('address_state', $filters['state']);
        }
        
        if (!empty($filters['is_preferred'])) {
            $builder = $builder->where('is_preferred', $filters['is_preferred']);
        }
        
        if (!empty($filters['is_certified'])) {
            $builder = $builder->where('is_certified', $filters['is_certified']);
        }
        
        if (!empty($filters['min_rating'])) {
            $builder = $builder->where('rating >=', $filters['min_rating']);
        }
        
        if (!empty($filters['max_rating'])) {
            $builder = $builder->where('rating <=', $filters['max_rating']);
        }
        
        if (!empty($filters['min_credit_limit'])) {
            $builder = $builder->where('credit_limit >=', $filters['min_credit_limit']);
        }
        
        if (!empty($filters['contract_expiring'])) {
            $days = $filters['expiring_days'] ?? 30;
            $expirationDate = date('Y-m-d', strtotime("+{$days} days"));
            $builder = $builder->where('contract_end_date <=', $expirationDate)
                             ->where('contract_end_date >=', date('Y-m-d'));
        }
        
        $orderBy = $filters['order_by'] ?? 'company_name';
        $orderDir = $filters['order_dir'] ?? 'ASC';
        
        return $builder->orderBy($orderBy, $orderDir)->findAll();
    }
    
    /**
     * Obtém estatísticas dos fornecedores
     */
    public function getSupplierStats(): array
    {
        $stats = [];
        
        // Total de fornecedores
        $stats['total_suppliers'] = $this->countAllResults();
        $stats['active_suppliers'] = $this->where('status', 'active')->countAllResults();
        $stats['inactive_suppliers'] = $this->where('status', 'inactive')->countAllResults();
        $stats['suspended_suppliers'] = $this->where('status', 'suspended')->countAllResults();
        
        // Fornecedores por categoria
        $categoryStats = $this->select('category, COUNT(*) as count')
                             ->where('status', 'active')
                             ->groupBy('category')
                             ->findAll();
        
        $stats['suppliers_by_category'] = [];
        foreach ($categoryStats as $category) {
            $stats['suppliers_by_category'][$category['category']] = $category['count'];
        }
        
        // Fornecedores por tipo
        $typeStats = $this->select('type, COUNT(*) as count')
                         ->where('status', 'active')
                         ->groupBy('type')
                         ->findAll();
        
        $stats['suppliers_by_type'] = [];
        foreach ($typeStats as $type) {
            $stats['suppliers_by_type'][$type['type']] = $type['count'];
        }
        
        // Fornecedores especiais
        $stats['preferred_suppliers'] = $this->where('is_preferred', 1)
                                            ->where('status', 'active')
                                            ->countAllResults();
        
        $stats['certified_suppliers'] = $this->where('is_certified', 1)
                                            ->where('status', 'active')
                                            ->countAllResults();
        
        // Contratos
        $stats['contracts_expiring_30_days'] = $this->where('contract_end_date <=', date('Y-m-d', strtotime('+30 days')))
                                                    ->where('contract_end_date >=', date('Y-m-d'))
                                                    ->where('status', 'active')
                                                    ->countAllResults();
        
        $stats['expired_contracts'] = $this->where('contract_end_date <', date('Y-m-d'))
                                          ->where('status', 'active')
                                          ->countAllResults();
        
        // Avaliações
        $ratingStats = $this->select('AVG(rating) as avg_rating, MIN(rating) as min_rating, MAX(rating) as max_rating')
                           ->where('status', 'active')
                           ->first();
        
        $stats['average_rating'] = round($ratingStats['avg_rating'] ?? 0, 2);
        $stats['min_rating'] = $ratingStats['min_rating'] ?? 0;
        $stats['max_rating'] = $ratingStats['max_rating'] ?? 0;
        
        // Performance
        $performanceStats = $this->select('AVG(on_time_delivery_rate) as avg_delivery_rate, AVG(quality_score) as avg_quality, AVG(service_score) as avg_service')
                                ->where('status', 'active')
                                ->where('total_orders >', 0)
                                ->first();
        
        $stats['average_delivery_rate'] = round($performanceStats['avg_delivery_rate'] ?? 0, 2);
        $stats['average_quality_score'] = round($performanceStats['avg_quality'] ?? 0, 2);
        $stats['average_service_score'] = round($performanceStats['avg_service'] ?? 0, 2);
        
        // Volume de compras
        $purchaseStats = $this->select('SUM(total_purchases) as total_volume, AVG(total_purchases) as avg_volume')
                             ->where('status', 'active')
                             ->first();
        
        $stats['total_purchase_volume'] = $purchaseStats['total_volume'] ?? 0;
        $stats['average_purchase_volume'] = round($purchaseStats['avg_volume'] ?? 0, 2);
        
        return $stats;
    }
    
    /**
     * Exporta fornecedores para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $suppliers = $this->advancedSearch($filters);
        
        $csv = "Código,Empresa,Nome Fantasia,Contato,Email,Telefone,CNPJ/CPF,Categoria,Tipo,Status,Avaliação,Cidade,Estado,Preferencial,Certificado\n";
        
        foreach ($suppliers as $supplier) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s\n",
                $supplier['supplier_code'],
                $supplier['company_name'],
                $supplier['trade_name'] ?? '',
                $supplier['contact_name'] ?? '',
                $supplier['email'] ?? '',
                $supplier['phone'] ?? '',
                $supplier['tax_id'] ?? '',
                $supplier['category'] ?? '',
                $supplier['type'] ?? '',
                $supplier['status'],
                $supplier['rating'],
                $supplier['address_city'] ?? '',
                $supplier['address_state'] ?? '',
                $supplier['is_preferred'] ? 'Sim' : 'Não',
                $supplier['is_certified'] ? 'Sim' : 'Não'
            );
        }
        
        return $csv;
    }
    
    /**
     * Obtém relatório de performance dos fornecedores
     */
    public function getPerformanceReport(string $startDate = null, string $endDate = null): array
    {
        $builder = $this->where('status', 'active')
                       ->where('total_orders >', 0);
        
        if ($startDate) {
            $builder->where('last_order_date >=', $startDate);
        }
        
        if ($endDate) {
            $builder->where('last_order_date <=', $endDate);
        }
        
        $suppliers = $builder->orderBy('on_time_delivery_rate', 'DESC')
                           ->orderBy('quality_score', 'DESC')
                           ->findAll();
        
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'suppliers_evaluated' => count($suppliers),
            'suppliers' => $suppliers
        ];
    }
    
    /**
     * Verifica se código do fornecedor já existe
     */
    public function supplierCodeExists(string $supplierCode, ?int $excludeId = null): bool
    {
        $builder = $this->where('supplier_code', $supplierCode);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
    
    /**
     * Verifica se CNPJ/CPF já existe
     */
    public function taxIdExists(string $taxId, ?int $excludeId = null): bool
    {
        $builder = $this->where('tax_id', $taxId);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
    
    /**
     * Verifica se email já existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $builder = $this->where('email', $email);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
    
    /**
     * Duplica fornecedor
     */
    public function duplicateSupplier(int $supplierId, string $newCompanyName = null): ?int
    {
        $supplier = $this->find($supplierId);
        if (!$supplier) {
            return null;
        }
        
        // Remove campos que não devem ser duplicados
        unset($supplier['id']);
        unset($supplier['supplier_code']);
        unset($supplier['created_at']);
        unset($supplier['updated_at']);
        unset($supplier['deleted_at']);
        
        // Define novo nome se fornecido
        if ($newCompanyName) {
            $supplier['company_name'] = $newCompanyName;
        } else {
            $supplier['company_name'] = $supplier['company_name'] . ' (Cópia)';
        }
        
        // Limpar dados únicos
        $supplier['tax_id'] = null;
        $supplier['email'] = null;
        
        // Resetar estatísticas
        $supplier['total_orders'] = 0;
        $supplier['total_purchases'] = 0.00;
        $supplier['last_order_date'] = null;
        $supplier['last_payment_date'] = null;
        $supplier['average_delivery_time'] = 0;
        $supplier['on_time_delivery_rate'] = 0.00;
        
        // Definir como não preferencial
        $supplier['is_preferred'] = 0;
        
        return $this->insert($supplier);
    }
}