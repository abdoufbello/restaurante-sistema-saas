<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use App\Services\SecurityService;
use Exception;

class SecurityFilter implements FilterInterface
{
    protected SecurityService $securityService;
    
    public function __construct()
    {
        $this->securityService = new SecurityService();
    }
    
    /**
     * Security checks before request processing
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri();
        $method = $request->getMethod();
        $ipAddress = $this->securityService->getClientIpAddress();
        
        // Rate limiting check
        $rateLimitKey = $ipAddress . '_' . session_id();
        if (!$this->securityService->checkRateLimit($rateLimitKey, 200, 3600)) {
            $this->logSecurityEvent([
                'action' => 'rate_limit_exceeded',
                'details' => "IP: {$ipAddress}, URI: {$uri}",
                'severity' => 'warning',
                'success' => false
            ]);
            
            return $this->handleRateLimitExceeded();
        }
        
        // Check for suspicious activity
        $restaurantId = session('restaurant_id');
        if ($restaurantId) {
            $suspiciousActivity = $this->securityService->detectSuspiciousActivity(
                $restaurantId, 
                $this->getActionFromUri($uri->getPath())
            );
            
            if ($suspiciousActivity['is_suspicious']) {
                $this->logSecurityEvent([
                    'restaurant_id' => $restaurantId,
                    'action' => 'suspicious_activity_detected',
                    'details' => json_encode($suspiciousActivity),
                    'severity' => $suspiciousActivity['risk_level'] === 'high' ? 'critical' : 'warning',
                    'success' => false
                ]);
                
                // Block high-risk activities
                if ($suspiciousActivity['risk_level'] === 'high') {
                    return $this->handleSuspiciousActivity($suspiciousActivity);
                }
            }
        }
        
        // HTTPS enforcement for sensitive operations
        if ($this->isSensitiveOperation($uri->getPath()) && !$request->isSecure()) {
            $this->logSecurityEvent([
                'restaurant_id' => $restaurantId,
                'action' => 'insecure_sensitive_operation',
                'details' => "URI: {$uri}, Method: {$method}",
                'severity' => 'warning',
                'success' => false
            ]);
            
            return $this->enforceHttps($uri);
        }
        
        // Session security checks
        if ($restaurantId && !$this->validateSession()) {
            return redirect()->to('/login')->with('error', 'Sessão inválida ou expirada');
        }
        
        // LGPD consent check for data processing operations
        if ($restaurantId && $this->requiresLGPDConsent($uri->getPath())) {
            if (!$this->hasValidLGPDConsent($restaurantId)) {
                return redirect()->to('/privacy/consent')
                    ->with('error', 'É necessário consentimento para processamento de dados');
            }
        }
        
        // Input sanitization for POST/PUT requests
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $this->sanitizeRequestData($request);
        }
        
        // Log access for audit
        if ($this->shouldLogAccess($uri->getPath())) {
            $this->logSecurityEvent([
                'restaurant_id' => $restaurantId,
                'action' => 'page_access',
                'resource' => $uri->getPath(),
                'details' => "Method: {$method}",
                'severity' => 'info',
                'success' => true
            ]);
        }
        
        return null;
    }
    
    /**
     * Security checks after request processing
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add security headers
        $response = $this->addSecurityHeaders($response);
        
        // Log response for sensitive operations
        $uri = $request->getUri()->getPath();
        if ($this->isSensitiveOperation($uri)) {
            $this->logSecurityEvent([
                'restaurant_id' => session('restaurant_id'),
                'action' => 'sensitive_operation_completed',
                'resource' => $uri,
                'details' => "Status: {$response->getStatusCode()}",
                'severity' => 'info',
                'success' => $response->getStatusCode() < 400
            ]);
        }
        
        return $response;
    }
    
    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders(ResponseInterface $response): ResponseInterface
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => $this->getContentSecurityPolicy(),
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
        ];
        
        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }
        
        return $response;
    }
    
    /**
     * Get Content Security Policy
     */
    protected function getContentSecurityPolicy(): string
    {
        $policy = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://api.mercadopago.com https://ws.sandbox.pagseguro.uol.com.br https://ws.pagseguro.uol.com.br",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        return implode('; ', $policy);
    }
    
