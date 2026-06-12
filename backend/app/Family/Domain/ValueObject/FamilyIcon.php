<?php

namespace App\Family\Domain\ValueObject;

class FamilyIcon
{
    /**
     * Allowed icon slugs, mirroring the frontend icon set (app-icon).
     *
     * @var list<string>
     */
    public const ALLOWED = [
        'utensils', 'gem', 'star', 'package', 'inbox',
        'coins', 'wallet', 'receipt', 'trophy', 'users', 'calendar',
        'map', 'bar-chart',
    ];

    private string $value;

    private function __construct(string $value)
    {
        $normalized = trim($value);

        if (! in_array($normalized, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid family icon: %s.', $value)
            );
        }

        $this->value = $normalized;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
