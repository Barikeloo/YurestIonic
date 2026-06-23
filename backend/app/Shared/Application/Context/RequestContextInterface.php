<?php

declare(strict_types=1);

namespace App\Shared\Application\Context;

interface RequestContextInterface
{
    public function restaurantId(): ?string;

    public function userId(): ?string;

    public function ipAddress(): ?string;

    public function deviceId(): ?string;

    public function sessionId(): ?string;
}
