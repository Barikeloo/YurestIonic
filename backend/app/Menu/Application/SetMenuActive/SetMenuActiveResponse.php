<?php

declare(strict_types=1);

namespace App\Menu\Application\SetMenuActive;

use App\Menu\Application\Shared\MenuView;
use App\Menu\Domain\Entity\Menu;

final readonly class SetMenuActiveResponse
{

    private function __construct(public array $data) {}

    public static function fromEntity(Menu $menu): self
    {
        return new self(MenuView::toArray($menu));
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
