<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\State;

use AngellEYE\PayPal\Migration\Contracts\Migration_State_Storage_Interface;
use AngellEYE\PayPal\Migration\Enums\Migration_Status;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * HPOS-compatible migration state storage implementation.
 * 
 * @package AngellEYE\PayPal\Migration\State
 * @since 1.0.0
 */
class HPOS_Migration_State_Storage implements Migration_State_Storage_Interface {
    
    private bool $is_hpos_enabled;
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->is_hpos_enabled = OrderUtil::custom_orders_table_usage_is_enabled();
    }
    
    /**
     * @inheritDoc
     */
    public function mark_started(int $subscription_id): void {
        $this->update_meta($subscription_id, self::META_STATUS, Migration_Status::IN_PROGRESS->value);
        
        $attempts = (int) $this->get_meta($subscription_id, self::META_ATTEMPTS, 0);
        $this->update_meta($subscription_id, self::META_ATTEMPTS, $attempts + 1);
        
        if ($attempts === 0) {
            $this->update_meta($subscription_id, self::META_STARTED_AT, time());
        }
    }
    
    /**
     * @inheritDoc
     */
    public function mark_completed(int $subscription_id): void {
        $this->update_meta($subscription_id, self::META_STATUS, Migration_Status::COMPLETED->value);
        $this->update_meta($subscription_id, self::META_COMPLETED_AT, time());
        $this->delete_meta($subscription_id, self::META_ERROR_CODE);
        $this->delete_meta($subscription_id, self::META_ERROR_MESSAGE);
    }
    
    /**
     * @inheritDoc
     */
    public function mark_failed(int $subscription_id, string $reason_code, ?string $message = null): void {
        $status = match($reason_code) {
            'no_token' => Migration_Status::FAILED_NO_TOKEN,
            'api_error' => Migration_Status::FAILED_API_ERROR,
            'data_error' => Migration_Status::FAILED_DATA_ERROR,
            default => Migration_Status::FAILED_DATA_ERROR,
        };
        
        $this->update_meta($subscription_id, self::META_STATUS, $status->value);
        $this->update_meta($subscription_id, self::META_ERROR_CODE, $reason_code);
        if ($message) {
            $this->update_meta($subscription_id, self::META_ERROR_MESSAGE, $message);
        }
        $this->update_meta($subscription_id, self::META_COMPLETED_AT, time());
    }
    
    /**
     * @inheritDoc
     */
    public function mark_skipped(int $subscription_id, string $reason): void {
        $status = match($reason) {
            'excluded' => Migration_Status::SKIPPED_EXCLUDED,
            'manual' => Migration_Status::SKIPPED_MANUAL,
            default => Migration_Status::SKIPPED_MANUAL,
        };
        
        $this->update_meta($subscription_id, self::META_STATUS, $status->value);
        $this->update_meta($subscription_id, self::META_ERROR_MESSAGE, $reason);
        $this->update_meta($subscription_id, self::META_COMPLETED_AT, time());
    }
    
    /**
     * @inheritDoc
     */
    public function is_processed(int $subscription_id): bool {
        $status = $this->get_status($subscription_id);
        return $status?->is_terminal() ?? false;
    }
    
    /**
     * @inheritDoc
     */
    public function get_status(int $subscription_id): ?Migration_Status {
        $status_value = $this->get_meta($subscription_id, self::META_STATUS);
        if (!$status_value) {
            return null;
        }
        return Migration_Status::tryFrom($status_value);
    }
    
    /**
     * @inheritDoc
     */
    public function get_failed_reason(int $subscription_id): ?string {
        return $this->get_meta($subscription_id, self::META_ERROR_MESSAGE) ?: null;
    }
    
    /**
     * @inheritDoc
     */
    public function get_attempts(int $subscription_id): int {
        return (int) $this->get_meta($subscription_id, self::META_ATTEMPTS, 0);
    }
    
    /**
     * @inheritDoc
     */
    public function get_pending_subscriptions(string $payment_method, int $limit = 100): array {
        $args = [
            'type' => 'shop_subscription',
            'limit' => $limit,
            'return' => 'ids',
            'status' => ['wc-active', 'wc-on-hold'],
            'payment_method' => $payment_method,
            'orderby' => 'ID',
            'order' => 'ASC',
        ];
        
        // Exclude already processed subscriptions
        $args['meta_query'] = [
            'relation' => 'OR',
            [
                'key' => self::META_STATUS,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => self::META_STATUS,
                'value' => [
                    Migration_Status::NOT_STARTED->value,
                    Migration_Status::IN_PROGRESS->value,
                ],
                'compare' => 'IN',
            ],
        ];
        
        return wc_get_orders($args);
    }
    
    /**
     * @inheritDoc
     */
    public function get_stats(string $payment_method): array {
        $status_counts = [];
        
        foreach (Migration_Status::cases() as $status) {
            $args = [
                'type' => 'shop_subscription',
                'status' => ['wc-active', 'wc-on-hold', 'wc-pending-cancel'],
                'payment_method' => $payment_method,
                'limit' => -1,
                'return' => 'ids',
                'meta_query' => [
                    [
                        'key' => self::META_STATUS,
                        'value' => $status->value,
                        'compare' => '=',
                    ],
                ],
            ];
            
            $status_counts[$status->value] = count(wc_get_orders($args));
        }
        
        // Count not started
        $args = [
            'type' => 'shop_subscription',
            'status' => ['wc-active', 'wc-on-hold', 'wc-pending-cancel'],
            'payment_method' => $payment_method,
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => self::META_STATUS,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];
        $status_counts['not_started'] = count(wc_get_orders($args));
        
        return $status_counts;
    }
    
    /**
     * HPOS-compatible meta update.
     *
     * @param int $subscription_id Subscription ID.
     * @param string $key Meta key.
     * @param mixed $value Meta value.
     * @return void
     */
    private function update_meta(int $subscription_id, string $key, mixed $value): void {
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        if ($this->is_hpos_enabled) {
            $subscription->update_meta_data($key, $value);
            $subscription->save();
        } else {
            update_post_meta($subscription_id, $key, $value);
        }
    }
    
    /**
     * HPOS-compatible meta retrieval.
     *
     * @param int $subscription_id Subscription ID.
     * @param string $key Meta key.
     * @param mixed $default Default value.
     * @return mixed
     */
    private function get_meta(int $subscription_id, string $key, mixed $default = null): mixed {
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            return $default;
        }
        
        $value = $subscription->get_meta($key, true);
        
        if ($value === '' || $value === null) {
            if (!$this->is_hpos_enabled) {
                $value = get_post_meta($subscription_id, $key, true);
            }
        }
        
        return $value !== '' && $value !== null ? $value : $default;
    }
    
    /**
     * HPOS-compatible meta deletion.
     *
     * @param int $subscription_id Subscription ID.
     * @param string $key Meta key.
     * @return void
     */
    private function delete_meta(int $subscription_id, string $key): void {
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        if ($this->is_hpos_enabled) {
            $subscription->delete_meta_data($key);
            $subscription->save();
        } else {
            delete_post_meta($subscription_id, $key);
        }
    }
}
