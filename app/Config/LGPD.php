<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class LGPD extends BaseConfig
{
    /**
     * Configurações gerais de LGPD
     */
    public array $general = [
        'enabled' => true,
        'company_name' => 'Sua Empresa',
        'dpo_email' => 'dpo@suaempresa.com.br',
        'dpo_phone' => '(11) 99999-9999',
        'privacy_policy_url' => '/privacy-policy',
        'terms_of_use_url' => '/terms-of-use',
        'cookie_policy_url' => '/cookie-policy'
    ];

    /**
     * Tipos de dados pessoais tratados
     */
    public array $personalDataTypes = [
        'identification' => [
            'name' => 'Dados de Identificação',
            'description' => 'Nome, CPF, RG, data de nascimento',
            'fields' => ['name', 'cpf', 'rg', 'birth_date'],
            'sensitive' => false,
            'retention_period' => '5 years'
        ],
        'contact' => [
            'name' => 'Dados de Contato',
            'description' => 'E-mail, telefone, endereço',
            'fields' => ['email', 'phone', 'address', 'city', 'state', 'zip_code'],
            'sensitive' => false,
            'retention_period' => '3 years'
        ],
        'financial' => [
            'name' => 'Dados Financeiros',
            'description' => 'Informações de pagamento e transações',
            'fields' => ['payment_method', 'card_last_digits', 'transaction_history'],
            'sensitive' => true,
            'retention_period' => '5 years'
        ],
        'behavioral' => [
            'name' => 'Dados Comportamentais',
            'description' => 'Histórico de pedidos, preferências',
            'fields' => ['order_history', 'preferences', 'ratings'],
            'sensitive' => false,
            'retention_period' => '2 years'
        ],
        'location' => [
            'name' => 'Dados de Localização',
            'description' => 'Endereços de entrega, geolocalização',
            'fields' => ['delivery_address', 'geolocation'],
            'sensitive' => false,
            'retention_period' => '1 year'
        ]
    ];

    /**
     * Finalidades de tratamento de dados
     */
    public array $processingPurposes = [
        'service_provision' => [
            'name' => 'Prestação de Serviços',
            'description' => 'Processamento de pedidos e entregas',
            'legal_basis' => 'execution_of_contract',
            'data_types' => ['identification', 'contact', 'location'],
            'required' => true
        ],
        'payment_processing' => [
            'name' => 'Processamento de Pagamentos',
            'description' => 'Cobrança e processamento de transações',
            'legal_basis' => 'execution_of_contract',
            'data_types' => ['identification', 'financial'],
            'required' => true
        ],
        'marketing' => [
            'name' => 'Marketing e Comunicação',
            'description' => 'Envio de ofertas e comunicações promocionais',
            'legal_basis' => 'consent',
            'data_types' => ['contact', 'behavioral'],
            'required' => false
        ],
        'analytics' => [
            'name' => 'Análises e Melhorias',
            'description' => 'Análise de comportamento para melhorar serviços',
            'legal_basis' => 'legitimate_interest',
            'data_types' => ['behavioral'],
            'required' => false
        ],
        'legal_compliance' => [
            'name' => 'Cumprimento Legal',
            'description' => 'Atendimento a obrigações legais e fiscais',
            'legal_basis' => 'legal_obligation',
            'data_types' => ['identification', 'financial'],
            'required' => true
        ]
    ];

    /**
     * Bases legais para tratamento
     */
    public array $legalBases = [
        'consent' => 'Consentimento do titular',
        'execution_of_contract' => 'Execução de contrato',
        'legal_obligation' => 'Cumprimento de obrigação legal',
        'vital_interests' => 'Proteção da vida ou incolumidade física',
        'public_interest' => 'Exercício regular de direitos',
        'legitimate_interest' => 'Interesse legítimo do controlador'
    ];

    /**
     * Configurações de consentimento
     */
    public array $consent = [
        'cookie_banner' => [
            'enabled' => true,
            'position' => 'bottom',
            'theme' => 'light',
            'auto_hide' => false,
            'show_details_link' => true
        ],
        'granular_consent' => [
            'enabled' => true,
            'categories' => [
                'essential' => [
                    'name' => 'Cookies Essenciais',
                    'description' => 'Necessários para o funcionamento básico do site',
                    'required' => true,
                    'default' => true
                ],
                'functional' => [
                    'name' => 'Cookies Funcionais',
                    'description' => 'Melhoram a experiência do usuário',
                    'required' => false,
                    'default' => false
                ],
                'analytics' => [
                    'name' => 'Cookies de Análise',
                    'description' => 'Ajudam a entender como o site é usado',
                    'required' => false,
                    'default' => false
                ],
                'marketing' => [
                    'name' => 'Cookies de Marketing',
                    'description' => 'Personalizam anúncios e ofertas',
                    'required' => false,
                    'default' => false
                ]
            ]
        ],
        'withdrawal' => [
            'enabled' => true,
            'methods' => ['email', 'phone', 'website'],
            'processing_time' => '72 hours'
        ]
    ];

    /**
     * Direitos dos titulares
     */
    public array $dataSubjectRights = [
        'access' => [
            'name' => 'Acesso aos Dados',
            'description' => 'Solicitar informações sobre dados pessoais tratados',
            'enabled' => true,
            'processing_time' => '15 days'
        ],
        'rectification' => [
            'name' => 'Correção de Dados',
            'description' => 'Solicitar correção de dados incorretos',
            'enabled' => true,
            'processing_time' => '15 days'
        ],
        'erasure' => [
            'name' => 'Exclusão de Dados',
            'description' => 'Solicitar exclusão de dados pessoais',
            'enabled' => true,
            'processing_time' => '15 days'
        ],
        'portability' => [
            'name' => 'Portabilidade de Dados',
            'description' => 'Solicitar dados em formato estruturado',
            'enabled' => true,
            'processing_time' => '15 days'
        ],
        'objection' => [
            'name' => 'Oposição ao Tratamento',
            'description' => 'Opor-se ao tratamento de dados',
            'enabled' => true,
            'processing_time' => '15 days'
        ]
    ];

    /**
     * Configurações de auditoria
     */
    public array $audit = [
        'enabled' => true,
        'log_retention' => '5 years',
        'events_to_log' => [
            'consent_given',
            'consent_withdrawn',
            'data_access_request',
            'data_rectification',
            'data_erasure',
            'data_portability',
            'data_breach',
            'privacy_policy_update'
        ],
        'sensitive_data_masking' => true,
        'automated_reports' => [
            'enabled' => true,
            'frequency' => 'monthly',
            'recipients' => ['dpo@suaempresa.com.br']
        ]
    ];

    /**
     * Configurações de segurança
     */
    public array $security = [
        'encryption' => [
            'enabled' => true,
            'algorithm' => 'AES-256-CBC',
            'key_rotation' => '90 days'
        ],
        'anonymization' => [
            'enabled' => true,
            'methods' => ['hashing', 'pseudonymization'],
            'retention_after_anonymization' => 'indefinite'
        ],
        'access_control' => [
            'role_based' => true,
            'audit_access' => true,
            'session_timeout' => '30 minutes'
        ]
    ];

    /**
     * Configurações de notificação
     */
    public array $notifications = [
        'data_breach' => [
            'enabled' => true,
            'notify_authority' => true,
            'authority_deadline' => '72 hours',
            'notify_subjects' => true,
            'subjects_deadline' => '72 hours',
            'notification_template' => 'lgpd/data_breach'
        ],
        'consent_expiry' => [
            'enabled' => true,
            'warning_period' => '30 days',
            'notification_template' => 'lgpd/consent_expiry'
        ],
        'data_retention' => [
            'enabled' => true,
            'warning_period' => '30 days',
            'auto_delete' => false,
            'notification_template' => 'lgpd/data_retention'
        ]
    ];

    /**
     * Configurações de terceiros
     */
    public array $thirdParties = [
        'processors' => [
            'payment_gateway' => [
                'name' => 'Gateway de Pagamento',
                'purpose' => 'Processamento de pagamentos',
                'data_types' => ['identification', 'financial'],
                'location' => 'Brasil',
                'contract_date' => null,
                'dpa_signed' => false
            ],
            'delivery_platform' => [
                'name' => 'Plataforma de Delivery',
                'purpose' => 'Gestão de entregas',
                'data_types' => ['identification', 'contact', 'location'],
                'location' => 'Brasil',
                'contract_date' => null,
                'dpa_signed' => false
            ]
        ],
        'international_transfers' => [
            'enabled' => false,
            'adequacy_decision' => false,
            'safeguards' => [],
            'notification_required' => true
        ]
    ];

    /**
     * Templates de comunicação
     */
    public array $templates = [
        'privacy_notice' => 'lgpd/privacy_notice',
        'consent_form' => 'lgpd/consent_form',
        'data_subject_request' => 'lgpd/data_subject_request',
        'breach_notification' => 'lgpd/breach_notification',
        'consent_withdrawal' => 'lgpd/consent_withdrawal'
    ];
}