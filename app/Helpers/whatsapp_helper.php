<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp Business Integration Helper
 * 
 * Este helper fornece funções para integração com WhatsApp Business API
 * e geração de URLs para envio de mensagens via WhatsApp Web
 */

if (!function_exists('generate_whatsapp_url')) {
    /**
     * Gera URL do WhatsApp Web para envio de mensagem
     * 
     * @param string $phone Número de telefone (com código do país)
     * @param string $message Mensagem a ser enviada
     * @return string URL do WhatsApp Web
     */
    function generate_whatsapp_url($phone, $message = '') {
        // Limpar o número de telefone (remover caracteres especiais)
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Adicionar código do país se não estiver presente
        if (strlen($clean_phone) == 11 && substr($clean_phone, 0, 1) != '5') {
            $clean_phone = '55' . $clean_phone; // Código do Brasil
        }
        
        // Codificar a mensagem para URL
        $encoded_message = urlencode($message);
        
        // Gerar URL do WhatsApp Web
        return "https://wa.me/{$clean_phone}?text={$encoded_message}";
    }
}

if (!function_exists('format_shopping_list_message')) {
    /**
     * Formata uma lista de compras para mensagem do WhatsApp
     * 
     * @param array $items Lista de itens
     * @param array $supplier_info Informações do fornecedor
     * @param array $company_info Informações da empresa
     * @return string Mensagem formatada
     */
    function format_shopping_list_message($items, $supplier_info = [], $company_info = []) {
        $message = "🛒 *LISTA DE COMPRAS*\n\n";
        
        // Informações da empresa
        if (!empty($company_info['name'])) {
            $message .= "🏢 *Empresa:* {$company_info['name']}\n";
        }
        
        // Data e hora
        $message .= "📅 *Data:* " . date('d/m/Y H:i') . "\n\n";
        
        // Informações do fornecedor
        if (!empty($supplier_info['company_name'])) {
            $message .= "👤 *Fornecedor:* {$supplier_info['company_name']}\n";
            if (!empty($supplier_info['contact_person'])) {
                $message .= "📞 *Contato:* {$supplier_info['contact_person']}\n";
            }
            $message .= "\n";
        }
        
        // Lista de itens
        $message .= "📦 *ITENS SOLICITADOS:*\n";
        $total_items = 0;
        $total_value = 0;
        
        foreach ($items as $item) {
            $message .= "\n• *{$item['name']}*\n";
            
            if (!empty($item['item_number'])) {
                $message .= "  📋 Código: {$item['item_number']}\n";
            }
            
            $message .= "  📊 Qtd solicitada: {$item['quantity']}";
            if (!empty($item['unit'])) {
                $message .= " {$item['unit']}";
            }
            $message .= "\n";
            
            if (!empty($item['current_stock'])) {
                $message .= "  📉 Estoque atual: {$item['current_stock']}";
                if (!empty($item['unit'])) {
                    $message .= " {$item['unit']}";
                }
                $message .= "\n";
            }
            
            if (!empty($item['urgency'])) {
                $urgency_emoji = [
                    'critical' => '🔴',
                    'high' => '🟠', 
                    'medium' => '🟡',
                    'low' => '🟢'
                ];
                $urgency_text = [
                    'critical' => 'CRÍTICA',
                    'high' => 'ALTA',
                    'medium' => 'MÉDIA', 
                    'low' => 'BAIXA'
                ];
                $emoji = $urgency_emoji[$item['urgency']] ?? '⚪';
                $text = $urgency_text[$item['urgency']] ?? 'NORMAL';
                $message .= "  {$emoji} Prioridade: {$text}\n";
            }
            
            if (!empty($item['estimated_cost'])) {
                $message .= "  💰 Valor estimado: R$ " . number_format($item['estimated_cost'], 2, ',', '.') . "\n";
                $total_value += $item['estimated_cost'];
            }
            
            if (!empty($item['notes'])) {
                $message .= "  📝 Obs: {$item['notes']}\n";
            }
            
            $total_items++;
        }
        
        // Resumo
        $message .= "\n" . str_repeat("─", 30) . "\n";
        $message .= "📊 *RESUMO:*\n";
        $message .= "• Total de itens: {$total_items}\n";
        
        if ($total_value > 0) {
            $message .= "• Valor estimado: R$ " . number_format($total_value, 2, ',', '.') . "\n";
        }
        
        // Instruções
        $message .= "\n📋 *INSTRUÇÕES:*\n";
        $message .= "• Favor confirmar disponibilidade\n";
        $message .= "• Informar prazo de entrega\n";
        $message .= "• Enviar orçamento detalhado\n";
        
        // Rodapé
        $message .= "\n⚡ *Gerado automaticamente pelo Sistema de Gestão de Estoque*\n";
        $message .= "🕐 " . date('d/m/Y H:i:s');
        
        return $message;
    }
}

