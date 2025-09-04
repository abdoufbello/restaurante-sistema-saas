<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\JWTAuthModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Controlador Base para APIs RESTful com Autenticação JWT
 */
class BaseApiController extends ResourceController
{
    use ResponseTrait;
    
    protected $format = 'json';
    protected $jwtAuth;
    protected $currentUser;
    protected $currentToken;
    protected $restaurantId;
    
    // Configurações de paginação
    protected $defaultLimit = 20;
    protected $maxLimit = 100;
    
    // Configurações de cache
    protected $cacheEnabled = true;
    protected $cacheTime = 300; // 5 minutos
    
    public function __construct()
    {
        parent::__construct();
        $this->jwtAuth = new JWTAuthModel();
        
        // Configurar CORS
        $this->setCorsHeaders();
        
        // Verificar autenticação se necessário
        if ($this->requiresAuth()) {
            $this->authenticate();
        }
    }
    
    /**
     * Configura headers CORS
     */
    protected function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // Responder a requisições OPTIONS
        if ($this->request->getMethod() === 'options') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Verifica se o endpoint requer autenticação
     */
    protected function requiresAuth(): bool
    {
        // Por padrão, todos os endpoints requerem autenticação
        // Sobrescrever em controladores específicos se necessário
        return true;
    }
    
