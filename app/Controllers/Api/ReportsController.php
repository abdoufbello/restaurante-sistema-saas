<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

class ReportsController extends BaseApiController
{
    protected $requiredPermissions = [
        'index' => 'reports.read',
        'show' => 'reports.read',
        'create' => 'reports.create',
        'update' => 'reports.update',
        'delete' => 'reports.delete',
        'generate' => 'reports.generate',
        'download' => 'reports.download',
        'schedule' => 'reports.schedule',
        'templates' => 'reports.read',
        'favorites' => 'reports.read'
    ];

    /**
     * Lista relatórios disponíveis
     */
    public function index()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('reports.read');
            
            $page = (int) ($this->request->getGet('page') ?? 1);
            $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
            $search = $this->request->getGet('search');
            $type = $this->request->getGet('type');
            $status = $this->request->getGet('status');
            $category = $this->request->getGet('category');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            // Verifica cache
            $cacheKey = $this->generateCacheKey('reports_list', [
                'restaurant_id' => $restaurantId,
                'page' => $page,
                'per_page' => $perPage,
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'category' => $category
            ]);
            
            $cachedData = $this->getCache($cacheKey);
            if ($cachedData) {
                return $this->successResponse($cachedData, 'Relatórios carregados do cache');
            }
            
            $db = \Config\Database::connect();
            $builder = $db->table('reports');
            
            // Aplica filtro de multi-tenancy
            $builder->where('restaurant_id', $restaurantId);
            
            // Aplica filtros
            if ($search) {
                $builder->groupStart()
                    ->like('name', $search)
                    ->orLike('description', $search)
                    ->orLike('category', $search)
                    ->groupEnd();
            }
            
            if ($type) {
                $builder->where('type', $type);
            }
            
            if ($status) {
                $builder->where('status', $status);
            }
            
            if ($category) {
                $builder->where('category', $category);
            }
            
            // Conta total
            $total = $builder->countAllResults(false);
            
            // Aplica paginação e ordenação
            $reports = $builder
                ->orderBy('created_at', 'DESC')
                ->limit($perPage, ($page - 1) * $perPage)
                ->get()
                ->getResultArray();
            
            // Sanitiza dados
            $reports = array_map([$this, 'sanitizeOutputData'], $reports);
            
            $data = [
                'reports' => $reports,
                'pagination' => $this->buildPaginationData($page, $perPage, $total)
            ];
            
            // Cache por 5 minutos
            $this->saveCache($cacheKey, $data, 300);
            
            $this->logActivity('reports_list', ['total' => $total]);
            
