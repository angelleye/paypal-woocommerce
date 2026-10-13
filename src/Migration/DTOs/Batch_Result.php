<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\DTOs;

/**
 * Data transfer object for batch processing results.
 * 
 * @package AngellEYE\PayPal\Migration\DTOs
 * @since 1.0.0
 */
class Batch_Result {
    
    /** @var Migration_Result[] */
    public readonly array $results;
    public readonly int $total;
    public readonly int $successful;
    public readonly int $failed;
    public readonly int $skipped;
    public readonly bool $has_more;
    public readonly ?string $next_batch_token;
    
    /**
     * Constructor.
     *
     * @param array $results Array of Migration_Result objects.
     * @param bool $has_more Whether more batches exist.
     * @param string|null $next_batch_token Token for next batch.
     */
    public function __construct(
        array $results,
        bool $has_more = false,
        ?string $next_batch_token = null
    ) {
        $this->results = $results;
        $this->total = count($results);
        $this->successful = count(array_filter($results, fn($r) => $r->is_success()));
        $this->failed = count(array_filter($results, fn($r) => $r->is_failure()));
        $this->skipped = count(array_filter($results, fn($r) => $r->is_skipped()));
        $this->has_more = $has_more;
        $this->next_batch_token = $next_batch_token;
    }
    
    /**
     * Get failed results.
     *
     * @return Migration_Result[]
     */
    public function get_failures(): array {
        return array_filter($this->results, fn($r) => $r->is_failure());
    }
    
    /**
     * Get successful results.
     *
     * @return Migration_Result[]
     */
    public function get_successes(): array {
        return array_filter($this->results, fn($r) => $r->is_success());
    }
    
    /**
     * Get skipped results.
     *
     * @return Migration_Result[]
     */
    public function get_skipped(): array {
        return array_filter($this->results, fn($r) => $r->is_skipped());
    }
    
    /**
     * Check if batch had any failures.
     *
     * @return bool
     */
    public function has_failures(): bool {
        return $this->failed > 0;
    }
    
    /**
     * Get success rate as percentage.
     *
     * @return float
     */
    public function success_rate(): float {
        if ($this->total === 0) {
            return 0.0;
        }
        return round(($this->successful / $this->total) * 100, 2);
    }
    
    /**
     * Convert to array.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'total' => $this->total,
            'successful' => $this->successful,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'success_rate' => $this->success_rate(),
            'has_more' => $this->has_more,
            'next_batch_token' => $this->next_batch_token,
            'results' => array_map(fn($r) => $r->to_array(), $this->results),
        ];
    }
}
