<?php

namespace App\User\Application\GetMe;

final readonly class GetMeCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
