<?php

namespace App\ProductModifier\Application\ListProductModifiers;

final readonly class ListProductModifiersResponse
{

    private function __construct(
        public array $modifiers,
    ) {}

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
