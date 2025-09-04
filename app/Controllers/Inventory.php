<?php

namespace App\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use CodeIgniter\Controller;

class Inventory extends Secure_Controller
{
    protected $itemModel;
    protected $purchaseModel;
    protected $saleModel;
    protected $supplierModel;

    public function __construct()
    {
        parent::__construct('inventory');
        
        $this->itemModel = new Item();
        $this->purchaseModel = new Purchase();
        $this->saleModel = new Sale();
        $this->supplierModel = new Supplier();
    }

    public function index()
    {
        $data = [];
        $data['controller_name'] = 'Gestão de Estoque';
        
        // Estatísticas gerais
        $data['stats'] = $this->getInventoryStats();
        
        // Produtos com estoque baixo
        $data['low_stock_items'] = $this->itemModel->get_low_stock_items(10);
        
        // Produtos mais vendidos
        $data['top_selling_items'] = $this->getTopSellingItems();
        
        // Previsão de reposição
        $data['restock_predictions'] = $this->getRestockPredictions();
        
        return view('inventory/dashboard', $data);
    }

    public function stock_levels()
    {
        $data = [];
        $data['controller_name'] = 'Níveis de Estoque';
        
        // Filtros
        $category = $this->request->getGet('category');
        $status = $this->request->getGet('status'); // low, normal, high
        $search = $this->request->getGet('search');
        
        // Buscar itens com filtros
        $data['items'] = $this->getItemsWithStock($category, $status, $search);
        $data['categories'] = $this->itemModel->get_categories();
        
        return view('inventory/stock_levels', $data);
    }

    public function restock_alerts()
    {
        $data = [];
        $data['controller_name'] = 'Alertas de Reposição';
        
        // Itens com estoque baixo
        $data['critical_items'] = $this->itemModel->get_low_stock_items(5);
        $data['warning_items'] = $this->itemModel->get_low_stock_items(15);
        
        // Previsões de demanda
        $data['demand_predictions'] = $this->calculateDemandPredictions();
        
        return view('inventory/restock_alerts', $data);
    }

    public function shopping_list()
    {
        $data = [];
        $data['controller_name'] = 'Lista de Compras Inteligente';
        
        // Gerar lista de compras automática
        $data['suggested_items'] = $this->generateShoppingList();
        
        // Fornecedores por categoria
        $data['suppliers_by_category'] = $this->getSuppliersByCategory();
        
        return view('inventory/shopping_list', $data);
    }

