<?php

declare(strict_types=1);

namespace App\Menu\Domain\Exception;

use DomainException;

class MenuNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self("Menu with id {$id} not found.");
    }
}