if (!function_exists('format_restock_alert_message')) {
    /**
     * Formata um alerta de reposição para mensagem do WhatsApp
     * 
     * @param array $item Informações do item
     * @param array $supplier_info Informações do fornecedor
     * @return string Mensagem formatada
     */
    function format_restock_alert_message($item, $supplier_info = []) {
        $message = "⚠️ *ALERTA DE REPOSIÇÃO*\n\n";
        
        // Informações do produto
        $message .= "📦 *Produto:* {$item['name']}\n";
        
        if (!empty($item['item_number'])) {
            $message .= "📋 *Código:* {$item['item_number']}\n";
        }
        
        // Status do estoque
        $message .= "\n📊 *STATUS DO ESTOQUE:*\n";
        $message .= "• Estoque atual: {$item['current_stock']}";
        if (!empty($item['unit'])) {
            $message .= " {$item['unit']}";
        }
        $message .= "\n";
        
        $message .= "• Ponto de reposição: {$item['reorder_point']}";
        if (!empty($item['unit'])) {
            $message .= " {$item['unit']}";
        }
        $message .= "\n";
        
        if (!empty($item['suggested_quantity'])) {
            $message .= "• Quantidade sugerida: {$item['suggested_quantity']}";
            if (!empty($item['unit'])) {
                $message .= " {$item['unit']}";
            }
            $message .= "\n";
        }
        
        // Urgência
        if (!empty($item['urgency'])) {
            $urgency_emoji = [
                'critical' => '🔴',
                'high' => '🟠',
                'medium' => '🟡', 
                'low' => '🟢'
            ];
            $urgency_text = [
                'critical' => 'CRÍTICA - Produto esgotado!',
                'high' => 'ALTA - Reposição urgente necessária',
                'medium' => 'MÉDIA - Programar reposição',
                'low' => 'BAIXA - Monitorar estoque'
            ];
            $emoji = $urgency_emoji[$item['urgency']] ?? '⚪';
            $text = $urgency_text[$item['urgency']] ?? 'NORMAL';
            $message .= "\n{$emoji} *Prioridade:* {$text}\n";
        }
        
        // Previsão
        if (!empty($item['days_until_stockout'])) {
            if ($item['days_until_stockout'] <= 0) {
                $message .= "\n🚨 *ATENÇÃO:* Produto já esgotado!\n";
            } else {
                $message .= "\n⏰ *Previsão:* Estoque se esgotará em {$item['days_until_stockout']} dias\n";
            }
        }
        
        // Informações do fornecedor
        if (!empty($supplier_info['company_name'])) {
            $message .= "\n👤 *Fornecedor sugerido:* {$supplier_info['company_name']}\n";
        }
        
        // Rodapé
        $message .= "\n⚡ *Sistema de Gestão de Estoque*\n";
        $message .= "🕐 " . date('d/m/Y H:i:s');
        
        return $message;
    }
}

if (!function_exists('format_purchase_confirmation_message')) {
    /**
     * Formata uma confirmação de compra para mensagem do WhatsApp
     * 
     * @param array $purchase Informações da compra
     * @param array $items Itens da compra
     * @return string Mensagem formatada
     */
    function format_purchase_confirmation_message($purchase, $items) {
        $message = "✅ *CONFIRMAÇÃO DE COMPRA*\n\n";
        
        // Informações da compra
        $message .= "📋 *Pedido:* #{$purchase['reference']}\n";
        $message .= "📅 *Data:* " . date('d/m/Y H:i', strtotime($purchase['created_at'])) . "\n";
        
        if (!empty($purchase['supplier_name'])) {
            $message .= "👤 *Fornecedor:* {$purchase['supplier_name']}\n";
        }
        
        // Itens
        $message .= "\n📦 *ITENS COMPRADOS:*\n";
        $total = 0;
        
        foreach ($items as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $total += $subtotal;
            
            $message .= "\n• {$item['name']}\n";
            $message .= "  Qtd: {$item['quantity']}";
            if (!empty($item['unit'])) {
                $message .= " {$item['unit']}";
            }
            $message .= "\n";
            $message .= "  Preço unit.: R$ " . number_format($item['unit_price'], 2, ',', '.') . "\n";
            $message .= "  Subtotal: R$ " . number_format($subtotal, 2, ',', '.') . "\n";
        }
        
        // Total
        $message .= "\n" . str_repeat("─", 30) . "\n";
        $message .= "💰 *TOTAL: R$ " . number_format($total, 2, ',', '.') . "*\n";
        
        // Observações
        if (!empty($purchase['notes'])) {
            $message .= "\n📝 *Observações:*\n{$purchase['notes']}\n";
        }
        
        // Rodapé
        $message .= "\n✨ Obrigado pela parceria!\n";
        $message .= "⚡ Sistema de Gestão de Estoque";
        
        return $message;
    }
}

