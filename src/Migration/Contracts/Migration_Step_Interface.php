<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Contracts;

use AngellEYE\PayPal\Migration\DTOs\Migration_Result;
use WC_Subscription;

/**
 * Interface for individual migration steps.
 * 
 * @package AngellEYE\PayPal\Migration\Contracts
 * @since 1.0.0
 */
interface Migration_Step_Interface {
    /**
     * Check if this step can process the subscription.
     *
     * @param WC_Subscription $subscription The subscription to check.
     * @return bool True if step can process this subscription.
     */
    public function can_process(WC_Subscription $subscription): bool;
    
    /**
     * Execute the migration step.
     *
     * @param WC_Subscription $subscription The subscription to process.
     * @return Migration_Result Result of the step execution.
     */
    public function process(WC_Subscription $subscription): Migration_Result;
    
    /**
     * Rollback changes if step fails.
     *
     * @param WC_Subscription $subscription The subscription to rollback.
     * @return void
     */
    public function rollback(WC_Subscription $subscription): void;
    
    /**
     * Get step name for logging.
     *
     * @return string Human-readable step name.
     */
    public function get_name(): string;
}
