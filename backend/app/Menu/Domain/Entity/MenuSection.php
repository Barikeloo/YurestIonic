<?php

declare(strict_types=1);

namespace App\Menu\Domain\Entity;

use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\ValueObject\MenuSectionChoiceRule;
use App\Menu\Domain\ValueObject\MenuSectionName;
use App\Shared\Domain\ValueObject\Uuid;

class MenuSection
{
    /**
     * @param  MenuItem[]  $items
     */
    private function __construct(
        private Uuid $id,
        private Uuid $menuId,
        private MenuSectionName $name,
        private int $position,
        private MenuSectionChoiceRule $choiceRule,
        private array $items,
    ) {
        if ($items === []) {
            throw MenuInvalidConfigurationException::emptySection($name->value());
        }
    }

    /**
     * @param  MenuItem[]  $items
     */
    public static function dddCreate(
        Uuid $menuId,
        MenuSectionName $name,
        int $position,
        MenuSectionChoiceRule $choiceRule,
        array $items,
    ): self {
        return new self(
            id: Uuid::generate(),
            menuId: $menuId,
            name: $name,
            position: $position,
            choiceRule: $choiceRule,
            items: $items,
        );
    }

    /**
     * @param  MenuItem[]  $items
     */
    public static function fromPersistence(
        string $id,
        string $menuId,
        string $name,
        int $position,
        int $minChoices,
        int $maxChoices,
        array $items,
    ): self {
        return new self(
            id: Uuid::create($id),
            menuId: Uuid::create($menuId),
            name: MenuSectionName::create($name),
            position: $position,
            choiceRule: MenuSectionChoiceRule::create($minChoices, $maxChoices),
            items: $items,
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function menuId(): Uuid
    {
        return $this->menuId;
    }

    public function name(): MenuSectionName
    {
        return $this->name;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function choiceRule(): MenuSectionChoiceRule
    {
        return $this->choiceRule;
    }

    /** @return MenuItem[] */
    public function items(): array
    {
        return $this->items;
    }
}
