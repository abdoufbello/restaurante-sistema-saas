<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\JWTAuthModel;
use App\Models\RestaurantModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTAuthFilter implements FilterInterface
{
    /**
     * Executa o filtro de autenticação JWT
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');
        
        try {
            // Obter token do header Authorization
            $authHeader = $request->getHeaderLine('Authorization');
            
            if (empty($authHeader)) {
                return $this->unauthorizedResponse($response, 'Token de acesso não fornecido');
            }
            
            // Verificar formato Bearer Token
            if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
                return $this->unauthorizedResponse($response, 'Formato de token inválido');
            }
            
            $token = $matches[1];
            
            // Validar token JWT
            $jwtModel = new JWTAuthModel();
            $tokenData = $jwtModel->validateToken($token);
            
            if (!$tokenData) {
                return $this->unauthorizedResponse($response, 'Token inválido ou expirado');
            }
            
            // Verificar se o token está na blacklist
            if ($jwtModel->isTokenBlacklisted($token)) {
                return $this->unauthorizedResponse($response, 'Token foi revogado');
            }
            
            // Decodificar JWT para obter dados do usuário
            $jwtKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
            $decoded = JWT::decode($token, new Key($jwtKey, 'HS256'));
            
            // Verificar se o restaurante está ativo
            if (!empty($decoded->restaurant_id)) {
                $restaurantModel = new RestaurantModel();
                $restaurant = $restaurantModel->find($decoded->restaurant_id);
                
                if (!$restaurant || $restaurant['status'] !== 'active') {
                    return $this->forbiddenResponse($response, 'Restaurante inativo ou suspenso');
                }
                
                // Verificar limites do plano
                if (!$this->checkPlanLimits($restaurant, $request)) {
                    return $this->forbiddenResponse($response, 'Limite do plano excedido');
                }
            }
            
            // Armazenar dados do usuário na requisição
            $request->user_id = $decoded->user_id ?? null;
            $request->restaurant_id = $decoded->restaurant_id ?? null;
            $request->roles = $decoded->roles ?? [];
            $request->permissions = $decoded->permissions ?? [];
            $request->token_id = $tokenData['token_id'];
            
            // Atualizar último uso do token
            $jwtModel->updateLastUsed($tokenData['id']);
            
            // Log da atividade da API
            $this->logApiActivity($request, $decoded);
            
        } catch (Exception $e) {
            log_message('error', 'JWT Auth Error: ' . $e->getMessage());
            return $this->unauthorizedResponse($response, 'Erro na autenticação: ' . $e->getMessage());
        }
        
        return $request;
    }
    
    /**
     * Executa após o processamento da requisição
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Adicionar headers de segurança
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        
        return $response;
    }
    
    /**
     * Retorna resposta de não autorizado
     *
     * @param ResponseInterface $response
     * @param string $message
     * @return ResponseInterface
     */
    private function unauthorizedResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        return $response->setStatusCode(401)
                       ->setContentType('application/json')
                       ->setBody(json_encode([
                           'success' => false,
                           'message' => $message,
                           'error_code' => 'UNAUTHORIZED',
                           'timestamp' => date('c')
                       ]));
    }
    
    /**
     * Retorna resposta de proibido
     *
     * @param ResponseInterface $response
     * @param string $message
     * @return ResponseInterface
     */
    private function forbiddenResponse(ResponseInterface $response, string $message): ResponseInterface
    {
        return $response->setStatusCode(403)
                       ->setContentType('application/json')
                       ->setBody(json_encode([
                           'success' => false,
                           'message' => $message,
                           'error_code' => 'FORBIDDEN',
                           'timestamp' => date('c')
                       ]));
    }
    
    /**
     * Verifica limites do plano
     *
     * @param array $restaurant
     * @param RequestInterface $request
     * @return bool
     */
    private function checkPlanLimits(array $restaurant, RequestInterface $request): bool
    {
        // Verificar se tem plano ativo
        if (empty($restaurant['subscription_plan']) || $restaurant['subscription_status'] !== 'active') {
            return false;
        }
        
        $plan = $restaurant['subscription_plan'];
        $usage = json_decode($restaurant['usage_stats'] ?? '{}', true);
        
        // Definir limites por plano
        $limits = [
            'starter' => [
                'api_calls_per_month' => 10000,
                'storage_mb' => 1000,
                'users' => 5
            ],
            'professional' => [
                'api_calls_per_month' => 50000,
                'storage_mb' => 5000,
                'users' => 25
            ],
            'enterprise' => [
                'api_calls_per_month' => -1, // Ilimitado
                'storage_mb' => -1, // Ilimitado
                'users' => -1 // Ilimitado
            ]
        ];
        
        if (!isset($limits[$plan])) {
            return false;
        }
        
        $planLimits = $limits[$plan];
        
        // Verificar limite de chamadas da API
        if ($planLimits['api_calls_per_month'] > 0) {
            $currentMonth = date('Y-m');
            $monthlyUsage = $usage['api_calls'][$currentMonth] ?? 0;
            
            if ($monthlyUsage >= $planLimits['api_calls_per_month']) {
                return false;
            }
        }
        
        // Verificar limite de usuários
        if ($planLimits['users'] > 0) {
            $userCount = $usage['active_users'] ?? 0;
            
            if ($userCount >= $planLimits['users']) {
                return false;
            }
        }
        
        // Verificar limite de armazenamento
        if ($planLimits['storage_mb'] > 0) {
            $storageUsed = $usage['storage_mb'] ?? 0;
            
            if ($storageUsed >= $planLimits['storage_mb']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Registra atividade da API
     *
     * @param RequestInterface $request
     * @param object $decoded
     * @return void
     */
    private function logApiActivity(RequestInterface $request, object $decoded): void
    {
        try {
            $logData = [
                'user_id' => $decoded->user_id ?? null,
                'restaurant_id' => $decoded->restaurant_id ?? null,
                'method' => $request->getMethod(),
                'uri' => $request->getUri()->getPath(),
                'ip_address' => $request->getIPAddress(),
                'user_agent' => $request->getUserAgent(),
                'timestamp' => date('Y-m-d H:i:s'),
                'query_params' => $request->getGet(),
                'body_size' => strlen($request->getBody())
            ];
            
            // Salvar no cache para processamento posterior
            $cacheKey = 'api_activity_' . uniqid();
            cache()->save($cacheKey, $logData, 3600); // 1 hora
            
        } catch (Exception $e) {
            log_message('error', 'Error logging API activity: ' . $e->getMessage());
        }
    }
}