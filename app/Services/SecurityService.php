<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use CodeIgniter\Encryption\Encryption;
use Exception;

class SecurityService
{
    protected Encryption $encryption;
    protected array $sensitiveFields = [
        'cpf', 'cnpj', 'phone', 'email', 'address', 'credit_card_number',
        'bank_account', 'pix_key', 'password', 'token', 'api_key'
    ];
    
    protected array $auditableActions = [
        'login', 'logout', 'create', 'update', 'delete', 'view_sensitive',
        'export_data', 'payment', 'subscription_change'
    ];
    
    public function __construct()
    {
        $this->encryption = \Config\Services::encrypter();
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encryptData(string $data, string $context = 'general'): string
    {
        try {
            // Add context and timestamp for additional security
            $payload = [
                'data' => $data,
                'context' => $context,
                'timestamp' => Time::now()->getTimestamp(),
                'checksum' => hash('sha256', $data . $context)
            ];
            
            return $this->encryption->encrypt(json_encode($payload));
            
        } catch (Exception $e) {
            log_message('error', 'Erro na criptografia: ' . $e->getMessage());
            throw new Exception('Falha na criptografia dos dados');
        }
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decryptData(string $encryptedData, string $expectedContext = 'general'): string
    {
        try {
            $decrypted = $this->encryption->decrypt($encryptedData);
            $payload = json_decode($decrypted, true);
            
            if (!$payload || !isset($payload['data'], $payload['context'], $payload['checksum'])) {
                throw new Exception('Dados criptografados inválidos');
            }
            
            // Verify context
            if ($payload['context'] !== $expectedContext) {
                throw new Exception('Contexto de descriptografia inválido');
            }
            
            // Verify checksum
            $expectedChecksum = hash('sha256', $payload['data'] . $payload['context']);
            if ($payload['checksum'] !== $expectedChecksum) {
                throw new Exception('Integridade dos dados comprometida');
            }
            
            return $payload['data'];
            
        } catch (Exception $e) {
            log_message('error', 'Erro na descriptografia: ' . $e->getMessage());
            throw new Exception('Falha na descriptografia dos dados');
        }
    }
    
    /**
     * Hash password with salt
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure token
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate API key
     */
    public function generateApiKey(int $restaurantId): string
    {
        $prefix = 'tts_' . $restaurantId . '_';
        $randomPart = $this->generateSecureToken(16);
        $timestamp = Time::now()->getTimestamp();
        
        return $prefix . $randomPart . '_' . base_convert($timestamp, 10, 36);
    }
    
    /**
     * Validate API key format
     */
    public function validateApiKeyFormat(string $apiKey): bool
    {
        return preg_match('/^tts_\d+_[a-f0-9]{32}_[a-z0-9]+$/', $apiKey) === 1;
    }
    
    /**
     * Mask sensitive data for display
     */
    public function maskSensitiveData(string $data, string $type = 'general'): string
    {
        switch ($type) {
            case 'cpf':
                return preg_replace('/(\d{3})\d{3}(\d{3})/', '$1***$2', $data);
            case 'cnpj':
                return preg_replace('/(\d{2})\d{3}\d{3}(\d{4})/', '$1***$2', $data);
            case 'email':
                $parts = explode('@', $data);
                if (count($parts) === 2) {
                    $username = $parts[0];
                    $domain = $parts[1];
                    $maskedUsername = substr($username, 0, 2) . str_repeat('*', max(1, strlen($username) - 4)) . substr($username, -2);
                    return $maskedUsername . '@' . $domain;
                }
                return $data;
            case 'phone':
                return preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '$1****$3', $data);
            case 'credit_card':
                return preg_replace('/(\d{4})\d{8}(\d{4})/', '$1********$2', $data);
            default:
                $length = strlen($data);
                if ($length <= 4) {
                    return str_repeat('*', $length);
                }
                return substr($data, 0, 2) . str_repeat('*', $length - 4) . substr($data, -2);
        }
    }
    
    /**
     * Log security event for audit
     */
    public function logSecurityEvent(array $eventData): void
    {
        $logData = [
            'timestamp' => Time::now()->toDateTimeString(),
            'ip_address' => $this->getClientIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'session_id' => session_id(),
            'restaurant_id' => $eventData['restaurant_id'] ?? null,
            'user_id' => $eventData['user_id'] ?? null,
            'action' => $eventData['action'] ?? 'unknown',
            'resource' => $eventData['resource'] ?? null,
            'details' => $eventData['details'] ?? null,
            'severity' => $eventData['severity'] ?? 'info', // info, warning, error, critical
            'success' => $eventData['success'] ?? true
        ];
        
        // Store in database (implement audit log table)
        $this->storeAuditLog($logData);
        
        // Log to file for backup
        log_message('info', 'Security Event: ' . json_encode($logData));
    }
    
    /**
     * Check for suspicious activity
     */
    public function detectSuspiciousActivity(int $restaurantId, string $action): array
    {
        $suspiciousIndicators = [];
        $ipAddress = $this->getClientIpAddress();
        
        // Check for multiple failed login attempts
        if ($action === 'login_failed') {
            $recentFailures = $this->getRecentFailedLogins($ipAddress, 15); // Last 15 minutes
            if ($recentFailures >= 5) {
                $suspiciousIndicators[] = 'multiple_failed_logins';
            }
        }
        
        // Check for unusual access patterns
        if (in_array($action, ['view_sensitive', 'export_data'])) {
            $recentSensitiveAccess = $this->getRecentSensitiveAccess($restaurantId, 60); // Last hour
            if ($recentSensitiveAccess >= 10) {
                $suspiciousIndicators[] = 'excessive_sensitive_access';
            }
        }
        
        // Check for access from new locations
        if ($this->isNewLocation($restaurantId, $ipAddress)) {
            $suspiciousIndicators[] = 'new_location_access';
        }
        
        // Check for unusual time access
        $currentHour = (int) Time::now()->format('H');
        if ($currentHour < 6 || $currentHour > 23) {
            $suspiciousIndicators[] = 'unusual_time_access';
        }
        
        return [
            'is_suspicious' => !empty($suspiciousIndicators),
            'indicators' => $suspiciousIndicators,
            'risk_level' => $this->calculateRiskLevel($suspiciousIndicators)
        ];
    }
    
    /**
     * Implement rate limiting
     */
    public function checkRateLimit(string $identifier, int $maxRequests = 100, int $timeWindow = 3600): bool
    {
        $cacheKey = 'rate_limit_' . hash('sha256', $identifier);
        $requests = cache($cacheKey) ?? [];
        
        // Remove old requests outside time window
        $currentTime = Time::now()->getTimestamp();
        $requests = array_filter($requests, function($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) < $timeWindow;
        });
        
        // Check if limit exceeded
        if (count($requests) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        $requests[] = $currentTime;
        cache()->save($cacheKey, $requests, $timeWindow);
        
        return true;
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove potentially dangerous characters
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                
                // Additional sanitization for specific fields
                switch ($key) {
                    case 'email':
                        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                        break;
                    case 'phone':
                        $value = preg_replace('/[^0-9+\-\(\)\s]/', '', $value);
                        break;
                    case 'cpf':
                    case 'cnpj':
                        $value = preg_replace('/[^0-9]/', '', $value);
                        break;
                    case 'url':
                    case 'website':
                        $value = filter_var($value, FILTER_SANITIZE_URL);
                        break;
                }
            } elseif (is_array($value)) {
                $value = $this->sanitizeInput($value);
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }
    
    /**
     * Validate LGPD consent
     */
    public function validateLGPDConsent(int $restaurantId, array $consentTypes): bool
    {
        // Check if all required consents are given
        $requiredConsents = ['data_processing', 'marketing', 'cookies'];
        
        foreach ($requiredConsents as $consent) {
            if (!isset($consentTypes[$consent]) || !$consentTypes[$consent]) {
                return false;
            }
        }
        
        // Log consent for audit
        $this->logSecurityEvent([
            'restaurant_id' => $restaurantId,
            'action' => 'lgpd_consent',
            'details' => json_encode($consentTypes),
            'severity' => 'info'
        ]);
        
        return true;
    }
    
    /**
     * Generate data export for LGPD compliance
     */
    public function generateDataExport(int $restaurantId): array
    {
        // This would collect all data related to the restaurant
        // from all tables and return it in a structured format
        
        $exportData = [
            'restaurant_data' => $this->getRestaurantData($restaurantId),
            'subscription_data' => $this->getSubscriptionData($restaurantId),
            'usage_data' => $this->getUsageData($restaurantId),
            'audit_logs' => $this->getAuditLogs($restaurantId),
            'export_timestamp' => Time::now()->toDateTimeString(),
            'export_format' => 'JSON',
            'data_retention_policy' => $this->getDataRetentionPolicy()
        ];
        
        // Log data export
        $this->logSecurityEvent([
            'restaurant_id' => $restaurantId,
            'action' => 'data_export',
            'details' => 'LGPD data export generated',
            'severity' => 'info'
        ]);
        
        return $exportData;
    }
    
    /**
     * Process data deletion request (LGPD right to be forgotten)
     */
    public function processDataDeletion(int $restaurantId, array $deletionScope = []): array
    {
        $deletionResults = [];
        
        try {
            // Define what can be deleted vs what must be retained for legal reasons
            $deletableData = ['marketing_data', 'analytics_data', 'optional_profile_data'];
            $retainedData = ['financial_records', 'legal_documents', 'audit_logs'];
            
            foreach ($deletionScope as $dataType) {
                if (in_array($dataType, $deletableData)) {
                    $result = $this->deleteDataType($restaurantId, $dataType);
                    $deletionResults[$dataType] = $result;
                } else {
                    $deletionResults[$dataType] = [
                        'deleted' => false,
                        'reason' => 'Legal retention requirement'
                    ];
                }
            }
            
            // Log deletion request
            $this->logSecurityEvent([
                'restaurant_id' => $restaurantId,
                'action' => 'data_deletion',
                'details' => json_encode($deletionResults),
                'severity' => 'warning'
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro na exclusão de dados: ' . $e->getMessage());
            throw new Exception('Falha no processamento da exclusão de dados');
        }
        
        return $deletionResults;
    }
    
    /**
     * Get client IP address
     */
    protected function getClientIpAddress(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Store audit log in database
     */
    protected function storeAuditLog(array $logData): void
    {
        // Implementation would store in audit_logs table
        // This is a placeholder for the actual database storage
        $db = \Config\Database::connect();
        
        try {
            $db->table('audit_logs')->insert($logData);
        } catch (Exception $e) {
            log_message('error', 'Falha ao armazenar log de auditoria: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate risk level based on suspicious indicators
     */
    protected function calculateRiskLevel(array $indicators): string
    {
        $riskScore = count($indicators);
        
        if ($riskScore >= 3) {
            return 'high';
        } elseif ($riskScore >= 2) {
            return 'medium';
        } elseif ($riskScore >= 1) {
            return 'low';
        }
        
        return 'none';
    }
    
    /**
     * Get recent failed login attempts
     */
    protected function getRecentFailedLogins(string $ipAddress, int $minutes): int
    {
        // Implementation would query audit_logs table
        // This is a placeholder
        return 0;
    }
    
    /**
     * Get recent sensitive data access
     */
    protected function getRecentSensitiveAccess(int $restaurantId, int $minutes): int
    {
        // Implementation would query audit_logs table
        // This is a placeholder
        return 0;
    }
    
    /**
     * Check if IP address is from a new location
     */
    protected function isNewLocation(int $restaurantId, string $ipAddress): bool
    {
        // Implementation would check against known IP addresses
        // This is a placeholder
        return false;
    }
    
    /**
     * Get restaurant data for export
     */
    protected function getRestaurantData(int $restaurantId): array
    {
        // Implementation would collect restaurant data
        return [];
    }
    
    /**
     * Get subscription data for export
     */
    protected function getSubscriptionData(int $restaurantId): array
    {
        // Implementation would collect subscription data
        return [];
    }
    
    /**
     * Get usage data for export
     */
    protected function getUsageData(int $restaurantId): array
    {
        // Implementation would collect usage data
        return [];
    }
    
    /**
     * Get audit logs for export
     */
    protected function getAuditLogs(int $restaurantId): array
    {
        // Implementation would collect audit logs
        return [];
    }
    
    /**
     * Get data retention policy
     */
    protected function getDataRetentionPolicy(): array
    {
        return [
            'financial_data' => '7 years',
            'audit_logs' => '5 years',
            'user_data' => '2 years after account closure',
            'marketing_data' => 'Until consent withdrawal',
            'analytics_data' => '2 years'
        ];
    }
    
    /**
     * Delete specific data type
     */
    protected function deleteDataType(int $restaurantId, string $dataType): array
    {
        // Implementation would delete specific data types
        return [
            'deleted' => true,
            'records_affected' => 0,
            'timestamp' => Time::now()->toDateTimeString()
        ];
    }
}