<?php

namespace App\Models;

use CodeIgniter\Model;

class Lead extends Model
{
    protected $table = 'leads';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'nome',
        'email', 
        'telefone',
        'restaurante',
        'cidade',
        'interesse',
        'origem',
        'ip',
        'user_agent',
        'status',
        'observacoes',
        'data_contato',
        'convertido_em',
        'valor_contrato',
        'created_at',
        'updated_at'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'nome' => 'required|min_length[2]|max_length[100]',
        'email' => 'required|valid_email|max_length[150]',
        'telefone' => 'required|min_length[10]|max_length[20]',
        'restaurante' => 'required|min_length[2]|max_length[100]',
        'cidade' => 'required|min_length[2]|max_length[50]',
        'interesse' => 'required|in_list[estoque,whatsapp,delivery,relatorios,completo]',
        'status' => 'in_list[novo,contatado,qualificado,proposta,fechado,perdido]'
    ];
    
    protected $validationMessages = [
        'nome' => [
            'required' => 'O nome é obrigatório',
            'min_length' => 'O nome deve ter pelo menos 2 caracteres',
            'max_length' => 'O nome não pode ter mais de 100 caracteres'
        ],
        'email' => [
            'required' => 'O email é obrigatório',
            'valid_email' => 'Digite um email válido',
            'max_length' => 'O email não pode ter mais de 150 caracteres'
        ],
        'telefone' => [
            'required' => 'O telefone é obrigatório',
            'min_length' => 'O telefone deve ter pelo menos 10 dígitos',
            'max_length' => 'O telefone não pode ter mais de 20 caracteres'
        ],
        'restaurante' => [
            'required' => 'O nome do restaurante é obrigatório',
            'min_length' => 'O nome do restaurante deve ter pelo menos 2 caracteres',
            'max_length' => 'O nome do restaurante não pode ter mais de 100 caracteres'
        ],
        'cidade' => [
            'required' => 'A cidade é obrigatória',
            'min_length' => 'A cidade deve ter pelo menos 2 caracteres',
            'max_length' => 'A cidade não pode ter mais de 50 caracteres'
        ],
        'interesse' => [
            'required' => 'Selecione uma área de interesse',
            'in_list' => 'Área de interesse inválida'
        ]
    ];
    
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['formatarTelefone'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['formatarTelefone'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Formatar telefone antes de salvar
     */
    protected function formatarTelefone(array $data)
    {
        if (isset($data['data']['telefone'])) {
            // Remove caracteres não numéricos
            $telefone = preg_replace('/[^0-9]/', '', $data['data']['telefone']);
            
            // Adiciona código do país se necessário
            if (strlen($telefone) === 11 && substr($telefone, 0, 2) !== '55') {
                $telefone = '55' . $telefone;
            }
            
            $data['data']['telefone'] = $telefone;
        }
        
        return $data;
    }

    /**
     * Buscar leads por status
     */
    public function porStatus($status)
    {
        return $this->where('status', $status)->findAll();
    }

    /**
     * Buscar leads novos (últimas 24h)
     */
    public function novos()
    {
        return $this->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                   ->where('status', 'novo')
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Estatísticas de leads
     */
    public function estatisticas($periodo = 30)
    {
        $dataInicio = date('Y-m-d H:i:s', strtotime("-{$periodo} days"));
        
        return [
            'total' => $this->where('created_at >=', $dataInicio)->countAllResults(false),
            'novos' => $this->where('status', 'novo')->countAllResults(false),
            'contatados' => $this->where('status', 'contatado')->countAllResults(false),
            'qualificados' => $this->where('status', 'qualificado')->countAllResults(false),
            'fechados' => $this->where('status', 'fechado')->countAllResults(false),
            'perdidos' => $this->where('status', 'perdido')->countAllResults(false),
            'taxa_conversao' => $this->calcularTaxaConversao($dataInicio)
        ];
    }

    /**
     * Calcular taxa de conversão
     */
    private function calcularTaxaConversao($dataInicio)
    {
        $total = $this->where('created_at >=', $dataInicio)->countAllResults(false);
        $fechados = $this->where('status', 'fechado')
                        ->where('created_at >=', $dataInicio)
                        ->countAllResults();
        
        return $total > 0 ? round(($fechados / $total) * 100, 2) : 0;
    }

    /**
     * Leads por origem
     */
    public function porOrigem($periodo = 30)
    {
        $dataInicio = date('Y-m-d H:i:s', strtotime("-{$periodo} days"));
        
        return $this->select('origem, COUNT(*) as total')
                   ->where('created_at >=', $dataInicio)
                   ->groupBy('origem')
                   ->orderBy('total', 'DESC')
                   ->findAll();
    }

    /**
     * Leads por interesse
     */
    public function porInteresse($periodo = 30)
    {
        $dataInicio = date('Y-m-d H:i:s', strtotime("-{$periodo} days"));
        
        return $this->select('interesse, COUNT(*) as total')
                   ->where('created_at >=', $dataInicio)
                   ->groupBy('interesse')
                   ->orderBy('total', 'DESC')
                   ->findAll();
    }

    /**
     * Leads por cidade
     */
    public function porCidade($periodo = 30)
    {
        $dataInicio = date('Y-m-d H:i:s', strtotime("-{$periodo} days"));
        
        return $this->select('cidade, COUNT(*) as total')
                   ->where('created_at >=', $dataInicio)
                   ->groupBy('cidade')
                   ->orderBy('total', 'DESC')
                   ->limit(10)
                   ->findAll();
    }

    /**
     * Atualizar status do lead
     */
    public function atualizarStatus($id, $status, $observacoes = null)
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($observacoes) {
            $data['observacoes'] = $observacoes;
        }
        
        if ($status === 'contatado' && !$this->find($id)['data_contato']) {
            $data['data_contato'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($id, $data);
    }

    /**
     * Marcar como convertido
     */
    public function marcarConvertido($id, $valorContrato = null)
    {
        $data = [
            'status' => 'fechado',
            'convertido_em' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($valorContrato) {
            $data['valor_contrato'] = $valorContrato;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Buscar leads para follow-up
     */
    public function paraFollowUp()
    {
        // Leads novos há mais de 1 hora
        $novos = $this->where('status', 'novo')
                     ->where('created_at <=', date('Y-m-d H:i:s', strtotime('-1 hour')))
                     ->findAll();
        
        // Leads contatados há mais de 3 dias
        $contatados = $this->where('status', 'contatado')
                          ->where('data_contato <=', date('Y-m-d H:i:s', strtotime('-3 days')))
                          ->findAll();
        
        return array_merge($novos, $contatados);
    }

    /**
     * Verificar se email já existe
     */
    public function emailExiste($email)
    {
        return $this->where('email', $email)->first() !== null;
    }

    /**
     * Obter lead por email
     */
    public function porEmail($email)
    {
        return $this->where('email', $email)->first();
    }
}