<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * API Authentication Filter
 * Protects API routes using JWT tokens
 */
class ApiAuthFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');
        
        // Get the authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            return $response->setJSON([
                'error' => 'Authorization header missing',
                'message' => 'Token de autorização é obrigatório'
            ])->setStatusCode(401);
        }
        
        // Extract token from "Bearer <token>" format
        if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $response->setJSON([
                'error' => 'Invalid authorization format',
                'message' => 'Formato de autorização inválido. Use: Bearer <token>'
            ])->setStatusCode(401);
        }
        
        $token = $matches[1];
        
        try {
            // Decode and validate JWT token
            $key = getenv('JWT_SECRET_KEY') ?: 'your-secret-key-change-this-in-production';
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            
            // Check if token is expired
            if ($decoded->exp < time()) {
                return $response->setJSON([
                    'error' => 'Token expired',
                    'message' => 'Token expirado. Faça login novamente.'
                ])->setStatusCode(401);
            }
            
            // Validate restaurant and employee
            $employeeModel = new \App\Models\Restaurant\EmployeeModel();
            $employee = $employeeModel->find($decoded->employee_id);
            
            if (!$employee || $employee['status'] !== 'active') {
                return $response->setJSON([
                    'error' => 'Invalid user',
                    'message' => 'Usuário inválido ou inativo'
                ])->setStatusCode(401);
            }
            
            $restaurantModel = new \App\Models\Restaurant\RestaurantModel();
            $restaurant = $restaurantModel->find($decoded->restaurant_id);
            
            if (!$restaurant || $restaurant['status'] !== 'active') {
                return $response->setJSON([
                    'error' => 'Invalid restaurant',
                    'message' => 'Restaurante inválido ou inativo'
                ])->setStatusCode(401);
            }
            
            // Check subscription validity
            if ($restaurant['subscription_expires_at'] && strtotime($restaurant['subscription_expires_at']) < time()) {
                return $response->setJSON([
                    'error' => 'Subscription expired',
                    'message' => 'Assinatura expirada'
                ])->setStatusCode(402); // Payment Required
            }
            
            // Store user data in request for use in controllers
            $request->user_data = [
                'employee_id' => $decoded->employee_id,
                'restaurant_id' => $decoded->restaurant_id,
                'username' => $decoded->username,
                'role' => $decoded->role,
                'permissions' => $decoded->permissions ?? []
            ];
            
        } catch (\Exception $e) {
            return $response->setJSON([
                'error' => 'Invalid token',
                'message' => 'Token inválido: ' . $e->getMessage()
            ])->setStatusCode(401);
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add CORS headers for API responses
        $response->setHeader('Access-Control-Allow-Origin', '*')
                 ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                 ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }
}