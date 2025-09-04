<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\AnalyticsService;
use CodeIgniter\HTTP\ResponseInterface;

class AnalyticsController extends BaseController
{
    protected $analyticsService;

    public function __construct()
    {
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Analytics dashboard main page
     */
    public function index(): string
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return redirect()->to('/auth/login');
        }

        $period = $this->request->getGet('period') ?? '30d';
        $validPeriods = ['7d', '30d', '90d', '1y'];
        
        if (!in_array($period, $validPeriods)) {
            $period = '30d';
        }

        try {
            $analytics = $this->analyticsService->getDashboardData($restaurantId, $period);
            
            $data = [
                'title' => 'Analytics Dashboard',
                'analytics' => $analytics,
                'current_period' => $period,
                'available_periods' => [
                    '7d' => 'Últimos 7 dias',
                    '30d' => 'Últimos 30 dias',
                    '90d' => 'Últimos 90 dias',
                    '1y' => 'Último ano'
                ]
            ];

            return view('analytics/dashboard', $data);
        } catch (\Exception $e) {
            log_message('error', 'Analytics dashboard error: ' . $e->getMessage());
            return view('analytics/dashboard', [
                'title' => 'Analytics Dashboard',
                'error' => 'Erro ao carregar dados de analytics. Tente novamente.'
            ]);
        }
    }

    /**
     * Get sales analytics data (API endpoint)
     */
    public function getSalesData(): ResponseInterface
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso não autorizado'
            ])->setStatusCode(401);
        }

        $period = $this->request->getGet('period') ?? '30d';
        
        try {
            $salesData = $this->analyticsService->getSalesAnalytics($restaurantId, $period);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $salesData
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Sales analytics API error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao carregar dados de vendas'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get popular items data (API endpoint)
     */
    public function getPopularItemsData(): ResponseInterface
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso não autorizado'
            ])->setStatusCode(401);
        }

        $period = $this->request->getGet('period') ?? '30d';
        $limit = (int) ($this->request->getGet('limit') ?? 10);
        
        try {
            $itemsData = $this->analyticsService->getPopularItems($restaurantId, $period, $limit);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $itemsData
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Popular items API error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao carregar dados de pratos populares'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get customer analytics data (API endpoint)
     */
    public function getCustomerData(): ResponseInterface
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso não autorizado'
            ])->setStatusCode(401);
        }

        $period = $this->request->getGet('period') ?? '30d';
        
        try {
            $customerData = $this->analyticsService->getCustomerAnalytics($restaurantId, $period);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $customerData
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Customer analytics API error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao carregar dados de clientes'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get financial analytics data (API endpoint)
     */
    public function getFinancialData(): ResponseInterface
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso não autorizado'
            ])->setStatusCode(401);
        }

        $period = $this->request->getGet('period') ?? '30d';
        
        try {
            $financialData = $this->analyticsService->getFinancialAnalytics($restaurantId, $period);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $financialData
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Financial analytics API error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao carregar dados financeiros'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get operational analytics data (API endpoint)
     */
    public function getOperationalData(): ResponseInterface
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso não autorizado'
            ])->setStatusCode(401);
        }

        $period = $this->request->getGet('period') ?? '30d';
        
        try {
            $operationalData = $this->analyticsService->getOperationalAnalytics($restaurantId, $period);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $operationalData
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Operational analytics API error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao carregar dados operacionais'
            ])->setStatusCode(500);
        }
    }

    /**
     * Export analytics report
     */
    public function exportReport(): ResponseInterface
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return redirect()->to('/auth/login');
        }

        $period = $this->request->getGet('period') ?? '30d';
        $format = $this->request->getGet('format') ?? 'json';
        $validFormats = ['json', 'csv', 'pdf'];
        
        if (!in_array($format, $validFormats)) {
            $format = 'json';
        }

        try {
            $exportData = $this->analyticsService->exportAnalytics($restaurantId, $period, $format);
            
            switch ($format) {
                case 'csv':
                    return $this->downloadCSV($exportData);
                case 'pdf':
                    return $this->downloadPDF($exportData);
                default:
                    return $this->response->setJSON($exportData);
            }
        } catch (\Exception $e) {
            log_message('error', 'Analytics export error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao exportar relatório'
            ])->setStatusCode(500);
        }
    }

    /**
     * Real-time analytics data for live dashboard
     */
    public function getRealTimeData(): ResponseInterface
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso não autorizado'
            ])->setStatusCode(401);
        }

        try {
            // Get today's data
            $today = date('Y-m-d');
            $todayStart = $today . ' 00:00:00';
            $todayEnd = $today . ' 23:59:59';
            
            $db = \Config\Database::connect();
            
            // Today's orders
            $todayOrders = $db->table('orders')
                ->select('COUNT(*) as count, SUM(total_amount) as revenue')
                ->where('restaurant_id', $restaurantId)
                ->where('status', 'completed')
                ->where('created_at >=', $todayStart)
                ->where('created_at <=', $todayEnd)
                ->get()
                ->getRowArray();

            // Pending orders
            $pendingOrders = $db->table('orders')
                ->select('COUNT(*) as count')
                ->where('restaurant_id', $restaurantId)
                ->whereIn('status', ['pending', 'preparing'])
                ->get()
                ->getRowArray();

            // Last hour activity
            $lastHour = date('Y-m-d H:i:s', strtotime('-1 hour'));
            $recentActivity = $db->table('orders')
                ->select('COUNT(*) as orders')
                ->where('restaurant_id', $restaurantId)
                ->where('created_at >=', $lastHour)
                ->get()
                ->getRowArray();

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'today_orders' => (int) ($todayOrders['count'] ?? 0),
                    'today_revenue' => (float) ($todayOrders['revenue'] ?? 0),
                    'pending_orders' => (int) ($pendingOrders['count'] ?? 0),
                    'last_hour_orders' => (int) ($recentActivity['orders'] ?? 0),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Real-time analytics error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao carregar dados em tempo real'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get analytics comparison between periods
     */
    public function getComparison(): ResponseInterface
    {
        $restaurantId = session('restaurant_id');
        if (!$restaurantId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso não autorizado'
            ])->setStatusCode(401);
        }

        $currentPeriod = $this->request->getGet('current') ?? '30d';
        $comparePeriod = $this->request->getGet('compare') ?? '30d';
        
        try {
            $currentData = $this->analyticsService->getSalesAnalytics($restaurantId, $currentPeriod);
            $compareData = $this->analyticsService->getSalesAnalytics($restaurantId, $comparePeriod);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'current' => $currentData,
                    'compare' => $compareData,
                    'comparison' => [
                        'revenue_change' => $currentData['revenue_growth'],
                        'orders_change' => $currentData['order_growth'],
                        'avg_order_change' => $this->calculateChange(
                            $compareData['average_order_value'],
                            $currentData['average_order_value']
                        )
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Analytics comparison error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao comparar períodos'
            ])->setStatusCode(500);
        }
    }

    /**
     * Helper methods
     */
    private function downloadCSV(array $data): ResponseInterface
    {
        $filename = $data['filename'] ?? 'analytics_export.csv';
        
        // Generate CSV content
        $csvContent = $this->generateCSVContent($data['data']);
        
        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csvContent);
    }

    private function downloadPDF(array $data): ResponseInterface
    {
        $filename = $data['filename'] ?? 'analytics_report.pdf';
        
        // Generate PDF content (placeholder - would need PDF library)
        $pdfContent = $this->generatePDFContent($data['data']);
        
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfContent);
    }

    private function generateCSVContent(array $data): string
    {
        $csv = "Relatório de Analytics\n";
        $csv .= "Gerado em: " . date('d/m/Y H:i:s') . "\n\n";
        
        // Sales data
        if (isset($data['sales'])) {
            $csv .= "VENDAS\n";
            $csv .= "Receita Total,Pedidos,Ticket Médio\n";
            $csv .= $data['sales']['total_revenue'] . "," . 
                   $data['sales']['total_orders'] . "," . 
                   $data['sales']['average_order_value'] . "\n\n";
        }
        
        // Popular items
        if (isset($data['popular_items']['popular_items'])) {
            $csv .= "PRATOS POPULARES\n";
            $csv .= "Nome,Quantidade Vendida,Receita\n";
            foreach ($data['popular_items']['popular_items'] as $item) {
                $csv .= $item['name'] . "," . $item['total_sold'] . "," . $item['revenue'] . "\n";
            }
        }
        
        return $csv;
    }

    private function generatePDFContent(array $data): string
    {
        // Placeholder for PDF generation
        // In a real implementation, you would use a PDF library like TCPDF or mPDF
        return "PDF content would be generated here";
    }

    private function calculateChange(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }
        
        return round((($new - $old) / $old) * 100, 2);
    }
}