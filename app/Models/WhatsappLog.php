<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * WhatsApp Message Log Model
 * 
 * Gerencia os logs das mensagens enviadas e recebidas via WhatsApp Business
 */
class WhatsappLog extends Model {
    
    protected $table = 'whatsapp_message_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'phone_number', 'message', 'message_type', 'status', 'whatsapp_message_id',
        'supplier_id', 'purchase_id', 'item_ids', 'metadata', 'error_message',
        'sent_at', 'delivered_at', 'read_at', 'created_by', 'ip_address', 'user_agent'
    ];
    
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
    protected $validationRules = [
        'phone_number' => 'required|min_length[10]|max_length[20]',
        'message' => 'required|max_length[4096]',
        'message_type' => 'required|in_list[inbound,outbound]',
        'status' => 'required|in_list[pending,sent,delivered,read,failed]'
    ];
    
    protected $validationMessages = [
        'phone_number' => [
            'required' => 'Número de telefone é obrigatório',
            'min_length' => 'Número de telefone deve ter pelo menos 10 dígitos',
            'max_length' => 'Número de telefone deve ter no máximo 20 dígitos'
        ],
        'message' => [
            'required' => 'Mensagem é obrigatória',
            'max_length' => 'Mensagem deve ter no máximo 4096 caracteres'
        ]
    ];
    
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    
    /**
     * Registra uma nova mensagem no log
     * 
     * @param array $data Dados da mensagem
     * @return int|bool ID do registro criado ou false em caso de erro
     */
    public function log_message($data) {
        // Validar dados obrigatórios
        if (empty($data['phone_number']) || empty($data['message'])) {
            return false;
        }
        
        $request = \Config\Services::request();
        
        // Preparar dados para inserção
        $log_data = [
            'phone_number' => $this->clean_phone_number($data['phone_number']),
            'message' => $data['message'],
            'message_type' => $data['message_type'] ?? 'outbound',
            'status' => $data['status'] ?? 'pending',
            'whatsapp_message_id' => $data['whatsapp_message_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'purchase_id' => $data['purchase_id'] ?? null,
            'item_ids' => !empty($data['item_ids']) ? json_encode($data['item_ids']) : null,
            'metadata' => !empty($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_by' => $data['created_by'] ?? null,
            'ip_address' => $data['ip_address'] ?? $request->getIPAddress(),
            'user_agent' => $data['user_agent'] ?? $request->getUserAgent()
        ];
        
        if ($this->insert($log_data)) {
            return $this->getInsertID();
        }
        
        return false;
    }
    
    /**
     * Atualiza o status de uma mensagem
     * 
     * @param int $log_id ID do log
     * @param string $status Novo status
     * @param array $additional_data Dados adicionais
     * @return bool Sucesso da operação
     */
    public function update_message_status($log_id, $status, $additional_data = []) {
        $update_data = ['status' => $status];
        
        // Adicionar timestamps baseados no status
        switch ($status) {
            case 'sent':
                $update_data['sent_at'] = date('Y-m-d H:i:s');
                break;
            case 'delivered':
                $update_data['delivered_at'] = date('Y-m-d H:i:s');
                break;
            case 'read':
                $update_data['read_at'] = date('Y-m-d H:i:s');
                break;
            case 'failed':
                if (!empty($additional_data['error_message'])) {
                    $update_data['error_message'] = $additional_data['error_message'];
                }
                break;
        }
        
        // Adicionar ID da mensagem do WhatsApp se fornecido
        if (!empty($additional_data['whatsapp_message_id'])) {
            $update_data['whatsapp_message_id'] = $additional_data['whatsapp_message_id'];
        }
        
        return $this->update($log_id, $update_data);
    }
    
    /**
     * Obtém logs de mensagens com filtros
     * 
     * @param array $filters Filtros de busca
     * @param int $limit Limite de registros
     * @param int $offset Offset para paginação
     * @return array Lista de logs
     */
    public function get_message_logs($filters = [], $limit = 50, $offset = 0) {
        $builder = $this->db->table($this->table . ' wml')
                           ->select('wml.*, s.company_name as supplier_name, p.reference as purchase_reference')
                           ->join('suppliers s', 's.person_id = wml.supplier_id', 'left')
                           ->join('purchases p', 'p.purchase_id = wml.purchase_id', 'left');
        
        // Aplicar filtros
        if (!empty($filters['phone_number'])) {
            $builder->like('wml.phone_number', $filters['phone_number']);
        }
        
        if (!empty($filters['message_type'])) {
            $builder->where('wml.message_type', $filters['message_type']);
        }
        
        if (!empty($filters['status'])) {
            $builder->where('wml.status', $filters['status']);
        }
        
        if (!empty($filters['supplier_id'])) {
            $builder->where('wml.supplier_id', $filters['supplier_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder->where('DATE(wml.created_at) >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder->where('DATE(wml.created_at) <=', $filters['date_to']);
        }
        
        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('wml.message', $filters['search'])
                    ->orLike('s.company_name', $filters['search'])
                    ->orLike('wml.phone_number', $filters['search'])
                    ->groupEnd();
        }
        
        $builder->orderBy('wml.created_at', 'DESC')
                ->limit($limit, $offset);
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Conta total de logs com filtros
     * 
     * @param array $filters Filtros de busca
     * @return int Total de registros
     */
    public function count_message_logs($filters = []) {
        $builder = $this->db->table($this->table . ' wml')
                           ->join('suppliers s', 's.person_id = wml.supplier_id', 'left');
        
        // Aplicar os mesmos filtros do método get_message_logs
        if (!empty($filters['phone_number'])) {
            $builder->like('wml.phone_number', $filters['phone_number']);
        }
        
        if (!empty($filters['message_type'])) {
            $builder->where('wml.message_type', $filters['message_type']);
        }
        
        if (!empty($filters['status'])) {
            $builder->where('wml.status', $filters['status']);
        }
        
        if (!empty($filters['supplier_id'])) {
            $builder->where('wml.supplier_id', $filters['supplier_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder->where('DATE(wml.created_at) >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder->where('DATE(wml.created_at) <=', $filters['date_to']);
        }
        
        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('wml.message', $filters['search'])
                    ->orLike('s.company_name', $filters['search'])
                    ->orLike('wml.phone_number', $filters['search'])
                    ->groupEnd();
        }
        
        return $builder->countAllResults();
    }
    
    /**
     * Obtém estatísticas das mensagens
     * 
     * @param array $filters Filtros de período
     * @return array Estatísticas
     */
    public function get_message_statistics($filters = []) {
        $builder = $this->builder();
        
        // Aplicar filtros de data
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $builder->where('DATE(created_at) >=', $filters['date_from'])
                   ->where('DATE(created_at) <=', $filters['date_to']);
        } elseif (!empty($filters['days'])) {
            $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$filters['days']} days")));
        }
        
        // Estatísticas gerais usando Query Builder
        $stats_builder = clone $builder;
        $stats = $stats_builder->select([
            'COUNT(*) as total_messages',
            'COUNT(CASE WHEN status = "sent" THEN 1 END) as sent_count',
            'COUNT(CASE WHEN status = "delivered" THEN 1 END) as delivered_count',
            'COUNT(CASE WHEN status = "read" THEN 1 END) as read_count',
            'COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count',
            'COUNT(CASE WHEN message_type = "outbound" THEN 1 END) as outbound_count',
            'COUNT(CASE WHEN message_type = "inbound" THEN 1 END) as inbound_count',
            'ROUND(COUNT(CASE WHEN status IN ("sent", "delivered", "read") THEN 1 END) * 100.0 / COUNT(*), 2) as success_rate'
        ])->get()->getRowArray();
        
        // Estatísticas por dia (últimos 7 dias)
        $daily_builder = $this->builder();
        $daily_stats = $daily_builder->select('DATE(created_at) as date, COUNT(*) as total, COUNT(CASE WHEN status IN ("sent", "delivered", "read") THEN 1 END) as successful')
                                    ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                                    ->groupBy('DATE(created_at)')
                                    ->orderBy('date', 'DESC')
                                    ->get()->getResultArray();
        
        // Top fornecedores por mensagens
        $suppliers_builder = $this->db->table($this->table . ' wml')
                                     ->select('s.company_name, COUNT(wml.id) as message_count')
                                     ->join('suppliers s', 's.person_id = wml.supplier_id')
                                     ->groupBy('wml.supplier_id, s.company_name')
                                     ->orderBy('message_count', 'DESC')
                                     ->limit(5);
        
        // Aplicar filtros de data para fornecedores
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $suppliers_builder->where('DATE(wml.created_at) >=', $filters['date_from'])
                             ->where('DATE(wml.created_at) <=', $filters['date_to']);
        } elseif (!empty($filters['days'])) {
            $suppliers_builder->where('wml.created_at >=', date('Y-m-d H:i:s', strtotime("-{$filters['days']} days")));
        }
        
        $top_suppliers = $suppliers_builder->get()->getResultArray();
        
        return [
            'general' => $stats,
            'daily' => $daily_stats,
            'top_suppliers' => $top_suppliers
        ];
    }
    
    /**
     * Obtém mensagens por fornecedor
     * 
     * @param int $supplier_id ID do fornecedor
     * @param int $limit Limite de registros
     * @return array Lista de mensagens
     */
    public function get_messages_by_supplier($supplier_id, $limit = 20) {
        return $this->where('supplier_id', $supplier_id)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Obtém mensagens por compra
     * 
     * @param int $purchase_id ID da compra
     * @return array Lista de mensagens
     */
    public function get_messages_by_purchase($purchase_id) {
        return $this->where('purchase_id', $purchase_id)
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }
    
    /**
     * Limpa logs antigos
     * 
     * @param int $days Dias para manter (padrão: 180)
     * @return int Número de registros removidos
     */
    public function clean_old_logs($days = 180) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Contar registros que serão removidos
        $count = $this->where('created_at <', $cutoff_date)
                     ->whereIn('status', ['delivered', 'read', 'failed'])
                     ->countAllResults(false);
        
        // Remover registros
        $this->where('created_at <', $cutoff_date)
            ->whereIn('status', ['delivered', 'read', 'failed'])
            ->delete();
        
        return $count;
    }
    
    /**
     * Obtém log por ID
     * 
     * @param int $log_id ID do log
     * @return array|null Dados do log
     */
    public function get_log_by_id($log_id) {
        return $this->db->table($this->table . ' wml')
                        ->select('wml.*, s.company_name as supplier_name, p.reference as purchase_reference')
                        ->join('suppliers s', 's.person_id = wml.supplier_id', 'left')
                        ->join('purchases p', 'p.purchase_id = wml.purchase_id', 'left')
                        ->where('wml.id', $log_id)
                        ->get()
                        ->getRowArray();
    }
    
    /**
     * Verifica se uma mensagem já foi enviada recentemente
     * 
     * @param string $phone_number Número de telefone
     * @param string $message_hash Hash da mensagem
     * @param int $minutes Minutos para considerar como recente
     * @return bool True se já foi enviada recentemente
     */
    public function is_duplicate_message($phone_number, $message_hash, $minutes = 5) {
        $count = $this->where('phone_number', $this->clean_phone_number($phone_number))
                     ->where('MD5(message)', $message_hash)
                     ->where('created_at >', date('Y-m-d H:i:s', strtotime("-{$minutes} minutes")))
                     ->countAllResults();
        
        return $count > 0;
    }
    
    /**
     * Limpa número de telefone
     * 
     * @param string $phone_number Número de telefone
     * @return string Número limpo
     */
    private function clean_phone_number($phone_number) {
        // Remove todos os caracteres não numéricos
        $clean = preg_replace('/[^0-9]/', '', $phone_number);
        
        // Se começar com 0, remove o 0 inicial
        if (substr($clean, 0, 1) === '0') {
            $clean = substr($clean, 1);
        }
        
        // Se não começar com código do país, adiciona +55 (Brasil)
        if (strlen($clean) === 10 || strlen($clean) === 11) {
            $clean = '55' . $clean;
        }
        
        return $clean;
    }
    
    /**
     * Exporta logs para CSV
     * 
     * @param array $filters Filtros de busca
     * @return string Conteúdo CSV
     */
    public function export_to_csv($filters = []) {
        $logs = $this->get_message_logs($filters, 10000, 0); // Limite alto para exportação
        
        $csv_content = "ID,Data/Hora,Telefone,Tipo,Status,Fornecedor,Compra,Mensagem\n";
        
        foreach ($logs as $log) {
            $csv_content .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $log['id'],
                $log['created_at'],
                $log['phone_number'],
                $log['message_type'],
                $log['status'],
                $log['supplier_name'] ?? '',
                $log['purchase_reference'] ?? '',
                str_replace('"', '""', $log['message'])
            );
        }
        
        return $csv_content;
    }
}

/* End of file WhatsappLog.php */
/* Location: ./application/models/WhatsappLog.php */