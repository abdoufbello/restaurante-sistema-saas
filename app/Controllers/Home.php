<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Home extends BaseController
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * Landing Page Principal
     */
    public function index()
    {
        // Se estiver em produção, mostrar landing page
        if (ENVIRONMENT === 'production') {
            return $this->landing();
        }
        
        // Em desenvolvimento, redirecionar para login
        return redirect()->to('/auth/login');
    }

    /**
     * Landing Page para Captação de Leads
     */
    public function landing()
    {
        $data = [
            'title' => 'Revolucione Seu Restaurante com IA | Sistema Completo de Gestão',
            'description' => 'Sistema completo de gestão para restaurantes com IA, WhatsApp automático, controle de estoque inteligente e integração com delivery. Teste grátis por 30 dias!',
            'keywords' => 'sistema restaurante, controle estoque, whatsapp delivery, gestão restaurante, pdv, automação'
        ];
        
        return view('landing/index', $data);
    }

    /**
     * Demo do Sistema
     */
    public function demo()
    {
        // Criar sessão demo
        session()->set([
            'demo_mode' => true,
            'demo_user' => [
                'id' => 999,
                'name' => 'Demo User',
                'email' => 'demo@restaurante.com',
                'role' => 'admin'
            ]
        ]);
        
        return redirect()->to('/dashboard?demo=true');
    }

    /**
     * Capturar Lead do Formulário
     */
    public function capturarLead()
    {
        // Validar dados
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nome' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email',
            'telefone' => 'required|min_length[10]|max_length[15]',
            'restaurante' => 'required|min_length[2]|max_length[100]',
            'cidade' => 'required|min_length[2]|max_length[50]',
            'interesse' => 'required|in_list[estoque,whatsapp,delivery,relatorios,completo]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Por favor, preencha todos os campos corretamente.',
                'errors' => $validation->getErrors()
            ]);
        }

        $data = $this->request->getPost();
        
        try {
            // Salvar lead no banco (implementar modelo Lead)
            log_message('info', 'Novo lead capturado: ' . $data['email']);

            // Enviar notificações
            $this->enviarNotificacaoLead($data);
            $this->enviarWhatsAppLead($data);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Obrigado! Você receberá o acesso ao sistema em seu email em até 5 minutos.',
                'redirect' => '/obrigado'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Erro ao capturar lead: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ops! Ocorreu um erro. Tente novamente ou entre em contato conosco.'
            ]);
        }
    }

    /**
     * Página de Obrigado
     */
    public function obrigado()
    {
        $data = [
            'title' => 'Obrigado! Acesso Enviado | Sistema Restaurante'
        ];
        
        return view('landing/obrigado', $data);
    }

    /**
     * Enviar notificação interna sobre novo lead
     */
    private function enviarNotificacaoLead($data)
    {
        $email = \Config\Services::email();
        
        $message = "
        🎯 NOVO LEAD CAPTURADO!
        
        👤 Nome: {$data['nome']}
        📧 Email: {$data['email']}
        📱 Telefone: {$data['telefone']}
        🏪 Restaurante: {$data['restaurante']}
        🌍 Cidade: {$data['cidade']}
        💡 Interesse: {$data['interesse']}
        
        ⚡ Responda em até 1 hora para maximizar conversão!
        ";
        
        $email->setTo('admin@seu-dominio.com');
        $email->setSubject('🚨 Novo Lead: ' . $data['restaurante']);
        $email->setMessage($message);
        
        try {
            $email->send();
        } catch (\Exception $e) {
            log_message('error', 'Erro ao enviar notificação de lead: ' . $e->getMessage());
        }
    }

    /**
     * Enviar WhatsApp automático para o lead
     */
    private function enviarWhatsAppLead($data)
    {
        // Implementar integração WhatsApp Business API
        $telefone = preg_replace('/[^0-9]/', '', $data['telefone']);
        
        $mensagem = "Olá {$data['nome']}! 👋\n\n";
        $mensagem .= "Obrigado pelo interesse no nosso sistema para o *{$data['restaurante']}*!\n\n";
        $mensagem .= "🎯 Seu acesso ao sistema já foi enviado para: {$data['email']}\n\n";
        $mensagem .= "📱 Em breve nossa equipe entrará em contato para uma demonstração personalizada.\n\n";
        $mensagem .= "🚀 *Teste GRÁTIS por 30 dias* - Sem compromisso!\n\n";
        $mensagem .= "Dúvidas? Responda esta mensagem que te ajudamos! 😊";
        
        // Log da mensagem (implementar envio real)
        log_message('info', 'WhatsApp para ' . $telefone . ': ' . $mensagem);
    }
}
