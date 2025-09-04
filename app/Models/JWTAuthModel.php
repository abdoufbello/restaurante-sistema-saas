<?php

namespace App\Models;

use App\Models\BaseMultiTenantModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Modelo para Autenticação JWT e Sistema de Roles com Multi-Tenancy
 */
class JWTAuthModel extends BaseMultiTenantModel
{
    protected $table = 'jwt_tokens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'restaurant_id',
        'user_id',
        'token_id',
        'token_type',
        'token_hash',
        'refresh_token_hash',
        'device_id',
        'device_name',
        'device_type',
        'ip_address',
        'user_agent',
        'location',
        'scopes',
        'permissions',
        'roles',
        'is_active',
        'is_revoked',
        'is_blacklisted',
        'last_used_at',
        'expires_at',
        'refresh_expires_at',
        'revoked_at',
        'revoked_by',
        'revoke_reason',
        'metadata',
        'created_by'
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
        'user_id' => 'required|integer',
        'token_id' => 'required|string|max_length[255]',
        'token_type' => 'required|in_list[access,refresh,api,mobile,web,admin]',
        'token_hash' => 'required|string',
        'device_type' => 'permit_empty|in_list[web,mobile,desktop,tablet,api,other]',
        'ip_address' => 'permit_empty|valid_ip',
        'expires_at' => 'required|valid_date',
        'refresh_expires_at' => 'permit_empty|valid_date'
    ];
    
    protected $validationMessages = [
        'token_id' => [
            'required' => 'ID do token é obrigatório',
            'max_length' => 'ID do token não pode exceder 255 caracteres'
        ],
        'token_type' => [
            'required' => 'Tipo do token é obrigatório',
            'in_list' => 'Tipo de token inválido'
        ],
        'expires_at' => [
            'required' => 'Data de expiração é obrigatória',
            'valid_date' => 'Data de expiração deve ser válida'
        ]
    ];
    
    // Callbacks
    protected $beforeInsert = ['setDefaults', 'prepareJsonFields'];
    protected $beforeUpdate = ['prepareJsonFields'];
    protected $afterFind = ['parseJsonFields'];
    
    // Constants
    const TOKEN_ACCESS = 'access';
    const TOKEN_REFRESH = 'refresh';
    const TOKEN_API = 'api';
    const TOKEN_MOBILE = 'mobile';
    const TOKEN_WEB = 'web';
    const TOKEN_ADMIN = 'admin';
    
    const DEVICE_WEB = 'web';
    const DEVICE_MOBILE = 'mobile';
    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_TABLET = 'tablet';
    const DEVICE_API = 'api';
    const DEVICE_OTHER = 'other';
    
    // JWT Configuration
    private $jwtSecret;
    private $jwtAlgorithm = 'HS256';
    private $accessTokenExpiry = 3600; // 1 hora
    private $refreshTokenExpiry = 2592000; // 30 dias
    
    public function __construct()
    {
        parent::__construct();
        $this->jwtSecret = env('JWT_SECRET', 'your-secret-key');
    }
    
    /**
     * Define valores padrão antes de inserir
     */
    protected function setDefaults(array $data): array
    {
        if (!isset($data['data']['token_type'])) {
            $data['data']['token_type'] = self::TOKEN_ACCESS;
        }
        
        if (!isset($data['data']['device_type'])) {
            $data['data']['device_type'] = self::DEVICE_WEB;
        }
        
        if (!isset($data['data']['is_active'])) {
            $data['data']['is_active'] = true;
        }
        
        if (!isset($data['data']['is_revoked'])) {
            $data['data']['is_revoked'] = false;
        }
        
        if (!isset($data['data']['is_blacklisted'])) {
            $data['data']['is_blacklisted'] = false;
        }
        
        if (!isset($data['data']['token_id'])) {
            $data['data']['token_id'] = uniqid('jwt_', true);
        }
        
        // Definir expiração padrão se não fornecida
        if (!isset($data['data']['expires_at'])) {
            $expiry = $data['data']['token_type'] === self::TOKEN_REFRESH 
                ? $this->refreshTokenExpiry 
                : $this->accessTokenExpiry;
            $data['data']['expires_at'] = date('Y-m-d H:i:s', time() + $expiry);
        }
        
        return $data;
    }
    
    /**
     * Prepara campos JSON antes de inserir/atualizar
     */
    protected function prepareJsonFields(array $data): array
    {
        $jsonFields = ['scopes', 'permissions', 'roles', 'metadata'];
        
        foreach ($jsonFields as $field) {
            if (isset($data['data'][$field]) && is_array($data['data'][$field])) {
                $data['data'][$field] = json_encode($data['data'][$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Analisa campos JSON após buscar
     */
    protected function parseJsonFields(array $data): array
    {
        $jsonFields = ['scopes', 'permissions', 'roles', 'metadata'];
        
        if (isset($data['data'])) {
            foreach ($jsonFields as $field) {
                if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                    $data['data'][$field] = json_decode($data['data'][$field], true);
                }
            }
        } elseif (is_array($data)) {
            foreach ($data as &$item) {
                if (is_array($item)) {
                    foreach ($jsonFields as $field) {
                        if (isset($item[$field]) && is_string($item[$field])) {
                            $item[$field] = json_decode($item[$field], true);
                        }
                    }
                }
            }
        }
        
        return $data;
    }
    
    // ========================================
    // MÉTODOS DE GERAÇÃO DE TOKEN
    // ========================================
    
    /**
     * Gera token JWT de acesso
     */
    public function generateAccessToken(array $userData, array $options = []): array
    {
        $tokenId = uniqid('access_', true);
        $issuedAt = time();
        $expiresAt = $issuedAt + ($options['expires_in'] ?? $this->accessTokenExpiry);
        
        $payload = [
            'iss' => env('APP_URL', 'localhost'), // Issuer
            'aud' => env('APP_URL', 'localhost'), // Audience
            'iat' => $issuedAt, // Issued at
            'exp' => $expiresAt, // Expires at
            'nbf' => $issuedAt, // Not before
            'jti' => $tokenId, // JWT ID
            'sub' => $userData['id'], // Subject (user ID)
            'restaurant_id' => $userData['restaurant_id'],
            'user_id' => $userData['id'],
            'username' => $userData['username'] ?? $userData['email'],
            'email' => $userData['email'],
            'role' => $userData['role'] ?? 'user',
            'roles' => $userData['roles'] ?? [$userData['role'] ?? 'user'],
            'permissions' => $userData['permissions'] ?? [],
            'scopes' => $options['scopes'] ?? ['read'],
            'device_id' => $options['device_id'] ?? null,
            'device_type' => $options['device_type'] ?? self::DEVICE_WEB,
            'ip_address' => $options['ip_address'] ?? null,
            'user_agent' => $options['user_agent'] ?? null
        ];
        
        $token = JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
        $tokenHash = hash('sha256', $token);
        
        // Salvar token no banco
        $tokenData = [
            'restaurant_id' => $userData['restaurant_id'],
            'user_id' => $userData['id'],
            'token_id' => $tokenId,
            'token_type' => self::TOKEN_ACCESS,
            'token_hash' => $tokenHash,
            'device_id' => $options['device_id'] ?? null,
            'device_name' => $options['device_name'] ?? null,
            'device_type' => $options['device_type'] ?? self::DEVICE_WEB,
            'ip_address' => $options['ip_address'] ?? null,
            'user_agent' => $options['user_agent'] ?? null,
            'location' => $options['location'] ?? null,
            'scopes' => $options['scopes'] ?? ['read'],
            'permissions' => $userData['permissions'] ?? [],
            'roles' => $userData['roles'] ?? [$userData['role'] ?? 'user'],
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'metadata' => $options['metadata'] ?? []
        ];
        
        $this->insert($tokenData);
        
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt - $issuedAt,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'token_id' => $tokenId
        ];
    }
    
    /**
     * Gera token de refresh
     */
    public function generateRefreshToken(array $userData, array $options = []): array
    {
        $tokenId = uniqid('refresh_', true);
        $issuedAt = time();
        $expiresAt = $issuedAt + ($options['expires_in'] ?? $this->refreshTokenExpiry);
        
        $payload = [
            'iss' => env('APP_URL', 'localhost'),
            'aud' => env('APP_URL', 'localhost'),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'nbf' => $issuedAt,
            'jti' => $tokenId,
            'sub' => $userData['id'],
            'restaurant_id' => $userData['restaurant_id'],
            'user_id' => $userData['id'],
            'type' => 'refresh'
        ];
        
        $token = JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
        $tokenHash = hash('sha256', $token);
        
        // Salvar token no banco
        $tokenData = [
            'restaurant_id' => $userData['restaurant_id'],
            'user_id' => $userData['id'],
            'token_id' => $tokenId,
            'token_type' => self::TOKEN_REFRESH,
            'refresh_token_hash' => $tokenHash,
            'device_id' => $options['device_id'] ?? null,
            'device_name' => $options['device_name'] ?? null,
            'device_type' => $options['device_type'] ?? self::DEVICE_WEB,
            'ip_address' => $options['ip_address'] ?? null,
            'user_agent' => $options['user_agent'] ?? null,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'refresh_expires_at' => date('Y-m-d H:i:s', $expiresAt)
        ];
        
        $this->insert($tokenData);
        
        return [
            'refresh_token' => $token,
            'expires_in' => $expiresAt - $issuedAt,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'token_id' => $tokenId
        ];
    }
    
    /**
     * Gera par de tokens (access + refresh)
     */
    public function generateTokenPair(array $userData, array $options = []): array
    {
        $accessToken = $this->generateAccessToken($userData, $options);
        $refreshToken = $this->generateRefreshToken($userData, $options);
        
        return [
            'access_token' => $accessToken['access_token'],
            'refresh_token' => $refreshToken['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $accessToken['expires_in'],
            'refresh_expires_in' => $refreshToken['expires_in'],
            'access_token_id' => $accessToken['token_id'],
            'refresh_token_id' => $refreshToken['token_id']
        ];
    }
    
    // ========================================
    // MÉTODOS DE VALIDAÇÃO DE TOKEN
    // ========================================
    
    /**
     * Valida e decodifica token JWT
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            $payload = (array) $decoded;
            
            // Verificar se token existe no banco e está ativo
            $tokenHash = hash('sha256', $token);
            $tokenRecord = $this->where('token_hash', $tokenHash)
                               ->orWhere('refresh_token_hash', $tokenHash)
                               ->where('is_active', true)
                               ->where('is_revoked', false)
                               ->where('is_blacklisted', false)
                               ->where('expires_at >', date('Y-m-d H:i:s'))
                               ->first();
            
            if (!$tokenRecord) {
                return null;
            }
            
            // Atualizar último uso
            $this->update($tokenRecord['id'], [
                'last_used_at' => date('Y-m-d H:i:s')
            ]);
            
            return array_merge($payload, [
                'token_record' => $tokenRecord
            ]);
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Verifica se token está na blacklist
     */
    public function isTokenBlacklisted(string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        
        return $this->where('token_hash', $tokenHash)
                   ->orWhere('refresh_token_hash', $tokenHash)
                   ->where('is_blacklisted', true)
                   ->countAllResults() > 0;
    }
    
    /**
     * Verifica se token foi revogado
     */
    public function isTokenRevoked(string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        
        return $this->where('token_hash', $tokenHash)
                   ->orWhere('refresh_token_hash', $tokenHash)
                   ->where('is_revoked', true)
                   ->countAllResults() > 0;
    }
    
    /**
     * Verifica se token expirou
     */
    public function isTokenExpired(string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        
        return $this->where('token_hash', $tokenHash)
                   ->orWhere('refresh_token_hash', $tokenHash)
                   ->where('expires_at <', date('Y-m-d H:i:s'))
                   ->countAllResults() > 0;
    }
    
    // ========================================
    // MÉTODOS DE GERENCIAMENTO DE TOKEN
    // ========================================
    
    /**
     * Revoga token específico
     */
    public function revokeToken(string $token, int $revokedBy = null, string $reason = null): bool
    {
        $tokenHash = hash('sha256', $token);
        
        return $this->where('token_hash', $tokenHash)
                   ->orWhere('refresh_token_hash', $tokenHash)
                   ->set([
                       'is_revoked' => true,
                       'is_active' => false,
                       'revoked_at' => date('Y-m-d H:i:s'),
                       'revoked_by' => $revokedBy,
                       'revoke_reason' => $reason
                   ])->update();
    }
    
    /**
     * Revoga todos os tokens de um usuário
     */
    public function revokeUserTokens(int $userId, int $revokedBy = null, string $reason = null): bool
    {
        return $this->where('user_id', $userId)
                   ->where('is_revoked', false)
                   ->set([
                       'is_revoked' => true,
                       'is_active' => false,
                       'revoked_at' => date('Y-m-d H:i:s'),
                       'revoked_by' => $revokedBy,
                       'revoke_reason' => $reason ?? 'User logout'
                   ])->update();
    }
    
    /**
     * Revoga tokens de um dispositivo específico
     */
    public function revokeDeviceTokens(string $deviceId, int $revokedBy = null, string $reason = null): bool
    {
        return $this->where('device_id', $deviceId)
                   ->where('is_revoked', false)
                   ->set([
                       'is_revoked' => true,
                       'is_active' => false,
                       'revoked_at' => date('Y-m-d H:i:s'),
                       'revoked_by' => $revokedBy,
                       'revoke_reason' => $reason ?? 'Device logout'
                   ])->update();
    }
    
    /**
     * Adiciona token à blacklist
     */
    public function blacklistToken(string $token, string $reason = null): bool
    {
        $tokenHash = hash('sha256', $token);
        
        return $this->where('token_hash', $tokenHash)
                   ->orWhere('refresh_token_hash', $tokenHash)
                   ->set([
                       'is_blacklisted' => true,
                       'is_active' => false,
                       'revoke_reason' => $reason ?? 'Blacklisted'
                   ])->update();
    }
    
    /**
     * Renova token usando refresh token
     */
    public function refreshToken(string $refreshToken): ?array
    {
        $payload = $this->validateToken($refreshToken);
        
        if (!$payload || !isset($payload['type']) || $payload['type'] !== 'refresh') {
            return null;
        }
        
        // Buscar dados do usuário
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($payload['user_id']);
        
        if (!$user) {
            return null;
        }
        
        // Revogar refresh token usado
        $this->revokeToken($refreshToken, null, 'Used for refresh');
        
        // Gerar novo par de tokens
        return $this->generateTokenPair($user, [
            'device_id' => $payload['token_record']['device_id'] ?? null,
            'device_type' => $payload['token_record']['device_type'] ?? self::DEVICE_WEB,
            'ip_address' => $payload['token_record']['ip_address'] ?? null,
            'user_agent' => $payload['token_record']['user_agent'] ?? null
        ]);
    }
    
    // ========================================
    // MÉTODOS DE BUSCA
    // ========================================
    
    /**
     * Obtém tokens ativos de um usuário
     */
    public function getUserActiveTokens(int $userId): array
    {
        return $this->where('user_id', $userId)
                   ->where('is_active', true)
                   ->where('is_revoked', false)
                   ->where('is_blacklisted', false)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém tokens de um dispositivo
     */
    public function getDeviceTokens(string $deviceId): array
    {
        return $this->where('device_id', $deviceId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Obtém tokens expirados
     */
    public function getExpiredTokens(): array
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
                   ->where('is_active', true)
                   ->findAll();
    }
    
    /**
     * Obtém tokens por tipo
     */
    public function getTokensByType(string $type): array
    {
        return $this->where('token_type', $type)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    // ========================================
    // MÉTODOS DE PERMISSÕES E ROLES
    // ========================================
    
    /**
     * Verifica se usuário tem permissão específica
     */
    public function hasPermission(array $tokenPayload, string $permission): bool
    {
        $permissions = $tokenPayload['permissions'] ?? [];
        
        // Verificar permissão direta
        if (in_array($permission, $permissions)) {
            return true;
        }
        
        // Verificar permissão por role
        $roles = $tokenPayload['roles'] ?? [];
        return $this->roleHasPermission($roles, $permission);
    }
    
    /**
     * Verifica se usuário tem role específica
     */
    public function hasRole(array $tokenPayload, string $role): bool
    {
        $roles = $tokenPayload['roles'] ?? [];
        return in_array($role, $roles);
    }
    
    /**
     * Verifica se usuário tem qualquer uma das roles
     */
    public function hasAnyRole(array $tokenPayload, array $roles): bool
    {
        $userRoles = $tokenPayload['roles'] ?? [];
        return !empty(array_intersect($userRoles, $roles));
    }
    
    /**
     * Verifica se usuário tem todas as roles
     */
    public function hasAllRoles(array $tokenPayload, array $roles): bool
    {
        $userRoles = $tokenPayload['roles'] ?? [];
        return empty(array_diff($roles, $userRoles));
    }
    
    /**
     * Verifica se role tem permissão específica
     */
    private function roleHasPermission(array $roles, string $permission): bool
    {
        // Definir permissões por role (em produção, isso viria do banco)
        $rolePermissions = [
            'super_admin' => ['*'], // Todas as permissões
            'admin' => [
                'users.create', 'users.read', 'users.update', 'users.delete',
                'orders.create', 'orders.read', 'orders.update', 'orders.delete',
                'products.create', 'products.read', 'products.update', 'products.delete',
                'reports.create', 'reports.read', 'reports.update', 'reports.delete',
                'settings.read', 'settings.update'
            ],
            'manager' => [
                'orders.create', 'orders.read', 'orders.update',
                'products.create', 'products.read', 'products.update',
                'reports.read', 'customers.read', 'customers.update'
            ],
            'employee' => [
                'orders.read', 'orders.update',
                'products.read', 'customers.read'
            ],
            'customer' => [
                'orders.create', 'orders.read',
                'profile.read', 'profile.update'
            ],
            'user' => [
                'profile.read', 'profile.update'
            ]
        ];
        
        foreach ($roles as $role) {
            if (isset($rolePermissions[$role])) {
                $permissions = $rolePermissions[$role];
                
                // Super admin tem todas as permissões
                if (in_array('*', $permissions)) {
                    return true;
                }
                
                // Verificar permissão específica
                if (in_array($permission, $permissions)) {
                    return true;
                }
                
                // Verificar permissão com wildcard
                foreach ($permissions as $rolePermission) {
                    if (str_ends_with($rolePermission, '*')) {
                        $prefix = rtrim($rolePermission, '*');
                        if (str_starts_with($permission, $prefix)) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    // ========================================
    // MÉTODOS DE ESTATÍSTICAS
    // ========================================
    
    /**
     * Obtém estatísticas de tokens
     */
    public function getTokenStats(): array
    {
        $stats = $this->select([
            'COUNT(*) as total_tokens',
            'COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_tokens',
            'COUNT(CASE WHEN is_revoked = 1 THEN 1 END) as revoked_tokens',
            'COUNT(CASE WHEN is_blacklisted = 1 THEN 1 END) as blacklisted_tokens',
            'COUNT(CASE WHEN expires_at < NOW() THEN 1 END) as expired_tokens',
            'COUNT(DISTINCT user_id) as unique_users',
            'COUNT(DISTINCT device_id) as unique_devices'
        ])->first();
        
        // Estatísticas por tipo
        $byType = $this->select([
            'token_type',
            'COUNT(*) as count'
        ])->groupBy('token_type')
          ->orderBy('count', 'DESC')
          ->findAll();
        
        // Estatísticas por dispositivo
        $byDevice = $this->select([
            'device_type',
            'COUNT(*) as count'
        ])->where('device_type IS NOT NULL')
          ->groupBy('device_type')
          ->orderBy('count', 'DESC')
          ->findAll();
        
        return [
            'general' => $stats,
            'by_type' => $byType,
            'by_device' => $byDevice
        ];
    }
    
    /**
     * Obtém tokens ativos por usuário
     */
    public function getActiveTokensByUser(): array
    {
        return $this->select([
            'user_id',
            'COUNT(*) as active_tokens'
        ])->where('is_active', true)
          ->where('is_revoked', false)
          ->where('expires_at >', date('Y-m-d H:i:s'))
          ->groupBy('user_id')
          ->orderBy('active_tokens', 'DESC')
          ->findAll();
    }
    
    // ========================================
    // MÉTODOS DE LIMPEZA
    // ========================================
    
    /**
     * Limpa tokens expirados
     */
    public function cleanExpiredTokens(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
                   ->delete();
    }
    
    /**
     * Limpa tokens revogados antigos
     */
    public function cleanRevokedTokens(int $daysOld = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        return $this->where('is_revoked', true)
                   ->where('revoked_at <', $cutoffDate)
                   ->delete();
    }
    
    /**
     * Busca avançada de tokens
     */
    public function advancedSearch(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $query = $this->select('*');
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['token_type'])) {
            if (is_array($filters['token_type'])) {
                $query->whereIn('token_type', $filters['token_type']);
            } else {
                $query->where('token_type', $filters['token_type']);
            }
        }
        
        if (!empty($filters['device_type'])) {
            $query->where('device_type', $filters['device_type']);
        }
        
        if (!empty($filters['device_id'])) {
            $query->where('device_id', $filters['device_id']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['is_revoked'])) {
            $query->where('is_revoked', $filters['is_revoked']);
        }
        
        if (isset($filters['is_blacklisted'])) {
            $query->where('is_blacklisted', $filters['is_blacklisted']);
        }
        
        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }
        
        if (!empty($filters['created_from'])) {
            $query->where('created_at >=', $filters['created_from']);
        }
        
        if (!empty($filters['created_to'])) {
            $query->where('created_at <=', $filters['created_to']);
        }
        
        if (!empty($filters['expires_from'])) {
            $query->where('expires_at >=', $filters['expires_from']);
        }
        
        if (!empty($filters['expires_to'])) {
            $query->where('expires_at <=', $filters['expires_to']);
        }
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $query->orderBy($orderBy, $orderDir)
                    ->limit($limit, $offset)
                    ->findAll();
    }
    
    /**
     * Exporta tokens para CSV
     */
    public function exportToCSV(array $filters = []): string
    {
        $tokens = $this->advancedSearch($filters, 10000);
        
        $csv = "ID,Usuário,Tipo,Dispositivo,IP,Ativo,Revogado,Blacklist,Criado,Expira,Último Uso\n";
        
        foreach ($tokens as $token) {
            $csv .= sprintf(
                "%d,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $token['id'],
                $token['user_id'],
                $token['token_type'],
                $token['device_type'] ?? '',
                $token['ip_address'] ?? '',
                $token['is_active'] ? 'Sim' : 'Não',
                $token['is_revoked'] ? 'Sim' : 'Não',
                $token['is_blacklisted'] ? 'Sim' : 'Não',
                $token['created_at'],
                $token['expires_at'],
                $token['last_used_at'] ?? ''
            );
        }
        
        return $csv;
    }
}