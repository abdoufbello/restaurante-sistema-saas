# Sistema LGPD - CodeIgniter 4

Sistema completo de compliance com a Lei Geral de Proteção de Dados (LGPD) para CodeIgniter 4.

## 📋 Funcionalidades

### ✅ Implementadas
- ✅ Gerenciamento de consentimentos
- ✅ Proteção e criptografia de dados pessoais
- ✅ Sistema de auditoria e logs
- ✅ Política de privacidade dinâmica
- ✅ APIs REST completas
- ✅ Interface web de exemplo
- ✅ Filtros de permissão
- ✅ Sistema de cookies e preferências

### 🎯 Principais Recursos
- **Consentimento Granular**: Controle detalhado de consentimentos por categoria
- **Auditoria Completa**: Logs de todas as operações com dados pessoais
- **Direitos do Titular**: Implementação completa dos direitos LGPD
- **Criptografia**: Proteção de dados sensíveis
- **Anonimização**: Ferramentas para anonimização de dados
- **Relatórios**: Dashboards e relatórios de compliance

## 🚀 Instalação

### 1. Configuração do Banco de Dados

```bash
# Execute o script SQL para criar as tabelas
mysql -u seu_usuario -p sua_base_dados < lgpd_tables.sql
```

### 2. Configuração do CodeIgniter

#### Arquivo `.env`
```env
# Configurações LGPD
LGPD_ENABLED=true
LGPD_ENCRYPTION_KEY=sua_chave_de_32_caracteres_aqui
LGPD_AUDIT_ENABLED=true
LGPD_CONSENT_EXPIRY_DAYS=365
LGPD_DATA_RETENTION_DAYS=2555
```

#### Autoload dos Serviços
Adicione ao `app/Config/Services.php`:

```php
public static function lgpdConsent($getShared = true)
{
    if ($getShared) {
        return static::getSharedInstance('lgpdConsent');
    }
    return new \App\Services\LGPD\ConsentService();
}

public static function lgpdDataProtection($getShared = true)
{
    if ($getShared) {
        return static::getSharedInstance('lgpdDataProtection');
    }
    return new \App\Services\LGPD\DataProtectionService();
}

public static function lgpdAudit($getShared = true)
{
    if ($getShared) {
        return static::getSharedInstance('lgpdAudit');
    }
    return new \App\Services\LGPD\AuditService();
}

public static function lgpdPrivacyPolicy($getShared = true)
{
    if ($getShared) {
        return static::getSharedInstance('lgpdPrivacyPolicy');
    }
    return new \App\Services\LGPD\PrivacyPolicyService();
}
```

### 3. Configuração de Filtros

Adicione ao `app/Config/Filters.php`:

```php
public $aliases = [
    // ... outros filtros
    'lgpd_permission' => \App\Filters\LGPDPermissionFilter::class,
];

public $filters = [
    'lgpd_permission' => [
        'before' => [
            'api/lgpd/admin/*',
            'api/lgpd/audit/*',
            'api/lgpd/data-breach/*'
        ]
    ]
];
```

## 📖 Uso

### APIs Disponíveis

#### Consentimento
```bash
# Registrar consentimento
POST /api/lgpd/consent
{
    "data_subject_id": "user123",
    "consent_type": "cookies",
    "purpose": "Analytics e melhorias",
    "legal_basis": "consent"
}

# Verificar consentimento
GET /api/lgpd/consent/check/user123/cookies

# Revogar consentimento
DELETE /api/lgpd/consent/user123/cookies
```

#### Direitos do Titular
```bash
# Portabilidade de dados
GET /api/lgpd/data/export/user123

# Solicitação de apagamento
DELETE /api/lgpd/data/delete/user123

# Verificar acesso a dados
GET /api/lgpd/data/access-check/user123
```

#### Política de Privacidade
```bash
# Obter política atual
GET /api/lgpd/privacy-policy

# Aceitar política
POST /api/lgpd/privacy-policy/accept
{
    "data_subject_id": "user123",
    "policy_version": "1.0"
}
```

### Interface Web

Acesse as páginas de exemplo:
- `/lgpd/example` - Página de demonstração
- `/lgpd/privacy-policy` - Política de privacidade
- `/lgpd/privacy-settings` - Configurações de privacidade
- `/lgpd/compliance-dashboard` - Dashboard de compliance

### Integração Frontend

