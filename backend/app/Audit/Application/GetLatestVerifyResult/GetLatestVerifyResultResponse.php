<?php

declare(strict_types=1);

namespace App\Audit\Application\GetLatestVerifyResult;

use App\Audit\Domain\ValueObject\VerifyChainResult;

final readonly class GetLatestVerifyResultResponse
{
    private function __construct(
        public ?VerifyChainResult $result,
    ) {}

    public static function create(?VerifyChainResult $result): self
    {
        return new self(result: $result);
    }

    public function toArray(): array
    {
        if ($this->result === null) {
            return [
                'latest' => null,
            ];
        }

        return [
            'latest' => [
                'is_valid' => $this->result->isValid,
                'total_events' => $this->result->totalEvents,
                'verified_count' => $this->result->verifiedCount,
                'broken_events' => $this->result->brokenEvents,
                'first_broken_index' => $this->result->firstBrokenIndex,
                'verified_at' => $this->result->verifiedAt->format(\DateTimeInterface::ATOM),
            ],
        ];
    }
}
