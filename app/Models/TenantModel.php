<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo base para Multi-Tenancy
 * Automaticamente filtra dados por tenant (restaurante)
 */
abstract class TenantModel extends Model
{
    /**
     * Campo que identifica o tenant
     */
    protected $tenantField = 'restaurant_id';
    
    /**
     * Se deve aplicar filtro de tenant automaticamente
     */
    protected $applyTenantFilter = true;
    
    /**
     * ID do tenant atual
     */
    protected $currentTenantId;
    
    public function __construct()
    {
        parent::__construct();
        
        // Obter tenant atual da sessão ou constante
        $this->currentTenantId = $this->getCurrentTenantId();
        
        // Aplicar filtro de tenant se habilitado
        if ($this->applyTenantFilter && $this->currentTenantId) {
            $this->where($this->tenantField, $this->currentTenantId);
        }
    }
    
    /**
     * Obtém o ID do tenant atual
     */
    protected function getCurrentTenantId()
    {
        // Primeiro tenta da constante (definida no TenantFilter)
        if (defined('CURRENT_TENANT_ID')) {
            return CURRENT_TENANT_ID;
        }
        
        // Depois tenta da sessão
        $session = session();
        if ($session->has('tenant_id')) {
            return $session->get('tenant_id');
        }
        
        return null;
    }
    
    /**
     * Override do método insert para adicionar tenant_id automaticamente
     */
    public function insert($data = null, bool $returnID = true)
    {
        if ($this->applyTenantFilter && $this->currentTenantId) {
            if (is_array($data)) {
                $data[$this->tenantField] = $this->currentTenantId;
            } elseif (is_object($data)) {
                $data->{$this->tenantField} = $this->currentTenantId;
            }
        }
        
        return parent::insert($data, $returnID);
    }
    
    /**
     * Override do método update para garantir que só atualiza dados do tenant
     */
    public function update($id = null, $data = null): bool
    {
        if ($this->applyTenantFilter && $this->currentTenantId) {
            // Verificar se o registro pertence ao tenant atual
            $existing = $this->withoutTenantFilter()->find($id);
            if (!$existing || $existing[$this->tenantField] != $this->currentTenantId) {
                throw new \RuntimeException('Tentativa de atualizar registro de outro tenant');
            }
        }
        
        return parent::update($id, $data);
    }
    
    /**
     * Override do método delete para garantir que só deleta dados do tenant
     */
    public function delete($id = null, bool $purge = false)
    {
        if ($this->applyTenantFilter && $this->currentTenantId) {
            // Verificar se o registro pertence ao tenant atual
            $existing = $this->withoutTenantFilter()->find($id);
            if (!$existing || $existing[$this->tenantField] != $this->currentTenantId) {
                throw new \RuntimeException('Tentativa de deletar registro de outro tenant');
            }
        }
        
        return parent::delete($id, $purge);
    }
    
    /**
     * Executa query sem filtro de tenant (usar com cuidado)
     */
    public function withoutTenantFilter()
    {
        $clone = clone $this;
        $clone->applyTenantFilter = false;
        $clone->resetQuery();
        return $clone;
    }
    
    /**
     * Define um tenant específico para a query
     */
    public function forTenant($tenantId)
    {
        $clone = clone $this;
        $clone->currentTenantId = $tenantId;
        $clone->resetQuery();
        $clone->where($this->tenantField, $tenantId);
        return $clone;
    }
    
    /**
     * Obtém dados de todos os tenants (apenas para super admin)
     */
    public function allTenants()
    {
        // Verificar se é super admin
        $session = session();
        if ($session->get('user_type') !== 'super_admin') {
            throw new \RuntimeException('Acesso negado: apenas super admin pode acessar dados de todos os tenants');
        }
        
        return $this->withoutTenantFilter();
    }
    
    /**
     * Valida se um registro pertence ao tenant atual
     */
    public function belongsToCurrentTenant($id): bool
    {
        if (!$this->currentTenantId) {
            return false;
        }
        
        $record = $this->withoutTenantFilter()->find($id);
        return $record && $record[$this->tenantField] == $this->currentTenantId;
    }
    
    /**
     * Conta registros do tenant atual
     */
    public function countForCurrentTenant(): int
    {
        return $this->countAllResults();
    }
    
    /**
     * Reset da query para permitir reutilização do modelo
     */
    protected function resetQuery()
    {
        $this->builder = null;
        return $this;
    }
}