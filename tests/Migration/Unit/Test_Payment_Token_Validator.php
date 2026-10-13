<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Tests\Unit;

use AngellEYE\PayPal\Migration\Services\Payment_Token_Validator;
use PHPUnit\Framework\TestCase;
use WC_Subscription;

/**
 * Tests for Payment_Token_Validator.
 * 
 * @package AngellEYE\PayPal\Migration\Tests\Unit
 * @covers \AngellEYE\PayPal\Migration\Services\Payment_Token_Validator
 */
class Test_Payment_Token_Validator extends TestCase {
    
    private Payment_Token_Validator $validator;
    
    protected function setUp(): void {
        parent::setUp();
        $this->validator = new Payment_Token_Validator();
    }
    
    /**
     * Create a mock subscription with meta values.
     */
    private function create_mock_subscription(array $meta_values): WC_Subscription {
        $subscription = $this->createMock(WC_Subscription::class);
        
        $subscription->method('get_meta')->willReturnCallback(
            function($key) use ($meta_values) {
                return $meta_values[$key] ?? '';
            }
        );
        
        $subscription->method('get_id')->willReturn(123);
        
        return $subscription;
    }
    
    public function test_has_valid_token_returns_true_for_payment_tokens_id(): void {
        $subscription = $this->create_mock_subscription([
            '_payment_tokens_id' => 'tok_valid_token_123',
        ]);
        
        $this->assertTrue($this->validator->has_valid_token($subscription));
    }
    
    public function test_has_valid_token_returns_true_for_ppec_billing_agreement(): void {
        $subscription = $this->create_mock_subscription([
            '_ppec_billing_agreement_id' => 'B-1234567890',
        ]);
        
        $this->assertTrue($this->validator->has_valid_token($subscription));
    }
    
    public function test_has_valid_token_returns_true_for_paypal_subscription_id(): void {
        $subscription = $this->create_mock_subscription([
            '_paypal_subscription_id' => 'B-9876543210',
        ]);
        
        $this->assertTrue($this->validator->has_valid_token($subscription));
    }
    
    public function test_has_valid_token_returns_false_for_invalid_subscription_id(): void {
        // PayPal subscription IDs must start with 'B-'
        $subscription = $this->create_mock_subscription([
            '_paypal_subscription_id' => 'I-9876543210', // Invalid - starts with I-
        ]);
        
        $this->assertFalse($this->validator->has_valid_token($subscription));
    }
    
    public function test_has_valid_token_returns_false_when_no_token(): void {
        $subscription = $this->create_mock_subscription([]);
        
        $this->assertFalse($this->validator->has_valid_token($subscription));
    }
    
    public function test_has_valid_token_returns_false_for_short_token(): void {
        $subscription = $this->create_mock_subscription([
            '_payment_tokens_id' => 'short', // Less than 10 chars
        ]);
        
        $this->assertFalse($this->validator->has_valid_token($subscription));
    }
    
    public function test_get_token_details_returns_correct_info(): void {
        $subscription = $this->create_mock_subscription([
            '_payment_tokens_id' => 'tok_valid_token_12345',
        ]);
        
        $details = $this->validator->get_token_details($subscription);
        
        $this->assertNotNull($details);
        $this->assertEquals('_payment_tokens_id', $details['meta_key']);
        $this->assertEquals('Payment Token', $details['token_type']);
        $this->assertTrue($details['is_valid']);
    }
    
    public function test_get_token_details_masks_token_value(): void {
        $subscription = $this->create_mock_subscription([
            '_payment_tokens_id' => 'tok_1234567890abcdef',
        ]);
        
        $details = $this->validator->get_token_details($subscription);
        
        $this->assertStringContainsString('****', $details['token_value']);
        $this->assertStringStartsWith('tok_', $details['token_value']); // First 4 chars visible
        $this->assertStringEndsWith('cdef', $details['token_value']);   // Last 4 chars visible
    }
    
    public function test_get_all_token_attempts_returns_all_checks(): void {
        $subscription = $this->create_mock_subscription([
            '_payment_tokens_id' => '',
            '_ppec_billing_agreement_id' => 'B-12345',
        ]);
        
        $attempts = $this->validator->get_all_token_attempts($subscription);
        
        $this->assertCount(4, $attempts); // 4 meta keys checked
        
        // Find the found one
        $found_attempt = array_filter($attempts, fn($a) => $a['found']);
        $this->assertCount(1, $found_attempt);
    }
}
