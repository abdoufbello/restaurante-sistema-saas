<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Configurações do WhatsApp Business
 * 
 * Este arquivo contém as configurações necessárias para integração
 * com a API do WhatsApp Business
 */

/*
|--------------------------------------------------------------------------
| WhatsApp Business API Configuration
|--------------------------------------------------------------------------
|
| Configurações para integração com WhatsApp Business API
|
*/

// Configurações da API do WhatsApp Business
$config['whatsapp_business_api_url'] = 'https://graph.facebook.com/v18.0/';
$config['whatsapp_business_phone_number_id'] = ''; // ID do número de telefone no WhatsApp Business
$config['whatsapp_business_access_token'] = ''; // Token de acesso permanente
$config['whatsapp_business_webhook_verify_token'] = ''; // Token para verificação do webhook

// Configurações do WhatsApp Web (fallback)
$config['whatsapp_web_url'] = 'https://web.whatsapp.com/send';
$config['whatsapp_api_url'] = 'https://api.whatsapp.com/send';

// Configurações gerais
$config['whatsapp_default_country_code'] = '55'; // Brasil
$config['whatsapp_message_timeout'] = 30; // Timeout em segundos
$config['whatsapp_retry_attempts'] = 3; // Tentativas de reenvio
$config['whatsapp_log_messages'] = true; // Registrar mensagens no log

// Configurações de templates de mensagem
$config['whatsapp_templates'] = [
    'shopping_list' => [
        'name' => 'lista_compras',
        'language' => 'pt_BR',
        'components' => [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => '{{supplier_name}}'
                    ]
                ]
            ],
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => '{{items_list}}'
                    ],
                    [
                        'type' => 'text',
                        'text' => '{{total_cost}}'
                    ]
                ]
            ]
        ]
    ],
    'restock_alert' => [
        'name' => 'alerta_reposicao',
        'language' => 'pt_BR',
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => '{{product_name}}'
                    ],
                    [
                        'type' => 'text',
                        'text' => '{{current_stock}}'
                    ],
                    [
                        'type' => 'text',
                        'text' => '{{min_stock}}'
                    ]
                ]
            ]
        ]
    ],
    'purchase_confirmation' => [
        'name' => 'confirmacao_compra',
        'language' => 'pt_BR',
        'components' => [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => '{{purchase_reference}}'
                    ]
                ]
            ],
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => '{{supplier_name}}'
                    ],
                    [
                        'type' => 'text',
                        'text' => '{{total_amount}}'
                    ],
                    [
                        'type' => 'text',
                        'text' => '{{delivery_date}}'
                    ]
                ]
            ]
        ]
    ]
];

// Configurações de webhook
$config['whatsapp_webhook_url'] = base_url('api/whatsapp/webhook');
$config['whatsapp_webhook_events'] = [
    'messages',
    'message_deliveries',
    'message_reads',
    'message_reactions'
];

// Configurações de rate limiting
$config['whatsapp_rate_limit'] = [
    'messages_per_second' => 20,
    'messages_per_minute' => 1000,
    'messages_per_hour' => 10000
];

// Configurações de formatação
$config['whatsapp_message_format'] = [
    'max_length' => 4096,
    'currency_symbol' => 'R$',
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
    'decimal_places' => 2,
    'thousand_separator' => '.',
    'decimal_separator' => ','
];

// Configurações de segurança
$config['whatsapp_security'] = [
    'encrypt_tokens' => true,
    'validate_webhook_signature' => true,
    'allowed_origins' => [
        'https://graph.facebook.com',
        'https://web.whatsapp.com'
    ],
    'ip_whitelist' => [
        // IPs do Facebook/Meta para webhooks
        '173.252.74.0/24',
        '173.252.75.0/24',
        '173.252.76.0/24',
        '173.252.77.0/24'
    ]
];

// Configurações de cache
$config['whatsapp_cache'] = [
    'enabled' => true,
    'ttl' => 3600, // 1 hora
    'prefix' => 'whatsapp_',
    'driver' => 'file' // file, redis, memcached
];

// Configurações de logs
$config['whatsapp_logging'] = [
    'enabled' => true,
    'level' => 'info', // debug, info, warning, error
    'file_path' => APPPATH . 'logs/whatsapp.log',
    'max_file_size' => '10MB',
    'rotate_files' => true,
    'max_files' => 5
];

// Configurações de notificações
$config['whatsapp_notifications'] = [
    'low_stock_alerts' => true,
    'purchase_confirmations' => true,
    'delivery_reminders' => true,
    'payment_reminders' => false,
    'promotional_messages' => false
];

// Configurações de backup
$config['whatsapp_backup'] = [
    'enabled' => true,
    'frequency' => 'daily', // hourly, daily, weekly
    'retention_days' => 30,
    'backup_path' => APPPATH . 'backups/whatsapp/',
    'compress' => true
];

// Configurações de desenvolvimento
$config['whatsapp_development'] = [
    'sandbox_mode' => false,
    'test_phone_numbers' => [
        '5511999999999' // Número de teste
    ],
    'mock_api_responses' => false,
    'debug_mode' => false
];

// Configurações de integração com outros sistemas
$config['whatsapp_integrations'] = [
    'crm_enabled' => false,
    'erp_enabled' => false,
    'analytics_enabled' => true,
    'chatbot_enabled' => false
];

/* End of file Whatsapp.php */
/* Location: ./application/config/Whatsapp.php */