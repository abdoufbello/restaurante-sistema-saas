const http = require('http');
const path = require('path');
const fs = require('fs');
const url = require('url');

const PORT = 8000;

// Função para servir arquivos estáticos
function serveStaticFile(filePath, res) {
    const extname = path.extname(filePath).toLowerCase();
    const mimeTypes = {
        '.html': 'text/html',
        '.js': 'text/javascript',
        '.css': 'text/css',
        '.json': 'application/json',
        '.png': 'image/png',
        '.jpg': 'image/jpg',
        '.gif': 'image/gif',
        '.svg': 'image/svg+xml',
        '.wav': 'audio/wav',
        '.mp4': 'video/mp4',
        '.woff': 'application/font-woff',
        '.ttf': 'application/font-ttf',
        '.eot': 'application/vnd.ms-fontobject',
        '.otf': 'application/font-otf',
        '.wasm': 'application/wasm'
    };

    const contentType = mimeTypes[extname] || 'application/octet-stream';

    fs.readFile(filePath, (error, content) => {
        if (error) {
            if (error.code == 'ENOENT') {
                res.writeHead(404, { 'Content-Type': 'text/html' });
                res.end('404 - Arquivo não encontrado', 'utf-8');
            } else {
                res.writeHead(500);
                res.end('Erro interno do servidor: ' + error.code + ' .\n');
            }
        } else {
            res.writeHead(200, { 'Content-Type': contentType });
            res.end(content, 'utf-8');
        }
    });
}

