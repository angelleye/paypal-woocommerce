<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Tests\Unit;

use AngellEYE\PayPal\Migration\Enums\Migration_Status;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Migration_Status enum.
 * 
 * @package AngellEYE\PayPal\Migration\Tests\Unit
 * @covers \AngellEYE\PayPal\Migration\Enums\Migration_Status
 */
class Test_Migration_Status_Enum extends TestCase {
    
    public function test_is_terminal_returns_true_for_completed(): void {
        $this->assertTrue(Migration_Status::COMPLETED->is_terminal());
    }
    
    public function test_is_terminal_returns_true_for_failed_no_token(): void {
        $this->assertTrue(Migration_Status::FAILED_NO_TOKEN->is_terminal());
    }
    
    public function test_is_terminal_returns_false_for_in_progress(): void {
        $this->assertFalse(Migration_Status::IN_PROGRESS->is_terminal());
    }
    
    public function test_is_terminal_returns_false_for_not_started(): void {
        $this->assertFalse(Migration_Status::NOT_STARTED->is_terminal());
    }
    
    public function test_is_failure_returns_true_for_failed_statuses(): void {
        $this->assertTrue(Migration_Status::FAILED_NO_TOKEN->is_failure());
        $this->assertTrue(Migration_Status::FAILED_API_ERROR->is_failure());
        $this->assertTrue(Migration_Status::FAILED_DATA_ERROR->is_failure());
    }
    
    public function test_is_failure_returns_false_for_non_failed_statuses(): void {
        $this->assertFalse(Migration_Status::COMPLETED->is_failure());
        $this->assertFalse(Migration_Status::IN_PROGRESS->is_failure());
        $this->assertFalse(Migration_Status::SKIPPED_EXCLUDED->is_failure());
    }
    
    public function test_label_returns_expected_strings(): void {
        $this->assertEquals('Completed', Migration_Status::COMPLETED->label());
        $this->assertEquals('Failed - No Payment Token', Migration_Status::FAILED_NO_TOKEN->label());
        $this->assertEquals('In Progress', Migration_Status::IN_PROGRESS->label());
    }
    
    public function test_css_class_returns_expected_values(): void {
        $this->assertEquals('status-completed', Migration_Status::COMPLETED->css_class());
        $this->assertEquals('status-failed', Migration_Status::FAILED_NO_TOKEN->css_class());
        $this->assertEquals('status-in-progress', Migration_Status::IN_PROGRESS->css_class());
    }
    
    public function test_all_statuses_can_be_instantiated_from_string(): void {
        foreach (Migration_Status::cases() as $status) {
            $from_string = Migration_Status::tryFrom($status->value);
            $this->assertSame($status, $from_string);
        }
    }
    
    public function test_tryFrom_returns_null_for_invalid_value(): void {
        $this->assertNull(Migration_Status::tryFrom('invalid_status'));
    }
}
