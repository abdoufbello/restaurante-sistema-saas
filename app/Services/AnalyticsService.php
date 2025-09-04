<?php

namespace App\Services;

use App\Models\OrderModel;
use App\Models\MenuItemModel;
use App\Models\RestaurantModel;
use App\Models\SubscriptionModel;
use CodeIgniter\Database\ConnectionInterface;

class AnalyticsService
{
    protected $db;
    protected $orderModel;
    protected $menuItemModel;
    protected $restaurantModel;
    protected $subscriptionModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->orderModel = new OrderModel();
        $this->menuItemModel = new MenuItemModel();
        $this->restaurantModel = new RestaurantModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * Get sales analytics for a restaurant
     */
    public function getSalesAnalytics(int $restaurantId, string $period = '30d'): array
    {
        $dateRange = $this->getDateRange($period);
        
        // Total sales
        $totalSales = $this->db->table('orders')
            ->select('SUM(total_amount) as total, COUNT(*) as count')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->get()
            ->getRowArray();

        // Sales by day
        $salesByDay = $this->db->table('orders')
            ->select('DATE(created_at) as date, SUM(total_amount) as total, COUNT(*) as orders')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        // Average order value
        $avgOrderValue = $totalSales['total'] > 0 ? $totalSales['total'] / $totalSales['count'] : 0;

        // Peak hours
        $peakHours = $this->db->table('orders')
            ->select('HOUR(created_at) as hour, COUNT(*) as orders')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->groupBy('HOUR(created_at)')
            ->orderBy('orders', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // Growth comparison with previous period
        $previousPeriod = $this->getPreviousDateRange($period);
        $previousSales = $this->db->table('orders')
            ->select('SUM(total_amount) as total, COUNT(*) as count')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $previousPeriod['start'])
            ->where('created_at <=', $previousPeriod['end'])
            ->get()
            ->getRowArray();

        $revenueGrowth = $this->calculateGrowthRate(
            $previousSales['total'] ?? 0,
            $totalSales['total'] ?? 0
        );

        $orderGrowth = $this->calculateGrowthRate(
            $previousSales['count'] ?? 0,
            $totalSales['count'] ?? 0
        );

        return [
            'total_revenue' => (float) ($totalSales['total'] ?? 0),
            'total_orders' => (int) ($totalSales['count'] ?? 0),
            'average_order_value' => round($avgOrderValue, 2),
            'revenue_growth' => $revenueGrowth,
            'order_growth' => $orderGrowth,
            'sales_by_day' => $salesByDay,
            'peak_hours' => $peakHours,
            'period' => $period,
            'date_range' => $dateRange
        ];
    }

