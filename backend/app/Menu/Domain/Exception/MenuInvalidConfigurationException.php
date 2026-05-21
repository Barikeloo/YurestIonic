<?php

declare(strict_types=1);

namespace App\Menu\Domain\Exception;

use DomainException;

class MenuInvalidConfigurationException extends DomainException
{
    public static function invalidValidityRange(): self
    {
        return new self('Menu validity "from" date cannot be later than "to" date.');
    }

    public static function invalidDaysBitmask(int $value): self
    {
        return new self("Invalid menu availability days bitmask: {$value}. Must be in 0..127.");
    }

    public static function partialTimeRange(): self
    {
        return new self('Menu availability time range must have both "from" and "to" or neither.');
    }

    public static function invalidTimeRange(): self
    {
        return new self('Menu availability "from" time must be earlier than "to" time.');
    }

    public static function invalidChoiceRule(string $reason): self
    {
        return new self("Invalid menu section choice rule: {$reason}.");
    }

    public static function emptyMenu(): self
    {
        return new self('A menu must contain at least one section.');
    }

    public static function emptySection(string $sectionName): self
    {
        return new self("Menu section '{$sectionName}' must contain at least one item.");
    }
}
