/**
 * LGPD Consent Management System
 * 
 * Sistema de gerenciamento de consentimentos LGPD para frontend
 * Inclui banner de cookies, modal de preferências e integração com API
 */

class LGPDConsentManager {
    constructor(options = {}) {
        this.options = {
            apiBaseUrl: '/api/lgpd',
            cookieName: 'lgpd_consent',
            cookieExpiry: 365, // dias
            showBanner: true,
            position: 'bottom', // 'top' ou 'bottom'
            theme: 'light', // 'light' ou 'dark'
            language: 'pt-BR',
            autoShow: true,
            ...options
        };
        
        this.consentData = this.loadConsentData();
        this.translations = this.getTranslations();
        
        if (this.options.autoShow) {
            this.init();
        }
    }
    
    /**
     * Inicializa o sistema de consentimento
     */
    init() {
        this.createStyles();
        
        if (this.shouldShowBanner()) {
            this.showConsentBanner();
        }
        
        this.bindEvents();
        this.checkConsentExpiry();
    }
    
    /**
     * Verifica se deve mostrar o banner
     */
    shouldShowBanner() {
        if (!this.options.showBanner) return false;
        
        // Verifica se já existe consentimento válido
        const consent = this.getConsent();
        return !consent || this.isConsentExpired(consent);
    }
    
