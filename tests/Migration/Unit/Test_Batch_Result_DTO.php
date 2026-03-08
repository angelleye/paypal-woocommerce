<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Tests\Unit;

use AngellEYE\PayPal\Migration\DTOs\Batch_Result;
use AngellEYE\PayPal\Migration\DTOs\Migration_Result;
use AngellEYE\PayPal\Migration\Enums\Migration_Status;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Batch_Result DTO.
 * 
 * @package AngellEYE\PayPal\Migration\Tests\Unit
 * @covers \AngellEYE\PayPal\Migration\DTOs\Batch_Result
 */
class Test_Batch_Result_DTO extends TestCase {
    
    private function create_results(): array {
        return [
            Migration_Result::success(1),
            Migration_Result::success(2),
            Migration_Result::failed(3, Migration_Status::FAILED_NO_TOKEN, 'ERR1', 'Error 1'),
            Migration_Result::failed(4, Migration_Status::FAILED_API_ERROR, 'ERR2', 'Error 2'),
            Migration_Result::skipped(5, Migration_Status::SKIPPED_EXCLUDED, 'Excluded'),
        ];
    }
    
    public function test_calculates_totals_correctly(): void {
        $result = new Batch_Result($this->create_results());
        
        $this->assertEquals(5, $result->total);
        $this->assertEquals(2, $result->successful);
        $this->assertEquals(2, $result->failed);
        $this->assertEquals(1, $result->skipped);
    }
    
    public function test_success_rate_calculation(): void {
        $result = new Batch_Result($this->create_results());
        
        $this->assertEquals(40.0, $result->success_rate()); // 2/5 = 40%
    }
    
    public function test_success_rate_returns_zero_for_empty_batch(): void {
        $result = new Batch_Result([]);
        
        $this->assertEquals(0.0, $result->success_rate());
    }
    
    public function test_get_failures_returns_only_failed_results(): void {
        $result = new Batch_Result($this->create_results());
        $failures = $result->get_failures();
        
        $this->assertCount(2, $failures);
        foreach ($failures as $failure) {
            $this->assertTrue($failure->is_failure());
        }
    }
    
    public function test_get_successes_returns_only_successful_results(): void {
        $result = new Batch_Result($this->create_results());
        $successes = $result->get_successes();
        
        $this->assertCount(2, $successes);
        foreach ($successes as $success) {
            $this->assertTrue($success->is_success());
        }
    }
    
    public function test_get_skipped_returns_only_skipped_results(): void {
        $result = new Batch_Result($this->create_results());
        $skipped = $result->get_skipped();
        
        $this->assertCount(1, $skipped);
        foreach ($skipped as $skip) {
            $this->assertTrue($skip->is_skipped());
        }
    }
    
    public function test_has_failures_returns_true_when_failures_exist(): void {
        $result = new Batch_Result($this->create_results());
        
        $this->assertTrue($result->has_failures());
    }
    
    public function test_has_failures_returns_false_when_no_failures(): void {
        $result = new Batch_Result([
            Migration_Result::success(1),
            Migration_Result::success(2),
        ]);
        
        $this->assertFalse($result->has_failures());
    }
    
    public function test_has_more_flag_is_stored(): void {
        $result = new Batch_Result([], true, 'next_token');
        
        $this->assertTrue($result->has_more);
        $this->assertEquals('next_token', $result->next_batch_token);
    }
    
    public function test_to_array_contains_all_fields(): void {
        $result = new Batch_Result($this->create_results(), true);
        $array = $result->to_array();
        
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('successful', $array);
        $this->assertArrayHasKey('failed', $array);
        $this->assertArrayHasKey('skipped', $array);
        $this->assertArrayHasKey('success_rate', $array);
        $this->assertArrayHasKey('has_more', $array);
        $this->assertArrayHasKey('next_batch_token', $array);
        $this->assertArrayHasKey('results', $array);
        
        $this->assertEquals(5, $array['total']);
        $this->assertEquals(40.0, $array['success_rate']);
        $this->assertTrue($array['has_more']);
    }
}
