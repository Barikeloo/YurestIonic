<?php

declare(strict_types=1);

namespace App\Menu\Domain\ValueObject;

use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use DateTimeImmutable;

/**
 * Rango de validez de un menú. Ambos campos opcionales:
 *   - from == null && to == null → siempre vigente
 *   - solo from → vigente desde esa fecha en adelante
 *   - solo to   → vigente hasta esa fecha
 *   - ambos     → rango cerrado [from, to]
 */
final class MenuValidity
{
    private function __construct(
        private ?DateTimeImmutable $from,
        private ?DateTimeImmutable $to,
    ) {
        if ($from !== null && $to !== null && $from > $to) {
            throw MenuInvalidConfigurationException::invalidValidityRange();
        }
    }

    public static function create(?DateTimeImmutable $from, ?DateTimeImmutable $to): self
    {
        return new self(
            $from !== null ? new DateTimeImmutable($from->format('Y-m-d')) : null,
            $to !== null ? new DateTimeImmutable($to->format('Y-m-d')) : null,
        );
    }

    public static function always(): self
    {
        return new self(null, null);
    }

    public function from(): ?DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): ?DateTimeImmutable
    {
        return $this->to;
    }

    public function isValidOnDate(DateTimeImmutable $date): bool
    {
        $day = new DateTimeImmutable($date->format('Y-m-d'));

        if ($this->from !== null && $day < $this->from) {
            return false;
        }
        if ($this->to !== null && $day > $this->to) {
            return false;
        }

        return true;
    }
}
