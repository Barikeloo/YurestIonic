<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Subscriber;

use App\Family\Domain\Event\FamilyCreated;
use App\Family\Domain\Event\FamilyDeleted;
use App\Family\Domain\Event\FamilyUpdated;
use App\Menu\Domain\Event\MenuActivated;
use App\Menu\Domain\Event\MenuArchived;
use App\Menu\Domain\Event\MenuCreated;
use App\Menu\Domain\Event\MenuDeactivated;
use App\Menu\Domain\Event\MenuUpdated;
use App\Product\Domain\Event\ProductActivated;
use App\Product\Domain\Event\ProductAvailabilityChanged;
use App\Product\Domain\Event\ProductCreated;
use App\Product\Domain\Event\ProductDeactivated;
use App\Product\Domain\Event\ProductDeleted;
use App\Product\Domain\Event\ProductPhotoUpdated;
use App\Product\Domain\Event\ProductPriceChanged;
use App\Product\Domain\Event\ProductUpdated;
use App\ProductModifier\Domain\Event\ProductModifierCreated;
use App\ProductModifier\Domain\Event\ProductModifierDeleted;
use App\ProductModifier\Domain\Event\ProductModifierUpdated;
use App\ProductVariant\Domain\Event\ProductVariantCreated;
use App\ProductVariant\Domain\Event\ProductVariantDeleted;
use App\ProductVariant\Domain\Event\ProductVariantUpdated;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\AuditableEvent;
use App\Shared\Domain\Event\DomainEvent;
use Illuminate\Support\Facades\DB;

final class CatalogVersionIncrementSubscriber implements EventSubscriber
{
    public function subscribedTo(): array
    {
        return [
            ProductCreated::class,
            ProductUpdated::class,
            ProductDeleted::class,
            ProductActivated::class,
            ProductDeactivated::class,
            ProductPriceChanged::class,
            ProductPhotoUpdated::class,
            ProductAvailabilityChanged::class,
            FamilyCreated::class,
            FamilyUpdated::class,
            FamilyDeleted::class,
            MenuCreated::class,
            MenuUpdated::class,
            MenuActivated::class,
            MenuDeactivated::class,
            MenuArchived::class,
            ProductVariantCreated::class,
            ProductVariantUpdated::class,
            ProductVariantDeleted::class,
            ProductModifierCreated::class,
            ProductModifierUpdated::class,
            ProductModifierDeleted::class,
        ];
    }

    public function handle(DomainEvent $event): void
    {
        if (! $event instanceof AuditableEvent) {
            return;
        }

        $restaurantInternalId = $this->resolveRestaurantId($event);

        if ($restaurantInternalId === null) {
            return;
        }

        DB::table('table_qr_tokens')
            ->where('restaurant_id', $restaurantInternalId)
            ->update(['catalog_version' => DB::raw('catalog_version + 1'), 'updated_at' => now()]);
    }

    private function resolveRestaurantId(AuditableEvent $event): ?int
    {
        $entityId = $event->auditEntityId();

        return match ($event->auditEntityType()) {
            'product'          => $this->fromProducts($entityId),
            'family'           => $this->fromFamilies($entityId),
            'menu'             => $this->fromMenus($entityId),
            'product_variant'  => $this->fromProductVariants($entityId),
            'product_modifier' => $this->fromProductModifiers($entityId),
            default            => null,
        };
    }

    private function fromProducts(string $uuid): ?int
    {
        return DB::table('products')->where('uuid', $uuid)->value('restaurant_id');
    }

    private function fromFamilies(string $uuid): ?int
    {
        return DB::table('families')->where('uuid', $uuid)->value('restaurant_id');
    }

    private function fromMenus(string $uuid): ?int
    {
        return DB::table('menus')->where('uuid', $uuid)->value('restaurant_id');
    }

    private function fromProductVariants(string $uuid): ?int
    {
        return DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('product_variants.uuid', $uuid)
            ->value('products.restaurant_id');
    }

    private function fromProductModifiers(string $uuid): ?int
    {
        return DB::table('product_modifiers')
            ->join('products', 'products.id', '=', 'product_modifiers.product_id')
            ->where('product_modifiers.uuid', $uuid)
            ->value('products.restaurant_id');
    }
}
