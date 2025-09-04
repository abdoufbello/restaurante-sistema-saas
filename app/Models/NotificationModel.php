<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Notificações com Multi-Tenancy
 */
class NotificationModel extends BaseMultiTenantModel
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'user_id',
        'customer_id',
        'order_id',
        'reservation_id',
        'notification_code',
        'type',
        'category',
        'priority',
        'title',
        'message',
        'description',
        'action_url',
        'action_text',
        'icon',
        'color',
        'channel',
        'recipient_type',
        'recipient_id',
        'recipient_email',
        'recipient_phone',
        'sender_id',
        'sender_name',
        'template_id',
        'template_data',
        'status',
        'is_read',
        'is_sent',
        'is_delivered',
        'is_clicked',
        'read_at',
        'sent_at',
        'delivered_at',
        'clicked_at',
        'scheduled_at',
        'expires_at',
        'retry_count',
        'max_retries',
        'last_retry_at',
        'error_message',
        'delivery_status',
        'delivery_response',
        'tracking_id',
        'external_id',
        'push_token',
        'email_subject',
        'email_body',
        'sms_body',
        'push_body',
        'webhook_url',
        'webhook_data',
        'tags',
        'metadata',
        'settings',
        'created_by',
        'updated_by'
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
        'type' => 'required|in_list[order,reservation,payment,inventory,system,marketing,reminder,alert,info,warning,error,success]',
        'category' => 'permit_empty|in_list[operational,financial,marketing,system,security,maintenance,customer_service,inventory,staff]',
        'priority' => 'permit_empty|in_list[low,normal,high,urgent,critical]',
        'title' => 'required|min_length[3]|max_length[200]',
        'message' => 'required|min_length[5]|max_length[1000]',
        'channel' => 'permit_empty|in_list[email,sms,push,in_app,webhook,whatsapp,telegram]',
        'recipient_type' => 'permit_empty|in_list[user,customer,employee,admin,system,all]',
        'recipient_email' => 'permit_empty|valid_email',
        'recipient_phone' => 'permit_empty|min_length[10]|max_length[20]',
        'status' => 'permit_empty|in_list[pending,scheduled,sent,delivered,failed,cancelled,expired]',
        'scheduled_at' => 'permit_empty|valid_date',
        'expires_at' => 'permit_empty|valid_date',
        'max_retries' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[10]'
    ];
    
    protected $validationMessages = [
        'type' => [
            'required' => 'Tipo de notificação é obrigatório',
            'in_list' => 'Tipo de notificação inválido'
        ],
        'title' => [
            'required' => 'Título é obrigatório',
            'min_length' => 'Título deve ter pelo menos 3 caracteres',
            'max_length' => 'Título não pode exceder 200 caracteres'
        ],
        'message' => [
            'required' => 'Mensagem é obrigatória',
            'min_length' => 'Mensagem deve ter pelo menos 5 caracteres',
            'max_length' => 'Mensagem não pode exceder 1000 caracteres'
        ],
        'recipient_email' => [
            'valid_email' => 'Email deve ter um formato válido'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'generateNotificationCode', 'prepareNotificationData'];
    protected $beforeUpdate = ['updateTimestamps', 'validateStatusChange'];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['category'])) {
            $data['data']['category'] = 'operational';
        }
        
        if (!isset($data['data']['priority'])) {
            $data['data']['priority'] = 'normal';
        }
        
        if (!isset($data['data']['channel'])) {
            $data['data']['channel'] = 'in_app';
        }
        
        if (!isset($data['data']['recipient_type'])) {
            $data['data']['recipient_type'] = 'user';
        }
        
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'pending';
        }
        
        if (!isset($data['data']['is_read'])) {
            $data['data']['is_read'] = 0;
        }
        
        if (!isset($data['data']['is_sent'])) {
            $data['data']['is_sent'] = 0;
        }
        
        if (!isset($data['data']['is_delivered'])) {
            $data['data']['is_delivered'] = 0;
        }
        
        if (!isset($data['data']['is_clicked'])) {
            $data['data']['is_clicked'] = 0;
        }
        
        if (!isset($data['data']['retry_count'])) {
            $data['data']['retry_count'] = 0;
        }
        
        if (!isset($data['data']['max_retries'])) {
            $data['data']['max_retries'] = 3;
        }
        
        // Define ícone baseado no tipo se não informado
        if (!isset($data['data']['icon'])) {
            $icons = [
                'order' => 'shopping-cart',
                'reservation' => 'calendar',
                'payment' => 'credit-card',
                'inventory' => 'package',
                'system' => 'settings',
                'marketing' => 'megaphone',
                'reminder' => 'bell',
                'alert' => 'alert-triangle',
                'info' => 'info',
                'warning' => 'alert-circle',
                'error' => 'x-circle',
                'success' => 'check-circle'
            ];
            $data['data']['icon'] = $icons[$data['data']['type']] ?? 'bell';
        }
        
        // Define cor baseada na prioridade se não informada
        if (!isset($data['data']['color'])) {
            $colors = [
                'low' => '#6c757d',
                'normal' => '#007bff',
                'high' => '#ffc107',
                'urgent' => '#fd7e14',
                'critical' => '#dc3545'
            ];
            $data['data']['color'] = $colors[$data['data']['priority']] ?? '#007bff';
        }
        
        // Define expiração padrão se não informada (7 dias)
        if (!isset($data['data']['expires_at'])) {
            $data['data']['expires_at'] = date('Y-m-d H:i:s', strtotime('+7 days'));
        }
        
        return $data;
    }
    
    /**
     * Gera código único da notificação
     */
    protected function generateNotificationCode(array $data): array
    {
        if (!isset($data['data']['notification_code']) || empty($data['data']['notification_code'])) {
            $restaurantId = $data['data']['restaurant_id'] ?? $this->getCurrentTenantId();
            $type = strtoupper(substr($data['data']['type'] ?? 'NOTIF', 0, 5));
            $timestamp = date('ymdHis');
            
            // Busca o último código gerado hoje
            $lastCode = $this->where('restaurant_id', $restaurantId)
                           ->where('DATE(created_at)', date('Y-m-d'))
                           ->orderBy('id', 'DESC')
                           ->first();
            
            $sequence = 1;
            if ($lastCode && !empty($lastCode['notification_code'])) {
                $lastSequence = (int) substr($lastCode['notification_code'], -4);
                $sequence = $lastSequence + 1;
            }
            
            $data['data']['notification_code'] = $type . $timestamp . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }
    
    /**
     * Prepara dados da notificação
     */
    protected function prepareNotificationData(array $data): array
    {
        // Gerar ID de rastreamento único
        if (!isset($data['data']['tracking_id'])) {
            $data['data']['tracking_id'] = uniqid('track_', true);
        }
        
        // Preparar corpo do email se canal for email
        if (isset($data['data']['channel']) && $data['data']['channel'] === 'email') {
            if (!isset($data['data']['email_subject'])) {
                $data['data']['email_subject'] = $data['data']['title'];
            }
            if (!isset($data['data']['email_body'])) {
                $data['data']['email_body'] = $data['data']['message'];
            }
        }
        
        // Preparar corpo do SMS se canal for SMS
        if (isset($data['data']['channel']) && $data['data']['channel'] === 'sms') {
            if (!isset($data['data']['sms_body'])) {
                $data['data']['sms_body'] = $data['data']['message'];
            }
        }
        
        // Preparar corpo do push se canal for push
        if (isset($data['data']['channel']) && $data['data']['channel'] === 'push') {
            if (!isset($data['data']['push_body'])) {
                $data['data']['push_body'] = $data['data']['message'];
            }
        }
        
        return $data;
    }
    
    /**
     * Atualiza timestamps de atividade
     */
    protected function updateTimestamps(array $data): array
    {
        // Atualizar timestamps baseado no status
        if (isset($data['data']['status'])) {
            switch ($data['data']['status']) {
                case 'sent':
                    if (!isset($data['data']['sent_at'])) {
                        $data['data']['sent_at'] = date('Y-m-d H:i:s');
                        $data['data']['is_sent'] = 1;
                    }
                    break;
                case 'delivered':
                    if (!isset($data['data']['delivered_at'])) {
                        $data['data']['delivered_at'] = date('Y-m-d H:i:s');
                        $data['data']['is_delivered'] = 1;
                    }
                    break;
            }
        }
        
        // Marcar como lida se is_read for definido
        if (isset($data['data']['is_read']) && $data['data']['is_read'] && !isset($data['data']['read_at'])) {
            $data['data']['read_at'] = date('Y-m-d H:i:s');
        }
        
        // Marcar como clicada se is_clicked for definido
        if (isset($data['data']['is_clicked']) && $data['data']['is_clicked'] && !isset($data['data']['clicked_at'])) {
            $data['data']['clicked_at'] = date('Y-m-d H:i:s');
        }
        
        return $data;
    }
    
    /**
     * Valida mudanças de status
     */
    protected function validateStatusChange(array $data): array
    {
        // Implementar lógica de validação de mudança de status se necessário
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Busca notificação por código
     */
    public function findByCode(string $notificationCode): ?array
    {
        return $this->where('notification_code', $notificationCode)->first();
    }
    
    /**
     * Busca notificação por ID de rastreamento
     */
    public function findByTrackingId(string $trackingId): ?array
    {
        return $this->where('tracking_id', $trackingId)->first();
    }
    
    /**
     * Obtém notificações por usuário
     */
    public function getNotificationsByUser(int $userId, bool $unreadOnly = false): array
    {
        $builder = $this->where('user_id', $userId)
                       ->orWhere('recipient_type', 'all');
        
        if ($unreadOnly) {
            $builder->where('is_read', 0);
        }
        
        return $builder->where('expires_at >', date('Y-m-d H:i:s'))
                      ->orderBy('created_at', 'DESC')
                      ->findAll();
    }
    
    /**
     * Obtém notificações por cliente
     */
    public function getNotificationsByCustomer(int $customerId, bool $unreadOnly = false): array
    {
        $builder = $this->where('customer_id', $customerId);
        
        if ($unreadOnly) {
            $builder->where('is_read', 0);
        }
        
        return $builder->where('expires_at >', date('Y-m-d H:i:s'))
                      ->orderBy('created_at', 'DESC')
                      ->findAll();
    }
    
    /**
     * Obtém notificações por tipo
     */
    public function getNotificationsByType(string $type): array
    {
        return $this->where('type', $type)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém notificações por categoria
     */
    public function getNotificationsByCategory(string $category): array
    {
        return $this->where('category', $category)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém notificações por prioridade
     */
    public function getNotificationsByPriority(string $priority): array
    {
        return $this->where('priority', $priority)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém notificações por status
     */
    public function getNotificationsByStatus(string $status): array
    {
        return $this->where('status', $status)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém notificações pendentes
     */
    public function getPendingNotifications(): array
    {
        return $this->where('status', 'pending')
                   ->where('scheduled_at <=', date('Y-m-d H:i:s'))
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('priority', 'DESC')
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém notificações agendadas
     */
    public function getScheduledNotifications(): array
    {
        return $this->where('status', 'scheduled')
                   ->where('scheduled_at >', date('Y-m-d H:i:s'))
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('scheduled_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém notificações falhadas para retry
     */
    public function getFailedNotificationsForRetry(): array
    {
        return $this->where('status', 'failed')
                   ->where('retry_count <', 'max_retries', false)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->groupStart()
                       ->where('last_retry_at IS NULL')
                       ->orWhere('last_retry_at <', date('Y-m-d H:i:s', strtotime('-1 hour')))
                   ->groupEnd()
                   ->orderBy('priority', 'DESC')
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Obtém notificações não lidas por usuário
     */
    public function getUnreadNotifications(int $userId): array
    {
        return $this->where('user_id', $userId)
                   ->where('is_read', 0)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('priority', 'DESC')
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Conta notificações não lidas por usuário
     */
    public function countUnreadNotifications(int $userId): int
    {
        return $this->where('user_id', $userId)
                   ->where('is_read', 0)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->countAllResults();
    }
    
    /**
     * Marca notificação como lida
     */
    public function markAsRead(int $notificationId): bool
    {
        return $this->update($notificationId, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Marca todas as notificações do usuário como lidas
     */
    public function markAllAsReadByUser(int $userId): bool
    {
        return $this->where('user_id', $userId)
                   ->where('is_read', 0)
                   ->set([
                       'is_read' => 1,
                       'read_at' => date('Y-m-d H:i:s')
                   ])
                   ->update();
    }
    
    /**
     * Marca notificação como clicada
     */
    public function markAsClicked(int $notificationId): bool
    {
        return $this->update($notificationId, [
            'is_clicked' => 1,
            'clicked_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Atualiza status da notificação
     */
    public function updateStatus(int $notificationId, string $status, string $response = null): bool
    {
        $updateData = ['status' => $status];
        
        switch ($status) {
            case 'sent':
                $updateData['is_sent'] = 1;
                $updateData['sent_at'] = date('Y-m-d H:i:s');
                break;
            case 'delivered':
                $updateData['is_delivered'] = 1;
                $updateData['delivered_at'] = date('Y-m-d H:i:s');
                break;
            case 'failed':
                $updateData['retry_count'] = 'retry_count + 1';
                $updateData['last_retry_at'] = date('Y-m-d H:i:s');
                if ($response) {
                    $updateData['error_message'] = $response;
                }
                break;
        }
        
        if ($response) {
            $updateData['delivery_response'] = $response;
        }
        
        return $this->update($notificationId, $updateData);
    }
    
    /**
     * Agenda notificação
     */
    public function scheduleNotification(array $notificationData, string $scheduledAt): ?int
    {
        $notificationData['status'] = 'scheduled';
        $notificationData['scheduled_at'] = $scheduledAt;
        
        return $this->insert($notificationData);
    }
    
    /**
     * Cancela notificação
     */
    public function cancelNotification(int $notificationId, string $reason = ''): bool
    {
        $updateData = ['status' => 'cancelled'];
        
        if (!empty($reason)) {
            $updateData['error_message'] = $reason;
        }
        
        return $this->update($notificationId, $updateData);
    }
    
    /**
     * Remove notificações expiradas
     */
    public function removeExpiredNotifications(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
                   ->delete();
    }
    
    /**
     * Busca avançada de notificações
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                             ->like('title', $filters['search'])
                             ->orLike('message', $filters['search'])
                             ->orLike('notification_code', $filters['search'])
                             ->groupEnd();
        }
        
        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $builder = $builder->whereIn('type', $filters['type']);
            } else {
                $builder = $builder->where('type', $filters['type']);
            }
        }
        
        if (!empty($filters['category'])) {
            if (is_array($filters['category'])) {
                $builder = $builder->whereIn('category', $filters['category']);
            } else {
                $builder = $builder->where('category', $filters['category']);
            }
        }
        
        if (!empty($filters['priority'])) {
            if (is_array($filters['priority'])) {
                $builder = $builder->whereIn('priority', $filters['priority']);
            } else {
                $builder = $builder->where('priority', $filters['priority']);
            }
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder = $builder->whereIn('status', $filters['status']);
            } else {
                $builder = $builder->where('status', $filters['status']);
            }
        }
        
        if (!empty($filters['channel'])) {
            if (is_array($filters['channel'])) {
                $builder = $builder->whereIn('channel', $filters['channel']);
            } else {
                $builder = $builder->where('channel', $filters['channel']);
            }
        }
        
        if (!empty($filters['user_id'])) {
            $builder = $builder->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['customer_id'])) {
            $builder = $builder->where('customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['is_read'])) {
            $builder = $builder->where('is_read', $filters['is_read']);
        }
        
        if (!empty($filters['is_sent'])) {
            $builder = $builder->where('is_sent', $filters['is_sent']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder = $builder->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder = $builder->where('created_at <=', $filters['date_to']);
        }
        
        if (!empty($filters['exclude_expired'])) {
            $builder = $builder->where('expires_at >', date('Y-m-d H:i:s'));
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $builder->orderBy($orderBy, $orderDir)->findAll();
    }
    
    /**
     * Obtém estatísticas das notificações
     */
    public function getNotificationStats(): array
    {
        $stats = [];
        
        // Total de notificações
        $stats['total_notifications'] = $this->countAllResults();
        $stats['active_notifications'] = $this->where('expires_at >', date('Y-m-d H:i:s'))->countAllResults();
        $stats['expired_notifications'] = $this->where('expires_at <=', date('Y-m-d H:i:s'))->countAllResults();
        
        // Notificações por status
        $statusStats = $this->select('status, COUNT(*) as count')
                           ->groupBy('status')
                           ->findAll();
        
        $stats['notifications_by_status'] = [];
        foreach ($statusStats as $status) {
            $stats['notifications_by_status'][$status['status']] = $status['count'];
        }
        
        // Notificações por tipo
        $typeStats = $this->select('type, COUNT(*) as count')
                         ->groupBy('type')
                         ->findAll();
        
        $stats['notifications_by_type'] = [];
        foreach ($typeStats as $type) {
            $stats['notifications_by_type'][$type['type']] = $type['count'];
        }
        
        // Notificações por canal
        $channelStats = $this->select('channel, COUNT(*) as count')
                            ->groupBy('channel')
                            ->findAll();
        
        $stats['notifications_by_channel'] = [];
        foreach ($channelStats as $channel) {
            $stats['notifications_by_channel'][$channel['channel']] = $channel['count'];
        }
        
        // Notificações por prioridade
        $priorityStats = $this->select('priority, COUNT(*) as count')
                             ->groupBy('priority')
                             ->findAll();
        
        $stats['notifications_by_priority'] = [];
        foreach ($priorityStats as $priority) {
            $stats['notifications_by_priority'][$priority['priority']] = $priority['count'];
        }
        
        // Taxas de engajamento
        $engagementStats = $this->select('COUNT(*) as total, SUM(is_read) as read_count, SUM(is_clicked) as clicked_count, SUM(is_delivered) as delivered_count')
                               ->where('expires_at >', date('Y-m-d H:i:s'))
                               ->first();
        
        $total = $engagementStats['total'] ?? 0;
        $stats['read_rate'] = $total > 0 ? round(($engagementStats['read_count'] / $total) * 100, 2) : 0;
        $stats['click_rate'] = $total > 0 ? round(($engagementStats['clicked_count'] / $total) * 100, 2) : 0;
        $stats['delivery_rate'] = $total > 0 ? round(($engagementStats['delivered_count'] / $total) * 100, 2) : 0;
        
        // Notificações hoje
        $stats['notifications_today'] = $this->where('DATE(created_at)', date('Y-m-d'))->countAllResults();
        $stats['notifications_sent_today'] = $this->where('DATE(sent_at)', date('Y-m-d'))->countAllResults();
        $stats['notifications_read_today'] = $this->where('DATE(read_at)', date('Y-m-d'))->countAllResults();
        
        // Notificações pendentes
        $stats['pending_notifications'] = $this->where('status', 'pending')
                                              ->where('scheduled_at <=', date('Y-m-d H:i:s'))
                                              ->where('expires_at >', date('Y-m-d H:i:s'))
                                              ->countAllResults();
        
        $stats['scheduled_notifications'] = $this->where('status', 'scheduled')
                                                ->where('scheduled_at >', date('Y-m-d H:i:s'))
                                                ->where('expires_at >', date('Y-m-d H:i:s'))
                                                ->countAllResults();
        
        $stats['failed_notifications'] = $this->where('status', 'failed')
                                             ->where('retry_count <', 'max_retries', false)
                                             ->where('expires_at >', date('Y-m-d H:i:s'))
                                             ->countAllResults();
        
        return $stats;
    }
    
    /**
     * Obtém relatório de engajamento
     */
    public function getEngagementReport(string $startDate, string $endDate): array
    {
        $notifications = $this->where('created_at >=', $startDate)
                             ->where('created_at <=', $endDate)
                             ->findAll();
        
        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'total_notifications' => count($notifications),
            'sent_notifications' => 0,
            'delivered_notifications' => 0,
            'read_notifications' => 0,
            'clicked_notifications' => 0,
            'failed_notifications' => 0,
            'by_type' => [],
            'by_channel' => [],
            'by_priority' => []
        ];
        
        foreach ($notifications as $notification) {
            // Contadores gerais
            if ($notification['is_sent']) $report['sent_notifications']++;
            if ($notification['is_delivered']) $report['delivered_notifications']++;
            if ($notification['is_read']) $report['read_notifications']++;
            if ($notification['is_clicked']) $report['clicked_notifications']++;
            if ($notification['status'] === 'failed') $report['failed_notifications']++;
            
            // Por tipo
            $type = $notification['type'];
            if (!isset($report['by_type'][$type])) {
                $report['by_type'][$type] = ['total' => 0, 'sent' => 0, 'read' => 0, 'clicked' => 0];
            }
            $report['by_type'][$type]['total']++;
            if ($notification['is_sent']) $report['by_type'][$type]['sent']++;
            if ($notification['is_read']) $report['by_type'][$type]['read']++;
            if ($notification['is_clicked']) $report['by_type'][$type]['clicked']++;
            
            // Por canal
            $channel = $notification['channel'];
            if (!isset($report['by_channel'][$channel])) {
                $report['by_channel'][$channel] = ['total' => 0, 'sent' => 0, 'delivered' => 0, 'read' => 0];
            }
            $report['by_channel'][$channel]['total']++;
            if ($notification['is_sent']) $report['by_channel'][$channel]['sent']++;
            if ($notification['is_delivered']) $report['by_channel'][$channel]['delivered']++;
            if ($notification['is_read']) $report['by_channel'][$channel]['read']++;
            
            // Por prioridade
            $priority = $notification['priority'];
            if (!isset($report['by_priority'][$priority])) {
                $report['by_priority'][$priority] = ['total' => 0, 'sent' => 0, 'read' => 0];
            }
            $report['by_priority'][$priority]['total']++;
            if ($notification['is_sent']) $report['by_priority'][$priority]['sent']++;
            if ($notification['is_read']) $report['by_priority'][$priority]['read']++;
        }
        
        // Calcular taxas
        $total = $report['total_notifications'];
        $report['send_rate'] = $total > 0 ? round(($report['sent_notifications'] / $total) * 100, 2) : 0;
        $report['delivery_rate'] = $total > 0 ? round(($report['delivered_notifications'] / $total) * 100, 2) : 0;
        $report['read_rate'] = $total > 0 ? round(($report['read_notifications'] / $total) * 100, 2) : 0;
        $report['click_rate'] = $total > 0 ? round(($report['clicked_notifications'] / $total) * 100, 2) : 0;
        $report['failure_rate'] = $total > 0 ? round(($report['failed_notifications'] / $total) * 100, 2) : 0;
        
        return $report;
    }
    
    /**
     * Exporta notificações para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $notifications = $this->advancedSearch($filters);
        
        $csv = "Código,Tipo,Categoria,Prioridade,Título,Canal,Status,Destinatário,Criado,Enviado,Lido,Clicado\n";
        
        foreach ($notifications as $notification) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $notification['notification_code'],
                $notification['type'],
                $notification['category'] ?? '',
                $notification['priority'],
                $notification['title'],
                $notification['channel'] ?? '',
                $notification['status'],
                $notification['recipient_email'] ?? $notification['recipient_phone'] ?? 'N/A',
                $notification['created_at'],
                $notification['sent_at'] ?? 'N/A',
                $notification['is_read'] ? 'Sim' : 'Não',
                $notification['is_clicked'] ? 'Sim' : 'Não'
            );
        }
        
        return $csv;
    }
    
    /**
     * Cria notificação em lote
     */
    public function createBulkNotifications(array $recipients, array $notificationData): array
    {
        $createdIds = [];
        
        foreach ($recipients as $recipient) {
            $data = array_merge($notificationData, [
                'recipient_type' => $recipient['type'] ?? 'user',
                'recipient_id' => $recipient['id'] ?? null,
                'recipient_email' => $recipient['email'] ?? null,
                'recipient_phone' => $recipient['phone'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'customer_id' => $recipient['customer_id'] ?? null
            ]);
            
            $id = $this->insert($data);
            if ($id) {
                $createdIds[] = $id;
            }
        }
        
        return $createdIds;
    }
    
    /**
     * Verifica se código da notificação já existe
     */
    public function notificationCodeExists(string $notificationCode, ?int $excludeId = null): bool
    {
        $builder = $this->where('notification_code', $notificationCode);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
}