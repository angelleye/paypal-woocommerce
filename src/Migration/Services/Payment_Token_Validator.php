<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Services;

use WC_Subscription;

/**
 * Service for validating payment tokens in subscriptions.
 * 
 * @package AngellEYE\PayPal\Migration\Services
 * @since 1.0.0
 */
class Payment_Token_Validator {
    
    /**
     * Meta keys to check for payment tokens.
     *
     * @var array<string>
     */
    private array $meta_keys = [
        '_payment_tokens_id',
        'payment_token_id',
        '_ppec_billing_agreement_id',
        '_paypal_subscription_id',
    ];
    
    /**
     * Check if subscription has a valid payment token.
     *
     * @param WC_Subscription $subscription The subscription to check.
     * @return bool True if valid token exists.
     */
    public function has_valid_token(WC_Subscription $subscription): bool {
        foreach ($this->meta_keys as $key) {
            $token = $this->get_token_value($subscription, $key);
            
            if (!empty($token) && $this->validate_token_format($key, $token)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get detailed token information.
     *
     * @param WC_Subscription $subscription The subscription to check.
     * @return array|null Token details or null if not found.
     */
    public function get_token_details(WC_Subscription $subscription): ?array {
        foreach ($this->meta_keys as $key) {
            $token = $this->get_token_value($subscription, $key);
            
            if (!empty($token) && $this->validate_token_format($key, $token)) {
                return [
                    'meta_key' => $key,
                    'token_value' => $this->mask_token($token),
                    'token_type' => $this->get_token_type($key),
                    'is_valid' => true,
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Get all token attempts for debugging.
     *
     * @param WC_Subscription $subscription The subscription to check.
     * @return array Array of token check results.
     */
    public function get_all_token_attempts(WC_Subscription $subscription): array {
        $attempts = [];
        
        foreach ($this->meta_keys as $key) {
            $token = $this->get_token_value($subscription, $key);
            $attempts[] = [
                'meta_key' => $key,
                'found' => !empty($token),
                'valid' => !empty($token) && $this->validate_token_format($key, $token),
                'token_preview' => $token ? $this->mask_token($token) : null,
            ];
        }
        
        return $attempts;
    }
    
    /**
     * Get token value from subscription meta.
     *
     * @param WC_Subscription $subscription The subscription.
     * @param string $key Meta key.
     * @return string|null Token value or null.
     */
    private function get_token_value(WC_Subscription $subscription, string $key): ?string {
        // Try subscription meta first (HPOS compatible)
        $value = $subscription->get_meta($key, true);
        
        if (!empty($value)) {
            return $value;
        }
        
        // Fallback to direct postmeta for legacy data
        $value = get_post_meta($subscription->get_id(), $key, true);
        
        return !empty($value) ? $value : null;
    }
    
    /**
     * Validate token format based on type.
     *
     * @param string $key Meta key.
     * @param string $token Token value.
     * @return bool True if valid format.
     */
    private function validate_token_format(string $key, string $token): bool {
        return match($key) {
            '_paypal_subscription_id' => str_starts_with($token, 'B-'),
            default => strlen($token) >= 10,
        };
    }
    
    /**
     * Get human-readable token type.
     *
     * @param string $key Meta key.
     * @return string Token type label.
     */
    private function get_token_type(string $key): string {
        return match($key) {
            '_payment_tokens_id' => __('Payment Token', 'paypal-for-woocommerce'),
            'payment_token_id' => __('Legacy Payment Token', 'paypal-for-woocommerce'),
            '_ppec_billing_agreement_id' => __('PayPal Express Billing Agreement', 'paypal-for-woocommerce'),
            '_paypal_subscription_id' => __('PayPal Subscription Profile', 'paypal-for-woocommerce'),
            default => __('Unknown', 'paypal-for-woocommerce'),
        };
    }
    
    /**
     * Mask token for safe logging.
     *
     * @param string $token Token value.
     * @return string Masked token.
     */
    private function mask_token(string $token): string {
        $length = strlen($token);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        return substr($token, 0, 4) . str_repeat('*', $length - 8) . substr($token, -4);
    }
}
