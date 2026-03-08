<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Enums;

/**
 * Migration status enum.
 * 
 * @package AngellEYE\PayPal\Migration\Enums
 * @since 1.0.0
 */
enum Migration_Status: string {
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED_NO_TOKEN = 'failed_no_token';
    case FAILED_API_ERROR = 'failed_api_error';
    case FAILED_DATA_ERROR = 'failed_data_error';
    case SKIPPED_EXCLUDED = 'skipped_excluded';
    case SKIPPED_MANUAL = 'skipped_manual';
    
    /**
     * Check if status is a terminal state (won't be retried automatically).
     *
     * @return bool True if terminal state.
     */
    public function is_terminal(): bool {
        return match($this) {
            self::COMPLETED,
            self::FAILED_NO_TOKEN,
            self::FAILED_API_ERROR,
            self::FAILED_DATA_ERROR,
            self::SKIPPED_EXCLUDED,
            self::SKIPPED_MANUAL => true,
            default => false,
        };
    }
    
    /**
     * Check if status represents failure.
     *
     * @return bool True if failed status.
     */
    public function is_failure(): bool {
        return match($this) {
            self::FAILED_NO_TOKEN,
            self::FAILED_API_ERROR,
            self::FAILED_DATA_ERROR => true,
            default => false,
        };
    }
    
    /**
     * Get human-readable label.
     *
     * @return string Label for display.
     */
    public function label(): string {
        return match($this) {
            self::NOT_STARTED => __('Not Started', 'paypal-for-woocommerce'),
            self::IN_PROGRESS => __('In Progress', 'paypal-for-woocommerce'),
            self::COMPLETED => __('Completed', 'paypal-for-woocommerce'),
            self::FAILED_NO_TOKEN => __('Failed - No Payment Token', 'paypal-for-woocommerce'),
            self::FAILED_API_ERROR => __('Failed - API Error', 'paypal-for-woocommerce'),
            self::FAILED_DATA_ERROR => __('Failed - Data Error', 'paypal-for-woocommerce'),
            self::SKIPPED_EXCLUDED => __('Skipped - Excluded', 'paypal-for-woocommerce'),
            self::SKIPPED_MANUAL => __('Skipped - Manual Review', 'paypal-for-woocommerce'),
        };
    }
    
    /**
     * Get CSS class for status display.
     *
     * @return string CSS class name.
     */
    public function css_class(): string {
        return match($this) {
            self::COMPLETED => 'status-completed',
            self::FAILED_NO_TOKEN, self::FAILED_API_ERROR, self::FAILED_DATA_ERROR => 'status-failed',
            self::SKIPPED_EXCLUDED, self::SKIPPED_MANUAL => 'status-skipped',
            self::IN_PROGRESS => 'status-in-progress',
            default => 'status-pending',
        };
    }
}
