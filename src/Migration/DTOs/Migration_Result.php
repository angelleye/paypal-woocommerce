<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\DTOs;

use AngellEYE\PayPal\Migration\Enums\Migration_Status;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Data transfer object for migration results.
 * 
 * @package AngellEYE\PayPal\Migration\DTOs
 * @since 1.0.0
 */
class Migration_Result {
    
    /**
     * Constructor.
     *
     * @param Migration_Status $status Migration status.
     * @param int $subscription_id Subscription ID.
     * @param string|null $error_code Error code if failed.
     * @param string|null $error_message Error message if failed.
     * @param array $context Additional context data.
     * @param DateTimeImmutable|null $processed_at Processing timestamp.
     */
    public readonly DateTimeImmutable $processed_at;

    public function __construct(
        public readonly Migration_Status $status,
        public readonly int $subscription_id,
        public readonly ?string $error_code = null,
        public readonly ?string $error_message = null,
        public readonly array $context = [],
        ?DateTimeImmutable $processed_at = null
    ) {
        $this->processed_at = $processed_at ?? new DateTimeImmutable();
    }
    
    /**
     * Create a success result.
     *
     * @param int $subscription_id Subscription ID.
     * @param array $context Additional context.
     * @return self
     */
    public static function success(int $subscription_id, array $context = []): self {
        return new self(
            Migration_Status::COMPLETED,
            $subscription_id,
            null,
            null,
            $context
        );
    }
    
    /**
     * Create a failure result.
     *
     * @param int $subscription_id Subscription ID.
     * @param Migration_Status $status Failure status.
     * @param string $error_code Error code.
     * @param string $error_message Error message.
     * @param array $context Additional context.
     * @return self
     * @throws InvalidArgumentException If status is not a failure status.
     */
    public static function failed(
        int $subscription_id,
        Migration_Status $status,
        string $error_code,
        string $error_message,
        array $context = []
    ): self {
        if (!$status->is_failure()) {
            throw new InvalidArgumentException('Status must be a failure status');
        }
        
        return new self(
            $status,
            $subscription_id,
            $error_code,
            $error_message,
            $context
        );
    }
    
    /**
     * Create a skipped result.
     *
     * @param int $subscription_id Subscription ID.
     * @param Migration_Status $status Skip status.
     * @param string $reason Skip reason.
     * @param array $context Additional context.
     * @return self
     */
    public static function skipped(
        int $subscription_id,
        Migration_Status $status,
        string $reason,
        array $context = []
    ): self {
        return new self(
            $status,
            $subscription_id,
            null,
            $reason,
            $context
        );
    }
    
    /**
     * Check if result is successful.
     *
     * @return bool
     */
    public function is_success(): bool {
        return $this->status === Migration_Status::COMPLETED;
    }
    
    /**
     * Check if result is failure.
     *
     * @return bool
     */
    public function is_failure(): bool {
        return $this->status->is_failure();
    }
    
    /**
     * Check if result is skipped.
     *
     * @return bool
     */
    public function is_skipped(): bool {
        return in_array($this->status, [
            Migration_Status::SKIPPED_EXCLUDED,
            Migration_Status::SKIPPED_MANUAL,
        ], true);
    }
    
    /**
     * Convert to array.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'subscription_id' => $this->subscription_id,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'context' => $this->context,
            'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
        ];
    }
}
