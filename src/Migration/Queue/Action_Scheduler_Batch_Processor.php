<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Queue;

use AngellEYE\PayPal\Migration\Contracts\Batch_Processor_Interface;
use AngellEYE\PayPal\Migration\DTOs\Batch_Result;
use AngellEYE\PayPal\Migration\Services\Subscription_Migration_Service;

/**
 * Action Scheduler implementation of batch processor.
 * 
 * @package AngellEYE\PayPal\Migration\Queue
 * @since 1.0.0
 */
class Action_Scheduler_Batch_Processor implements Batch_Processor_Interface {
    
    private Subscription_Migration_Service $migration_service;
    private string $hook_name = 'angelleye_ppcp_migration_process_batch';
    private string $group = 'angelleye_ppcp_migration';
    
    /**
     * Constructor.
     *
     * @param Subscription_Migration_Service $migration_service Migration service.
     */
    public function __construct(Subscription_Migration_Service $migration_service) {
        $this->migration_service = $migration_service;
        
        // Register hook
        add_action($this->hook_name, [$this, 'process_scheduled_batch'], 10, 3);
    }
    
    /**
     * @inheritDoc
     */
    public function start(string $from_payment_method, string $to_payment_method, int $batch_size = 100): void {
        // Cancel any existing scheduled actions
        $this->cancel($from_payment_method, $to_payment_method);
        
        // Schedule first batch
        $this->schedule_next($from_payment_method, $to_payment_method, $batch_size, time());
    }
    
    /**
     * @inheritDoc
     */
    public function schedule_next(
        string $from_payment_method,
        string $to_payment_method,
        int $batch_size,
        ?int $timestamp = null
    ): void {
        if ($timestamp === null) {
            $timestamp = time() + 30; // 30 second delay between batches
        }
        
        as_schedule_single_action(
            $timestamp,
            $this->hook_name,
            [
                'from' => $from_payment_method,
                'to' => $to_payment_method,
                'batch_size' => $batch_size,
            ],
            $this->group
        );
    }
    
    /**
     * Process a scheduled batch.
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @param int $batch_size Batch size.
     * @return void
     */
    public function process_scheduled_batch(
        string $from_payment_method,
        string $to_payment_method,
        int $batch_size
    ): void {
        $result = $this->migration_service->process_batch(
            $from_payment_method,
            $to_payment_method,
            $batch_size
        );
        
        // Log results
        $this->log_batch_results($result, $from_payment_method);
        
        // Schedule next batch if there are more
        if ($result->has_more) {
            $this->schedule_next($from_payment_method, $to_payment_method, $batch_size);
        } else {
            $this->log_migration_complete($from_payment_method, $to_payment_method);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function pause(string $from_payment_method, string $to_payment_method): void {
        as_unschedule_action(
            $this->hook_name,
            [
                'from' => $from_payment_method,
                'to' => $to_payment_method,
            ],
            $this->group
        );
    }
    
    /**
     * @inheritDoc
     */
    public function cancel(?string $from_payment_method = null, ?string $to_payment_method = null): void {
        if ($from_payment_method && $to_payment_method) {
            as_unschedule_all_actions(
                $this->hook_name,
                [
                    'from' => $from_payment_method,
                    'to' => $to_payment_method,
                ],
                $this->group
            );
        } else {
            // Cancel all migration actions
            as_unschedule_all_actions($this->hook_name, [], $this->group);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function is_running(string $from_payment_method, string $to_payment_method): bool {
        $pending_actions = as_get_scheduled_actions([
            'hook' => $this->hook_name,
            'args' => [
                'from' => $from_payment_method,
                'to' => $to_payment_method,
            ],
            'status' => \ActionScheduler_Store::STATUS_PENDING,
            'group' => $this->group,
        ]);
        
        return !empty($pending_actions);
    }
    
    /**
     * Get pending batch count.
     *
     * @return int Number of pending batches.
     */
    public function get_pending_count(): int {
        return as_get_scheduled_action_count([
            'hook' => $this->hook_name,
            'group' => $this->group,
            'status' => \ActionScheduler_Store::STATUS_PENDING,
        ]);
    }
    
    /**
     * Log batch results.
     *
     * @param Batch_Result $result Batch result.
     * @param string $payment_method Payment method.
     * @return void
     */
    private function log_batch_results(Batch_Result $result, string $payment_method): void {
        if (!function_exists('wc_get_logger')) {
            return;
        }
        
        $logger = wc_get_logger();
        $context = ['source' => 'angelleye-migration'];
        
        $message = sprintf(
            'Batch completed for %s: %d processed, %d successful, %d failed, %d skipped (%.1f%% success)',
            $payment_method,
            $result->total,
            $result->successful,
            $result->failed,
            $result->skipped,
            $result->success_rate()
        );
        
        $logger->info($message, $context);
        
        // Log failures in detail
        foreach ($result->get_failures() as $failure) {
            $logger->error(sprintf(
                'Failed - Subscription %d: [%s] %s',
                $failure->subscription_id,
                $failure->error_code,
                $failure->error_message
            ), $context);
        }
    }
    
    /**
     * Log migration completion.
     *
     * @param string $from Source payment method.
     * @param string $to Target payment method.
     * @return void
     */
    private function log_migration_complete(string $from, string $to): void {
        if (!function_exists('wc_get_logger')) {
            return;
        }
        
        wc_get_logger()->info(
            sprintf('Migration from %s to %s completed', $from, $to),
            ['source' => 'angelleye-migration']
        );
    }
}