            return $this->successResponse($data, 'Relatórios listados com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao listar relatórios: ' . $e->getMessage());
        }
    }
    
    /**
     * Exibe um relatório específico
     */
    public function show($id)
    {
        try {
            $this->validateJWT();
            $this->checkPermission('reports.read');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            $db = \Config\Database::connect();
            $report = $db->table('reports')
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->get()
                ->getRowArray();
            
            if (!$report) {
                return $this->notFoundResponse('Relatório não encontrado');
            }
            
            // Decodifica configurações JSON
            $report['config'] = json_decode($report['config'] ?? '{}', true);
            $report['filters'] = json_decode($report['filters'] ?? '{}', true);
            $report['schedule'] = json_decode($report['schedule'] ?? '{}', true);
            
            // Sanitiza dados
            $report = $this->sanitizeOutputData($report);
            
            $this->logActivity('report_view', ['report_id' => $id]);
            
            return $this->successResponse($report, 'Relatório carregado com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao carregar relatório: ' . $e->getMessage());
        }
    }
    
    /**
     * Cria um novo relatório
     */
    public function create()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('reports.create');
            
            $data = $this->getRequestData();
            
            // Validação
            $validation = \Config\Services::validation();
            $validation->setRules([
                'name' => 'required|max_length[255]',
                'type' => 'required|in_list[sales,products,customers,inventory,financial,custom]',
                'category' => 'required|max_length[100]',
                'description' => 'permit_empty|max_length[1000]',
                'config' => 'required',
                'filters' => 'permit_empty',
                'schedule' => 'permit_empty',
                'is_public' => 'permit_empty|in_list[0,1]',
                'status' => 'permit_empty|in_list[active,inactive]'
            ]);
            
            if (!$validation->run($data)) {
                return $this->validationErrorResponse($validation->getErrors());
            }
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            $userId = $this->getCurrentUser()['id'];
            
            // Verifica limites do plano
            if (!$this->checkPlanLimit('reports', $restaurantId)) {
                return $this->forbiddenResponse('Limite de relatórios do plano atingido');
            }
            
            $reportData = [
                'restaurant_id' => $restaurantId,
                'user_id' => $userId,
                'name' => $data['name'],
                'type' => $data['type'],
                'category' => $data['category'],
                'description' => $data['description'] ?? '',
                'config' => json_encode($data['config']),
                'filters' => json_encode($data['filters'] ?? []),
                'schedule' => json_encode($data['schedule'] ?? []),
                'is_public' => $data['is_public'] ?? 0,
                'status' => $data['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db = \Config\Database::connect();
            $db->table('reports')->insert($reportData);
            $reportId = $db->insertID();
            
            // Busca o relatório criado
            $report = $db->table('reports')
                ->where('id', $reportId)
                ->get()
                ->getRowArray();
            
            // Decodifica configurações
            $report['config'] = json_decode($report['config'], true);
            $report['filters'] = json_decode($report['filters'], true);
            $report['schedule'] = json_decode($report['schedule'], true);
            
            // Remove cache
            $this->removeCache("reports_list_*");
            
            $this->logActivity('report_create', [
                'report_id' => $reportId,
                'name' => $data['name'],
                'type' => $data['type']
            ]);
            
            return $this->successResponse($report, 'Relatório criado com sucesso', 201);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao criar relatório: ' . $e->getMessage());
        }
    }
    
    /**
     * Gera um relatório
     */
    public function generate($id)
    {
        try {
            $this->validateJWT();
            $this->checkPermission('reports.generate');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            $db = \Config\Database::connect();
            $report = $db->table('reports')
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->get()
                ->getRowArray();
            
            if (!$report) {
                return $this->notFoundResponse('Relatório não encontrado');
            }
            
            if ($report['status'] !== 'active') {
                return $this->forbiddenResponse('Relatório inativo');
            }
            
            // Parâmetros de geração
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            $format = $this->request->getGet('format') ?? 'json';
            $customFilters = $this->request->getGet('filters') ?? [];
            
            // Decodifica configurações
            $config = json_decode($report['config'], true);
            $filters = json_decode($report['filters'], true);
            
            // Mescla filtros personalizados
            if ($customFilters) {
                $filters = array_merge($filters, $customFilters);
            }
            
            // Define período se não especificado
            if (!$dateFrom || !$dateTo) {
                $period = $filters['period'] ?? 'month';
                $dates = $this->getDefaultPeriod($period);
                $dateFrom = $dateFrom ?? $dates['from'];
                $dateTo = $dateTo ?? $dates['to'];
            }
            
            // Gera dados do relatório
            $reportData = $this->generateReportData($report['type'], $config, $filters, $restaurantId, $dateFrom, $dateTo);
            
            // Formata dados
            $formattedData = $this->formatReportData($reportData, $format);
            
            // Atualiza estatísticas do relatório
            $db->table('reports')
                ->where('id', $id)
                ->set('last_generated_at', date('Y-m-d H:i:s'))
                ->set('generation_count', 'generation_count + 1', false)
                ->update();
            
            $this->logActivity('report_generate', [
                'report_id' => $id,
                'format' => $format,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
            
            if ($format === 'json') {
                return $this->successResponse([
                    'report' => [
                        'id' => $report['id'],
                        'name' => $report['name'],
                        'type' => $report['type'],
                        'generated_at' => date('Y-m-d H:i:s'),
                        'period' => [
                            'from' => $dateFrom,
                            'to' => $dateTo
                        ]
                    ],
                    'data' => $reportData
                ], 'Relatório gerado com sucesso');
            } else {
                // Download direto para outros formatos
                $filename = $this->generateReportFilename($report['name'], $format);
                return $this->downloadResponse($formattedData, $filename, $format);
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar relatório: ' . $e->getMessage());
        }
    }
    
    /**
     * Lista templates de relatórios disponíveis
     */
    public function templates()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('reports.read');
            
            $templates = [
                [
                    'id' => 'sales_summary',
                    'name' => 'Resumo de Vendas',
                    'description' => 'Relatório resumido de vendas com métricas principais',
                    'type' => 'sales',
                    'category' => 'vendas',
                    'config' => [
                        'metrics' => ['total_revenue', 'total_orders', 'avg_order_value'],
                        'charts' => ['revenue_timeline', 'orders_by_status'],
                        'grouping' => 'daily'
                    ]
                ],
                [
                    'id' => 'product_performance',
                    'name' => 'Performance de Produtos',
                    'description' => 'Análise detalhada da performance dos produtos',
                    'type' => 'products',
                    'category' => 'produtos',
                    'config' => [
                        'metrics' => ['quantity_sold', 'revenue', 'profit_margin'],
                        'top_products' => 20,
                        'include_categories' => true
                    ]
                ],
                [
                    'id' => 'customer_analysis',
                    'name' => 'Análise de Clientes',
                    'description' => 'Relatório completo sobre comportamento dos clientes',
                    'type' => 'customers',
                    'category' => 'clientes',
                    'config' => [
                        'metrics' => ['new_customers', 'returning_customers', 'lifetime_value'],
                        'segmentation' => true,
                        'retention_analysis' => true
                    ]
                ],
                [
                    'id' => 'inventory_status',
                    'name' => 'Status do Estoque',
                    'description' => 'Relatório de controle e status do estoque',
                    'type' => 'inventory',
                    'category' => 'estoque',
                    'config' => [
                        'low_stock_alert' => true,
                        'stock_movement' => true,
                        'valuation' => true
                    ]
                ],
                [
                    'id' => 'financial_summary',
                    'name' => 'Resumo Financeiro',
                    'description' => 'Relatório financeiro com receitas, custos e lucros',
                    'type' => 'financial',
                    'category' => 'financeiro',
                    'config' => [
                        'revenue_breakdown' => true,
                        'cost_analysis' => true,
                        'profit_margins' => true,
                        'payment_methods' => true
                    ]
                ]
            ];
            
            return $this->successResponse($templates, 'Templates carregados com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao carregar templates: ' . $e->getMessage());
        }
    }
    
    /**
     * Gerencia relatórios favoritos
     */
    public function favorites()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('reports.read');
            
            $method = $this->request->getMethod();
            $userId = $this->getCurrentUser()['id'];
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            if ($method === 'POST') {
                // Adicionar aos favoritos
                $reportId = $this->request->getPost('report_id');
                
                if (!$reportId) {
                    return $this->validationErrorResponse(['report_id' => 'ID do relatório é obrigatório']);
                }
                
                // Verifica se o relatório existe e pertence ao restaurante
                $db = \Config\Database::connect();
                $report = $db->table('reports')
                    ->where('id', $reportId)
                    ->where('restaurant_id', $restaurantId)
                    ->get()
                    ->getRowArray();
                
                if (!$report) {
                    return $this->notFoundResponse('Relatório não encontrado');
                }
                
                // Verifica se já está nos favoritos
                $existing = $db->table('report_favorites')
                    ->where('user_id', $userId)
                    ->where('report_id', $reportId)
                    ->get()
                    ->getRowArray();
                
                if ($existing) {
                    return $this->conflictResponse('Relatório já está nos favoritos');
                }
                
                // Adiciona aos favoritos
                $db->table('report_favorites')->insert([
                    'user_id' => $userId,
                    'report_id' => $reportId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                return $this->successResponse(null, 'Relatório adicionado aos favoritos');
                
            } elseif ($method === 'DELETE') {
                // Remover dos favoritos
                $reportId = $this->request->getGet('report_id');
                
                if (!$reportId) {
                    return $this->validationErrorResponse(['report_id' => 'ID do relatório é obrigatório']);
                }
                
                $db = \Config\Database::connect();
                $deleted = $db->table('report_favorites')
                    ->where('user_id', $userId)
                    ->where('report_id', $reportId)
                    ->delete();
                
                if (!$deleted) {
                    return $this->notFoundResponse('Favorito não encontrado');
                }
                
                return $this->successResponse(null, 'Relatório removido dos favoritos');
                
            } else {
                // Listar favoritos
                $db = \Config\Database::connect();
                $favorites = $db->table('report_favorites rf')
                    ->select('r.*, rf.created_at as favorited_at')
                    ->join('reports r', 'r.id = rf.report_id')
                    ->where('rf.user_id', $userId)
                    ->where('r.restaurant_id', $restaurantId)
                    ->orderBy('rf.created_at', 'DESC')
                    ->get()
                    ->getResultArray();
                
                // Sanitiza dados
                $favorites = array_map([$this, 'sanitizeOutputData'], $favorites);
                
                return $this->successResponse($favorites, 'Favoritos carregados com sucesso');
            }
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerenciar favoritos: ' . $e->getMessage());
        }
    }
    
    // Métodos auxiliares privados
    
    private function generateReportData($type, $config, $filters, $restaurantId, $dateFrom, $dateTo)
    {
        switch ($type) {
            case 'sales':
                return $this->generateSalesReport($config, $filters, $restaurantId, $dateFrom, $dateTo);
            case 'products':
                return $this->generateProductsReport($config, $filters, $restaurantId, $dateFrom, $dateTo);
            case 'customers':
                return $this->generateCustomersReport($config, $filters, $restaurantId, $dateFrom, $dateTo);
            case 'inventory':
                return $this->generateInventoryReport($config, $filters, $restaurantId, $dateFrom, $dateTo);
            case 'financial':
                return $this->generateFinancialReport($config, $filters, $restaurantId, $dateFrom, $dateTo);
            default:
                throw new \Exception('Tipo de relatório não suportado: ' . $type);
        }
    }
    
    private function generateSalesReport($config, $filters, $restaurantId, $dateFrom, $dateTo)
    {
        $db = \Config\Database::connect();
        
        $data = [];
        
        // Métricas principais
        if (in_array('total_revenue', $config['metrics'] ?? [])) {
            $revenue = $db->query("
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM orders 
                WHERE restaurant_id = ? 
                AND DATE(created_at) BETWEEN ? AND ?
                AND status != 'cancelled'
            ", [$restaurantId, $dateFrom, $dateTo])->getRowArray();
            
            $data['total_revenue'] = (float) $revenue['total'];
        }
        
        if (in_array('total_orders', $config['metrics'] ?? [])) {
            $orders = $db->query("
                SELECT COUNT(*) as total
                FROM orders 
                WHERE restaurant_id = ? 
                AND DATE(created_at) BETWEEN ? AND ?
                AND status != 'cancelled'
            ", [$restaurantId, $dateFrom, $dateTo])->getRowArray();
            
            $data['total_orders'] = (int) $orders['total'];
        }
        
        if (in_array('avg_order_value', $config['metrics'] ?? [])) {
            $avg = $db->query("
                SELECT COALESCE(AVG(total_amount), 0) as average
                FROM orders 
                WHERE restaurant_id = ? 
                AND DATE(created_at) BETWEEN ? AND ?
                AND status != 'cancelled'
            ", [$restaurantId, $dateFrom, $dateTo])->getRowArray();
            
            $data['avg_order_value'] = (float) $avg['average'];
        }
        
        // Timeline de receita
        if (in_array('revenue_timeline', $config['charts'] ?? [])) {
            $grouping = $config['grouping'] ?? 'daily';
            $dateFormat = $grouping === 'daily' ? '%Y-%m-%d' : '%Y-%m';
            
            $timeline = $db->query("
                SELECT 
                    DATE_FORMAT(created_at, '{$dateFormat}') as period,
                    COUNT(*) as orders,
                    COALESCE(SUM(total_amount), 0) as revenue
                FROM orders 
                WHERE restaurant_id = ? 
                AND DATE(created_at) BETWEEN ? AND ?
                AND status != 'cancelled'
                GROUP BY DATE_FORMAT(created_at, '{$dateFormat}')
                ORDER BY period
            ", [$restaurantId, $dateFrom, $dateTo])->getResultArray();
            
            $data['revenue_timeline'] = array_map(function($row) {
                return [
                    'period' => $row['period'],
                    'orders' => (int) $row['orders'],
                    'revenue' => (float) $row['revenue']
                ];
            }, $timeline);
        }
        
        return $data;
    }
    
    private function generateProductsReport($config, $filters, $restaurantId, $dateFrom, $dateTo)
    {
        $db = \Config\Database::connect();
        $data = [];
        
        // Top produtos
        $limit = $config['top_products'] ?? 10;
        $topProducts = $db->query("
            SELECT 
                p.name,
                p.id,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.total_price) as revenue,
                AVG(oi.unit_price) as avg_price
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.restaurant_id = ? 
            AND DATE(o.created_at) BETWEEN ? AND ?
            AND o.status != 'cancelled'
            GROUP BY p.id, p.name
            ORDER BY revenue DESC
            LIMIT ?
        ", [$restaurantId, $dateFrom, $dateTo, $limit])->getResultArray();
        
        $data['top_products'] = array_map(function($row) {
            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'quantity_sold' => (int) $row['quantity_sold'],
                'revenue' => (float) $row['revenue'],
                'avg_price' => (float) $row['avg_price']
            ];
        }, $topProducts);
        
        return $data;
    }
    
    private function getDefaultPeriod($period)
    {
        $now = new \DateTime();
        
        switch ($period) {
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
    
    private function formatReportData($data, $format)
    {
        switch ($format) {
            case 'csv':
                return $this->arrayToCsv($data);
            case 'xml':
                return $this->arrayToXml($data);
            case 'pdf':
                return $this->arrayToPdf($data);
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }
    
    private function generateReportFilename($reportName, $format)
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $reportName);
        return $safeName . '_' . date('Y-m-d_H-i-s') . '.' . $format;
    }
    
    private function downloadResponse($data, $filename, $format)
    {
        $mimeTypes = [
            'json' => 'application/json',
            'csv' => 'text/csv',
            'xml' => 'application/xml',
            'pdf' => 'application/pdf'
        ];
        
        return $this->response
            ->setHeader('Content-Type', $mimeTypes[$format] ?? 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($data);
    }
}