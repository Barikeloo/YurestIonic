<?php

namespace App\ProductModifier\Application\ListProductModifiers;

final readonly class ListProductModifiersResponse
{
    /**
     * @param array<int, array<string, mixed>> $modifiers
     */
    private function __construct(
        public array $modifiers,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $modifiers
     */
    public static function create(array $modifiers): self
    {
        return new self(modifiers: $modifiers);
    }

    public function toArray(): array
    {
        return [
            'modifiers' => $this->modifiers,
        ];
    }
}
