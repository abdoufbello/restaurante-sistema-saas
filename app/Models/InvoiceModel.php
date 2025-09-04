<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table = 'invoices';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'subscription_id',
        'user_id',
        'invoice_number',
        'status',
        'type',
        'billing_period_start',
        'billing_period_end',
        'due_date',
        'paid_at',
        'currency',
        'subtotal',
        'tax_amount',
        'tax_percentage',
        'discount_amount',
        'discount_percentage',
        'total_amount',
        'payment_method',
        'payment_gateway',
        'payment_gateway_id',
        'gateway_transaction_id',
        'gateway_invoice_id',
        'payment_attempts',
        'last_payment_attempt',
        'next_payment_attempt',
        'failure_reason',
        'notes',
        'metadata',
        'tags',
        'pdf_path',
        'sent_at',
        'viewed_at',
        'download_count'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'subscription_id' => 'permit_empty|integer',
        'user_id' => 'permit_empty|integer',
        'invoice_number' => 'required|string|max_length[50]',
        'status' => 'required|in_list[draft,pending,paid,overdue,canceled,refunded,failed]',
        'type' => 'required|in_list[subscription,one_time,setup,upgrade,downgrade,refund]',
        'billing_period_start' => 'permit_empty|valid_date',
        'billing_period_end' => 'permit_empty|valid_date',
        'due_date' => 'required|valid_date',
        'currency' => 'required|string|max_length[3]',
        'subtotal' => 'required|decimal',
        'tax_amount' => 'permit_empty|decimal',
        'tax_percentage' => 'permit_empty|decimal',
        'discount_amount' => 'permit_empty|decimal',
        'discount_percentage' => 'permit_empty|decimal',
        'total_amount' => 'required|decimal',
        'payment_method' => 'permit_empty|string|max_length[50]',
        'payment_gateway' => 'permit_empty|string|max_length[50]',
        'payment_attempts' => 'permit_empty|integer',
        'download_count' => 'permit_empty|integer'
    ];

    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'O ID do restaurante é obrigatório.',
            'integer' => 'O ID do restaurante deve ser um número inteiro.'
        ],
        'invoice_number' => [
            'required' => 'O número da fatura é obrigatório.',
            'string' => 'O número da fatura deve ser uma string.',
            'max_length' => 'O número da fatura não pode ter mais de 50 caracteres.'
        ],
        'status' => [
            'required' => 'O status da fatura é obrigatório.',
            'in_list' => 'Status inválido para a fatura.'
        ],
        'type' => [
            'required' => 'O tipo da fatura é obrigatório.',
            'in_list' => 'Tipo inválido para a fatura.'
        ],
        'due_date' => [
            'required' => 'A data de vencimento é obrigatória.',
            'valid_date' => 'A data de vencimento deve ser uma data válida.'
        ],
        'currency' => [
            'required' => 'A moeda é obrigatória.',
            'string' => 'A moeda deve ser uma string.',
            'max_length' => 'A moeda não pode ter mais de 3 caracteres.'
        ],
        'subtotal' => [
            'required' => 'O subtotal é obrigatório.',
            'decimal' => 'O subtotal deve ser um valor decimal.'
        ],
        'total_amount' => [
            'required' => 'O valor total é obrigatório.',
            'decimal' => 'O valor total deve ser um valor decimal.'
        ]
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_FAILED = 'failed';

    // Type constants
    const TYPE_SUBSCRIPTION = 'subscription';
    const TYPE_ONE_TIME = 'one_time';
    const TYPE_SETUP = 'setup';
    const TYPE_UPGRADE = 'upgrade';
    const TYPE_DOWNGRADE = 'downgrade';
    const TYPE_REFUND = 'refund';

    protected $beforeInsert = ['setDefaults', 'generateInvoiceNumber', 'calculateAmounts', 'prepareJsonFields'];
    protected $beforeUpdate = ['updateTimestamps', 'calculateAmounts', 'prepareJsonFields'];
    protected $afterFind = ['parseJsonFields'];

    /**
     * Set defaults before insert
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = self::STATUS_DRAFT;
        }

        if (!isset($data['data']['type'])) {
            $data['data']['type'] = self::TYPE_SUBSCRIPTION;
        }

        if (!isset($data['data']['currency'])) {
            $data['data']['currency'] = 'BRL';
        }

        if (!isset($data['data']['payment_attempts'])) {
            $data['data']['payment_attempts'] = 0;
        }

        if (!isset($data['data']['download_count'])) {
            $data['data']['download_count'] = 0;
        }

        if (!isset($data['data']['due_date'])) {
            $data['data']['due_date'] = date('Y-m-d H:i:s', strtotime('+30 days'));
        }

        return $data;
    }

    /**
     * Generate invoice number before insert
     */
    protected function generateInvoiceNumber(array $data)
    {
        if (!isset($data['data']['invoice_number'])) {
            $year = date('Y');
            $month = date('m');
            
            // Get the last invoice number for this month
            $lastInvoice = $this->select('invoice_number')
                               ->like('invoice_number', "INV-{$year}{$month}-", 'after')
                               ->orderBy('id', 'DESC')
                               ->first();
            
            $sequence = 1;
            if ($lastInvoice) {
                $lastNumber = str_replace("INV-{$year}{$month}-", '', $lastInvoice['invoice_number']);
                $sequence = intval($lastNumber) + 1;
            }
            
            $data['data']['invoice_number'] = sprintf('INV-%s%s-%04d', $year, $month, $sequence);
        }

        return $data;
    }

    /**
     * Calculate amounts before insert/update
     */
    protected function calculateAmounts(array $data)
    {
        if (isset($data['data']['subtotal'])) {
            $subtotal = $data['data']['subtotal'];
            $discountAmount = $data['data']['discount_amount'] ?? 0;
            $discountPercentage = $data['data']['discount_percentage'] ?? 0;
            $taxPercentage = $data['data']['tax_percentage'] ?? 0;

            // Calculate discount
            if ($discountPercentage > 0) {
                $discountAmount = $subtotal * ($discountPercentage / 100);
                $data['data']['discount_amount'] = $discountAmount;
            }

            // Calculate tax
            $taxableAmount = $subtotal - $discountAmount;
            if ($taxPercentage > 0) {
                $taxAmount = $taxableAmount * ($taxPercentage / 100);
                $data['data']['tax_amount'] = $taxAmount;
            } else {
                $taxAmount = $data['data']['tax_amount'] ?? 0;
            }

            // Calculate total
            $data['data']['total_amount'] = $taxableAmount + $taxAmount;
        }

        return $data;
    }

    /**
     * Prepare JSON fields before insert/update
     */
    protected function prepareJsonFields(array $data)
    {
        $jsonFields = ['metadata', 'tags'];
        
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
        $jsonFields = ['metadata', 'tags'];
        
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
     * Update timestamps before update
     */
    protected function updateTimestamps(array $data)
    {
        return $data;
    }

    /**
     * Get invoices by status
     */
    public function getInvoicesByStatus($status, $limit = null)
    {
        $query = $this->select('invoices.*, restaurants.name as restaurant_name, subscriptions.id as subscription_id')
                     ->join('restaurants', 'restaurants.id = invoices.restaurant_id', 'left')
                     ->join('subscriptions', 'subscriptions.id = invoices.subscription_id', 'left')
                     ->where('invoices.status', $status)
                     ->orderBy('invoices.created_at', 'DESC');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->findAll();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices()
    {
        return $this->select('invoices.*, restaurants.name as restaurant_name')
                   ->join('restaurants', 'restaurants.id = invoices.restaurant_id')
                   ->where('invoices.status', self::STATUS_PENDING)
                   ->where('invoices.due_date <', date('Y-m-d H:i:s'))
                   ->orderBy('invoices.due_date', 'ASC')
                   ->findAll();
    }

    /**
     * Get invoices due soon
     */
    public function getInvoicesDueSoon($days = 7)
    {
        $dueDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        return $this->select('invoices.*, restaurants.name as restaurant_name')
                   ->join('restaurants', 'restaurants.id = invoices.restaurant_id')
                   ->where('invoices.status', self::STATUS_PENDING)
                   ->where('invoices.due_date <=', $dueDate)
                   ->where('invoices.due_date >=', date('Y-m-d H:i:s'))
                   ->orderBy('invoices.due_date', 'ASC')
                   ->findAll();
    }

    /**
     * Get invoices for restaurant
     */
    public function getRestaurantInvoices($restaurantId, $limit = 50, $offset = 0)
    {
        return $this->select('invoices.*, subscriptions.id as subscription_id')
                   ->join('subscriptions', 'subscriptions.id = invoices.subscription_id', 'left')
                   ->where('invoices.restaurant_id', $restaurantId)
                   ->orderBy('invoices.created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Create invoice from subscription
     */
    public function createFromSubscription($subscriptionId, $billingPeriodStart = null, $billingPeriodEnd = null)
    {
        $subscriptionModel = new \App\Models\SubscriptionModel();
        $subscription = $subscriptionModel->find($subscriptionId);
        
        if (!$subscription) {
            return false;
        }

        $planModel = new \App\Models\PlanModel();
        $plan = $planModel->find($subscription['plan_id']);
        
        if (!$plan) {
            return false;
        }

        // Calculate billing period if not provided
        if (!$billingPeriodStart) {
            $billingPeriodStart = $subscription['starts_at'];
        }
        
        if (!$billingPeriodEnd) {
            $interval = match($subscription['billing_cycle']) {
                'weekly' => '+1 week',
                'monthly' => '+1 month',
                'quarterly' => '+3 months',
                'yearly' => '+1 year',
                default => '+1 month'
            };
            $billingPeriodEnd = date('Y-m-d H:i:s', strtotime($interval, strtotime($billingPeriodStart)));
        }

        $invoiceData = [
            'restaurant_id' => $subscription['restaurant_id'],
            'subscription_id' => $subscriptionId,
            'user_id' => $subscription['user_id'],
            'status' => self::STATUS_PENDING,
            'type' => self::TYPE_SUBSCRIPTION,
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'currency' => $subscription['currency'],
            'subtotal' => $subscription['amount'],
            'tax_percentage' => $subscription['tax_percentage'] ?? 0,
            'discount_percentage' => $subscription['discount_percentage'] ?? 0,
            'payment_gateway' => $subscription['payment_gateway'],
            'metadata' => [
                'subscription_id' => $subscriptionId,
                'plan_name' => $plan['name'],
                'billing_cycle' => $subscription['billing_cycle']
            ]
        ];

        return $this->insert($invoiceData);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid($invoiceId, $paymentData = [])
    {
        $updateData = [
            'status' => self::STATUS_PAID,
            'paid_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($paymentData['payment_method'])) {
            $updateData['payment_method'] = $paymentData['payment_method'];
        }

        if (!empty($paymentData['gateway_transaction_id'])) {
            $updateData['gateway_transaction_id'] = $paymentData['gateway_transaction_id'];
        }

        if (!empty($paymentData['payment_gateway'])) {
            $updateData['payment_gateway'] = $paymentData['payment_gateway'];
        }

        return $this->update($invoiceId, $updateData);
    }

    /**
     * Mark invoice as failed
     */
    public function markAsFailed($invoiceId, $reason = null)
    {
        $updateData = [
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'last_payment_attempt' => date('Y-m-d H:i:s')
        ];

        // Increment payment attempts
        $invoice = $this->find($invoiceId);
        if ($invoice) {
            $updateData['payment_attempts'] = ($invoice['payment_attempts'] ?? 0) + 1;
            
            // Set next payment attempt (24 hours later)
            $updateData['next_payment_attempt'] = date('Y-m-d H:i:s', strtotime('+24 hours'));
        }

        return $this->update($invoiceId, $updateData);
    }

    /**
     * Cancel invoice
     */
    public function cancelInvoice($invoiceId, $reason = null)
    {
        $updateData = [
            'status' => self::STATUS_CANCELED
        ];

        if ($reason) {
            $updateData['notes'] = $reason;
        }

        return $this->update($invoiceId, $updateData);
    }

    /**
     * Refund invoice
     */
    public function refundInvoice($invoiceId, $refundAmount = null, $reason = null)
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice || $invoice['status'] !== self::STATUS_PAID) {
            return false;
        }

        $updateData = [
            'status' => self::STATUS_REFUNDED,
            'metadata' => array_merge($invoice['metadata'] ?? [], [
                'refund' => [
                    'amount' => $refundAmount ?? $invoice['total_amount'],
                    'reason' => $reason,
                    'refunded_at' => date('Y-m-d H:i:s')
                ]
            ])
        ];

        return $this->update($invoiceId, $updateData);
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent($invoiceId)
    {
        return $this->update($invoiceId, [
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Mark invoice as viewed
     */
    public function markAsViewed($invoiceId)
    {
        return $this->update($invoiceId, [
            'viewed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount($invoiceId)
    {
        $invoice = $this->find($invoiceId);
        if ($invoice) {
            return $this->update($invoiceId, [
                'download_count' => ($invoice['download_count'] ?? 0) + 1
            ]);
        }
        return false;
    }

    /**
     * Advanced search for invoices
     */
    public function searchInvoices($filters = [], $limit = 50, $offset = 0)
    {
        $query = $this->select('invoices.*, restaurants.name as restaurant_name, subscriptions.id as subscription_id')
                     ->join('restaurants', 'restaurants.id = invoices.restaurant_id', 'left')
                     ->join('subscriptions', 'subscriptions.id = invoices.subscription_id', 'left');

        // Apply filters
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('invoices.status', $filters['status']);
            } else {
                $query->where('invoices.status', $filters['status']);
            }
        }

        if (!empty($filters['type'])) {
            $query->where('invoices.type', $filters['type']);
        }

        if (!empty($filters['restaurant_id'])) {
            $query->where('invoices.restaurant_id', $filters['restaurant_id']);
        }

        if (!empty($filters['subscription_id'])) {
            $query->where('invoices.subscription_id', $filters['subscription_id']);
        }

        if (!empty($filters['payment_gateway'])) {
            $query->where('invoices.payment_gateway', $filters['payment_gateway']);
        }

        if (!empty($filters['created_from'])) {
            $query->where('invoices.created_at >=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->where('invoices.created_at <=', $filters['created_to']);
        }

        if (!empty($filters['due_from'])) {
            $query->where('invoices.due_date >=', $filters['due_from']);
        }

        if (!empty($filters['due_to'])) {
            $query->where('invoices.due_date <=', $filters['due_to']);
        }

        if (!empty($filters['amount_min'])) {
            $query->where('invoices.total_amount >=', $filters['amount_min']);
        }

        if (!empty($filters['amount_max'])) {
            $query->where('invoices.total_amount <=', $filters['amount_max']);
        }

        if (!empty($filters['search'])) {
            $query->groupStart()
                  ->like('invoices.invoice_number', $filters['search'])
                  ->orLike('restaurants.name', $filters['search'])
                  ->orLike('invoices.gateway_transaction_id', $filters['search'])
                  ->groupEnd();
        }

        return $query->orderBy('invoices.created_at', 'DESC')
                    ->limit($limit, $offset)
                    ->findAll();
    }

    /**
     * Get invoice statistics
     */
    public function getInvoiceStats($period = '30 days')
    {
        $stats = [];
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));

        // Total invoices
        $stats['total'] = $this->countAllResults();

        // By status
        $statusCounts = $this->select('status, COUNT(*) as count, SUM(total_amount) as amount')
                           ->groupBy('status')
                           ->findAll();
        
        foreach ($statusCounts as $status) {
            $stats['by_status'][$status['status']] = [
                'count' => $status['count'],
                'amount' => $status['amount']
            ];
        }

        // Revenue in period
        $revenueQuery = $this->select('SUM(total_amount) as revenue')
                           ->where('status', self::STATUS_PAID)
                           ->where('paid_at >=', $dateFrom)
                           ->first();
        $stats['revenue_in_period'] = $revenueQuery['revenue'] ?? 0;

        // Overdue invoices
        $stats['overdue_count'] = $this->where('status', self::STATUS_PENDING)
                                      ->where('due_date <', date('Y-m-d H:i:s'))
                                      ->countAllResults();

        $overdueAmount = $this->select('SUM(total_amount) as amount')
                             ->where('status', self::STATUS_PENDING)
                             ->where('due_date <', date('Y-m-d H:i:s'))
                             ->first();
        $stats['overdue_amount'] = $overdueAmount['amount'] ?? 0;

        // Average invoice value
        $avgQuery = $this->select('AVG(total_amount) as avg_amount')
                        ->where('status', self::STATUS_PAID)
                        ->first();
        $stats['average_invoice_value'] = $avgQuery['avg_amount'] ?? 0;

        // Payment success rate
        $totalInvoices = $this->whereIn('status', [self::STATUS_PAID, self::STATUS_FAILED, self::STATUS_OVERDUE])
                             ->countAllResults();
        $paidInvoices = $this->where('status', self::STATUS_PAID)->countAllResults();
        $stats['payment_success_rate'] = $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;

        return $stats;
    }

    /**
     * Export invoices to CSV
     */
    public function exportToCSV($filters = [])
    {
        $invoices = $this->searchInvoices($filters, 10000);
        
        $csvData = [];
        $csvData[] = [
            'Número da Fatura', 'Restaurante', 'Status', 'Tipo', 'Período de Cobrança',
            'Data de Vencimento', 'Subtotal', 'Desconto', 'Imposto', 'Total',
            'Método de Pagamento', 'Gateway', 'Data de Pagamento', 'Criado em'
        ];
        
        foreach ($invoices as $invoice) {
            $billingPeriod = '';
            if ($invoice['billing_period_start'] && $invoice['billing_period_end']) {
                $billingPeriod = date('d/m/Y', strtotime($invoice['billing_period_start'])) . ' - ' . 
                               date('d/m/Y', strtotime($invoice['billing_period_end']));
            }
            
            $csvData[] = [
                $invoice['invoice_number'],
                $invoice['restaurant_name'],
                $invoice['status'],
                $invoice['type'],
                $billingPeriod,
                $invoice['due_date'] ? date('d/m/Y', strtotime($invoice['due_date'])) : '',
                $invoice['subtotal'],
                $invoice['discount_amount'] ?? 0,
                $invoice['tax_amount'] ?? 0,
                $invoice['total_amount'],
                $invoice['payment_method'] ?? '',
                $invoice['payment_gateway'] ?? '',
                $invoice['paid_at'] ? date('d/m/Y H:i', strtotime($invoice['paid_at'])) : '',
                date('d/m/Y H:i', strtotime($invoice['created_at']))
            ];
        }
        
        return $csvData;
    }

    /**
     * Get monthly revenue report
     */
    public function getMonthlyRevenueReport($year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $query = $this->select('MONTH(paid_at) as month, SUM(total_amount) as revenue, COUNT(*) as invoice_count')
                     ->where('status', self::STATUS_PAID)
                     ->where('YEAR(paid_at)', $year)
                     ->groupBy('MONTH(paid_at)')
                     ->orderBy('month', 'ASC')
                     ->findAll();

        // Fill missing months with zero
        $report = [];
        for ($i = 1; $i <= 12; $i++) {
            $report[$i] = [
                'month' => $i,
                'revenue' => 0,
                'invoice_count' => 0
            ];
        }

        foreach ($query as $row) {
            $report[$row['month']] = $row;
        }

        return array_values($report);
    }
}