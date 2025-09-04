<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\Exceptions\DatabaseException;

class BaseMultiTenantModel extends Model
{
    protected $tenantField = 'restaurant_id';
    protected $currentTenantId = null;
    
    // Campos padrão para soft delete e timestamps
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $dateFormat = 'datetime';
    
    public function __construct()
    {
        parent::__construct();
        $this->setTenantId($this->getCurrentTenantId());
    }
    
    /**
     * Define o ID do tenant atual
     */
    public function setTenantId($tenantId): self
    {
        $this->currentTenantId = $tenantId;
        return $this;
    }
    
    /**
     * Obtém o ID do tenant atual da sessão ou contexto
     */
    protected function getCurrentTenantId(): ?int
    {
        // Primeiro tenta pegar da sessão
        $session = session();
        if ($session->has('restaurant_id')) {
            return $session->get('restaurant_id');
        }
        
        // Depois tenta pegar do header da requisição (para APIs)
        $request = service('request');
        $tenantId = $request->getHeaderLine('X-Tenant-ID');
        if ($tenantId) {
            return (int) $tenantId;
        }
        
        // Por último, tenta pegar de uma variável global
        if (defined('CURRENT_TENANT_ID')) {
            return CURRENT_TENANT_ID;
        }
        
        return null;
    }
    
    /**
     * Adiciona automaticamente o filtro de tenant em todas as consultas
     */
    protected function addTenantScope(): void
    {
        if ($this->currentTenantId && $this->tenantField) {
            $this->where($this->tenantField, $this->currentTenantId);
        }
    }
    
    /**
     * Override do método find para incluir scope de tenant
     */
    public function find($id = null)
    {
        $this->addTenantScope();
        return parent::find($id);
    }
    
    /**
     * Override do método findAll para incluir scope de tenant
     */
    public function findAll(int $limit = 0, int $offset = 0)
    {
        $this->addTenantScope();
        return parent::findAll($limit, $offset);
    }
    
    /**
     * Override do método where para manter o scope de tenant
     */
    public function where($key, $value = null, bool $escape = null)
    {
        $result = parent::where($key, $value, $escape);
        $this->addTenantScope();
        return $result;
    }
    
    /**
     * Override do método insert para adicionar tenant_id automaticamente
     */
    public function insert($data = null, bool $returnID = true)
    {
        if (is_array($data) && $this->currentTenantId && $this->tenantField) {
            $data[$this->tenantField] = $this->currentTenantId;
        }
        
        return parent::insert($data, $returnID);
    }
    
    /**
     * Override do método insertBatch para adicionar tenant_id automaticamente
     */
    public function insertBatch(array $set = null, bool $escape = null, int $batchSize = 100, bool $testing = false)
    {
        if ($set && $this->currentTenantId && $this->tenantField) {
            foreach ($set as &$row) {
                if (is_array($row)) {
                    $row[$this->tenantField] = $this->currentTenantId;
                }
            }
        }
        
        return parent::insertBatch($set, $escape, $batchSize, $testing);
    }
    
    /**
     * Override do método update para manter scope de tenant
     */
    public function update($id = null, $data = null): bool
    {
        $this->addTenantScope();
        return parent::update($id, $data);
    }
    
    /**
     * Override do método delete para manter scope de tenant
     */
    public function delete($id = null, bool $purge = false)
    {
        $this->addTenantScope();
        return parent::delete($id, $purge);
    }
    
    /**
     * Método para buscar dados sem scope de tenant (apenas para super admin)
     */
    public function withoutTenantScope()
    {
        $clone = clone $this;
        $clone->currentTenantId = null;
        return $clone;
    }
    
    /**
     * Valida se o usuário tem acesso ao tenant
     */
    protected function validateTenantAccess(): void
    {
        if (!$this->currentTenantId) {
            throw new DatabaseException('Tenant ID não definido. Acesso negado.');
        }
    }
    
    /**
     * Método para contar registros com scope de tenant
     */
    public function countAllResults(bool $reset = true, bool $test = false): int
    {
        $this->addTenantScope();
        return parent::countAllResults($reset, $test);
    }
    
    /**
     * Método para paginação com scope de tenant
     */
    public function paginate(int $perPage = null, string $group = 'default', int $page = null, int $segment = 0)
    {
        $this->addTenantScope();
        return parent::paginate($perPage, $group, $page, $segment);
    }
    
    /**
     * Método para obter estatísticas do tenant atual
     */
    public function getTenantStats(): array
    {
        $this->addTenantScope();
        
        return [
            'total' => $this->countAllResults(false),
            'active' => $this->where('is_active', 1)->countAllResults(false),
            'inactive' => $this->where('is_active', 0)->countAllResults(false),
        ];
    }
    
    /**
     * Método para buscar registros criados hoje
     */
    public function getCreatedToday()
    {
        $this->addTenantScope();
        return $this->where('DATE(created_at)', date('Y-m-d'))->findAll();
    }
    
    /**
     * Método para buscar registros criados esta semana
     */
    public function getCreatedThisWeek()
    {
        $this->addTenantScope();
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        return $this->where('DATE(created_at) >=', $startOfWeek)
                   ->where('DATE(created_at) <=', $endOfWeek)
                   ->findAll();
    }
    
    /**
     * Método para buscar registros criados este mês
     */
    public function getCreatedThisMonth()
    {
        $this->addTenantScope();
        return $this->where('YEAR(created_at)', date('Y'))
                   ->where('MONTH(created_at)', date('m'))
                   ->findAll();
    }
}