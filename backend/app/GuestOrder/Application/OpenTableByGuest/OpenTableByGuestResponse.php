<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\OpenTableByGuest;

final readonly class OpenTableByGuestResponse
{
    private function __construct(
        public string $sessionId,
        public string $sessionToken,
        public string $orderId,
        public string $identityMode,
        public ?string $guestName,
        public int $dinersCount,
        public string $expiresAt,
    ) {}

    public static function create(
        string $sessionId,
        string $sessionToken,
        string $orderId,
        string $identityMode,
        ?string $guestName,
        int $dinersCount,
        string $expiresAt,
    ): self {
        return new self(
            sessionId: $sessionId,
            sessionToken: $sessionToken,
            orderId: $orderId,
            identityMode: $identityMode,
            guestName: $guestName,
            dinersCount: $dinersCount,
            expiresAt: $expiresAt,
        );
    }

    public function toArray(): array
    {
        return [
            'session_id'    => $this->sessionId,
            'session_token' => $this->sessionToken,
            'order_id'      => $this->orderId,
            'identity_mode' => $this->identityMode,
            'guest_name'    => $this->guestName,
            'diners_count'  => $this->dinersCount,
            'expires_at'    => $this->expiresAt,
        ];
    }
}
