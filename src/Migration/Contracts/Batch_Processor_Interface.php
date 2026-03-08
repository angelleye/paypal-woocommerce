<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Contracts;

/**
 * Interface for batch processing implementations.
 * 
 * @package AngellEYE\PayPal\Migration\Contracts
 * @since 1.0.0
 */
interface Batch_Processor_Interface {
    
    /**
     * Start the migration process.
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @param int $batch_size Number of subscriptions per batch.
     * @return void
     */
    public function start(string $from_payment_method, string $to_payment_method, int $batch_size = 100): void;
    
    /**
     * Schedule next batch.
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @param int $batch_size Number of subscriptions per batch.
     * @param int|null $timestamp Optional timestamp for scheduling.
     * @return void
     */
    public function schedule_next(string $from_payment_method, string $to_payment_method, int $batch_size, ?int $timestamp = null): void;
    
    /**
     * Pause migration.
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @return void
     */
    public function pause(string $from_payment_method, string $to_payment_method): void;
    
    /**
     * Cancel migration.
     *
     * @param string|null $from_payment_method Optional source payment method to cancel specific migration.
     * @param string|null $to_payment_method Optional target payment method.
     * @return void
     */
    public function cancel(?string $from_payment_method = null, ?string $to_payment_method = null): void;
    
    /**
     * Check if migration is running.
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @return bool True if migration is scheduled or running.
     */
    public function is_running(string $from_payment_method, string $to_payment_method): bool;
}
