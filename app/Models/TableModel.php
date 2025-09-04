<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Mesas com Multi-Tenancy
 */
class TableModel extends BaseMultiTenantModel
{
    protected $table = 'tables';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'number',
        'name',
        'description',
        'capacity',
        'location',
        'floor',
        'section',
        'status',
        'qr_code',
        'qr_code_url',
        'is_active',
        'is_available',
        'is_reserved',
        'reserved_until',
        'current_order_id',
        'last_cleaned_at',
        'cleaning_required',
        'position_x',
        'position_y',
        'shape',
        'color',
        'notes',
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
        'number' => 'required|max_length[10]',
        'name' => 'permit_empty|max_length[255]',
        'description' => 'permit_empty|max_length[500]',
        'capacity' => 'required|integer|greater_than[0]|less_than_equal_to[50]',
        'location' => 'permit_empty|max_length[255]',
        'floor' => 'permit_empty|max_length[50]',
        'section' => 'permit_empty|max_length[100]',
        'status' => 'required|in_list[available,occupied,reserved,cleaning,maintenance,inactive]',
        'qr_code' => 'permit_empty|max_length[255]',
        'qr_code_url' => 'permit_empty|max_length[500]',
        'is_active' => 'permit_empty|in_list[0,1]',
        'is_available' => 'permit_empty|in_list[0,1]',
        'is_reserved' => 'permit_empty|in_list[0,1]',
        'reserved_until' => 'permit_empty|valid_date',
        'current_order_id' => 'permit_empty|integer',
        'last_cleaned_at' => 'permit_empty|valid_date',
        'cleaning_required' => 'permit_empty|in_list[0,1]',
        'position_x' => 'permit_empty|decimal',
        'position_y' => 'permit_empty|decimal',
        'shape' => 'permit_empty|in_list[square,rectangle,circle,oval]',
        'color' => 'permit_empty|max_length[7]',
        'notes' => 'permit_empty|max_length[1000]'
    ];
    
    protected $validationMessages = [
        'restaurant_id' => [
            'required' => 'ID do restaurante é obrigatório',
            'integer' => 'ID do restaurante deve ser um número inteiro'
        ],
        'number' => [
            'required' => 'Número da mesa é obrigatório',
            'max_length' => 'Número da mesa deve ter no máximo 10 caracteres'
        ],
        'name' => [
            'max_length' => 'Nome da mesa deve ter no máximo 255 caracteres'
        ],
        'description' => [
            'max_length' => 'Descrição deve ter no máximo 500 caracteres'
        ],
        'capacity' => [
            'required' => 'Capacidade é obrigatória',
            'integer' => 'Capacidade deve ser um número inteiro',
            'greater_than' => 'Capacidade deve ser maior que zero',
            'less_than_equal_to' => 'Capacidade deve ser no máximo 50 pessoas'
        ],
        'location' => [
            'max_length' => 'Localização deve ter no máximo 255 caracteres'
        ],
        'floor' => [
            'max_length' => 'Andar deve ter no máximo 50 caracteres'
        ],
        'section' => [
            'max_length' => 'Seção deve ter no máximo 100 caracteres'
        ],
        'status' => [
            'required' => 'Status é obrigatório',
            'in_list' => 'Status deve ser: available, occupied, reserved, cleaning, maintenance ou inactive'
        ],
        'qr_code' => [
            'max_length' => 'Código QR deve ter no máximo 255 caracteres'
        ],
        'qr_code_url' => [
            'max_length' => 'URL do QR Code deve ter no máximo 500 caracteres'
        ],
        'is_active' => [
            'in_list' => 'Is active deve ser 0 ou 1'
        ],
        'is_available' => [
            'in_list' => 'Is available deve ser 0 ou 1'
        ],
        'is_reserved' => [
            'in_list' => 'Is reserved deve ser 0 ou 1'
        ],
        'reserved_until' => [
            'valid_date' => 'Data de reserva deve ser uma data válida'
        ],
        'current_order_id' => [
            'integer' => 'ID do pedido atual deve ser um número inteiro'
        ],
        'last_cleaned_at' => [
            'valid_date' => 'Data da última limpeza deve ser uma data válida'
        ],
        'cleaning_required' => [
            'in_list' => 'Cleaning required deve ser 0 ou 1'
        ],
        'position_x' => [
            'decimal' => 'Posição X deve ser um valor decimal válido'
        ],
        'position_y' => [
            'decimal' => 'Posição Y deve ser um valor decimal válido'
        ],
        'shape' => [
            'in_list' => 'Formato deve ser: square, rectangle, circle ou oval'
        ],
        'color' => [
            'max_length' => 'Cor deve ter no máximo 7 caracteres'
        ],
        'notes' => [
            'max_length' => 'Notas devem ter no máximo 1000 caracteres'
        ]
    ];
    
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    
    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['generateQRCode', 'setDefaults'];
    protected $beforeUpdate = ['updateQRCode'];
    
    /**
     * Gera código QR para a mesa
     */
    protected function generateQRCode(array $data)
    {
        if (empty($data['data']['qr_code'])) {
            $data['data']['qr_code'] = $this->createQRCode($data['data']['number']);
        }
        return $data;
    }
    
    /**
     * Atualiza código QR se necessário
     */
    protected function updateQRCode(array $data)
    {
        if (isset($data['data']['number']) && empty($data['data']['qr_code'])) {
            $data['data']['qr_code'] = $this->createQRCode($data['data']['number']);
        }
        return $data;
    }
    
    /**
     * Define valores padrão
     */
    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'available';
        }
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = 1;
        }
        if (!isset($data['data']['is_available'])) {
            $data['data']['is_available'] = 1;
        }
        if (!isset($data['data']['is_reserved'])) {
            $data['data']['is_reserved'] = 0;
        }
        if (!isset($data['data']['cleaning_required'])) {
            $data['data']['cleaning_required'] = 0;
        }
        if (!isset($data['data']['shape'])) {
            $data['data']['shape'] = 'square';
        }
        if (!isset($data['data']['color'])) {
            $data['data']['color'] = '#3498db';
        }
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = json_encode([]);
        }
        return $data;
    }
    
    /**
     * Cria código QR único para a mesa
     */
    private function createQRCode(string $tableNumber): string
    {
        $tenantId = $this->getTenantId();
        return 'TBL_' . $tenantId . '_' . strtoupper($tableNumber) . '_' . uniqid();
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Verifica se número da mesa já existe
     */
    public function tableNumberExists(string $number, int $excludeId = null): bool
    {
        $builder = $this->where('number', $number);
        
        if ($excludeId) {
            $builder = $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
    
    /**
     * Busca mesa por número
     */
    public function findByNumber(string $number)
    {
        return $this->where('number', $number)->first();
    }
    
    /**
     * Busca mesa por código QR
     */
    public function findByQRCode(string $qrCode)
    {
        return $this->where('qr_code', $qrCode)->first();
    }
    
    /**
     * Obtém mesas disponíveis
     */
    public function getAvailableTables(): array
    {
        return $this->where('status', 'available')
                    ->where('is_active', 1)
                    ->where('is_available', 1)
                    ->orderBy('number', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém mesas ocupadas
     */
    public function getOccupiedTables(): array
    {
        return $this->where('status', 'occupied')
                    ->orderBy('number', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém mesas reservadas
     */
    public function getReservedTables(): array
    {
        return $this->where('status', 'reserved')
                    ->where('is_reserved', 1)
                    ->orderBy('reserved_until', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém mesas por status
     */
    public function getTablesByStatus(string $status): array
    {
        return $this->where('status', $status)
                    ->orderBy('number', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém mesas por andar
     */
    public function getTablesByFloor(string $floor): array
    {
        return $this->where('floor', $floor)
                    ->where('is_active', 1)
                    ->orderBy('number', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém mesas por seção
     */
    public function getTablesBySection(string $section): array
    {
        return $this->where('section', $section)
                    ->where('is_active', 1)
                    ->orderBy('number', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém mesas por capacidade
     */
    public function getTablesByCapacity(int $minCapacity, int $maxCapacity = null): array
    {
        $builder = $this->where('capacity >=', $minCapacity)
                        ->where('is_active', 1);
        
        if ($maxCapacity) {
            $builder = $builder->where('capacity <=', $maxCapacity);
        }
        
        return $builder->orderBy('capacity', 'ASC')
                      ->orderBy('number', 'ASC')
                      ->findAll();
    }
    
    /**
     * Obtém mesas que precisam de limpeza
     */
    public function getTablesNeedingCleaning(): array
    {
        return $this->where('cleaning_required', 1)
                    ->orderBy('last_cleaned_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Obtém mesas em manutenção
     */
    public function getTablesInMaintenance(): array
    {
        return $this->where('status', 'maintenance')
                    ->orderBy('number', 'ASC')
                    ->findAll();
    }
    
    /**
     * Ocupa mesa
     */
    public function occupyTable(int $tableId, int $orderId = null): bool
    {
        $updateData = [
            'status' => 'occupied',
            'is_available' => 0
        ];
        
        if ($orderId) {
            $updateData['current_order_id'] = $orderId;
        }
        
        return $this->update($tableId, $updateData);
    }
    
    /**
     * Libera mesa
     */
    public function releaseTable(int $tableId): bool
    {
        return $this->update($tableId, [
            'status' => 'cleaning',
            'is_available' => 0,
            'is_reserved' => 0,
            'reserved_until' => null,
            'current_order_id' => null,
            'cleaning_required' => 1
        ]);
    }
    
    /**
     * Reserva mesa
     */
    public function reserveTable(int $tableId, string $reservedUntil): bool
    {
        return $this->update($tableId, [
            'status' => 'reserved',
            'is_reserved' => 1,
            'is_available' => 0,
            'reserved_until' => $reservedUntil
        ]);
    }
    
    /**
     * Cancela reserva da mesa
     */
    public function cancelReservation(int $tableId): bool
    {
        return $this->update($tableId, [
            'status' => 'available',
            'is_reserved' => 0,
            'is_available' => 1,
            'reserved_until' => null
        ]);
    }
    
    /**
     * Marca mesa como limpa
     */
    public function markTableCleaned(int $tableId): bool
    {
        return $this->update($tableId, [
            'status' => 'available',
            'is_available' => 1,
            'cleaning_required' => 0,
            'last_cleaned_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Coloca mesa em manutenção
     */
    public function setTableMaintenance(int $tableId, string $notes = null): bool
    {
        $updateData = [
            'status' => 'maintenance',
            'is_available' => 0
        ];
        
        if ($notes) {
            $updateData['notes'] = $notes;
        }
        
        return $this->update($tableId, $updateData);
    }
    
    /**
     * Remove mesa da manutenção
     */
    public function removeFromMaintenance(int $tableId): bool
    {
        return $this->update($tableId, [
            'status' => 'available',
            'is_available' => 1,
            'notes' => null
        ]);
    }
    
    /**
     * Ativa/desativa mesa
     */
    public function toggleTableStatus(int $tableId): bool
    {
        $table = $this->find($tableId);
        
        if (!$table) {
            return false;
        }
        
        $newStatus = $table['is_active'] ? 0 : 1;
        $newAvailability = $newStatus ? 1 : 0;
        $status = $newStatus ? 'available' : 'inactive';
        
        return $this->update($tableId, [
            'is_active' => $newStatus,
            'is_available' => $newAvailability,
            'status' => $status
        ]);
    }
    
    /**
     * Atualiza posição da mesa (para layout visual)
     */
    public function updateTablePosition(int $tableId, float $x, float $y): bool
    {
        return $this->update($tableId, [
            'position_x' => $x,
            'position_y' => $y
        ]);
    }
    
    /**
     * Obtém layout das mesas
     */
    public function getTableLayout(): array
    {
        return $this->select('id, number, name, capacity, status, position_x, position_y, shape, color, floor, section')
                    ->where('is_active', 1)
                    ->orderBy('floor', 'ASC')
                    ->orderBy('section', 'ASC')
                    ->orderBy('number', 'ASC')
                    ->findAll();
    }
    
    /**
     * Verifica reservas expiradas
     */
    public function checkExpiredReservations(): array
    {
        $expiredTables = $this->where('status', 'reserved')
                             ->where('reserved_until <', date('Y-m-d H:i:s'))
                             ->findAll();
        
        // Libera mesas com reservas expiradas
        foreach ($expiredTables as $table) {
            $this->cancelReservation($table['id']);
        }
        
        return $expiredTables;
    }
    
    /**
     * Obtém estatísticas das mesas
     */
    public function getTableStats(): array
    {
        return [
            'total_tables' => $this->countAllResults(),
            'active_tables' => $this->where('is_active', 1)->countAllResults(),
            'available_tables' => $this->where('status', 'available')->where('is_active', 1)->countAllResults(),
            'occupied_tables' => $this->where('status', 'occupied')->countAllResults(),
            'reserved_tables' => $this->where('status', 'reserved')->countAllResults(),
            'cleaning_tables' => $this->where('status', 'cleaning')->countAllResults(),
            'maintenance_tables' => $this->where('status', 'maintenance')->countAllResults(),
            'inactive_tables' => $this->where('is_active', 0)->countAllResults(),
            'tables_needing_cleaning' => $this->where('cleaning_required', 1)->countAllResults(),
            'total_capacity' => $this->selectSum('capacity', 'total_capacity')->where('is_active', 1)->first()['total_capacity'] ?? 0,
            'available_capacity' => $this->selectSum('capacity', 'available_capacity')->where('status', 'available')->where('is_active', 1)->first()['available_capacity'] ?? 0,
            'occupancy_rate' => $this->calculateOccupancyRate(),
            'avg_table_capacity' => $this->getAverageTableCapacity(),
            'created_today' => $this->getCreatedToday(),
            'created_this_week' => $this->getCreatedThisWeek(),
            'created_this_month' => $this->getCreatedThisMonth()
        ];
    }
    
    /**
     * Calcula taxa de ocupação
     */
    public function calculateOccupancyRate(): float
    {
        $totalActive = $this->where('is_active', 1)->countAllResults();
        $occupied = $this->where('status', 'occupied')->countAllResults();
        
        return $totalActive > 0 ? ($occupied / $totalActive) * 100 : 0;
    }
    
    /**
     * Obtém capacidade média das mesas
     */
    public function getAverageTableCapacity(): float
    {
        $result = $this->selectAvg('capacity', 'avg_capacity')
                      ->where('is_active', 1)
                      ->first();
        
        return (float) ($result['avg_capacity'] ?? 0);
    }
    
    /**
     * Busca avançada de mesas
     */
    public function advancedSearch(array $filters = []): array
    {
        $builder = $this;
        
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                              ->like('number', $filters['search'])
                              ->orLike('name', $filters['search'])
                              ->orLike('location', $filters['search'])
                              ->orLike('section', $filters['search'])
                              ->groupEnd();
        }
        
        if (!empty($filters['status'])) {
            $builder = $builder->where('status', $filters['status']);
        }
        
        if (!empty($filters['floor'])) {
            $builder = $builder->where('floor', $filters['floor']);
        }
        
        if (!empty($filters['section'])) {
            $builder = $builder->where('section', $filters['section']);
        }
        
        if (!empty($filters['min_capacity'])) {
            $builder = $builder->where('capacity >=', $filters['min_capacity']);
        }
        
        if (!empty($filters['max_capacity'])) {
            $builder = $builder->where('capacity <=', $filters['max_capacity']);
        }
        
        if (isset($filters['is_active'])) {
            $builder = $builder->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['cleaning_required'])) {
            $builder = $builder->where('cleaning_required', $filters['cleaning_required']);
        }
        
        $orderBy = $filters['order_by'] ?? 'number';
        $orderDir = $filters['order_dir'] ?? 'ASC';
        $builder = $builder->orderBy($orderBy, $orderDir);
        
        return $builder->findAll();
    }
    
    /**
     * Exporta mesas para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $tables = $this->advancedSearch($filters);
        
        $csv = "Número,Nome,Descrição,Capacidade,Localização,Andar,Seção,Status,Ativo,Disponível,Reservado,Limpeza Necessária,Última Limpeza,Formato,Cor,Criado em\n";
        
        foreach ($tables as $table) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $table['number'],
                $table['name'] ?? '',
                $table['description'] ?? '',
                $table['capacity'],
                $table['location'] ?? '',
                $table['floor'] ?? '',
                $table['section'] ?? '',
                $table['status'],
                $table['is_active'] ? 'Sim' : 'Não',
                $table['is_available'] ? 'Sim' : 'Não',
                $table['is_reserved'] ? 'Sim' : 'Não',
                $table['cleaning_required'] ? 'Sim' : 'Não',
                $table['last_cleaned_at'] ?? '',
                $table['shape'] ?? '',
                $table['color'] ?? '',
                $table['created_at']
            );
        }
        
        return $csv;
    }
    
    /**
     * Duplica mesa
     */
    public function duplicateTable(int $tableId): ?int
    {
        $table = $this->find($tableId);
        
        if (!$table) {
            return null;
        }
        
        // Remove campos únicos
        unset($table['id'], $table['created_at'], $table['updated_at'], $table['deleted_at']);
        
        // Gera novo número único
        $baseNumber = $table['number'];
        $counter = 1;
        
        do {
            $newNumber = $baseNumber . '_' . $counter;
            $counter++;
        } while ($this->tableNumberExists($newNumber));
        
        $table['number'] = $newNumber;
        $table['name'] = ($table['name'] ?? '') . ' (Cópia)';
        $table['qr_code'] = null; // Será gerado automaticamente
        $table['status'] = 'available';
        $table['is_available'] = 1;
        $table['is_reserved'] = 0;
        $table['current_order_id'] = null;
        
        return $this->insert($table);
    }
}