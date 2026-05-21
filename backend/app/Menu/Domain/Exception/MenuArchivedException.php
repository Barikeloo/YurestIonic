<?php

declare(strict_types=1);

namespace App\Menu\Domain\Exception;

use DomainException;

class MenuArchivedException extends DomainException
{
    public static function cannotModify(string $id): self
    {
        return new self("Cannot modify archived menu {$id}.");
    }
}
