<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

class AnalyticsController extends BaseApiController
{
    protected $requiredPermissions = [
        'dashboard' => 'analytics.read',
        'events' => 'analytics.read',
        'revenue' => 'analytics.read',
        'customers' => 'analytics.read',
        'products' => 'analytics.read',
        'performance' => 'analytics.read',
        'funnel' => 'analytics.read',
        'cohort' => 'analytics.read',
        'export' => 'analytics.export'
    ];

    /**
     * Dashboard principal com métricas resumidas
     */
    public function dashboard()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('analytics.read');
            
            $period = $this->request->getGet('period') ?? 'month';
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            
            // Define período baseado no parâmetro
            $dates = $this->getPeriodDates($period, $dateFrom, $dateTo);
            $previousDates = $this->getPreviousPeriodDates($dates);
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            // Métricas principais
            $metrics = $this->getDashboardMetrics($restaurantId, $dates, $previousDates);
            
            // Gráfico de receita
            $revenueChart = $this->getRevenueChart($restaurantId, $dates);
            
            // Top produtos
            $topProducts = $this->getTopProducts($restaurantId, $dates, 10);
            
            // Distribuição de pedidos por status
            $ordersByStatus = $this->getOrdersByStatus($restaurantId, $dates);
            
            // Métricas de clientes
            $customerMetrics = $this->getCustomerMetrics($restaurantId, $dates, $previousDates);
            
            $data = [
                'period' => $period,
                'date_range' => $dates,
                'metrics' => $metrics,
                'revenue_chart' => $revenueChart,
                'top_products' => $topProducts,
                'orders_by_status' => $ordersByStatus,
                'customer_metrics' => $customerMetrics
            ];
            
            // Cache por 15 minutos
            $cacheKey = "analytics_dashboard_{$restaurantId}_{$period}_" . md5(serialize($dates));
            $this->saveCache($cacheKey, $data, 900);
            
