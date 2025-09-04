<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Pagamentos com Multi-Tenancy
 */
class PaymentModel extends BaseMultiTenantModel
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'order_id',
        'customer_id',
        'payment_code',
        'transaction_id',
        'external_id',
        'payment_method',
        'payment_type',
        'gateway',
        'gateway_transaction_id',
        'status',
        'amount',
        'currency',
        'exchange_rate',
        'amount_paid',
        'amount_refunded',
        'amount_fee',
        'discount_amount',
        'tax_amount',
        'tip_amount',
        'total_amount',
        'installments',
        'installment_amount',
        'card_brand',
        'card_last_four',
        'card_holder_name',
        'authorization_code',
        'capture_id',
        'refund_id',
        'chargeback_id',
        'pix_key',
        'pix_qr_code',
        'pix_copy_paste',
        'bank_slip_url',
        'bank_slip_barcode',
        'bank_slip_due_date',
        'payment_date',
        'authorized_at',
        'captured_at',
        'cancelled_at',
        'refunded_at',
        'failed_at',
        'expires_at',
        'failure_reason',
        'failure_code',
        'gateway_response',
        'webhook_data',
        'metadata',
        'notes',
        'internal_notes',
        'processed_by',
        'is_test',
        'risk_score',
        'fraud_analysis',
        'customer_ip',
        'user_agent',
        'device_fingerprint',
        'settings'
    ];
    
    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
    // Validation
    protected $validationRules = [
        'restaurant_id' => 'required|integer',
        'order_id' => 'permit_empty|integer',
        'customer_id' => 'permit_empty|integer',
        'payment_method' => 'required|in_list[cash,credit_card,debit_card,pix,bank_slip,bank_transfer,digital_wallet,voucher,check]',
        'payment_type' => 'permit_empty|in_list[payment,refund,chargeback,adjustment]',
        'gateway' => 'permit_empty|in_list[stripe,mercadopago,pagseguro,cielo,rede,stone,getnet,adyen,paypal,manual]',
        'status' => 'permit_empty|in_list[pending,processing,authorized,captured,paid,cancelled,failed,refunded,partially_refunded,disputed,chargeback]',
        'amount' => 'required|decimal|greater_than[0]',
        'currency' => 'permit_empty|alpha|exact_length[3]',
        'exchange_rate' => 'permit_empty|decimal|greater_than[0]',
        'amount_paid' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'amount_refunded' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'amount_fee' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'installments' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[24]',
        'card_last_four' => 'permit_empty|numeric|exact_length[4]',
        'payment_date' => 'permit_empty|valid_date',
        'expires_at' => 'permit_empty|valid_date'
    ];
    
    protected $validationMessages = [
        'amount' => [
            'required' => 'Valor do pagamento é obrigatório',
            'decimal' => 'Valor deve ser um número decimal válido',
            'greater_than' => 'Valor deve ser maior que zero'
        ],
        'payment_method' => [
            'required' => 'Método de pagamento é obrigatório',
            'in_list' => 'Método de pagamento inválido'
        ],
        'installments' => [
            'integer' => 'Número de parcelas deve ser um número inteiro',
            'greater_than' => 'Número de parcelas deve ser maior que zero',
            'less_than_equal_to' => 'Número máximo de parcelas é 24'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generatePaymentCode'];
    protected $beforeUpdate = ['updateTimestamps'];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'pending';
        }
        
        if (!isset($data['data']['payment_type'])) {
            $data['data']['payment_type'] = 'payment';
        }
        
        if (!isset($data['data']['currency'])) {
            $data['data']['currency'] = 'BRL';
        }
        
        if (!isset($data['data']['exchange_rate'])) {
            $data['data']['exchange_rate'] = 1.00;
        }
        
        if (!isset($data['data']['amount_paid'])) {
            $data['data']['amount_paid'] = 0.00;
        }
        
        if (!isset($data['data']['amount_refunded'])) {
            $data['data']['amount_refunded'] = 0.00;
        }
        
        if (!isset($data['data']['amount_fee'])) {
            $data['data']['amount_fee'] = 0.00;
        }
        
        if (!isset($data['data']['discount_amount'])) {
            $data['data']['discount_amount'] = 0.00;
        }
        
        if (!isset($data['data']['tax_amount'])) {
            $data['data']['tax_amount'] = 0.00;
        }
        
        if (!isset($data['data']['tip_amount'])) {
            $data['data']['tip_amount'] = 0.00;
        }
        
        if (!isset($data['data']['installments'])) {
            $data['data']['installments'] = 1;
        }
        
        if (!isset($data['data']['is_test'])) {
            $data['data']['is_test'] = 0;
        }
        
        // Calcular valor total se não informado
        if (!isset($data['data']['total_amount'])) {
            $amount = $data['data']['amount'] ?? 0;
            $discount = $data['data']['discount_amount'] ?? 0;
            $tax = $data['data']['tax_amount'] ?? 0;
            $tip = $data['data']['tip_amount'] ?? 0;
            $data['data']['total_amount'] = $amount - $discount + $tax + $tip;
        }
        
        // Calcular valor da parcela
        if (!isset($data['data']['installment_amount']) && isset($data['data']['total_amount'])) {
            $installments = $data['data']['installments'] ?? 1;
            $data['data']['installment_amount'] = $data['data']['total_amount'] / $installments;
        }
        
        return $data;
    }
    
    /**
     * Gera código único do pagamento
     */
    protected function generatePaymentCode(array $data): array
    {
        if (!isset($data['data']['payment_code']) || empty($data['data']['payment_code'])) {
            $restaurantId = $data['data']['restaurant_id'] ?? $this->getCurrentTenantId();
            $prefix = 'PAY';
            $timestamp = date('ymdHis');
            
            // Busca o último código gerado hoje
            $lastCode = $this->where('restaurant_id', $restaurantId)
                           ->where('DATE(created_at)', date('Y-m-d'))
                           ->orderBy('id', 'DESC')
                           ->first();
            
            $sequence = 1;
            if ($lastCode && !empty($lastCode['payment_code'])) {
                $lastSequence = (int) substr($lastCode['payment_code'], -3);
                $sequence = $lastSequence + 1;
            }
            
            $data['data']['payment_code'] = $prefix . $timestamp . str_pad($sequence, 3, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }
    
    /**
     * Atualiza timestamps baseado no status
     */
    protected function updateTimestamps(array $data): array
    {
        if (isset($data['data']['status'])) {
            $now = date('Y-m-d H:i:s');
            
            switch ($data['data']['status']) {
                case 'authorized':
                    if (!isset($data['data']['authorized_at'])) {
                        $data['data']['authorized_at'] = $now;
                    }
                    break;
                    
                case 'captured':
                case 'paid':
                    if (!isset($data['data']['captured_at'])) {
                        $data['data']['captured_at'] = $now;
                    }
                    if (!isset($data['data']['payment_date'])) {
                        $data['data']['payment_date'] = $now;
                    }
                    break;
                    
                case 'cancelled':
                    if (!isset($data['data']['cancelled_at'])) {
                        $data['data']['cancelled_at'] = $now;
                    }
                    break;
                    
                case 'failed':
                    if (!isset($data['data']['failed_at'])) {
                        $data['data']['failed_at'] = $now;
                    }
                    break;
                    
                case 'refunded':
                case 'partially_refunded':
                    if (!isset($data['data']['refunded_at'])) {
                        $data['data']['refunded_at'] = $now;
                    }
                    break;
            }
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Busca pagamento por código
     */
    public function findByCode(string $paymentCode): ?array
    {
        return $this->where('payment_code', $paymentCode)->first();
    }
    
    /**
     * Busca pagamento por ID da transação
     */
    public function findByTransactionId(string $transactionId): ?array
    {
        return $this->where('transaction_id', $transactionId)->first();
    }
    
    /**
     * Busca pagamento por ID externo (gateway)
     */
    public function findByExternalId(string $externalId): ?array
    {
        return $this->where('external_id', $externalId)->first();
    }
    
    /**
     * Obtém pagamentos por pedido
     */
    public function getPaymentsByOrder(int $orderId): array
    {
        return $this->where('order_id', $orderId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos por cliente
     */
    public function getPaymentsByCustomer(int $customerId): array
    {
        return $this->where('customer_id', $customerId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos por status
     */
    public function getPaymentsByStatus(string $status): array
    {
        return $this->where('status', $status)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos por método
     */
    public function getPaymentsByMethod(string $method): array
    {
        return $this->where('payment_method', $method)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos por gateway
     */
    public function getPaymentsByGateway(string $gateway): array
    {
        return $this->where('gateway', $gateway)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos pendentes
     */
    public function getPendingPayments(): array
    {
        return $this->whereIn('status', ['pending', 'processing', 'authorized'])
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos aprovados
     */
    public function getApprovedPayments(): array
    {
        return $this->whereIn('status', ['captured', 'paid'])
                   ->orderBy('payment_date', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos falhados
     */
    public function getFailedPayments(): array
    {
        return $this->whereIn('status', ['failed', 'cancelled'])
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos reembolsados
     */
    public function getRefundedPayments(): array
    {
        return $this->whereIn('status', ['refunded', 'partially_refunded'])
                   ->orderBy('refunded_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos expirados
     */
    public function getExpiredPayments(): array
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
                   ->where('status', 'pending')
                   ->orderBy('expires_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos por período
     */
    public function getPaymentsByPeriod(string $startDate, string $endDate): array
    {
        return $this->where('DATE(payment_date) >=', $startDate)
                   ->where('DATE(payment_date) <=', $endDate)
                   ->orderBy('payment_date', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém receita por período
     */
    public function getRevenueByPeriod(string $startDate, string $endDate): float
    {
        $result = $this->selectSum('amount_paid')
                      ->where('DATE(payment_date) >=', $startDate)
                      ->where('DATE(payment_date) <=', $endDate)
                      ->whereIn('status', ['captured', 'paid'])
                      ->first();
        
        return $result['amount_paid'] ?? 0.00;
    }
    
    /**
     * Obtém receita por método de pagamento
     */
    public function getRevenueByMethod(string $startDate = null, string $endDate = null): array
    {
        $builder = $this->select('payment_method, SUM(amount_paid) as total_revenue, COUNT(*) as total_transactions')
                       ->whereIn('status', ['captured', 'paid'])
                       ->groupBy('payment_method')
                       ->orderBy('total_revenue', 'DESC');
        
        if ($startDate) {
            $builder->where('DATE(payment_date) >=', $startDate);
        }
        
        if ($endDate) {
            $builder->where('DATE(payment_date) <=', $endDate);
        }
        
        return $builder->findAll();
    }
    
    /**
     * Atualiza status do pagamento
     */
    public function updatePaymentStatus(int $paymentId, string $status, array $additionalData = []): bool
    {
        $updateData = array_merge(['status' => $status], $additionalData);
        
        // Atualizar timestamps automaticamente
        $now = date('Y-m-d H:i:s');
        
        switch ($status) {
            case 'authorized':
                $updateData['authorized_at'] = $now;
                break;
                
            case 'captured':
            case 'paid':
                $updateData['captured_at'] = $now;
                $updateData['payment_date'] = $now;
                break;
                
            case 'cancelled':
                $updateData['cancelled_at'] = $now;
                break;
                
            case 'failed':
                $updateData['failed_at'] = $now;
                break;
                
            case 'refunded':
            case 'partially_refunded':
                $updateData['refunded_at'] = $now;
                break;
        }
        
        return $this->update($paymentId, $updateData);
    }
    
    /**
     * Processa reembolso
     */
    public function processRefund(int $paymentId, float $refundAmount, string $reason = ''): bool
    {
        $payment = $this->find($paymentId);
        if (!$payment || !in_array($payment['status'], ['captured', 'paid'])) {
            return false;
        }
        
        $currentRefunded = $payment['amount_refunded'] ?? 0;
        $totalRefunded = $currentRefunded + $refundAmount;
        $amountPaid = $payment['amount_paid'] ?? 0;
        
        if ($totalRefunded > $amountPaid) {
            return false; // Não pode reembolsar mais que o pago
        }
        
        $status = ($totalRefunded >= $amountPaid) ? 'refunded' : 'partially_refunded';
        
        $updateData = [
            'amount_refunded' => $totalRefunded,
            'status' => $status,
            'refunded_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($reason)) {
            $updateData['notes'] = $reason;
        }
        
        return $this->update($paymentId, $updateData);
    }
    
    /**
     * Captura pagamento autorizado
     */
    public function capturePayment(int $paymentId, ?float $captureAmount = null): bool
    {
        $payment = $this->find($paymentId);
        if (!$payment || $payment['status'] !== 'authorized') {
            return false;
        }
        
        $amount = $captureAmount ?? $payment['total_amount'];
        
        return $this->updatePaymentStatus($paymentId, 'captured', [
            'amount_paid' => $amount,
            'captured_at' => date('Y-m-d H:i:s'),
            'payment_date' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Cancela pagamento
     */
    public function cancelPayment(int $paymentId, string $reason = ''): bool
    {
        $payment = $this->find($paymentId);
        if (!$payment || !in_array($payment['status'], ['pending', 'processing', 'authorized'])) {
            return false;
        }
        
        $updateData = [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($reason)) {
            $updateData['failure_reason'] = $reason;
        }
        
        return $this->update($paymentId, $updateData);
    }
    
    /**
     * Busca avançada de pagamentos
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('payment_code', $filters['search'])
                             ->orLike('transaction_id', $filters['search'])
                             ->orLike('external_id', $filters['search'])
                             ->orLike('authorization_code', $filters['search'])
                             ->orLike('card_last_four', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder = $builder->whereIn('status', $filters['status']);
            } else {
                $builder = $builder->where('status', $filters['status']);
            }
        }
        
        if (!empty($filters['payment_method'])) {
            $builder = $builder->where('payment_method', $filters['payment_method']);
        }
        
        if (!empty($filters['gateway'])) {
            $builder = $builder->where('gateway', $filters['gateway']);
        }
        
        if (!empty($filters['order_id'])) {
            $builder = $builder->where('order_id', $filters['order_id']);
        }
        
        if (!empty($filters['customer_id'])) {
            $builder = $builder->where('customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['min_amount'])) {
            $builder = $builder->where('total_amount >=', $filters['min_amount']);
        }
        
        if (!empty($filters['max_amount'])) {
            $builder = $builder->where('total_amount <=', $filters['max_amount']);
        }
        
        if (!empty($filters['start_date'])) {
            $builder = $builder->where('DATE(created_at) >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder = $builder->where('DATE(created_at) <=', $filters['end_date']);
        }
        
        if (!empty($filters['payment_start_date'])) {
            $builder = $builder->where('DATE(payment_date) >=', $filters['payment_start_date']);
        }
        
        if (!empty($filters['payment_end_date'])) {
            $builder = $builder->where('DATE(payment_date) <=', $filters['payment_end_date']);
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $builder->orderBy($orderBy, $orderDir)->findAll();
    }
    
    /**
     * Obtém estatísticas de pagamentos
     */
    public function getPaymentStats(): array
    {
        $stats = [];
        
        // Total de pagamentos
        $stats['total_payments'] = $this->countAllResults();
        
        // Pagamentos por status
        $stats['payments_by_status'] = [
            'pending' => $this->where('status', 'pending')->countAllResults(),
            'processing' => $this->where('status', 'processing')->countAllResults(),
            'authorized' => $this->where('status', 'authorized')->countAllResults(),
            'captured' => $this->where('status', 'captured')->countAllResults(),
            'paid' => $this->where('status', 'paid')->countAllResults(),
            'cancelled' => $this->where('status', 'cancelled')->countAllResults(),
            'failed' => $this->where('status', 'failed')->countAllResults(),
            'refunded' => $this->where('status', 'refunded')->countAllResults(),
            'partially_refunded' => $this->where('status', 'partially_refunded')->countAllResults()
        ];
        
        // Receita total
        $revenueResult = $this->selectSum('amount_paid')
                             ->whereIn('status', ['captured', 'paid'])
                             ->first();
        $stats['total_revenue'] = $revenueResult['amount_paid'] ?? 0;
        
        // Total reembolsado
        $refundResult = $this->selectSum('amount_refunded')
                            ->whereIn('status', ['refunded', 'partially_refunded'])
                            ->first();
        $stats['total_refunded'] = $refundResult['amount_refunded'] ?? 0;
        
        // Receita líquida
        $stats['net_revenue'] = $stats['total_revenue'] - $stats['total_refunded'];
        
        // Taxa de aprovação
        $approvedCount = $stats['payments_by_status']['captured'] + $stats['payments_by_status']['paid'];
        $stats['approval_rate'] = $stats['total_payments'] > 0 
            ? ($approvedCount / $stats['total_payments']) * 100 
            : 0;
        
        // Valor médio por transação
        $stats['average_transaction_value'] = $approvedCount > 0 
            ? $stats['total_revenue'] / $approvedCount 
            : 0;
        
        // Pagamentos hoje
        $today = date('Y-m-d');
        $stats['payments_today'] = $this->where('DATE(created_at)', $today)->countAllResults();
        
        // Receita hoje
        $todayRevenueResult = $this->selectSum('amount_paid')
                                  ->where('DATE(payment_date)', $today)
                                  ->whereIn('status', ['captured', 'paid'])
                                  ->first();
        $stats['revenue_today'] = $todayRevenueResult['amount_paid'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Exporta pagamentos para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $payments = $this->advancedSearch($filters);
        
        $csv = "Código,Pedido,Método,Gateway,Status,Valor,Valor Pago,Taxa,Parcelas,Data Pagamento,Data Criação\n";
        
        foreach ($payments as $payment) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%.2f,%.2f,%.2f,%d,%s,%s\n",
                $payment['payment_code'],
                $payment['order_id'] ?? '',
                $payment['payment_method'],
                $payment['gateway'] ?? '',
                $payment['status'],
                $payment['total_amount'],
                $payment['amount_paid'],
                $payment['amount_fee'],
                $payment['installments'],
                $payment['payment_date'] ?? '',
                $payment['created_at']
            );
        }
        
        return $csv;
    }
    
    /**
     * Obtém relatório financeiro por período
     */
    public function getFinancialReport(string $startDate, string $endDate): array
    {
        $payments = $this->getPaymentsByPeriod($startDate, $endDate);
        
        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'total_transactions' => count($payments),
                'total_revenue' => 0,
                'total_fees' => 0,
                'total_refunded' => 0,
                'net_revenue' => 0
            ],
            'by_method' => [],
            'by_status' => [],
            'by_day' => []
        ];
        
        foreach ($payments as $payment) {
            // Resumo geral
            if (in_array($payment['status'], ['captured', 'paid'])) {
                $report['summary']['total_revenue'] += $payment['amount_paid'];
                $report['summary']['total_fees'] += $payment['amount_fee'];
            }
            
            if (in_array($payment['status'], ['refunded', 'partially_refunded'])) {
                $report['summary']['total_refunded'] += $payment['amount_refunded'];
            }
            
            // Por método
            $method = $payment['payment_method'];
            if (!isset($report['by_method'][$method])) {
                $report['by_method'][$method] = [
                    'count' => 0,
                    'revenue' => 0,
                    'fees' => 0
                ];
            }
            
            $report['by_method'][$method]['count']++;
            if (in_array($payment['status'], ['captured', 'paid'])) {
                $report['by_method'][$method]['revenue'] += $payment['amount_paid'];
                $report['by_method'][$method]['fees'] += $payment['amount_fee'];
            }
            
            // Por status
            $status = $payment['status'];
            if (!isset($report['by_status'][$status])) {
                $report['by_status'][$status] = 0;
            }
            $report['by_status'][$status]++;
            
            // Por dia
            if ($payment['payment_date']) {
                $day = date('Y-m-d', strtotime($payment['payment_date']));
                if (!isset($report['by_day'][$day])) {
                    $report['by_day'][$day] = [
                        'count' => 0,
                        'revenue' => 0
                    ];
                }
                
                $report['by_day'][$day]['count']++;
                if (in_array($payment['status'], ['captured', 'paid'])) {
                    $report['by_day'][$day]['revenue'] += $payment['amount_paid'];
                }
            }
        }
        
        $report['summary']['net_revenue'] = $report['summary']['total_revenue'] - $report['summary']['total_refunded'];
        
        return $report;
    }
    
    /**
     * Verifica se transação já existe
     */
    public function transactionExists(string $transactionId): bool
    {
        return $this->where('transaction_id', $transactionId)->countAllResults() > 0;
    }
    
    /**
     * Obtém pagamentos com risco de fraude
     */
    public function getHighRiskPayments(float $minRiskScore = 70): array
    {
        return $this->where('risk_score >=', $minRiskScore)
                   ->whereIn('status', ['pending', 'processing', 'authorized'])
                   ->orderBy('risk_score', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém pagamentos para reconciliação
     */
    public function getPaymentsForReconciliation(string $date): array
    {
        return $this->where('DATE(payment_date)', $date)
                   ->whereIn('status', ['captured', 'paid'])
                   ->orderBy('payment_date', 'ASC')
                   ->findAll();
    }
}