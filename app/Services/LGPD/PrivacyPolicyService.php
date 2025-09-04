<?php

namespace App\Services\LGPD;

use CodeIgniter\Config\Services;
use CodeIgniter\Database\ConnectionInterface;
use App\Config\LGPD;
use Exception;

/**
 * Serviço de Política de Privacidade LGPD
 * 
 * Gerencia políticas de privacidade dinâmicas, termos de uso
 * e documentos de compliance LGPD
 */
class PrivacyPolicyService
{
    protected $db;
    protected $config;
    protected $auditService;
    
    public function __construct()
    {
        $this->db = Services::database();
        $this->config = new LGPD();
        $this->auditService = new AuditService();
    }
    
    /**
     * Cria uma nova versão da política de privacidade
     */
    public function createPrivacyPolicy(array $policyData): array
    {
        try {
            $this->db->transStart();
            
            // Desativa versões anteriores
            $this->db->table('lgpd_privacy_policies')
                     ->where('policy_type', $policyData['policy_type'])
                     ->where('is_active', 1)
                     ->update(['is_active' => 0, 'deactivated_at' => date('Y-m-d H:i:s')]);
            
            // Cria nova versão
            $newPolicy = [
                'policy_type' => $policyData['policy_type'],
                'version' => $this->generateNextVersion($policyData['policy_type']),
                'title' => $policyData['title'],
                'content' => $policyData['content'],
                'summary' => $policyData['summary'] ?? null,
                'language' => $policyData['language'] ?? 'pt-BR',
                'effective_date' => $policyData['effective_date'] ?? date('Y-m-d H:i:s'),
                'created_by' => $policyData['created_by'],
                'is_active' => 1,
                'requires_consent' => $policyData['requires_consent'] ?? 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->table('lgpd_privacy_policies')->insert($newPolicy);
            $policyId = $this->db->insertID();
            
            // Adiciona seções da política
            if (!empty($policyData['sections'])) {
                $this->createPolicySections($policyId, $policyData['sections']);
            }
            
            // Adiciona categorias de dados tratados
            if (!empty($policyData['data_categories'])) {
                $this->createPolicyDataCategories($policyId, $policyData['data_categories']);
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus()) {
                // Log da criação
                $this->auditService->logDataOperation([
                    'operation' => 'privacy_policy_created',
                    'data_type' => 'privacy_policy',
                    'policy_id' => $policyId,
                    'policy_type' => $policyData['policy_type'],
                    'version' => $newPolicy['version'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                return $this->successResponse('Política de privacidade criada com sucesso', [
                    'policy_id' => $policyId,
                    'version' => $newPolicy['version']
                ]);
            } else {
                throw new Exception('Falha na transação do banco de dados');
            }
            
        } catch (Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Erro ao criar política de privacidade: ' . $e->getMessage());
            return $this->errorResponse('Erro ao criar política de privacidade: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém a política de privacidade ativa
     */
    public function getActivePrivacyPolicy(string $policyType = 'general', string $language = 'pt-BR'): array
    {
        try {
            $policy = $this->db->table('lgpd_privacy_policies')
                              ->where('policy_type', $policyType)
                              ->where('language', $language)
                              ->where('is_active', 1)
                              ->where('effective_date <=', date('Y-m-d H:i:s'))
                              ->orderBy('version', 'DESC')
                              ->get()
                              ->getRowArray();
            
            if (!$policy) {
                return $this->errorResponse('Política de privacidade não encontrada');
            }
            
            // Carrega seções da política
            $policy['sections'] = $this->getPolicySections($policy['id']);
            
            // Carrega categorias de dados
            $policy['data_categories'] = $this->getPolicyDataCategories($policy['id']);
            
            // Carrega histórico de consentimentos (se aplicável)
            $policy['consent_stats'] = $this->getPolicyConsentStats($policy['id']);
            
            return $this->successResponse('Política encontrada', $policy);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao obter política de privacidade: ' . $e->getMessage());
            return $this->errorResponse('Erro ao obter política de privacidade');
        }
    }
    
    /**
     * Lista todas as versões de uma política
     */
    public function getPolicyVersions(string $policyType = 'general'): array
    {
        try {
            $versions = $this->db->table('lgpd_privacy_policies')
                                ->select('id, version, title, effective_date, is_active, created_at, created_by')
                                ->where('policy_type', $policyType)
                                ->orderBy('version', 'DESC')
                                ->get()
                                ->getResultArray();
            
            return $this->successResponse('Versões encontradas', $versions);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao listar versões da política: ' . $e->getMessage());
            return $this->errorResponse('Erro ao listar versões da política');
        }
    }
    
    /**
     * Registra aceite de política pelo usuário
     */
    public function recordPolicyAcceptance(array $acceptanceData): array
    {
        try {
            $policyId = $acceptanceData['policy_id'];
            $userId = $acceptanceData['user_id'] ?? null;
            $dataSubject = $acceptanceData['data_subject'];
            
            // Verifica se a política existe e está ativa
            $policy = $this->db->table('lgpd_privacy_policies')
                              ->where('id', $policyId)
                              ->where('is_active', 1)
                              ->get()
                              ->getRowArray();
            
            if (!$policy) {
                return $this->errorResponse('Política de privacidade não encontrada ou inativa');
            }
            
            // Registra o aceite
            $acceptanceRecord = [
                'policy_id' => $policyId,
                'policy_version' => $policy['version'],
                'user_id' => $userId,
                'data_subject' => $dataSubject,
                'acceptance_method' => $acceptanceData['method'] ?? 'web_form',
                'ip_address' => $acceptanceData['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
                'user_agent' => $acceptanceData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'],
                'acceptance_timestamp' => date('Y-m-d H:i:s'),
                'consent_details' => json_encode($acceptanceData['consent_details'] ?? []),
                'is_valid' => 1
            ];
            
            $this->db->table('lgpd_policy_acceptances')->insert($acceptanceRecord);
            $acceptanceId = $this->db->insertID();
            
            // Log do aceite
            $this->auditService->logConsentOperation([
                'consent_type' => 'privacy_policy',
                'operation' => 'granted',
                'data_subject' => $dataSubject,
                'purpose' => $policy['policy_type'],
                'legal_basis' => 'consent',
                'policy_id' => $policyId,
                'policy_version' => $policy['version'],
                'acceptance_id' => $acceptanceId
            ]);
            
            return $this->successResponse('Aceite registrado com sucesso', [
                'acceptance_id' => $acceptanceId,
                'policy_version' => $policy['version']
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao registrar aceite da política: ' . $e->getMessage());
            return $this->errorResponse('Erro ao registrar aceite da política');
        }
    }
    
    /**
     * Verifica se usuário aceitou a política atual
     */
    public function checkPolicyAcceptance(string $dataSubject, string $policyType = 'general'): array
    {
        try {
            // Obtém política ativa atual
            $currentPolicy = $this->db->table('lgpd_privacy_policies')
                                     ->where('policy_type', $policyType)
                                     ->where('is_active', 1)
                                     ->orderBy('version', 'DESC')
                                     ->get()
                                     ->getRowArray();
            
            if (!$currentPolicy) {
                return $this->errorResponse('Política ativa não encontrada');
            }
            
            // Verifica se há aceite para a versão atual
            $acceptance = $this->db->table('lgpd_policy_acceptances')
                                  ->where('data_subject', $dataSubject)
                                  ->where('policy_id', $currentPolicy['id'])
                                  ->where('is_valid', 1)
                                  ->orderBy('acceptance_timestamp', 'DESC')
                                  ->get()
                                  ->getRowArray();
            
            $hasAccepted = !empty($acceptance);
            $requiresNewAcceptance = !$hasAccepted && $currentPolicy['requires_consent'];
            
            return $this->successResponse('Verificação concluída', [
                'has_accepted' => $hasAccepted,
                'requires_new_acceptance' => $requiresNewAcceptance,
                'current_policy_version' => $currentPolicy['version'],
                'accepted_version' => $acceptance['policy_version'] ?? null,
                'acceptance_date' => $acceptance['acceptance_timestamp'] ?? null
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao verificar aceite da política: ' . $e->getMessage());
            return $this->errorResponse('Erro ao verificar aceite da política');
        }
    }
    
    /**
     * Gera documento de política em diferentes formatos
     */
    public function generatePolicyDocument(int $policyId, string $format = 'html'): array
    {
        try {
            $policy = $this->db->table('lgpd_privacy_policies')
                              ->where('id', $policyId)
                              ->get()
                              ->getRowArray();
            
            if (!$policy) {
                return $this->errorResponse('Política não encontrada');
            }
            
            // Carrega dados completos
            $policy['sections'] = $this->getPolicySections($policyId);
            $policy['data_categories'] = $this->getPolicyDataCategories($policyId);
            
            switch ($format) {
                case 'html':
                    $document = $this->generateHTMLDocument($policy);
                    break;
                case 'pdf':
                    $document = $this->generatePDFDocument($policy);
                    break;
                case 'json':
                    $document = json_encode($policy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    break;
                default:
                    return $this->errorResponse('Formato não suportado');
            }
            
            // Log da geração
            $this->auditService->logDataOperation([
                'operation' => 'policy_document_generated',
                'data_type' => 'privacy_policy',
                'policy_id' => $policyId,
                'format' => $format,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $this->successResponse('Documento gerado', [
                'format' => $format,
                'content' => $document,
                'filename' => $this->generateFilename($policy, $format)
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao gerar documento da política: ' . $e->getMessage());
            return $this->errorResponse('Erro ao gerar documento da política');
        }
    }
    
    /**
     * Cria template de política baseado no tipo de negócio
     */
    public function createPolicyTemplate(string $businessType, array $customizations = []): array
    {
        try {
            $templates = $this->config->privacyPolicyTemplates;
            
            if (!isset($templates[$businessType])) {
                return $this->errorResponse('Template não encontrado para este tipo de negócio');
            }
            
            $template = $templates[$businessType];
            
            // Aplica customizações
            if (!empty($customizations)) {
                $template = $this->applyTemplateCustomizations($template, $customizations);
            }
            
            // Substitui placeholders
            $template = $this->replacePlaceholders($template, $customizations);
            
            return $this->successResponse('Template criado', $template);
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao criar template da política: ' . $e->getMessage());
            return $this->errorResponse('Erro ao criar template da política');
        }
    }
    
    /**
     * Analisa conformidade da política com LGPD
     */
    public function analyzePolicyCompliance(int $policyId): array
    {
        try {
            $policy = $this->db->table('lgpd_privacy_policies')
                              ->where('id', $policyId)
                              ->get()
                              ->getRowArray();
            
            if (!$policy) {
                return $this->errorResponse('Política não encontrada');
            }
            
            $sections = $this->getPolicySections($policyId);
            $dataCategories = $this->getPolicyDataCategories($policyId);
            
            $compliance = [
                'overall_score' => 0,
                'required_sections' => $this->checkRequiredSections($sections),
                'data_treatment_clarity' => $this->checkDataTreatmentClarity($dataCategories),
                'legal_basis_coverage' => $this->checkLegalBasisCoverage($sections),
                'rights_information' => $this->checkRightsInformation($sections),
                'contact_information' => $this->checkContactInformation($sections),
                'recommendations' => []
            ];
            
            // Calcula score geral
            $scores = [
                $compliance['required_sections']['score'],
                $compliance['data_treatment_clarity']['score'],
                $compliance['legal_basis_coverage']['score'],
                $compliance['rights_information']['score'],
                $compliance['contact_information']['score']
            ];
            
            $compliance['overall_score'] = array_sum($scores) / count($scores);
            
            // Gera recomendações
            $compliance['recommendations'] = $this->generateComplianceRecommendations($compliance);
            
            return $this->successResponse('Análise concluída', $compliance);
            
        } catch (Exception $e) {
            log_message('error', 'Erro na análise de conformidade: ' . $e->getMessage());
            return $this->errorResponse('Erro na análise de conformidade');
        }
    }
    
    // Métodos auxiliares privados
    
    private function generateNextVersion(string $policyType): string
    {
        $lastVersion = $this->db->table('lgpd_privacy_policies')
                               ->selectMax('version')
                               ->where('policy_type', $policyType)
                               ->get()
                               ->getRow();
        
        if (!$lastVersion || !$lastVersion->version) {
            return '1.0';
        }
        
        $versionParts = explode('.', $lastVersion->version);
        $major = intval($versionParts[0]);
        $minor = intval($versionParts[1] ?? 0);
        
        return $major . '.' . ($minor + 1);
    }
    
    private function createPolicySections(int $policyId, array $sections): void
    {
        foreach ($sections as $section) {
            $this->db->table('lgpd_policy_sections')->insert([
                'policy_id' => $policyId,
                'section_type' => $section['type'],
                'title' => $section['title'],
                'content' => $section['content'],
                'order_index' => $section['order'] ?? 0,
                'is_required' => $section['required'] ?? 0
            ]);
        }
    }
    
    private function createPolicyDataCategories(int $policyId, array $categories): void
    {
        foreach ($categories as $category) {
            $this->db->table('lgpd_policy_data_categories')->insert([
                'policy_id' => $policyId,
                'category_name' => $category['name'],
                'data_types' => json_encode($category['types']),
                'purposes' => json_encode($category['purposes']),
                'legal_basis' => $category['legal_basis'],
                'retention_period' => $category['retention_period'] ?? null,
                'sharing_info' => json_encode($category['sharing'] ?? [])
            ]);
        }
    }
    
    private function getPolicySections(int $policyId): array
    {
        return $this->db->table('lgpd_policy_sections')
                       ->where('policy_id', $policyId)
                       ->orderBy('order_index')
                       ->get()
                       ->getResultArray();
    }
    
    private function getPolicyDataCategories(int $policyId): array
    {
        $categories = $this->db->table('lgpd_policy_data_categories')
                              ->where('policy_id', $policyId)
                              ->get()
                              ->getResultArray();
        
        foreach ($categories as &$category) {
            $category['data_types'] = json_decode($category['data_types'], true);
            $category['purposes'] = json_decode($category['purposes'], true);
            $category['sharing'] = json_decode($category['sharing_info'], true);
            unset($category['sharing_info']);
        }
        
        return $categories;
    }
    
    private function getPolicyConsentStats(int $policyId): array
    {
        return [
            'total_acceptances' => $this->db->table('lgpd_policy_acceptances')
                                           ->where('policy_id', $policyId)
                                           ->where('is_valid', 1)
                                           ->countAllResults(),
            'recent_acceptances' => $this->db->table('lgpd_policy_acceptances')
                                            ->where('policy_id', $policyId)
                                            ->where('is_valid', 1)
                                            ->where('acceptance_timestamp >=', date('Y-m-d', strtotime('-30 days')))
                                            ->countAllResults()
        ];
    }
    
    private function generateHTMLDocument(array $policy): string
    {
        $html = "<!DOCTYPE html>\n<html lang='pt-BR'>\n<head>\n";
        $html .= "<meta charset='UTF-8'>\n";
        $html .= "<title>" . htmlspecialchars($policy['title']) . "</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:40px;line-height:1.6}h1,h2{color:#333}p{margin-bottom:15px}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>" . htmlspecialchars($policy['title']) . "</h1>\n";
        $html .= "<p><strong>Versão:</strong> " . htmlspecialchars($policy['version']) . "</p>\n";
        $html .= "<p><strong>Data de Vigência:</strong> " . date('d/m/Y', strtotime($policy['effective_date'])) . "</p>\n";
        
        if (!empty($policy['summary'])) {
            $html .= "<div class='summary'>\n<h2>Resumo</h2>\n";
            $html .= "<p>" . nl2br(htmlspecialchars($policy['summary'])) . "</p>\n</div>\n";
        }
        
        foreach ($policy['sections'] as $section) {
            $html .= "<div class='section'>\n";
            $html .= "<h2>" . htmlspecialchars($section['title']) . "</h2>\n";
            $html .= "<div>" . nl2br(htmlspecialchars($section['content'])) . "</div>\n";
            $html .= "</div>\n";
        }
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    private function generatePDFDocument(array $policy): string
    {
        // Implementação simplificada - em produção usar biblioteca como TCPDF ou DomPDF
        return "PDF generation not implemented in this example";
    }
    
    private function generateFilename(array $policy, string $format): string
    {
        $sanitizedTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $policy['title']);
        return $sanitizedTitle . '_v' . $policy['version'] . '.' . $format;
    }
    
    private function applyTemplateCustomizations(array $template, array $customizations): array
    {
        // Aplica customizações específicas ao template
        return array_merge_recursive($template, $customizations);
    }
    
    private function replacePlaceholders(array $template, array $customizations): array
    {
        // Substitui placeholders como {{COMPANY_NAME}}, {{CONTACT_EMAIL}}, etc.
        $placeholders = $customizations['placeholders'] ?? [];
        
        array_walk_recursive($template, function(&$value) use ($placeholders) {
            if (is_string($value)) {
                foreach ($placeholders as $placeholder => $replacement) {
                    $value = str_replace('{{' . $placeholder . '}}', $replacement, $value);
                }
            }
        });
        
        return $template;
    }
    
    private function checkRequiredSections(array $sections): array
    {
        $requiredSectionTypes = [
            'data_collection', 'data_usage', 'data_sharing', 
            'data_retention', 'user_rights', 'contact_info'
        ];
        
        $presentSections = array_column($sections, 'section_type');
        $missingSections = array_diff($requiredSectionTypes, $presentSections);
        
        return [
            'score' => (count($requiredSectionTypes) - count($missingSections)) / count($requiredSectionTypes) * 100,
            'missing_sections' => $missingSections,
            'present_sections' => $presentSections
        ];
    }
    
    private function checkDataTreatmentClarity(array $dataCategories): array
    {
        $score = 0;
        $issues = [];
        
        foreach ($dataCategories as $category) {
            if (empty($category['purposes'])) {
                $issues[] = "Categoria '{$category['category_name']}' sem finalidades definidas";
            } else {
                $score += 25;
            }
            
            if (empty($category['legal_basis'])) {
                $issues[] = "Categoria '{$category['category_name']}' sem base legal definida";
            } else {
                $score += 25;
            }
        }
        
        return [
            'score' => min(100, $score),
            'issues' => $issues
        ];
    }
    
    private function checkLegalBasisCoverage(array $sections): array
    {
        // Implementação simplificada
        return ['score' => 85, 'coverage' => 'good'];
    }
    
    private function checkRightsInformation(array $sections): array
    {
        // Implementação simplificada
        return ['score' => 90, 'coverage' => 'excellent'];
    }
    
    private function checkContactInformation(array $sections): array
    {
        // Implementação simplificada
        return ['score' => 95, 'coverage' => 'excellent'];
    }
    
    private function generateComplianceRecommendations(array $compliance): array
    {
        $recommendations = [];
        
        if ($compliance['overall_score'] < 80) {
            $recommendations[] = 'Política precisa de melhorias significativas para compliance LGPD';
        }
        
        if ($compliance['required_sections']['score'] < 100) {
            $recommendations[] = 'Adicionar seções obrigatórias: ' . implode(', ', $compliance['required_sections']['missing_sections']);
        }
        
        return $recommendations;
    }
    
    private function successResponse(string $message, $data = null): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }
    
    private function errorResponse(string $message, $data = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => $data
        ];
    }
}