<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo para gerenciamento de consentimentos LGPD
 */
class LGPDConsentModel extends Model
{
    protected $table = 'lgpd_consents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'data_subject_id',
        'consent_type',
        'consent_given',
        'consent_text',
        'ip_address',
        'user_agent',
        'consent_version',
        'expires_at',
        'revoked_at',
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
        'consent_type' => 'required|max_length[50]',
        'consent_given' => 'required|in_list[0,1]',
        'ip_address' => 'required|valid_ip',
        'consent_version' => 'max_length[10]'
    ];

    protected $validationMessages = [
        'data_subject_id' => [
            'required' => 'O identificador do titular é obrigatório',
            'max_length' => 'O identificador do titular não pode exceder 255 caracteres'
        ],
        'consent_type' => [
            'required' => 'O tipo de consentimento é obrigatório',
            'max_length' => 'O tipo de consentimento não pode exceder 50 caracteres'
        ],
        'consent_given' => [
            'required' => 'O status do consentimento é obrigatório',
            'in_list' => 'O status do consentimento deve ser 0 ou 1'
        ],
        'ip_address' => [
            'required' => 'O endereço IP é obrigatório',
            'valid_ip' => 'O endereço IP deve ser válido'
        ],
        'consent_version' => [
            'required' => 'A versão do consentimento é obrigatória',
            'max_length' => 'A versão do consentimento não pode exceder 10 caracteres'
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
     * Busca consentimento ativo por titular e tipo
     */
    public function getActiveConsent(string $dataSubjectId, string $consentType)
    {
        return $this->where('data_subject_id', $dataSubjectId)
                   ->where('consent_type', $consentType)
                   ->where('consent_given', 1)
                   ->where('(expires_at IS NULL OR expires_at > NOW())')
                   ->where('revoked_at IS NULL')
                   ->orderBy('created_at', 'DESC')
                   ->first();
    }

    /**
     * Busca histórico de consentimentos por titular
     */
    public function getConsentHistory(string $dataSubjectId, int $limit = 50)
    {
        return $this->where('data_subject_id', $dataSubjectId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Revoga consentimento
     */
    public function revokeConsent(int $consentId)
    {
        return $this->update($consentId, [
            'consent_given' => 0,
            'revoked_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verifica se titular possui consentimento ativo
     */
    public function hasConsent(string $dataSubjectId, string $consentType)
    {
        return $this->where('data_subject_id', $dataSubjectId)
                   ->where('consent_type', $consentType)
                   ->where('consent_given', 1)
                   ->where('(expires_at IS NULL OR expires_at > NOW())')
                   ->where('revoked_at IS NULL')
                   ->first() !== null;
    }

    /**
     * Busca consentimento por titular e tipo
     */
    public function getConsentBySubjectAndType(string $dataSubjectId, string $consentType)
    {
        return $this->where('data_subject_id', $dataSubjectId)
                   ->where('consent_type', $consentType)
                   ->orderBy('created_at', 'DESC')
                   ->first();
    }

    /**
     * Conta consentimentos por tipo
     */
    public function countByType(string $consentType = null)
    {
        $builder = $this->builder();
        
        if ($consentType) {
            $builder->where('consent_type', $consentType);
        }
        
        return $builder->where('consent_given', 1)
                      ->where('(expires_at IS NULL OR expires_at > NOW())')
                      ->where('revoked_at IS NULL')
                      ->countAllResults();
    }

    /**
     * Busca consentimentos expirados
     */
    public function getExpiredConsents()
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
                   ->where('consent_given', 1)
                   ->where('revoked_at IS NULL')
                   ->findAll();
    }

    /**
     * Estatísticas de consentimentos por mês
     */
    public function getMonthlyStats(string $month)
    {
        return $this->select('consent_type, COUNT(*) as total')
                   ->where('DATE_FORMAT(created_at, "%Y-%m")', $month)
                   ->where('consent_given', 1)
                   ->groupBy('consent_type')
                   ->findAll();
    }
}