<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Analytics e Métricas com Multi-Tenancy
 */
class AnalyticsModel extends BaseMultiTenantModel
{
    protected $table = 'analytics';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'user_id',
        'session_id',
        'event_type',
        'event_category',
        'event_action',
        'event_label',
        'event_value',
        'page_url',
        'page_title',
        'referrer_url',
        'user_agent',
        'ip_address',
        'country',
        'region',
        'city',
        'device_type',
        'device_brand',
        'device_model',
        'browser_name',
        'browser_version',
        'os_name',
        'os_version',
        'screen_resolution',
        'viewport_size',
        'language',
        'timezone',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'custom_dimensions',
        'custom_metrics',
        'conversion_goal',
        'conversion_value',
        'revenue',
        'currency',
        'transaction_id',
        'product_id',
        'product_name',
        'product_category',
        'product_quantity',
        'product_price',
        'funnel_step',
        'funnel_name',
        'ab_test_variant',
        'cohort_group',
        'customer_segment',
        'subscription_plan',
        'feature_flag',
        'error_type',
        'error_message',
        'performance_metric',
        'load_time',
        'api_endpoint',
        'api_method',
        'api_response_time',
        'api_status_code',
        'metadata',
        'tags',
        'processed_at',
        'aggregated_at'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'event_type' => 'required|in_list[pageview,event,transaction,error,performance,api_call,user_action,system_event,custom]',
        'event_category' => 'permit_empty|in_list[engagement,conversion,navigation,interaction,commerce,system,error,performance,marketing,user_behavior]',
        'event_action' => 'required|string|max_length[100]',
        'page_url' => 'permit_empty|valid_url_strict',
        'ip_address' => 'permit_empty|valid_ip',
        'device_type' => 'permit_empty|in_list[desktop,mobile,tablet,tv,watch,other]',
        'currency' => 'permit_empty|exact_length[3]',
        'revenue' => 'permit_empty|decimal',
        'conversion_value' => 'permit_empty|decimal',
        'product_quantity' => 'permit_empty|integer|greater_than_equal_to[0]',
        'product_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'load_time' => 'permit_empty|integer|greater_than_equal_to[0]',
        'api_response_time' => 'permit_empty|integer|greater_than_equal_to[0]',
        'api_status_code' => 'permit_empty|integer|greater_than_equal_to[100]|less_than_equal_to[599]'
    ];
    
    protected $validationMessages = [
        'event_type' => [
            'required' => 'Tipo de evento é obrigatório',
            'in_list' => 'Tipo de evento inválido'
        ],
        'event_action' => [
            'required' => 'Ação do evento é obrigatória',
            'max_length' => 'Ação do evento não pode exceder 100 caracteres'
        ],
        'page_url' => [
            'valid_url_strict' => 'URL da página deve ser válida'
        ],
        'ip_address' => [
            'valid_ip' => 'Endereço IP deve ser válido'
        ],
        'currency' => [
            'exact_length' => 'Código da moeda deve ter exatamente 3 caracteres'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'processGeoLocation', 'parseUserAgent', 'prepareJsonFields'];
    protected $beforeUpdate = ['prepareJsonFields'];
    protected $afterFind = ['parseJsonFields'];
    
    // Constants
    const EVENT_PAGEVIEW = 'pageview';
    const EVENT_EVENT = 'event';
    const EVENT_TRANSACTION = 'transaction';
    const EVENT_ERROR = 'error';
    const EVENT_PERFORMANCE = 'performance';
    const EVENT_API_CALL = 'api_call';
    const EVENT_USER_ACTION = 'user_action';
    const EVENT_SYSTEM_EVENT = 'system_event';
    const EVENT_CUSTOM = 'custom';
    
    const CATEGORY_ENGAGEMENT = 'engagement';
    const CATEGORY_CONVERSION = 'conversion';
    const CATEGORY_NAVIGATION = 'navigation';
    const CATEGORY_INTERACTION = 'interaction';
    const CATEGORY_COMMERCE = 'commerce';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_ERROR = 'error';
    const CATEGORY_PERFORMANCE = 'performance';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_USER_BEHAVIOR = 'user_behavior';
    
    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_MOBILE = 'mobile';
    const DEVICE_TABLET = 'tablet';
    const DEVICE_TV = 'tv';
    const DEVICE_WATCH = 'watch';
    const DEVICE_OTHER = 'other';
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['event_category'])) {
            $data['data']['event_category'] = self::CATEGORY_USER_BEHAVIOR;
        }
        
        if (!isset($data['data']['currency'])) {
            $data['data']['currency'] = 'BRL';
        }
        
        if (!isset($data['data']['session_id'])) {
            $data['data']['session_id'] = session_id() ?: uniqid('sess_', true);
        }
        
        if (!isset($data['data']['timezone'])) {
            $data['data']['timezone'] = date_default_timezone_get();
        }
        
        if (!isset($data['data']['language'])) {
            $data['data']['language'] = 'pt-BR';
        }
        
        return $data;
    }
    
    /**
     * Processa geolocalização baseada no IP
     */
    protected function processGeoLocation(array $data): array
    {
        if (isset($data['data']['ip_address']) && !empty($data['data']['ip_address'])) {
            // Simulação de geolocalização - em produção usar serviço real como MaxMind
            $ip = $data['data']['ip_address'];
            
            // Detectar se é IP local/privado
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                // IP público - fazer lookup real
                $geoData = $this->getGeoLocationData($ip);
                $data['data']['country'] = $geoData['country'] ?? 'BR';
                $data['data']['region'] = $geoData['region'] ?? 'SP';
                $data['data']['city'] = $geoData['city'] ?? 'São Paulo';
            } else {
                // IP privado/local - valores padrão
                $data['data']['country'] = 'BR';
                $data['data']['region'] = 'SP';
                $data['data']['city'] = 'São Paulo';
            }
        }
        
        return $data;
    }
    
    /**
     * Analisa User Agent para extrair informações do dispositivo
     */
    protected function parseUserAgent(array $data): array
    {
        if (isset($data['data']['user_agent']) && !empty($data['data']['user_agent'])) {
            $userAgent = $data['data']['user_agent'];
            
            // Detectar tipo de dispositivo
            if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
                if (preg_match('/iPad/', $userAgent)) {
                    $data['data']['device_type'] = self::DEVICE_TABLET;
                } else {
                    $data['data']['device_type'] = self::DEVICE_MOBILE;
                }
            } else {
                $data['data']['device_type'] = self::DEVICE_DESKTOP;
            }
            
            // Detectar navegador
            if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
                $data['data']['browser_name'] = 'Chrome';
                $data['data']['browser_version'] = $matches[1];
            } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
                $data['data']['browser_name'] = 'Firefox';
                $data['data']['browser_version'] = $matches[1];
            } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
                $data['data']['browser_name'] = 'Safari';
                $data['data']['browser_version'] = $matches[1];
            } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent, $matches)) {
                $data['data']['browser_name'] = 'Edge';
                $data['data']['browser_version'] = $matches[1];
            }
            
            // Detectar sistema operacional
            if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
                $data['data']['os_name'] = 'Windows';
                $data['data']['os_version'] = $matches[1];
            } elseif (preg_match('/Mac OS X ([0-9_.]+)/', $userAgent, $matches)) {
                $data['data']['os_name'] = 'macOS';
                $data['data']['os_version'] = str_replace('_', '.', $matches[1]);
            } elseif (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
                $data['data']['os_name'] = 'Android';
                $data['data']['os_version'] = $matches[1];
            } elseif (preg_match('/iPhone OS ([0-9_]+)/', $userAgent, $matches)) {
                $data['data']['os_name'] = 'iOS';
                $data['data']['os_version'] = str_replace('_', '.', $matches[1]);
            }
        }
        
        return $data;
    }
    
    /**
     * Prepara campos JSON antes de inserir/atualizar
     */
    protected function prepareJsonFields(array $data): array
    {
        $jsonFields = ['custom_dimensions', 'custom_metrics', 'metadata', 'tags'];
        
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
        $jsonFields = ['custom_dimensions', 'custom_metrics', 'metadata', 'tags'];
        
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
    
    /**
     * Obtém dados de geolocalização (simulado)
     */
    private function getGeoLocationData(string $ip): array
    {
        // Em produção, usar serviço real como MaxMind GeoIP2
        // Por enquanto, retorna dados simulados
        return [
            'country' => 'BR',
            'region' => 'SP',
            'city' => 'São Paulo'
        ];
    }
    
    // ========================================
    // MÉTODOS DE TRACKING
    // ========================================
    
    /**
     * Registra pageview
     */
    public function trackPageview(string $pageUrl, string $pageTitle, array $additionalData = []): ?int
    {
        $data = array_merge([
            'event_type' => self::EVENT_PAGEVIEW,
            'event_category' => self::CATEGORY_NAVIGATION,
            'event_action' => 'pageview',
            'page_url' => $pageUrl,
            'page_title' => $pageTitle
        ], $additionalData);
        
        return $this->insert($data);
    }
    
    /**
     * Registra evento personalizado
     */
    public function trackEvent(string $category, string $action, string $label = null, $value = null, array $additionalData = []): ?int
    {
        $data = array_merge([
            'event_type' => self::EVENT_EVENT,
            'event_category' => $category,
            'event_action' => $action,
            'event_label' => $label,
            'event_value' => $value
        ], $additionalData);
        
        return $this->insert($data);
    }
    
    /**
     * Registra transação/conversão
     */
    public function trackTransaction(string $transactionId, float $revenue, string $currency = 'BRL', array $products = [], array $additionalData = []): ?int
    {
        $data = array_merge([
            'event_type' => self::EVENT_TRANSACTION,
            'event_category' => self::CATEGORY_COMMERCE,
            'event_action' => 'purchase',
            'transaction_id' => $transactionId,
            'revenue' => $revenue,
            'currency' => $currency,
            'conversion_value' => $revenue
        ], $additionalData);
        
        $analyticsId = $this->insert($data);
        
        // Registrar produtos da transação
        if ($analyticsId && !empty($products)) {
            foreach ($products as $product) {
                $productData = array_merge($data, [
                    'product_id' => $product['id'] ?? null,
                    'product_name' => $product['name'] ?? null,
                    'product_category' => $product['category'] ?? null,
                    'product_quantity' => $product['quantity'] ?? 1,
                    'product_price' => $product['price'] ?? 0
                ]);
                
                $this->insert($productData);
            }
        }
        
        return $analyticsId;
    }
    
    /**
     * Registra erro
     */
    public function trackError(string $errorType, string $errorMessage, array $additionalData = []): ?int
    {
        $data = array_merge([
            'event_type' => self::EVENT_ERROR,
            'event_category' => self::CATEGORY_ERROR,
            'event_action' => 'error_occurred',
            'error_type' => $errorType,
            'error_message' => $errorMessage
        ], $additionalData);
        
        return $this->insert($data);
    }
    
    /**
     * Registra métrica de performance
     */
    public function trackPerformance(string $metric, int $value, array $additionalData = []): ?int
    {
        $data = array_merge([
            'event_type' => self::EVENT_PERFORMANCE,
            'event_category' => self::CATEGORY_PERFORMANCE,
            'event_action' => 'performance_metric',
            'performance_metric' => $metric,
            'event_value' => $value,
            'load_time' => $metric === 'page_load_time' ? $value : null
        ], $additionalData);
        
        return $this->insert($data);
    }
    
    /**
     * Registra chamada de API
     */
    public function trackApiCall(string $endpoint, string $method, int $responseTime, int $statusCode, array $additionalData = []): ?int
    {
        $data = array_merge([
            'event_type' => self::EVENT_API_CALL,
            'event_category' => self::CATEGORY_SYSTEM,
            'event_action' => 'api_call',
            'api_endpoint' => $endpoint,
            'api_method' => $method,
            'api_response_time' => $responseTime,
            'api_status_code' => $statusCode
        ], $additionalData);
        
        return $this->insert($data);
    }
    
    // ========================================
    // MÉTODOS DE RELATÓRIOS
    // ========================================
    
    /**
     * Obtém estatísticas gerais
     */
    public function getGeneralStats(string $startDate = null, string $endDate = null): array
    {
        $query = $this->select([
            'COUNT(*) as total_events',
            'COUNT(DISTINCT session_id) as unique_sessions',
            'COUNT(DISTINCT user_id) as unique_users',
            'COUNT(CASE WHEN event_type = "pageview" THEN 1 END) as pageviews',
            'COUNT(CASE WHEN event_type = "transaction" THEN 1 END) as transactions',
            'SUM(CASE WHEN event_type = "transaction" THEN revenue ELSE 0 END) as total_revenue',
            'COUNT(CASE WHEN event_type = "error" THEN 1 END) as errors',
            'AVG(CASE WHEN load_time IS NOT NULL THEN load_time END) as avg_load_time'
        ]);
        
        if ($startDate) {
            $query->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at <=', $endDate);
        }
        
        $stats = $query->first();
        
        // Calcular taxa de conversão
        $stats['conversion_rate'] = $stats['unique_sessions'] > 0 
            ? round(($stats['transactions'] / $stats['unique_sessions']) * 100, 2) 
            : 0;
        
        // Calcular receita média por transação
        $stats['avg_transaction_value'] = $stats['transactions'] > 0 
            ? round($stats['total_revenue'] / $stats['transactions'], 2) 
            : 0;
        
        return $stats;
    }
    
    /**
     * Obtém estatísticas por período
     */
    public function getStatsByPeriod(string $period = 'day', string $startDate = null, string $endDate = null): array
    {
        $dateFormat = match($period) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };
        
        $query = $this->select([
            "DATE_FORMAT(created_at, '{$dateFormat}') as period",
            'COUNT(*) as total_events',
            'COUNT(DISTINCT session_id) as unique_sessions',
            'COUNT(DISTINCT user_id) as unique_users',
            'COUNT(CASE WHEN event_type = "pageview" THEN 1 END) as pageviews',
            'COUNT(CASE WHEN event_type = "transaction" THEN 1 END) as transactions',
            'SUM(CASE WHEN event_type = "transaction" THEN revenue ELSE 0 END) as revenue'
        ])->groupBy('period')
          ->orderBy('period', 'ASC');
        
        if ($startDate) {
            $query->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at <=', $endDate);
        }
        
        return $query->findAll();
    }
    
    /**
     * Obtém páginas mais visitadas
     */
    public function getTopPages(int $limit = 10, string $startDate = null, string $endDate = null): array
    {
        $query = $this->select([
            'page_url',
            'page_title',
            'COUNT(*) as pageviews',
            'COUNT(DISTINCT session_id) as unique_pageviews',
            'AVG(load_time) as avg_load_time'
        ])->where('event_type', self::EVENT_PAGEVIEW)
          ->where('page_url IS NOT NULL')
          ->groupBy(['page_url', 'page_title'])
          ->orderBy('pageviews', 'DESC')
          ->limit($limit);
        
        if ($startDate) {
            $query->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at <=', $endDate);
        }
        
        return $query->findAll();
    }
    
    /**
     * Obtém eventos mais frequentes
     */
    public function getTopEvents(int $limit = 10, string $startDate = null, string $endDate = null): array
    {
        $query = $this->select([
            'event_category',
            'event_action',
            'event_label',
            'COUNT(*) as event_count',
            'COUNT(DISTINCT session_id) as unique_events',
            'AVG(event_value) as avg_value'
        ])->where('event_type', self::EVENT_EVENT)
          ->groupBy(['event_category', 'event_action', 'event_label'])
          ->orderBy('event_count', 'DESC')
          ->limit($limit);
        
        if ($startDate) {
            $query->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at <=', $endDate);
        }
        
        return $query->findAll();
    }
    
    /**
     * Obtém estatísticas de dispositivos
     */
    public function getDeviceStats(string $startDate = null, string $endDate = null): array
    {
        $query = $this->select([
            'device_type',
            'browser_name',
            'os_name',
            'COUNT(*) as sessions',
            'COUNT(DISTINCT user_id) as users'
        ])->where('device_type IS NOT NULL')
          ->groupBy(['device_type', 'browser_name', 'os_name'])
          ->orderBy('sessions', 'DESC');
        
        if ($startDate) {
            $query->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at <=', $endDate);
        }
        
        return $query->findAll();
    }
    
    /**
     * Obtém estatísticas geográficas
     */
    public function getGeoStats(string $startDate = null, string $endDate = null): array
    {
        $query = $this->select([
            'country',
            'region',
            'city',
            'COUNT(*) as sessions',
            'COUNT(DISTINCT user_id) as users',
            'SUM(CASE WHEN event_type = "transaction" THEN revenue ELSE 0 END) as revenue'
        ])->where('country IS NOT NULL')
          ->groupBy(['country', 'region', 'city'])
          ->orderBy('sessions', 'DESC');
        
        if ($startDate) {
            $query->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at <=', $endDate);
        }
        
        return $query->findAll();
    }
    
    /**
     * Obtém funil de conversão
     */
    public function getConversionFunnel(array $steps, string $startDate = null, string $endDate = null): array
    {
        $funnel = [];
        
        foreach ($steps as $index => $step) {
            $query = $this->select('COUNT(DISTINCT session_id) as users')
                         ->where('funnel_step', $step['step'])
                         ->where('funnel_name', $step['name'] ?? 'default');
            
            if ($startDate) {
                $query->where('created_at >=', $startDate);
            }
            
            if ($endDate) {
                $query->where('created_at <=', $endDate);
            }
            
            $result = $query->first();
            
            $funnel[] = [
                'step' => $step['step'],
                'name' => $step['name'] ?? 'Step ' . ($index + 1),
                'users' => (int) $result['users'],
                'conversion_rate' => $index === 0 ? 100 : 
                    ($funnel[0]['users'] > 0 ? round(($result['users'] / $funnel[0]['users']) * 100, 2) : 0)
            ];
        }
        
        return $funnel;
    }
    
    /**
     * Obtém análise de coorte
     */
    public function getCohortAnalysis(string $startDate, string $endDate, string $period = 'week'): array
    {
        // Implementação simplificada de análise de coorte
        $cohorts = [];
        
        $query = $this->select([
            'cohort_group',
            "DATE_FORMAT(created_at, '%Y-%m-%d') as activity_date",
            'COUNT(DISTINCT user_id) as active_users'
        ])->where('cohort_group IS NOT NULL')
          ->where('created_at >=', $startDate)
          ->where('created_at <=', $endDate)
          ->groupBy(['cohort_group', 'activity_date'])
          ->orderBy('cohort_group')
          ->orderBy('activity_date');
        
        $results = $query->findAll();
        
        foreach ($results as $result) {
            $cohorts[$result['cohort_group']][$result['activity_date']] = $result['active_users'];
        }
        
        return $cohorts;
    }
    
    /**
     * Obtém relatório de receita
     */
    public function getRevenueReport(string $startDate = null, string $endDate = null): array
    {
        $query = $this->select([
            'COUNT(CASE WHEN event_type = "transaction" THEN 1 END) as total_transactions',
            'SUM(CASE WHEN event_type = "transaction" THEN revenue ELSE 0 END) as total_revenue',
            'AVG(CASE WHEN event_type = "transaction" THEN revenue END) as avg_transaction_value',
            'MIN(CASE WHEN event_type = "transaction" THEN revenue END) as min_transaction_value',
            'MAX(CASE WHEN event_type = "transaction" THEN revenue END) as max_transaction_value',
            'COUNT(DISTINCT CASE WHEN event_type = "transaction" THEN user_id END) as paying_customers'
        ]);
        
        if ($startDate) {
            $query->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at <=', $endDate);
        }
        
        $report = $query->first();
        
        // Obter receita por período
        $revenueByPeriod = $this->select([
            "DATE_FORMAT(created_at, '%Y-%m-%d') as date",
            'SUM(revenue) as daily_revenue',
            'COUNT(*) as daily_transactions'
        ])->where('event_type', self::EVENT_TRANSACTION);
        
        if ($startDate) {
            $revenueByPeriod->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $revenueByPeriod->where('created_at <=', $endDate);
        }
        
        $report['revenue_by_date'] = $revenueByPeriod->groupBy('date')
                                                    ->orderBy('date')
                                                    ->findAll();
        
        return $report;
    }
    
    /**
     * Obtém relatório de performance
     */
    public function getPerformanceReport(string $startDate = null, string $endDate = null): array
    {
        $query = $this->select([
            'AVG(load_time) as avg_load_time',
            'MIN(load_time) as min_load_time',
            'MAX(load_time) as max_load_time',
            'AVG(api_response_time) as avg_api_response_time',
            'COUNT(CASE WHEN event_type = "error" THEN 1 END) as total_errors',
            'COUNT(CASE WHEN api_status_code >= 400 THEN 1 END) as api_errors'
        ]);
        
        if ($startDate) {
            $query->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at <=', $endDate);
        }
        
        $report = $query->first();
        
        // Obter erros mais frequentes
        $topErrors = $this->select([
            'error_type',
            'error_message',
            'COUNT(*) as error_count'
        ])->where('event_type', self::EVENT_ERROR)
          ->groupBy(['error_type', 'error_message'])
          ->orderBy('error_count', 'DESC')
          ->limit(10);
        
        if ($startDate) {
            $topErrors->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $topErrors->where('created_at <=', $endDate);
        }
        
        $report['top_errors'] = $topErrors->findAll();
        
        return $report;
    }
    
    /**
     * Busca avançada de eventos
     */
    public function advancedSearch(array $filters = [], int $limit = 1000, int $offset = 0): array
    {
        $query = $this->select('*');
        
        if (!empty($filters['event_type'])) {
            if (is_array($filters['event_type'])) {
                $query->whereIn('event_type', $filters['event_type']);
            } else {
                $query->where('event_type', $filters['event_type']);
            }
        }
        
        if (!empty($filters['event_category'])) {
            $query->where('event_category', $filters['event_category']);
        }
        
        if (!empty($filters['event_action'])) {
            $query->like('event_action', $filters['event_action']);
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }
        
        if (!empty($filters['device_type'])) {
            $query->where('device_type', $filters['device_type']);
        }
        
        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at <=', $filters['date_to']);
        }
        
        if (!empty($filters['has_revenue'])) {
            $query->where('revenue >', 0);
        }
        
        if (!empty($filters['min_load_time'])) {
            $query->where('load_time >=', $filters['min_load_time']);
        }
        
        if (!empty($filters['max_load_time'])) {
            $query->where('load_time <=', $filters['max_load_time']);
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $query->orderBy($orderBy, $orderDir)
                    ->limit($limit, $offset)
                    ->findAll();
    }
    
    /**
     * Exporta dados para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $events = $this->advancedSearch($filters, 10000);
        
        $csv = "Data,Tipo,Categoria,Ação,Usuário,Sessão,Página,Dispositivo,País,Receita,Tempo de Carregamento\n";
        
        foreach ($events as $event) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $event['created_at'],
                $event['event_type'],
                $event['event_category'] ?? '',
                $event['event_action'],
                $event['user_id'] ?? '',
                $event['session_id'] ?? '',
                $event['page_url'] ?? '',
                $event['device_type'] ?? '',
                $event['country'] ?? '',
                $event['revenue'] ?? '0',
                $event['load_time'] ?? ''
            );
        }
        
        return $csv;
    }
    
    /**
     * Limpa dados antigos de analytics
     */
    public function cleanOldData(int $daysToKeep = 365): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        return $this->where('created_at <', $cutoffDate)
                   ->where('processed_at IS NOT NULL')
                   ->delete();
    }
    
    /**
     * Marca eventos como processados
     */
    public function markAsProcessed(array $eventIds): bool
    {
        return $this->whereIn('id', $eventIds)
                   ->set('processed_at', date('Y-m-d H:i:s'))
                   ->update();
    }
    
    /**
     * Obtém eventos não processados
     */
    public function getUnprocessedEvents(int $limit = 1000): array
    {
        return $this->where('processed_at IS NULL')
                   ->orderBy('created_at', 'ASC')
                   ->limit($limit)
                   ->findAll();
    }
}