    public function generate_shopping_list()
    {
        $items = $this->generateShoppingList();
        
        if ($this->request->getPost('format') === 'whatsapp') {
            return $this->response->setJSON([
                'success' => true,
                'whatsapp_message' => $this->formatWhatsAppMessage($items)
            ]);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'items' => $items
        ]);
    }

    public function send_whatsapp_list()
    {
        $supplier_id = $this->request->getPost('supplier_id');
        $items = $this->request->getPost('items');
        
        if (!$supplier_id || !$items) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados incompletos'
            ]);
        }
        
        $supplier = $this->supplierModel->get_info($supplier_id);
        if (!$supplier || !$supplier->phone_number) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Fornecedor não possui WhatsApp cadastrado'
            ]);
        }
        
        $message = $this->formatWhatsAppMessage($items, $supplier);
        $whatsapp_url = $this->generateWhatsAppURL($supplier->phone_number, $message);
        
        return $this->response->setJSON([
            'success' => true,
            'whatsapp_url' => $whatsapp_url
        ]);
    }

    public function analytics()
    {
        $data = [];
        $data['controller_name'] = 'Analytics de Estoque';
        
        $start_date = $this->request->getGet('start_date') ?: date('Y-m-01');
        $end_date = $this->request->getGet('end_date') ?: date('Y-m-t');
        
        // Dados para gráficos
        $data['stock_movement'] = $this->getStockMovementData($start_date, $end_date);
        $data['purchase_trends'] = $this->getPurchaseTrends($start_date, $end_date);
        $data['waste_analysis'] = $this->getWasteAnalysis($start_date, $end_date);
        $data['supplier_performance'] = $this->getSupplierPerformance($start_date, $end_date);
        
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        
        return view('inventory/analytics', $data);
    }

    public function update_stock_level()
    {
        $item_id = $this->request->getPost('item_id');
        $new_quantity = $this->request->getPost('quantity');
        $reason = $this->request->getPost('reason');
        
        if (!$item_id || $new_quantity === null) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados incompletos'
            ]);
        }
        
        // Atualizar estoque
        $result = $this->itemModel->update_stock($item_id, $new_quantity, $reason);
        
        if ($result) {
            // Registrar movimento de estoque
            $this->logStockMovement($item_id, $new_quantity, $reason);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Estoque atualizado com sucesso'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Erro ao atualizar estoque'
        ]);
    }

    private function getInventoryStats()
    {
        $stats = [];
        
        // Total de itens
        $stats['total_items'] = $this->itemModel->count_all_active();
        
        // Valor total do estoque
        $stats['total_value'] = $this->itemModel->get_total_inventory_value();
        
        // Itens com estoque baixo
        $stats['low_stock_count'] = count($this->itemModel->get_low_stock_items(10));
        
        // Movimentação do mês
        $stats['monthly_movement'] = $this->getMonthlyMovement();
        
        return $stats;
    }

    private function getTopSellingItems($limit = 10)
    {
        return $this->saleModel->get_top_selling_items($limit, 30); // Últimos 30 dias
    }

    private function getRestockPredictions()
    {
        $predictions = [];
        $low_stock_items = $this->itemModel->get_low_stock_items(15);
        
        foreach ($low_stock_items as $item) {
            $avg_daily_sales = $this->calculateAverageDailySales($item->item_id, 30);
            $days_until_stockout = $avg_daily_sales > 0 ? $item->quantity / $avg_daily_sales : 999;
            
            $predictions[] = [
                'item' => $item,
                'days_until_stockout' => round($days_until_stockout),
                'suggested_order_quantity' => $this->calculateSuggestedOrderQuantity($item->item_id),
                'priority' => $days_until_stockout <= 7 ? 'high' : ($days_until_stockout <= 14 ? 'medium' : 'low')
            ];
        }
        
        // Ordenar por prioridade
        usort($predictions, function($a, $b) {
            $priority_order = ['high' => 1, 'medium' => 2, 'low' => 3];
            return $priority_order[$a['priority']] - $priority_order[$b['priority']];
        });
        
        return array_slice($predictions, 0, 10);
    }

    private function generateShoppingList()
    {
        $items = [];
        $low_stock_items = $this->itemModel->get_low_stock_items(20);
        
        foreach ($low_stock_items as $item) {
            $suggested_quantity = $this->calculateSuggestedOrderQuantity($item->item_id);
            $preferred_supplier = $this->getPreferredSupplier($item->item_id);
            
            $items[] = [
                'item' => $item,
                'current_stock' => $item->quantity,
                'suggested_quantity' => $suggested_quantity,
                'estimated_cost' => $suggested_quantity * ($item->cost_price ?? 0),
                'preferred_supplier' => $preferred_supplier,
                'urgency' => $this->calculateUrgency($item->item_id)
            ];
        }
        
        return $items;
    }

    private function calculateAverageDailySales($item_id, $days = 30)
    {
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        return $this->saleModel->get_average_daily_sales($item_id, $start_date);
    }

    private function calculateSuggestedOrderQuantity($item_id)
    {
        $avg_daily_sales = $this->calculateAverageDailySales($item_id, 30);
        $lead_time_days = 7; // Tempo médio de entrega
        $safety_stock_days = 14; // Estoque de segurança
        
        return ceil($avg_daily_sales * ($lead_time_days + $safety_stock_days));
    }

    private function getPreferredSupplier($item_id)
    {
        // Buscar o fornecedor mais usado para este item
        return $this->purchaseModel->get_preferred_supplier_for_item($item_id);
    }

    private function calculateUrgency($item_id)
    {
        $item = $this->itemModel->get_info($item_id);
        $avg_daily_sales = $this->calculateAverageDailySales($item_id, 30);
        
        if ($avg_daily_sales <= 0) return 'low';
        
        $days_until_stockout = $item->quantity / $avg_daily_sales;
        
        if ($days_until_stockout <= 3) return 'critical';
        if ($days_until_stockout <= 7) return 'high';
        if ($days_until_stockout <= 14) return 'medium';
        
        return 'low';
    }

    private function formatWhatsAppMessage($items, $supplier = null)
    {
        $message = "🛒 *LISTA DE COMPRAS*\n\n";
        
        if ($supplier) {
            $message .= "📋 Fornecedor: *{$supplier->company_name}*\n";
            $message .= "📅 Data: " . date('d/m/Y H:i') . "\n\n";
        }
        
        $message .= "📦 *ITENS SOLICITADOS:*\n";
        
        $total_estimated = 0;
        foreach ($items as $item) {
            $message .= "• {$item['item']->name}\n";
            $message .= "  Qtd: {$item['suggested_quantity']}\n";
            $message .= "  Estoque atual: {$item['current_stock']}\n";
            if (isset($item['estimated_cost'])) {
                $message .= "  Valor estimado: R$ " . number_format($item['estimated_cost'], 2, ',', '.') . "\n";
                $total_estimated += $item['estimated_cost'];
            }
            $message .= "\n";
        }
        
        if ($total_estimated > 0) {
            $message .= "💰 *Total Estimado: R$ " . number_format($total_estimated, 2, ',', '.') . "*\n\n";
        }
        
        $message .= "⚡ Gerado automaticamente pelo Sistema de Gestão de Estoque";
        
        return $message;
    }

    private function generateWhatsAppURL($phone, $message)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 11 && substr($phone, 0, 1) !== '55') {
            $phone = '55' . $phone;
        }
        
        return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
    }

    /**
     * Exibe logs do WhatsApp
     */
    public function whatsapp_logs()
    {
        $data = [];
        $data['controller_name'] = 'Logs do WhatsApp Business';
        $data['suppliers'] = $this->supplierModel->get_all();
        
        return view('inventory/whatsapp_logs', $data);
    }

    /**
     * Obtém logs do WhatsApp via AJAX
     */
    public function get_whatsapp_logs()
    {
        // Parâmetros de filtro
        $filters = [];
        $page = $this->request->getPost('page') ?: 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        // Aplicar filtros de período
        $period = $this->request->getPost('period');
        switch ($period) {
            case 'today':
                $filters['date_from'] = date('Y-m-d');
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'yesterday':
                $filters['date_from'] = date('Y-m-d', strtotime('-1 day'));
                $filters['date_to'] = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'week':
                $filters['date_from'] = date('Y-m-d', strtotime('-7 days'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'month':
                $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'custom':
                if ($this->request->getPost('date_from')) {
                    $filters['date_from'] = $this->request->getPost('date_from');
                }
                if ($this->request->getPost('date_to')) {
                    $filters['date_to'] = $this->request->getPost('date_to');
                }
                break;
        }
        
        // Outros filtros
        if ($this->request->getPost('message_type')) {
            $filters['message_type'] = $this->request->getPost('message_type');
        }
        if ($this->request->getPost('status')) {
            $filters['status'] = $this->request->getPost('status');
        }
        if ($this->request->getPost('supplier_id')) {
            $filters['supplier_id'] = $this->request->getPost('supplier_id');
        }
        if ($this->request->getPost('search')) {
            $filters['search'] = $this->request->getPost('search');
        }
        
        try {
            $whatsappLogModel = new \App\Models\WhatsappLog();
            $logs = $whatsappLogModel->get_message_logs($filters, $limit, $offset);
            $total = $whatsappLogModel->count_message_logs($filters);
            
            $total_pages = ceil($total / $limit);
            $showing_from = $offset + 1;
            $showing_to = min($offset + $limit, $total);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => $total_pages,
                'total_records' => $total,
                'showing_from' => $showing_from,
                'showing_to' => $showing_to,
                'start_page' => max(1, $page - 2),
                'end_page' => min($total_pages, $page + 2)
            ];
            
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'pagination' => $pagination
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtém estatísticas resumidas do WhatsApp
     */
    public function get_whatsapp_stats_summary()
    {
        try {
            $whatsappLogModel = new \App\Models\WhatsappLog();
            $stats = $whatsappLogModel->get_message_statistics(['days' => 30]);
            
            // Estatísticas de hoje
            $today_stats = $whatsappLogModel->get_message_statistics([
                'date_from' => date('Y-m-d'),
                'date_to' => date('Y-m-d')
            ]);
            
            $summary = [
                'total_messages' => $stats['general']['total_messages'],
                'success_rate' => $stats['general']['success_rate'],
                'today_messages' => $today_stats['general']['total_messages'],
                'failed_messages' => $stats['general']['failed_count']
            ];
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtém detalhes de um log específico
     */
    public function get_whatsapp_log_details()
    {
        $log_id = $this->request->getPost('log_id');
        
        if (!$log_id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID do log não fornecido'
            ]);
        }
        
        try {
            $whatsappLogModel = new \App\Models\WhatsappLog();
            $log = $whatsappLogModel->get_log_by_id($log_id);
            
            if (!$log) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Log não encontrado'
                ]);
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $log
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtém estatísticas detalhadas do WhatsApp
     */
    public function get_whatsapp_statistics()
    {
        // Usar os mesmos filtros da listagem
        $filters = [];
        $period = $this->request->getPost('period');
        
        switch ($period) {
            case 'today':
                $filters['date_from'] = date('Y-m-d');
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'yesterday':
                $filters['date_from'] = date('Y-m-d', strtotime('-1 day'));
                $filters['date_to'] = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'week':
                $filters['date_from'] = date('Y-m-d', strtotime('-7 days'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'month':
                $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
                $filters['date_to'] = date('Y-m-d');
                break;
            case 'custom':
                if ($this->request->getPost('date_from')) {
                    $filters['date_from'] = $this->request->getPost('date_from');
                }
                if ($this->request->getPost('date_to')) {
                    $filters['date_to'] = $this->request->getPost('date_to');
                }
                break;
            default:
                $filters['days'] = 7;
        }
        
        try {
            $whatsappLogModel = new \App\Models\WhatsappLog();
            $stats = $whatsappLogModel->get_message_statistics($filters);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reenvia uma mensagem do WhatsApp
     */
    public function resend_whatsapp_message()
    {
        $log_id = $this->request->getPost('log_id');
        
        if (!$log_id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID do log não fornecido'
            ]);
        }
        
        try {
            $whatsappLogModel = new \App\Models\WhatsappLog();
            $log = $whatsappLogModel->get_log_by_id($log_id);
            
            if (!$log) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Log não encontrado'
                ]);
            }
            
            // Criar novo log para o reenvio
            $new_log_data = [
                'phone_number' => $log['phone_number'],
                'message' => $log['message'],
                'message_type' => 'outbound',
                'status' => 'pending',
                'supplier_id' => $log['supplier_id'],
                'purchase_id' => $log['purchase_id'],
                'item_ids' => $log['item_ids'],
                'metadata' => json_encode([
                    'resent_from' => $log_id,
                    'resent_at' => date('Y-m-d H:i:s')
                ]),
                'created_by' => session()->get('person_id')
            ];
            
            $new_log_id = $whatsappLogModel->log_message($new_log_data);
            
            if ($new_log_id) {
                // Tentar enviar via API do WhatsApp Business
                helper('whatsapp');
                $result = send_whatsapp_business_message($log['phone_number'], $log['message']);
                
                if ($result['success']) {
                    $whatsappLogModel->update_message_status($new_log_id, 'sent', [
                        'whatsapp_message_id' => $result['message_id'] ?? null
                    ]);
                    
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => 'Mensagem reenviada com sucesso',
                        'whatsapp_url' => $result['whatsapp_url'] ?? null
                    ]);
                } else {
                    $whatsappLogModel->update_message_status($new_log_id, 'failed', [
                        'error_message' => $result['error'] ?? 'Erro desconhecido'
                    ]);
                    
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Erro ao reenviar: ' . ($result['error'] ?? 'Erro desconhecido')
                    ]);
                }
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Erro ao criar log de reenvio'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Exporta logs do WhatsApp para CSV
     */
    public function export_whatsapp_logs()
    {
        // Aplicar os mesmos filtros da listagem
        $filters = [];
        
        if ($this->request->getGet('period')) {
            $period = $this->request->getGet('period');
            switch ($period) {
                case 'today':
                    $filters['date_from'] = date('Y-m-d');
                    $filters['date_to'] = date('Y-m-d');
                    break;
                case 'yesterday':
                    $filters['date_from'] = date('Y-m-d', strtotime('-1 day'));
                    $filters['date_to'] = date('Y-m-d', strtotime('-1 day'));
                    break;
                case 'week':
                    $filters['date_from'] = date('Y-m-d', strtotime('-7 days'));
                    $filters['date_to'] = date('Y-m-d');
                    break;
                case 'month':
                    $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
                    $filters['date_to'] = date('Y-m-d');
                    break;
            }
        }
        
        if ($this->request->getGet('message_type')) {
            $filters['message_type'] = $this->request->getGet('message_type');
        }
        if ($this->request->getGet('status')) {
            $filters['status'] = $this->request->getGet('status');
        }
        if ($this->request->getGet('supplier_id')) {
            $filters['supplier_id'] = $this->request->getGet('supplier_id');
        }
        if ($this->request->getGet('search')) {
            $filters['search'] = $this->request->getGet('search');
        }
        
        try {
            $whatsappLogModel = new \App\Models\WhatsappLog();
            $filepath = $whatsappLogModel->export_logs_to_csv($filters);
            
            if (file_exists($filepath)) {
                $filename = basename($filepath);
                
                return $this->response->download($filepath, null)->setFileName($filename);
            } else {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Erro ao exportar logs: ' . $e->getMessage());
        }
    }

    private function getItemsWithStock($category = null, $status = null, $search = null)
    {
        // Implementar busca com filtros
        return $this->itemModel->get_items_with_filters($category, $status, $search);
    }

    private function getSuppliersByCategory()
    {
        return $this->supplierModel->get_suppliers_grouped_by_category();
    }

    private function calculateDemandPredictions()
    {
        // Implementar algoritmo de previsão de demanda
        return [];
    }

    private function getStockMovementData($start_date, $end_date)
    {
        // Implementar análise de movimentação de estoque
        return [];
    }

    private function getPurchaseTrends($start_date, $end_date)
    {
        return $this->purchaseModel->get_purchase_trends($start_date, $end_date);
    }

    private function getWasteAnalysis($start_date, $end_date)
    {
        // Implementar análise de desperdício
        return [];
    }

    private function getSupplierPerformance($start_date, $end_date)
    {
        return $this->supplierModel->get_performance_metrics($start_date, $end_date);
    }

    private function getMonthlyMovement()
    {
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        
        return [
            'purchases' => $this->purchaseModel->get_monthly_total($start_date, $end_date),
            'sales' => $this->saleModel->get_monthly_total($start_date, $end_date)
        ];
    }

    private function logStockMovement($item_id, $quantity, $reason)
    {
        // Implementar log de movimentação de estoque
        $data = [
            'item_id' => $item_id,
            'quantity_change' => $quantity,
            'reason' => $reason,
            'employee_id' => session()->get('person_id'),
            'movement_time' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->table('stock_movements')->insert($data);
    }
}