    /**
     * Handle rate limit exceeded
     */
    protected function handleRateLimitExceeded()
    {
        if (service('request')->isAJAX()) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'error' => 'Muitas solicitações. Tente novamente em alguns minutos.',
                    'retry_after' => 3600
                ]);
        }
        
        return service('response')
            ->setStatusCode(429)
            ->setBody(view('errors/rate_limit_exceeded'));
    }
    
    /**
     * Handle suspicious activity
     */
    protected function handleSuspiciousActivity(array $suspiciousActivity)
    {
        if (service('request')->isAJAX()) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'error' => 'Atividade suspeita detectada. Acesso temporariamente bloqueado.',
                    'risk_level' => $suspiciousActivity['risk_level'],
                    'contact_support' => true
                ]);
        }
        
        return service('response')
            ->setStatusCode(403)
            ->setBody(view('errors/suspicious_activity', ['data' => $suspiciousActivity]));
    }
    
    /**
     * Enforce HTTPS for sensitive operations
     */
    protected function enforceHttps($uri)
    {
        $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $uri;
        return redirect()->to($httpsUrl, 301);
    }
    
    /**
     * Validate session security
     */
    protected function validateSession(): bool
    {
        $session = session();
        
        // Check session timeout
        $lastActivity = $session->get('last_activity');
        if ($lastActivity) {
            $timeout = 8 * 3600; // 8 hours default
            if (time() - $lastActivity > $timeout) {
                $session->destroy();
                return false;
            }
        }
        
        // Update last activity
        $session->set('last_activity', time());
        
        // Validate session fingerprint
        $currentFingerprint = $this->generateSessionFingerprint();
        $storedFingerprint = $session->get('session_fingerprint');
        
        if ($storedFingerprint && $currentFingerprint !== $storedFingerprint) {
            $this->logSecurityEvent([
                'restaurant_id' => $session->get('restaurant_id'),
                'action' => 'session_hijack_attempt',
                'details' => 'Session fingerprint mismatch',
                'severity' => 'critical',
                'success' => false
            ]);
            
            $session->destroy();
            return false;
        }
        
        if (!$storedFingerprint) {
            $session->set('session_fingerprint', $currentFingerprint);
        }
        
        return true;
    }
    
    /**
     * Generate session fingerprint
     */
    protected function generateSessionFingerprint(): string
    {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Check if operation is sensitive
     */
    protected function isSensitiveOperation(string $path): bool
    {
        $sensitivePatterns = [
            '/admin/',
            '/subscription/',
            '/billing/',
            '/payment/',
            '/export/',
            '/settings/security',
            '/api/sensitive'
        ];
        
        foreach ($sensitivePatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if operation requires LGPD consent
     */
    protected function requiresLGPDConsent(string $path): bool
    {
        $consentRequiredPatterns = [
            '/analytics/',
            '/marketing/',
            '/export/',
            '/reports/',
            '/customer/data'
        ];
        
        foreach ($consentRequiredPatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if restaurant has valid LGPD consent
     */
    protected function hasValidLGPDConsent(int $restaurantId): bool
    {
        $db = \Config\Database::connect();
        
        $requiredConsents = ['data_processing', 'analytics', 'marketing'];
        
        foreach ($requiredConsents as $consentType) {
            $consent = $db->table('data_consents')
                ->where('restaurant_id', $restaurantId)
                ->where('consent_type', $consentType)
                ->where('consent_given', true)
                ->where('expires_at >', Time::now()->toDateTimeString())
                ->get()
                ->getRow();
                
            if (!$consent) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize request data
     */
    protected function sanitizeRequestData(RequestInterface $request): void
    {
        $data = $request->getPost() ?: $request->getJSON(true) ?: [];
        
        if (!empty($data)) {
            $sanitized = $this->securityService->sanitizeInput($data);
            
            // Update request with sanitized data
            if ($request->getPost()) {
                $_POST = $sanitized;
            }
        }
    }
    
    /**
     * Check if access should be logged
     */
    protected function shouldLogAccess(string $path): bool
    {
        $logPatterns = [
            '/admin/',
            '/subscription/',
            '/billing/',
            '/settings/',
            '/export/',
            '/api/'
        ];
        
        foreach ($logPatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get action from URI path
     */
    protected function getActionFromUri(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        
        if (empty($segments[0])) {
            return 'home';
        }
        
        $action = $segments[0];
        if (isset($segments[1])) {
            $action .= '_' . $segments[1];
        }
        
        return $action;
    }
    
    /**
     * Log security event
     */
    protected function logSecurityEvent(array $eventData): void
    {
        try {
            $this->securityService->logSecurityEvent($eventData);
        } catch (Exception $e) {
            log_message('error', 'Falha ao registrar evento de segurança: ' . $e->getMessage());
        }
    }
}