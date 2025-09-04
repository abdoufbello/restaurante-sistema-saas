<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Manual - Sistema SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-card {
            transition: transform 0.2s;
        }
        .test-card:hover {
            transform: translateY(-2px);
        }
        .credential-card {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .test-result {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="card-body text-center">
                        <h1 class="mb-3">
                            <i class="fas fa-user-check me-3"></i>
                            Teste Manual Completo - Sistema SaaS
                        </h1>
                        <p class="mb-0">Validação de funcionalidades como Heavy User</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Credenciais de Teste -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-key me-2"></i>
                            Credenciais de Teste Rápido
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="credential-card p-3 rounded">
                                    <h6 class="text-primary">Pizzaria Bella Napoli (Admin)</h6>
                                    <p class="mb-1"><strong>CNPJ:</strong> 12.345.678/0001-90</p>
                                    <p class="mb-1"><strong>Usuário:</strong> admin_pizza</p>
                                    <p class="mb-1"><strong>Senha:</strong> 123456</p>
                                    <p class="mb-0"><small class="text-muted">Plano: Professional</small></p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="credential-card p-3 rounded">
                                    <h6 class="text-success">Burger House Premium (Admin)</h6>
                                    <p class="mb-1"><strong>CNPJ:</strong> 98.765.432/0001-10</p>
                                    <p class="mb-1"><strong>Usuário:</strong> admin_burger</p>
                                    <p class="mb-1"><strong>Senha:</strong> 123456</p>
                                    <p class="mb-0"><small class="text-muted">Plano: Enterprise</small></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="credential-card p-3 rounded">
                                    <h6 class="text-info">Sushi Zen (Cozinha)</h6>
                                    <p class="mb-1"><strong>CNPJ:</strong> 11.222.333/0001-44</p>
                                    <p class="mb-1"><strong>Usuário:</strong> cozinha_sushi</p>
                                    <p class="mb-1"><strong>Senha:</strong> 123456</p>
                                    <p class="mb-0"><small class="text-muted">Plano: Starter</small></p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="credential-card p-3 rounded">
                                    <h6 class="text-warning">Café Gourmet (Caixa)</h6>
                                    <p class="mb-1"><strong>CNPJ:</strong> 44.555.666/0001-77</p>
                                    <p class="mb-1"><strong>Usuário:</strong> caixa_cafe</p>
                                    <p class="mb-1"><strong>Senha:</strong> 123456</p>
                                    <p class="mb-0"><small class="text-muted">Plano: Trial</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Testes Manuais -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card test-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Teste 1: Login e Redirecionamento
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Objetivo:</strong> Verificar se o login redireciona corretamente para o dashboard</p>
                        <ol>
                            <li>Acesse a página de login</li>
                            <li>Use as credenciais: admin_pizza / 123456</li>
                            <li>CNPJ: 12.345.678/0001-90</li>
                            <li>Clique em "Entrar"</li>
                            <li>Verifique se foi redirecionado para dashboard.php</li>
                        </ol>
                        <div class="d-grid">
                            <a href="simple_auth.php" class="btn btn-primary" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                Executar Teste
                            </a>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm" onclick="markTest(1, 'pass')">✓ Passou</button>
                            <button class="btn btn-danger btn-sm" onclick="markTest(1, 'fail')">✗ Falhou</button>
                        </div>
                        <div id="result-1" class="test-result mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card test-card h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Teste 2: Dashboard Funcional
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Objetivo:</strong> Verificar se o dashboard carrega corretamente</p>
                        <ol>
                            <li>Após login bem-sucedido</li>
                            <li>Verifique se o nome do restaurante aparece</li>
                            <li>Teste os botões: Categorias, Pratos, Pedidos</li>
                            <li>Verifique o botão "Interface de Pedidos"</li>
                            <li>Confirme se os dados estão sendo exibidos</li>
                        </ol>
                        <div class="d-grid">
                            <a href="dashboard.php" class="btn btn-success" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                Executar Teste
                            </a>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm" onclick="markTest(2, 'pass')">✓ Passou</button>
                            <button class="btn btn-danger btn-sm" onclick="markTest(2, 'fail')">✗ Falhou</button>
                        </div>
                        <div id="result-2" class="test-result mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card test-card h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Teste 3: Interface de Pedidos
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Objetivo:</strong> Testar a interface de pedidos online</p>
                        <ol>
                            <li>Acesse a interface de pedidos (kiosk.php)</li>
                            <li>Selecione um restaurante</li>
                            <li>Navegue pelas categorias</li>
                            <li>Adicione itens ao carrinho</li>
                            <li>Teste o processo de checkout</li>
                        </ol>
                        <div class="d-grid">
                            <a href="kiosk.php" class="btn btn-info" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                Executar Teste
                            </a>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm" onclick="markTest(3, 'pass')">✓ Passou</button>
                            <button class="btn btn-danger btn-sm" onclick="markTest(3, 'fail')">✗ Falhou</button>
                        </div>
                        <div id="result-3" class="test-result mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card test-card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Teste 4: Diferentes Tipos de Usuário
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Objetivo:</strong> Testar diferentes perfis de usuário</p>
                        <ol>
                            <li>Teste login como Admin (admin_pizza)</li>
                            <li>Teste login como Cozinha (cozinha_sushi)</li>
                            <li>Teste login como Caixa (caixa_cafe)</li>
                            <li>Verifique permissões diferentes</li>
                            <li>Confirme funcionalidades por perfil</li>
                        </ol>
                        <div class="d-grid">
                            <a href="simple_auth.php" class="btn btn-warning" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                Executar Teste
                            </a>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm" onclick="markTest(4, 'pass')">✓ Passou</button>
                            <button class="btn btn-danger btn-sm" onclick="markTest(4, 'fail')">✗ Falhou</button>
                        </div>
                        <div id="result-4" class="test-result mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card test-card h-100">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Teste 5: Integridade dos Dados
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Objetivo:</strong> Verificar se os dados estão consistentes</p>
                        <ol>
                            <li>Execute a suite de testes automatizados</li>
                            <li>Verifique se todos os testes passaram</li>
                            <li>Confirme dados de restaurantes</li>
                            <li>Valide funcionários e permissões</li>
                            <li>Teste categorias e pratos</li>
                        </ol>
                        <div class="d-grid">
                            <a href="test_suite.php" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                Executar Teste
                            </a>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm" onclick="markTest(5, 'pass')">✓ Passou</button>
                            <button class="btn btn-danger btn-sm" onclick="markTest(5, 'fail')">✗ Falhou</button>
                        </div>
                        <div id="result-5" class="test-result mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card test-card h-100">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-mobile-alt me-2"></i>
                            Teste 6: Responsividade
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Objetivo:</strong> Testar em diferentes dispositivos</p>
                        <ol>
                            <li>Teste em desktop (1920x1080)</li>
                            <li>Teste em tablet (768px)</li>
                            <li>Teste em mobile (375px)</li>
                            <li>Verifique navegação touch</li>
                            <li>Confirme usabilidade em todos os tamanhos</li>
                        </ol>
                        <div class="d-grid">
                            <button class="btn btn-dark" onclick="testResponsive()">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Testar Responsividade
                            </button>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm" onclick="markTest(6, 'pass')">✓ Passou</button>
                            <button class="btn btn-danger btn-sm" onclick="markTest(6, 'fail')">✗ Falhou</button>
                        </div>
                        <div id="result-6" class="test-result mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumo dos Testes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Resumo dos Testes Manuais
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="test-summary">
                            <p class="text-muted">Execute os testes acima e marque os resultados para ver o resumo.</p>
                        </div>
                        <div class="progress mt-3" style="height: 20px;">
                            <div id="progress-bar" class="progress-bar" style="width: 0%">0%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Conceito SaaS -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-lightbulb me-2"></i>Conceito SaaS Refinado</h6>
                    <p class="mb-2">
                        <strong>Para Restaurantes SEM Totem Físico:</strong> 
                        Podem usar o sistema para gestão completa do negócio (funcionários, cardápio, pedidos, relatórios) 
                        e receber pedidos através da interface web/app.
                    </p>
                    <p class="mb-0">
                        <strong>Para Restaurantes COM Totem Físico:</strong> 
                        Além da gestão, podem usar o totem para pedidos presenciais e a interface web/app para pedidos online, 
                        integrando ambos os canais.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let testResults = {};
        
        function markTest(testId, result) {
            testResults[testId] = result;
            
            const resultDiv = document.getElementById(`result-${testId}`);
            resultDiv.style.display = 'block';
            
            if (result === 'pass') {
                resultDiv.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="fas fa-check me-2"></i>Teste passou com sucesso!</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="fas fa-times me-2"></i>Teste falhou - verifique os problemas.</div>';
            }
            
            updateSummary();
        }
        
        function updateSummary() {
            const totalTests = 6;
            const completedTests = Object.keys(testResults).length;
            const passedTests = Object.values(testResults).filter(r => r === 'pass').length;
            const failedTests = Object.values(testResults).filter(r => r === 'fail').length;
            
            const progress = (completedTests / totalTests) * 100;
            
            document.getElementById('progress-bar').style.width = progress + '%';
            document.getElementById('progress-bar').textContent = Math.round(progress) + '%';
            
            if (completedTests > 0) {
                document.getElementById('test-summary').innerHTML = `
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h4 class="text-primary">${completedTests}</h4>
                            <p class="mb-0">Executados</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-success">${passedTests}</h4>
                            <p class="mb-0">Passou</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-danger">${failedTests}</h4>
                            <p class="mb-0">Falhou</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-warning">${totalTests - completedTests}</h4>
                            <p class="mb-0">Pendentes</p>
                        </div>
                    </div>
                `;
            }
        }
        
        function testResponsive() {
            alert('Para testar responsividade:\n\n1. Pressione F12 para abrir DevTools\n2. Clique no ícone de dispositivo móvel\n3. Teste diferentes resoluções\n4. Verifique se todos os elementos se adaptam corretamente');
        }
    </script>
</body>
</html>