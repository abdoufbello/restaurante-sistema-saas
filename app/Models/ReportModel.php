<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Relatórios e Dashboards com Multi-Tenancy
 */
class ReportModel extends BaseMultiTenantModel
{
    protected $table = 'reports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'user_id',
        'name',
        'slug',
        'description',
        'type',
        'category',
        'data_source',
        'query_config',
        'filters',
        'columns',
        'chart_config',
        'layout_config',
        'schedule_config',
        'export_config',
        'permissions',
        'is_public',
        'is_favorite',
        'is_dashboard',
        'is_scheduled',
        'is_cached',
        'cache_duration',
        'last_generated_at',
        'last_cached_at',
        'generation_time',
        'file_path',
        'file_size',
        'file_format',
        'status',
        'priority',
        'tags',
        'metadata',
        'settings',
        'version',
        'parent_id',
        'template_id',
        'shared_token',
        'expires_at',
        'view_count',
        'download_count',
        'error_message',
        'created_by',
        'updated_by'
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
        'user_id' => 'permit_empty|integer',
        'name' => 'required|string|max_length[255]',
        'slug' => 'permit_empty|string|max_length[255]|alpha_dash',
        'type' => 'required|in_list[table,chart,dashboard,kpi,summary,detailed,custom]',
        'category' => 'permit_empty|in_list[sales,orders,customers,inventory,financial,marketing,operations,analytics,performance]',
        'data_source' => 'required|in_list[orders,customers,products,payments,analytics,inventory,reservations,reviews,users,custom]',
        'status' => 'permit_empty|in_list[draft,active,inactive,generating,completed,failed,scheduled]',
        'priority' => 'permit_empty|in_list[low,medium,high,urgent]',
        'file_format' => 'permit_empty|in_list[pdf,excel,csv,json,html]',
        'cache_duration' => 'permit_empty|integer|greater_than_equal_to[0]',
        'view_count' => 'permit_empty|integer|greater_than_equal_to[0]',
        'download_count' => 'permit_empty|integer|greater_than_equal_to[0]',
        'version' => 'permit_empty|decimal|greater_than[0]'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Nome do relatório é obrigatório',
            'max_length' => 'Nome não pode exceder 255 caracteres'
        ],
        'type' => [
            'required' => 'Tipo do relatório é obrigatório',
            'in_list' => 'Tipo de relatório inválido'
        ],
        'data_source' => [
            'required' => 'Fonte de dados é obrigatória',
            'in_list' => 'Fonte de dados inválida'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['generateSlug', 'setDefaults', 'prepareJsonFields'];
    protected $beforeUpdate = ['generateSlug', 'prepareJsonFields'];
    protected $afterFind = ['parseJsonFields'];
    
    // Constants
    const TYPE_TABLE = 'table';
    const TYPE_CHART = 'chart';
    const TYPE_DASHBOARD = 'dashboard';
    const TYPE_KPI = 'kpi';
    const TYPE_SUMMARY = 'summary';
    const TYPE_DETAILED = 'detailed';
    const TYPE_CUSTOM = 'custom';
    
    const CATEGORY_SALES = 'sales';
    const CATEGORY_ORDERS = 'orders';
    const CATEGORY_CUSTOMERS = 'customers';
    const CATEGORY_INVENTORY = 'inventory';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_OPERATIONS = 'operations';
    const CATEGORY_ANALYTICS = 'analytics';
    const CATEGORY_PERFORMANCE = 'performance';
    
    const DATA_SOURCE_ORDERS = 'orders';
    const DATA_SOURCE_CUSTOMERS = 'customers';
    const DATA_SOURCE_PRODUCTS = 'products';
    const DATA_SOURCE_PAYMENTS = 'payments';
    const DATA_SOURCE_ANALYTICS = 'analytics';
    const DATA_SOURCE_INVENTORY = 'inventory';
    const DATA_SOURCE_RESERVATIONS = 'reservations';
    const DATA_SOURCE_REVIEWS = 'reviews';
    const DATA_SOURCE_USERS = 'users';
    const DATA_SOURCE_CUSTOM = 'custom';
    
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_GENERATING = 'generating';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SCHEDULED = 'scheduled';
    
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';
    
    const FORMAT_PDF = 'pdf';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_CSV = 'csv';
    const FORMAT_JSON = 'json';
    const FORMAT_HTML = 'html';
    
    /**
     * Gera slug único para o relatório
     */
    protected function generateSlug(array $data): array
    {
        if (empty($data['data']['slug']) && !empty($data['data']['name'])) {
            $baseSlug = url_title($data['data']['name'], '-', true);
            $slug = $baseSlug;
            $counter = 1;
            
            while ($this->where('slug', $slug)->where('restaurant_id', $data['data']['restaurant_id'])->first()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $data['data']['slug'] = $slug;
        }
        
        return $data;
    }
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = self::STATUS_DRAFT;
        }
        
        if (!isset($data['data']['priority'])) {
            $data['data']['priority'] = self::PRIORITY_MEDIUM;
        }
        
        if (!isset($data['data']['is_public'])) {
            $data['data']['is_public'] = false;
        }
        
        if (!isset($data['data']['is_favorite'])) {
            $data['data']['is_favorite'] = false;
        }
        
        if (!isset($data['data']['is_dashboard'])) {
            $data['data']['is_dashboard'] = false;
        }
        
        if (!isset($data['data']['is_scheduled'])) {
            $data['data']['is_scheduled'] = false;
        }
        
        if (!isset($data['data']['is_cached'])) {
            $data['data']['is_cached'] = false;
        }
        
        if (!isset($data['data']['cache_duration'])) {
            $data['data']['cache_duration'] = 3600; // 1 hora
        }
        
        if (!isset($data['data']['view_count'])) {
            $data['data']['view_count'] = 0;
        }
        
        if (!isset($data['data']['download_count'])) {
            $data['data']['download_count'] = 0;
        }
        
        if (!isset($data['data']['version'])) {
            $data['data']['version'] = 1.0;
        }
        
        if (!isset($data['data']['file_format'])) {
            $data['data']['file_format'] = self::FORMAT_HTML;
        }
        
        // Gerar token compartilhado se for público
        if (!empty($data['data']['is_public']) && empty($data['data']['shared_token'])) {
            $data['data']['shared_token'] = bin2hex(random_bytes(16));
        }
        
        return $data;
    }
    
    /**
     * Prepara campos JSON antes de inserir/atualizar
     */
    protected function prepareJsonFields(array $data): array
    {
        $jsonFields = [
            'query_config', 'filters', 'columns', 'chart_config', 
            'layout_config', 'schedule_config', 'export_config', 
            'permissions', 'tags', 'metadata', 'settings'
        ];
        
        foreach ($jsonFields as $field) {
            if (isset($data['data'][$field]) && is_array($data['data'][$field])) {
                $data['data'][$field] = json_encode($data['data'][$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Analisa campos JSON após buscar
     */
    protected function parseJsonFields(array $data): array
    {
        $jsonFields = [
            'query_config', 'filters', 'columns', 'chart_config', 
            'layout_config', 'schedule_config', 'export_config', 
            'permissions', 'tags', 'metadata', 'settings'
        ];
        
        if (isset($data['data'])) {
            foreach ($jsonFields as $field) {
                if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                    $data['data'][$field] = json_decode($data['data'][$field], true);
                }
            }
        } elseif (is_array($data)) {
            foreach ($data as &$item) {
                if (is_array($item)) {
                    foreach ($jsonFields as $field) {
                        if (isset($item[$field]) && is_string($item[$field])) {
                            $item[$field] = json_decode($item[$field], true);
                        }
                    }
                }
            }
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS DE BUSCA
    // ========================================
    
    /**
     * Busca relatório por slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }
    
    /**
     * Busca relatório por token compartilhado
     */
    public function findBySharedToken(string $token): ?array
    {
        return $this->where('shared_token', $token)
                   ->where('is_public', true)
                   ->where('status', self::STATUS_ACTIVE)
                   ->first();
    }
    
    /**
     * Obtém relatórios por tipo
     */
    public function getByType(string $type): array
    {
        return $this->where('type', $type)
                   ->where('status', self::STATUS_ACTIVE)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém relatórios por categoria
     */
    public function getByCategory(string $category): array
    {
        return $this->where('category', $category)
                   ->where('status', self::STATUS_ACTIVE)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém dashboards
     */
    public function getDashboards(): array
    {
        return $this->where('is_dashboard', true)
                   ->where('status', self::STATUS_ACTIVE)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém relatórios favoritos do usuário
     */
    public function getFavorites(int $userId): array
    {
        return $this->where('user_id', $userId)
                   ->where('is_favorite', true)
                   ->where('status', self::STATUS_ACTIVE)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém relatórios públicos
     */
    public function getPublicReports(): array
    {
        return $this->where('is_public', true)
                   ->where('status', self::STATUS_ACTIVE)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém relatórios agendados
     */
    public function getScheduledReports(): array
    {
        return $this->where('is_scheduled', true)
                   ->where('status', self::STATUS_ACTIVE)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém relatórios por usuário
     */
    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém templates de relatório
     */
    public function getTemplates(): array
    {
        return $this->where('parent_id IS NULL')
                   ->where('template_id IS NULL')
                   ->where('status', self::STATUS_ACTIVE)
                   ->orderBy('category', 'ASC')
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }
    
    // ========================================
    // MÉTODOS DE AÇÃO
    // ========================================
    
    /**
     * Marca/desmarca relatório como favorito
     */
    public function toggleFavorite(int $reportId, int $userId): bool
    {
        $report = $this->find($reportId);
        
        if (!$report || $report['user_id'] != $userId) {
            return false;
        }
        
        return $this->update($reportId, [
            'is_favorite' => !$report['is_favorite']
        ]);
    }
    
    /**
     * Incrementa contador de visualizações
     */
    public function incrementViewCount(int $reportId): bool
    {
        return $this->set('view_count', 'view_count + 1', false)
                   ->where('id', $reportId)
                   ->update();
    }
    
    /**
     * Incrementa contador de downloads
     */
    public function incrementDownloadCount(int $reportId): bool
    {
        return $this->set('download_count', 'download_count + 1', false)
                   ->where('id', $reportId)
                   ->update();
    }
    
    /**
     * Atualiza status do relatório
     */
    public function updateStatus(int $reportId, string $status, string $errorMessage = null): bool
    {
        $data = ['status' => $status];
        
        if ($status === self::STATUS_COMPLETED) {
            $data['last_generated_at'] = date('Y-m-d H:i:s');
        }
        
        if ($errorMessage) {
            $data['error_message'] = $errorMessage;
        }
        
        return $this->update($reportId, $data);
    }
    
    /**
     * Atualiza informações do arquivo gerado
     */
    public function updateFileInfo(int $reportId, string $filePath, int $fileSize, float $generationTime): bool
    {
        return $this->update($reportId, [
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'generation_time' => $generationTime,
            'last_generated_at' => date('Y-m-d H:i:s'),
            'status' => self::STATUS_COMPLETED
        ]);
    }
    
    /**
     * Atualiza cache do relatório
     */
    public function updateCache(int $reportId, array $cacheData): bool
    {
        return $this->update($reportId, [
            'last_cached_at' => date('Y-m-d H:i:s'),
            'is_cached' => true,
            'metadata' => array_merge(
                json_decode($this->find($reportId)['metadata'] ?? '{}', true),
                ['cache_data' => $cacheData]
            )
        ]);
    }
    
    /**
     * Limpa cache do relatório
     */
    public function clearCache(int $reportId): bool
    {
        $report = $this->find($reportId);
        if (!$report) return false;
        
        $metadata = json_decode($report['metadata'] ?? '{}', true);
        unset($metadata['cache_data']);
        
        return $this->update($reportId, [
            'last_cached_at' => null,
            'is_cached' => false,
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Duplica relatório
     */
    public function duplicateReport(int $reportId, string $newName = null): ?int
    {
        $original = $this->find($reportId);
        if (!$original) return null;
        
        // Remove campos que não devem ser duplicados
        unset($original['id'], $original['created_at'], $original['updated_at'], $original['deleted_at']);
        
        // Define novo nome
        if ($newName) {
            $original['name'] = $newName;
        } else {
            $original['name'] = $original['name'] . ' (Cópia)';
        }
        
        // Remove slug para gerar novo
        unset($original['slug']);
        
        // Reset contadores
        $original['view_count'] = 0;
        $original['download_count'] = 0;
        $original['status'] = self::STATUS_DRAFT;
        $original['shared_token'] = null;
        $original['file_path'] = null;
        $original['file_size'] = null;
        $original['last_generated_at'] = null;
        $original['last_cached_at'] = null;
        $original['is_cached'] = false;
        
        return $this->insert($original);
    }
    
    /**
     * Cria relatório a partir de template
     */
    public function createFromTemplate(int $templateId, array $customData = []): ?int
    {
        $template = $this->find($templateId);
        if (!$template) return null;
        
        // Remove campos que não devem ser copiados
        unset($template['id'], $template['created_at'], $template['updated_at'], $template['deleted_at']);
        
        // Aplica dados customizados
        $reportData = array_merge($template, $customData);
        
        // Define como baseado no template
        $reportData['template_id'] = $templateId;
        $reportData['status'] = self::STATUS_DRAFT;
        
        // Remove slug para gerar novo
        unset($reportData['slug']);
        
        // Reset contadores
        $reportData['view_count'] = 0;
        $reportData['download_count'] = 0;
        $reportData['shared_token'] = null;
        $reportData['file_path'] = null;
        $reportData['file_size'] = null;
        $reportData['last_generated_at'] = null;
        $reportData['last_cached_at'] = null;
        $reportData['is_cached'] = false;
        
        return $this->insert($reportData);
    }
    
    // ========================================
    // MÉTODOS DE GERAÇÃO DE RELATÓRIOS
    // ========================================
    
    /**
     * Gera dados do relatório
     */
    public function generateReportData(int $reportId): ?array
    {
        $report = $this->find($reportId);
        if (!$report) return null;
        
        // Atualiza status para gerando
        $this->updateStatus($reportId, self::STATUS_GENERATING);
        
        $startTime = microtime(true);
        
        try {
            // Gera dados baseado na fonte de dados
            $data = $this->generateDataBySource($report);
            
            $generationTime = microtime(true) - $startTime;
            
            // Atualiza status para concluído
            $this->update($reportId, [
                'status' => self::STATUS_COMPLETED,
                'generation_time' => $generationTime,
                'last_generated_at' => date('Y-m-d H:i:s')
            ]);
            
            return $data;
            
        } catch (\Exception $e) {
            // Atualiza status para falha
            $this->updateStatus($reportId, self::STATUS_FAILED, $e->getMessage());
            return null;
        }
    }
    
    /**
     * Gera dados baseado na fonte de dados
     */
    private function generateDataBySource(array $report): array
    {
        $dataSource = $report['data_source'];
        $queryConfig = json_decode($report['query_config'] ?? '{}', true);
        $filters = json_decode($report['filters'] ?? '{}', true);
        
        switch ($dataSource) {
            case self::DATA_SOURCE_ORDERS:
                return $this->generateOrdersData($queryConfig, $filters);
                
            case self::DATA_SOURCE_CUSTOMERS:
                return $this->generateCustomersData($queryConfig, $filters);
                
            case self::DATA_SOURCE_PRODUCTS:
                return $this->generateProductsData($queryConfig, $filters);
                
            case self::DATA_SOURCE_PAYMENTS:
                return $this->generatePaymentsData($queryConfig, $filters);
                
            case self::DATA_SOURCE_ANALYTICS:
                return $this->generateAnalyticsData($queryConfig, $filters);
                
            case self::DATA_SOURCE_INVENTORY:
                return $this->generateInventoryData($queryConfig, $filters);
                
            case self::DATA_SOURCE_RESERVATIONS:
                return $this->generateReservationsData($queryConfig, $filters);
                
            case self::DATA_SOURCE_REVIEWS:
                return $this->generateReviewsData($queryConfig, $filters);
                
            case self::DATA_SOURCE_USERS:
                return $this->generateUsersData($queryConfig, $filters);
                
            case self::DATA_SOURCE_CUSTOM:
                return $this->generateCustomData($queryConfig, $filters);
                
            default:
                throw new \Exception('Fonte de dados não suportada: ' . $dataSource);
        }
    }
    
    /**
     * Gera dados de pedidos
     */
    private function generateOrdersData(array $queryConfig, array $filters): array
    {
        $ordersModel = new \App\Models\OrderModel();
        
        $query = $ordersModel->select([
            'id', 'order_number', 'customer_name', 'total_amount', 
            'status', 'payment_status', 'created_at'
        ]);
        
        // Aplicar filtros
        if (!empty($filters['date_from'])) {
            $query->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at <=', $filters['date_to']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['min_amount'])) {
            $query->where('total_amount >=', $filters['min_amount']);
        }
        
        if (!empty($filters['max_amount'])) {
            $query->where('total_amount <=', $filters['max_amount']);
        }
        
        // Aplicar ordenação
        $orderBy = $queryConfig['order_by'] ?? 'created_at';
        $orderDir = $queryConfig['order_dir'] ?? 'DESC';
        $query->orderBy($orderBy, $orderDir);
        
        // Aplicar limite
        if (!empty($queryConfig['limit'])) {
            $query->limit($queryConfig['limit']);
        }
        
        $orders = $query->findAll();
        
        // Calcular estatísticas
        $stats = [
            'total_orders' => count($orders),
            'total_revenue' => array_sum(array_column($orders, 'total_amount')),
            'avg_order_value' => count($orders) > 0 ? array_sum(array_column($orders, 'total_amount')) / count($orders) : 0
        ];
        
        return [
            'data' => $orders,
            'stats' => $stats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gera dados de clientes
     */
    private function generateCustomersData(array $queryConfig, array $filters): array
    {
        $customersModel = new \App\Models\CustomerModel();
        
        $query = $customersModel->select([
            'id', 'name', 'email', 'phone', 'total_orders', 
            'total_spent', 'last_order_at', 'created_at'
        ]);
        
        // Aplicar filtros
        if (!empty($filters['date_from'])) {
            $query->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at <=', $filters['date_to']);
        }
        
        if (!empty($filters['min_orders'])) {
            $query->where('total_orders >=', $filters['min_orders']);
        }
        
        if (!empty($filters['min_spent'])) {
            $query->where('total_spent >=', $filters['min_spent']);
        }
        
        $customers = $query->findAll();
        
        $stats = [
            'total_customers' => count($customers),
            'total_spent' => array_sum(array_column($customers, 'total_spent')),
            'avg_customer_value' => count($customers) > 0 ? array_sum(array_column($customers, 'total_spent')) / count($customers) : 0
        ];
        
        return [
            'data' => $customers,
            'stats' => $stats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gera dados de produtos
     */
    private function generateProductsData(array $queryConfig, array $filters): array
    {
        $productsModel = new \App\Models\ProductModel();
        
        $query = $productsModel->select([
            'id', 'name', 'category', 'price', 'stock_quantity', 
            'total_sold', 'revenue', 'status', 'created_at'
        ]);
        
        // Aplicar filtros
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['min_price'])) {
            $query->where('price >=', $filters['min_price']);
        }
        
        if (!empty($filters['max_price'])) {
            $query->where('price <=', $filters['max_price']);
        }
        
        $products = $query->findAll();
        
        $stats = [
            'total_products' => count($products),
            'total_revenue' => array_sum(array_column($products, 'revenue')),
            'total_sold' => array_sum(array_column($products, 'total_sold'))
        ];
        
        return [
            'data' => $products,
            'stats' => $stats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gera dados de pagamentos
     */
    private function generatePaymentsData(array $queryConfig, array $filters): array
    {
        $paymentsModel = new \App\Models\PaymentModel();
        
        $query = $paymentsModel->select([
            'id', 'transaction_id', 'amount', 'currency', 'method', 
            'status', 'gateway', 'created_at'
        ]);
        
        // Aplicar filtros
        if (!empty($filters['date_from'])) {
            $query->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at <=', $filters['date_to']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }
        
        if (!empty($filters['gateway'])) {
            $query->where('gateway', $filters['gateway']);
        }
        
        $payments = $query->findAll();
        
        $stats = [
            'total_payments' => count($payments),
            'total_amount' => array_sum(array_column($payments, 'amount')),
            'success_rate' => count($payments) > 0 ? 
                (count(array_filter($payments, fn($p) => $p['status'] === 'completed')) / count($payments)) * 100 : 0
        ];
        
        return [
            'data' => $payments,
            'stats' => $stats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gera dados de analytics
     */
    private function generateAnalyticsData(array $queryConfig, array $filters): array
    {
        $analyticsModel = new \App\Models\AnalyticsModel();
        
        return $analyticsModel->getGeneralStats(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );
    }
    
    /**
     * Gera dados de estoque
     */
    private function generateInventoryData(array $queryConfig, array $filters): array
    {
        // Implementação simplificada
        return [
            'data' => [],
            'stats' => ['message' => 'Dados de estoque não implementados'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gera dados de reservas
     */
    private function generateReservationsData(array $queryConfig, array $filters): array
    {
        // Implementação simplificada
        return [
            'data' => [],
            'stats' => ['message' => 'Dados de reservas não implementados'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gera dados de avaliações
     */
    private function generateReviewsData(array $queryConfig, array $filters): array
    {
        // Implementação simplificada
        return [
            'data' => [],
            'stats' => ['message' => 'Dados de avaliações não implementados'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gera dados de usuários
     */
    private function generateUsersData(array $queryConfig, array $filters): array
    {
        $usersModel = new \App\Models\UserModel();
        
        $query = $usersModel->select([
            'id', 'username', 'email', 'role', 'status', 
            'last_login_at', 'created_at'
        ]);
        
        // Aplicar filtros
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $users = $query->findAll();
        
        $stats = [
            'total_users' => count($users),
            'active_users' => count(array_filter($users, fn($u) => $u['status'] === 'active'))
        ];
        
        return [
            'data' => $users,
            'stats' => $stats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gera dados customizados
     */
    private function generateCustomData(array $queryConfig, array $filters): array
    {
        // Implementação para consultas SQL customizadas
        if (empty($queryConfig['sql'])) {
            throw new \Exception('SQL customizado não fornecido');
        }
        
        $db = \Config\Database::connect();
        $query = $db->query($queryConfig['sql']);
        $data = $query->getResultArray();
        
        return [
            'data' => $data,
            'stats' => ['total_records' => count($data)],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // ========================================
    // MÉTODOS DE BUSCA AVANÇADA
    // ========================================
    
    /**
     * Busca avançada de relatórios
     */
    public function advancedSearch(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = $this->select('*');
        
        if (!empty($filters['search'])) {
            $query->groupStart()
                  ->like('name', $filters['search'])
                  ->orLike('description', $filters['search'])
                  ->orLike('tags', $filters['search'])
                  ->groupEnd();
        }
        
        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $query->whereIn('type', $filters['type']);
            } else {
                $query->where('type', $filters['type']);
            }
        }
        
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        if (!empty($filters['data_source'])) {
            $query->where('data_source', $filters['data_source']);
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }
        
        if (isset($filters['is_dashboard'])) {
            $query->where('is_dashboard', $filters['is_dashboard']);
        }
        
        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', $filters['is_favorite']);
        }
        
        if (isset($filters['is_scheduled'])) {
            $query->where('is_scheduled', $filters['is_scheduled']);
        }
        
        if (!empty($filters['created_from'])) {
            $query->where('created_at >=', $filters['created_from']);
        }
        
        if (!empty($filters['created_to'])) {
            $query->where('created_at <=', $filters['created_to']);
        }
        
        if (!empty($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->like('tags', $tag);
            }
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $query->orderBy($orderBy, $orderDir)
                    ->limit($limit, $offset)
                    ->findAll();
    }
    
    /**
     * Obtém estatísticas de relatórios
     */
    public function getReportStats(): array
    {
        $stats = $this->select([
            'COUNT(*) as total_reports',
            'COUNT(CASE WHEN status = "active" THEN 1 END) as active_reports',
            'COUNT(CASE WHEN status = "draft" THEN 1 END) as draft_reports',
            'COUNT(CASE WHEN is_dashboard = 1 THEN 1 END) as dashboards',
            'COUNT(CASE WHEN is_public = 1 THEN 1 END) as public_reports',
            'COUNT(CASE WHEN is_scheduled = 1 THEN 1 END) as scheduled_reports',
            'SUM(view_count) as total_views',
            'SUM(download_count) as total_downloads',
            'AVG(generation_time) as avg_generation_time'
        ])->first();
        
        // Estatísticas por tipo
        $byType = $this->select([
            'type',
            'COUNT(*) as count'
        ])->groupBy('type')
          ->orderBy('count', 'DESC')
          ->findAll();
        
        // Estatísticas por categoria
        $byCategory = $this->select([
            'category',
            'COUNT(*) as count'
        ])->where('category IS NOT NULL')
          ->groupBy('category')
          ->orderBy('count', 'DESC')
          ->findAll();
        
        // Relatórios mais visualizados
        $mostViewed = $this->select(['name', 'view_count'])
                          ->where('view_count > 0')
                          ->orderBy('view_count', 'DESC')
                          ->limit(10)
                          ->findAll();
        
        return [
            'general' => $stats,
            'by_type' => $byType,
            'by_category' => $byCategory,
            'most_viewed' => $mostViewed
        ];
    }
    
    /**
     * Exporta relatórios para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $reports = $this->advancedSearch($filters, 10000);
        
        $csv = "ID,Nome,Tipo,Categoria,Fonte de Dados,Status,Público,Dashboard,Visualizações,Downloads,Criado em\n";
        
        foreach ($reports as $report) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%d,%d,%s\n",
                $report['id'],
                '"' . str_replace('"', '""', $report['name']) . '"',
                $report['type'],
                $report['category'] ?? '',
                $report['data_source'],
                $report['status'],
                $report['is_public'] ? 'Sim' : 'Não',
                $report['is_dashboard'] ? 'Sim' : 'Não',
                $report['view_count'],
                $report['download_count'],
                $report['created_at']
            );
        }
        
        return $csv;
    }
    
    /**
     * Limpa relatórios antigos
     */
    public function cleanOldReports(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        return $this->where('created_at <', $cutoffDate)
                   ->where('status', self::STATUS_COMPLETED)
                   ->where('is_favorite', false)
                   ->delete();
    }
    
    /**
     * Cria templates padrão de relatórios
     */
    public function createDefaultTemplates(int $restaurantId): array
    {
        $templates = [
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Relatório de Vendas Diário',
                'description' => 'Relatório diário de vendas e pedidos',
                'type' => self::TYPE_SUMMARY,
                'category' => self::CATEGORY_SALES,
                'data_source' => self::DATA_SOURCE_ORDERS,
                'query_config' => json_encode([
                    'date_range' => 'today',
                    'group_by' => 'hour',
                    'metrics' => ['total_orders', 'total_revenue', 'avg_order_value']
                ]),
                'chart_config' => json_encode([
                    'type' => 'line',
                    'x_axis' => 'hour',
                    'y_axis' => 'revenue'
                ]),
                'status' => self::STATUS_ACTIVE
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Top Produtos',
                'description' => 'Produtos mais vendidos do mês',
                'type' => self::TYPE_TABLE,
                'category' => self::CATEGORY_SALES,
                'data_source' => self::DATA_SOURCE_PRODUCTS,
                'query_config' => json_encode([
                    'date_range' => 'this_month',
                    'order_by' => 'total_sold',
                    'order_dir' => 'DESC',
                    'limit' => 20
                ]),
                'status' => self::STATUS_ACTIVE
            ],
            [
                'restaurant_id' => $restaurantId,
                'name' => 'Dashboard Executivo',
                'description' => 'Visão geral das métricas principais',
                'type' => self::TYPE_DASHBOARD,
                'category' => self::CATEGORY_ANALYTICS,
                'data_source' => self::DATA_SOURCE_ANALYTICS,
                'is_dashboard' => true,
                'layout_config' => json_encode([
                    'widgets' => [
                        ['type' => 'kpi', 'metric' => 'total_revenue', 'size' => 'small'],
                        ['type' => 'kpi', 'metric' => 'total_orders', 'size' => 'small'],
                        ['type' => 'chart', 'data_source' => 'orders', 'chart_type' => 'line'],
                        ['type' => 'table', 'data_source' => 'products', 'limit' => 10]
                    ]
                ]),
                'status' => self::STATUS_ACTIVE
            ]
        ];
        
        $createdIds = [];
        foreach ($templates as $template) {
            $id = $this->insert($template);
            if ($id) {
                $createdIds[] = $id;
            }
        }
        
        return $createdIds;
    }
}