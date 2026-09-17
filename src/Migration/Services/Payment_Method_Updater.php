<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Services;

use Exception;
use WC_Gateway_PPCP_AngellEYE_Settings;
use WC_Subscription;

/**
 * Service for updating subscription payment methods.
 * 
 * @package AngellEYE\PayPal\Migration\Services
 * @since 1.0.0
 */
class Payment_Method_Updater {
    
    private WC_Gateway_PPCP_AngellEYE_Settings $settings;
    
    /**
     * Constructor.
     *
     * @param WC_Gateway_PPCP_AngellEYE_Settings $settings Plugin settings.
     */
    public function __construct(WC_Gateway_PPCP_AngellEYE_Settings $settings) {
        $this->settings = $settings;
    }
    
    /**
     * Update subscription payment method.
     *
     * @param WC_Subscription $subscription Subscription to update.
     * @param string $new_payment_method New payment method ID.
     * @return array Result data.
     * @throws Exception If update fails.
     */
    public function update(WC_Subscription $subscription, string $new_payment_method): array {
        $old_payment_method = $subscription->get_payment_method();
        $old_payment_method_title = $subscription->get_payment_method_title();
        
        $new_payment_method_title = $this->get_payment_method_title($new_payment_method);
        
        do_action(
            'woocommerce_subscriptions_pre_update_payment_method',
            $subscription,
            $new_payment_method,
            $old_payment_method
        );
        
        try {
            // Update payment method
            $subscription->set_payment_method($new_payment_method);
            $subscription->set_payment_method_title($new_payment_method_title);
            
            // Store old method for reference
            $subscription->update_meta_data('_old_payment_method', $old_payment_method);
            $subscription->update_meta_data('_angelleye_ppcp_old_payment_method', $old_payment_method);
            $subscription->update_meta_data('_old_payment_method_title', $old_payment_method_title);
            
            // Add order note
            $note = sprintf(
                /* translators: %1$s: old payment method, %2$s: new payment method */
                __('Payment method changed from "%1$s" to "%2$s" by Angelleye Migration.', 'paypal-for-woocommerce'),
                $old_payment_method_title ?: $old_payment_method,
                $new_payment_method_title
            );
            $subscription->add_order_note($note);
            
            $subscription->save();
            
            // Trigger actions
            do_action('woocommerce_subscription_payment_method_updated', $subscription, $new_payment_method, $old_payment_method);
            do_action("woocommerce_subscription_payment_method_updated_to_{$new_payment_method}", $subscription, $old_payment_method);
            
            if ($old_payment_method) {
                do_action("woocommerce_subscription_payment_method_updated_from_{$old_payment_method}", $subscription, $new_payment_method);
            }
            
            return [
                'success' => true,
                'old_method' => $old_payment_method,
                'new_method' => $new_payment_method,
                'old_title' => $old_payment_method_title,
                'new_title' => $new_payment_method_title,
            ];
            
        } catch (Exception $e) {
            // Add error note
            $error_note = sprintf(
                /* translators: %1$s: error message */
                __('Migration error: %1$s', 'paypal-for-woocommerce'),
                $e->getMessage()
            );
            $subscription->add_order_note($error_note);
            $subscription->save();
            
            throw $e;
        }
    }
    
    /**
     * Get payment method title.
     *
     * @param string $payment_method Payment method ID.
     * @return string Payment method title.
     */
    private function get_payment_method_title(string $payment_method): string {
        return match($payment_method) {
            'angelleye_ppcp_cc' => $this->settings->get('advanced_card_payments_title', __('Credit Card', 'paypal-for-woocommerce')),
            'angelleye_ppcp' => $this->settings->get('title', __('PayPal', 'paypal-for-woocommerce')),
            'angelleye_ppcp_google_pay' => $this->settings->get('google_pay_payments_title', __('Google Pay', 'paypal-for-woocommerce')),
            'angelleye_ppcp_apple_pay' => $this->settings->get('apple_pay_payments_title', __('Apple Pay', 'paypal-for-woocommerce')),
            default => $this->settings->get('title', __('PayPal', 'paypal-for-woocommerce')),
        };
    }
}
