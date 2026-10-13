<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Tests\Unit;

use AngellEYE\PayPal\Migration\DTOs\Migration_Result;
use AngellEYE\PayPal\Migration\Enums\Migration_Status;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Migration_Result DTO.
 * 
 * @package AngellEYE\PayPal\Migration\Tests\Unit
 * @covers \AngellEYE\PayPal\Migration\DTOs\Migration_Result
 */
class Test_Migration_Result_DTO extends TestCase {
    
    public function test_success_creates_completed_result(): void {
        $result = Migration_Result::success(123, ['key' => 'value']);
        
        $this->assertTrue($result->is_success());
        $this->assertEquals(Migration_Status::COMPLETED, $result->status);
        $this->assertEquals(123, $result->subscription_id);
        $this->assertEquals(['key' => 'value'], $result->context);
        $this->assertNull($result->error_code);
        $this->assertNull($result->error_message);
    }
    
    public function test_failed_creates_failure_result(): void {
        $result = Migration_Result::failed(
            456,
            Migration_Status::FAILED_NO_TOKEN,
            'NO_TOKEN',
            'No token found',
            ['attempt' => 1]
        );
        
        $this->assertTrue($result->is_failure());
        $this->assertFalse($result->is_success());
        $this->assertEquals(Migration_Status::FAILED_NO_TOKEN, $result->status);
        $this->assertEquals(456, $result->subscription_id);
        $this->assertEquals('NO_TOKEN', $result->error_code);
        $this->assertEquals('No token found', $result->error_message);
    }
    
    public function test_failed_throws_exception_for_non_failure_status(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Status must be a failure status');
        
        Migration_Result::failed(
            123,
            Migration_Status::COMPLETED, // Not a failure status
            'ERROR',
            'Error message'
        );
    }
    
    public function test_skipped_creates_skipped_result(): void {
        $result = Migration_Result::skipped(
            789,
            Migration_Status::SKIPPED_EXCLUDED,
            'Already processed'
        );
        
        $this->assertTrue($result->is_skipped());
        $this->assertFalse($result->is_success());
        $this->assertFalse($result->is_failure());
        $this->assertEquals(Migration_Status::SKIPPED_EXCLUDED, $result->status);
        $this->assertEquals('Already processed', $result->error_message);
    }
    
    public function test_to_array_contains_all_fields(): void {
        $result = Migration_Result::success(123, ['extra' => 'data']);
        $array = $result->to_array();
        
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('status_label', $array);
        $this->assertArrayHasKey('subscription_id', $array);
        $this->assertArrayHasKey('error_code', $array);
        $this->assertArrayHasKey('error_message', $array);
        $this->assertArrayHasKey('context', $array);
        $this->assertArrayHasKey('processed_at', $array);
        
        $this->assertEquals('completed', $array['status']);
        $this->assertEquals('Completed', $array['status_label']);
        $this->assertEquals(['extra' => 'data'], $array['context']);
    }
    
    public function test_processed_at_is_set_automatically(): void {
        $before = new \DateTimeImmutable();
        $result = Migration_Result::success(123);
        $after = new \DateTimeImmutable();
        
        $this->assertNotNull($result->processed_at);
        $this->assertGreaterThanOrEqual($before, $result->processed_at);
        $this->assertLessThanOrEqual($after, $result->processed_at);
    }
    
    public function test_is_skipped_returns_true_for_skipped_statuses(): void {
        $skipped_excluded = Migration_Result::skipped(1, Migration_Status::SKIPPED_EXCLUDED, 'test');
        $skipped_manual = Migration_Result::skipped(2, Migration_Status::SKIPPED_MANUAL, 'test');
        
        $this->assertTrue($skipped_excluded->is_skipped());
        $this->assertTrue($skipped_manual->is_skipped());
    }
    
    public function test_is_skipped_returns_false_for_non_skipped(): void {
        $success = Migration_Result::success(1);
        $failed = Migration_Result::failed(2, Migration_Status::FAILED_NO_TOKEN, 'ERR', 'msg');
        
        $this->assertFalse($success->is_skipped());
        $this->assertFalse($failed->is_skipped());
    }
}