#### HTML
```html
<!-- Inclua os arquivos CSS e JS -->
<link rel="stylesheet" href="/assets/css/lgpd-consent.css">
<script src="/assets/js/lgpd-consent.js"></script>

<!-- Inicialize o sistema -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    LGPDConsent.init({
        apiBaseUrl: '/api/lgpd',
        dataSubjectId: 'user123', // ID do usuário atual
        showBanner: true,
        granularConsent: true
    });
});
</script>
```

## 🔧 Configurações Avançadas

### Personalização de Consentimentos

Edite `app/Config/LGPD.php` para personalizar:

```php
public $consentTypes = [
    'essential' => [
        'name' => 'Cookies Essenciais',
        'description' => 'Necessários para funcionamento básico',
        'required' => true,
        'expiry_days' => 365
    ],
    'analytics' => [
        'name' => 'Analytics',
        'description' => 'Análise de uso e melhorias',
        'required' => false,
        'expiry_days' => 365
    ],
    // ... mais tipos
];
```

### Configuração de Criptografia

```php
// Dados que devem ser criptografados
public $encryptionFields = [
    'users' => ['cpf', 'phone'],
    'customers' => ['cpf', 'rg', 'credit_card'],
    // ... mais tabelas
];
```

### Retenção de Dados

```php
// Períodos de retenção por categoria
public $dataRetention = [
    'identification' => 2555, // 7 anos
    'financial' => 1825,      // 5 anos
    'marketing' => 365,       // 1 ano
    // ... mais categorias
];
```

## 📊 Monitoramento e Relatórios

### Dashboard de Compliance

Acesse `/lgpd/compliance-dashboard` para visualizar:
- Status geral de compliance
- Consentimentos ativos/revogados
- Atividades de auditoria
- Violações de dados
- Relatórios de conformidade

### Logs de Auditoria

Todos os eventos são registrados automaticamente:
- Acesso a dados pessoais
- Concessão/revogação de consentimentos
- Exportação de dados
- Apagamento de dados
- Tentativas de acesso não autorizado

## 🛡️ Segurança

### Medidas Implementadas
- Criptografia de dados sensíveis
- Controle de acesso baseado em permissões
- Rate limiting para operações sensíveis
- Logs de auditoria completos
- Validação rigorosa de entrada
- Proteção contra ataques comuns

### Recomendações
1. Use HTTPS em produção
2. Configure backups regulares
3. Monitore logs de auditoria
4. Mantenha o sistema atualizado
5. Treine a equipe sobre LGPD

## 🔍 Troubleshooting

### Problemas Comuns

#### Erro de Permissão
```
Solução: Verifique se o usuário tem as permissões adequadas
no filtro LGPDPermissionFilter
```

#### Consentimento Não Encontrado
```
Solução: Verifique se o consentimento foi registrado corretamente
e se o data_subject_id está correto
```

#### Erro de Criptografia
```
Solução: Verifique se a chave de criptografia está configurada
corretamente no arquivo .env
```

## 📚 Documentação Adicional

### Estrutura de Arquivos
```
app/
├── Config/LGPD.php                    # Configurações LGPD
├── Controllers/
│   ├── Api/LGPDController.php         # APIs REST
│   └── LGPDExampleController.php      # Páginas de exemplo
├── Services/LGPD/
│   ├── ConsentService.php             # Gerenciamento de consentimentos
│   ├── DataProtectionService.php      # Proteção de dados
│   ├── AuditService.php              # Auditoria e logs
│   └── PrivacyPolicyService.php      # Política de privacidade
├── Filters/LGPDPermissionFilter.php   # Filtro de permissões
├── Database/Migrations/               # Migrations do banco
└── Views/templates/lgpd_example.php   # Template de exemplo

public/assets/
├── css/lgpd-consent.css              # Estilos do sistema
└── js/lgpd-consent.js                # JavaScript do frontend
```

### Compliance Checklist

- [ ] Tabelas do banco criadas
- [ ] Configurações do .env definidas
- [ ] Serviços registrados no Services.php
- [ ] Filtros configurados
- [ ] Política de privacidade criada
- [ ] Sistema de consentimento ativo
- [ ] Logs de auditoria funcionando
- [ ] Testes realizados
- [ ] Documentação da equipe
- [ ] Treinamento realizado

## 📞 Suporte

Para dúvidas ou suporte:
- Consulte a documentação oficial da LGPD
- Revise os logs de auditoria
- Verifique as configurações do sistema
- Entre em contato com o DPO (Data Protection Officer)

---

**Importante**: Este sistema fornece ferramentas para compliance com a LGPD, mas a conformidade total depende também de processos organizacionais, treinamento da equipe e políticas internas adequadas.