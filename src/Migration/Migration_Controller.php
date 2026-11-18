<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration;

use AngellEYE\PayPal\Migration\DTOs\Batch_Result;
use AngellEYE\PayPal\Migration\DTOs\Migration_Stats;
use AngellEYE\PayPal\Migration\Enums\Migration_Status;
use AngellEYE\PayPal\Migration\Queue\Action_Scheduler_Batch_Processor;
use AngellEYE\PayPal\Migration\Services\Payment_Method_Updater;
use AngellEYE\PayPal\Migration\Services\Payment_Token_Validator;
use AngellEYE\PayPal\Migration\Services\Subscription_Migration_Service;
use AngellEYE\PayPal\Migration\State\HPOS_Migration_State_Storage;
use WC_Gateway_PPCP_AngellEYE_Settings;

/**
 * Main entry point for migration functionality.
 * 
 * Provides a simplified facade API for the rest of the plugin
 * to coordinate migration operations.
 * 
 * @package AngellEYE\PayPal\Migration
 * @since 1.0.0
 */
class Migration_Controller {
    
    private static ?self $_instance = null;
    
    private Subscription_Migration_Service $migration_service;
    private Action_Scheduler_Batch_Processor $batch_processor;
    private HPOS_Migration_State_Storage $state_storage;
    
    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance(): self {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor.
     */
    private function __construct() {
        $this->initialize_services();
    }
    
    /**
     * Initialize all services.
     *
     * @return void
     */
    private function initialize_services(): void {
        $this->state_storage = new HPOS_Migration_State_Storage();
        
        $settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        $token_validator = new Payment_Token_Validator();
        $payment_method_updater = new Payment_Method_Updater($settings);
        
        $this->migration_service = new Subscription_Migration_Service(
            $this->state_storage,
            $token_validator,
            $payment_method_updater
        );
        
        $this->batch_processor = new Action_Scheduler_Batch_Processor($this->migration_service);
    }
    
    /**
     * Start migration for a payment method.
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @param int $batch_size Number of subscriptions per batch.
     * @return bool True if started successfully.
     */
    public function start_migration(
        string $from_payment_method,
        string $to_payment_method = 'angelleye_ppcp',
        int $batch_size = 100
    ): bool {
        // Check if already running
        if ($this->batch_processor->is_running($from_payment_method, $to_payment_method)) {
            return false;
        }
        
        $this->batch_processor->start($from_payment_method, $to_payment_method, $batch_size);
        return true;
    }
    
    /**
     * Process a single subscription immediately.
     *
     * @param int $subscription_id Subscription ID.
     * @param string $to_payment_method Target payment method.
     * @return array Result data.
     */
    public function migrate_subscription(int $subscription_id, string $to_payment_method = 'angelleye_ppcp'): array {
        $result = $this->migration_service->retry($subscription_id, $to_payment_method);
        return $result->to_array();
    }
    
    /**
     * Get migration statistics.
     *
     * @param string $payment_method Payment method.
     * @return array Statistics array.
     */
    public function get_stats(string $payment_method): array {
        $status_counts = $this->state_storage->get_stats($payment_method);
        
        $total = array_sum($status_counts);
        $completed = $status_counts[Migration_Status::COMPLETED->value] ?? 0;
        $failed = ($status_counts[Migration_Status::FAILED_NO_TOKEN->value] ?? 0)
            + ($status_counts[Migration_Status::FAILED_API_ERROR->value] ?? 0)
            + ($status_counts[Migration_Status::FAILED_DATA_ERROR->value] ?? 0);
        $pending = $status_counts['not_started'] ?? 0;
        $in_progress = $status_counts[Migration_Status::IN_PROGRESS->value] ?? 0;
        
        $processed = $completed + $failed;
        $completion_percentage = $total > 0 ? round(($processed / $total) * 100, 2) : 0;
        $success_rate = $processed > 0 ? round(($completed / $processed) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
            'in_progress' => $in_progress,
            'completion_percentage' => $completion_percentage,
            'success_rate' => $success_rate,
            'by_status' => $status_counts,
        ];
    }
    
    /**
     * Cancel running migration.
     *
     * @param string|null $from_payment_method Optional specific migration to cancel.
     * @return void
     */
    public function cancel_migration(?string $from_payment_method = null): void {
        $this->batch_processor->cancel($from_payment_method);
    }
    
    /**
     * Check if migration is running.
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @return bool True if migration is running.
     */
    public function is_migration_running(
        string $from_payment_method,
        string $to_payment_method = 'angelleye_ppcp'
    ): bool {
        return $this->batch_processor->is_running($from_payment_method, $to_payment_method);
    }
    
    /**
     * Process batch immediately (for testing/debugging).
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @param int $batch_size Batch size.
     * @return array Batch result.
     */
    public function process_batch(
        string $from_payment_method,
        string $to_payment_method = 'angelleye_ppcp',
        int $batch_size = 10
    ): array {
        $result = $this->migration_service->process_batch(
            $from_payment_method,
            $to_payment_method,
            $batch_size
        );
        
        return $result->to_array();
    }
    
    /**
     * Reset migration state for a subscription.
     *
     * @param int $subscription_id Subscription ID.
     * @return void
     */
    public function reset_subscription(int $subscription_id): void {
        $meta_keys = [
            HPOS_Migration_State_Storage::META_STATUS,
            HPOS_Migration_State_Storage::META_ATTEMPTS,
            HPOS_Migration_State_Storage::META_ERROR_CODE,
            HPOS_Migration_State_Storage::META_ERROR_MESSAGE,
            HPOS_Migration_State_Storage::META_STARTED_AT,
            HPOS_Migration_State_Storage::META_COMPLETED_AT,
        ];
        
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        foreach ($meta_keys as $key) {
            $subscription->delete_meta_data($key);
        }
        $subscription->save();
    }
    
    /**
     * Check if migration can be started (not already running).
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @return bool True if can start.
     */
    public function can_start_migration(
        string $from_payment_method = 'paypal_express',
        string $to_payment_method = 'angelleye_ppcp'
    ): bool {
        return !$this->batch_processor->is_running($from_payment_method, $to_payment_method);
    }
    
    /**
     * Stop/cancel running migration.
     *
     * @return void
     */
    public function stop_migration(): void {
        $this->batch_processor->cancel();
    }
    
    /**
     * Retry a single subscription.
     *
     * @param int $subscription_id Subscription ID.
     * @param string $to_payment_method Target payment method.
     * @return \AngellEYE\PayPal\Migration\DTOs\Migration_Result|null
     */
    public function retry_subscription(int $subscription_id, string $to_payment_method = 'angelleye_ppcp'): ?\AngellEYE\PayPal\Migration\DTOs\Migration_Result {
        return $this->migration_service->retry($subscription_id, $to_payment_method);
    }
    
    /**
     * Retry failed subscriptions.
     *
     * @param string|null $error_code Optional error code filter.
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @return int Number of subscriptions queued for retry.
     */
    public function retry_failed(
        ?string $error_code = null,
        string $from_payment_method = 'paypal_express',
        string $to_payment_method = 'angelleye_ppcp'
    ): int {
        $failed = $this->get_failed_subscriptions($from_payment_method, $error_code ? [$error_code] : null, 1000);

        $count = 0;
        foreach ($failed as $item) {
            // Reset status to allow retry
            $this->reset_subscription($item['subscription_id']);
            $count++;
        }
        
        // Restart migration if not running
        if ($count > 0 && $this->can_start_migration($from_payment_method, $to_payment_method)) {
            $this->start_migration($from_payment_method, $to_payment_method, 50);
        }
        
        return $count;
    }
    
    /**
     * Get failed subscriptions with details.
     *
     * @param string $from_payment_method Source payment method.
     * @param array|null $error_codes Optional error codes filter.
     * @param int $limit Maximum results.
     * @return array Array of failed subscription details.
     */
    public function get_failed_subscriptions(
        string $from_payment_method = 'paypal_express',
        ?array $error_codes = null,
        int $limit = 100
    ): array {
        $failed_statuses = [
            Migration_Status::FAILED_NO_TOKEN->value,
            Migration_Status::FAILED_API_ERROR->value,
            Migration_Status::FAILED_DATA_ERROR->value,
        ];
        
        $args = [
            'type' => 'shop_subscription',
            'status' => ['wc-active', 'wc-on-hold', 'wc-pending-cancel'],
            'payment_method' => $from_payment_method,
            'limit' => $limit,
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => HPOS_Migration_State_Storage::META_STATUS,
                    'value' => $failed_statuses,
                    'compare' => 'IN',
                ],
            ],
        ];
        
