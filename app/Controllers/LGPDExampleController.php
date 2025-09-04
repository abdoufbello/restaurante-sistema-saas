<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\LGPD\ConsentService;
use App\Services\LGPD\PrivacyPolicyService;
use App\Services\LGPD\DataProtectionService;
use App\Services\LGPD\AuditService;

/**
 * LGPD Example Controller
 * 
 * Controller para demonstração das funcionalidades do sistema LGPD
 * Inclui páginas de exemplo, política de privacidade e termos de uso
 */
class LGPDExampleController extends BaseController
{
    protected $consentService;
    protected $privacyPolicyService;
    protected $dataProtectionService;
    protected $auditService;
    
    public function __construct()
    {
        $this->consentService = new ConsentService();
        $this->privacyPolicyService = new PrivacyPolicyService();
        $this->dataProtectionService = new DataProtectionService();
        $this->auditService = new AuditService();
    }
    
    /**
     * Página de demonstração do sistema LGPD
     */
    public function index()
    {
        $data = [
            'title' => 'Sistema LGPD - Demonstração',
            'description' => 'Demonstração completa do sistema de compliance LGPD',
            'keywords' => 'LGPD, privacidade, consentimento, cookies, proteção de dados'
        ];
        
        return view('templates/lgpd_example', $data);
    }
    
    /**
     * Página de política de privacidade
     */
    public function privacyPolicy()
    {
        try {
            $policy = $this->privacyPolicyService->getCurrentPolicy();
            
            if (!$policy) {
                // Cria uma política padrão se não existir
                $policy = $this->createDefaultPrivacyPolicy();
            }
            
            $data = [
                'title' => 'Política de Privacidade',
                'policy' => $policy,
                'showAcceptButton' => !$this->hasUserAcceptedPolicy($policy['id'])
            ];
            
            return view('lgpd/privacy_policy', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao carregar política de privacidade: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Página de termos de uso
     */
    public function termsOfService()
    {
        try {
            $terms = $this->privacyPolicyService->getCurrentPolicy('terms_of_service');
            
            if (!$terms) {
                $terms = $this->createDefaultTermsOfService();
            }
            
            $data = [
                'title' => 'Termos de Uso',
                'terms' => $terms,
                'showAcceptButton' => !$this->hasUserAcceptedPolicy($terms['id'])
            ];
            
            return view('lgpd/terms_of_service', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao carregar termos de uso: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Página de direitos do titular
     */
    public function dataRights()
    {
        $data = [
            'title' => 'Seus Direitos sob a LGPD',
            'description' => 'Conheça e exerça seus direitos como titular de dados pessoais'
        ];
        
        return view('lgpd/data_rights', $data);
    }
    
    /**
     * Página de configurações de privacidade
     */
    public function privacySettings()
    {
        $dataSubject = $this->getDataSubject();
        
        if (!$dataSubject) {
            return redirect()->to('/login')->with('error', 'Você precisa estar logado para acessar as configurações de privacidade.');
        }
        
        try {
            $consents = $this->consentService->getConsentHistory($dataSubject);
            $currentConsent = $this->consentService->getConsent($dataSubject, 'cookies');
            
            $data = [
                'title' => 'Configurações de Privacidade',
                'consents' => $consents,
                'currentConsent' => $currentConsent,
                'dataSubject' => $dataSubject
            ];
            
            return view('lgpd/privacy_settings', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao carregar configurações de privacidade: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao carregar configurações de privacidade.');
        }
    }
    
    /**
     * Aceitar política de privacidade
     */
    public function acceptPolicy()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Requisição inválida'
            ]);
        }
        
        $policyId = $this->request->getPost('policy_id');
        $dataSubject = $this->getDataSubject();
        
        if (!$policyId || !$dataSubject) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Dados obrigatórios não fornecidos'
            ]);
        }
        
        try {
            $result = $this->privacyPolicyService->recordAcceptance(
                $policyId,
                $dataSubject,
                $this->request->getIPAddress(),
                $this->request->getUserAgent()
            );
            
            if ($result) {
                // Registra no log de auditoria
                $this->auditService->logDataAccess(
                    $dataSubject,
                    'policy_acceptance',
                    'Aceite de política de privacidade',
                    ['policy_id' => $policyId]
                );
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Política aceita com sucesso'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Erro ao registrar aceite da política'
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao aceitar política: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * Dashboard de compliance LGPD (admin)
     */
    public function complianceDashboard()
    {
        // Verificar se é administrador
        if (!$this->isAdmin()) {
            return redirect()->to('/')->with('error', 'Acesso negado.');
        }
        
        try {
            $stats = [
                'total_consents' => $this->consentService->getTotalConsents(),
                'active_consents' => $this->consentService->getActiveConsents(),
                'expired_consents' => $this->consentService->getExpiredConsents(),
                'recent_activities' => $this->auditService->getRecentActivities(50),
                'consent_by_type' => $this->consentService->getConsentsByType(),
                'monthly_stats' => $this->getMonthlyStats()
            ];
            
            $data = [
                'title' => 'Dashboard de Compliance LGPD',
                'stats' => $stats
            ];
            
            return view('admin/lgpd_dashboard', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao carregar dashboard LGPD: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao carregar dashboard.');
        }
    }
    
    /**
     * Relatório de auditoria LGPD
     */
    public function auditReport()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/')->with('error', 'Acesso negado.');
        }
        
        $startDate = $this->request->getGet('start_date') ?? date('Y-m-01');
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-t');
        $type = $this->request->getGet('type') ?? 'all';
        
        try {
            $report = $this->auditService->generateReport($startDate, $endDate, $type);
            
            $data = [
                'title' => 'Relatório de Auditoria LGPD',
                'report' => $report,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'type' => $type
                ]
            ];
            
            return view('admin/lgpd_audit_report', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao gerar relatório de auditoria: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao gerar relatório.');
        }
    }
    
    /**
     * Cria política de privacidade padrão
     */
    private function createDefaultPrivacyPolicy()
    {
        $content = $this->getDefaultPrivacyPolicyContent();
        
        return $this->privacyPolicyService->createPolicy(
            'Política de Privacidade',
            $content,
            'privacy_policy',
            date('Y-m-d'),
            '1.0'
        );
    }
    
    /**
     * Cria termos de uso padrão
     */
    private function createDefaultTermsOfService()
    {
        $content = $this->getDefaultTermsOfServiceContent();
        
        return $this->privacyPolicyService->createPolicy(
            'Termos de Uso',
            $content,
            'terms_of_service',
            date('Y-m-d'),
            '1.0'
        );
    }
    
    /**
     * Verifica se o usuário aceitou a política
     */
    private function hasUserAcceptedPolicy($policyId)
    {
        $dataSubject = $this->getDataSubject();
        if (!$dataSubject) return false;
        
        return $this->privacyPolicyService->hasAccepted($policyId, $dataSubject);
    }
    
    /**
     * Obtém o identificador do titular dos dados
     */
    private function getDataSubject()
    {
        // Se o usuário estiver logado, usar o email
        if (session()->has('user_email')) {
            return session('user_email');
        }
        
        // Para visitantes anônimos, usar ID da sessão
        return 'session_' . session()->session_id;
    }
    
    /**
     * Verifica se o usuário é administrador
     */
    private function isAdmin()
    {
        return session()->has('user_role') && session('user_role') === 'admin';
    }
    
    /**
     * Obtém estatísticas mensais
     */
    private function getMonthlyStats()
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $months[] = [
                'month' => $date,
                'consents' => $this->consentService->getConsentsByMonth($date),
                'activities' => $this->auditService->getActivitiesByMonth($date)
            ];
        }
        
        return $months;
    }
    
