<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentWebhook extends Model
{
    protected $table = 'payment_webhooks';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'gateway_type',
        'transaction_id',
        'event_type',
        'payload',
        'headers',
        'processed',
        'processed_at',
        'error_message'
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
        'gateway_type' => 'required|in_list[pix,stripe,pagseguro,mercadopago,paypal,picpay,nubank]',
        'event_type' => 'required|string|max_length[50]',
        'payload' => 'required'
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
        'event_type' => [
            'required' => 'Tipo de evento é obrigatório',
            'max_length' => 'Tipo de evento muito longo'
        ],
        'payload' => [
            'required' => 'Payload é obrigatório'
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
    protected $afterFind = ['decodeJsonFields'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Decode JSON fields after find
     */
    protected function decodeJsonFields(array $data)
    {
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                foreach ($data['data'] as &$row) {
                    $this->decodeJsonRow($row);
                }
            } else {
                $this->decodeJsonRow($data['data']);
            }
        }
        
        return $data;
    }

    /**
     * Decode JSON fields for a single row
     */
    private function decodeJsonRow(&$row)
    {
        if (isset($row['payload']) && is_string($row['payload'])) {
            $row['payload'] = json_decode($row['payload'], true);
        }
        
        if (isset($row['headers']) && is_string($row['headers'])) {
            $row['headers'] = json_decode($row['headers'], true);
        }
    }

    /**
     * Buscar webhooks por restaurante
     */
    public function getByRestaurant($restaurantId, $limit = 50, $offset = 0)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll($limit, $offset);
    }

    /**
     * Buscar webhooks não processados
     */
    public function getUnprocessed($restaurantId = null, $limit = 100)
    {
        $builder = $this->where('processed', false);
        
        if ($restaurantId) {
            $builder->where('restaurant_id', $restaurantId);
        }
        
        return $builder->orderBy('created_at', 'ASC')
                      ->findAll($limit);
    }

    /**
     * Buscar webhooks por gateway
     */
    public function getByGateway($restaurantId, $gatewayType, $limit = 50, $offset = 0)
    {
        return $this->where('restaurant_id', $restaurantId)
                   ->where('gateway_type', $gatewayType)
                   ->orderBy('created_at', 'DESC')
                   ->findAll($limit, $offset);
    }

    /**
     * Buscar webhooks por transação
     */
    public function getByTransaction($transactionId)
    {
        return $this->where('transaction_id', $transactionId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Marcar webhook como processado
     */
    public function markAsProcessed($id, $errorMessage = null)
    {
        $data = [
            'processed' => true,
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($errorMessage) {
            $data['error_message'] = $errorMessage;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Obter estatísticas de webhooks
     */
    public function getStats($restaurantId, $days = 30)
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Total de webhooks
        $total = $this->where('restaurant_id', $restaurantId)
                     ->where('created_at >=', $startDate)
                     ->countAllResults();
        
        // Webhooks processados
        $processed = $this->where('restaurant_id', $restaurantId)
                         ->where('created_at >=', $startDate)
                         ->where('processed', true)
                         ->countAllResults();
        
        // Webhooks com erro
        $errors = $this->where('restaurant_id', $restaurantId)
                      ->where('created_at >=', $startDate)
                      ->where('error_message IS NOT NULL')
                      ->countAllResults();
        
        // Por gateway
        $byGateway = $this->select('gateway_type, COUNT(*) as total')
                         ->where('restaurant_id', $restaurantId)
                         ->where('created_at >=', $startDate)
                         ->groupBy('gateway_type')
                         ->findAll();
        
        // Por tipo de evento
        $byEventType = $this->select('event_type, COUNT(*) as total')
                           ->where('restaurant_id', $restaurantId)
                           ->where('created_at >=', $startDate)
                           ->groupBy('event_type')
                           ->findAll();
        
        return [
            'total' => $total,
            'processed' => $processed,
            'pending' => $total - $processed,
            'errors' => $errors,
            'success_rate' => $total > 0 ? round(($processed / $total) * 100, 2) : 0,
            'by_gateway' => $byGateway,
            'by_event_type' => $byEventType
        ];
    }

    /**
     * Limpar webhooks antigos
     */
    public function cleanOldWebhooks($days = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->where('created_at <', $cutoffDate)
                   ->where('processed', true)
                   ->delete();
    }

    /**
     * Reprocessar webhooks com erro
     */
    public function getFailedWebhooks($restaurantId = null, $limit = 50)
    {
        $builder = $this->where('processed', true)
                       ->where('error_message IS NOT NULL');
        
        if ($restaurantId) {
            $builder->where('restaurant_id', $restaurantId);
        }
        
        return $builder->orderBy('created_at', 'DESC')
                      ->findAll($limit);
    }

    /**
     * Resetar webhook para reprocessamento
     */
    public function resetForReprocessing($id)
    {
        return $this->update($id, [
            'processed' => false,
            'processed_at' => null,
            'error_message' => null
        ]);
    }
}