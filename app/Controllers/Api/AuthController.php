<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\UserModel;
use App\Models\JWTAuthModel;

/**
 * Controlador de Autenticação para APIs RESTful
 */
class AuthController extends BaseApiController
{
    protected $userModel;
    
    public function __construct()
    {
        // Não chamar parent::__construct() para evitar autenticação automática
        $this->format = 'json';
        $this->jwtAuth = new JWTAuthModel();
        $this->userModel = new UserModel();
        
        // Configurar CORS
        $this->setCorsHeaders();
    }
    
    /**
     * Endpoints de autenticação não requerem token
     */
    protected function requiresAuth(): bool
    {
        $publicEndpoints = ['login', 'register', 'forgotPassword', 'resetPassword'];
        $currentMethod = $this->request->getUri()->getSegment(3);
        
        return !in_array($currentMethod, $publicEndpoints);
    }
    
    /**
     * Login do usuário
     * POST /api/auth/login
     */
    public function login()
    {
        try {
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'email' => 'required|valid_email',
                'password' => 'required|min_length[6]',
                'device_name' => 'permit_empty|string|max_length[255]',
                'device_type' => 'permit_empty|in_list[web,mobile,desktop,tablet,api]',
                'remember_me' => 'permit_empty|boolean'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar usuário por email
            $user = $this->userModel->where('email', $validatedData['email'])
                                   ->where('deleted_at', null)
                                   ->first();
            
            if (!$user) {
                return $this->failUnauthorized('Credenciais inválidas');
            }
            
            // Verificar senha
            if (!password_verify($validatedData['password'], $user['password'])) {
                return $this->failUnauthorized('Credenciais inválidas');
            }
            
            // Verificar se usuário está ativo
            if ($user['status'] !== 'active') {
                return $this->failUnauthorized('Conta inativa ou suspensa');
            }
            
            // Verificar se restaurante está ativo
            if ($user['restaurant_id'] && !$this->isRestaurantActive($user['restaurant_id'])) {
                return $this->failUnauthorized('Restaurante inativo ou suspenso');
            }
            
            // Preparar dados do usuário para o token
            $userData = [
                'id' => $user['id'],
                'restaurant_id' => $user['restaurant_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'roles' => json_decode($user['roles'] ?? '[]', true),
                'permissions' => json_decode($user['permissions'] ?? '[]', true)
            ];
            
            // Opções do token
            $tokenOptions = [
                'device_id' => $input['device_id'] ?? uniqid('device_'),
                'device_name' => $validatedData['device_name'] ?? 'Unknown Device',
                'device_type' => $validatedData['device_type'] ?? 'web',
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()->getAgentString(),
                'scopes' => ['read', 'write'],
                'metadata' => [
                    'login_time' => date('Y-m-d H:i:s'),
                    'remember_me' => $validatedData['remember_me'] ?? false
                ]
            ];
            
            // Gerar tokens
            $tokens = $this->jwtAuth->generateTokenPair($userData, $tokenOptions);
            
            // Atualizar último login do usuário
            $this->userModel->update($user['id'], [
                'last_login' => date('Y-m-d H:i:s'),
                'login_count' => ($user['login_count'] ?? 0) + 1
            ]);
            
            // Log da atividade
            $this->logActivity('user_login', [
                'user_id' => $user['id'],
                'device_type' => $tokenOptions['device_type']
            ]);
            
            // Preparar resposta
            $response = [
                'user' => $this->sanitizeOutput($user, ['password']),
                'tokens' => $tokens,
                'expires_in' => $tokens['expires_in'],
                'token_type' => 'Bearer'
            ];
            
            return $this->respondSuccess($response, 'Login realizado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Registro de novo usuário
     * POST /api/auth/register
     */
    public function register()
    {
        try {
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'restaurant_id' => 'required|integer',
                'username' => 'required|string|min_length[3]|max_length[50]|is_unique[users.username]',
                'email' => 'required|valid_email|is_unique[users.email]',
                'password' => 'required|min_length[8]',
                'password_confirm' => 'required|matches[password]',
                'first_name' => 'required|string|max_length[100]',
                'last_name' => 'required|string|max_length[100]',
                'phone' => 'permit_empty|string|max_length[20]',
                'role' => 'permit_empty|in_list[admin,manager,employee,customer]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Verificar se restaurante existe e está ativo
            if (!$this->isRestaurantActive($validatedData['restaurant_id'])) {
                return $this->failValidationErrors(['restaurant_id' => 'Restaurante inválido ou inativo']);
            }
            
            // Preparar dados do usuário
            $userData = [
                'restaurant_id' => $validatedData['restaurant_id'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'password' => password_hash($validatedData['password'], PASSWORD_DEFAULT),
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'phone' => $validatedData['phone'] ?? null,
                'role' => $validatedData['role'] ?? 'customer',
                'status' => 'active',
                'email_verified' => false,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Criar usuário
            $userId = $this->userModel->insert($userData);
            
            if (!$userId) {
                return $this->failServerError('Erro ao criar usuário');
            }
            
            // Buscar usuário criado
            $user = $this->userModel->find($userId);
            
            // Log da atividade
            $this->logActivity('user_register', [
                'user_id' => $userId,
                'email' => $user['email']
            ]);
            
            // Preparar resposta (sem senha)
            $response = $this->sanitizeOutput($user, ['password']);
            
            return $this->respondSuccess($response, 'Usuário criado com sucesso', 201);
            
        } catch (\Exception $e) {
            log_message('error', 'Register error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Refresh do token de acesso
     * POST /api/auth/refresh
     */
    public function refresh()
    {
        try {
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'refresh_token' => 'required|string'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Renovar token
            $newTokens = $this->jwtAuth->refreshToken($validatedData['refresh_token']);
            
            if (!$newTokens) {
                return $this->failUnauthorized('Refresh token inválido ou expirado');
            }
            
            return $this->respondSuccess($newTokens, 'Token renovado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Refresh token error: ' . $e->getMessage());
            return $this->failUnauthorized('Refresh token inválido');
        }
    }
    
    /**
     * Logout do usuário
     * POST /api/auth/logout
     */
    public function logout()
    {
        try {
            // Revogar token atual
            if ($this->currentToken) {
                $this->jwtAuth->revokeToken($this->currentToken, $this->currentUser['user_id'], 'User logout');
            }
            
            // Log da atividade
            $this->logActivity('user_logout', [
                'user_id' => $this->currentUser['user_id']
            ]);
            
            return $this->respondSuccess(null, 'Logout realizado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Logout error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Logout de todos os dispositivos
     * POST /api/auth/logout-all
     */
    public function logoutAll()
    {
        try {
            // Revogar todos os tokens do usuário
            $this->jwtAuth->revokeUserTokens(
                $this->currentUser['user_id'],
                $this->currentUser['user_id'],
                'Logout from all devices'
            );
            
            // Log da atividade
            $this->logActivity('user_logout_all', [
                'user_id' => $this->currentUser['user_id']
            ]);
            
            return $this->respondSuccess(null, 'Logout realizado em todos os dispositivos');
            
        } catch (\Exception $e) {
            log_message('error', 'Logout all error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Informações do usuário atual
     * GET /api/auth/me
     */
    public function me()
    {
        try {
            // Buscar dados atualizados do usuário
            $user = $this->userModel->find($this->currentUser['user_id']);
            
            if (!$user) {
                return $this->failNotFound('Usuário não encontrado');
            }
            
            // Buscar tokens ativos
            $activeTokens = $this->jwtAuth->getUserActiveTokens($this->currentUser['user_id']);
            
            $response = [
                'user' => $this->sanitizeOutput($user, ['password']),
                'active_sessions' => count($activeTokens),
                'current_token_info' => [
                    'device_type' => $this->currentUser['device_type'] ?? 'unknown',
                    'ip_address' => $this->currentUser['ip_address'] ?? 'unknown',
                    'expires_at' => date('Y-m-d H:i:s', $this->currentUser['exp'])
                ]
            ];
            
            return $this->respondSuccess($response);
            
        } catch (\Exception $e) {
            log_message('error', 'Me endpoint error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Atualizar perfil do usuário
     * PUT /api/auth/profile
     */
    public function updateProfile()
    {
        try {
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'first_name' => 'permit_empty|string|max_length[100]',
                'last_name' => 'permit_empty|string|max_length[100]',
                'phone' => 'permit_empty|string|max_length[20]',
                'avatar' => 'permit_empty|string|max_length[255]',
                'preferences' => 'permit_empty|array'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Atualizar usuário
            $updated = $this->userModel->update($this->currentUser['user_id'], $validatedData);
            
            if (!$updated) {
                return $this->failServerError('Erro ao atualizar perfil');
            }
            
            // Buscar dados atualizados
            $user = $this->userModel->find($this->currentUser['user_id']);
            
            // Log da atividade
            $this->logActivity('profile_update', [
                'user_id' => $this->currentUser['user_id'],
                'fields_updated' => array_keys($validatedData)
            ]);
            
            return $this->respondSuccess(
                $this->sanitizeOutput($user, ['password']),
                'Perfil atualizado com sucesso'
            );
            
        } catch (\Exception $e) {
            log_message('error', 'Update profile error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Alterar senha
     * PUT /api/auth/change-password
     */
    public function changePassword()
    {
        try {
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'current_password' => 'required|string',
                'new_password' => 'required|min_length[8]',
                'new_password_confirm' => 'required|matches[new_password]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar usuário atual
            $user = $this->userModel->find($this->currentUser['user_id']);
            
            if (!$user) {
                return $this->failNotFound('Usuário não encontrado');
            }
            
            // Verificar senha atual
            if (!password_verify($validatedData['current_password'], $user['password'])) {
                return $this->failValidationErrors(['current_password' => 'Senha atual incorreta']);
            }
            
            // Atualizar senha
            $updated = $this->userModel->update($this->currentUser['user_id'], [
                'password' => password_hash($validatedData['new_password'], PASSWORD_DEFAULT),
                'password_changed_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$updated) {
                return $this->failServerError('Erro ao alterar senha');
            }
            
            // Revogar todos os outros tokens (manter apenas o atual)
            $this->jwtAuth->revokeUserTokens(
                $this->currentUser['user_id'],
                $this->currentUser['user_id'],
                'Password changed'
            );
            
            // Log da atividade
            $this->logActivity('password_change', [
                'user_id' => $this->currentUser['user_id']
            ]);
            
            return $this->respondSuccess(null, 'Senha alterada com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Change password error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Esqueci minha senha
     * POST /api/auth/forgot-password
     */
    public function forgotPassword()
    {
        try {
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'email' => 'required|valid_email'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar usuário
            $user = $this->userModel->where('email', $validatedData['email'])
                                   ->where('deleted_at', null)
                                   ->first();
            
            if (!$user) {
                // Por segurança, sempre retornar sucesso mesmo se email não existir
                return $this->respondSuccess(null, 'Se o email existir, você receberá instruções para redefinir sua senha');
            }
            
            // Gerar token de reset
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Salvar token de reset
            $this->userModel->update($user['id'], [
                'reset_token' => hash('sha256', $resetToken),
                'reset_token_expires' => $expiresAt
            ]);
            
            // Aqui você enviaria o email com o token
            // Por enquanto, apenas log
            log_message('info', "Password reset token for {$user['email']}: {$resetToken}");
            
            // Log da atividade
            $this->logActivity('password_reset_request', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);
            
            return $this->respondSuccess(null, 'Se o email existir, você receberá instruções para redefinir sua senha');
            
        } catch (\Exception $e) {
            log_message('error', 'Forgot password error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Redefinir senha
     * POST /api/auth/reset-password
     */
    public function resetPassword()
    {
        try {
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'token' => 'required|string',
                'email' => 'required|valid_email',
                'password' => 'required|min_length[8]',
                'password_confirm' => 'required|matches[password]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar usuário com token válido
            $user = $this->userModel->where('email', $validatedData['email'])
                                   ->where('reset_token', hash('sha256', $validatedData['token']))
                                   ->where('reset_token_expires >', date('Y-m-d H:i:s'))
                                   ->where('deleted_at', null)
                                   ->first();
            
            if (!$user) {
                return $this->failValidationErrors(['token' => 'Token inválido ou expirado']);
            }
            
            // Atualizar senha e limpar token
            $updated = $this->userModel->update($user['id'], [
                'password' => password_hash($validatedData['password'], PASSWORD_DEFAULT),
                'password_changed_at' => date('Y-m-d H:i:s'),
                'reset_token' => null,
                'reset_token_expires' => null
            ]);
            
            if (!$updated) {
                return $this->failServerError('Erro ao redefinir senha');
            }
            
            // Revogar todos os tokens do usuário
            $this->jwtAuth->revokeUserTokens(
                $user['id'],
                $user['id'],
                'Password reset'
            );
            
            // Log da atividade
            $this->logActivity('password_reset', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);
            
            return $this->respondSuccess(null, 'Senha redefinida com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Reset password error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Listar sessões ativas
     * GET /api/auth/sessions
     */
    public function sessions()
    {
        try {
            $activeTokens = $this->jwtAuth->getUserActiveTokens($this->currentUser['user_id']);
            
            $sessions = array_map(function($token) {
                return [
                    'id' => $token['id'],
                    'device_name' => $token['device_name'],
                    'device_type' => $token['device_type'],
                    'ip_address' => $token['ip_address'],
                    'location' => $token['location'],
                    'created_at' => $token['created_at'],
                    'last_used_at' => $token['last_used_at'],
                    'expires_at' => $token['expires_at'],
                    'is_current' => hash('sha256', $this->currentToken) === $token['token_hash']
                ];
            }, $activeTokens);
            
            return $this->respondSuccess($sessions);
            
        } catch (\Exception $e) {
            log_message('error', 'Sessions error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Revogar sessão específica
     * DELETE /api/auth/sessions/{id}
     */
    public function revokeSession($sessionId = null)
    {
        try {
            if (!$sessionId) {
                return $this->failValidationErrors(['session_id' => 'ID da sessão é obrigatório']);
            }
            
            // Buscar token
            $token = $this->jwtAuth->find($sessionId);
            
            if (!$token || $token['user_id'] != $this->currentUser['user_id']) {
                return $this->failNotFound('Sessão não encontrada');
            }
            
            // Revogar token
            $this->jwtAuth->update($sessionId, [
                'is_revoked' => true,
                'is_active' => false,
                'revoked_at' => date('Y-m-d H:i:s'),
                'revoked_by' => $this->currentUser['user_id'],
                'revoke_reason' => 'Revoked by user'
            ]);
            
            // Log da atividade
            $this->logActivity('session_revoke', [
                'user_id' => $this->currentUser['user_id'],
                'session_id' => $sessionId
            ]);
            
            return $this->respondSuccess(null, 'Sessão revogada com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Revoke session error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
    
    /**
     * Verificar status do token
     * GET /api/auth/verify
     */
    public function verify()
    {
        try {
            $tokenInfo = [
                'valid' => true,
                'user_id' => $this->currentUser['user_id'],
                'restaurant_id' => $this->currentUser['restaurant_id'],
                'roles' => $this->currentUser['roles'],
                'permissions' => $this->currentUser['permissions'],
                'expires_at' => date('Y-m-d H:i:s', $this->currentUser['exp']),
                'issued_at' => date('Y-m-d H:i:s', $this->currentUser['iat'])
            ];
            
            return $this->respondSuccess($tokenInfo, 'Token válido');
            
        } catch (\Exception $e) {
            log_message('error', 'Verify token error: ' . $e->getMessage());
            return $this->failServerError('Erro interno do servidor');
        }
    }
}