<?php

namespace App\Services\PaymentServices;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use CodeIgniter\HTTP\CURLRequest;
use Exception;

class PixService
{
    protected $gatewayModel;
    protected $transactionModel;
    protected $webhookModel;
    protected $client;
    
    public function __construct()
    {
        $this->gatewayModel = new PaymentGateway();
        $this->transactionModel = new PaymentTransaction();
        $this->webhookModel = new PaymentWebhook();
        $this->client = \Config\Services::curlrequest();
    }

    /**
     * Gerar QR Code PIX
     */
    public function generatePixQRCode($restaurantId, $amount, $description, $orderId = null)
    {
        try {
            // Buscar configuração PIX do restaurante
            $gateway = $this->gatewayModel->getActiveByType($restaurantId, 'pix');
            
            if (!$gateway) {
                throw new Exception('Gateway PIX não configurado para este restaurante');
            }

            $credentials = json_decode($gateway['credentials'], true);
            
            // Gerar chave PIX única
            $pixKey = $this->generatePixKey();
            
            // Criar transação
            $transactionData = [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'gateway_id' => $gateway['id'],
                'gateway_type' => 'pix',
                'transaction_id' => $pixKey,
                'amount' => $amount,
                'currency' => 'BRL',
                'status' => 'pending',
                'payment_method' => 'pix',
                'gateway_response' => json_encode([
                    'pix_key' => $pixKey,
                    'description' => $description,
                    'generated_at' => date('Y-m-d H:i:s')
                ])
            ];
            
            $transactionId = $this->transactionModel->insert($transactionData);
            
            // Gerar payload PIX
            $pixPayload = $this->generatePixPayload(
                $credentials['pix_key'],
                $amount,
                $description,
                $pixKey
            );
            
            // Gerar QR Code
            $qrCodeData = $this->generateQRCodeImage($pixPayload);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'pix_key' => $pixKey,
                'qr_code' => $qrCodeData,
                'pix_payload' => $pixPayload,
                'amount' => $amount,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'instructions' => 'Escaneie o QR Code com seu app bancário ou copie e cole o código PIX'
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao gerar PIX QR Code: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar status do pagamento PIX
     */
    public function checkPixPayment($transactionId)
    {
        try {
            $transaction = $this->transactionModel->find($transactionId);
            
            if (!$transaction) {
                throw new Exception('Transação não encontrada');
            }

            // Em um cenário real, aqui consultaríamos a API do banco
            // Por enquanto, simularemos a verificação
            $gatewayResponse = json_decode($transaction['gateway_response'], true);
            $pixKey = $gatewayResponse['pix_key'];
            
            // Simular consulta ao banco (em produção, usar API real)
            $paymentStatus = $this->simulatePixPaymentCheck($pixKey);
            
            if ($paymentStatus['paid']) {
                // Atualizar status da transação
                $this->transactionModel->update($transactionId, [
                    'status' => 'completed',
                    'processed_at' => date('Y-m-d H:i:s'),
                    'gateway_response' => json_encode(array_merge(
                        $gatewayResponse,
                        $paymentStatus
                    ))
                ]);
                
                return [
                    'success' => true,
                    'status' => 'completed',
                    'paid' => true,
                    'paid_at' => $paymentStatus['paid_at'],
                    'amount' => $transaction['amount']
                ];
            }
            
            return [
                'success' => true,
                'status' => $transaction['status'],
                'paid' => false,
                'amount' => $transaction['amount']
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao verificar pagamento PIX: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processar webhook PIX
     */
    public function processPixWebhook($restaurantId, $payload, $headers = [])
    {
        try {
            // Salvar webhook
            $webhookData = [
                'restaurant_id' => $restaurantId,
                'gateway_type' => 'pix',
                'event_type' => $payload['event_type'] ?? 'payment_update',
                'payload' => json_encode($payload),
                'headers' => json_encode($headers),
                'transaction_id' => $payload['transaction_id'] ?? null
            ];
            
            $webhookId = $this->webhookModel->insert($webhookData);
            
            // Processar evento
            $result = $this->processPixEvent($payload);
            
            // Marcar webhook como processado
            $this->webhookModel->markAsProcessed(
                $webhookId,
                $result['success'] ? null : $result['error']
            );
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao processar webhook PIX: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Gerar chave PIX única
     */
    private function generatePixKey()
    {
        return 'PIX_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Gerar payload PIX (EMV)
     */
    private function generatePixPayload($pixKey, $amount, $description, $transactionId)
    {
        // Implementação simplificada do payload PIX EMV
        // Em produção, usar biblioteca específica para PIX
        
        $payload = "00020126";
        $payload .= "580014BR.GOV.BCB.PIX";
        $payload .= "01" . sprintf("%02d", strlen($pixKey)) . $pixKey;
        $payload .= "520400005303986";
        $payload .= "54" . sprintf("%02d", strlen($amount)) . $amount;
        $payload .= "5802BR";
        $payload .= "59" . sprintf("%02d", strlen($description)) . $description;
        $payload .= "60" . sprintf("%02d", strlen('SAO PAULO')) . 'SAO PAULO';
        $payload .= "62" . sprintf("%02d", strlen($transactionId) + 4) . "05" . sprintf("%02d", strlen($transactionId)) . $transactionId;
        
        // Calcular CRC16
        $crc = $this->calculateCRC16($payload . "6304");
        $payload .= "6304" . strtoupper(dechex($crc));
        
        return $payload;
    }

    /**
     * Calcular CRC16 para PIX
     */
    private function calculateCRC16($data)
    {
        $crc = 0xFFFF;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
        }
        
        return $crc & 0xFFFF;
    }

    /**
     * Gerar imagem QR Code
     */
    private function generateQRCodeImage($payload)
    {
        // Em produção, usar biblioteca como endroid/qr-code
        // Por enquanto, retornar URL de serviço online
        
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($payload);
        
        return [
            'url' => $qrCodeUrl,
            'base64' => null, // Implementar se necessário
            'payload' => $payload
        ];
    }

    /**
     * Simular verificação de pagamento PIX
     */
    private function simulatePixPaymentCheck($pixKey)
    {
        // Simular 30% de chance de pagamento aprovado
        $isPaid = (rand(1, 100) <= 30);
        
        if ($isPaid) {
            return [
                'paid' => true,
                'paid_at' => date('Y-m-d H:i:s'),
                'bank_reference' => 'BANK_' . strtoupper(uniqid()),
                'end_to_end_id' => 'E' . date('Ymd') . strtoupper(uniqid())
            ];
        }
        
        return [
            'paid' => false,
            'status' => 'pending'
        ];
    }

    /**
     * Processar evento PIX
     */
    private function processPixEvent($payload)
    {
        try {
            $eventType = $payload['event_type'] ?? 'unknown';
            $transactionId = $payload['transaction_id'] ?? null;
            
            if (!$transactionId) {
                throw new Exception('ID da transação não fornecido');
            }
            
            switch ($eventType) {
                case 'payment_completed':
                    return $this->handlePixPaymentCompleted($payload);
                    
                case 'payment_failed':
                    return $this->handlePixPaymentFailed($payload);
                    
                case 'payment_expired':
                    return $this->handlePixPaymentExpired($payload);
                    
                default:
                    log_message('warning', 'Evento PIX não reconhecido: ' . $eventType);
                    return ['success' => true, 'message' => 'Evento ignorado'];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processar pagamento PIX completado
     */
    private function handlePixPaymentCompleted($payload)
    {
        $transactionId = $payload['transaction_id'];
        
        $updateData = [
            'status' => 'completed',
            'processed_at' => date('Y-m-d H:i:s'),
            'gateway_response' => json_encode($payload)
        ];
        
        $this->transactionModel->update($transactionId, $updateData);
        
        return [
            'success' => true,
            'message' => 'Pagamento PIX confirmado'
        ];
    }

    /**
     * Processar pagamento PIX falhado
     */
    private function handlePixPaymentFailed($payload)
    {
        $transactionId = $payload['transaction_id'];
        
        $updateData = [
            'status' => 'failed',
            'processed_at' => date('Y-m-d H:i:s'),
            'gateway_response' => json_encode($payload)
        ];
        
        $this->transactionModel->update($transactionId, $updateData);
        
        return [
            'success' => true,
            'message' => 'Pagamento PIX falhou'
        ];
    }

    /**
     * Processar pagamento PIX expirado
     */
    private function handlePixPaymentExpired($payload)
    {
        $transactionId = $payload['transaction_id'];
        
        $updateData = [
            'status' => 'cancelled',
            'processed_at' => date('Y-m-d H:i:s'),
            'gateway_response' => json_encode($payload)
        ];
        
        $this->transactionModel->update($transactionId, $updateData);
        
        return [
            'success' => true,
            'message' => 'Pagamento PIX expirado'
        ];
    }
}