        if ($error_codes) {
            $args['meta_query'][] = [
                'key' => HPOS_Migration_State_Storage::META_ERROR_CODE,
                'value' => $error_codes,
                'compare' => 'IN',
            ];
        }
        
        $subscription_ids = wc_get_orders($args);
        $results = [];
        
        foreach ($subscription_ids as $subscription_id) {
            $subscription = wcs_get_subscription($subscription_id);
            if (!$subscription) {
                continue;
            }
            
            $results[] = [
                'subscription_id' => $subscription_id,
                'error_code' => $subscription->get_meta(HPOS_Migration_State_Storage::META_ERROR_CODE),
                'error_message' => $subscription->get_meta(HPOS_Migration_State_Storage::META_ERROR_MESSAGE),
            ];
        }
        
        return $results;
    }
    
    /**
     * Reset all migration data.
     *
     * @param string $payment_method Payment method to reset.
     * @return int Number of subscriptions reset.
     */
    public function reset_all(string $payment_method = 'paypal_express'): int {
        // Get all subscriptions with migration data
        $args = [
            'type' => 'shop_subscription',
            'status' => 'any',
            'payment_method' => $payment_method,
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => HPOS_Migration_State_Storage::META_STATUS,
                    'compare' => 'EXISTS',
                ],
            ],
        ];
        
        $subscription_ids = wc_get_orders($args);
        
        foreach ($subscription_ids as $subscription_id) {
            $this->reset_subscription($subscription_id);
        }
        
        // Cancel any running migration
        $this->stop_migration();
        
        return count($subscription_ids);
    }
    
    /**
     * Get the batch processor instance.
     *
     * @return Action_Scheduler_Batch_Processor
     */
    public function get_batch_processor(): Action_Scheduler_Batch_Processor {
        return $this->batch_processor;
    }
}
