<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Tests\Unit;

use AngellEYE\PayPal\Migration\Contracts\Migration_State_Storage_Interface;
use AngellEYE\PayPal\Migration\DTOs\Migration_Result;
use AngellEYE\PayPal\Migration\Enums\Migration_Status;
use AngellEYE\PayPal\Migration\Services\Payment_Method_Updater;
use AngellEYE\PayPal\Migration\Services\Payment_Token_Validator;
use AngellEYE\PayPal\Migration\Services\Subscription_Migration_Service;
use PHPUnit\Framework\TestCase;
use WC_Subscription;

/**
 * Tests for Subscription_Migration_Service.
 *
 * @covers \AngellEYE\PayPal\Migration\Services\Subscription_Migration_Service
 */
class Test_Subscription_Migration_Service extends TestCase {

    private Subscription_Migration_Service $service;
    private $state_storage;
    private $token_validator;
    private $payment_method_updater;

    protected function setUp(): void {
        parent::setUp();

        $this->state_storage = $this->createMock(Migration_State_Storage_Interface::class);
        $this->token_validator = $this->createMock(Payment_Token_Validator::class);
        $this->payment_method_updater = $this->createMock(Payment_Method_Updater::class);

        $this->service = new Subscription_Migration_Service(
            $this->state_storage,
            $this->token_validator,
            $this->payment_method_updater
        );

        // Clear the global test subscriptions registry
        global $_test_subscriptions;
        $_test_subscriptions = [];
    }

    protected function tearDown(): void {
        global $_test_subscriptions;
        $_test_subscriptions = [];
        parent::tearDown();
    }

    /**
     * Register a mock subscription so wcs_get_subscription() returns it.
     */
    private function register_subscription(int $id, ?WC_Subscription $subscription = null): WC_Subscription {
        global $_test_subscriptions;

        if ($subscription === null) {
            $subscription = $this->createMock(WC_Subscription::class);
            $subscription->method('get_id')->willReturn($id);
        }

        $_test_subscriptions[$id] = $subscription;
        return $subscription;
    }

    // ── process_single ──────────────────────────────────────────────────

    public function test_process_single_skips_already_processed(): void {
        $this->state_storage->expects($this->once())
            ->method('is_processed')
            ->with(123)
            ->willReturn(true);

        $this->state_storage->method('get_status')
            ->willReturn(Migration_Status::COMPLETED);

        $result = $this->service->process_single(123, 'paypal_express', 'angelleye_ppcp');

        $this->assertTrue($result->is_skipped());
        $this->assertEquals(Migration_Status::SKIPPED_EXCLUDED, $result->status);
    }

    public function test_process_single_fails_for_missing_subscription(): void {
        $this->state_storage->method('is_processed')->willReturn(false);
        // No subscription registered → wcs_get_subscription returns null

        $this->state_storage->expects($this->once())
            ->method('mark_failed')
            ->with(999, 'data_error', $this->isType('string'));

        $result = $this->service->process_single(999, 'paypal_express', 'angelleye_ppcp');

        $this->assertTrue($result->is_failure());
        $this->assertEquals(Migration_Status::FAILED_DATA_ERROR, $result->status);
        $this->assertEquals('SUBSCRIPTION_NOT_FOUND', $result->error_code);
    }

    public function test_process_single_fails_when_no_token(): void {
        $subscription = $this->register_subscription(456);

        $this->state_storage->method('is_processed')->willReturn(false);
        $this->state_storage->method('get_attempts')->willReturn(1);

        $this->token_validator->expects($this->once())
            ->method('has_valid_token')
            ->with($subscription)
            ->willReturn(false);

        $this->token_validator->method('get_all_token_attempts')->willReturn([]);

        $this->state_storage->expects($this->once())
            ->method('mark_failed')
            ->with(456, 'no_token');

        $result = $this->service->process_single(456, 'paypal_express', 'angelleye_ppcp');

        $this->assertTrue($result->is_failure());
        $this->assertEquals(Migration_Status::FAILED_NO_TOKEN, $result->status);
        $this->assertEquals('NO_VALID_TOKEN', $result->error_code);
    }

    public function test_process_single_succeeds_with_valid_token(): void {
        $subscription = $this->register_subscription(789);

        $this->state_storage->method('is_processed')->willReturn(false);

        $this->token_validator->method('has_valid_token')->willReturn(true);
        $this->token_validator->method('get_token_details')->willReturn([
            'meta_key' => '_payment_tokens_id',
            'token_value' => 'tok_****cdef',
            'token_type' => 'Payment Token',
            'is_valid' => true,
        ]);

        $this->payment_method_updater->expects($this->once())
            ->method('update')
            ->with($subscription, 'angelleye_ppcp')
            ->willReturn(['success' => true]);

        $this->state_storage->expects($this->once())
            ->method('mark_completed')
            ->with(789);

        $result = $this->service->process_single(789, 'paypal_express', 'angelleye_ppcp');

        $this->assertTrue($result->is_success());
        $this->assertEquals(Migration_Status::COMPLETED, $result->status);
        $this->assertArrayHasKey('old_payment_method', $result->context);
        $this->assertEquals('paypal_express', $result->context['old_payment_method']);
    }

