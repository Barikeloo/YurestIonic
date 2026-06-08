<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class VerifyChainResult
{

    public function __construct(
        public Uuid $restaurantId,
        public bool $isValid,
        public int $totalEvents,
        public int $verifiedCount,
        public array $brokenEvents,
        public ?int $firstBrokenIndex,
        public \DateTimeImmutable $verifiedAt,
    ) {}

    public static function create(
        Uuid $restaurantId,
        bool $isValid,
        int $totalEvents,
        int $verifiedCount,
        array $brokenEvents,
        ?int $firstBrokenIndex,
        \DateTimeImmutable $verifiedAt,
    ): self {
        return new self($restaurantId, $isValid, $totalEvents, $verifiedCount, $brokenEvents, $firstBrokenIndex, $verifiedAt);
    }
}
