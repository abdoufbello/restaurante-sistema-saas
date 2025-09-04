<?php

namespace App\Models;

use CodeIgniter\Model;

class MonthlyUsageModel extends Model
{
    protected $table = 'monthly_usage';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'year',
        'month',
        'orders_count',
        'totems_used'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'year' => 'required|integer|greater_than[2020]',
        'month' => 'required|integer|greater_than[0]|less_than[13]',
        'orders_count' => 'integer|greater_than_equal_to[0]',
        'totems_used' => 'integer|greater_than_equal_to[0]'
    ];
    
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Obter uso do mês atual para um restaurante
     */
    public function getCurrentMonthUsage($restaurantId)
    {
        $year = date('Y');
        $month = date('n');
        
        $usage = $this->where([
            'restaurant_id' => $restaurantId,
            'year' => $year,
            'month' => $month
        ])->first();
        
        if (!$usage) {
            // Criar registro se não existir
            $data = [
                'restaurant_id' => $restaurantId,
                'year' => $year,
                'month' => $month,
                'orders_count' => 0,
                'totems_used' => 0
            ];
            
            $this->insert($data);
            return $data;
        }
        
        return $usage;
    }

    /**
     * Incrementar contador de pedidos
     */
    public function incrementOrderCount($restaurantId, $count = 1)
    {
        $usage = $this->getCurrentMonthUsage($restaurantId);
        
        return $this->where([
            'restaurant_id' => $restaurantId,
            'year' => date('Y'),
            'month' => date('n')
        ])->set('orders_count', 'orders_count + ' . $count, false)->update();
    }

    /**
     * Atualizar número de totems usados
     */
    public function updateTotemsUsed($restaurantId, $count)
    {
        $usage = $this->getCurrentMonthUsage($restaurantId);
        
        return $this->where([
            'restaurant_id' => $restaurantId,
            'year' => date('Y'),
            'month' => date('n')
        ])->set('totems_used', $count)->update();
    }

    /**
     * Obter histórico de uso de um restaurante
     */
    public function getUsageHistory($restaurantId, $months = 12)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->orderBy('year', 'DESC')
                   ->orderBy('month', 'DESC')
                   ->limit($months)
                   ->findAll();
    }

    /**
     * Resetar contadores mensais (executar no início de cada mês)
     */
    public function resetMonthlyCounters()
    {
        // Este método pode ser chamado por um cron job
        // Não precisa fazer nada pois cada mês tem seu próprio registro
        return true;
    }
}