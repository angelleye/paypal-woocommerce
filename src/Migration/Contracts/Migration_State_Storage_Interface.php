<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Contracts;

use AngellEYE\PayPal\Migration\Enums\Migration_Status;

/**
 * Interface for migration state storage.
 * 
 * Provides abstraction for storing and retrieving migration state,
 * supporting both HPOS and legacy storage.
 * 
 * @package AngellEYE\PayPal\Migration\Contracts
 * @since 1.0.0
 */
interface Migration_State_Storage_Interface {
    
    /**
     * Meta key constants for state storage.
     */
    public const META_STATUS = '_angelleye_ppcp_migration_status';
    public const META_ATTEMPTS = '_angelleye_ppcp_migration_attempts';
    public const META_ERROR_CODE = '_angelleye_ppcp_migration_error_code';
    public const META_ERROR_MESSAGE = '_angelleye_ppcp_migration_error_message';
    public const META_STARTED_AT = '_angelleye_ppcp_migration_started_at';
    public const META_COMPLETED_AT = '_angelleye_ppcp_migration_completed_at';
    public const META_OLD_PAYMENT_METHOD = '_angelleye_ppcp_old_payment_method';
    
    /**
     * Mark migration as started for a subscription.
     *
     * @param int $subscription_id The subscription ID.
     * @return void
     */
    public function mark_started(int $subscription_id): void;
    
    /**
     * Mark migration as completed.
     *
     * @param int $subscription_id The subscription ID.
     * @return void
     */
    public function mark_completed(int $subscription_id): void;
    
    /**
     * Mark migration as failed with reason.
     *
     * @param int $subscription_id The subscription ID.
     * @param string $reason_code Error code (e.g., 'no_token', 'api_error').
     * @param string|null $message Optional human-readable message.
     * @return void
     */
    public function mark_failed(int $subscription_id, string $reason_code, ?string $message = null): void;
    
    /**
     * Mark as skipped with reason.
     *
     * @param int $subscription_id The subscription ID.
     * @param string $reason Skip reason code.
     * @return void
     */
    public function mark_skipped(int $subscription_id, string $reason): void;
    
    /**
     * Check if subscription has been processed.
     *
     * @param int $subscription_id The subscription ID.
     * @return bool True if in terminal state.
     */
    public function is_processed(int $subscription_id): bool;
    
    /**
     * Get current status.
     *
     * @param int $subscription_id The subscription ID.
     * @return Migration_Status|null Current status or null if not started.
     */
    public function get_status(int $subscription_id): ?Migration_Status;
    
    /**
     * Get failed reason.
     *
     * @param int $subscription_id The subscription ID.
     * @return string|null Error message if failed, null otherwise.
     */
    public function get_failed_reason(int $subscription_id): ?string;
    
    /**
     * Get migration attempts count.
     *
     * @param int $subscription_id The subscription ID.
     * @return int Number of migration attempts.
     */
    public function get_attempts(int $subscription_id): int;
    
    /**
     * Get all pending subscriptions for a payment method.
     *
     * @param string $payment_method The payment method to query.
     * @param int $limit Maximum number of subscriptions to return.
     * @return array<int> Array of subscription IDs.
     */
    public function get_pending_subscriptions(string $payment_method, int $limit = 100): array;
    
    /**
     * Get migration statistics.
     *
     * @param string $payment_method The payment method to query.
     * @return array Status counts keyed by status value.
     */
    public function get_stats(string $payment_method): array;
}