    /**
     * Autentica usuário via JWT
     */
    protected function authenticate(): void
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            $this->failUnauthorized('Token de acesso requerido');
        }
        
        // Extrair token do header Authorization
        if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $this->failUnauthorized('Formato de token inválido');
        }
        
        $token = $matches[1];
        
        // Validar token
        $payload = $this->jwtAuth->validateToken($token);
        
        if (!$payload) {
            $this->failUnauthorized('Token inválido ou expirado');
        }
        
        // Definir usuário e token atuais
        $this->currentUser = $payload;
        $this->currentToken = $token;
        $this->restaurantId = $payload['restaurant_id'] ?? null;
        
        // Verificar se o restaurante está ativo (se aplicável)
        if ($this->restaurantId && !$this->isRestaurantActive($this->restaurantId)) {
            $this->failForbidden('Restaurante inativo ou suspenso');
        }
    }
    
    /**
     * Verifica se restaurante está ativo
     */
    protected function isRestaurantActive(int $restaurantId): bool
    {
        $restaurantModel = new \App\Models\RestaurantModel();
        $restaurant = $restaurantModel->find($restaurantId);
        
        return $restaurant && $restaurant['status'] === 'active';
    }
    
    /**
     * Verifica se usuário tem permissão específica
     */
    protected function hasPermission(string $permission): bool
    {
        if (!$this->currentUser) {
            return false;
        }
        
        return $this->jwtAuth->hasPermission($this->currentUser, $permission);
    }
    
    /**
     * Verifica se usuário tem role específica
     */
    protected function hasRole(string $role): bool
    {
        if (!$this->currentUser) {
            return false;
        }
        
        return $this->jwtAuth->hasRole($this->currentUser, $role);
    }
    
    /**
     * Verifica permissão ou falha com 403
     */
    protected function requirePermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            $this->failForbidden("Permissão '{$permission}' requerida");
        }
    }
    
    /**
     * Verifica role ou falha com 403
     */
    protected function requireRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            $this->failForbidden("Role '{$role}' requerida");
        }
    }
    
    /**
     * Obtém parâmetros de paginação
     */
    protected function getPaginationParams(): array
    {
        $page = max(1, (int) $this->request->getGet('page') ?: 1);
        $limit = min(
            $this->maxLimit,
            max(1, (int) $this->request->getGet('limit') ?: $this->defaultLimit)
        );
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Obtém parâmetros de ordenação
     */
    protected function getSortParams(array $allowedFields = []): array
    {
        $sortBy = $this->request->getGet('sort_by') ?: 'id';
        $sortDir = strtoupper($this->request->getGet('sort_dir') ?: 'DESC');
        
        // Validar campo de ordenação
        if (!empty($allowedFields) && !in_array($sortBy, $allowedFields)) {
            $sortBy = $allowedFields[0] ?? 'id';
        }
        
        // Validar direção
        if (!in_array($sortDir, ['ASC', 'DESC'])) {
            $sortDir = 'DESC';
        }
        
        return [
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];
    }
    
    /**
     * Obtém filtros de data
     */
    protected function getDateFilters(): array
    {
        $filters = [];
        
        if ($dateFrom = $this->request->getGet('date_from')) {
            $filters['date_from'] = $dateFrom;
        }
        
        if ($dateTo = $this->request->getGet('date_to')) {
            $filters['date_to'] = $dateTo;
        }
        
        if ($createdFrom = $this->request->getGet('created_from')) {
            $filters['created_from'] = $createdFrom;
        }
        
        if ($createdTo = $this->request->getGet('created_to')) {
            $filters['created_to'] = $createdTo;
        }
        
        return $filters;
    }
    
    /**
     * Valida dados de entrada
     */
    protected function validateInput(array $data, array $rules, array $messages = []): array
    {
        $validation = \Config\Services::validation();
        $validation->setRules($rules, $messages);
        
        if (!$validation->run($data)) {
            $this->failValidationErrors($validation->getErrors());
        }
        
        return $validation->getValidated();
    }
    
    /**
     * Resposta de sucesso padronizada
     */
    protected function respondSuccess($data = null, string $message = 'Operação realizada com sucesso', int $code = 200): \CodeIgniter\HTTP\ResponseInterface
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $this->respond($response, $code);
    }
    
    /**
     * Resposta de sucesso com paginação
     */
    protected function respondWithPagination($data, int $total, array $pagination): \CodeIgniter\HTTP\ResponseInterface
    {
        $totalPages = ceil($total / $pagination['limit']);
        
        $response = [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['limit'],
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $pagination['page'] < $totalPages,
                'has_prev' => $pagination['page'] > 1
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $this->respond($response);
    }
    
    /**
     * Resposta de erro de validação
     */
    protected function failValidationErrors($errors): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], 422);
    }
    
    /**
     * Resposta de erro não autorizado
     */
    protected function failUnauthorized(string $message = 'Não autorizado'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 401);
    }
    
    /**
     * Resposta de erro proibido
     */
    protected function failForbidden(string $message = 'Acesso negado'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 403);
    }
    
    /**
     * Resposta de erro não encontrado
     */
    protected function failNotFound(string $message = 'Recurso não encontrado'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 404);
    }
    
    /**
     * Resposta de erro interno
     */
    protected function failServerError(string $message = 'Erro interno do servidor'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 500);
    }
    
    /**
     * Resposta de erro de conflito
     */
    protected function failConflict(string $message = 'Conflito de dados'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 409);
    }
    
    /**
     * Resposta de erro de limite excedido
     */
    protected function failTooManyRequests(string $message = 'Muitas requisições'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 429);
    }
    
    /**
     * Log de atividade da API
     */
    protected function logActivity(string $action, array $data = []): void
    {
        $logData = [
            'user_id' => $this->currentUser['user_id'] ?? null,
            'restaurant_id' => $this->restaurantId,
            'action' => $action,
            'endpoint' => $this->request->getUri()->getPath(),
            'method' => $this->request->getMethod(),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'data' => json_encode($data),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Salvar no log (implementar conforme necessário)
        log_message('info', 'API Activity: ' . json_encode($logData));
    }
    
    /**
     * Obtém dados do cache
     */
    protected function getFromCache(string $key)
    {
        if (!$this->cacheEnabled) {
            return null;
        }
        
        $cache = \Config\Services::cache();
        return $cache->get($key);
    }
    
    /**
     * Salva dados no cache
     */
    protected function saveToCache(string $key, $data, int $ttl = null): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        $cache = \Config\Services::cache();
        return $cache->save($key, $data, $ttl ?? $this->cacheTime);
    }
    
    /**
     * Remove dados do cache
     */
    protected function deleteFromCache(string $key): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        $cache = \Config\Services::cache();
        return $cache->delete($key);
    }
    
    /**
     * Gera chave de cache baseada em parâmetros
     */
    protected function generateCacheKey(string $prefix, array $params = []): string
    {
        $key = $prefix;
        
        if ($this->restaurantId) {
            $key .= '_restaurant_' . $this->restaurantId;
        }
        
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        
        return $key;
    }
    
    /**
     * Aplica filtros de multi-tenancy
     */
    protected function applyTenantFilter($query)
    {
        if ($this->restaurantId) {
            $query->where('restaurant_id', $this->restaurantId);
        }
        
        return $query;
    }
    
    /**
     * Sanitiza dados de saída removendo campos sensíveis
     */
    protected function sanitizeOutput(array $data, array $hiddenFields = []): array
    {
        $defaultHidden = ['password', 'token_hash', 'refresh_token_hash', 'deleted_at'];
        $fieldsToHide = array_merge($defaultHidden, $hiddenFields);
        
        if (isset($data[0]) && is_array($data[0])) {
            // Array de registros
            return array_map(function($item) use ($fieldsToHide) {
                return array_diff_key($item, array_flip($fieldsToHide));
            }, $data);
        } else {
            // Registro único
            return array_diff_key($data, array_flip($fieldsToHide));
        }
    }
    
    /**
     * Converte dados para formato de exportação
     */
    protected function formatForExport(array $data, string $format = 'json'): string
    {
        switch (strtolower($format)) {
            case 'csv':
                return $this->arrayToCsv($data);
            case 'xml':
                return $this->arrayToXml($data);
            case 'json':
            default:
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Converte array para CSV
     */
    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Cabeçalhos
        if (isset($data[0]) && is_array($data[0])) {
            fputcsv($output, array_keys($data[0]));
            
            // Dados
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, array_keys($data));
            fputcsv($output, $data);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Converte array para XML
     */
    private function arrayToXml(array $data, string $rootElement = 'data'): string
    {
        $xml = new \SimpleXMLElement("<{$rootElement}></{$rootElement}>");
        $this->arrayToXmlRecursive($data, $xml);
        return $xml->asXML();
    }
    
    /**
     * Função recursiva para conversão XML
     */
    private function arrayToXmlRecursive(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXmlRecursive($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}