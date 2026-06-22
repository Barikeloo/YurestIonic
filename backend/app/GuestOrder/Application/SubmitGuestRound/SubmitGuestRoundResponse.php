<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\SubmitGuestRound;

final readonly class SubmitGuestRoundResponse
{
    private function __construct(
        public string $roundId,
        public int $roundNumber,
        public ?string $label,
        public int $lineCount,
        public string $submittedAt,
        public bool $alreadySubmitted,
    ) {}

    public static function create(
        string $roundId,
        int $roundNumber,
        ?string $label,
        int $lineCount,
        string $submittedAt,
        bool $alreadySubmitted = false,
    ): self {
        return new self(
            roundId: $roundId,
            roundNumber: $roundNumber,
            label: $label,
            lineCount: $lineCount,
            submittedAt: $submittedAt,
            alreadySubmitted: $alreadySubmitted,
        );
    }

    public function toArray(): array
    {
        return [
            'round_id'          => $this->roundId,
            'round_number'      => $this->roundNumber,
            'label'             => $this->label,
            'line_count'        => $this->lineCount,
            'submitted_at'      => $this->submittedAt,
            'already_submitted' => $this->alreadySubmitted,
        ];
    }
}
