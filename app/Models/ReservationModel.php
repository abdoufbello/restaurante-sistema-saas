<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Reservas com Multi-Tenancy
 */
class ReservationModel extends BaseMultiTenantModel
{
    protected $table = 'reservations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'customer_id',
        'table_id',
        'reservation_code',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_document',
        'party_size',
        'reservation_date',
        'reservation_time',
        'arrival_time',
        'seated_time',
        'departure_time',
        'duration_minutes',
        'status',
        'source',
        'channel',
        'special_requests',
        'dietary_restrictions',
        'occasion',
        'seating_preference',
        'high_chair_needed',
        'wheelchair_accessible',
        'vip_service',
        'deposit_required',
        'deposit_amount',
        'deposit_paid',
        'deposit_payment_id',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'no_show_fee',
        'no_show_charged',
        'reminder_sent',
        'confirmation_sent',
        'feedback_rating',
        'feedback_comment',
        'internal_notes',
        'staff_notes',
        'created_by',
        'updated_by',
        'tags',
        'metadata',
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
        'customer_name' => 'required|min_length[2]|max_length[100]',
        'customer_email' => 'permit_empty|valid_email|max_length[100]',
        'customer_phone' => 'permit_empty|min_length[10]|max_length[20]',
        'party_size' => 'required|integer|greater_than[0]|less_than_equal_to[50]',
        'reservation_date' => 'required|valid_date',
        'reservation_time' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
        'status' => 'permit_empty|in_list[pending,confirmed,seated,completed,cancelled,no_show]',
        'source' => 'permit_empty|in_list[website,phone,walk_in,app,third_party,social_media,email]',
        'channel' => 'permit_empty|in_list[direct,opentable,resy,yelp,google,facebook,instagram]',
        'seating_preference' => 'permit_empty|in_list[indoor,outdoor,bar,window,quiet,private]',
        'deposit_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'duration_minutes' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[480]',
        'feedback_rating' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[5]'
    ];
    
    protected $validationMessages = [
        'customer_name' => [
            'required' => 'Nome do cliente é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 2 caracteres',
            'max_length' => 'Nome não pode exceder 100 caracteres'
        ],
        'party_size' => [
            'required' => 'Número de pessoas é obrigatório',
            'integer' => 'Número de pessoas deve ser um número inteiro',
            'greater_than' => 'Deve haver pelo menos 1 pessoa',
            'less_than_equal_to' => 'Máximo de 50 pessoas por reserva'
        ],
        'reservation_date' => [
            'required' => 'Data da reserva é obrigatória',
            'valid_date' => 'Data da reserva deve ser válida'
        ],
        'reservation_time' => [
            'required' => 'Horário da reserva é obrigatório',
            'regex_match' => 'Horário deve estar no formato HH:MM'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generateReservationCode', 'validateReservationTime'];
    protected $beforeUpdate = ['updateTimestamps', 'validateStatusChange'];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'pending';
        }
        
        if (!isset($data['data']['source'])) {
            $data['data']['source'] = 'website';
        }
        
        if (!isset($data['data']['duration_minutes'])) {
            $data['data']['duration_minutes'] = 120; // 2 horas padrão
        }
        
        if (!isset($data['data']['deposit_required'])) {
            $data['data']['deposit_required'] = 0;
        }
        
        if (!isset($data['data']['deposit_paid'])) {
            $data['data']['deposit_paid'] = 0;
        }
        
        if (!isset($data['data']['high_chair_needed'])) {
            $data['data']['high_chair_needed'] = 0;
        }
        
        if (!isset($data['data']['wheelchair_accessible'])) {
            $data['data']['wheelchair_accessible'] = 0;
        }
        
        if (!isset($data['data']['vip_service'])) {
            $data['data']['vip_service'] = 0;
        }
        
        if (!isset($data['data']['no_show_charged'])) {
            $data['data']['no_show_charged'] = 0;
        }
        
        if (!isset($data['data']['reminder_sent'])) {
            $data['data']['reminder_sent'] = 0;
        }
        
        if (!isset($data['data']['confirmation_sent'])) {
            $data['data']['confirmation_sent'] = 0;
        }
        
        return $data;
    }
    
    /**
     * Gera código único da reserva
     */
    protected function generateReservationCode(array $data): array
    {
        if (!isset($data['data']['reservation_code']) || empty($data['data']['reservation_code'])) {
            $restaurantId = $data['data']['restaurant_id'] ?? $this->getCurrentTenantId();
            $prefix = 'RES';
            $date = date('ymd');
            
            // Busca o último código gerado hoje
            $lastCode = $this->where('restaurant_id', $restaurantId)
                           ->where('DATE(created_at)', date('Y-m-d'))
                           ->orderBy('id', 'DESC')
                           ->first();
            
            $sequence = 1;
            if ($lastCode && !empty($lastCode['reservation_code'])) {
                $lastSequence = (int) substr($lastCode['reservation_code'], -4);
                $sequence = $lastSequence + 1;
            }
            
            $data['data']['reservation_code'] = $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }
    
    /**
     * Valida horário da reserva
     */
    protected function validateReservationTime(array $data): array
    {
        if (isset($data['data']['reservation_date']) && isset($data['data']['reservation_time'])) {
            $reservationDateTime = $data['data']['reservation_date'] . ' ' . $data['data']['reservation_time'];
            $reservationTimestamp = strtotime($reservationDateTime);
            $currentTimestamp = time();
            
            // Não permitir reservas no passado (com margem de 1 hora)
            if ($reservationTimestamp < ($currentTimestamp - 3600)) {
                throw new \InvalidArgumentException('Não é possível fazer reservas para horários passados');
            }
            
            // Não permitir reservas muito no futuro (6 meses)
            $maxFutureTimestamp = $currentTimestamp + (6 * 30 * 24 * 3600);
            if ($reservationTimestamp > $maxFutureTimestamp) {
                throw new \InvalidArgumentException('Não é possível fazer reservas com mais de 6 meses de antecedência');
            }
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
                case 'seated':
                    if (!isset($data['data']['seated_time'])) {
                        $data['data']['seated_time'] = $now;
                    }
                    break;
                    
                case 'completed':
                    if (!isset($data['data']['departure_time'])) {
                        $data['data']['departure_time'] = $now;
                    }
                    break;
                    
                case 'cancelled':
                    if (!isset($data['data']['cancelled_at'])) {
                        $data['data']['cancelled_at'] = $now;
                    }
                    break;
            }
        }
        
        return $data;
    }
    
    /**
     * Valida mudanças de status
     */
    protected function validateStatusChange(array $data): array
    {
        if (isset($data['data']['status']) && isset($data['id'])) {
            $currentReservation = $this->find($data['id']);
            if ($currentReservation) {
                $currentStatus = $currentReservation['status'];
                $newStatus = $data['data']['status'];
                
                // Regras de transição de status
                $allowedTransitions = [
                    'pending' => ['confirmed', 'cancelled'],
                    'confirmed' => ['seated', 'cancelled', 'no_show'],
                    'seated' => ['completed', 'cancelled'],
                    'completed' => [], // Status final
                    'cancelled' => [], // Status final
                    'no_show' => [] // Status final
                ];
                
                if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
                    throw new \InvalidArgumentException("Transição de status inválida: {$currentStatus} -> {$newStatus}");
                }
            }
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Busca reserva por código
     */
    public function findByCode(string $reservationCode): ?array
    {
        return $this->where('reservation_code', $reservationCode)->first();
    }
    
    /**
     * Obtém reservas por cliente
     */
    public function getReservationsByCustomer(int $customerId): array
    {
        return $this->where('customer_id', $customerId)
                   ->orderBy('reservation_date', 'DESC')
                   ->orderBy('reservation_time', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém reservas por mesa
     */
    public function getReservationsByTable(int $tableId): array
    {
        return $this->where('table_id', $tableId)
                   ->orderBy('reservation_date', 'DESC')
                   ->orderBy('reservation_time', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém reservas por status
     */
    public function getReservationsByStatus(string $status): array
    {
        return $this->where('status', $status)
                   ->orderBy('reservation_date', 'ASC')
                   ->orderBy('reservation_time', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém reservas por data
     */
    public function getReservationsByDate(string $date): array
    {
        return $this->where('reservation_date', $date)
                   ->orderBy('reservation_time', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém reservas por período
     */
    public function getReservationsByPeriod(string $startDate, string $endDate): array
    {
        return $this->where('reservation_date >=', $startDate)
                   ->where('reservation_date <=', $endDate)
                   ->orderBy('reservation_date', 'ASC')
                   ->orderBy('reservation_time', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém reservas de hoje
     */
    public function getTodayReservations(): array
    {
        return $this->getReservationsByDate(date('Y-m-d'));
    }
    
    /**
     * Obtém próximas reservas
     */
    public function getUpcomingReservations(int $limit = 10): array
    {
        $now = date('Y-m-d H:i:s');
        
        return $this->where('CONCAT(reservation_date, " ", reservation_time) >=', $now)
                   ->whereIn('status', ['pending', 'confirmed'])
                   ->orderBy('reservation_date', 'ASC')
                   ->orderBy('reservation_time', 'ASC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém reservas pendentes de confirmação
     */
    public function getPendingReservations(): array
    {
        return $this->where('status', 'pending')
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém reservas confirmadas
     */
    public function getConfirmedReservations(): array
    {
        return $this->where('status', 'confirmed')
                   ->orderBy('reservation_date', 'ASC')
                   ->orderBy('reservation_time', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém reservas em andamento (clientes já sentados)
     */
    public function getActiveReservations(): array
    {
        return $this->where('status', 'seated')
                   ->orderBy('seated_time', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém reservas canceladas
     */
    public function getCancelledReservations(): array
    {
        return $this->where('status', 'cancelled')
                   ->orderBy('cancelled_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém no-shows
     */
    public function getNoShowReservations(): array
    {
        return $this->where('status', 'no_show')
                   ->orderBy('reservation_date', 'DESC')
                   ->orderBy('reservation_time', 'DESC')
                   ->findAll();
    }
    
    /**
     * Verifica disponibilidade de mesa
     */
    public function checkTableAvailability(int $tableId, string $date, string $time, int $duration = 120, ?int $excludeReservationId = null): bool
    {
        $startTime = $date . ' ' . $time;
        $endTime = date('Y-m-d H:i:s', strtotime($startTime) + ($duration * 60));
        
        $builder = $this->where('table_id', $tableId)
                       ->whereIn('status', ['confirmed', 'seated'])
                       ->groupStart()
                           // Reserva começa durante o período solicitado
                           ->groupStart()
                               ->where('CONCAT(reservation_date, " ", reservation_time) >=', $startTime)
                               ->where('CONCAT(reservation_date, " ", reservation_time) <', $endTime)
                           ->groupEnd()
                           // Reserva termina durante o período solicitado
                           ->orGroupStart()
                               ->where('DATE_ADD(CONCAT(reservation_date, " ", reservation_time), INTERVAL duration_minutes MINUTE) >', $startTime)
                               ->where('DATE_ADD(CONCAT(reservation_date, " ", reservation_time), INTERVAL duration_minutes MINUTE) <=', $endTime)
                           ->groupEnd()
                           // Reserva engloba todo o período solicitado
                           ->orGroupStart()
                               ->where('CONCAT(reservation_date, " ", reservation_time) <=', $startTime)
                               ->where('DATE_ADD(CONCAT(reservation_date, " ", reservation_time), INTERVAL duration_minutes MINUTE) >=', $endTime)
                           ->groupEnd()
                       ->groupEnd();
        
        if ($excludeReservationId) {
            $builder->where('id !=', $excludeReservationId);
        }
        
        return $builder->countAllResults() === 0;
    }
    
    /**
     * Busca mesas disponíveis
     */
    public function findAvailableTables(string $date, string $time, int $partySize, int $duration = 120): array
    {
        // Esta função requer integração com TableModel
        // Por enquanto, retorna array vazio
        return [];
    }
    
    /**
     * Confirma reserva
     */
    public function confirmReservation(int $reservationId): bool
    {
        $reservation = $this->find($reservationId);
        if (!$reservation || $reservation['status'] !== 'pending') {
            return false;
        }
        
        return $this->update($reservationId, [
            'status' => 'confirmed',
            'confirmation_sent' => 1
        ]);
    }
    
    /**
     * Cancela reserva
     */
    public function cancelReservation(int $reservationId, string $reason = '', ?int $cancelledBy = null): bool
    {
        $reservation = $this->find($reservationId);
        if (!$reservation || in_array($reservation['status'], ['completed', 'cancelled', 'no_show'])) {
            return false;
        }
        
        $updateData = [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($reason)) {
            $updateData['cancellation_reason'] = $reason;
        }
        
        if ($cancelledBy) {
            $updateData['cancelled_by'] = $cancelledBy;
        }
        
        return $this->update($reservationId, $updateData);
    }
    
    /**
     * Marca cliente como sentado
     */
    public function seatCustomer(int $reservationId, ?int $actualTableId = null): bool
    {
        $reservation = $this->find($reservationId);
        if (!$reservation || $reservation['status'] !== 'confirmed') {
            return false;
        }
        
        $updateData = [
            'status' => 'seated',
            'seated_time' => date('Y-m-d H:i:s'),
            'arrival_time' => date('Y-m-d H:i:s')
        ];
        
        if ($actualTableId) {
            $updateData['table_id'] = $actualTableId;
        }
        
        return $this->update($reservationId, $updateData);
    }
    
    /**
     * Completa reserva
     */
    public function completeReservation(int $reservationId): bool
    {
        $reservation = $this->find($reservationId);
        if (!$reservation || $reservation['status'] !== 'seated') {
            return false;
        }
        
        return $this->update($reservationId, [
            'status' => 'completed',
            'departure_time' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Marca como no-show
     */
    public function markAsNoShow(int $reservationId, float $noShowFee = 0): bool
    {
        $reservation = $this->find($reservationId);
        if (!$reservation || !in_array($reservation['status'], ['pending', 'confirmed'])) {
            return false;
        }
        
        $updateData = [
            'status' => 'no_show'
        ];
        
        if ($noShowFee > 0) {
            $updateData['no_show_fee'] = $noShowFee;
        }
        
        return $this->update($reservationId, $updateData);
    }
    
    /**
     * Adiciona feedback da reserva
     */
    public function addFeedback(int $reservationId, int $rating, string $comment = ''): bool
    {
        $reservation = $this->find($reservationId);
        if (!$reservation || $reservation['status'] !== 'completed') {
            return false;
        }
        
        return $this->update($reservationId, [
            'feedback_rating' => $rating,
            'feedback_comment' => $comment
        ]);
    }
    
    /**
     * Busca avançada de reservas
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('reservation_code', $filters['search'])
                             ->orLike('customer_name', $filters['search'])
                             ->orLike('customer_email', $filters['search'])
                             ->orLike('customer_phone', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder = $builder->whereIn('status', $filters['status']);
            } else {
                $builder = $builder->where('status', $filters['status']);
            }
        }
        
        if (!empty($filters['customer_id'])) {
            $builder = $builder->where('customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['table_id'])) {
            $builder = $builder->where('table_id', $filters['table_id']);
        }
        
        if (!empty($filters['source'])) {
            $builder = $builder->where('source', $filters['source']);
        }
        
        if (!empty($filters['start_date'])) {
            $builder = $builder->where('reservation_date >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $builder = $builder->where('reservation_date <=', $filters['end_date']);
        }
        
        if (!empty($filters['min_party_size'])) {
            $builder = $builder->where('party_size >=', $filters['min_party_size']);
        }
        
        if (!empty($filters['max_party_size'])) {
            $builder = $builder->where('party_size <=', $filters['max_party_size']);
        }
        
        if (!empty($filters['vip_only'])) {
            $builder = $builder->where('vip_service', 1);
        }
        
        if (!empty($filters['special_needs'])) {
            $builder = $builder->groupStart()
                             ->where('high_chair_needed', 1)
                             ->orWhere('wheelchair_accessible', 1)
                             ->groupEnd();
        }
        
        $orderBy = $filters['order_by'] ?? 'reservation_date';
        $orderDir = $filters['order_dir'] ?? 'ASC';
        
        return $builder->orderBy($orderBy, $orderDir)
                      ->orderBy('reservation_time', $orderDir)
                      ->findAll();
    }
    
    /**
     * Obtém estatísticas de reservas
     */
    public function getReservationStats(): array
    {
        $stats = [];
        
        // Total de reservas
        $stats['total_reservations'] = $this->countAllResults();
        
        // Reservas por status
        $stats['reservations_by_status'] = [
            'pending' => $this->where('status', 'pending')->countAllResults(),
            'confirmed' => $this->where('status', 'confirmed')->countAllResults(),
            'seated' => $this->where('status', 'seated')->countAllResults(),
            'completed' => $this->where('status', 'completed')->countAllResults(),
            'cancelled' => $this->where('status', 'cancelled')->countAllResults(),
            'no_show' => $this->where('status', 'no_show')->countAllResults()
        ];
        
        // Taxa de comparecimento
        $totalCompleted = $stats['reservations_by_status']['completed'];
        $totalNoShow = $stats['reservations_by_status']['no_show'];
        $totalFinalized = $totalCompleted + $totalNoShow;
        
        $stats['attendance_rate'] = $totalFinalized > 0 
            ? ($totalCompleted / $totalFinalized) * 100 
            : 0;
        
        // Taxa de cancelamento
        $totalCancelled = $stats['reservations_by_status']['cancelled'];
        $stats['cancellation_rate'] = $stats['total_reservations'] > 0 
            ? ($totalCancelled / $stats['total_reservations']) * 100 
            : 0;
        
        // Tamanho médio do grupo
        $avgResult = $this->selectAvg('party_size')->first();
        $stats['average_party_size'] = round($avgResult['party_size'] ?? 0, 1);
        
        // Reservas hoje
        $today = date('Y-m-d');
        $stats['reservations_today'] = $this->where('reservation_date', $today)->countAllResults();
        
        // Reservas esta semana
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        $stats['reservations_this_week'] = $this->where('reservation_date >=', $weekStart)
                                               ->where('reservation_date <=', $weekEnd)
                                               ->countAllResults();
        
        // Reservas por fonte
        $sourceStats = $this->select('source, COUNT(*) as count')
                           ->groupBy('source')
                           ->orderBy('count', 'DESC')
                           ->findAll();
        
        $stats['reservations_by_source'] = [];
        foreach ($sourceStats as $source) {
            $stats['reservations_by_source'][$source['source']] = $source['count'];
        }
        
        // Horários mais populares
        $timeStats = $this->select('reservation_time, COUNT(*) as count')
                         ->where('status !=', 'cancelled')
                         ->groupBy('reservation_time')
                         ->orderBy('count', 'DESC')
                         ->limit(5)
                         ->findAll();
        
        $stats['popular_times'] = $timeStats;
        
        return $stats;
    }
    
    /**
     * Exporta reservas para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $reservations = $this->advancedSearch($filters);
        
        $csv = "Código,Cliente,Email,Telefone,Pessoas,Data,Horário,Status,Mesa,Origem,Criado em\n";
        
        foreach ($reservations as $reservation) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%d,%s,%s,%s,%s,%s,%s\n",
                $reservation['reservation_code'],
                $reservation['customer_name'],
                $reservation['customer_email'] ?? '',
                $reservation['customer_phone'] ?? '',
                $reservation['party_size'],
                $reservation['reservation_date'],
                $reservation['reservation_time'],
                $reservation['status'],
                $reservation['table_id'] ?? '',
                $reservation['source'],
                $reservation['created_at']
            );
        }
        
        return $csv;
    }
    
    /**
     * Obtém relatório de ocupação
     */
    public function getOccupancyReport(string $startDate, string $endDate): array
    {
        $reservations = $this->getReservationsByPeriod($startDate, $endDate);
        
        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'total_reservations' => count($reservations),
                'completed_reservations' => 0,
                'cancelled_reservations' => 0,
                'no_show_reservations' => 0,
                'total_guests' => 0,
                'average_party_size' => 0
            ],
            'by_date' => [],
            'by_time_slot' => [],
            'by_source' => []
        ];
        
        $guestsByDate = [];
        $reservationsByDate = [];
        
        foreach ($reservations as $reservation) {
            $date = $reservation['reservation_date'];
            $timeSlot = substr($reservation['reservation_time'], 0, 2) . ':00';
            $source = $reservation['source'];
            
            // Resumo
            switch ($reservation['status']) {
                case 'completed':
                    $report['summary']['completed_reservations']++;
                    break;
                case 'cancelled':
                    $report['summary']['cancelled_reservations']++;
                    break;
                case 'no_show':
                    $report['summary']['no_show_reservations']++;
                    break;
            }
            
            $report['summary']['total_guests'] += $reservation['party_size'];
            
            // Por data
            if (!isset($report['by_date'][$date])) {
                $report['by_date'][$date] = [
                    'reservations' => 0,
                    'guests' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'no_show' => 0
                ];
            }
            
            $report['by_date'][$date]['reservations']++;
            $report['by_date'][$date]['guests'] += $reservation['party_size'];
            $report['by_date'][$date][$reservation['status']]++;
            
            // Por horário
            if (!isset($report['by_time_slot'][$timeSlot])) {
                $report['by_time_slot'][$timeSlot] = [
                    'reservations' => 0,
                    'guests' => 0
                ];
            }
            
            $report['by_time_slot'][$timeSlot]['reservations']++;
            $report['by_time_slot'][$timeSlot]['guests'] += $reservation['party_size'];
            
            // Por origem
            if (!isset($report['by_source'][$source])) {
                $report['by_source'][$source] = [
                    'reservations' => 0,
                    'guests' => 0
                ];
            }
            
            $report['by_source'][$source]['reservations']++;
            $report['by_source'][$source]['guests'] += $reservation['party_size'];
        }
        
        // Calcular média de pessoas por reserva
        $report['summary']['average_party_size'] = $report['summary']['total_reservations'] > 0 
            ? round($report['summary']['total_guests'] / $report['summary']['total_reservations'], 1) 
            : 0;
        
        return $report;
    }
    
    /**
     * Envia lembretes automáticos
     */
    public function sendReminders(int $hoursBeforeReservation = 24): array
    {
        $reminderTime = date('Y-m-d H:i:s', time() + ($hoursBeforeReservation * 3600));
        
        $reservations = $this->where('status', 'confirmed')
                            ->where('reminder_sent', 0)
                            ->where('CONCAT(reservation_date, " ", reservation_time) <=', $reminderTime)
                            ->findAll();
        
        $sentReminders = [];
        
        foreach ($reservations as $reservation) {
            // Aqui seria integrado com sistema de notificações/email
            // Por enquanto, apenas marca como enviado
            $this->update($reservation['id'], ['reminder_sent' => 1]);
            $sentReminders[] = $reservation['id'];
        }
        
        return $sentReminders;
    }
    
    /**
     * Verifica se email já tem reserva no período
     */
    public function hasReservationInPeriod(string $email, string $date, string $startTime, string $endTime): bool
    {
        return $this->where('customer_email', $email)
                   ->where('reservation_date', $date)
                   ->where('reservation_time >=', $startTime)
                   ->where('reservation_time <=', $endTime)
                   ->whereIn('status', ['pending', 'confirmed', 'seated'])
                   ->countAllResults() > 0;
    }
    
    /**
     * Duplica reserva
     */
    public function duplicateReservation(int $reservationId, string $newDate, string $newTime): ?int
    {
        $reservation = $this->find($reservationId);
        if (!$reservation) {
            return null;
        }
        
        // Remove campos que não devem ser duplicados
        unset($reservation['id']);
        unset($reservation['reservation_code']);
        unset($reservation['created_at']);
        unset($reservation['updated_at']);
        unset($reservation['deleted_at']);
        
        // Define nova data e horário
        $reservation['reservation_date'] = $newDate;
        $reservation['reservation_time'] = $newTime;
        $reservation['status'] = 'pending';
        $reservation['confirmation_sent'] = 0;
        $reservation['reminder_sent'] = 0;
        
        // Remove timestamps de ações
        $reservation['arrival_time'] = null;
        $reservation['seated_time'] = null;
        $reservation['departure_time'] = null;
        $reservation['cancelled_at'] = null;
        
        return $this->insert($reservation);
    }
}