    public function test_process_single_catches_update_exception(): void {
        $subscription = $this->register_subscription(321);

        $this->state_storage->method('is_processed')->willReturn(false);
        $this->token_validator->method('has_valid_token')->willReturn(true);

        $this->payment_method_updater->method('update')
            ->willThrowException(new \Exception('PayPal API timeout'));

        $this->state_storage->expects($this->once())
            ->method('mark_failed')
            ->with(321, 'api_error', 'PayPal API timeout');

        $result = $this->service->process_single(321, 'paypal_express', 'angelleye_ppcp');

        $this->assertTrue($result->is_failure());
        $this->assertEquals(Migration_Status::FAILED_API_ERROR, $result->status);
        $this->assertEquals('UPDATE_ERROR', $result->error_code);
        $this->assertEquals('PayPal API timeout', $result->error_message);
    }

    // ── process_batch ───────────────────────────────────────────────────

    public function test_process_batch_returns_correct_counts(): void {
        // Register mock subscriptions for all IDs
        foreach ([1, 2, 3, 4, 5] as $id) {
            $this->register_subscription($id);
        }

        // First call: return batch IDs. Second call: check remaining → empty.
        $this->state_storage->method('get_pending_subscriptions')
            ->willReturnOnConsecutiveCalls([1, 2, 3, 4, 5], []);

        $this->state_storage->method('is_processed')->willReturn(false);
        $this->state_storage->method('get_attempts')->willReturn(1);

        // First 2 have valid tokens, last 3 don't
        $call_count = 0;
        $this->token_validator->method('has_valid_token')
            ->willReturnCallback(function () use (&$call_count) {
                $call_count++;
                return $call_count <= 2;
            });

        $this->token_validator->method('get_token_details')->willReturn([
            'meta_key' => '_payment_tokens_id',
            'token_value' => '****',
        ]);
        $this->token_validator->method('get_all_token_attempts')->willReturn([]);

        $this->payment_method_updater->method('update')
            ->willReturn(['success' => true]);

        $result = $this->service->process_batch('paypal_express', 'angelleye_ppcp', 5);

        $this->assertEquals(5, $result->total);
        $this->assertEquals(2, $result->successful);
        $this->assertEquals(3, $result->failed);
        $this->assertFalse($result->has_more);
    }

    public function test_process_batch_empty_returns_zero_results(): void {
        $this->state_storage->method('get_pending_subscriptions')
            ->willReturn([]);

        $result = $this->service->process_batch('paypal_express', 'angelleye_ppcp', 10);

        $this->assertEquals(0, $result->total);
        $this->assertFalse($result->has_more);
    }

    public function test_process_batch_sets_has_more_when_remaining(): void {
        $this->register_subscription(1);
        $this->register_subscription(2);

        // First call: return 2 IDs. Second call (remaining check): return 1 ID.
        $this->state_storage->method('get_pending_subscriptions')
            ->willReturnOnConsecutiveCalls([1, 2], [3]);

        $this->state_storage->method('is_processed')->willReturn(false);
        $this->state_storage->method('get_attempts')->willReturn(1);
        $this->token_validator->method('has_valid_token')->willReturn(false);
        $this->token_validator->method('get_all_token_attempts')->willReturn([]);

        $result = $this->service->process_batch('paypal_express', 'angelleye_ppcp', 2);

        $this->assertTrue($result->has_more);
    }

    // ── retry ───────────────────────────────────────────────────────────

    public function test_retry_calls_process_single_and_returns_result(): void {
        $subscription = $this->createMock(WC_Subscription::class);
        $subscription->method('get_meta')->willReturn('');
        $subscription->method('get_payment_method')->willReturn('paypal_express');
        $subscription->method('get_id')->willReturn(500);
        $this->register_subscription(500, $subscription);

        $this->state_storage->method('is_processed')->willReturn(false);
        $this->state_storage->method('get_attempts')->willReturn(1);
        $this->token_validator->method('has_valid_token')->willReturn(true);
        $this->token_validator->method('get_token_details')->willReturn([
            'meta_key' => '_payment_tokens_id',
            'token_value' => '****',
        ]);
        $this->payment_method_updater->method('update')
            ->willReturn(['success' => true]);

        $result = $this->service->retry(500, 'angelleye_ppcp');

        $this->assertInstanceOf(Migration_Result::class, $result);
        $this->assertTrue($result->is_success());
    }

    public function test_retry_throws_for_nonexistent_subscription(): void {
        // No subscription registered → wcs_get_subscription returns null

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subscription 999 not found');

        $this->service->retry(999, 'angelleye_ppcp');
    }

    public function test_retry_uses_old_payment_method_meta_when_available(): void {
        $subscription = $this->createMock(WC_Subscription::class);
        $subscription->method('get_meta')
            ->willReturnCallback(function ($key) {
                if ($key === '_angelleye_ppcp_old_payment_method') {
                    return 'paypal_express';
                }
                return '';
            });
        $subscription->method('get_payment_method')->willReturn('angelleye_ppcp');
        $subscription->method('get_id')->willReturn(600);
        $this->register_subscription(600, $subscription);

        // process_single will be called with 'paypal_express' (from meta), not 'angelleye_ppcp' (current)
        $this->state_storage->method('is_processed')->willReturn(false);
        $this->state_storage->method('get_attempts')->willReturn(1);
        $this->token_validator->method('has_valid_token')->willReturn(false);
        $this->token_validator->method('get_all_token_attempts')->willReturn([]);

        $result = $this->service->retry(600, 'angelleye_ppcp');

        // It should have used 'paypal_express' as from_payment_method
        $this->assertTrue($result->is_failure());
        $this->assertEquals(600, $result->subscription_id);
    }
}
