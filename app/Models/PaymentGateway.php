<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentGateway extends Model
{
    protected $table = 'payment_gateways';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'gateway_type',
        'is_active',
        'credentials',
        'webhook_url',
        'settings',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'gateway_type' => 'required|in_list[pix,stripe,pagseguro,mercadopago,paypal]',
        'is_active' => 'required|boolean',
        'credentials' => 'required'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'integer' => 'ID do restaurante deve ser um número'
        ],
        'gateway_type' => [
            'required' => 'Tipo de gateway é obrigatório',
            'in_list' => 'Tipo de gateway inválido'
        ],
        'is_active' => [
            'required' => 'Status ativo é obrigatório',
            'boolean' => 'Status ativo deve ser verdadeiro ou falso'
        ],
        'credentials' => [
            'required' => 'Credenciais são obrigatórias'
        ]
    ];

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
     * Busca gateways ativos por restaurante
     */
    public function getActiveGateways($restaurantId)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('is_active', 1)
                   ->findAll();
    }

    /**
     * Busca gateway específico por tipo e restaurante
     */
    public function getGatewayByType($restaurantId, $gatewayType)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('gateway_type', $gatewayType)
                   ->first();
    }

    /**
     * Ativa/desativa um gateway
     */
    public function toggleGateway($id, $isActive)
    {
        return $this->update($id, [
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Atualiza configurações do gateway
     */
    public function updateSettings($id, $settings)
    {
        return $this->update($id, [
            'settings' => json_encode($settings),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verifica se gateway está configurado e ativo
     */
    public function isGatewayActive($restaurantId, $gatewayType)
    {
        $gateway = $this->getGatewayByType($restaurantId, $gatewayType);
        return $gateway && $gateway['is_active'] == 1;
    }

    /**
     * Lista gateways com estatísticas
     */
    public function getGatewaysWithStats($restaurantId)
    {
        $builder = $this->db->table($this->table . ' pg')
                           ->select('pg.*, 
                                   COUNT(pt.id) as total_transactions,
                                   SUM(CASE WHEN pt.status = "completed" THEN pt.amount ELSE 0 END) as total_processed,
                                   AVG(CASE WHEN pt.status = "completed" THEN pt.amount ELSE NULL END) as avg_transaction')
                           ->join('payment_transactions pt', 'pt.gateway_type = pg.gateway_type AND pt.restaurant_id = pg.restaurant_id', 'left')
                           ->where('pg.restaurant_id', $restaurantId)
                           ->groupBy('pg.id');

        return $builder->get()->getResultArray();
    }
}