<?php

declare(strict_types=1);

namespace App\Shared\Application\Context;

/**
 * Request-scoped "who/where" context, decoupled from domain events so events
 * carry only domain data. Returns null outside an HTTP request (e.g. console).
 */
interface RequestContextInterface
{
    public function restaurantId(): ?string;

    public function userId(): ?string;

    public function ipAddress(): ?string;

    public function deviceId(): ?string;

    public function sessionId(): ?string;
}
