<?php

declare(strict_types=1);

namespace App\Audit\Application\VerifyAuditChain;

final readonly class VerifyAuditChainResponse
{
    /**
     * @param  list<array{uuid: string, expected_hash: string, actual_hash: string}>  $brokenEvents
     */
    private function __construct(
        public int $totalEvents,
        public int $verifiedCount,
        public array $brokenEvents,
        public ?int $firstBrokenIndex,
        public bool $isValid,
    ) {}

    /**
     * @param  list<array{uuid: string, expected_hash: string, actual_hash: string}>  $brokenEvents
     */
    public static function create(
        int $totalEvents,
        int $verifiedCount,
        array $brokenEvents,
        ?int $firstBrokenIndex,
        bool $isValid,
    ): self {
        return new self($totalEvents, $verifiedCount, $brokenEvents, $firstBrokenIndex, $isValid);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_events' => $this->totalEvents,
            'verified_count' => $this->verifiedCount,
            'broken_events' => $this->brokenEvents,
            'first_broken_index' => $this->firstBrokenIndex,
            'is_valid' => $this->isValid,
        ];
    }
}