    /**
     * Get popular menu items analytics
     */
    public function getPopularItems(int $restaurantId, string $period = '30d', int $limit = 10): array
    {
        $dateRange = $this->getDateRange($period);

        $popularItems = $this->db->table('order_items oi')
            ->select('mi.name, mi.id, mi.price, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue')
            ->join('menu_items mi', 'mi.id = oi.menu_item_id')
            ->join('orders o', 'o.id = oi.order_id')
            ->where('o.restaurant_id', $restaurantId)
            ->where('o.status', 'completed')
            ->where('o.created_at >=', $dateRange['start'])
            ->where('o.created_at <=', $dateRange['end'])
            ->groupBy('mi.id')
            ->orderBy('total_sold', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        // Get category performance
        $categoryPerformance = $this->db->table('order_items oi')
            ->select('mc.name as category, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue')
            ->join('menu_items mi', 'mi.id = oi.menu_item_id')
            ->join('menu_categories mc', 'mc.id = mi.category_id')
            ->join('orders o', 'o.id = oi.order_id')
            ->where('o.restaurant_id', $restaurantId)
            ->where('o.status', 'completed')
            ->where('o.created_at >=', $dateRange['start'])
            ->where('o.created_at <=', $dateRange['end'])
            ->groupBy('mc.id')
            ->orderBy('revenue', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'popular_items' => $popularItems,
            'category_performance' => $categoryPerformance,
            'period' => $period
        ];
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(int $restaurantId, string $period = '30d'): array
    {
        $dateRange = $this->getDateRange($period);

        // New vs returning customers
        $customerStats = $this->db->query("
            SELECT 
                COUNT(DISTINCT customer_phone) as total_customers,
                COUNT(DISTINCT CASE WHEN order_count = 1 THEN customer_phone END) as new_customers,
                COUNT(DISTINCT CASE WHEN order_count > 1 THEN customer_phone END) as returning_customers
            FROM (
                SELECT 
                    customer_phone,
                    COUNT(*) as order_count
                FROM orders 
                WHERE restaurant_id = ? 
                    AND status = 'completed'
                    AND created_at >= ?
                    AND created_at <= ?
                    AND customer_phone IS NOT NULL
                GROUP BY customer_phone
            ) customer_orders
        ", [$restaurantId, $dateRange['start'], $dateRange['end']])->getRowArray();

        // Customer lifetime value
        $customerLTV = $this->db->query("
            SELECT 
                customer_phone,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_spent,
                AVG(total_amount) as avg_order_value,
                DATEDIFF(MAX(created_at), MIN(created_at)) as customer_lifespan_days
            FROM orders 
            WHERE restaurant_id = ? 
                AND status = 'completed'
                AND customer_phone IS NOT NULL
            GROUP BY customer_phone
            HAVING total_orders > 1
            ORDER BY total_spent DESC
            LIMIT 20
        ", [$restaurantId])->getResultArray();

        // Order frequency distribution
        $orderFrequency = $this->db->query("
            SELECT 
                CASE 
                    WHEN order_count = 1 THEN '1 pedido'
                    WHEN order_count BETWEEN 2 AND 5 THEN '2-5 pedidos'
                    WHEN order_count BETWEEN 6 AND 10 THEN '6-10 pedidos'
                    ELSE '10+ pedidos'
                END as frequency_range,
                COUNT(*) as customer_count
            FROM (
                SELECT customer_phone, COUNT(*) as order_count
                FROM orders 
                WHERE restaurant_id = ? 
                    AND status = 'completed'
                    AND customer_phone IS NOT NULL
                GROUP BY customer_phone
            ) customer_orders
            GROUP BY frequency_range
        ", [$restaurantId])->getResultArray();

        return [
            'total_customers' => (int) ($customerStats['total_customers'] ?? 0),
            'new_customers' => (int) ($customerStats['new_customers'] ?? 0),
            'returning_customers' => (int) ($customerStats['returning_customers'] ?? 0),
            'customer_retention_rate' => $this->calculateRetentionRate($customerStats),
            'top_customers' => $customerLTV,
            'order_frequency' => $orderFrequency,
            'period' => $period
        ];
    }

    /**
     * Get financial analytics
     */
    public function getFinancialAnalytics(int $restaurantId, string $period = '30d'): array
    {
        $dateRange = $this->getDateRange($period);

        // Revenue breakdown
        $revenueBreakdown = $this->db->table('orders')
            ->select('SUM(subtotal) as gross_revenue, SUM(tax_amount) as tax_revenue, SUM(total_amount) as net_revenue')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->get()
            ->getRowArray();

        // Payment method distribution
        $paymentMethods = $this->db->table('orders')
            ->select('payment_method, COUNT(*) as count, SUM(total_amount) as total')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->groupBy('payment_method')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        // Monthly recurring revenue (for subscription)
        $subscription = $this->subscriptionModel->where('restaurant_id', $restaurantId)->first();
        $mrr = 0;
        if ($subscription && $subscription['status'] === 'active') {
            $plan = $this->db->table('subscription_plans')
                ->where('id', $subscription['plan_id'])
                ->get()
                ->getRowArray();
            $mrr = $plan['price'] ?? 0;
        }

        // Cost analysis (estimated)
        $totalRevenue = (float) ($revenueBreakdown['net_revenue'] ?? 0);
        $estimatedCosts = [
            'subscription_cost' => $mrr,
            'payment_processing' => $totalRevenue * 0.035, // 3.5% estimated
            'estimated_food_cost' => $totalRevenue * 0.30, // 30% estimated
            'estimated_labor_cost' => $totalRevenue * 0.25, // 25% estimated
        ];

        $totalCosts = array_sum($estimatedCosts);
        $estimatedProfit = $totalRevenue - $totalCosts;
        $profitMargin = $totalRevenue > 0 ? ($estimatedProfit / $totalRevenue) * 100 : 0;

        return [
            'gross_revenue' => (float) ($revenueBreakdown['gross_revenue'] ?? 0),
            'tax_revenue' => (float) ($revenueBreakdown['tax_revenue'] ?? 0),
            'net_revenue' => $totalRevenue,
            'payment_methods' => $paymentMethods,
            'monthly_recurring_revenue' => $mrr,
            'estimated_costs' => $estimatedCosts,
            'total_costs' => $totalCosts,
            'estimated_profit' => $estimatedProfit,
            'profit_margin' => round($profitMargin, 2),
            'period' => $period
        ];
    }

    /**
     * Get operational analytics
     */
    public function getOperationalAnalytics(int $restaurantId, string $period = '30d'): array
    {
        $dateRange = $this->getDateRange($period);

        // Order status distribution
        $orderStatus = $this->db->table('orders')
            ->select('status, COUNT(*) as count')
            ->where('restaurant_id', $restaurantId)
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->groupBy('status')
            ->get()
            ->getResultArray();

        // Average preparation time
        $avgPrepTime = $this->db->table('orders')
            ->select('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_prep_time')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->get()
            ->getRowArray();

        // Busiest days of week
        $busiestDays = $this->db->table('orders')
            ->select('DAYNAME(created_at) as day_name, DAYOFWEEK(created_at) as day_num, COUNT(*) as orders')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->groupBy('DAYOFWEEK(created_at)')
            ->orderBy('orders', 'DESC')
            ->get()
            ->getResultArray();

        // Order source distribution (totem vs other)
        $orderSources = $this->db->table('orders')
            ->select('order_source, COUNT(*) as count, SUM(total_amount) as revenue')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->where('created_at >=', $dateRange['start'])
            ->where('created_at <=', $dateRange['end'])
            ->groupBy('order_source')
            ->get()
            ->getResultArray();

        return [
            'order_status_distribution' => $orderStatus,
            'average_prep_time' => round((float) ($avgPrepTime['avg_prep_time'] ?? 0), 1),
            'busiest_days' => $busiestDays,
            'order_sources' => $orderSources,
            'period' => $period
        ];
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData(int $restaurantId, string $period = '30d'): array
    {
        return [
            'sales' => $this->getSalesAnalytics($restaurantId, $period),
            'popular_items' => $this->getPopularItems($restaurantId, $period, 5),
            'customers' => $this->getCustomerAnalytics($restaurantId, $period),
            'financial' => $this->getFinancialAnalytics($restaurantId, $period),
            'operational' => $this->getOperationalAnalytics($restaurantId, $period),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Export analytics data to various formats
     */
    public function exportAnalytics(int $restaurantId, string $period = '30d', string $format = 'json'): array
    {
        $data = $this->getDashboardData($restaurantId, $period);
        
        switch ($format) {
            case 'csv':
                return $this->convertToCSV($data);
            case 'pdf':
                return $this->generatePDFReport($data, $restaurantId);
            default:
                return $data;
        }
    }

    /**
     * Helper methods
     */
    private function getDateRange(string $period): array
    {
        $end = date('Y-m-d 23:59:59');
        
        switch ($period) {
            case '7d':
                $start = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case '30d':
                $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            case '90d':
                $start = date('Y-m-d 00:00:00', strtotime('-90 days'));
                break;
            case '1y':
                $start = date('Y-m-d 00:00:00', strtotime('-1 year'));
                break;
            default:
                $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
        }
        
        return ['start' => $start, 'end' => $end];
    }

    private function getPreviousDateRange(string $period): array
    {
        $current = $this->getDateRange($period);
        $days = (strtotime($current['end']) - strtotime($current['start'])) / (60 * 60 * 24);
        
        $end = date('Y-m-d 23:59:59', strtotime($current['start'] . ' -1 day'));
        $start = date('Y-m-d 00:00:00', strtotime($end . ' -' . $days . ' days'));
        
        return ['start' => $start, 'end' => $end];
    }

    private function calculateGrowthRate(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function calculateRetentionRate(array $customerStats): float
    {
        $total = (int) ($customerStats['total_customers'] ?? 0);
        $returning = (int) ($customerStats['returning_customers'] ?? 0);
        
        return $total > 0 ? round(($returning / $total) * 100, 2) : 0;
    }

    private function convertToCSV(array $data): array
    {
        // Implementation for CSV conversion
        return [
            'format' => 'csv',
            'filename' => 'analytics_' . date('Y-m-d') . '.csv',
            'data' => $data
        ];
    }

    private function generatePDFReport(array $data, int $restaurantId): array
    {
        // Implementation for PDF generation
        return [
            'format' => 'pdf',
            'filename' => 'analytics_report_' . date('Y-m-d') . '.pdf',
            'data' => $data
        ];
    }
}