if (!function_exists('validate_phone_number')) {
    /**
     * Valida um número de telefone brasileiro
     * 
     * @param string $phone Número de telefone
     * @return bool True se válido, False caso contrário
     */
    function validate_phone_number($phone) {
        // Remover caracteres especiais
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Verificar se tem 10 ou 11 dígitos (sem código do país)
        // ou 12 ou 13 dígitos (com código do país 55)
        $length = strlen($clean_phone);
        
        if ($length == 10 || $length == 11) {
            // Número brasileiro sem código do país
            return true;
        } elseif ($length == 12 || $length == 13) {
            // Número brasileiro com código do país
            return substr($clean_phone, 0, 2) == '55';
        }
        
        return false;
    }
}

if (!function_exists('format_phone_number')) {
    /**
     * Formata um número de telefone para exibição
     * 
     * @param string $phone Número de telefone
     * @return string Número formatado
     */
    function format_phone_number($phone) {
        // Remover caracteres especiais
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Adicionar código do país se necessário
        if (strlen($clean_phone) == 11 && substr($clean_phone, 0, 1) != '5') {
            $clean_phone = '55' . $clean_phone;
        }
        
        // Formatar para exibição
        if (strlen($clean_phone) == 13) {
            // +55 (11) 99999-9999
            return '+' . substr($clean_phone, 0, 2) . ' (' . substr($clean_phone, 2, 2) . ') ' . 
                   substr($clean_phone, 4, 5) . '-' . substr($clean_phone, 9, 4);
        } elseif (strlen($clean_phone) == 12) {
            // +55 (11) 9999-9999
            return '+' . substr($clean_phone, 0, 2) . ' (' . substr($clean_phone, 2, 2) . ') ' . 
                   substr($clean_phone, 4, 4) . '-' . substr($clean_phone, 8, 4);
        }
        
        return $phone; // Retornar original se não conseguir formatar
    }
}

if (!function_exists('send_whatsapp_business_message')) {
    /**
     * Envia mensagem via WhatsApp Business API (implementação futura)
     * 
     * @param string $phone Número de telefone
     * @param string $message Mensagem
     * @param array $options Opções adicionais
     * @return array Resultado do envio
     */
    function send_whatsapp_business_message($phone, $message, $options = []) {
        // Esta função seria implementada quando integrar com WhatsApp Business API
        // Por enquanto, retorna apenas a URL do WhatsApp Web
        
        return [
            'success' => true,
            'method' => 'whatsapp_web',
            'url' => generate_whatsapp_url($phone, $message),
            'message' => 'URL do WhatsApp Web gerada com sucesso'
        ];
    }
}

if (!function_exists('get_whatsapp_business_config')) {
    /**
     * Obtém configurações do WhatsApp Business
     * 
     * @return array Configurações
     */
    function get_whatsapp_business_config() {
        $CI =& get_instance();
        
        // Carregar configurações do banco de dados ou arquivo de config
        return [
            'enabled' => true,
            'api_url' => '',
            'api_token' => '',
            'phone_number_id' => '',
            'business_account_id' => '',
            'webhook_verify_token' => '',
            'use_web_version' => true // Por enquanto usar WhatsApp Web
        ];
    }
}

if (!function_exists('log_whatsapp_message')) {
    /**
     * Registra log de mensagem do WhatsApp
     * 
     * @param string $phone Número de telefone
     * @param string $message Mensagem
     * @param string $type Tipo da mensagem
     * @param array $metadata Metadados adicionais
     * @return bool Sucesso do log
     */
    function log_whatsapp_message($phone, $message, $type = 'outbound', $metadata = []) {
        $CI =& get_instance();
        
        // Implementar log no banco de dados
        $log_data = [
            'phone_number' => $phone,
            'message' => $message,
            'message_type' => $type,
            'metadata' => json_encode($metadata),
            'created_at' => date('Y-m-d H:i:s'),
            'ip_address' => $CI->input->ip_address(),
            'user_agent' => $CI->input->user_agent()
        ];
        
        // Aqui seria inserido no banco de dados
        // $CI->db->insert('whatsapp_message_logs', $log_data);
        
        return true;
    }
}

/* End of file whatsapp_helper.php */
/* Location: ./application/helpers/whatsapp_helper.php */