            return $this->successResponse($data, 'Dashboard carregado com sucesso');
            
        } catch (\Exception $e) {
            $this->logActivity('analytics_dashboard_error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Erro ao carregar dashboard: ' . $e->getMessage());
        }
    }
    
    /**
     * Registra eventos de analytics
     */
    public function events()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('analytics.read');
            
            if ($this->request->getMethod() === 'POST') {
                return $this->createEvent();
            }
            
            return $this->getEvents();
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao processar eventos: ' . $e->getMessage());
        }
    }
    
    /**
     * Análise detalhada de receita
     */
    public function revenue()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('analytics.read');
            
            $period = $this->request->getGet('period') ?? 'month';
            $groupBy = $this->request->getGet('group_by') ?? 'day';
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            
            $dates = $this->getPeriodDates($period, $dateFrom, $dateTo);
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            // Receita por período
            $revenueData = $this->getDetailedRevenue($restaurantId, $dates, $groupBy);
            
            // Receita por categoria
            $revenueByCategory = $this->getRevenueByCategory($restaurantId, $dates);
            
            // Receita por método de pagamento
            $revenueByPayment = $this->getRevenueByPaymentMethod($restaurantId, $dates);
            
            // Análise de margem
            $marginAnalysis = $this->getMarginAnalysis($restaurantId, $dates);
            
            $data = [
                'period' => $period,
                'group_by' => $groupBy,
                'date_range' => $dates,
                'revenue_timeline' => $revenueData,
                'revenue_by_category' => $revenueByCategory,
                'revenue_by_payment' => $revenueByPayment,
                'margin_analysis' => $marginAnalysis
            ];
            
            return $this->successResponse($data, 'Análise de receita carregada');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao analisar receita: ' . $e->getMessage());
        }
    }
    
    /**
     * Análise de clientes
     */
    public function customers()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('analytics.read');
            
            $period = $this->request->getGet('period') ?? 'month';
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            
            $dates = $this->getPeriodDates($period, $dateFrom, $dateTo);
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            // Métricas de clientes
            $customerStats = $this->getCustomerAnalytics($restaurantId, $dates);
            
            // Segmentação de clientes
            $customerSegments = $this->getCustomerSegments($restaurantId, $dates);
            
            // Top clientes
            $topCustomers = $this->getTopCustomers($restaurantId, $dates, 20);
            
            // Análise de retenção
            $retentionAnalysis = $this->getCustomerRetention($restaurantId, $dates);
            
            $data = [
                'period' => $period,
                'date_range' => $dates,
                'customer_stats' => $customerStats,
                'customer_segments' => $customerSegments,
                'top_customers' => $topCustomers,
                'retention_analysis' => $retentionAnalysis
            ];
            
            return $this->successResponse($data, 'Análise de clientes carregada');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao analisar clientes: ' . $e->getMessage());
        }
    }
    
    /**
     * Análise de produtos
     */
    public function products()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('analytics.read');
            
            $period = $this->request->getGet('period') ?? 'month';
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            
            $dates = $this->getPeriodDates($period, $dateFrom, $dateTo);
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            // Performance de produtos
            $productPerformance = $this->getProductPerformance($restaurantId, $dates);
            
            // Produtos mais vendidos
            $bestSellers = $this->getTopProducts($restaurantId, $dates, 20);
            
            // Produtos com baixa performance
            $underPerformers = $this->getUnderPerformingProducts($restaurantId, $dates, 10);
            
            // Análise de categorias
            $categoryAnalysis = $this->getCategoryAnalysis($restaurantId, $dates);
            
            // Análise de estoque
            $stockAnalysis = $this->getStockAnalysis($restaurantId);
            
            $data = [
                'period' => $period,
                'date_range' => $dates,
                'product_performance' => $productPerformance,
                'best_sellers' => $bestSellers,
                'under_performers' => $underPerformers,
                'category_analysis' => $categoryAnalysis,
                'stock_analysis' => $stockAnalysis
            ];
            
            return $this->successResponse($data, 'Análise de produtos carregada');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao analisar produtos: ' . $e->getMessage());
        }
    }
    
    /**
     * Análise de performance operacional
     */
    public function performance()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('analytics.read');
            
            $period = $this->request->getGet('period') ?? 'month';
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            
            $dates = $this->getPeriodDates($period, $dateFrom, $dateTo);
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            // Métricas de performance
            $performanceMetrics = $this->getPerformanceMetrics($restaurantId, $dates);
            
            // Tempo médio de preparo
            $preparationTimes = $this->getPreparationTimes($restaurantId, $dates);
            
            // Taxa de cancelamento
            $cancellationRate = $this->getCancellationAnalysis($restaurantId, $dates);
            
            // Eficiência por período do dia
            $hourlyEfficiency = $this->getHourlyEfficiency($restaurantId, $dates);
            
            // Análise de picos de demanda
            $demandAnalysis = $this->getDemandAnalysis($restaurantId, $dates);
            
            $data = [
                'period' => $period,
                'date_range' => $dates,
                'performance_metrics' => $performanceMetrics,
                'preparation_times' => $preparationTimes,
                'cancellation_analysis' => $cancellationRate,
                'hourly_efficiency' => $hourlyEfficiency,
                'demand_analysis' => $demandAnalysis
            ];
            
            return $this->successResponse($data, 'Análise de performance carregada');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao analisar performance: ' . $e->getMessage());
        }
    }
    
    /**
     * Exporta dados de analytics
     */
    public function export()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('analytics.export');
            
            $type = $this->request->getGet('type') ?? 'dashboard';
            $format = $this->request->getGet('format') ?? 'json';
            $period = $this->request->getGet('period') ?? 'month';
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            
            $dates = $this->getPeriodDates($period, $dateFrom, $dateTo);
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            // Coleta dados baseado no tipo
            $data = $this->getExportData($type, $restaurantId, $dates);
            
            // Formata dados para exportação
            $exportData = $this->formatExportData($data, $format);
            
            $filename = "analytics_{$type}_{$period}_" . date('Y-m-d_H-i-s');
            
            $this->logActivity('analytics_export', [
                'type' => $type,
                'format' => $format,
                'period' => $period,
                'filename' => $filename
            ]);
            
            return $this->downloadResponse($exportData, $filename, $format);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao exportar dados: ' . $e->getMessage());
        }
    }
    
    // Métodos auxiliares privados
    
    private function getPeriodDates($period, $dateFrom = null, $dateTo = null)
    {
        if ($dateFrom && $dateTo) {
            return [
                'from' => $dateFrom,
                'to' => $dateTo
            ];
        }
        
        $now = new \DateTime();
        
        switch ($period) {
            case 'today':
                return [
                    'from' => $now->format('Y-m-d'),
                    'to' => $now->format('Y-m-d')
                ];
            case 'week':
                $start = clone $now;
                $start->modify('monday this week');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $now->format('Y-m-d')
                ];
            case 'month':
                return [
                    'from' => $now->format('Y-m-01'),
                    'to' => $now->format('Y-m-d')
                ];
            case 'quarter':
                $quarter = ceil($now->format('n') / 3);
                $start = new \DateTime($now->format('Y') . '-' . (($quarter - 1) * 3 + 1) . '-01');
                return [
                    'from' => $start->format('Y-m-d'),
                    'to' => $now->format('Y-m-d')
                ];
            case 'year':
                return [
                    'from' => $now->format('Y-01-01'),
                    'to' => $now->format('Y-m-d')
                ];
            default:
                return [
                    'from' => $now->format('Y-m-01'),
                    'to' => $now->format('Y-m-d')
                ];
        }
    }
    
    private function getPreviousPeriodDates($dates)
    {
        $from = new \DateTime($dates['from']);
        $to = new \DateTime($dates['to']);
        $diff = $from->diff($to)->days + 1;
        
        $previousTo = clone $from;
        $previousTo->modify('-1 day');
        
        $previousFrom = clone $previousTo;
        $previousFrom->modify("-{$diff} days");
        
        return [
            'from' => $previousFrom->format('Y-m-d'),
            'to' => $previousTo->format('Y-m-d')
        ];
    }
    
    private function getDashboardMetrics($restaurantId, $dates, $previousDates)
    {
        $db = \Config\Database::connect();
        
        // Métricas atuais
        $currentMetrics = $db->query("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(AVG(total_amount), 0) as avg_order_value,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM orders 
            WHERE restaurant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            AND status != 'cancelled'
        ", [$restaurantId, $dates['from'], $dates['to']])->getRowArray();
        
        // Métricas do período anterior
        $previousMetrics = $db->query("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(AVG(total_amount), 0) as avg_order_value,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM orders 
            WHERE restaurant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            AND status != 'cancelled'
        ", [$restaurantId, $previousDates['from'], $previousDates['to']])->getRowArray();
        
        // Calcula crescimento
        $metrics = [
            'total_revenue' => (float) $currentMetrics['total_revenue'],
            'total_orders' => (int) $currentMetrics['total_orders'],
            'avg_order_value' => (float) $currentMetrics['avg_order_value'],
            'unique_customers' => (int) $currentMetrics['unique_customers'],
            'revenue_growth' => $this->calculateGrowth($currentMetrics['total_revenue'], $previousMetrics['total_revenue']),
            'orders_growth' => $this->calculateGrowth($currentMetrics['total_orders'], $previousMetrics['total_orders']),
            'avg_order_growth' => $this->calculateGrowth($currentMetrics['avg_order_value'], $previousMetrics['avg_order_value']),
            'customers_growth' => $this->calculateGrowth($currentMetrics['unique_customers'], $previousMetrics['unique_customers'])
        ];
        
        return $metrics;
    }
    
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
    
    private function getRevenueChart($restaurantId, $dates)
    {
        $db = \Config\Database::connect();
        
        $result = $db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders,
                COALESCE(SUM(total_amount), 0) as revenue
            FROM orders 
            WHERE restaurant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            AND status != 'cancelled'
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$restaurantId, $dates['from'], $dates['to']])->getResultArray();
        
        return array_map(function($row) {
            return [
                'date' => $row['date'],
                'orders' => (int) $row['orders'],
                'revenue' => (float) $row['revenue']
            ];
        }, $result);
    }
    
    private function getTopProducts($restaurantId, $dates, $limit = 10)
    {
        $db = \Config\Database::connect();
        
        $result = $db->query("
            SELECT 
                p.name as product_name,
                p.id as product_id,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.total_price) as revenue,
                COUNT(DISTINCT o.id) as orders_count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.restaurant_id = ? 
            AND DATE(o.created_at) BETWEEN ? AND ?
            AND o.status != 'cancelled'
            GROUP BY p.id, p.name
            ORDER BY revenue DESC
            LIMIT ?
        ", [$restaurantId, $dates['from'], $dates['to'], $limit])->getResultArray();
        
        return array_map(function($row) {
            return [
                'product_id' => (int) $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity_sold' => (int) $row['quantity_sold'],
                'revenue' => (float) $row['revenue'],
                'orders_count' => (int) $row['orders_count']
            ];
        }, $result);
    }
    
    private function getOrdersByStatus($restaurantId, $dates)
    {
        $db = \Config\Database::connect();
        
        $result = $db->query("
            SELECT 
                status,
                COUNT(*) as count,
                COALESCE(SUM(total_amount), 0) as revenue
            FROM orders 
            WHERE restaurant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY status
            ORDER BY count DESC
        ", [$restaurantId, $dates['from'], $dates['to']])->getResultArray();
        
        return array_map(function($row) {
            return [
                'status' => $row['status'],
                'count' => (int) $row['count'],
                'revenue' => (float) $row['revenue']
            ];
        }, $result);
    }
    
    private function getCustomerMetrics($restaurantId, $dates, $previousDates)
    {
        $db = \Config\Database::connect();
        
        // Novos clientes no período
        $newCustomers = $db->query("
            SELECT COUNT(*) as count
            FROM customers 
            WHERE restaurant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
        ", [$restaurantId, $dates['from'], $dates['to']])->getRowArray()['count'];
        
        // Clientes que retornaram
        $returningCustomers = $db->query("
            SELECT COUNT(DISTINCT customer_id) as count
            FROM orders 
            WHERE restaurant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            AND customer_id IN (
                SELECT DISTINCT customer_id 
                FROM orders 
                WHERE restaurant_id = ? 
                AND DATE(created_at) < ?
            )
        ", [$restaurantId, $dates['from'], $dates['to'], $restaurantId, $dates['from']])->getRowArray()['count'];
        
        return [
            'new_customers' => (int) $newCustomers,
            'returning_customers' => (int) $returningCustomers,
            'retention_rate' => $newCustomers > 0 ? round(($returningCustomers / $newCustomers) * 100, 2) : 0
        ];
    }
    
    // Métodos adicionais para outras análises seriam implementados aqui...
    // Por brevidade, incluindo apenas os principais métodos
    
    private function getExportData($type, $restaurantId, $dates)
    {
        switch ($type) {
            case 'dashboard':
                return $this->getDashboardMetrics($restaurantId, $dates, $this->getPreviousPeriodDates($dates));
            case 'revenue':
                return $this->getRevenueChart($restaurantId, $dates);
            case 'products':
                return $this->getTopProducts($restaurantId, $dates, 50);
            default:
                return [];
        }
    }
    
    private function formatExportData($data, $format)
    {
        switch ($format) {
            case 'csv':
                return $this->arrayToCsv($data);
            case 'xml':
                return $this->arrayToXml($data);
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }
    
    private function downloadResponse($data, $filename, $format)
    {
        $mimeTypes = [
            'json' => 'application/json',
            'csv' => 'text/csv',
            'xml' => 'application/xml'
        ];
        
        $extensions = [
            'json' => 'json',
            'csv' => 'csv',
            'xml' => 'xml'
        ];
        
        return $this->response
            ->setHeader('Content-Type', $mimeTypes[$format] ?? 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.' . $extensions[$format] . '"')
            ->setBody($data);
    }
}