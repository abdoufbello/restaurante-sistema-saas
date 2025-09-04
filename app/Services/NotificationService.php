<?php

namespace App\Services;

use App\Models\RestaurantModel;
use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Email\Email;
use Exception;

class NotificationService
{
    protected Email $email;
    protected RestaurantModel $restaurantModel;
    protected SubscriptionModel $subscriptionModel;
    protected PlanModel $planModel;
    
    public function __construct()
    {
        $this->email = \Config\Services::email();
        $this->restaurantModel = new RestaurantModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->planModel = new PlanModel();
    }
    
    /**
     * Send welcome email after subscription
     */
    public function sendWelcomeEmail(int $restaurantId, array $subscription): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            $plan = $this->planModel->find($subscription['plan_id']);
            
            if (!$restaurant || !$plan) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject('Bem-vindo ao TotemSystem - Sua assinatura está ativa!');
            
            $message = $this->buildWelcomeEmailTemplate($restaurant, $plan, $subscription);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar email de boas-vindas: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send trial started notification
     */
    public function sendTrialStartedEmail(int $restaurantId, array $subscription): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            $plan = $this->planModel->find($subscription['plan_id']);
            
            if (!$restaurant || !$plan) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject('Seu período de teste gratuito começou!');
            
            $message = $this->buildTrialStartedEmailTemplate($restaurant, $plan, $subscription);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar email de início de trial: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send trial expiration warning
     */
    public function sendTrialExpirationWarning(int $restaurantId, int $daysRemaining): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            $subscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
            $plan = $this->planModel->find($subscription['plan_id']);
            
            if (!$restaurant || !$subscription || !$plan) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject("Seu período de teste expira em {$daysRemaining} dias");
            
            $message = $this->buildTrialExpirationWarningTemplate($restaurant, $plan, $subscription, $daysRemaining);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar aviso de expiração de trial: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send payment failure notification
     */
    public function sendPaymentFailureNotification(int $restaurantId, array $subscription, int $failureCount, int $maxFailures): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            $plan = $this->planModel->find($subscription['plan_id']);
            
            if (!$restaurant || !$plan) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject('Problema com o pagamento da sua assinatura');
            
            $message = $this->buildPaymentFailureEmailTemplate($restaurant, $plan, $subscription, $failureCount, $maxFailures);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar notificação de falha de pagamento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send subscription suspended notification
     */
    public function sendSubscriptionSuspendedNotification(int $restaurantId, array $subscription): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            $plan = $this->planModel->find($subscription['plan_id']);
            
            if (!$restaurant || !$plan) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject('Sua assinatura foi suspensa');
            
            $message = $this->buildSubscriptionSuspendedEmailTemplate($restaurant, $plan, $subscription);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar notificação de suspensão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send billing reminder
     */
    public function sendBillingReminder(int $restaurantId, int $daysUntilBilling): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            $subscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
            $plan = $this->planModel->find($subscription['plan_id']);
            
            if (!$restaurant || !$subscription || !$plan) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject("Lembrete: Próxima cobrança em {$daysUntilBilling} dias");
            
            $message = $this->buildBillingReminderEmailTemplate($restaurant, $plan, $subscription, $daysUntilBilling);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar lembrete de cobrança: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send usage limit warning
     */
    public function sendUsageLimitWarning(int $restaurantId, string $limitType, float $usagePercentage): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            $subscription = $this->subscriptionModel->getActiveSubscription($restaurantId);
            $plan = $this->planModel->find($subscription['plan_id']);
            
            if (!$restaurant || !$subscription || !$plan) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject('Aviso: Limite de uso próximo do máximo');
            
            $message = $this->buildUsageLimitWarningEmailTemplate($restaurant, $plan, $limitType, $usagePercentage);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar aviso de limite de uso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send plan change confirmation
     */
    public function sendPlanChangeConfirmation(int $restaurantId, array $oldPlan, array $newPlan, float $proratedAmount = 0): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            
            if (!$restaurant) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject('Confirmação: Plano alterado com sucesso');
            
