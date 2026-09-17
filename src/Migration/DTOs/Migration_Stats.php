<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\DTOs;

use DateTimeImmutable;

/**
 * Data transfer object for migration statistics.
 * 
 * @package AngellEYE\PayPal\Migration\DTOs
 * @since 1.0.0
 */
class Migration_Stats {
    
    /**
     * Constructor.
     *
     * @param int $total Total subscriptions.
     * @param int $completed Completed migrations.
     * @param int $failed Failed migrations.
     * @param int $skipped Skipped migrations.
     * @param int $pending Pending migrations.
     * @param int $in_progress In progress migrations.
     * @param array $failures_by_reason Counts by failure reason.
     * @param DateTimeImmutable|null $started_at Migration start time.
     * @param DateTimeImmutable|null $completed_at Migration end time.
     */
    public function __construct(
        public readonly int $total,
        public readonly int $completed,
        public readonly int $failed,
        public readonly int $skipped,
        public readonly int $pending,
        public readonly int $in_progress,
        public readonly array $failures_by_reason = [],
        public readonly ?DateTimeImmutable $started_at = null,
        public readonly ?DateTimeImmutable $completed_at = null
    ) {}
    
    /**
     * Calculate completion percentage.
     *
     * @return float
     */
    public function completion_percentage(): float {
        if ($this->total === 0) {
            return 0.0;
        }
        $processed = $this->completed + $this->failed + $this->skipped;
        return round(($processed / $this->total) * 100, 2);
    }
    
    /**
     * Calculate success rate among completed.
     *
     * @return float
     */
    public function success_rate(): float {
        $processed = $this->completed + $this->failed;
        if ($processed === 0) {
            return 0.0;
        }
        return round(($this->completed / $processed) * 100, 2);
    }
    
    /**
     * Check if migration is complete.
     *
     * @return bool
     */
    public function is_complete(): bool {
        return $this->pending === 0 && $this->in_progress === 0;
    }
    
    /**
     * Get estimated remaining time in seconds.
     *
     * @param float $processing_rate Subscriptions per second.
     * @return int|null
     */
    public function estimated_remaining_seconds(float $processing_rate = 0.5): ?int {
        if ($processing_rate <= 0) {
            return null;
        }
        $remaining = $this->pending + $this->in_progress;
        return (int) ($remaining / $processing_rate);
    }
    
    /**
     * Convert to array.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'total' => $this->total,
            'completed' => $this->completed,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'pending' => $this->pending,
            'in_progress' => $this->in_progress,
            'completion_percentage' => $this->completion_percentage(),
            'success_rate' => $this->success_rate(),
            'is_complete' => $this->is_complete(),
            'failures_by_reason' => $this->failures_by_reason,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
        ];
    }
}