const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;
    
    // Configurar CORS
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    
    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }

    // Rota para a página LGPD de exemplo
    if (pathname === '/lgpd/example' && req.method === 'GET') {
    const templatePath = path.join(__dirname, 'app', 'Views', 'templates', 'lgpd_example.php');
    
    if (fs.existsSync(templatePath)) {
        let content = fs.readFileSync(templatePath, 'utf8');
        
        // Substituir variáveis PHP por valores estáticos para demonstração
        content = content.replace(/<?php.*?\$.*?>/g, '');
        content = content.replace(/<\?= \$.*? \?>/g, 'Demonstração LGPD');
        content = content.replace(/<\?= base_url\('([^']+)'\) \?>/g, '/$1');
        content = content.replace(/<\?php.*?\?>/gs, ''); // Remove outras tags PHP
        
        res.writeHead(200, { 'Content-Type': 'text/html' });
        res.end(content);
    } else {
        res.writeHead(404, { 'Content-Type': 'text/html' });
        res.end('Template não encontrado');
    }
    return;
    }

    // Rota para política de privacidade
    if (pathname === '/lgpd/privacy-policy' && req.method === 'GET') {
        const htmlContent = `
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Política de Privacidade - LGPD</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="/assets/css/lgpd-consent.css">
        </head>
        <body>
            <div class="container mt-5">
                <h1>Política de Privacidade</h1>
                <div class="card">
                    <div class="card-body">
                        <h2>1. Informações Gerais</h2>
                        <p>Esta Política de Privacidade descreve como coletamos, usamos, armazenamos e protegemos suas informações pessoais em conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei 13.709/2018).</p>
                        
                        <h2>2. Dados Coletados</h2>
                        <p>Coletamos as seguintes categorias de dados pessoais:</p>
                        <ul>
                            <li><strong>Dados de identificação:</strong> nome, CPF, RG</li>
                            <li><strong>Dados de contato:</strong> e-mail, telefone, endereço</li>
                            <li><strong>Dados financeiros:</strong> informações de pagamento (quando aplicável)</li>
                            <li><strong>Dados de navegação:</strong> cookies, logs de acesso, endereço IP</li>
                        </ul>
                        
                        <h2>3. Seus Direitos</h2>
                        <p>Você tem direito a:</p>
                        <ul>
                            <li>Confirmação da existência de tratamento</li>
                            <li>Acesso aos dados</li>
                            <li>Correção de dados incompletos, inexatos ou desatualizados</li>
                            <li>Anonimização, bloqueio ou eliminação</li>
                            <li>Portabilidade dos dados</li>
                            <li>Eliminação dos dados tratados com consentimento</li>
                            <li>Revogação do consentimento</li>
                        </ul>
                        
                        <div class="mt-4">
                            <a href="/lgpd/example" class="btn btn-primary">Voltar ao Exemplo</a>
                            <a href="/lgpd/privacy-settings" class="btn btn-secondary">Configurações de Privacidade</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        `;
        res.writeHead(200, { 'Content-Type': 'text/html' });
        res.end(htmlContent);
        return;
    }

    // Rota para configurações de privacidade
    if (pathname === '/lgpd/privacy-settings' && req.method === 'GET') {
        const htmlContent = `
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configurações de Privacidade - LGPD</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="/assets/css/lgpd-consent.css">
        </head>
        <body>
            <div class="container mt-5">
                <h1>Configurações de Privacidade</h1>
                <div class="card">
                    <div class="card-body">
                        <h3>Gerenciar Consentimentos</h3>
                        
                        <div class="consent-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5>Cookies Essenciais</h5>
                                    <p class="text-muted">Necessários para o funcionamento básico do site</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" checked disabled>
                                    <label class="form-check-label">Sempre ativo</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="consent-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5>Analytics</h5>
                                    <p class="text-muted">Nos ajuda a melhorar o site analisando como você o usa</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="analytics-consent">
                                    <label class="form-check-label" for="analytics-consent"></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="consent-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5>Marketing</h5>
                                    <p class="text-muted">Personalizar anúncios e conteúdo baseado em seus interesses</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="marketing-consent">
                                    <label class="form-check-label" for="marketing-consent"></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button class="btn btn-primary" onclick="savePreferences()">Salvar Preferências</button>
                            <a href="/lgpd/example" class="btn btn-secondary">Voltar</a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h3>Seus Direitos</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-outline-primary w-100" onclick="requestDataExport()">Exportar Meus Dados</button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-outline-danger w-100" onclick="requestDataDeletion()">Solicitar Exclusão</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                function savePreferences() {
                    const analytics = document.getElementById('analytics-consent').checked;
                    const marketing = document.getElementById('marketing-consent').checked;
                    
                    // Simular salvamento
                    alert('Preferências salvas com sucesso!');
                    console.log('Analytics:', analytics, 'Marketing:', marketing);
                }
                
                function requestDataExport() {
                    alert('Solicitação de exportação de dados enviada. Você receberá um e-mail em até 72 horas.');
                }
                
                function requestDataDeletion() {
                    if (confirm('Tem certeza que deseja solicitar a exclusão de seus dados? Esta ação não pode ser desfeita.')) {
                        alert('Solicitação de exclusão enviada. Entraremos em contato para confirmar.');
                    }
                }
            </script>
        </body>
        </html>
        `;
        res.writeHead(200, { 'Content-Type': 'text/html' });
        res.end(htmlContent);
        return;
    }

    // Rota para dashboard de compliance
    if (pathname === '/lgpd/compliance-dashboard' && req.method === 'GET') {
        const htmlContent = `
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Dashboard de Compliance LGPD</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="/assets/css/lgpd-consent.css">
        </head>
        <body>
            <div class="container-fluid mt-3">
                <h1>Dashboard de Compliance LGPD</h1>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success">Status Geral</h5>
                                <h2 class="text-success">✓ Conforme</h2>
                                <p class="card-text">Sistema em compliance</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Consentimentos</h5>
                                <h2>1,247</h2>
                                <p class="card-text">Ativos este mês</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Solicitações</h5>
                                <h2>23</h2>
                                <p class="card-text">Direitos do titular</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Auditoria</h5>
                                <h2>5,892</h2>
                                <p class="card-text">Eventos registrados</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Consentimentos por Categoria</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Cookies Essenciais</span>
                                        <span>100%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: 100%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Analytics</span>
                                        <span>78%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" style="width: 78%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Marketing</span>
                                        <span>45%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" style="width: 45%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Atividades Recentes</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Consentimento registrado - Analytics</span>
                                        <small class="text-muted">2 min atrás</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Solicitação de exportação de dados</span>
                                        <small class="text-muted">15 min atrás</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Política de privacidade atualizada</span>
                                        <small class="text-muted">1 hora atrás</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Consentimento revogado - Marketing</span>
                                        <small class="text-muted">2 horas atrás</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="/lgpd/example" class="btn btn-primary">Voltar ao Exemplo</a>
                    <a href="/lgpd/privacy-policy" class="btn btn-secondary">Política de Privacidade</a>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        `;
        res.writeHead(200, { 'Content-Type': 'text/html' });
        res.end(htmlContent);
        return;
    }

    // APIs simuladas para demonstração
    if (pathname === '/api/lgpd/consent' && req.method === 'POST') {
        const response = { success: true, message: 'Consentimento registrado com sucesso' };
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify(response));
        return;
    }

    if (pathname.startsWith('/api/lgpd/consent/') && req.method === 'GET') {
        const response = {
            success: true,
            consents: {
                essential: { status: 'granted', granted_at: new Date().toISOString() },
                analytics: { status: 'granted', granted_at: new Date().toISOString() },
                marketing: { status: 'revoked', revoked_at: new Date().toISOString() }
            }
        };
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify(response));
        return;
    }

    if (pathname === '/api/lgpd/privacy-policy' && req.method === 'GET') {
        const response = {
            success: true,
            policy: {
                version: '1.0',
                title: 'Política de Privacidade',
                effective_date: new Date().toISOString(),
                content: 'Conteúdo da política de privacidade...'
            }
        };
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify(response));
        return;
    }

    // Servir arquivos estáticos da pasta public
     let filePath = path.join(__dirname, 'public', pathname);
     if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
         serveStaticFile(filePath, res);
         return;
     }
 
     // Rota não encontrada
     const notFoundHtml = `
     <!DOCTYPE html>
     <html lang="pt-BR">
     <head>
         <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <title>Página não encontrada</title>
         <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
     </head>
     <body>
         <div class="container mt-5 text-center">
             <h1>404 - Página não encontrada</h1>
             <p>A página que você está procurando não existe.</p>
             <a href="/lgpd/example" class="btn btn-primary">Ir para Exemplo LGPD</a>
         </div>
     </body>
     </html>
     `;
     res.writeHead(404, { 'Content-Type': 'text/html' });
     res.end(notFoundHtml);
 });

server.listen(PORT, () => {
    console.log(`Servidor rodando em http://localhost:${PORT}`);
    console.log('Páginas disponíveis:');
    console.log('- http://localhost:8000/lgpd/example');
    console.log('- http://localhost:8000/lgpd/privacy-policy');
    console.log('- http://localhost:8000/lgpd/privacy-settings');
    console.log('- http://localhost:8000/lgpd/compliance-dashboard');
});