<?php

namespace App\Shared\Infrastructure\Persistence\Concerns;

use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait HasTenantScope
{
    protected static function bootHasTenantScope(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            /** @var TenantContext $tenantContext */
            $tenantContext = app(TenantContext::class);
            $restaurantId = $tenantContext->restaurantId();

            if ($restaurantId === null) {
                return;
            }

            $builder->where($builder->qualifyColumn('restaurant_id'), $restaurantId);
        });
    }
}
