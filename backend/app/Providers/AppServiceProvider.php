<?php

namespace App\Providers;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Repositories\EloquentFamilyRepository;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderLineRepository;
use App\Order\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use App\Restaurant\Domain\Interfaces\RestaurantCascadeDeleteInterface;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Repositories\EloquentRestaurantRepository;
use App\Restaurant\Infrastructure\Services\EloquentRestaurantCascadeDeleteService;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Repositories\EloquentSaleLineRepository;
use App\Sale\Infrastructure\Persistence\Repositories\EloquentSaleRepository;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Infrastructure\Persistence\LaravelTransactionManager;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use App\SuperAdmin\Infrastructure\Persistence\Repositories\EloquentSuperAdminRepository;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Infrastructure\Persistence\Repositories\EloquentTableRepository;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Repositories\EloquentTaxRepository;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\User\Infrastructure\Services\LaravelPasswordHasher;
use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Infrastructure\Persistence\Repositories\EloquentZoneRepository;
use \App\User\Domain\Interfaces\UserQuickAccessRepositoryInterface;
use \App\User\Infrastructure\Persistence\Repositories\EloquentUserQuickAccessRepository;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\Interfaces\TipRepositoryInterface;
use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Cash\Infrastructure\Persistence\Repositories\EloquentCashSessionRepository;
use App\Cash\Infrastructure\Persistence\Repositories\EloquentCashMovementRepository;
use App\Cash\Infrastructure\Persistence\Repositories\EloquentSalePaymentRepository;
use App\Cash\Infrastructure\Persistence\Repositories\EloquentTipRepository;
use App\Cash\Infrastructure\Persistence\Repositories\EloquentZReportRepository;
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
        $this->app->bind(RestaurantRepositoryInterface::class, EloquentRestaurantRepository::class);
        $this->app->bind(RestaurantCascadeDeleteInterface::class, EloquentRestaurantCascadeDeleteService::class);
        $this->app->bind(SuperAdminRepositoryInterface::class, EloquentSuperAdminRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(OrderLineRepositoryInterface::class, EloquentOrderLineRepository::class);
        $this->app->bind(SaleRepositoryInterface::class, EloquentSaleRepository::class);
        $this->app->bind(SaleLineRepositoryInterface::class, EloquentSaleLineRepository::class);
        $this->app->bind(UserQuickAccessRepositoryInterface::class,EloquentUserQuickAccessRepository::class);
        $this->app->bind(CashSessionRepositoryInterface::class, EloquentCashSessionRepository::class);
        $this->app->bind(CashMovementRepositoryInterface::class, EloquentCashMovementRepository::class);
        $this->app->bind(SalePaymentRepositoryInterface::class, EloquentSalePaymentRepository::class);
        $this->app->bind(TipRepositoryInterface::class, EloquentTipRepository::class);
        $this->app->bind(ZReportRepositoryInterface::class, EloquentZReportRepository::class);
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->singleton(TenantContext::class, static fn (): TenantContext => new TenantContext());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