    /**
     * Cria os estilos CSS necessários
     */
    createStyles() {
        if (document.getElementById('lgpd-styles')) return;
        
        const styles = `
            <style id="lgpd-styles">
                .lgpd-banner {
                    position: fixed;
                    left: 0;
                    right: 0;
                    z-index: 9999;
                    background: ${this.options.theme === 'dark' ? '#2c3e50' : '#ffffff'};
                    color: ${this.options.theme === 'dark' ? '#ffffff' : '#333333'};
                    padding: 20px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    border-top: ${this.options.position === 'bottom' ? '1px solid #e0e0e0' : 'none'};
                    border-bottom: ${this.options.position === 'top' ? '1px solid #e0e0e0' : 'none'};
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    font-size: 14px;
                    line-height: 1.5;
                    transform: translateY(${this.options.position === 'bottom' ? '100%' : '-100%'});
                    transition: transform 0.3s ease-in-out;
                }
                
                .lgpd-banner.show {
                    transform: translateY(0);
                }
                
                .lgpd-banner.bottom {
                    bottom: 0;
                }
                
                .lgpd-banner.top {
                    top: 0;
                }
                
                .lgpd-banner-content {
                    max-width: 1200px;
                    margin: 0 auto;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 15px;
                }
                
                .lgpd-banner-text {
                    flex: 1;
                    min-width: 300px;
                }
                
                .lgpd-banner-actions {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                
                .lgpd-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.2s ease;
                    min-width: 100px;
                    text-align: center;
                }
                
                .lgpd-btn-primary {
                    background: #007bff;
                    color: white;
                }
                
                .lgpd-btn-primary:hover {
                    background: #0056b3;
                }
                
                .lgpd-btn-secondary {
                    background: transparent;
                    color: ${this.options.theme === 'dark' ? '#ffffff' : '#007bff'};
                    border: 1px solid ${this.options.theme === 'dark' ? '#ffffff' : '#007bff'};
                }
                
                .lgpd-btn-secondary:hover {
                    background: ${this.options.theme === 'dark' ? '#ffffff' : '#007bff'};
                    color: ${this.options.theme === 'dark' ? '#2c3e50' : '#ffffff'};
                }
                
                .lgpd-btn-success {
                    background: #28a745;
                    color: white;
                }
                
                .lgpd-btn-success:hover {
                    background: #1e7e34;
                }
                
                .lgpd-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    z-index: 10000;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                
                .lgpd-modal.show {
                    display: flex;
                }
                
                .lgpd-modal-content {
                    background: white;
                    border-radius: 10px;
                    padding: 30px;
                    max-width: 600px;
                    width: 100%;
                    max-height: 80vh;
                    overflow-y: auto;
                    position: relative;
                }
                
                .lgpd-modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #e0e0e0;
                }
                
                .lgpd-modal-title {
                    font-size: 20px;
                    font-weight: 600;
                    margin: 0;
                    color: #333;
                }
                
                .lgpd-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: #999;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .lgpd-close:hover {
                    color: #333;
                }
                
                .lgpd-consent-group {
                    margin-bottom: 20px;
                    padding: 15px;
                    border: 1px solid #e0e0e0;
                    border-radius: 5px;
                }
                
                .lgpd-consent-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }
                
                .lgpd-consent-title {
                    font-weight: 600;
                    color: #333;
                }
                
                .lgpd-toggle {
                    position: relative;
                    display: inline-block;
                    width: 50px;
                    height: 24px;
                }
                
                .lgpd-toggle input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                
                .lgpd-slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .4s;
                    border-radius: 24px;
                }
                
                .lgpd-slider:before {
                    position: absolute;
                    content: "";
                    height: 18px;
                    width: 18px;
                    left: 3px;
                    bottom: 3px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }
                
                input:checked + .lgpd-slider {
                    background-color: #007bff;
                }
                
                input:checked + .lgpd-slider:before {
                    transform: translateX(26px);
                }
                
                .lgpd-consent-description {
                    color: #666;
                    font-size: 13px;
                    line-height: 1.4;
                }
                
                .lgpd-required {
                    color: #28a745;
                    font-size: 12px;
                    font-weight: 500;
                }
                
                @media (max-width: 768px) {
                    .lgpd-banner-content {
                        flex-direction: column;
                        text-align: center;
                    }
                    
                    .lgpd-banner-actions {
                        width: 100%;
                        justify-content: center;
                    }
                    
                    .lgpd-modal-content {
                        margin: 10px;
                        padding: 20px;
                    }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
    }
    
    /**
     * Mostra o banner de consentimento
     */
    showConsentBanner() {
        if (document.getElementById('lgpd-banner')) return;
        
        const banner = this.createBannerHTML();
        document.body.insertAdjacentHTML('beforeend', banner);
        
        // Anima a entrada do banner
        setTimeout(() => {
            const bannerEl = document.getElementById('lgpd-banner');
            if (bannerEl) {
                bannerEl.classList.add('show');
            }
        }, 100);
    }
    
    /**
     * Cria o HTML do banner
     */
    createBannerHTML() {
        const t = this.translations;
        
        return `
            <div id="lgpd-banner" class="lgpd-banner ${this.options.position}">
                <div class="lgpd-banner-content">
                    <div class="lgpd-banner-text">
                        <strong>${t.bannerTitle}</strong><br>
                        ${t.bannerText}
                        <a href="#" onclick="lgpdConsent.showPrivacyPolicy(); return false;">${t.privacyPolicy}</a>
                    </div>
                    <div class="lgpd-banner-actions">
                        <button class="lgpd-btn lgpd-btn-secondary" onclick="lgpdConsent.showPreferences()">
                            ${t.managePreferences}
                        </button>
                        <button class="lgpd-btn lgpd-btn-success" onclick="lgpdConsent.acceptAll()">
                            ${t.acceptAll}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Mostra o modal de preferências
     */
    showPreferences() {
        if (document.getElementById('lgpd-preferences-modal')) {
            document.getElementById('lgpd-preferences-modal').classList.add('show');
            return;
        }
        
        const modal = this.createPreferencesModal();
        document.body.insertAdjacentHTML('beforeend', modal);
        document.getElementById('lgpd-preferences-modal').classList.add('show');
    }
    
    /**
     * Cria o modal de preferências
     */
    createPreferencesModal() {
        const t = this.translations;
        const currentConsent = this.getConsent() || {};
        
        const consentTypes = [
            {
                id: 'necessary',
                title: t.necessaryCookies,
                description: t.necessaryDescription,
                required: true,
                checked: true
            },
            {
                id: 'analytics',
                title: t.analyticsCookies,
                description: t.analyticsDescription,
                required: false,
                checked: currentConsent.analytics !== false
            },
            {
                id: 'marketing',
                title: t.marketingCookies,
                description: t.marketingDescription,
                required: false,
                checked: currentConsent.marketing !== false
            },
            {
                id: 'personalization',
                title: t.personalizationCookies,
                description: t.personalizationDescription,
                required: false,
                checked: currentConsent.personalization !== false
            }
        ];
        
        const consentGroupsHTML = consentTypes.map(type => `
            <div class="lgpd-consent-group">
                <div class="lgpd-consent-header">
                    <div class="lgpd-consent-title">${type.title}</div>
                    <div>
                        ${type.required ? 
                            `<span class="lgpd-required">${t.required}</span>` :
                            `<label class="lgpd-toggle">
                                <input type="checkbox" id="consent-${type.id}" ${type.checked ? 'checked' : ''}>
                                <span class="lgpd-slider"></span>
                            </label>`
                        }
                    </div>
                </div>
                <div class="lgpd-consent-description">${type.description}</div>
            </div>
        `).join('');
        
        return `
            <div id="lgpd-preferences-modal" class="lgpd-modal">
                <div class="lgpd-modal-content">
                    <div class="lgpd-modal-header">
                        <h3 class="lgpd-modal-title">${t.preferencesTitle}</h3>
                        <button class="lgpd-close" onclick="lgpdConsent.closePreferences()">&times;</button>
                    </div>
                    <div class="lgpd-modal-body">
                        <p>${t.preferencesDescription}</p>
                        ${consentGroupsHTML}
                    </div>
                    <div class="lgpd-banner-actions" style="margin-top: 20px; justify-content: flex-end;">
                        <button class="lgpd-btn lgpd-btn-secondary" onclick="lgpdConsent.rejectAll()">
                            ${t.rejectAll}
                        </button>
                        <button class="lgpd-btn lgpd-btn-primary" onclick="lgpdConsent.savePreferences()">
                            ${t.savePreferences}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Fecha o modal de preferências
     */
    closePreferences() {
        const modal = document.getElementById('lgpd-preferences-modal');
        if (modal) {
            modal.classList.remove('show');
        }
    }
    
    /**
     * Aceita todos os cookies
     */
    async acceptAll() {
        const consent = {
            necessary: true,
            analytics: true,
            marketing: true,
            personalization: true,
            timestamp: new Date().toISOString(),
            method: 'banner_accept_all'
        };
        
        await this.saveConsent(consent);
        this.hideBanner();
        this.triggerConsentEvent('accept_all', consent);
    }
    
    /**
     * Rejeita todos os cookies opcionais
     */
    async rejectAll() {
        const consent = {
            necessary: true,
            analytics: false,
            marketing: false,
            personalization: false,
            timestamp: new Date().toISOString(),
            method: 'preferences_reject_all'
        };
        
        await this.saveConsent(consent);
        this.hideBanner();
        this.closePreferences();
        this.triggerConsentEvent('reject_all', consent);
    }
    
    /**
     * Salva as preferências personalizadas
     */
    async savePreferences() {
        const consent = {
            necessary: true,
            analytics: document.getElementById('consent-analytics')?.checked || false,
            marketing: document.getElementById('consent-marketing')?.checked || false,
            personalization: document.getElementById('consent-personalization')?.checked || false,
            timestamp: new Date().toISOString(),
            method: 'preferences_custom'
        };
        
        await this.saveConsent(consent);
        this.hideBanner();
        this.closePreferences();
        this.triggerConsentEvent('save_preferences', consent);
    }
    
    /**
     * Salva o consentimento
     */
    async saveConsent(consent) {
        // Salva no localStorage
        this.setConsent(consent);
        
        // Envia para a API
        try {
            await this.sendConsentToAPI(consent);
        } catch (error) {
            console.warn('Erro ao enviar consentimento para API:', error);
        }
        
        // Atualiza scripts baseados no consentimento
        this.updateScripts(consent);
    }
    
    /**
     * Envia consentimento para a API
     */
    async sendConsentToAPI(consent) {
        const dataSubject = this.getDataSubject();
        if (!dataSubject) return;
        
        const consentData = {
            data_subject: dataSubject,
            consent_type: 'cookies',
            purpose: 'Cookies e rastreamento do website',
            legal_basis: 'consent',
            metadata: consent,
            ip_address: await this.getClientIP(),
            user_agent: navigator.userAgent,
            method: consent.method
        };
        
        const response = await fetch(`${this.options.apiBaseUrl}/consent`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(consentData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    }
    
    /**
     * Obtém o identificador do titular dos dados
     */
    getDataSubject() {
        // Tenta obter do usuário logado
        const userEmail = this.getUserEmail();
        if (userEmail) return userEmail;
        
        // Gera um ID único para visitantes anônimos
        let anonymousId = localStorage.getItem('lgpd_anonymous_id');
        if (!anonymousId) {
            anonymousId = 'anon_' + this.generateUUID();
            localStorage.setItem('lgpd_anonymous_id', anonymousId);
        }
        
        return anonymousId;
    }
    
    /**
     * Obtém o email do usuário logado
     */
    getUserEmail() {
        // Implementar baseado no sistema de autenticação
        // Exemplo: return window.currentUser?.email;
        return null;
    }
    
    /**
     * Obtém o IP do cliente
     */
    async getClientIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            return data.ip;
        } catch {
            return null;
        }
    }
    
    /**
     * Gera UUID v4
     */
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    
    /**
     * Atualiza scripts baseados no consentimento
     */
    updateScripts(consent) {
        // Google Analytics
        if (consent.analytics) {
            this.loadGoogleAnalytics();
        } else {
            this.disableGoogleAnalytics();
        }
        
        // Scripts de marketing
        if (consent.marketing) {
            this.loadMarketingScripts();
        } else {
            this.disableMarketingScripts();
        }
        
        // Scripts de personalização
        if (consent.personalization) {
            this.loadPersonalizationScripts();
        } else {
            this.disablePersonalizationScripts();
        }
    }
    
    /**
     * Carrega Google Analytics
     */
    loadGoogleAnalytics() {
        if (window.gtag || !window.GA_MEASUREMENT_ID) return;
        
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${window.GA_MEASUREMENT_ID}`;
        document.head.appendChild(script);
        
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', window.GA_MEASUREMENT_ID);
        window.gtag = gtag;
    }
    
    /**
     * Desabilita Google Analytics
     */
    disableGoogleAnalytics() {
        if (window.GA_MEASUREMENT_ID) {
            window[`ga-disable-${window.GA_MEASUREMENT_ID}`] = true;
        }
    }
    
    /**
     * Carrega scripts de marketing
     */
    loadMarketingScripts() {
        // Implementar conforme necessário
        // Facebook Pixel, etc.
    }
    
    /**
     * Desabilita scripts de marketing
     */
    disableMarketingScripts() {
        // Implementar conforme necessário
    }
    
    /**
     * Carrega scripts de personalização
     */
    loadPersonalizationScripts() {
        // Implementar conforme necessário
    }
    
    /**
     * Desabilita scripts de personalização
     */
    disablePersonalizationScripts() {
        // Implementar conforme necessário
    }
    
    /**
     * Esconde o banner
     */
    hideBanner() {
        const banner = document.getElementById('lgpd-banner');
        if (banner) {
            banner.classList.remove('show');
            setTimeout(() => banner.remove(), 300);
        }
    }
    
    /**
     * Dispara evento de consentimento
     */
    triggerConsentEvent(action, consent) {
        const event = new CustomEvent('lgpdConsent', {
            detail: { action, consent }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Vincula eventos
     */
    bindEvents() {
        // Fecha modal ao clicar fora
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('lgpd-modal')) {
                this.closePreferences();
            }
        });
        
        // Tecla ESC fecha modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closePreferences();
            }
        });
    }
    
    /**
     * Verifica expiração do consentimento
     */
    checkConsentExpiry() {
        const consent = this.getConsent();
        if (consent && this.isConsentExpired(consent)) {
            this.clearConsent();
            if (this.options.showBanner) {
                this.showConsentBanner();
            }
        }
    }
    
    /**
     * Verifica se o consentimento expirou
     */
    isConsentExpired(consent) {
        if (!consent.timestamp) return true;
        
        const consentDate = new Date(consent.timestamp);
        const expiryDate = new Date(consentDate.getTime() + (this.options.cookieExpiry * 24 * 60 * 60 * 1000));
        
        return new Date() > expiryDate;
    }
    
    /**
     * Obtém consentimento do localStorage
     */
    getConsent() {
        try {
            const consent = localStorage.getItem(this.options.cookieName);
            return consent ? JSON.parse(consent) : null;
        } catch {
            return null;
        }
    }
    
    /**
     * Salva consentimento no localStorage
     */
    setConsent(consent) {
        localStorage.setItem(this.options.cookieName, JSON.stringify(consent));
    }
    
    /**
     * Remove consentimento
     */
    clearConsent() {
        localStorage.removeItem(this.options.cookieName);
    }
    
    /**
     * Carrega dados de consentimento
     */
    loadConsentData() {
        return this.getConsent() || {};
    }
    
    /**
     * Mostra política de privacidade
     */
    async showPrivacyPolicy() {
        try {
            const response = await fetch(`${this.options.apiBaseUrl}/privacy-policy`);
            const data = await response.json();
            
            if (data.success && data.data) {
                this.showPrivacyPolicyModal(data.data);
            } else {
                // Fallback para página de política
                window.open('/privacy-policy', '_blank');
            }
        } catch (error) {
            console.error('Erro ao carregar política de privacidade:', error);
            window.open('/privacy-policy', '_blank');
        }
    }
    
    /**
     * Mostra modal da política de privacidade
     */
    showPrivacyPolicyModal(policy) {
        const modal = `
            <div id="lgpd-policy-modal" class="lgpd-modal show">
                <div class="lgpd-modal-content" style="max-width: 800px;">
                    <div class="lgpd-modal-header">
                        <h3 class="lgpd-modal-title">${policy.title}</h3>
                        <button class="lgpd-close" onclick="document.getElementById('lgpd-policy-modal').remove()">&times;</button>
                    </div>
                    <div class="lgpd-modal-body">
                        <div style="max-height: 60vh; overflow-y: auto;">
                            ${policy.content}
                        </div>
                        <p style="margin-top: 20px; font-size: 12px; color: #666;">
                            Versão: ${policy.version} | Vigência: ${new Date(policy.effective_date).toLocaleDateString('pt-BR')}
                        </p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modal);
    }
    
    /**
     * Obtém traduções
     */
    getTranslations() {
        const translations = {
            'pt-BR': {
                bannerTitle: 'Utilizamos cookies',
                bannerText: 'Este site utiliza cookies para melhorar sua experiência de navegação e personalizar conteúdo. Ao continuar navegando, você concorda com nossa ',
                privacyPolicy: 'Política de Privacidade',
                managePreferences: 'Gerenciar Preferências',
                acceptAll: 'Aceitar Todos',
                rejectAll: 'Rejeitar Todos',
                savePreferences: 'Salvar Preferências',
                preferencesTitle: 'Preferências de Cookies',
                preferencesDescription: 'Personalize suas preferências de cookies. Você pode alterar essas configurações a qualquer momento.',
                necessaryCookies: 'Cookies Necessários',
                necessaryDescription: 'Essenciais para o funcionamento básico do site. Não podem ser desabilitados.',
                analyticsCookies: 'Cookies de Análise',
                analyticsDescription: 'Nos ajudam a entender como você usa o site para melhorarmos a experiência.',
                marketingCookies: 'Cookies de Marketing',
                marketingDescription: 'Utilizados para personalizar anúncios e medir a eficácia de campanhas publicitárias.',
                personalizationCookies: 'Cookies de Personalização',
                personalizationDescription: 'Permitem personalizar conteúdo e funcionalidades baseadas em suas preferências.',
                required: 'Obrigatório'
            },
            'en': {
                bannerTitle: 'We use cookies',
                bannerText: 'This website uses cookies to improve your browsing experience and personalize content. By continuing to browse, you agree to our ',
                privacyPolicy: 'Privacy Policy',
                managePreferences: 'Manage Preferences',
                acceptAll: 'Accept All',
                rejectAll: 'Reject All',
                savePreferences: 'Save Preferences',
                preferencesTitle: 'Cookie Preferences',
                preferencesDescription: 'Customize your cookie preferences. You can change these settings at any time.',
                necessaryCookies: 'Necessary Cookies',
                necessaryDescription: 'Essential for basic site functionality. Cannot be disabled.',
                analyticsCookies: 'Analytics Cookies',
                analyticsDescription: 'Help us understand how you use the site so we can improve the experience.',
                marketingCookies: 'Marketing Cookies',
                marketingDescription: 'Used to personalize ads and measure the effectiveness of advertising campaigns.',
                personalizationCookies: 'Personalization Cookies',
                personalizationDescription: 'Allow personalization of content and features based on your preferences.',
                required: 'Required'
            }
        };
        
        return translations[this.options.language] || translations['pt-BR'];
    }
    
    /**
     * Métodos públicos para integração
     */
    
    // Verifica se tem consentimento para um tipo específico
    hasConsent(type) {
        const consent = this.getConsent();
        return consent && consent[type] === true;
    }
    
    // Revoga consentimento
    async revokeConsent(type = null) {
        if (type) {
            const consent = this.getConsent() || {};
            consent[type] = false;
            consent.timestamp = new Date().toISOString();
            await this.saveConsent(consent);
        } else {
            this.clearConsent();
        }
        
        this.triggerConsentEvent('revoke', { type });
    }
    
    // Mostra novamente o banner
    showBanner() {
        this.showConsentBanner();
    }
}

// Inicialização global
window.LGPDConsentManager = LGPDConsentManager;

// Instância global
window.lgpdConsent = new LGPDConsentManager({
    // Configurações podem ser sobrescritas
});

// Auto-inicialização quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Já inicializado no construtor
    });
}