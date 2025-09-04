<?php

namespace App\Models;

use CodeIgniter\Model;

class Purchase extends Model
{
    protected $table = 'purchases';
    protected $primaryKey = 'purchase_id';
    protected $allowedFields = [
        'supplier_id', 'employee_id', 'purchase_time', 'reference', 
        'comment', 'total', 'payment_type', 'invoice_number'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function get_info($purchase_id)
    {
        return $this->db->table('purchases p')
            ->select('p.*, s.company_name as supplier_name, e.first_name, e.last_name')
            ->join('suppliers s', 's.person_id = p.supplier_id', 'left')
            ->join('employees e', 'e.person_id = p.employee_id', 'left')
            ->where('p.purchase_id', $purchase_id)
            ->get()
            ->getRow();
    }

    public function get_all($limit = 20, $offset = 0, $col = 'purchase_time', $order = 'desc')
    {
        return $this->db->table('purchases p')
            ->select('p.*, s.company_name as supplier_name')
            ->join('suppliers s', 's.person_id = p.supplier_id', 'left')
            ->orderBy($col, $order)
            ->limit($limit, $offset)
            ->get();
    }

    public function count_all()
    {
        return $this->db->table('purchases')->countAllResults();
    }

    public function get_purchase_items($purchase_id)
    {
        return $this->db->table('purchase_items pi')
            ->select('pi.*, i.name as item_name, i.item_number')
            ->join('items i', 'i.item_id = pi.item_id')
            ->where('pi.purchase_id', $purchase_id)
            ->get();
    }

    public function save_purchase($purchase_data, $items)
    {
        $this->db->transStart();
        
        // Insert purchase
        $purchase_data['purchase_time'] = date('Y-m-d H:i:s');
        $purchase_data['employee_id'] = session()->get('person_id');
        
        $this->insert($purchase_data);
        $purchase_id = $this->getInsertID();
        
        // Insert purchase items
        foreach ($items as $item)
        {
            $item_data = [
                'purchase_id' => $purchase_id,
                'item_id' => $item['item_id'],
                'quantity_purchased' => $item['quantity'],
                'item_cost_price' => $item['cost_price'],
                'discount_percent' => $item['discount_percent'] ?? 0,
                'item_location' => $item['item_location'] ?? 1
            ];
            
            $this->db->table('purchase_items')->insert($item_data);
            
            // Update item stock
            $this->update_item_stock($item['item_id'], $item['quantity']);
        }
        
        $this->db->transComplete();
        
        return $this->db->transStatus() ? $purchase_id : false;
    }

    public function delete_purchase($purchase_id)
    {
        $this->db->transStart();
        
        // Get purchase items to reverse stock
        $items = $this->get_purchase_items($purchase_id)->getResult();
        
        foreach ($items as $item)
        {
            // Reverse stock update
            $this->update_item_stock($item->item_id, -$item->quantity_purchased);
        }
        
        // Delete purchase items
        $this->db->table('purchase_items')->where('purchase_id', $purchase_id)->delete();
        
        // Delete purchase
        $this->delete($purchase_id);
        
        $this->db->transComplete();
        
        return $this->db->transStatus();
    }

    public function get_search_suggestions($search, $limit = 25)
    {
        $suggestions = [];
        
        $results = $this->db->table('purchases p')
            ->select('p.purchase_id, p.reference, p.purchase_time, s.company_name')
            ->join('suppliers s', 's.person_id = p.supplier_id', 'left')
            ->groupStart()
                ->like('p.reference', $search)
                ->orLike('s.company_name', $search)
            ->groupEnd()
            ->limit($limit)
            ->get()
            ->getResult();
        
        foreach ($results as $result)
        {
            $suggestions[] = [
                'value' => $result->purchase_id,
                'label' => $result->reference . ' - ' . $result->company_name . ' (' . date('d/m/Y', strtotime($result->purchase_time)) . ')'
            ];
        }
        
        return $suggestions;
    }

    public function get_monthly_purchases($year = null, $month = null)
    {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        
        return $this->db->table('purchases')
            ->select('DATE(purchase_time) as date, COUNT(*) as count, SUM(total) as total')
            ->where('YEAR(purchase_time)', $year)
            ->where('MONTH(purchase_time)', $month)
            ->groupBy('DATE(purchase_time)')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResult();
    }

    public function get_top_suppliers($limit = 10)
    {
        return $this->db->table('purchases p')
            ->select('s.company_name, COUNT(*) as purchase_count, SUM(p.total) as total_spent')
            ->join('suppliers s', 's.person_id = p.supplier_id')
            ->groupBy('p.supplier_id')
            ->orderBy('total_spent', 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    private function update_item_stock($item_id, $quantity)
    {
        $this->db->table('items')
            ->set('quantity', 'quantity + ' . (float)$quantity, false)
            ->where('item_id', $item_id)
            ->update();
    }

    public function get_low_stock_items($threshold = 10)
    {
        return $this->db->table('items i')
            ->select('i.*, c.category_name')
            ->join('categories c', 'c.category_id = i.category_id', 'left')
            ->where('i.quantity <=', $threshold)
            ->where('i.deleted', 0)
            ->orderBy('i.quantity', 'ASC')
            ->get()
            ->getResult();
    }

    public function get_purchase_analytics($start_date = null, $end_date = null)
    {
        if (!$start_date) $start_date = date('Y-m-01');
        if (!$end_date) $end_date = date('Y-m-t');
        
        $analytics = [];
        
        // Total purchases
        $analytics['total_purchases'] = $this->db->table('purchases')
            ->where('purchase_time >=', $start_date)
            ->where('purchase_time <=', $end_date . ' 23:59:59')
            ->countAllResults();
        
        // Total amount
        $result = $this->db->table('purchases')
            ->selectSum('total')
            ->where('purchase_time >=', $start_date)
            ->where('purchase_time <=', $end_date . ' 23:59:59')
            ->get()
            ->getRow();
        
        $analytics['total_amount'] = $result->total ?? 0;
        
        // Average purchase value
        $analytics['average_purchase'] = $analytics['total_purchases'] > 0 
            ? $analytics['total_amount'] / $analytics['total_purchases'] 
            : 0;
        
        return $analytics;
    }
}