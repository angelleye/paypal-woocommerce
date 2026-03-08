<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Services;

use AngellEYE\PayPal\Migration\Contracts\Migration_State_Storage_Interface;
use AngellEYE\PayPal\Migration\DTOs\Batch_Result;
use AngellEYE\PayPal\Migration\DTOs\Migration_Result;
use AngellEYE\PayPal\Migration\Enums\Migration_Status;
use Exception;
use WC_Subscription;

/**
 * Main service for subscription migration operations.
 * 
 * @package AngellEYE\PayPal\Migration\Services
 * @since 1.0.0
 */
class Subscription_Migration_Service {
    
    private Migration_State_Storage_Interface $state_storage;
    private Payment_Token_Validator $token_validator;
    private Payment_Method_Updater $payment_method_updater;
    
    /**
     * Constructor.
     *
     * @param Migration_State_Storage_Interface $state_storage State storage service.
     * @param Payment_Token_Validator $token_validator Token validator.
     * @param Payment_Method_Updater $payment_method_updater Payment method updater.
     */
    public function __construct(
        Migration_State_Storage_Interface $state_storage,
        Payment_Token_Validator $token_validator,
        Payment_Method_Updater $payment_method_updater
    ) {
        $this->state_storage = $state_storage;
        $this->token_validator = $token_validator;
        $this->payment_method_updater = $payment_method_updater;
    }
    
    /**
     * Process a batch of subscriptions.
     *
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @param int $batch_size Number of subscriptions to process.
     * @return Batch_Result
     */
    public function process_batch(
        string $from_payment_method,
        string $to_payment_method,
        int $batch_size = 100
    ): Batch_Result {
        $subscription_ids = $this->state_storage->get_pending_subscriptions(
            $from_payment_method,
            $batch_size
        );
        
        if (empty($subscription_ids)) {
            return new Batch_Result([], false);
        }
        
        $results = [];
        foreach ($subscription_ids as $subscription_id) {
            $results[] = $this->process_single(
                $subscription_id,
                $from_payment_method,
                $to_payment_method
            );
        }
        
        // Check if there are more pending subscriptions
        $remaining = $this->state_storage->get_pending_subscriptions($from_payment_method, 1);
        $has_more = !empty($remaining);
        
        return new Batch_Result($results, $has_more);
    }
    
    /**
     * Process a single subscription.
     *
     * @param int $subscription_id Subscription ID.
     * @param string $from_payment_method Source payment method.
     * @param string $to_payment_method Target payment method.
     * @return Migration_Result
     */
    public function process_single(
        int $subscription_id,
        string $from_payment_method,
        string $to_payment_method
    ): Migration_Result {
        
        // Check if already processed
        if ($this->state_storage->is_processed($subscription_id)) {
            $current_status = $this->state_storage->get_status($subscription_id);
            
            return Migration_Result::skipped(
                $subscription_id,
                Migration_Status::SKIPPED_EXCLUDED,
                __('Already processed', 'paypal-for-woocommerce'),
                ['previous_status' => $current_status?->value]
            );
        }
        
        // Mark as started
        $this->state_storage->mark_started($subscription_id);
        
        // Get subscription
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            $this->state_storage->mark_failed(
                $subscription_id,
                'data_error',
                __('Subscription not found', 'paypal-for-woocommerce')
            );
            
            return Migration_Result::failed(
                $subscription_id,
                Migration_Status::FAILED_DATA_ERROR,
                'SUBSCRIPTION_NOT_FOUND',
                __('Subscription could not be loaded', 'paypal-for-woocommerce'),
                ['subscription_id' => $subscription_id]
            );
        }
        
        // Validate token
        if (!$this->token_validator->has_valid_token($subscription)) {
            $this->state_storage->mark_failed($subscription_id, 'no_token');
            
            // Add order note only on first attempt
            if ($this->state_storage->get_attempts($subscription_id) === 1) {
                $subscription->add_order_note(
                    __('Migration failed: No valid payment token found for subscription.', 'paypal-for-woocommerce')
                );
                $subscription->save();
            }
            
            return Migration_Result::failed(
                $subscription_id,
                Migration_Status::FAILED_NO_TOKEN,
                'NO_VALID_TOKEN',
                __('No valid payment token found in subscription meta', 'paypal-for-woocommerce'),
                [
                    'subscription_id' => $subscription_id,
                    'token_attempts' => $this->token_validator->get_all_token_attempts($subscription),
                ]
            );
        }
        
        // Update payment method
        try {
            $update_result = $this->payment_method_updater->update($subscription, $to_payment_method);
            $this->state_storage->mark_completed($subscription_id);
            
            return Migration_Result::success(
                $subscription_id,
                [
                    'old_payment_method' => $from_payment_method,
                    'new_payment_method' => $to_payment_method,
                    'update_result' => $update_result,
                    'token_details' => $this->token_validator->get_token_details($subscription),
                ]
            );
            
        } catch (Exception $e) {
            $this->state_storage->mark_failed(
                $subscription_id,
                'api_error',
                $e->getMessage()
            );
            
            return Migration_Result::failed(
                $subscription_id,
                Migration_Status::FAILED_API_ERROR,
                'UPDATE_ERROR',
                $e->getMessage(),
                [
                    'subscription_id' => $subscription_id,
                    'old_payment_method' => $from_payment_method,
                    'new_payment_method' => $to_payment_method,
                ]
            );
        }
    }
    
    /**
     * Retry a failed migration.
     *
     * @param int $subscription_id Subscription ID.
     * @param string $to_payment_method Target payment method.
     * @return Migration_Result
     * @throws \InvalidArgumentException If subscription not found.
     */
    public function retry(int $subscription_id, string $to_payment_method): Migration_Result {
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            throw new \InvalidArgumentException(
                sprintf(__('Subscription %d not found', 'paypal-for-woocommerce'), $subscription_id)
            );
        }
        
        $from_payment_method = $subscription->get_meta('_angelleye_ppcp_old_payment_method')
            ?: $subscription->get_payment_method();

        return $this->process_single($subscription_id, $from_payment_method, $to_payment_method);
    }
    
    /**
     * Get migration statistics.
     *
     * @param string $payment_method Payment method.
     * @return array
     */
    public function get_stats(string $payment_method): array {
        return $this->state_storage->get_stats($payment_method);
    }
}
