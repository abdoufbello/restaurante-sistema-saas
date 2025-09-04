<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

class SettingsController extends BaseApiController
{
    protected $requiredPermissions = [
        'index' => 'settings.read',
        'show' => 'settings.read',
        'update' => 'settings.update',
        'groups' => 'settings.read',
        'backup' => 'settings.backup',
        'restore' => 'settings.restore',
        'reset' => 'settings.update'
    ];

    /**
     * Lista todas as configurações
     */
    public function index()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('settings.read');
            
            $group = $this->request->getGet('group');
            $search = $this->request->getGet('search');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            // Verifica cache
            $cacheKey = $this->generateCacheKey('settings_list', [
                'restaurant_id' => $restaurantId,
                'group' => $group,
                'search' => $search
            ]);
            
            $cachedData = $this->getCache($cacheKey);
            if ($cachedData) {
                return $this->successResponse($cachedData, 'Configurações carregadas do cache');
            }
            
            $db = \Config\Database::connect();
            $builder = $db->table('settings');
            
            // Aplica filtro de multi-tenancy
            $builder->where('restaurant_id', $restaurantId);
            
            // Aplica filtros
            if ($group) {
                $builder->where('group_name', $group);
            }
            
            if ($search) {
                $builder->groupStart()
                    ->like('key', $search)
                    ->orLike('label', $search)
                    ->orLike('description', $search)
                    ->groupEnd();
            }
            
            $settings = $builder
                ->orderBy('group_name', 'ASC')
                ->orderBy('sort_order', 'ASC')
                ->orderBy('key', 'ASC')
                ->get()
                ->getResultArray();
            
            // Agrupa configurações por grupo
            $groupedSettings = [];
            foreach ($settings as $setting) {
                $group = $setting['group_name'];
                if (!isset($groupedSettings[$group])) {
                    $groupedSettings[$group] = [];
                }
                
                // Decodifica valor JSON se necessário
                if ($setting['type'] === 'json' || $setting['type'] === 'array') {
                    $setting['value'] = json_decode($setting['value'], true);
                } elseif ($setting['type'] === 'boolean') {
                    $setting['value'] = (bool) $setting['value'];
                } elseif ($setting['type'] === 'integer') {
                    $setting['value'] = (int) $setting['value'];
                } elseif ($setting['type'] === 'float') {
                    $setting['value'] = (float) $setting['value'];
                }
                
                // Decodifica opções se existirem
                if ($setting['options']) {
                    $setting['options'] = json_decode($setting['options'], true);
                }
                
                // Sanitiza dados
                $setting = $this->sanitizeOutputData($setting);
                
                $groupedSettings[$group][] = $setting;
            }
            
            $data = [
                'settings' => $groupedSettings,
                'total_groups' => count($groupedSettings),
                'total_settings' => count($settings)
            ];
            
            // Cache por 10 minutos
            $this->saveCache($cacheKey, $data, 600);
            
            $this->logActivity('settings_list', ['total' => count($settings)]);
            