    /**
     * Conteúdo padrão da política de privacidade
     */
    private function getDefaultPrivacyPolicyContent()
    {
        return '
        <h2>1. Informações Gerais</h2>
        <p>Esta Política de Privacidade descreve como coletamos, usamos, armazenamos e protegemos suas informações pessoais em conformidade com a Lei Geral de Proteção de Dados Pessoais (LGPD - Lei nº 13.709/2018).</p>
        
        <h2>2. Dados Coletados</h2>
        <p>Coletamos os seguintes tipos de dados pessoais:</p>
        <ul>
            <li><strong>Dados de identificação:</strong> nome, e-mail, telefone</li>
            <li><strong>Dados de navegação:</strong> cookies, logs de acesso, preferências</li>
            <li><strong>Dados de localização:</strong> endereço para entrega</li>
            <li><strong>Dados de pagamento:</strong> informações de cartão (tokenizadas)</li>
        </ul>
        
        <h2>3. Finalidades do Tratamento</h2>
        <p>Utilizamos seus dados pessoais para:</p>
        <ul>
            <li>Processar pedidos e entregas</li>
            <li>Melhorar nossos serviços</li>
            <li>Comunicação sobre produtos e promoções</li>
            <li>Cumprimento de obrigações legais</li>
        </ul>
        
        <h2>4. Base Legal</h2>
        <p>O tratamento de seus dados pessoais é baseado em:</p>
        <ul>
            <li>Consentimento do titular</li>
            <li>Execução de contrato</li>
            <li>Cumprimento de obrigação legal</li>
            <li>Legítimo interesse</li>
        </ul>
        
        <h2>5. Compartilhamento de Dados</h2>
        <p>Seus dados podem ser compartilhados com:</p>
        <ul>
            <li>Parceiros de entrega</li>
            <li>Processadores de pagamento</li>
            <li>Autoridades competentes quando exigido por lei</li>
        </ul>
        
        <h2>6. Seus Direitos</h2>
        <p>Você tem direito a:</p>
        <ul>
            <li>Confirmação da existência de tratamento</li>
            <li>Acesso aos dados</li>
            <li>Correção de dados incompletos ou inexatos</li>
            <li>Anonimização, bloqueio ou eliminação</li>
            <li>Portabilidade dos dados</li>
            <li>Eliminação dos dados tratados com consentimento</li>
            <li>Revogação do consentimento</li>
        </ul>
        
        <h2>7. Segurança</h2>
        <p>Implementamos medidas técnicas e organizacionais adequadas para proteger seus dados pessoais contra acesso não autorizado, alteração, divulgação ou destruição.</p>
        
        <h2>8. Retenção de Dados</h2>
        <p>Mantemos seus dados pessoais apenas pelo tempo necessário para cumprir as finalidades descritas nesta política ou conforme exigido por lei.</p>
        
        <h2>9. Contato</h2>
        <p>Para exercer seus direitos ou esclarecer dúvidas sobre esta política, entre em contato conosco:</p>
        <ul>
            <li>E-mail: privacidade@empresa.com</li>
            <li>Telefone: (11) 1234-5678</li>
        </ul>
        
        <h2>10. Alterações</h2>
        <p>Esta política pode ser atualizada periodicamente. Notificaremos sobre mudanças significativas através dos nossos canais de comunicação.</p>
        ';
    }
    
