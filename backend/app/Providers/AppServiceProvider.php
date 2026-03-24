<?php

namespace App\Providers;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Repositories\EloquentFamilyRepository;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Infrastructure\Persistence\Repositories\EloquentTableRepository;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Repositories\EloquentTaxRepository;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\User\Infrastructure\Services\LaravelPasswordHasher;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Infrastructure\Persistence\Repositories\EloquentZoneRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FamilyRepositoryInterface::class, EloquentFamilyRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(TableRepositoryInterface::class, EloquentTableRepository::class);
        $this->app->bind(TaxRepositoryInterface::class, EloquentTaxRepository::class);
        $this->app->bind(ZoneRepositoryInterface::class, EloquentZoneRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(PasswordHasherInterface::class, LaravelPasswordHasher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
