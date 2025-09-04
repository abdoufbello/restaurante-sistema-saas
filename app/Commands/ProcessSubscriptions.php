<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\BillingService;
use App\Services\NotificationService;
use App\Models\SubscriptionModel;
use CodeIgniter\I18n\Time;
use Exception;

class ProcessSubscriptions extends BaseCommand
{
    protected $group = 'Subscription';
    protected $name = 'subscription:process';
    protected $description = 'Process recurring billing and send notifications for subscriptions';
    protected $usage = 'subscription:process [options]';
    protected $arguments = [];
    protected $options = [
        '--billing' => 'Process recurring billing only',
        '--notifications' => 'Send notifications only',
        '--dry-run' => 'Show what would be processed without making changes',
        '--force' => 'Force processing even if already run today'
    ];
    
    protected BillingService $billingService;
    protected NotificationService $notificationService;
    protected SubscriptionModel $subscriptionModel;
    
    public function run(array $params)
    {
        $this->billingService = new BillingService();
        $this->notificationService = new NotificationService();
        $this->subscriptionModel = new SubscriptionModel();
        
        $billingOnly = CLI::getOption('billing');
        $notificationsOnly = CLI::getOption('notifications');
        $dryRun = CLI::getOption('dry-run');
        $force = CLI::getOption('force');
        
        CLI::write('=== TotemSystem Subscription Processor ===', 'yellow');
        CLI::write('Started at: ' . Time::now()->toDateTimeString());
        
        if ($dryRun) {
            CLI::write('DRY RUN MODE - No changes will be made', 'yellow');
        }
        
        try {
            // Check if already processed today (unless forced)
            if (!$force && $this->wasProcessedToday()) {
                CLI::write('Subscriptions already processed today. Use --force to override.', 'yellow');
                return;
            }
            
            $results = [];
            
            // Process billing if not notifications-only
            if (!$notificationsOnly) {
                CLI::write('\n--- Processing Recurring Billing ---', 'cyan');
                $results['billing'] = $this->processBilling($dryRun);
            }
            
            // Process notifications if not billing-only
            if (!$billingOnly) {
                CLI::write('\n--- Processing Notifications ---', 'cyan');
                $results['notifications'] = $this->processNotifications($dryRun);
            }
            
            // Process subscription status updates
            if (!$notificationsOnly) {
                CLI::write('\n--- Updating Subscription Statuses ---', 'cyan');
                $results['status_updates'] = $this->processStatusUpdates($dryRun);
            }
            
            // Display summary
            $this->displaySummary($results);
            
            // Mark as processed today
            if (!$dryRun) {
                $this->markProcessedToday();
            }
            
            CLI::write('\nCompleted at: ' . Time::now()->toDateTimeString(), 'green');
            
        } catch (Exception $e) {
            CLI::error('Error processing subscriptions: ' . $e->getMessage());
            log_message('error', 'Subscription processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Process recurring billing
     */
    protected function processBilling(bool $dryRun = false): array
    {
        if ($dryRun) {
            return $this->simulateBilling();
        }
        
        CLI::write('Processing recurring billing...');
        $results = $this->billingService->processRecurringBilling();
        
        CLI::write("Processed: {$results['processed']} subscriptions", 'green');
        CLI::write("Failed: {$results['failed']} subscriptions", $results['failed'] > 0 ? 'red' : 'white');
        
        if (!empty($results['errors'])) {
            CLI::write('Errors:', 'red');
            foreach ($results['errors'] as $error) {
                CLI::write("  - Subscription {$error['subscription_id']}: {$error['error']}", 'red');
            }
        }
        
        return $results;
    }
    
    /**
     * Process notifications
     */
    protected function processNotifications(bool $dryRun = false): array
    {
        if ($dryRun) {
            return $this->simulateNotifications();
        }
        
        CLI::write('Processing notifications...');
        $results = $this->notificationService->processScheduledNotifications();
        
        CLI::write("Trial warnings sent: {$results['trial_warnings']}", 'green');
        CLI::write("Billing reminders sent: {$results['billing_reminders']}", 'green');
        CLI::write("Usage warnings sent: {$results['usage_warnings']}", 'green');
        
        if (!empty($results['errors'])) {
            CLI::write('Notification errors:', 'red');
            foreach ($results['errors'] as $error) {
                CLI::write("  - {$error}", 'red');
            }
        }
        
        return $results;
    }
    
    /**
     * Process subscription status updates
     */
    protected function processStatusUpdates(bool $dryRun = false): array
    {
        $results = [
            'expired_trials' => 0,
            'expired_subscriptions' => 0,
            'reactivated' => 0
        ];
        
        CLI::write('Updating subscription statuses...');
        
        // Expire trial subscriptions
        $expiredTrials = $this->subscriptionModel
            ->where('status', 'trialing')
            ->where('trial_ends_at <', Time::now()->toDateTimeString())
            ->findAll();
        
        foreach ($expiredTrials as $subscription) {
            if (!$dryRun) {
                $this->subscriptionModel->update($subscription['id'], [
                    'status' => 'expired',
                    'expired_at' => Time::now()->toDateTimeString()
                ]);
            }
            $results['expired_trials']++;
        }
        
        // Expire regular subscriptions
        $expiredSubscriptions = $this->subscriptionModel
            ->where('status', 'active')
            ->where('end_date <', Time::now()->toDateTimeString())
            ->findAll();
        
        foreach ($expiredSubscriptions as $subscription) {
            if (!$dryRun) {
                $this->subscriptionModel->update($subscription['id'], [
                    'status' => 'expired',
                    'expired_at' => Time::now()->toDateTimeString()
                ]);
            }
            $results['expired_subscriptions']++;
        }
        
        // Check for subscriptions that can be reactivated after payment
        $suspendedSubscriptions = $this->subscriptionModel
            ->where('status', 'suspended')
            ->where('payment_failures', 0) // Payment was successful
            ->findAll();
        
        foreach ($suspendedSubscriptions as $subscription) {
            if (!$dryRun) {
                $this->subscriptionModel->update($subscription['id'], [
                    'status' => 'active',
                    'suspended_at' => null
                ]);
            }
            $results['reactivated']++;
        }
        
        CLI::write("Expired trials: {$results['expired_trials']}", 'yellow');
        CLI::write("Expired subscriptions: {$results['expired_subscriptions']}", 'yellow');
        CLI::write("Reactivated subscriptions: {$results['reactivated']}", 'green');
        
        return $results;
    }
    
    /**
     * Simulate billing for dry run
     */
    protected function simulateBilling(): array
    {
        $dueSubscriptions = $this->subscriptionModel
            ->where('status', 'active')
            ->where('next_billing_date <=', Time::now()->toDateTimeString())
            ->findAll();
        
        CLI::write("Would process {" . count($dueSubscriptions) . "} subscriptions for billing");
        
        foreach ($dueSubscriptions as $subscription) {
            CLI::write("  - Subscription {$subscription['id']}: R$ {$subscription['amount']}");
        }
        
        return [
            'processed' => count($dueSubscriptions),
            'failed' => 0,
            'errors' => []
        ];
    }
    
    /**
     * Simulate notifications for dry run
     */
    protected function simulateNotifications(): array
    {
        $results = [
            'trial_warnings' => 0,
            'billing_reminders' => 0,
            'usage_warnings' => 0,
            'errors' => []
        ];
        
        // Count trial warnings
        $warningDays = [7, 3, 1];
        foreach ($warningDays as $days) {
            $targetDate = Time::now()->addDays($days)->toDateString();
            $count = $this->subscriptionModel
                ->where('status', 'trialing')
                ->where('DATE(trial_ends_at)', $targetDate)
                ->countAllResults();
            $results['trial_warnings'] += $count;
        }
        
        // Count billing reminders
        $reminderDays = [7, 3, 1];
        foreach ($reminderDays as $days) {
            $targetDate = Time::now()->addDays($days)->toDateString();
            $count = $this->subscriptionModel
                ->where('status', 'active')
                ->where('DATE(next_billing_date)', $targetDate)
                ->countAllResults();
            $results['billing_reminders'] += $count;
        }
        
        CLI::write("Would send {$results['trial_warnings']} trial warnings");
        CLI::write("Would send {$results['billing_reminders']} billing reminders");
        CLI::write("Would send {$results['usage_warnings']} usage warnings");
        
        return $results;
    }
    
    /**
     * Display processing summary
     */
    protected function displaySummary(array $results): void
    {
        CLI::write('\n=== Processing Summary ===', 'yellow');
        
        if (isset($results['billing'])) {
            CLI::write('Billing:', 'cyan');
            CLI::write("  Processed: {$results['billing']['processed']}");
            CLI::write("  Failed: {$results['billing']['failed']}");
        }
        
        if (isset($results['notifications'])) {
            CLI::write('Notifications:', 'cyan');
            CLI::write("  Trial warnings: {$results['notifications']['trial_warnings']}");
            CLI::write("  Billing reminders: {$results['notifications']['billing_reminders']}");
            CLI::write("  Usage warnings: {$results['notifications']['usage_warnings']}");
        }
        
        if (isset($results['status_updates'])) {
            CLI::write('Status Updates:', 'cyan');
            CLI::write("  Expired trials: {$results['status_updates']['expired_trials']}");
            CLI::write("  Expired subscriptions: {$results['status_updates']['expired_subscriptions']}");
            CLI::write("  Reactivated: {$results['status_updates']['reactivated']}");
        }
        
        // Calculate totals
        $totalProcessed = ($results['billing']['processed'] ?? 0) + 
                         ($results['status_updates']['expired_trials'] ?? 0) + 
                         ($results['status_updates']['expired_subscriptions'] ?? 0) + 
                         ($results['status_updates']['reactivated'] ?? 0);
        
        $totalNotifications = ($results['notifications']['trial_warnings'] ?? 0) + 
                             ($results['notifications']['billing_reminders'] ?? 0) + 
                             ($results['notifications']['usage_warnings'] ?? 0);
        
        CLI::write('\nTotals:', 'yellow');
        CLI::write("  Subscriptions processed: {$totalProcessed}");
        CLI::write("  Notifications sent: {$totalNotifications}");
    }
    
    /**
     * Check if subscriptions were already processed today
     */
    protected function wasProcessedToday(): bool
    {
        $cacheKey = 'subscription_processed_' . Time::now()->toDateString();
        return cache($cacheKey) !== null;
    }
    
    /**
     * Mark subscriptions as processed today
     */
    protected function markProcessedToday(): void
    {
        $cacheKey = 'subscription_processed_' . Time::now()->toDateString();
        cache()->save($cacheKey, Time::now()->toDateTimeString(), 86400); // 24 hours
    }
    
    /**
     * Get subscription statistics
     */
    public function stats(array $params)
    {
        $this->billingService = new BillingService();
        
        CLI::write('=== Subscription Statistics ===', 'yellow');
        
        $stats = $this->billingService->getSubscriptionStats();
        
        CLI::write('Subscriptions:', 'cyan');
        CLI::write("  Total: {$stats['total_subscriptions']}");
        CLI::write("  Active: {$stats['active_subscriptions']}", 'green');
        CLI::write("  Trial: {$stats['trial_subscriptions']}", 'yellow');
        CLI::write("  Canceled: {$stats['canceled_subscriptions']}", 'red');
        CLI::write("  Suspended: {$stats['suspended_subscriptions']}", 'red');
        
        CLI::write('\nRevenue:', 'cyan');
        CLI::write("  Monthly Revenue: R$ " . number_format($stats['monthly_revenue'], 2, ',', '.'), 'green');
        CLI::write("  ARPU: R$ " . number_format($stats['average_revenue_per_user'], 2, ',', '.'));
        CLI::write("  Churn Rate: " . number_format($stats['churn_rate'], 2) . '%', $stats['churn_rate'] > 10 ? 'red' : 'green');
        
        // Show upcoming billing
        $upcomingBilling = $this->subscriptionModel
            ->where('status', 'active')
            ->where('next_billing_date <=', Time::now()->addDays(7)->toDateTimeString())
            ->countAllResults();
        
        CLI::write('\nUpcoming:', 'cyan');
        CLI::write("  Billing in next 7 days: {$upcomingBilling}");
        
        // Show trial expirations
        $trialExpirations = $this->subscriptionModel
            ->where('status', 'trialing')
            ->where('trial_ends_at <=', Time::now()->addDays(7)->toDateTimeString())
            ->countAllResults();
        
        CLI::write("  Trial expirations in next 7 days: {$trialExpirations}");
    }
}