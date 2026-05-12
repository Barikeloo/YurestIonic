<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class SaleMustHaveLinesException extends \DomainException
{
    public static function create(): self
    {
        return new self('A sale must have at least one line before closing.');
    }
}