            return $this->successResponse($data, 'Configurações listadas com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao listar configurações: ' . $e->getMessage());
        }
    }
    
    /**
     * Exibe uma configuração específica
     */
    public function show($key)
    {
        try {
            $this->validateJWT();
            $this->checkPermission('settings.read');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            $db = \Config\Database::connect();
            $setting = $db->table('settings')
                ->where('key', $key)
                ->where('restaurant_id', $restaurantId)
                ->get()
                ->getRowArray();
            
            if (!$setting) {
                return $this->notFoundResponse('Configuração não encontrada');
            }
            
            // Decodifica valor conforme o tipo
            if ($setting['type'] === 'json' || $setting['type'] === 'array') {
                $setting['value'] = json_decode($setting['value'], true);
            } elseif ($setting['type'] === 'boolean') {
                $setting['value'] = (bool) $setting['value'];
            } elseif ($setting['type'] === 'integer') {
                $setting['value'] = (int) $setting['value'];
            } elseif ($setting['type'] === 'float') {
                $setting['value'] = (float) $setting['value'];
            }
            
            // Decodifica opções
            if ($setting['options']) {
                $setting['options'] = json_decode($setting['options'], true);
            }
            
            // Sanitiza dados
            $setting = $this->sanitizeOutputData($setting);
            
            $this->logActivity('setting_view', ['key' => $key]);
            
            return $this->successResponse($setting, 'Configuração carregada com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao carregar configuração: ' . $e->getMessage());
        }
    }
    
    /**
     * Atualiza configurações
     */
    public function update()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('settings.update');
            
            $data = $this->getRequestData();
            
            if (empty($data['settings'])) {
                return $this->validationErrorResponse(['settings' => 'Configurações são obrigatórias']);
            }
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            $userId = $this->getCurrentUser()['id'];
            
            $db = \Config\Database::connect();
            $db->transStart();
            
            $updatedSettings = [];
            
            foreach ($data['settings'] as $key => $value) {
                // Busca a configuração existente
                $setting = $db->table('settings')
                    ->where('key', $key)
                    ->where('restaurant_id', $restaurantId)
                    ->get()
                    ->getRowArray();
                
                if (!$setting) {
                    continue; // Ignora configurações inexistentes
                }
                
                // Verifica se a configuração é editável
                if (!$setting['is_editable']) {
                    continue; // Ignora configurações não editáveis
                }
                
                // Valida o valor conforme o tipo
                $validatedValue = $this->validateSettingValue($value, $setting['type'], $setting['options']);
                
                if ($validatedValue === false) {
                    $db->transRollback();
                    return $this->validationErrorResponse([
                        $key => 'Valor inválido para a configuração ' . $setting['label']
                    ]);
                }
                
                // Codifica valor para armazenamento
                $storedValue = $this->encodeSettingValue($validatedValue, $setting['type']);
                
                // Atualiza a configuração
                $db->table('settings')
                    ->where('key', $key)
                    ->where('restaurant_id', $restaurantId)
                    ->update([
                        'value' => $storedValue,
                        'updated_by' => $userId,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                $updatedSettings[$key] = $validatedValue;
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                return $this->errorResponse('Erro ao atualizar configurações');
            }
            
            // Remove cache
            $this->removeCache("settings_*");
            
            $this->logActivity('settings_update', [
                'updated_count' => count($updatedSettings),
                'keys' => array_keys($updatedSettings)
            ]);
            
            return $this->successResponse([
                'updated_settings' => $updatedSettings,
                'updated_count' => count($updatedSettings)
            ], 'Configurações atualizadas com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }
    
    /**
     * Lista grupos de configurações
     */
    public function groups()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('settings.read');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            $db = \Config\Database::connect();
            $groups = $db->query("
                SELECT 
                    group_name,
                    COUNT(*) as setting_count,
                    MAX(updated_at) as last_updated
                FROM settings 
                WHERE restaurant_id = ?
                GROUP BY group_name
                ORDER BY group_name
            ", [$restaurantId])->getResultArray();
            
            // Adiciona metadados dos grupos
            $groupsWithMeta = array_map(function($group) {
                $meta = $this->getGroupMetadata($group['group_name']);
                return array_merge($group, $meta, [
                    'setting_count' => (int) $group['setting_count']
                ]);
            }, $groups);
            
            return $this->successResponse($groupsWithMeta, 'Grupos carregados com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao carregar grupos: ' . $e->getMessage());
        }
    }
    
    /**
     * Cria backup das configurações
     */
    public function backup()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('settings.backup');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            $userId = $this->getCurrentUser()['id'];
            
            $db = \Config\Database::connect();
            
            // Busca todas as configurações
            $settings = $db->table('settings')
                ->where('restaurant_id', $restaurantId)
                ->get()
                ->getResultArray();
            
            // Prepara dados do backup
            $backupData = [
                'restaurant_id' => $restaurantId,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'settings' => $settings
            ];
            
            // Salva backup
            $backupJson = json_encode($backupData, JSON_PRETTY_PRINT);
            $backupId = uniqid('backup_', true);
            
            $db->table('setting_backups')->insert([
                'id' => $backupId,
                'restaurant_id' => $restaurantId,
                'created_by' => $userId,
                'backup_data' => $backupJson,
                'settings_count' => count($settings),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->logActivity('settings_backup', [
                'backup_id' => $backupId,
                'settings_count' => count($settings)
            ]);
            
            return $this->successResponse([
                'backup_id' => $backupId,
                'settings_count' => count($settings),
                'created_at' => date('Y-m-d H:i:s')
            ], 'Backup criado com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao criar backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Restaura configurações de um backup
     */
    public function restore()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('settings.restore');
            
            $data = $this->getRequestData();
            
            if (empty($data['backup_id'])) {
                return $this->validationErrorResponse(['backup_id' => 'ID do backup é obrigatório']);
            }
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            $userId = $this->getCurrentUser()['id'];
            
            $db = \Config\Database::connect();
            
            // Busca o backup
            $backup = $db->table('setting_backups')
                ->where('id', $data['backup_id'])
                ->where('restaurant_id', $restaurantId)
                ->get()
                ->getRowArray();
            
            if (!$backup) {
                return $this->notFoundResponse('Backup não encontrado');
            }
            
            // Decodifica dados do backup
            $backupData = json_decode($backup['backup_data'], true);
            
            if (!$backupData || !isset($backupData['settings'])) {
                return $this->errorResponse('Dados do backup corrompidos');
            }
            
            $db->transStart();
            
            $restoredCount = 0;
            
            foreach ($backupData['settings'] as $setting) {
                // Verifica se a configuração ainda existe
                $existing = $db->table('settings')
                    ->where('key', $setting['key'])
                    ->where('restaurant_id', $restaurantId)
                    ->get()
                    ->getRowArray();
                
                if ($existing && $existing['is_editable']) {
                    // Atualiza configuração existente
                    $db->table('settings')
                        ->where('key', $setting['key'])
                        ->where('restaurant_id', $restaurantId)
                        ->update([
                            'value' => $setting['value'],
                            'updated_by' => $userId,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    $restoredCount++;
                }
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                return $this->errorResponse('Erro ao restaurar configurações');
            }
            
            // Remove cache
            $this->removeCache("settings_*");
            
            $this->logActivity('settings_restore', [
                'backup_id' => $data['backup_id'],
                'restored_count' => $restoredCount
            ]);
            
            return $this->successResponse([
                'restored_count' => $restoredCount,
                'total_in_backup' => count($backupData['settings'])
            ], 'Configurações restauradas com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao restaurar configurações: ' . $e->getMessage());
        }
    }
    
    /**
     * Reseta configurações para valores padrão
     */
    public function reset()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('settings.update');
            
            $data = $this->getRequestData();
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            $userId = $this->getCurrentUser()['id'];
            
            $db = \Config\Database::connect();
            $db->transStart();
            
            $resetCount = 0;
            
            if (isset($data['group'])) {
                // Reset por grupo
                $settings = $db->table('settings')
                    ->where('group_name', $data['group'])
                    ->where('restaurant_id', $restaurantId)
                    ->where('is_editable', 1)
                    ->get()
                    ->getResultArray();
            } elseif (isset($data['keys'])) {
                // Reset por chaves específicas
                $settings = $db->table('settings')
                    ->whereIn('key', $data['keys'])
                    ->where('restaurant_id', $restaurantId)
                    ->where('is_editable', 1)
                    ->get()
                    ->getResultArray();
            } else {
                // Reset todas as configurações editáveis
                $settings = $db->table('settings')
                    ->where('restaurant_id', $restaurantId)
                    ->where('is_editable', 1)
                    ->get()
                    ->getResultArray();
            }
            
            foreach ($settings as $setting) {
                $db->table('settings')
                    ->where('id', $setting['id'])
                    ->update([
                        'value' => $setting['default_value'],
                        'updated_by' => $userId,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                $resetCount++;
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                return $this->errorResponse('Erro ao resetar configurações');
            }
            
            // Remove cache
            $this->removeCache("settings_*");
            
            $this->logActivity('settings_reset', [
                'reset_count' => $resetCount,
                'scope' => $data['group'] ?? ($data['keys'] ?? 'all')
            ]);
            
            return $this->successResponse([
                'reset_count' => $resetCount
            ], 'Configurações resetadas com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao resetar configurações: ' . $e->getMessage());
        }
    }
    
    // Métodos auxiliares privados
    
    private function validateSettingValue($value, $type, $options = null)
    {
        switch ($type) {
            case 'string':
                return is_string($value) ? $value : false;
                
            case 'integer':
                return is_numeric($value) ? (int) $value : false;
                
            case 'float':
                return is_numeric($value) ? (float) $value : false;
                
            case 'boolean':
                return is_bool($value) ? $value : (in_array($value, [0, 1, '0', '1', 'true', 'false']) ? (bool) $value : false);
                
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : false;
                
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) ? $value : false;
                
            case 'select':
                if ($options) {
                    $validOptions = json_decode($options, true);
                    return in_array($value, array_keys($validOptions)) ? $value : false;
                }
                return $value;
                
            case 'json':
            case 'array':
                return is_array($value) ? $value : false;
                
            default:
                return $value;
        }
    }
    
    private function encodeSettingValue($value, $type)
    {
        switch ($type) {
            case 'json':
            case 'array':
                return json_encode($value);
                
            case 'boolean':
                return $value ? '1' : '0';
                
            default:
                return (string) $value;
        }
    }
    
    private function getGroupMetadata($groupName)
    {
        $metadata = [
            'general' => [
                'label' => 'Configurações Gerais',
                'description' => 'Configurações básicas do sistema',
                'icon' => 'settings'
            ],
            'restaurant' => [
                'label' => 'Restaurante',
                'description' => 'Informações e configurações do restaurante',
                'icon' => 'store'
            ],
            'orders' => [
                'label' => 'Pedidos',
                'description' => 'Configurações de pedidos e delivery',
                'icon' => 'shopping-cart'
            ],
            'payments' => [
                'label' => 'Pagamentos',
                'description' => 'Configurações de métodos de pagamento',
                'icon' => 'credit-card'
            ],
            'notifications' => [
                'label' => 'Notificações',
                'description' => 'Configurações de notificações e alertas',
                'icon' => 'bell'
            ],
            'integrations' => [
                'label' => 'Integrações',
                'description' => 'Configurações de APIs e integrações externas',
                'icon' => 'link'
            ],
            'security' => [
                'label' => 'Segurança',
                'description' => 'Configurações de segurança e privacidade',
                'icon' => 'shield'
            ],
            'appearance' => [
                'label' => 'Aparência',
                'description' => 'Configurações de tema e interface',
                'icon' => 'palette'
            ]
        ];
        
        return $metadata[$groupName] ?? [
            'label' => ucfirst($groupName),
            'description' => 'Configurações de ' . $groupName,
            'icon' => 'settings'
        ];
    }
}