<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

use InvalidArgumentException;

final class ProductAllergens
{
    /**
     * 14 alérgenos oficiales del Reglamento UE 1169/2011.
     */
    public const ALLERGENS = [
        'gluten',
        'crustaceans',
        'eggs',
        'fish',
        'peanuts',
        'soy',
        'dairy',
        'nuts',
        'celery',
        'mustard',
        'sesame',
        'sulphites',
        'lupin',
        'molluscs',
    ];

    /**
     * @var string[]
     */
    private array $codes;

    private function __construct(array $codes)
    {
        $unique = array_values(array_unique($codes));
        sort($unique);

        foreach ($unique as $code) {
            if (! in_array($code, self::ALLERGENS, true)) {
                throw new InvalidArgumentException("Alérgeno inválido: {$code}");
            }
        }

        $this->codes = $unique;
    }

    /**
     * @param  string[]  $codes
     */
    public static function create(array $codes): self
    {
        return new self($codes);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return string[]
     */
    public function values(): array
    {
        return $this->codes;
    }

    public function isEmpty(): bool
    {
        return $this->codes === [];
    }
}
