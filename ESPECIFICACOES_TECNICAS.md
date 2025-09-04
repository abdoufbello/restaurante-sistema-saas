# Especificações Técnicas - Sistema para Totem K2 Mini Rex Sunmi

## Visão Geral do Projeto

Desenvolvimento de um sistema web/app para totem de autoatendimento K2 Mini Rex Sunmi, focado em atender restaurantes com funcionalidades de:
- Painel administrativo para restaurantes (login com CNPJ, operador e senha)
- Cadastro de pratos (nome, foto, preço, categoria, ingredientes)
- Interface do cliente para consulta de pratos, montagem de pedidos e compra

## Especificações do Hardware - K2 Mini Rex Sunmi

### Configurações Básicas
- **Sistema Operacional**: Android 7.1 (SUNMI OS)
- **Processador**: Rockchip Quad-Core / Qualcomm Snapdragon Octa-core
- **Memória**: 2GB RAM + 16GB ROM (expansível até 64GB via MicroSD)
- **Display**: 15.6" 1920x1080 FHD (orientação retrato), tela capacitiva multitoque
- **Peso**: 8-10.5Kg (dependendo da configuração)

### Recursos Integrados
- **Câmera 3D**: Câmera de luz estruturada para reconhecimento facial
- **Impressora Térmica**: Suporte a bobinas de 58mm/80mm, velocidade até 250mm/s
- **Leitor de Códigos**: Suporte a códigos 1D e 2D (impressos e telas de celular)
- **NFC**: Suporte para cartões M1/ID/CPU
- **Alto-falante**: 3W integrado
- **Conectividade**: Wi-Fi 802.11 a/b/g/n/ac (2.4GHz/5GHz), Bluetooth 2.1/3.0/4.0

### Interfaces e Portas
- 5x Portas USB Tipo-A
- 1x Porta Serial RJ11
- 1x Porta gaveta de dinheiro RJ12
- 1x Porta LAN RJ45
- 1x Porta saída de áudio
- 1x Porta de alimentação
- 1x Porta Micro USB (debug)

### Dimensões e Instalação
- **Versão Mesa**: 300x250x590mm
- **Versão Parede**: 265x120x680mm
- **Alimentação**: AC100~240V/1.7A, DC24V/2.5A
- **Temperatura Operação**: 0°C~40°C
- **Temperatura Armazenamento**: -20°C~55°C

## Arquitetura do Sistema

### Stack Tecnológico
- **Backend**: PHP 8.1+ com CodeIgniter 4
- **Banco de Dados**: MySQL 8.0+ ou MariaDB 10.6+
- **Frontend Admin**: Bootstrap 5 + jQuery
- **Frontend Kiosk**: HTML5, CSS3, JavaScript (otimizado para touch)
- **Servidor Web**: Apache 2.4+ ou Nginx

### Baseado no OpenSourcePOS
- **Versão**: 3.4+ (CodeIgniter 4)
- **Funcionalidades Aproveitadas**:
  - Sistema de autenticação e permissões
  - Gestão de produtos (adaptado para pratos)
  - Sistema de vendas e relatórios
  - Interface administrativa
  - Suporte multilíngue (português brasileiro)

## Funcionalidades Específicas para Restaurantes

### Painel Administrativo
1. **Sistema de Login**:
   - CNPJ do restaurante
   - Nome do operador
   - Senha
   - Validação de CNPJ brasileiro

2. **Gestão de Pratos**:
   - Nome do prato
   - Foto em alta resolução
   - Preço (formato brasileiro R$)
   - Categoria (Entradas, Principais, Sobremesas, Bebidas)
   - Lista de ingredientes
   - Descrição detalhada
   - Status (ativo/inativo)

3. **Relatórios e Analytics**:
   - Vendas por período
   - Pratos mais vendidos
   - Relatórios fiscais brasileiros

### Interface do Cliente (Kiosk)
1. **Navegação por Categorias**:
   - Filtros visuais por categoria
   - Busca por nome do prato
   - Visualização em grid otimizada para 15.6"

2. **Carrinho de Compras**:
   - Adição/remoção de itens
   - Quantidade personalizável
   - Observações especiais
   - Cálculo automático do total

3. **Finalização do Pedido**:
   - Seleção de mesa/balcão
   - Métodos de pagamento brasileiros
   - Impressão automática do pedido

## Integração com Hardware do Totem

### Impressora Térmica
- Impressão automática de pedidos
- Formato de recibo otimizado (58mm/80mm)
- Corte automático do papel

### Leitor de Códigos
- Leitura de QR codes para promoções
- Códigos de desconto
- Integração com programas de fidelidade

### Câmera 3D (Futuro)
- Reconhecimento facial para clientes VIP
- Sistema de fidelidade baseado em biometria

### NFC (Futuro)
- Pagamentos contactless
- Cartões de fidelidade

## Métodos de Pagamento Brasileiros

### Implementação Obrigatória
1. **PIX**: Integração com API de pagamento PIX
2. **Cartão de Crédito/Débito**: Via terminal de pagamento
3. **Dinheiro**: Com gaveta de dinheiro integrada

### Implementação Futura
- Carteiras digitais (PicPay, Mercado Pago)
- Vale-refeição/alimentação
- Crediário próprio

## Requisitos de Performance

### Interface do Kiosk
- Tempo de carregamento < 3 segundos
- Resposta ao toque < 200ms
- Suporte a múltiplos toques simultâneos
- Interface responsiva para 1920x1080

### Backend
- Suporte a múltiplos restaurantes (multi-tenant)
- API RESTful para comunicação com o kiosk
- Cache de imagens e dados frequentes
- Backup automático diário

## Segurança

### Autenticação
- Hash de senhas com bcrypt
- Sessões seguras com timeout
- Validação de CNPJ em tempo real

### Dados
- Criptografia de dados sensíveis
- Logs de auditoria
- Conformidade com LGPD

## Ambiente de Desenvolvimento

### Local (Localhost)
- XAMPP 8.1+ ou WAMP
- MySQL 8.0+
- PHP 8.1+
- Composer para dependências

### Produção (VPS)
- Ubuntu 20.04+ ou CentOS 8+
- Apache/Nginx com SSL
- MySQL/MariaDB
- Backup automatizado
- Monitoramento de performance

## Cronograma de Desenvolvimento

### Fase 1 - Configuração Base (1-2 semanas)
- Setup do ambiente local
- Instalação e configuração do OpenSourcePOS
- Customização inicial para restaurantes

### Fase 2 - Backend (2-3 semanas)
- Sistema de autenticação com CNPJ
- CRUD de pratos e categorias
- API para o kiosk

### Fase 3 - Frontend Kiosk (2-3 semanas)
- Interface otimizada para touch
- Carrinho de compras
- Integração com pagamentos

### Fase 4 - Testes e Deploy (1-2 semanas)
- Testes em ambiente de produção
- Otimizações de performance
- Deploy no VPS

## Considerações Especiais

### Localização Brasileira
- Formato de data DD/MM/AAAA
- Moeda em Real (R$)
- Validação de CNPJ
- Fuso horário brasileiro
- Idioma português brasileiro

### Acessibilidade
- Interface adaptada para diferentes idades
- Botões grandes para facilitar o toque
- Contraste adequado para leitura
- Prompts de voz para orientação

### Manutenção
- Logs detalhados para troubleshooting
- Sistema de atualização remota
- Monitoramento de status do hardware
- Backup automático de dados