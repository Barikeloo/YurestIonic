<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Repositories;

use App\GuestOrder\Domain\Entity\GuestSession;
use App\GuestOrder\Domain\Exception\InvalidGuestLineException;
use App\GuestOrder\Domain\Interfaces\GuestOrderLineRepositoryInterface;
use App\GuestOrder\Domain\ReadModel\CartLineData;
use App\GuestOrder\Domain\ValueObject\GuestLineInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentGuestOrderLineRepository implements GuestOrderLineRepositoryInterface
{
    public function savePendingLines(GuestSession $session, array $lines): array
    {
        $orderInternalId    = DB::table('orders')->where('uuid', $session->orderId()->value())->value('id');
        $restaurantInternalId = DB::table('restaurants')->where('uuid', $session->restaurantId()->value())->value('id');

        $createdUuids = [];
        $now          = now();

        foreach ($lines as $line) {
            $uuid = Str::uuid()->toString();

            if ($line->isProductLine()) {
                $row = $this->resolveProductLine($line, (int) $orderInternalId, (int) $restaurantInternalId, $session, $uuid, $now);
            } else {
                $row = $this->resolveMenuLine($line, (int) $orderInternalId, (int) $restaurantInternalId, $session, $uuid, $now);
            }

            DB::table('order_lines')->insert($row);
            $createdUuids[] = $uuid;
        }

        return $createdUuids;
    }

    public function getPendingLines(string $sessionUuid): array
    {
        return DB::table('order_lines')
            ->where('guest_session_id', $sessionUuid)
            ->where('send_status', 'pending')
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r): CartLineData => $this->hydrateCartLine($r))
            ->values()
            ->all();
    }

    public function getLinesByIds(array $lineUuids, string $sessionUuid): array
    {
        return DB::table('order_lines')
            ->whereIn('uuid', $lineUuids)
            ->where('guest_session_id', $sessionUuid)
            ->whereNull('deleted_at')
            ->get()
            ->map(fn ($r): CartLineData => $this->hydrateCartLine($r))
            ->values()
            ->all();
    }

    public function getAllLinesBySession(string $sessionUuid): array
    {
        return DB::table('order_lines')
            ->where('guest_session_id', $sessionUuid)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r): CartLineData => $this->hydrateCartLine($r))
            ->values()
            ->all();
    }

    public function markLinesAsSent(array $lineUuids, string $roundUuid): void
    {
        DB::table('order_lines')
            ->whereIn('uuid', $lineUuids)
            ->update([
                'send_status'    => 'sent',
                'guest_round_id' => $roundUuid,
                'updated_at'     => now(),
            ]);
    }

    private function resolveProductLine(
        GuestLineInput $line,
        int $orderId,
        int $restaurantId,
        GuestSession $session,
        string $uuid,
        \Illuminate\Support\Carbon $now,
    ): array {
        $product = DB::table('products')
            ->where('uuid', $line->productId)
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->first();

        if ($product === null) {
            throw InvalidGuestLineException::productNotFound((string) $line->productId);
        }

        if (isset($product->available) && ! $product->available) {
            throw InvalidGuestLineException::productNotAvailable((string) $line->productId);
        }

        $price = $product->price;
        $variantId   = null;
        $variantName = null;

        if ($line->variantId !== null) {
            $variant = DB::table('product_variants')
                ->where('uuid', $line->variantId)
                ->where('product_id', $product->id)
                ->where('active', 1)
                ->whereNull('deleted_at')
                ->first();

            if ($variant !== null) {
                $price       = $variant->price;
                $variantId   = $variant->uuid;
                $variantName = $variant->name;
            }
        }

        $modifiers = [];
        if (! empty($line->modifierIds)) {
            $mods = DB::table('product_modifiers')
                ->whereIn('uuid', $line->modifierIds)
                ->where('product_id', $product->id)
                ->where('active', 1)
                ->whereNull('deleted_at')
                ->get();

            foreach ($mods as $mod) {
                $price      += $mod->price;
                $modifiers[] = ['id' => $mod->uuid, 'name' => $mod->name, 'price' => $mod->price];
            }
        }

        $taxPercentage = (int) DB::table('taxes')->where('id', $product->tax_id)->value('percentage');
        $productId     = $product->id;
        $productName   = $product->name;

        return $this->buildRow(
            uuid: $uuid,
            orderId: $orderId,
            restaurantId: $restaurantId,
            session: $session,
            productId: $productId,
            productName: $productName,
            menuId: null,
            menuName: null,
            variantId: $variantId,
            variantName: $variantName,
            modifiers: $modifiers,
            menuSelections: null,
            quantity: $line->quantity,
            price: $price,
            taxPercentage: $taxPercentage,
            notes: $line->notes,
            now: $now,
        );
    }

    private function resolveMenuLine(
        GuestLineInput $line,
        int $orderId,
        int $restaurantId,
        GuestSession $session,
        string $uuid,
        \Illuminate\Support\Carbon $now,
    ): array {
        $menu = DB::table('menus')
            ->where('uuid', $line->menuId)
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->first();

        if ($menu === null) {
            throw InvalidGuestLineException::menuNotFound((string) $line->menuId);
        }

        $price         = $menu->price;
        $menuSelections = [];

        foreach ($line->menuSelections as $sel) {
            $product = DB::table('products')->where('uuid', $sel['product_id'] ?? '')->first();
            $variant = isset($sel['variant_id']) && $sel['variant_id']
                ? DB::table('product_variants')->where('uuid', $sel['variant_id'])->first()
                : null;

            $extraPrice = 0;
            if ($product) {
                $menuItem = DB::table('menu_items')
                    ->join('menu_sections', 'menu_sections.id', '=', 'menu_items.section_id')
                    ->where('menu_sections.menu_id', $menu->id)
                    ->where('menu_items.product_id', $product->id)
                    ->first();

                $extraPrice = $menuItem ? (int) $menuItem->extra_price : 0;
                $price     += $extraPrice;
            }

            $menuSelections[] = [
                'section_id'   => $sel['section_id'] ?? null,
                'product_id'   => $product?->uuid ?? null,
                'product_name' => $product?->name ?? null,
                'variant_id'   => $variant?->uuid ?? null,
                'variant_name' => $variant?->name ?? null,
                'extra_price'  => $extraPrice,
            ];
        }

        $taxPercentage = (int) DB::table('taxes')->where('id', $menu->tax_id)->value('percentage');

        return $this->buildRow(
            uuid: $uuid,
            orderId: $orderId,
            restaurantId: $restaurantId,
            session: $session,
            productId: null,
            productName: null,
            menuId: $menu->uuid,
            menuName: $menu->name,
            variantId: null,
            variantName: null,
            modifiers: [],
            menuSelections: $menuSelections,
            quantity: $line->quantity,
            price: $price,
            taxPercentage: $taxPercentage,
            notes: $line->notes,
            now: $now,
        );
    }

    private function buildRow(
        string $uuid,
        int $orderId,
        int $restaurantId,
        GuestSession $session,
        ?int $productId,
        ?string $productName,
        ?string $menuId,
        ?string $menuName,
        ?string $variantId,
        ?string $variantName,
        array $modifiers,
        ?array $menuSelections,
        int $quantity,
        int $price,
        int $taxPercentage,
        ?string $notes,
        \Illuminate\Support\Carbon $now,
    ): array {
        return [
            'uuid'             => $uuid,
            'order_id'         => $orderId,
            'restaurant_id'    => $restaurantId,
            'product_id'       => $productId,
            'menu_id'          => $menuId,
            'menu_name'        => $menuName,
            'variant_id'       => $variantId,
            'variant_name'     => $variantName,
            'modifiers'        => $modifiers ? json_encode($modifiers) : null,
            'menu_selections'  => $menuSelections ? json_encode($menuSelections) : null,
            'user_id'          => null,
            'quantity'         => $quantity,
            'price'            => $price,
            'tax_percentage'   => $taxPercentage,
            'origin'           => 'guest',
            'send_status'      => 'pending',
            'guest_session_id' => $session->id()->value(),
            'guest_name'       => $session->guestName(),
            'guest_round_id'   => null,
            'is_invitation'    => 0,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];
    }

    private function hydrateCartLine(\stdClass $row): CartLineData
    {
        $productName = null;
        if ($row->product_id !== null) {
            $productName = DB::table('products')->where('id', $row->product_id)->value('name');
        }

        $modifiers = $row->modifiers ? json_decode($row->modifiers, true) : [];

        return new CartLineData(
            id: $row->uuid,
            productId: $row->product_id !== null
                ? DB::table('products')->where('id', $row->product_id)->value('uuid')
                : null,
            productName: $productName,
            menuId: $row->menu_id,
            menuName: $row->menu_name,
            variantId: $row->variant_id,
            variantName: $row->variant_name,
            modifiers: $modifiers,
            quantity: (int) $row->quantity,
            unitPrice: (int) $row->price,
            notes: $row->notes,
            sendStatus: $row->send_status,
            roundId: $row->guest_round_id,
        );
    }
}
