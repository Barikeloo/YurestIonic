<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetCatalogForGuest;

use App\GuestOrder\Domain\ReadModel\CatalogReadModel;
use App\GuestOrder\Domain\ReadModel\FamilyCatalogItem;
use App\GuestOrder\Domain\ReadModel\MenuCatalogItem;
use App\GuestOrder\Domain\ReadModel\MenuSectionCatalogItem;

final readonly class GetCatalogForGuestResponse
{
    private function __construct(
        public int $version,
        public array $families,
        public array $menus,
    ) {}

    public static function fromReadModel(CatalogReadModel $catalog): self
    {
        return new self(
            version: $catalog->version,
            families: $catalog->families,
            menus: $catalog->menus,
        );
    }

    public function toArray(): array
    {
        return [
            'version'  => $this->version,
            'families' => array_map(fn (FamilyCatalogItem $f): array => [
                'id'       => $f->id,
                'name'     => $f->name,
                'icon'     => $f->icon,
                'color'    => $f->color,
                'products' => array_map(fn ($p): array => [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'price_cents' => $p->price_cents,
                    'photo_url'   => $p->photo_url,
                    'allergens'   => $p->allergens,
                    'available'   => $p->available,
                    'variants'    => array_map(fn ($v): array => [
                        'id'          => $v->id,
                        'name'        => $v->name,
                        'price_cents' => $v->price_cents,
                    ], $p->variants),
                    'modifiers'   => array_map(fn ($m): array => [
                        'id'             => $m->id,
                        'name'           => $m->name,
                        'price_cents'    => $m->price_cents,
                        'is_required'    => $m->is_required,
                        'selection_type' => $m->selection_type,
                    ], $p->modifiers),
                ], $f->products),
            ], $this->families),
            'menus' => array_map(fn (MenuCatalogItem $m): array => [
                'id'          => $m->id,
                'name'        => $m->name,
                'description' => $m->description,
                'price_cents' => $m->price_cents,
                'sections'    => array_map(fn (MenuSectionCatalogItem $s): array => [
                    'id'          => $s->id,
                    'name'        => $s->name,
                    'min_choices' => $s->min_choices,
                    'max_choices' => $s->max_choices,
                    'position'    => $s->position,
                    'items'       => array_map(fn ($i): array => [
                        'id'               => $i->id,
                        'product_id'       => $i->product_id,
                        'product_name'     => $i->product_name,
                        'variant_id'       => $i->variant_id,
                        'variant_name'     => $i->variant_name,
                        'extra_price_cents' => $i->extra_price_cents,
                        'position'         => $i->position,
                    ], $s->items),
                ], $m->sections),
            ], $this->menus),
        ];
    }
}
