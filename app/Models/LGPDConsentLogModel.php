<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo para logs de consentimentos LGPD
 */
class LGPDConsentLogModel extends Model
{
    protected $table = 'lgpd_consent_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'consent_id',
        'data_subject_id',
        'action',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
        'metadata'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'data_subject_id' => 'required|max_length[255]',
        'action' => 'required|max_length[50]',
        'ip_address' => 'required|valid_ip'
    ];

    protected $validationMessages = [
        'data_subject_id' => [
            'required' => 'O identificador do titular é obrigatório',
            'max_length' => 'O identificador do titular não pode exceder 255 caracteres'
        ],
        'action' => [
            'required' => 'A ação é obrigatória',
            'max_length' => 'A ação não pode exceder 50 caracteres'
        ],
        'ip_address' => [
            'required' => 'O endereço IP é obrigatório',
            'valid_ip' => 'O endereço IP deve ser válido'
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
     * Registra uma ação de consentimento
     */
    public function logAction($consentId, $dataSubjectId, $action, $oldValue = null, $newValue = null, $metadata = null)
    {
        $data = [
            'consent_id' => $consentId,
            'data_subject_id' => $dataSubjectId,
            'action' => $action,
            'old_value' => $oldValue ? json_encode($oldValue) : null,
            'new_value' => $newValue ? json_encode($newValue) : null,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => $metadata ? json_encode($metadata) : null
        ];

        return $this->insert($data);
    }

    /**
     * Busca logs por titular
     */
    public function getLogsBySubject($dataSubjectId, $limit = 100)
    {
        return $this->where('data_subject_id', $dataSubjectId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Busca logs por consentimento
     */
    public function getLogsByConsent(int $consentId, int $limit = 50)
    {
        return $this->where('consent_id', $consentId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Busca logs por ação
     */
    public function getLogsByAction(string $action, int $limit = 100)
    {
        return $this->where('action', $action)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Busca logs recentes
     */
    public function getRecentLogs(int $limit = 100)
    {
        return $this->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Estatísticas de logs por período
     */
    public function getStatsByPeriod(string $startDate, string $endDate)
    {
        return $this->select('action, COUNT(*) as total, DATE(created_at) as date')
                   ->where('created_at >=', $startDate)
                   ->where('created_at <=', $endDate)
                   ->groupBy(['action', 'DATE(created_at)'])
                   ->orderBy('date', 'DESC')
                   ->findAll();
    }

    /**
     * Conta logs por mês
     */
    public function getMonthlyCount(string $month)
    {
        return $this->where('DATE_FORMAT(created_at, "%Y-%m")', $month)
                   ->countAllResults();
    }

    /**
     * Limpa logs antigos (para manutenção)
     */
    public function cleanOldLogs($days = 365)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->where('created_at <', $cutoffDate)
                   ->delete();
    }

    /**
     * Obtém o IP do cliente
     */
    private function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}