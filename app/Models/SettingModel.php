<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;

/**
 * Modelo para Configurações com Multi-Tenancy
 */
class SettingModel extends BaseMultiTenantModel
{
    protected $table = 'settings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'restaurant_id',
        'category',
        'group_name',
        'key',
        'value',
        'default_value',
        'type',
        'label',
        'description',
        'options',
        'validation_rules',
        'is_public',
        'is_encrypted',
        'is_system',
        'is_required',
        'is_editable',
        'sort_order',
        'depends_on',
        'conditions',
        'metadata',
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
        'category' => 'required|in_list[general,restaurant,payment,notification,security,integration,appearance,advanced,system]',
        'key' => 'required|min_length[3]|max_length[100]',
        'type' => 'required|in_list[string,integer,float,boolean,json,array,email,url,password,text,select,multiselect,file,image,color,date,datetime,time]',
        'label' => 'required|min_length[3]|max_length[200]',
        'description' => 'permit_empty|max_length[500]',
        'sort_order' => 'permit_empty|integer|greater_than_equal_to[0]'
    ];
    
    protected $validationMessages = [
        'category' => [
            'required' => 'Categoria é obrigatória',
            'in_list' => 'Categoria inválida'
        ],
        'key' => [
            'required' => 'Chave é obrigatória',
            'min_length' => 'Chave deve ter pelo menos 3 caracteres',
            'max_length' => 'Chave deve ter no máximo 100 caracteres'
        ],
        'type' => [
            'required' => 'Tipo é obrigatório',
            'in_list' => 'Tipo inválido'
        ],
        'label' => [
            'required' => 'Label é obrigatório',
            'min_length' => 'Label deve ter pelo menos 3 caracteres',
            'max_length' => 'Label deve ter no máximo 200 caracteres'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'validateUniqueKey', 'encryptValue'];
    protected $beforeUpdate = ['validateUniqueKey', 'encryptValue'];
    protected $afterFind = ['decryptValue'];
    
    // Cache de configurações
    private static $settingsCache = [];
    
    // Categorias de configuração
    protected $settingCategories = [
        'general' => [
            'name' => 'Geral',
            'description' => 'Configurações gerais do sistema',
            'icon' => 'settings'
        ],
        'restaurant' => [
            'name' => 'Restaurante',
            'description' => 'Informações e configurações do restaurante',
            'icon' => 'store'
        ],
        'payment' => [
            'name' => 'Pagamento',
            'description' => 'Configurações de pagamento e gateways',
            'icon' => 'credit-card'
        ],
        'notification' => [
            'name' => 'Notificações',
            'description' => 'Configurações de notificações e alertas',
            'icon' => 'bell'
        ],
        'security' => [
            'name' => 'Segurança',
            'description' => 'Configurações de segurança e autenticação',
            'icon' => 'shield'
        ],
        'integration' => [
            'name' => 'Integrações',
            'description' => 'Configurações de APIs e integrações externas',
            'icon' => 'link'
        ],
        'appearance' => [
            'name' => 'Aparência',
            'description' => 'Configurações de tema e interface',
            'icon' => 'palette'
        ],
        'advanced' => [
            'name' => 'Avançado',
            'description' => 'Configurações avançadas do sistema',
            'icon' => 'code'
        ],
        'system' => [
            'name' => 'Sistema',
            'description' => 'Configurações internas do sistema',
            'icon' => 'cpu'
        ]
    ];
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['is_public'])) {
            $data['data']['is_public'] = 0;
        }
        
        if (!isset($data['data']['is_encrypted'])) {
            $data['data']['is_encrypted'] = 0;
        }
        
        if (!isset($data['data']['is_system'])) {
            $data['data']['is_system'] = 0;
        }
        
        if (!isset($data['data']['is_required'])) {
            $data['data']['is_required'] = 0;
        }
        
        if (!isset($data['data']['is_editable'])) {
            $data['data']['is_editable'] = 1;
        }
        
        if (!isset($data['data']['sort_order'])) {
            $data['data']['sort_order'] = 0;
        }
        
        // Define valor padrão se não fornecido
        if (!isset($data['data']['default_value']) && isset($data['data']['value'])) {
            $data['data']['default_value'] = $data['data']['value'];
        }
        
        return $data;
    }
    
    /**
     * Valida chave única por tenant
     */
    protected function validateUniqueKey(array $data): array
    {
        if (isset($data['data']['key'])) {
            $restaurantId = $data['data']['restaurant_id'] ?? $this->getCurrentTenantId();
            $key = $data['data']['key'];
            
            $builder = $this->where('restaurant_id', $restaurantId)
                           ->where('key', $key);
            
            // Se for update, exclui o próprio registro
            if (isset($data['id'])) {
                $builder->where('id !=', $data['id']);
            }
            
            if ($builder->countAllResults() > 0) {
                throw new \InvalidArgumentException("Chave '{$key}' já existe para este restaurante");
            }
        }
        
        return $data;
    }
    
    /**
     * Criptografa valor se necessário
     */
    protected function encryptValue(array $data): array
    {
        if (isset($data['data']['is_encrypted']) && $data['data']['is_encrypted'] && isset($data['data']['value'])) {
            $data['data']['value'] = $this->encrypt($data['data']['value']);
        }
        
        return $data;
    }
    
    /**
     * Descriptografa valor se necessário
     */
    protected function decryptValue(array $data): array
    {
        if (isset($data['is_encrypted']) && $data['is_encrypted'] && !empty($data['value'])) {
            $data['value'] = $this->decrypt($data['value']);
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS SAAS MULTI-TENANT
    // ========================================
    
    /**
     * Obtém configuração por chave
     */
    public function getSetting(string $key, $default = null)
    {
        $restaurantId = $this->getCurrentTenantId();
        $cacheKey = "setting_{$restaurantId}_{$key}";
        
        // Verifica cache
        if (isset(self::$settingsCache[$cacheKey])) {
            return self::$settingsCache[$cacheKey];
        }
        
        $setting = $this->where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }
        
        $value = $this->castValue($setting['value'], $setting['type']);
        
        // Armazena no cache
        self::$settingsCache[$cacheKey] = $value;
        
        return $value;
    }
    
    /**
     * Define configuração
     */
    public function setSetting(string $key, $value, array $options = []): bool
    {
        $restaurantId = $this->getCurrentTenantId();
        
        $existing = $this->where('key', $key)->first();
        
        $data = array_merge([
            'key' => $key,
            'value' => is_array($value) || is_object($value) ? json_encode($value) : (string) $value
        ], $options);
        
        if ($existing) {
            $result = $this->update($existing['id'], $data);
        } else {
            // Define valores padrão para nova configuração
            $data = array_merge([
                'category' => 'general',
                'type' => $this->detectType($value),
                'label' => ucfirst(str_replace('_', ' ', $key)),
                'is_editable' => 1
            ], $data);
            
            $result = $this->insert($data) !== false;
        }
        
        // Limpa cache
        $cacheKey = "setting_{$restaurantId}_{$key}";
        unset(self::$settingsCache[$cacheKey]);
        
        return $result;
    }
    
    /**
     * Obtém múltiplas configurações
     */
    public function getSettings(array $keys = []): array
    {
        $builder = $this;
        
        if (!empty($keys)) {
            $builder = $builder->whereIn('key', $keys);
        }
        
        $settings = $builder->orderBy('category')
                           ->orderBy('sort_order')
                           ->orderBy('key')
                           ->findAll();
        
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['key']] = $this->castValue($setting['value'], $setting['type']);
        }
        
        return $result;
    }
    
    /**
     * Obtém configurações por categoria
     */
    public function getSettingsByCategory(string $category): array
    {
        $settings = $this->where('category', $category)
                        ->orderBy('sort_order')
                        ->orderBy('key')
                        ->findAll();
        
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['key']] = [
                'value' => $this->castValue($setting['value'], $setting['type']),
                'label' => $setting['label'],
                'description' => $setting['description'],
                'type' => $setting['type'],
                'options' => $setting['options'] ? json_decode($setting['options'], true) : null,
                'is_required' => $setting['is_required'],
                'is_editable' => $setting['is_editable']
            ];
        }
        
        return $result;
    }
    
    /**
     * Obtém configurações públicas
     */
    public function getPublicSettings(): array
    {
        $settings = $this->where('is_public', 1)
                        ->orderBy('category')
                        ->orderBy('sort_order')
                        ->findAll();
        
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['key']] = $this->castValue($setting['value'], $setting['type']);
        }
        
        return $result;
    }
    
    /**
     * Define múltiplas configurações
     */
    public function setSettings(array $settings): bool
    {
        $success = true;
        
        foreach ($settings as $key => $value) {
            if (!$this->setSetting($key, $value)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Remove configuração
     */
    public function removeSetting(string $key): bool
    {
        $setting = $this->where('key', $key)->first();
        
        if (!$setting) {
            return false;
        }
        
        // Não permite remover configurações do sistema
        if ($setting['is_system']) {
            return false;
        }
        
        $result = $this->delete($setting['id']);
        
        // Limpa cache
        $restaurantId = $this->getCurrentTenantId();
        $cacheKey = "setting_{$restaurantId}_{$key}";
        unset(self::$settingsCache[$cacheKey]);
        
        return $result;
    }
    
    /**
     * Restaura configuração para valor padrão
     */
    public function resetSetting(string $key): bool
    {
        $setting = $this->where('key', $key)->first();
        
        if (!$setting || empty($setting['default_value'])) {
            return false;
        }
        
        return $this->setSetting($key, $setting['default_value']);
    }
    
    /**
     * Restaura todas as configurações para valores padrão
     */
    public function resetAllSettings(): bool
    {
        $settings = $this->where('default_value IS NOT NULL')
                        ->where('is_system', 0)
                        ->findAll();
        
        $success = true;
        
        foreach ($settings as $setting) {
            if (!$this->setSetting($setting['key'], $setting['default_value'])) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Busca configurações
     */
    public function searchSettings(string $search): array
    {
        $settings = $this->groupStart()
                        ->like('key', $search)
                        ->orLike('label', $search)
                        ->orLike('description', $search)
                        ->groupEnd()
                        ->orderBy('category')
                        ->orderBy('sort_order')
                        ->findAll();
        
        $result = [];
        
        foreach ($settings as $setting) {
            $result[] = [
                'key' => $setting['key'],
                'value' => $this->castValue($setting['value'], $setting['type']),
                'label' => $setting['label'],
                'description' => $setting['description'],
                'category' => $setting['category'],
                'type' => $setting['type'],
                'is_editable' => $setting['is_editable']
            ];
        }
        
        return $result;
    }
    
    /**
     * Exporta configurações
     */
    public function exportSettings(array $categories = []): array
    {
        $builder = $this->where('is_system', 0);
        
        if (!empty($categories)) {
            $builder->whereIn('category', $categories);
        }
        
        $settings = $builder->orderBy('category')
                           ->orderBy('sort_order')
                           ->findAll();
        
        $export = [];
        
        foreach ($settings as $setting) {
            $export[$setting['key']] = [
                'value' => $setting['value'],
                'category' => $setting['category'],
                'type' => $setting['type'],
                'label' => $setting['label'],
                'description' => $setting['description'],
                'default_value' => $setting['default_value'],
                'options' => $setting['options'],
                'validation_rules' => $setting['validation_rules']
            ];
        }
        
        return $export;
    }
    
    /**
     * Importa configurações
     */
    public function importSettings(array $settings, bool $overwrite = false): array
    {
        $imported = [];
        $skipped = [];
        $errors = [];
        
        foreach ($settings as $key => $config) {
            try {
                $existing = $this->where('key', $key)->first();
                
                if ($existing && !$overwrite) {
                    $skipped[] = $key;
                    continue;
                }
                
                $data = [
                    'key' => $key,
                    'value' => $config['value'],
                    'category' => $config['category'] ?? 'general',
                    'type' => $config['type'] ?? 'string',
                    'label' => $config['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                    'description' => $config['description'] ?? '',
                    'default_value' => $config['default_value'] ?? $config['value'],
                    'options' => $config['options'] ?? null,
                    'validation_rules' => $config['validation_rules'] ?? null
                ];
                
                if ($existing) {
                    $this->update($existing['id'], $data);
                } else {
                    $this->insert($data);
                }
                
                $imported[] = $key;
                
            } catch (\Exception $e) {
                $errors[$key] = $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }
    
    /**
     * Cria configurações padrão do sistema
     */
    public function createDefaultSettings(): bool
    {
        $defaultSettings = [
            // Configurações Gerais
            'app_name' => [
                'category' => 'general',
                'type' => 'string',
                'label' => 'Nome da Aplicação',
                'description' => 'Nome da aplicação exibido no sistema',
                'value' => 'Restaurant SaaS',
                'is_public' => 1
            ],
            'app_version' => [
                'category' => 'general',
                'type' => 'string',
                'label' => 'Versão da Aplicação',
                'description' => 'Versão atual da aplicação',
                'value' => '1.0.0',
                'is_public' => 1,
                'is_system' => 1
            ],
            'timezone' => [
                'category' => 'general',
                'type' => 'select',
                'label' => 'Fuso Horário',
                'description' => 'Fuso horário padrão do sistema',
                'value' => 'America/Sao_Paulo',
                'options' => json_encode([
                    'America/Sao_Paulo' => 'São Paulo (UTC-3)',
                    'America/New_York' => 'New York (UTC-5)',
                    'Europe/London' => 'London (UTC+0)',
                    'Asia/Tokyo' => 'Tokyo (UTC+9)'
                ])
            ],
            'language' => [
                'category' => 'general',
                'type' => 'select',
                'label' => 'Idioma',
                'description' => 'Idioma padrão do sistema',
                'value' => 'pt-BR',
                'options' => json_encode([
                    'pt-BR' => 'Português (Brasil)',
                    'en-US' => 'English (US)',
                    'es-ES' => 'Español (España)'
                ])
            ],
            
            // Configurações do Restaurante
            'restaurant_name' => [
                'category' => 'restaurant',
                'type' => 'string',
                'label' => 'Nome do Restaurante',
                'description' => 'Nome oficial do restaurante',
                'value' => '',
                'is_required' => 1
            ],
            'restaurant_phone' => [
                'category' => 'restaurant',
                'type' => 'string',
                'label' => 'Telefone',
                'description' => 'Telefone de contato do restaurante',
                'value' => ''
            ],
            'restaurant_email' => [
                'category' => 'restaurant',
                'type' => 'email',
                'label' => 'E-mail',
                'description' => 'E-mail de contato do restaurante',
                'value' => ''
            ],
            'restaurant_address' => [
                'category' => 'restaurant',
                'type' => 'text',
                'label' => 'Endereço',
                'description' => 'Endereço completo do restaurante',
                'value' => ''
            ],
            
            // Configurações de Pagamento
            'payment_methods' => [
                'category' => 'payment',
                'type' => 'multiselect',
                'label' => 'Métodos de Pagamento',
                'description' => 'Métodos de pagamento aceitos',
                'value' => json_encode(['cash', 'card']),
                'options' => json_encode([
                    'cash' => 'Dinheiro',
                    'card' => 'Cartão',
                    'pix' => 'PIX',
                    'voucher' => 'Vale Refeição'
                ])
            ],
            'tax_rate' => [
                'category' => 'payment',
                'type' => 'float',
                'label' => 'Taxa de Serviço (%)',
                'description' => 'Taxa de serviço padrão em porcentagem',
                'value' => '10.0'
            ],
            
            // Configurações de Notificação
            'email_notifications' => [
                'category' => 'notification',
                'type' => 'boolean',
                'label' => 'Notificações por E-mail',
                'description' => 'Habilitar notificações por e-mail',
                'value' => '1'
            ],
            'sms_notifications' => [
                'category' => 'notification',
                'type' => 'boolean',
                'label' => 'Notificações por SMS',
                'description' => 'Habilitar notificações por SMS',
                'value' => '0'
            ],
            
            // Configurações de Segurança
            'session_timeout' => [
                'category' => 'security',
                'type' => 'integer',
                'label' => 'Timeout de Sessão (minutos)',
                'description' => 'Tempo limite para sessões inativas',
                'value' => '120'
            ],
            'password_min_length' => [
                'category' => 'security',
                'type' => 'integer',
                'label' => 'Tamanho Mínimo da Senha',
                'description' => 'Número mínimo de caracteres para senhas',
                'value' => '8'
            ],
            
            // Configurações de Aparência
            'theme' => [
                'category' => 'appearance',
                'type' => 'select',
                'label' => 'Tema',
                'description' => 'Tema visual do sistema',
                'value' => 'light',
                'options' => json_encode([
                    'light' => 'Claro',
                    'dark' => 'Escuro',
                    'auto' => 'Automático'
                ])
            ],
            'primary_color' => [
                'category' => 'appearance',
                'type' => 'color',
                'label' => 'Cor Primária',
                'description' => 'Cor primária do tema',
                'value' => '#007bff'
            ]
        ];
        
        $success = true;
        
        foreach ($defaultSettings as $key => $config) {
            // Verifica se já existe
            if ($this->where('key', $key)->first()) {
                continue;
            }
            
            $data = array_merge($config, [
                'key' => $key,
                'default_value' => $config['value'],
                'sort_order' => 0
            ]);
            
            if (!$this->insert($data)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Obtém categorias disponíveis
     */
    public function getAvailableCategories(): array
    {
        return $this->settingCategories;
    }
    
    /**
     * Converte valor para o tipo correto
     */
    private function castValue($value, string $type)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        return match($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array', 'multiselect' => json_decode($value, true),
            default => $value
        };
    }
    
    /**
     * Detecta tipo do valor
     */
    private function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_int($value)) {
            return 'integer';
        }
        
        if (is_float($value)) {
            return 'float';
        }
        
        if (is_array($value) || is_object($value)) {
            return 'json';
        }
        
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return 'url';
        }
        
        return 'string';
    }
    
    /**
     * Criptografa valor
     */
    private function encrypt(string $value): string
    {
        // Implementar criptografia segura
        // Por exemplo, usando sodium_crypto_secretbox
        return base64_encode($value); // Placeholder - implementar criptografia real
    }
    
    /**
     * Descriptografa valor
     */
    private function decrypt(string $value): string
    {
        // Implementar descriptografia
        return base64_decode($value); // Placeholder - implementar descriptografia real
    }
    
    /**
     * Limpa cache de configurações
     */
    public function clearCache(): void
    {
        self::$settingsCache = [];
    }
}