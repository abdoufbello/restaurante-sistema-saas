<?php

namespace App\Models;

use CodeIgniter\Model;

class StockManagement extends Model
{
    protected $table = 'stock_levels';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'item_id',
        'current_quantity',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'last_updated',
        'location_id',
        'batch_number',
        'expiry_date',
        'cost_per_unit',
        'supplier_id'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'item_id' => 'required|integer',
        'current_quantity' => 'required|decimal',
        'min_stock_level' => 'permit_empty|decimal',
        'max_stock_level' => 'permit_empty|decimal',
        'reorder_point' => 'permit_empty|decimal'
    ];

    public function get_stock_summary()
    {
        return $this->select('stock_levels.*, items.name as item_name, items.category, items.item_number, suppliers.company_name as supplier_name')
                   ->join('items', 'items.item_id = stock_levels.item_id')
                   ->join('suppliers', 'suppliers.person_id = stock_levels.supplier_id', 'left')
                   ->where('items.deleted', 0)
                   ->orderBy('items.name')
                   ->findAll();
    }

    public function get_low_stock_items($limit = null)
    {
        $builder = $this->select('stock_levels.*, items.name as item_name, items.category, items.item_number, items.cost_price')
                       ->join('items', 'items.item_id = stock_levels.item_id')
                       ->where('items.deleted', 0)
                       ->where('stock_levels.current_quantity <= stock_levels.reorder_point')
                       ->orderBy('(stock_levels.current_quantity / NULLIF(stock_levels.reorder_point, 0))', 'ASC');
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        return $builder->findAll();
    }

    public function get_items_by_stock_status($status = 'all', $category = null, $search = null)
    {
        $builder = $this->select('stock_levels.*, items.name as item_name, items.category, items.item_number, items.cost_price')
                       ->join('items', 'items.item_id = stock_levels.item_id')
                       ->where('items.deleted', 0);
        
        // Filtro por categoria
        if ($category) {
            $builder->where('items.category', $category);
        }
        
        // Filtro por busca
        if ($search) {
            $builder->groupStart()
                   ->like('items.name', $search)
                   ->orLike('items.item_number', $search)
                   ->orLike('items.description', $search)
                   ->groupEnd();
        }
        
        // Filtro por status do estoque
        switch ($status) {
            case 'low':
                $builder->where('stock_levels.current_quantity <= stock_levels.reorder_point');
                break;
            case 'normal':
                $builder->where('stock_levels.current_quantity > stock_levels.reorder_point')
                       ->where('stock_levels.current_quantity < stock_levels.max_stock_level');
                break;
            case 'high':
                $builder->where('stock_levels.current_quantity >= stock_levels.max_stock_level');
                break;
            case 'out_of_stock':
                $builder->where('stock_levels.current_quantity', 0);
                break;
        }
        
        return $builder->orderBy('items.name')->findAll();
    }

    public function get_expiring_items($days_ahead = 30)
    {
        $future_date = date('Y-m-d', strtotime("+{$days_ahead} days"));
        
        return $this->select('stock_levels.*, items.name as item_name, items.category')
                   ->join('items', 'items.item_id = stock_levels.item_id')
                   ->where('items.deleted', 0)
                   ->where('stock_levels.expiry_date IS NOT NULL')
                   ->where('stock_levels.expiry_date <=', $future_date)
                   ->where('stock_levels.current_quantity >', 0)
                   ->orderBy('stock_levels.expiry_date')
                   ->findAll();
    }

    public function get_total_inventory_value()
    {
        $result = $this->select('SUM(stock_levels.current_quantity * stock_levels.cost_per_unit) as total_value')
                      ->join('items', 'items.item_id = stock_levels.item_id')
                      ->where('items.deleted', 0)
                      ->first();
        
        return $result ? $result['total_value'] ?? 0 : 0;
    }

    public function update_stock_quantity($item_id, $new_quantity, $reason = null)
    {
        $current_stock = $this->where('item_id', $item_id)->first();
        
        if (!$current_stock) {
            // Criar novo registro de estoque
            $data = [
                'item_id' => $item_id,
                'current_quantity' => $new_quantity,
                'last_updated' => date('Y-m-d H:i:s')
            ];
            return $this->insert($data);
        } else {
            // Atualizar registro existente
            $old_quantity = $current_stock['current_quantity'];
            $data = [
                'current_quantity' => $new_quantity,
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->update($current_stock['id'], $data);
            
            // Registrar movimento de estoque
            if ($result && $reason) {
                $this->log_stock_movement($item_id, $new_quantity - $old_quantity, $reason);
            }
            
            return $result;
        }
    }

    public function adjust_stock($item_id, $quantity_change, $reason = 'adjustment')
    {
        $current_stock = $this->where('item_id', $item_id)->first();
        
        if (!$current_stock) {
            return false;
        }
        
        $new_quantity = max(0, $current_stock['current_quantity'] + $quantity_change);
        
        $data = [
            'current_quantity' => $new_quantity,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->update($current_stock['id'], $data);
        
        if ($result) {
            $this->log_stock_movement($item_id, $quantity_change, $reason);
        }
        
        return $result;
    }

    public function get_stock_movements($item_id = null, $start_date = null, $end_date = null, $limit = 100)
    {
        $builder = $this->db->table('stock_movements sm')
                           ->select('sm.*, items.name as item_name, people.first_name, people.last_name')
                           ->join('items', 'items.item_id = sm.item_id')
                           ->join('people', 'people.person_id = sm.employee_id', 'left')
                           ->orderBy('sm.movement_time', 'DESC');
        
        if ($item_id) {
            $builder->where('sm.item_id', $item_id);
        }
        
        if ($start_date) {
            $builder->where('DATE(sm.movement_time) >=', $start_date);
        }
        
        if ($end_date) {
            $builder->where('DATE(sm.movement_time) <=', $end_date);
        }
        
        return $builder->limit($limit)->get()->getResultArray();
    }

    public function get_reorder_suggestions()
    {
        $low_stock_items = $this->get_low_stock_items();
        $suggestions = [];
        
        foreach ($low_stock_items as $item) {
            // Calcular quantidade sugerida baseada na demanda histórica
            $avg_daily_sales = $this->get_average_daily_sales($item['item_id'], 30);
            $lead_time_days = 7; // Tempo médio de entrega
            $safety_stock_days = 14; // Estoque de segurança
            
            $suggested_quantity = ceil($avg_daily_sales * ($lead_time_days + $safety_stock_days));
            
            // Buscar fornecedor preferido
            $preferred_supplier = $this->get_preferred_supplier($item['item_id']);
            
            $suggestions[] = [
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'current_stock' => $item['current_quantity'],
                'reorder_point' => $item['reorder_point'],
                'suggested_quantity' => $suggested_quantity,
                'avg_daily_sales' => $avg_daily_sales,
                'preferred_supplier' => $preferred_supplier,
                'urgency_score' => $this->calculate_urgency_score($item),
                'estimated_cost' => $suggested_quantity * ($item['cost_price'] ?? 0)
            ];
        }
        
        // Ordenar por urgência
        usort($suggestions, function($a, $b) {
            return $b['urgency_score'] - $a['urgency_score'];
        });
        
        return $suggestions;
    }

    public function set_reorder_levels($item_id, $min_level, $max_level, $reorder_point)
    {
        $stock_record = $this->where('item_id', $item_id)->first();
        
        $data = [
            'min_stock_level' => $min_level,
            'max_stock_level' => $max_level,
            'reorder_point' => $reorder_point
        ];
        
        if ($stock_record) {
            return $this->update($stock_record['id'], $data);
        } else {
            $data['item_id'] = $item_id;
            $data['current_quantity'] = 0;
            return $this->insert($data);
        }
    }

    public function get_abc_analysis()
    {
        // Análise ABC baseada no valor de vendas dos últimos 90 dias
        $results = $this->db->table('sales_items si')
                           ->select('si.item_id, items.name as item_name, SUM(si.quantity_purchased * si.item_unit_price) as total_revenue')
                           ->join('items', 'items.item_id = si.item_id')
                           ->join('sales s', 's.sale_id = si.sale_id')
                           ->where('DATE(s.sale_time) >=', date('Y-m-d', strtotime('-90 days')))
                           ->where('items.deleted', 0)
                           ->groupBy('si.item_id')
                           ->orderBy('total_revenue', 'DESC')
                           ->get()->getResultArray();
        
        $total_revenue = array_sum(array_column($results, 'total_revenue'));
        $cumulative_percentage = 0;
        
        foreach ($results as &$result) {
            $percentage = $total_revenue > 0 ? ($result['total_revenue'] / $total_revenue) * 100 : 0;
            $cumulative_percentage += $percentage;
            
            if ($cumulative_percentage <= 80) {
                $result['abc_category'] = 'A';
            } elseif ($cumulative_percentage <= 95) {
                $result['abc_category'] = 'B';
            } else {
                $result['abc_category'] = 'C';
            }
            
            $result['revenue_percentage'] = $percentage;
            $result['cumulative_percentage'] = $cumulative_percentage;
        }
        
        return $results;
    }

    public function get_inventory_turnover($item_id = null, $days = 30)
    {
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $builder = $this->db->table('sales_items si')
                           ->select('si.item_id, items.name as item_name, SUM(si.quantity_purchased) as total_sold, AVG(stock_levels.current_quantity) as avg_inventory')
                           ->join('sales s', 's.sale_id = si.sale_id')
                           ->join('items', 'items.item_id = si.item_id')
                           ->join('stock_levels', 'stock_levels.item_id = si.item_id')
                           ->where('DATE(s.sale_time) >=', $start_date)
                           ->where('items.deleted', 0)
                           ->groupBy('si.item_id');
        
        if ($item_id) {
            $builder->where('si.item_id', $item_id);
        }
        
        $results = $builder->get()->getResultArray();
        
        // Calcular taxa de rotatividade
        foreach ($results as &$result) {
            $result['turnover_rate'] = $result['avg_inventory'] > 0 ? 
                                     ($result['total_sold'] / $result['avg_inventory']) : 0;
            $result['days_to_sell'] = $result['turnover_rate'] > 0 ? 
                                    ($days / $result['turnover_rate']) : 999;
        }
        
        return $results;
    }

    public function get_waste_report($start_date, $end_date)
    {
        // Relatório de desperdício baseado em produtos vencidos ou perdidos
        return $this->db->table('stock_movements sm')
                       ->select('sm.item_id, items.name as item_name, SUM(ABS(sm.quantity_change)) as wasted_quantity, SUM(ABS(sm.quantity_change) * stock_levels.cost_per_unit) as wasted_value')
                       ->join('items', 'items.item_id = sm.item_id')
                       ->join('stock_levels', 'stock_levels.item_id = sm.item_id')
                       ->whereIn('sm.reason', ['expired', 'damaged', 'lost'])
                       ->where('DATE(sm.movement_time) >=', $start_date)
                       ->where('DATE(sm.movement_time) <=', $end_date)
                       ->where('sm.quantity_change <', 0)
                       ->groupBy('sm.item_id')
                       ->orderBy('wasted_value', 'DESC')
                       ->get()->getResultArray();
    }

    public function get_stock_alerts()
    {
        $alerts = [];
        
        // Alertas de estoque baixo
        $low_stock = $this->get_low_stock_items(20);
        foreach ($low_stock as $item) {
            $alerts[] = [
                'type' => 'low_stock',
                'priority' => $item['current_quantity'] == 0 ? 'critical' : 'high',
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'message' => "Estoque baixo: {$item['item_name']} ({$item['current_quantity']} unidades)",
                'current_quantity' => $item['current_quantity'],
                'reorder_point' => $item['reorder_point']
            ];
        }
        
        // Alertas de produtos próximos ao vencimento
        $expiring_items = $this->get_expiring_items(7);
        foreach ($expiring_items as $item) {
            $days_to_expire = ceil((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
            $alerts[] = [
                'type' => 'expiring',
                'priority' => $days_to_expire <= 2 ? 'critical' : 'medium',
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'message' => "Produto vencendo em {$days_to_expire} dias: {$item['item_name']}",
                'expiry_date' => $item['expiry_date'],
                'days_to_expire' => $days_to_expire
            ];
        }
        
        // Ordenar por prioridade
        $priority_order = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        usort($alerts, function($a, $b) use ($priority_order) {
            return $priority_order[$a['priority']] - $priority_order[$b['priority']];
        });
        
        return $alerts;
    }

    private function log_stock_movement($item_id, $quantity_change, $reason)
    {
        $data = [
            'item_id' => $item_id,
            'quantity_change' => $quantity_change,
            'reason' => $reason,
            'employee_id' => session()->get('person_id'),
            'movement_time' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->table('stock_movements')->insert($data);
    }

    private function get_average_daily_sales($item_id, $days = 30)
    {
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $result = $this->db->table('sales_items si')
                          ->select('COALESCE(SUM(si.quantity_purchased), 0) / ' . $days . ' as avg_daily_sales')
                          ->join('sales s', 's.sale_id = si.sale_id')
                          ->where('si.item_id', $item_id)
                          ->where('DATE(s.sale_time) >=', $start_date)
                          ->get()->getRowArray();
        
        return $result ? ($result['avg_daily_sales'] ?? 0) : 0;
    }

    private function get_preferred_supplier($item_id)
    {
        $result = $this->db->table('purchases_items pi')
                          ->select('suppliers.person_id, suppliers.company_name, COUNT(*) as purchase_count')
                          ->join('purchases p', 'p.purchase_id = pi.purchase_id')
                          ->join('suppliers', 'suppliers.person_id = p.supplier_id')
                          ->where('pi.item_id', $item_id)
                          ->where('DATE(p.purchase_time) >=', date('Y-m-d', strtotime('-180 days')))
                          ->groupBy('suppliers.person_id')
                          ->orderBy('purchase_count', 'DESC')
                          ->limit(1)
                          ->get()->getRowArray();
        
        return $result;
    }

    private function calculate_urgency_score($item)
    {
        $stock_ratio = $item['reorder_point'] > 0 ? $item['current_quantity'] / $item['reorder_point'] : 1;
        $base_score = max(0, 100 - ($stock_ratio * 100));
        
        // Ajustar score baseado na categoria do item
        if (in_array(strtolower($item['category'] ?? ''), ['perecível', 'perishable'])) {
            $base_score *= 1.5;
        }
        
        return min(100, $base_score);
    }
}