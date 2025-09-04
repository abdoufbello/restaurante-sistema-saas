<?php

namespace App\Models;

use CodeIgniter\Model;

class WebhookModel extends Model
{
    protected $table = 'webhooks';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'gateway_id',
        'event_id',
        'event_type',
        'event_name',
        'source',
        'provider',
        'status',
        'http_method',
        'url',
        'headers',
        'payload',
        'signature',
        'signature_verified',
        'ip_address',
        'user_agent',
        'processed_at',
        'failed_at',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'response_status',
        'response_body',
        'response_time',
        'error_message',
        'related_type',
        'related_id',
        'metadata',
        'notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'gateway_id' => 'permit_empty|integer',
        'event_id' => 'permit_empty|string|max_length[100]',
        'event_type' => 'required|string|max_length[100]',
        'event_name' => 'permit_empty|string|max_length[100]',
        'source' => 'required|in_list[stripe,mercadopago,pagseguro,paypal,cielo,rede,stone,getnet,adyen,square,braintree,razorpay,payu,custom,manual]',
        'provider' => 'required|string|max_length[50]',
        'status' => 'required|in_list[pending,processing,processed,failed,ignored,duplicate]',
        'http_method' => 'required|in_list[GET,POST,PUT,PATCH,DELETE]',
        'url' => 'required|string|max_length[500]',
        'signature_verified' => 'permit_empty|in_list[0,1]',
        'retry_count' => 'permit_empty|integer|greater_than_equal_to[0]',
        'max_retries' => 'permit_empty|integer|greater_than_equal_to[0]',
        'response_status' => 'permit_empty|integer',
        'response_time' => 'permit_empty|integer',
        'related_type' => 'permit_empty|in_list[payment,subscription,invoice,refund,chargeback,customer]',
        'related_id' => 'permit_empty|integer'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'O ID do restaurante é obrigatório.',
            'integer' => 'O ID do restaurante deve ser um número inteiro.'
        ],
        'event_type' => [
            'required' => 'O tipo do evento é obrigatório.',
            'string' => 'O tipo do evento deve ser uma string.',
            'max_length' => 'O tipo do evento não pode ter mais de 100 caracteres.'
        ],
        'source' => [
            'required' => 'A origem é obrigatória.',
            'in_list' => 'Origem inválida.'
        ],
        'provider' => [
            'required' => 'O provedor é obrigatório.',
            'string' => 'O provedor deve ser uma string.',
            'max_length' => 'O provedor não pode ter mais de 50 caracteres.'
        ],
        'status' => [
            'required' => 'O status é obrigatório.',
            'in_list' => 'Status inválido.'
        ],
        'http_method' => [
            'required' => 'O método HTTP é obrigatório.',
            'in_list' => 'Método HTTP inválido.'
        ],
        'url' => [
            'required' => 'A URL é obrigatória.',
            'string' => 'A URL deve ser uma string.',
            'max_length' => 'A URL não pode ter mais de 500 caracteres.'
        ]
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';
    const STATUS_IGNORED = 'ignored';
    const STATUS_DUPLICATE = 'duplicate';

    // Source constants
    const SOURCE_STRIPE = 'stripe';
    const SOURCE_MERCADOPAGO = 'mercadopago';
    const SOURCE_PAGSEGURO = 'pagseguro';
    const SOURCE_PAYPAL = 'paypal';
    const SOURCE_CIELO = 'cielo';
    const SOURCE_REDE = 'rede';
    const SOURCE_STONE = 'stone';
    const SOURCE_GETNET = 'getnet';
    const SOURCE_ADYEN = 'adyen';
    const SOURCE_SQUARE = 'square';
    const SOURCE_BRAINTREE = 'braintree';
    const SOURCE_RAZORPAY = 'razorpay';
    const SOURCE_PAYU = 'payu';
    const SOURCE_CUSTOM = 'custom';
    const SOURCE_MANUAL = 'manual';

    // Related type constants
    const RELATED_PAYMENT = 'payment';
    const RELATED_SUBSCRIPTION = 'subscription';
    const RELATED_INVOICE = 'invoice';
    const RELATED_REFUND = 'refund';
    const RELATED_CHARGEBACK = 'chargeback';
    const RELATED_CUSTOMER = 'customer';

    protected $beforeInsert = ['setDefaults', 'prepareJsonFields'];
    protected $beforeUpdate = ['prepareJsonFields'];
    protected $afterFind = ['parseJsonFields'];

    /**
     * Set defaults before insert
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = self::STATUS_PENDING;
        }

        if (!isset($data['data']['http_method'])) {
            $data['data']['http_method'] = 'POST';
        }

        if (!isset($data['data']['signature_verified'])) {
            $data['data']['signature_verified'] = 0;
        }

        if (!isset($data['data']['retry_count'])) {
            $data['data']['retry_count'] = 0;
        }

        if (!isset($data['data']['max_retries'])) {
            $data['data']['max_retries'] = 3;
        }

        // Set IP address and user agent if available
        if (!isset($data['data']['ip_address']) && isset($_SERVER['REMOTE_ADDR'])) {
            $data['data']['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }

        if (!isset($data['data']['user_agent']) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $data['data']['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        return $data;
    }

    /**
     * Prepare JSON fields before insert/update
     */
    protected function prepareJsonFields(array $data)
    {
        $jsonFields = ['headers', 'payload', 'metadata'];
        
        foreach ($jsonFields as $field) {
            if (isset($data['data'][$field]) && is_array($data['data'][$field])) {
                $data['data'][$field] = json_encode($data['data'][$field]);
            }
        }
        
        return $data;
    }

    /**
     * Parse JSON fields after find
     */
    protected function parseJsonFields(array $data)
    {
        $jsonFields = ['headers', 'payload', 'metadata'];
        
        if (isset($data['data'])) {
            foreach ($jsonFields as $field) {
                if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                    $data['data'][$field] = json_decode($data['data'][$field], true);
                }
            }
        } elseif (is_array($data)) {
            foreach ($data as &$item) {
                if (is_array($item)) {
                    foreach ($jsonFields as $field) {
                        if (isset($item[$field]) && is_string($item[$field])) {
                            $item[$field] = json_decode($item[$field], true);
                        }
                    }
                }
            }
        }
        
        return $data;
    }

    /**
     * Create webhook from request
     */
    public function createFromRequest($restaurantId, $gatewayId, $source, $eventType, $payload, $headers = [], $signature = null)
    {
        $webhookData = [
            'restaurant_id' => $restaurantId,
            'gateway_id' => $gatewayId,
            'event_type' => $eventType,
            'source' => $source,
            'provider' => $source,
            'status' => self::STATUS_PENDING,
            'http_method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'headers' => $headers,
            'payload' => $payload,
            'signature' => $signature,
            'signature_verified' => 0
        ];

        // Extract event ID if available
        if (is_array($payload)) {
            $webhookData['event_id'] = $payload['id'] ?? $payload['event_id'] ?? null;
            $webhookData['event_name'] = $payload['type'] ?? $payload['event'] ?? $eventType;
        }

        return $this->insert($webhookData);
    }

    /**
     * Get webhooks by status
     */
    public function getWebhooksByStatus($status, $limit = null)
    {
        $query = $this->select('webhooks.*, payment_gateways.name as gateway_name')
                     ->join('payment_gateways', 'payment_gateways.id = webhooks.gateway_id', 'left')
                     ->where('webhooks.status', $status)
                     ->orderBy('webhooks.created_at', 'ASC');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->findAll();
    }

    /**
     * Get pending webhooks for processing
     */
    public function getPendingWebhooks($limit = 50)
    {
        return $this->getWebhooksByStatus(self::STATUS_PENDING, $limit);
    }

    /**
     * Get failed webhooks for retry
     */
    public function getFailedWebhooksForRetry($limit = 50)
    {
        return $this->select('webhooks.*, payment_gateways.name as gateway_name')
                   ->join('payment_gateways', 'payment_gateways.id = webhooks.gateway_id', 'left')
                   ->where('webhooks.status', self::STATUS_FAILED)
                   ->where('webhooks.retry_count <', 'webhooks.max_retries', false)
                   ->where('webhooks.next_retry_at <=', date('Y-m-d H:i:s'))
                   ->orderBy('webhooks.next_retry_at', 'ASC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Get webhooks by source
     */
    public function getWebhooksBySource($source, $limit = 50, $offset = 0)
    {
        return $this->select('webhooks.*, payment_gateways.name as gateway_name')
                   ->join('payment_gateways', 'payment_gateways.id = webhooks.gateway_id', 'left')
                   ->where('webhooks.source', $source)
                   ->orderBy('webhooks.created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Get webhooks by event type
     */
    public function getWebhooksByEventType($eventType, $limit = 50, $offset = 0)
    {
        return $this->select('webhooks.*, payment_gateways.name as gateway_name')
                   ->join('payment_gateways', 'payment_gateways.id = webhooks.gateway_id', 'left')
                   ->where('webhooks.event_type', $eventType)
                   ->orderBy('webhooks.created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Get webhooks for restaurant
     */
    public function getRestaurantWebhooks($restaurantId, $limit = 50, $offset = 0)
    {
        return $this->select('webhooks.*, payment_gateways.name as gateway_name')
                   ->join('payment_gateways', 'payment_gateways.id = webhooks.gateway_id', 'left')
                   ->where('webhooks.restaurant_id', $restaurantId)
                   ->orderBy('webhooks.created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Check if webhook is duplicate
     */
    public function isDuplicate($eventId, $source, $eventType)
    {
        if (empty($eventId)) {
            return false;
        }

        return $this->where('event_id', $eventId)
                   ->where('source', $source)
                   ->where('event_type', $eventType)
                   ->countAllResults() > 0;
    }

    /**
     * Mark webhook as duplicate
     */
    public function markAsDuplicate($webhookId)
    {
        return $this->update($webhookId, [
            'status' => self::STATUS_DUPLICATE
        ]);
    }

    /**
     * Start processing webhook
     */
    public function startProcessing($webhookId)
    {
        return $this->update($webhookId, [
            'status' => self::STATUS_PROCESSING
        ]);
    }

    /**
     * Mark webhook as processed
     */
    public function markAsProcessed($webhookId, $relatedType = null, $relatedId = null, $responseData = [])
    {
        $updateData = [
            'status' => self::STATUS_PROCESSED,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        if ($relatedType && $relatedId) {
            $updateData['related_type'] = $relatedType;
            $updateData['related_id'] = $relatedId;
        }

        if (!empty($responseData['status'])) {
            $updateData['response_status'] = $responseData['status'];
        }

        if (!empty($responseData['body'])) {
            $updateData['response_body'] = $responseData['body'];
        }

        if (!empty($responseData['time'])) {
            $updateData['response_time'] = $responseData['time'];
        }

        return $this->update($webhookId, $updateData);
    }

    /**
     * Mark webhook as failed
     */
    public function markAsFailed($webhookId, $errorMessage = null, $responseData = [])
    {
        $webhook = $this->find($webhookId);
        if (!$webhook) {
            return false;
        }

        $retryCount = ($webhook['retry_count'] ?? 0) + 1;
        $maxRetries = $webhook['max_retries'] ?? 3;

        $updateData = [
            'status' => self::STATUS_FAILED,
            'failed_at' => date('Y-m-d H:i:s'),
            'retry_count' => $retryCount,
            'error_message' => $errorMessage
        ];

        // Set next retry time if not exceeded max retries
        if ($retryCount < $maxRetries) {
            $retryDelayMinutes = pow(2, $retryCount) * 5; // Exponential backoff: 5, 10, 20 minutes
            $updateData['next_retry_at'] = date('Y-m-d H:i:s', strtotime("+{$retryDelayMinutes} minutes"));
        }

        if (!empty($responseData['status'])) {
            $updateData['response_status'] = $responseData['status'];
        }

        if (!empty($responseData['body'])) {
            $updateData['response_body'] = $responseData['body'];
        }

        if (!empty($responseData['time'])) {
            $updateData['response_time'] = $responseData['time'];
        }

        return $this->update($webhookId, $updateData);
    }

    /**
     * Mark webhook as ignored
     */
    public function markAsIgnored($webhookId, $reason = null)
    {
        $updateData = [
            'status' => self::STATUS_IGNORED
        ];

        if ($reason) {
            $updateData['notes'] = $reason;
        }

        return $this->update($webhookId, $updateData);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature($webhookId, $isVerified = true)
    {
        return $this->update($webhookId, [
            'signature_verified' => $isVerified ? 1 : 0
        ]);
    }

    /**
     * Retry webhook processing
     */
    public function retryWebhook($webhookId)
    {
        $webhook = $this->find($webhookId);
        if (!$webhook || $webhook['status'] !== self::STATUS_FAILED) {
            return false;
        }

        if ($webhook['retry_count'] >= $webhook['max_retries']) {
            return false; // Max retries exceeded
        }

        return $this->update($webhookId, [
            'status' => self::STATUS_PENDING,
            'next_retry_at' => null,
            'error_message' => null
        ]);
    }

    /**
     * Advanced search for webhooks
     */
    public function searchWebhooks($filters = [], $limit = 50, $offset = 0)
    {
        $query = $this->select('webhooks.*, payment_gateways.name as gateway_name')
                     ->join('payment_gateways', 'payment_gateways.id = webhooks.gateway_id', 'left');

        // Apply filters
        if (!empty($filters['restaurant_id'])) {
            $query->where('webhooks.restaurant_id', $filters['restaurant_id']);
        }

        if (!empty($filters['gateway_id'])) {
            $query->where('webhooks.gateway_id', $filters['gateway_id']);
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('webhooks.status', $filters['status']);
            } else {
                $query->where('webhooks.status', $filters['status']);
            }
        }

        if (!empty($filters['source'])) {
            if (is_array($filters['source'])) {
                $query->whereIn('webhooks.source', $filters['source']);
            } else {
                $query->where('webhooks.source', $filters['source']);
            }
        }

        if (!empty($filters['event_type'])) {
            $query->where('webhooks.event_type', $filters['event_type']);
        }

        if (!empty($filters['event_id'])) {
            $query->where('webhooks.event_id', $filters['event_id']);
        }

        if (!empty($filters['related_type'])) {
            $query->where('webhooks.related_type', $filters['related_type']);
        }

        if (!empty($filters['related_id'])) {
            $query->where('webhooks.related_id', $filters['related_id']);
        }

        if (isset($filters['signature_verified'])) {
            $query->where('webhooks.signature_verified', $filters['signature_verified']);
        }

        if (!empty($filters['created_from'])) {
            $query->where('webhooks.created_at >=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->where('webhooks.created_at <=', $filters['created_to']);
        }

        if (!empty($filters['processed_from'])) {
            $query->where('webhooks.processed_at >=', $filters['processed_from']);
        }

        if (!empty($filters['processed_to'])) {
            $query->where('webhooks.processed_at <=', $filters['processed_to']);
        }

        if (!empty($filters['search'])) {
            $query->groupStart()
                  ->like('webhooks.event_id', $filters['search'])
                  ->orLike('webhooks.event_type', $filters['search'])
                  ->orLike('webhooks.event_name', $filters['search'])
                  ->orLike('payment_gateways.name', $filters['search'])
                  ->groupEnd();
        }

        return $query->orderBy('webhooks.created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get webhook statistics
     */
    public function getWebhookStats($period = '30 days', $restaurantId = null)
    {
        $stats = [];
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));

        $query = $this;
        if ($restaurantId) {
            $query = $query->where('restaurant_id', $restaurantId);
        }

        // Total webhooks
        $stats['total'] = $query->countAllResults(false);

        // By status
        $statusCounts = $query->select('status, COUNT(*) as count')
                             ->groupBy('status')
                             ->findAll();
        
        foreach ($statusCounts as $status) {
            $stats['by_status'][$status['status']] = $status['count'];
        }

        // By source
        $sourceCounts = $this->select('source, COUNT(*) as count')
                           ->groupBy('source');
        
        if ($restaurantId) {
            $sourceCounts->where('restaurant_id', $restaurantId);
        }
        
        $sourceCounts = $sourceCounts->findAll();
        
        foreach ($sourceCounts as $source) {
            $stats['by_source'][$source['source']] = $source['count'];
        }

        // By event type
        $eventCounts = $this->select('event_type, COUNT(*) as count')
                          ->groupBy('event_type');
        
        if ($restaurantId) {
            $eventCounts->where('restaurant_id', $restaurantId);
        }
        
        $eventCounts = $eventCounts->findAll();
        
        foreach ($eventCounts as $event) {
            $stats['by_event_type'][$event['event_type']] = $event['count'];
        }

        // In period
        $periodQuery = $this->where('created_at >=', $dateFrom);
        if ($restaurantId) {
            $periodQuery->where('restaurant_id', $restaurantId);
        }
        $stats['in_period'] = $periodQuery->countAllResults();

        // Success rate
        $processedCount = $this->where('status', self::STATUS_PROCESSED);
        if ($restaurantId) {
            $processedCount->where('restaurant_id', $restaurantId);
        }
        $processedCount = $processedCount->countAllResults();
        
        $stats['success_rate'] = $stats['total'] > 0 ? ($processedCount / $stats['total']) * 100 : 0;

        // Average processing time
        $avgQuery = $this->select('AVG(response_time) as avg_time')
                        ->where('status', self::STATUS_PROCESSED)
                        ->where('response_time IS NOT NULL');
        
        if ($restaurantId) {
            $avgQuery->where('restaurant_id', $restaurantId);
        }
        
        $avgResult = $avgQuery->first();
        $stats['average_processing_time'] = $avgResult['avg_time'] ?? 0;

        // Failed webhooks needing retry
        $failedQuery = $this->where('status', self::STATUS_FAILED)
                          ->where('retry_count <', 'max_retries', false);
        
        if ($restaurantId) {
            $failedQuery->where('restaurant_id', $restaurantId);
        }
        
        $stats['failed_needing_retry'] = $failedQuery->countAllResults();

        // Signature verification rate
        $verifiedQuery = $this->where('signature_verified', 1);
        if ($restaurantId) {
            $verifiedQuery->where('restaurant_id', $restaurantId);
        }
        $verifiedCount = $verifiedQuery->countAllResults();
        
        $stats['signature_verification_rate'] = $stats['total'] > 0 ? ($verifiedCount / $stats['total']) * 100 : 0;

        return $stats;
    }

    /**
     * Export webhooks to CSV
     */
    public function exportToCSV($filters = [])
    {
        $webhooks = $this->searchWebhooks($filters, 10000);
        
        $csvData = [];
        $csvData[] = [
            'ID do Evento', 'Tipo do Evento', 'Nome do Evento', 'Origem', 'Gateway',
            'Status', 'Método HTTP', 'Assinatura Verificada', 'Tentativas',
            'Tempo de Resposta', 'Status da Resposta', 'Processado em', 'Falhado em', 'Criado em'
        ];
        
        foreach ($webhooks as $webhook) {
            $csvData[] = [
                $webhook['event_id'] ?? '',
                $webhook['event_type'],
                $webhook['event_name'] ?? '',
                $webhook['source'],
                $webhook['gateway_name'] ?? '',
                $webhook['status'],
                $webhook['http_method'],
                $webhook['signature_verified'] ? 'Sim' : 'Não',
                $webhook['retry_count'] ?? 0,
                $webhook['response_time'] ? $webhook['response_time'] . 'ms' : '',
                $webhook['response_status'] ?? '',
                $webhook['processed_at'] ? date('d/m/Y H:i', strtotime($webhook['processed_at'])) : '',
                $webhook['failed_at'] ? date('d/m/Y H:i', strtotime($webhook['failed_at'])) : '',
                date('d/m/Y H:i', strtotime($webhook['created_at']))
            ];
        }
        
        return $csvData;
    }

    /**
     * Get webhook processing report
     */
    public function getProcessingReport($days = 7, $restaurantId = null)
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        
        $query = $this->select('DATE(created_at) as date, status, COUNT(*) as count')
                     ->where('created_at >=', $dateFrom)
                     ->groupBy('DATE(created_at), status')
                     ->orderBy('date', 'ASC');
        
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        
        $results = $query->findAll();
        
        $report = [];
        foreach ($results as $result) {
            $date = $result['date'];
            if (!isset($report[$date])) {
                $report[$date] = [
                    'date' => $date,
                    'total' => 0,
                    'processed' => 0,
                    'failed' => 0,
                    'pending' => 0,
                    'ignored' => 0,
                    'duplicate' => 0
                ];
            }
            
            $report[$date]['total'] += $result['count'];
            $report[$date][$result['status']] = $result['count'];
        }
        
        return array_values($report);
    }

    /**
     * Clean old webhooks
     */
    public function cleanOldWebhooks($days = 90)
    {
        $dateLimit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->where('created_at <', $dateLimit)
                   ->whereIn('status', [self::STATUS_PROCESSED, self::STATUS_IGNORED, self::STATUS_DUPLICATE])
                   ->delete();
    }
}