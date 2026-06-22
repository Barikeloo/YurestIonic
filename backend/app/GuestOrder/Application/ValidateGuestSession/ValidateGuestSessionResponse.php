<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\ValidateGuestSession;

final readonly class ValidateGuestSessionResponse
{
    private function __construct(
        public bool $valid,
        public ?string $guestName,
        public ?string $identityMode,
        public ?string $orderStatus,
        public ?string $expiresAt,
        public ?string $orderId = null,
    ) {}

    public static function valid(
        ?string $guestName,
        string $identityMode,
        string $orderStatus,
        string $expiresAt,
        ?string $orderId = null,
    ): self {
        return new self(
            valid: true,
            guestName: $guestName,
            identityMode: $identityMode,
            orderStatus: $orderStatus,
            expiresAt: $expiresAt,
            orderId: $orderId,
        );
    }

    public static function invalid(): self
    {
        return new self(
            valid: false,
            guestName: null,
            identityMode: null,
            orderStatus: null,
            expiresAt: null,
        );
    }

    public function toArray(): array
    {
        return [
            'valid'          => $this->valid,
            'guest_name'     => $this->guestName,
            'identity_mode'  => $this->identityMode,
            'order_status'   => $this->orderStatus,
            'expires_at'     => $this->expiresAt,
            'order_id'       => $this->orderId,
        ];
    }
}
