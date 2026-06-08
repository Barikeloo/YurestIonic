<?php

declare(strict_types=1);

namespace App\Menu\Application\Shared;

final readonly class MenuSectionInput
{

    public function __construct(
        public string $name,
        public int $position,
        public int $minChoices,
        public int $maxChoices,
        public array $items,
    ) {}

    public static function fromArray(array $data, int $defaultPosition = 0): self
    {
        $rawItems = $data['items'] ?? [];

        $items = [];
        $i = 0;
        foreach ($rawItems as $rawItem) {
            $items[] = MenuItemInput::fromArray((array) $rawItem, $i);
            $i++;
        }

        return new self(
            name: (string) $data['name'],
            position: (int) ($data['position'] ?? $defaultPosition),
            minChoices: (int) ($data['min_choices'] ?? 1),
            maxChoices: (int) ($data['max_choices'] ?? 1),
            items: $items,
        );
    }
}
