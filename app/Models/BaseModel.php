<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Validation\ValidationInterface;

/**
 * Modelo base para multi-tenancy
 * Todos os modelos devem estender esta classe para garantir isolamento de dados por tenant
 */
class BaseModel extends Model
{
    protected $tenantId;
    protected $tenantField = 'restaurant_id';
    protected $allowedFields = [];
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $dateFormat = 'datetime';

    public function __construct(ConnectionInterface &$db = null, ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
        
        // Obter tenant_id da sessão ou contexto atual
        $this->setTenantId($this->getCurrentTenantId());
    }

    /**
     * Define o ID do tenant atual
     */
    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Obtém o ID do tenant atual
     */
    public function getTenantId()
    {
        return $this->tenantId;
    }

    /**
     * Obtém o tenant_id do contexto atual (sessão, JWT, etc.)
     */
    protected function getCurrentTenantId()
    {
        // Por enquanto, retorna 1 como padrão
        // TODO: Implementar lógica para obter tenant_id da sessão/JWT
        $session = session();
        return $session->get('restaurant_id') ?? 1;
    }

    /**
     * Aplica filtro de tenant automaticamente em todas as consultas
     */
    protected function applyTenantFilter()
    {
        if ($this->tenantId && $this->tenantField) {
            $this->where($this->tenantField, $this->tenantId);
        }
        return $this;
    }

    /**
     * Override do método find para aplicar filtro de tenant
     */
    public function find($id = null)
    {
        $this->applyTenantFilter();
        return parent::find($id);
    }

    /**
     * Override do método findAll para aplicar filtro de tenant
     */
    public function findAll(int $limit = 0, int $offset = 0)
    {
        $this->applyTenantFilter();
        return parent::findAll($limit, $offset);
    }

    /**
     * Override do método where para manter fluent interface
     */
    public function where($key, $value = null, bool $escape = null)
    {
        parent::where($key, $value, $escape);
        return $this;
    }

    /**
     * Override do método insert para adicionar tenant_id automaticamente
     */
    public function insert($data = null, bool $returnID = true)
    {
        if (is_array($data) && $this->tenantId && $this->tenantField) {
            $data[$this->tenantField] = $this->tenantId;
        }
        return parent::insert($data, $returnID);
    }

    /**
     * Override do método update para aplicar filtro de tenant
     */
    public function update($id = null, $data = null): bool
    {
        if ($this->tenantId && $this->tenantField) {
            $this->where($this->tenantField, $this->tenantId);
        }
        return parent::update($id, $data);
    }

    /**
     * Override do método delete para aplicar filtro de tenant
     */
    public function delete($id = null, bool $purge = false)
    {
        if ($this->tenantId && $this->tenantField) {
            $this->where($this->tenantField, $this->tenantId);
        }
        return parent::delete($id, $purge);
    }

    /**
     * Método para buscar dados sem filtro de tenant (uso administrativo)
     */
    public function withoutTenantFilter()
    {
        $clone = clone $this;
        $clone->tenantId = null;
        return $clone;
    }

    /**
     * Método para contar registros com filtro de tenant
     */
    public function countAllResults(bool $reset = true, bool $test = false)
    {
        $this->applyTenantFilter();
        return parent::countAllResults($reset, $test);
    }

    /**
     * Método para paginar com filtro de tenant
     */
    public function paginate(int $perPage = null, string $group = 'default', int $page = null, int $segment = 0)
    {
        $this->applyTenantFilter();
        return parent::paginate($perPage, $group, $page, $segment);
    }
}