            $message = $this->buildPlanChangeConfirmationEmailTemplate($restaurant, $oldPlan, $newPlan, $proratedAmount);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar confirmação de mudança de plano: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send subscription cancellation confirmation
     */
    public function sendCancellationConfirmation(int $restaurantId, array $subscription, bool $immediate = false): bool
    {
        try {
            $restaurant = $this->restaurantModel->find($restaurantId);
            $plan = $this->planModel->find($subscription['plan_id']);
            
            if (!$restaurant || !$plan) {
                return false;
            }
            
            $this->email->setTo($restaurant['email']);
            $this->email->setSubject('Confirmação: Assinatura cancelada');
            
            $message = $this->buildCancellationConfirmationEmailTemplate($restaurant, $plan, $subscription, $immediate);
            $this->email->setMessage($message);
            
            return $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao enviar confirmação de cancelamento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process scheduled notifications
     */
    public function processScheduledNotifications(): array
    {
        $results = [
            'trial_warnings' => 0,
            'billing_reminders' => 0,
            'usage_warnings' => 0,
            'errors' => []
        ];
        
        try {
            // Send trial expiration warnings
            $results['trial_warnings'] = $this->sendTrialExpirationWarnings();
            
            // Send billing reminders
            $results['billing_reminders'] = $this->sendBillingReminders();
            
            // Send usage limit warnings
            $results['usage_warnings'] = $this->sendUsageLimitWarnings();
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            log_message('error', 'Erro no processamento de notificações: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Send trial expiration warnings for subscriptions expiring soon
     */
    protected function sendTrialExpirationWarnings(): int
    {
        $warningDays = [7, 3, 1]; // Send warnings 7, 3, and 1 days before expiration
        $sent = 0;
        
        foreach ($warningDays as $days) {
            $targetDate = Time::now()->addDays($days)->toDateString();
            
            $subscriptions = $this->subscriptionModel
                ->where('status', 'trialing')
                ->where('DATE(trial_ends_at)', $targetDate)
                ->findAll();
            
            foreach ($subscriptions as $subscription) {
                if ($this->sendTrialExpirationWarning($subscription['restaurant_id'], $days)) {
                    $sent++;
                }
            }
        }
        
        return $sent;
    }
    
    /**
     * Send billing reminders for upcoming charges
     */
    protected function sendBillingReminders(): int
    {
        $reminderDays = config('PaymentGateway')->subscription['reminder_days_before'] ?? [7, 3, 1];
        $sent = 0;
        
        foreach ($reminderDays as $days) {
            $targetDate = Time::now()->addDays($days)->toDateString();
            
            $subscriptions = $this->subscriptionModel
                ->where('status', 'active')
                ->where('DATE(next_billing_date)', $targetDate)
                ->findAll();
            
            foreach ($subscriptions as $subscription) {
                if ($this->sendBillingReminder($subscription['restaurant_id'], $days)) {
                    $sent++;
                }
            }
        }
        
        return $sent;
    }
    
    /**
     * Send usage limit warnings
     */
    protected function sendUsageLimitWarnings(): int
    {
        // This would integrate with UsageTrackingModel to check usage percentages
        // and send warnings when approaching limits (e.g., 80%, 90%, 95%)
        $sent = 0;
        
        // Implementation would go here based on usage tracking requirements
        
        return $sent;
    }
    
    /**
     * Build welcome email template
     */
    protected function buildWelcomeEmailTemplate(array $restaurant, array $plan, array $subscription): string
    {
        $template = view('emails/subscription/welcome', [
            'restaurant' => $restaurant,
            'plan' => $plan,
            'subscription' => $subscription,
            'dashboard_url' => base_url('dashboard'),
            'support_url' => base_url('support')
        ]);
        
        return $template;
    }
    
    /**
     * Build trial started email template
     */
    protected function buildTrialStartedEmailTemplate(array $restaurant, array $plan, array $subscription): string
    {
        $trialEndDate = Time::parse($subscription['trial_ends_at'])->toLocalizedString('d/m/Y');
        
        $template = view('emails/subscription/trial_started', [
            'restaurant' => $restaurant,
            'plan' => $plan,
            'subscription' => $subscription,
            'trial_end_date' => $trialEndDate,
            'dashboard_url' => base_url('dashboard'),
            'billing_url' => base_url('subscription')
        ]);
        
        return $template;
    }
    
    /**
     * Build trial expiration warning email template
     */
    protected function buildTrialExpirationWarningTemplate(array $restaurant, array $plan, array $subscription, int $daysRemaining): string
    {
        $template = view('emails/subscription/trial_expiration_warning', [
            'restaurant' => $restaurant,
            'plan' => $plan,
            'subscription' => $subscription,
            'days_remaining' => $daysRemaining,
            'billing_url' => base_url('subscription/plans'),
            'support_url' => base_url('support')
        ]);
        
        return $template;
    }
    
    /**
     * Build payment failure email template
     */
    protected function buildPaymentFailureEmailTemplate(array $restaurant, array $plan, array $subscription, int $failureCount, int $maxFailures): string
    {
        $template = view('emails/subscription/payment_failure', [
            'restaurant' => $restaurant,
            'plan' => $plan,
            'subscription' => $subscription,
            'failure_count' => $failureCount,
            'max_failures' => $maxFailures,
            'remaining_attempts' => $maxFailures - $failureCount,
            'billing_url' => base_url('subscription'),
            'support_url' => base_url('support')
        ]);
        
        return $template;
    }
    
    /**
     * Build subscription suspended email template
     */
    protected function buildSubscriptionSuspendedEmailTemplate(array $restaurant, array $plan, array $subscription): string
    {
        $template = view('emails/subscription/suspended', [
            'restaurant' => $restaurant,
            'plan' => $plan,
            'subscription' => $subscription,
            'reactivate_url' => base_url('subscription/reactivate'),
            'support_url' => base_url('support')
        ]);
        
        return $template;
    }
    
    /**
     * Build billing reminder email template
     */
    protected function buildBillingReminderEmailTemplate(array $restaurant, array $plan, array $subscription, int $daysUntilBilling): string
    {
        $billingDate = Time::parse($subscription['next_billing_date'])->toLocalizedString('d/m/Y');
        
        $template = view('emails/subscription/billing_reminder', [
            'restaurant' => $restaurant,
            'plan' => $plan,
            'subscription' => $subscription,
            'days_until_billing' => $daysUntilBilling,
            'billing_date' => $billingDate,
            'amount' => config('PaymentGateway')->formatCurrency($subscription['amount']),
            'billing_url' => base_url('subscription'),
            'support_url' => base_url('support')
        ]);
        
        return $template;
    }
    
    /**
     * Build usage limit warning email template
     */
    protected function buildUsageLimitWarningEmailTemplate(array $restaurant, array $plan, string $limitType, float $usagePercentage): string
    {
        $template = view('emails/subscription/usage_limit_warning', [
            'restaurant' => $restaurant,
            'plan' => $plan,
            'limit_type' => $limitType,
            'usage_percentage' => $usagePercentage,
            'upgrade_url' => base_url('subscription/plans'),
            'dashboard_url' => base_url('dashboard'),
            'support_url' => base_url('support')
        ]);
        
        return $template;
    }
    
    /**
     * Build plan change confirmation email template
     */
    protected function buildPlanChangeConfirmationEmailTemplate(array $restaurant, array $oldPlan, array $newPlan, float $proratedAmount): string
    {
        $template = view('emails/subscription/plan_change_confirmation', [
            'restaurant' => $restaurant,
            'old_plan' => $oldPlan,
            'new_plan' => $newPlan,
            'prorated_amount' => $proratedAmount,
            'formatted_amount' => config('PaymentGateway')->formatCurrency($proratedAmount),
            'dashboard_url' => base_url('dashboard'),
            'billing_url' => base_url('subscription')
        ]);
        
        return $template;
    }
    
    /**
     * Build cancellation confirmation email template
     */
    protected function buildCancellationConfirmationEmailTemplate(array $restaurant, array $plan, array $subscription, bool $immediate): string
    {
        $endDate = $immediate ? 
            Time::now()->toLocalizedString('d/m/Y') : 
            Time::parse($subscription['end_date'])->toLocalizedString('d/m/Y');
        
        $template = view('emails/subscription/cancellation_confirmation', [
            'restaurant' => $restaurant,
            'plan' => $plan,
            'subscription' => $subscription,
            'immediate' => $immediate,
            'end_date' => $endDate,
            'resubscribe_url' => base_url('subscription/plans'),
            'feedback_url' => base_url('feedback'),
            'support_url' => base_url('support')
        ]);
        
        return $template;
    }
}