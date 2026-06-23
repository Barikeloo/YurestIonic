<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Repositories;

use App\GuestOrder\Domain\Interfaces\CustomerOfferRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentCustomerOfferRepository implements CustomerOfferRepositoryInterface
{
    public function list(string $restaurantId): array
    {
        $rid = $this->restaurantId($restaurantId);

        return DB::table('customer_offers')
            ->where('restaurant_id', $rid)
            ->orderByDesc('active')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => $this->map($r))
            ->all();
    }

    public function findByUuid(string $uuid, string $restaurantId): ?array
    {
        $rid = $this->restaurantId($restaurantId);
        $row = DB::table('customer_offers')->where('uuid', $uuid)->where('restaurant_id', $rid)->first();
        return $row ? $this->map($row) : null;
    }

    public function create(string $restaurantId, array $data): array
    {
        $rid  = $this->restaurantId($restaurantId);
        $uuid = (string) Str::uuid();

        DB::table('customer_offers')->insert([
            'uuid'           => $uuid,
            'restaurant_id'  => $rid,
            'title'          => $data['title'],
            'description'    => $data['description'] ?? null,
            'discount_type'  => $data['discount_type'],
            'discount_value' => (int) $data['discount_value'],
            'min_points'     => (int) ($data['min_points'] ?? 0),
            'valid_from'     => $data['valid_from'] ?? null,
            'valid_until'    => $data['valid_until'] ?? null,
            'active'         => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $this->findByUuid($uuid, $restaurantId);
    }

    public function update(string $uuid, string $restaurantId, array $data): ?array
    {
        $rid = $this->restaurantId($restaurantId);

        $fields = array_filter([
            'title'          => $data['title'] ?? null,
            'description'    => $data['description'] ?? null,
            'discount_type'  => $data['discount_type'] ?? null,
            'discount_value' => isset($data['discount_value']) ? (int) $data['discount_value'] : null,
            'min_points'     => isset($data['min_points']) ? (int) $data['min_points'] : null,
            'valid_from'     => array_key_exists('valid_from', $data) ? $data['valid_from'] : '__skip__',
            'valid_until'    => array_key_exists('valid_until', $data) ? $data['valid_until'] : '__skip__',
            'active'         => $data['active'] ?? null,
        ], fn ($v) => $v !== null && $v !== '__skip__');

        if (array_key_exists('valid_from', $data)) {
            $fields['valid_from'] = $data['valid_from'];
        }
        if (array_key_exists('valid_until', $data)) {
            $fields['valid_until'] = $data['valid_until'];
        }

        $fields['updated_at'] = now();

        DB::table('customer_offers')->where('uuid', $uuid)->where('restaurant_id', $rid)->update($fields);

        return $this->findByUuid($uuid, $restaurantId);
    }

    public function delete(string $uuid, string $restaurantId): bool
    {
        $rid = $this->restaurantId($restaurantId);
        return (bool) DB::table('customer_offers')->where('uuid', $uuid)->where('restaurant_id', $rid)->delete();
    }

    private function restaurantId(string $uuid): ?int
    {
        return DB::table('restaurants')->where('uuid', $uuid)->value('id');
    }

    private function map(\stdClass $r): array
    {
        return [
            'id'             => $r->uuid,
            'title'          => $r->title,
            'description'    => $r->description,
            'discount_type'  => $r->discount_type,
            'discount_value' => (int) $r->discount_value,
            'min_points'     => (int) $r->min_points,
            'valid_from'     => $r->valid_from,
            'valid_until'    => $r->valid_until,
            'active'         => (bool) $r->active,
            'created_at'     => $r->created_at,
        ];
    }
}
