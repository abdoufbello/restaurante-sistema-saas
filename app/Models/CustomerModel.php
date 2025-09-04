<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Clientes com Multi-Tenancy
 */
class CustomerModel extends BaseMultiTenantModel
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'customer_code',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'mobile',
        'birth_date',
        'gender',
        'document_type',
        'document_number',
        'address',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'neighborhood',
        'reference_point',
        'delivery_instructions',
        'customer_type',
        'status',
        'is_active',
        'is_vip',
        'loyalty_points',
        'total_orders',
        'total_spent',
        'average_order_value',
        'last_order_date',
        'first_order_date',
        'preferred_payment_method',
        'dietary_restrictions',
        'allergies',
        'preferences',
        'notes',
        'internal_notes',
        'marketing_consent',
        'sms_consent',
        'email_consent',
        'language',
        'timezone',
        'source',
        'referral_code',
        'referred_by',
        'tags',
        'avatar',
        'social_media',
        'emergency_contact',
        'emergency_phone',
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
        'first_name' => 'required|min_length[2]|max_length[100]',
        'last_name' => 'required|min_length[2]|max_length[100]',
        'email' => 'permit_empty|valid_email|max_length[255]',
        'phone' => 'permit_empty|min_length[10]|max_length[20]',
        'mobile' => 'permit_empty|min_length[10]|max_length[20]',
        'birth_date' => 'permit_empty|valid_date',
        'gender' => 'permit_empty|in_list[M,F,O]',
        'document_type' => 'permit_empty|in_list[cpf,cnpj,rg,passport,other]',
        'document_number' => 'permit_empty|max_length[50]',
        'address' => 'permit_empty|max_length[500]',
        'city' => 'permit_empty|max_length[100]',
        'state' => 'permit_empty|max_length[100]',
        'postal_code' => 'permit_empty|max_length[20]',
        'country' => 'permit_empty|max_length[100]',
        'customer_type' => 'permit_empty|in_list[individual,corporate,vip]',
        'status' => 'permit_empty|in_list[active,inactive,blocked,pending]',
        'loyalty_points' => 'permit_empty|integer|greater_than_equal_to[0]',
        'total_orders' => 'permit_empty|integer|greater_than_equal_to[0]',
        'total_spent' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'preferred_payment_method' => 'permit_empty|in_list[cash,credit_card,debit_card,pix,bank_transfer,digital_wallet]'
    ];
    
    protected $validationMessages = [
        'first_name' => [
            'required' => 'Nome é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome deve ter no máximo 100 caracteres'
        ],
        'last_name' => [
            'required' => 'Sobrenome é obrigatório',
            'min_length' => 'Sobrenome deve ter pelo menos 2 caracteres',
            'max_length' => 'Sobrenome deve ter no máximo 100 caracteres'
        ],
        'email' => [
            'valid_email' => 'Email deve ter um formato válido',
            'max_length' => 'Email deve ter no máximo 255 caracteres'
        ],
        'phone' => [
            'min_length' => 'Telefone deve ter pelo menos 10 dígitos',
            'max_length' => 'Telefone deve ter no máximo 20 dígitos'
        ],
        'mobile' => [
            'min_length' => 'Celular deve ter pelo menos 10 dígitos',
            'max_length' => 'Celular deve ter no máximo 20 dígitos'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generateCustomerCode'];
    protected $beforeUpdate = ['updateStats'];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'active';
        }
        
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        
        if (!isset($data['data']['is_vip'])) {
            $data['data']['is_vip'] = 0;
        }
        
        if (!isset($data['data']['loyalty_points'])) {
            $data['data']['loyalty_points'] = 0;
        }
        
        if (!isset($data['data']['total_orders'])) {
            $data['data']['total_orders'] = 0;
        }
        
        if (!isset($data['data']['total_spent'])) {
            $data['data']['total_spent'] = 0.00;
        }
        
        if (!isset($data['data']['customer_type'])) {
            $data['data']['customer_type'] = 'individual';
        }
        
        if (!isset($data['data']['marketing_consent'])) {
            $data['data']['marketing_consent'] = 0;
        }
        
        if (!isset($data['data']['sms_consent'])) {
            $data['data']['sms_consent'] = 0;
        }
        
        if (!isset($data['data']['email_consent'])) {
            $data['data']['email_consent'] = 0;
        }
        
        if (!isset($data['data']['language'])) {
            $data['data']['language'] = 'pt-BR';
        }
        
        if (!isset($data['data']['timezone'])) {
            $data['data']['timezone'] = 'America/Sao_Paulo';
        }
        
        // Gerar nome completo
        if (isset($data['data']['first_name']) && isset($data['data']['last_name'])) {
            $data['data']['full_name'] = trim($data['data']['first_name'] . ' ' . $data['data']['last_name']);
        }
        
        return $data;
    }
    
    /**
     * Gera código único do cliente
     */
    protected function generateCustomerCode(array $data): array
    {
        if (!isset($data['data']['customer_code']) || empty($data['data']['customer_code'])) {
            $restaurantId = $data['data']['restaurant_id'] ?? $this->getCurrentTenantId();
            $prefix = 'CL';
            $timestamp = date('ymd');
            
            // Busca o último código gerado hoje
            $lastCode = $this->where('restaurant_id', $restaurantId)
                           ->where('DATE(created_at)', date('Y-m-d'))
                           ->orderBy('id', 'DESC')
                           ->first();
            
            $sequence = 1;
            if ($lastCode && !empty($lastCode['customer_code'])) {
                $lastSequence = (int) substr($lastCode['customer_code'], -4);
                $sequence = $lastSequence + 1;
            }
            
            $data['data']['customer_code'] = $prefix . $timestamp . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }
    
    /**
     * Atualiza estatísticas do cliente
     */
    protected function updateStats(array $data): array
    {
        // Gerar nome completo se necessário
        if (isset($data['data']['first_name']) && isset($data['data']['last_name'])) {
            $data['data']['full_name'] = trim($data['data']['first_name'] . ' ' . $data['data']['last_name']);
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Busca cliente por código
     */
    public function findByCode(string $customerCode): ?array
    {
        return $this->where('customer_code', $customerCode)->first();
    }
    
    /**
     * Busca cliente por email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
    
    /**
     * Busca cliente por telefone
     */
    public function findByPhone(string $phone): ?array
    {
        return $this->where('phone', $phone)
                   ->orWhere('mobile', $phone)
                   ->first();
    }
    
    /**
     * Busca cliente por documento
     */
    public function findByDocument(string $documentNumber): ?array
    {
        return $this->where('document_number', $documentNumber)->first();
    }
    
    /**
     * Obtém clientes ativos
     */
    public function getActiveCustomers(): array
    {
        return $this->where('status', 'active')
                   ->where('is_active', 1)
                   ->orderBy('full_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém clientes VIP
     */
    public function getVipCustomers(): array
    {
        return $this->where('is_vip', 1)
                   ->where('status', 'active')
                   ->orderBy('total_spent', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém clientes por tipo
     */
    public function getCustomersByType(string $type): array
    {
        return $this->where('customer_type', $type)
                   ->where('status', 'active')
                   ->orderBy('full_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém clientes por status
     */
    public function getCustomersByStatus(string $status): array
    {
        return $this->where('status', $status)
                   ->orderBy('full_name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém aniversariantes do mês
     */
    public function getBirthdaysThisMonth(): array
    {
        $month = date('m');
        return $this->where('MONTH(birth_date)', $month)
                   ->where('status', 'active')
                   ->orderBy('DAY(birth_date)', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém aniversariantes de hoje
     */
    public function getBirthdaysToday(): array
    {
        $today = date('m-d');
        return $this->where('DATE_FORMAT(birth_date, "%m-%d")', $today)
                   ->where('status', 'active')
                   ->findAll();
    }
    
    /**
     * Obtém top clientes por valor gasto
     */
    public function getTopCustomersBySpending(int $limit = 10): array
    {
        return $this->where('status', 'active')
                   ->where('total_spent >', 0)
                   ->orderBy('total_spent', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém clientes frequentes
     */
    public function getFrequentCustomers(int $minOrders = 5): array
    {
        return $this->where('status', 'active')
                   ->where('total_orders >=', $minOrders)
                   ->orderBy('total_orders', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém novos clientes (últimos 30 dias)
     */
    public function getNewCustomers(int $days = 30): array
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->where('created_at >=', $date)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém clientes inativos
     */
    public function getInactiveCustomers(int $days = 90): array
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->where('status', 'active')
                   ->where('last_order_date <', $date)
                   ->orderBy('last_order_date', 'ASC')
                   ->findAll();
    }
    
    /**
     * Atualiza estatísticas do cliente após pedido
     */
    public function updateCustomerStats(int $customerId, float $orderValue): bool
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return false;
        }
        
        $totalOrders = $customer['total_orders'] + 1;
        $totalSpent = $customer['total_spent'] + $orderValue;
        $averageOrderValue = $totalSpent / $totalOrders;
        
        $updateData = [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'average_order_value' => $averageOrderValue,
            'last_order_date' => date('Y-m-d H:i:s')
        ];
        
        if ($customer['first_order_date'] === null) {
            $updateData['first_order_date'] = date('Y-m-d H:i:s');
        }
        
        // Verificar se deve ser VIP (mais de R$ 1000 gastos ou mais de 10 pedidos)
        if ($totalSpent >= 1000 || $totalOrders >= 10) {
            $updateData['is_vip'] = 1;
        }
        
        return $this->update($customerId, $updateData);
    }
    
    /**
     * Adiciona pontos de fidelidade
     */
    public function addLoyaltyPoints(int $customerId, int $points): bool
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return false;
        }
        
        $newPoints = $customer['loyalty_points'] + $points;
        return $this->update($customerId, ['loyalty_points' => $newPoints]);
    }
    
    /**
     * Remove pontos de fidelidade
     */
    public function redeemLoyaltyPoints(int $customerId, int $points): bool
    {
        $customer = $this->find($customerId);
        if (!$customer || $customer['loyalty_points'] < $points) {
            return false;
        }
        
        $newPoints = $customer['loyalty_points'] - $points;
        return $this->update($customerId, ['loyalty_points' => $newPoints]);
    }
    
    /**
     * Ativa/desativa cliente
     */
    public function toggleStatus(int $customerId): bool
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return false;
        }
        
        $newStatus = $customer['status'] === 'active' ? 'inactive' : 'active';
        $newIsActive = $customer['is_active'] ? 0 : 1;
        
        return $this->update($customerId, [
            'status' => $newStatus,
            'is_active' => $newIsActive
        ]);
    }
    
    /**
     * Bloqueia cliente
     */
    public function blockCustomer(int $customerId, string $reason = ''): bool
    {
        $updateData = [
            'status' => 'blocked',
            'is_active' => 0
        ];
        
        if (!empty($reason)) {
            $updateData['internal_notes'] = $reason;
        }
        
        return $this->update($customerId, $updateData);
    }
    
    /**
     * Desbloqueia cliente
     */
    public function unblockCustomer(int $customerId): bool
    {
        return $this->update($customerId, [
            'status' => 'active',
            'is_active' => 1
        ]);
    }
    
    /**
     * Busca avançada de clientes
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('full_name', $filters['search'])
                             ->orLike('first_name', $filters['search'])
                             ->orLike('last_name', $filters['search'])
                             ->orLike('email', $filters['search'])
                             ->orLike('phone', $filters['search'])
                             ->orLike('mobile', $filters['search'])
                             ->orLike('customer_code', $filters['search'])
                             ->orLike('document_number', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['status'])) {
            $builder = $builder->where('status', $filters['status']);
        }
        
        if (!empty($filters['customer_type'])) {
            $builder = $builder->where('customer_type', $filters['customer_type']);
        }
        
        if (!empty($filters['is_vip'])) {
            $builder = $builder->where('is_vip', $filters['is_vip']);
        }
        
        if (!empty($filters['city'])) {
            $builder = $builder->where('city', $filters['city']);
        }
        
        if (!empty($filters['state'])) {
            $builder = $builder->where('state', $filters['state']);
        }
        
        if (!empty($filters['birth_month'])) {
            $builder = $builder->where('MONTH(birth_date)', $filters['birth_month']);
        }
        
        if (!empty($filters['min_orders'])) {
            $builder = $builder->where('total_orders >=', $filters['min_orders']);
        }
        
        if (!empty($filters['min_spent'])) {
            $builder = $builder->where('total_spent >=', $filters['min_spent']);
        }
        
        if (!empty($filters['start_date'])) {
            $builder = $builder->where('DATE(created_at) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder = $builder->where('DATE(created_at) <=', $filters['end_date']);
        }
        
        $orderBy = $filters['order_by'] ?? 'full_name';
        $orderDir = $filters['order_dir'] ?? 'ASC';
        
        return $builder->orderBy($orderBy, $orderDir)->findAll();
    }
    
    /**
     * Obtém estatísticas de clientes
     */
    public function getCustomerStats(): array
    {
        $stats = [];
        
        // Total de clientes
        $stats['total_customers'] = $this->countAllResults();
        
        // Clientes ativos
        $stats['active_customers'] = $this->where('status', 'active')->countAllResults();
        
        // Clientes VIP
        $stats['vip_customers'] = $this->where('is_vip', 1)->countAllResults();
        
        // Clientes bloqueados
        $stats['blocked_customers'] = $this->where('status', 'blocked')->countAllResults();
        
        // Novos clientes (últimos 30 dias)
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        $stats['new_customers_30d'] = $this->where('created_at >=', $thirtyDaysAgo)->countAllResults();
        
        // Clientes por tipo
        $stats['customers_by_type'] = [
            'individual' => $this->where('customer_type', 'individual')->countAllResults(),
            'corporate' => $this->where('customer_type', 'corporate')->countAllResults(),
            'vip' => $this->where('customer_type', 'vip')->countAllResults()
        ];
        
        // Valor total gasto por todos os clientes
        $totalSpentResult = $this->selectSum('total_spent')->first();
        $stats['total_revenue'] = $totalSpentResult['total_spent'] ?? 0;
        
        // Valor médio por cliente
        $stats['average_customer_value'] = $stats['active_customers'] > 0 
            ? $stats['total_revenue'] / $stats['active_customers'] 
            : 0;
        
        // Total de pedidos de todos os clientes
        $totalOrdersResult = $this->selectSum('total_orders')->first();
        $stats['total_orders'] = $totalOrdersResult['total_orders'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Exporta clientes para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $customers = $this->advancedSearch($filters);
        
        $csv = "Código,Nome Completo,Email,Telefone,Celular,Tipo,Status,VIP,Total Pedidos,Total Gasto,Pontos Fidelidade,Data Cadastro\n";
        
        foreach ($customers as $customer) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%d,%.2f,%d,%s\n",
                $customer['customer_code'],
                $customer['full_name'],
                $customer['email'] ?? '',
                $customer['phone'] ?? '',
                $customer['mobile'] ?? '',
                $customer['customer_type'],
                $customer['status'],
                $customer['is_vip'] ? 'Sim' : 'Não',
                $customer['total_orders'],
                $customer['total_spent'],
                $customer['loyalty_points'],
                $customer['created_at']
            );
        }
        
        return $csv;
    }
    
    /**
     * Duplica cliente (para migração entre tenants)
     */
    public function duplicateCustomer(int $customerId, int $newRestaurantId): ?int
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return null;
        }
        
        // Remove campos que não devem ser duplicados
        unset($customer['id'], $customer['created_at'], $customer['updated_at'], $customer['deleted_at']);
        
        // Define novo restaurante
        $customer['restaurant_id'] = $newRestaurantId;
        
        // Gera novo código de cliente
        unset($customer['customer_code']);
        
        // Reseta estatísticas
        $customer['total_orders'] = 0;
        $customer['total_spent'] = 0.00;
        $customer['average_order_value'] = 0.00;
        $customer['loyalty_points'] = 0;
        $customer['last_order_date'] = null;
        $customer['first_order_date'] = null;
        
        return $this->insert($customer);
    }
    
    /**
     * Gera relatório de clientes por período
     */
    public function getCustomerReport(string $startDate, string $endDate): array
    {
        return $this->where('DATE(created_at) >=', $startDate)
                   ->where('DATE(created_at) <=', $endDate)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém clientes com mais pontos de fidelidade
     */
    public function getTopLoyaltyCustomers(int $limit = 10): array
    {
        return $this->where('loyalty_points >', 0)
                   ->where('status', 'active')
                   ->orderBy('loyalty_points', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Verifica se email já existe no tenant
     */
    public function emailExistsInTenant(string $email, ?int $excludeId = null): bool
    {
        $query = $this->where('email', $email);
        
        if ($excludeId) {
            $query->where('id !=', $excludeId);
        }
        
        return $query->countAllResults() > 0;
    }
    
    /**
     * Verifica se documento já existe no tenant
     */
    public function documentExistsInTenant(string $documentNumber, ?int $excludeId = null): bool
    {
        $query = $this->where('document_number', $documentNumber);
        
        if ($excludeId) {
            $query->where('id !=', $excludeId);
        }
        
        return $query->countAllResults() > 0;
    }
    
    /**
     * Obtém clientes por cidade
     */
    public function getCustomersByCity(): array
    {
        return $this->select('city, COUNT(*) as total')
                   ->where('city IS NOT NULL')
                   ->where('city !=', '')
                   ->where('status', 'active')
                   ->groupBy('city')
                   ->orderBy('total', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém clientes por estado
     */
    public function getCustomersByState(): array
    {
        return $this->select('state, COUNT(*) as total')
                   ->where('state IS NOT NULL')
                   ->where('state !=', '')
                   ->where('status', 'active')
                   ->groupBy('state')
                   ->orderBy('total', 'DESC')
                   ->findAll();
    }
}