    /**
     * Conteúdo padrão dos termos de uso
     */
    private function getDefaultTermsOfServiceContent()
    {
        return '
        <h2>1. Aceitação dos Termos</h2>
        <p>Ao utilizar nossos serviços, você concorda com estes Termos de Uso. Se não concordar, não utilize nossos serviços.</p>
        
        <h2>2. Descrição do Serviço</h2>
        <p>Oferecemos uma plataforma de delivery de alimentos que conecta usuários a restaurantes parceiros.</p>
        
        <h2>3. Cadastro e Conta</h2>
        <p>Para utilizar nossos serviços, você deve:</p>
        <ul>
            <li>Fornecer informações verdadeiras e atualizadas</li>
            <li>Manter a segurança de sua conta</li>
            <li>Ser responsável por todas as atividades em sua conta</li>
        </ul>
        
        <h2>4. Uso Permitido</h2>
        <p>Você pode usar nossos serviços apenas para:</p>
        <ul>
            <li>Fazer pedidos de alimentos</li>
            <li>Acompanhar entregas</li>
            <li>Avaliar produtos e serviços</li>
        </ul>
        
        <h2>5. Uso Proibido</h2>
        <p>É proibido:</p>
        <ul>
            <li>Usar a plataforma para atividades ilegais</li>
            <li>Interferir no funcionamento do sistema</li>
            <li>Criar contas falsas</li>
            <li>Violar direitos de terceiros</li>
        </ul>
        
        <h2>6. Pedidos e Pagamentos</h2>
        <p>Ao fazer um pedido, você:</p>
        <ul>
            <li>Confirma que as informações são corretas</li>
            <li>Autoriza a cobrança do valor total</li>
            <li>Aceita os prazos de entrega estimados</li>
        </ul>
        
        <h2>7. Cancelamentos e Reembolsos</h2>
        <p>Cancelamentos e reembolsos seguem nossa política específica, disponível na plataforma.</p>
        
        <h2>8. Responsabilidades</h2>
        <p>Não nos responsabilizamos por:</p>
        <ul>
            <li>Qualidade dos alimentos (responsabilidade do restaurante)</li>
            <li>Atrasos devido a fatores externos</li>
            <li>Problemas de conectividade do usuário</li>
        </ul>
        
        <h2>9. Propriedade Intelectual</h2>
        <p>Todo o conteúdo da plataforma é protegido por direitos autorais e outras leis de propriedade intelectual.</p>
        
        <h2>10. Modificações</h2>
        <p>Podemos modificar estes termos a qualquer tempo. Continuando a usar nossos serviços após as modificações, você aceita os novos termos.</p>
        
        <h2>11. Rescisão</h2>
        <p>Podemos suspender ou encerrar sua conta a qualquer momento por violação destes termos.</p>
        
        <h2>12. Lei Aplicável</h2>
        <p>Estes termos são regidos pelas leis brasileiras, com foro na comarca de São Paulo/SP.</p>
        ';
    }
}