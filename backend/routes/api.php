<?php

use App\Family\Infrastructure\Entrypoint\Http\ActivateController as FamilyActivateController;
use App\Family\Infrastructure\Entrypoint\Http\DeactivateController as FamilyDeactivateController;
use App\Family\Infrastructure\Entrypoint\Http\DeleteController as FamilyDeleteController;
use App\Family\Infrastructure\Entrypoint\Http\GetCollectionController as FamilyGetCollectionController;
use App\Family\Infrastructure\Entrypoint\Http\GetController as FamilyGetController;
use App\Family\Infrastructure\Entrypoint\Http\PostController as FamilyPostController;
use App\Family\Infrastructure\Entrypoint\Http\PutController as FamilyPutController;
use App\Order\Infrastructure\Entrypoint\Http\AddLineController as OrderAddLineController;
use App\Order\Infrastructure\Entrypoint\Http\DeleteLineController as OrderDeleteLineController;
use App\Order\Infrastructure\Entrypoint\Http\GetCollectionController as OrderGetCollectionController;
use App\Order\Infrastructure\Entrypoint\Http\GetController as OrderGetController;
use App\Order\Infrastructure\Entrypoint\Http\GetLinesController as OrderGetLinesController;
use App\Order\Infrastructure\Entrypoint\Http\PutController as OrderPutController;
use App\Order\Infrastructure\Entrypoint\Http\DeleteController as OrderDeleteController;
use App\Order\Infrastructure\Entrypoint\Http\PostController as OrderPostController;
use App\Product\Infrastructure\Entrypoint\Http\ActivateController as ProductActivateController;
use App\Product\Infrastructure\Entrypoint\Http\DeactivateController as ProductDeactivateController;
use App\Product\Infrastructure\Entrypoint\Http\DeleteController as ProductDeleteController;
use App\Product\Infrastructure\Entrypoint\Http\GetCollectionController as ProductGetCollectionController;
use App\Product\Infrastructure\Entrypoint\Http\GetController as ProductGetController;
use App\Product\Infrastructure\Entrypoint\Http\PostController as ProductPostController;
use App\Product\Infrastructure\Entrypoint\Http\PutController as ProductPutController;
use App\Restaurant\Infrastructure\Entrypoint\Http\PostController as RestaurantPostController;
use App\Restaurant\Infrastructure\Entrypoint\Http\AdminGetCollectionController as RestaurantAdminGetCollectionController;
use App\Restaurant\Infrastructure\Entrypoint\Http\AdminSelectRestaurantContextController;
use App\Restaurant\Infrastructure\Entrypoint\Http\GetController as RestaurantGetController;
use App\Restaurant\Infrastructure\Entrypoint\Http\PutController as RestaurantPutController;
use App\Restaurant\Infrastructure\Entrypoint\Http\DeleteController as RestaurantDeleteController;
use App\Shared\Infrastructure\Http\Middleware\RequireAdminSession;
use App\Shared\Infrastructure\Http\Middleware\RequireManagementSession;
use App\Shared\Infrastructure\Http\Middleware\ResolveTenantContext;
use App\Shared\Infrastructure\Http\Middleware\RequireSuperAdminSession;
use App\SuperAdmin\Infrastructure\Entrypoint\Http\GetMeController as SuperAdminGetMeController;
use App\SuperAdmin\Infrastructure\Entrypoint\Http\LoginController as SuperAdminLoginController;
use App\SuperAdmin\Infrastructure\Entrypoint\Http\LogoutController as SuperAdminLogoutController;
use App\Sale\Infrastructure\Entrypoint\Http\AddLineController as SaleAddLineController;
use App\Sale\Infrastructure\Entrypoint\Http\GetCollectionController as SaleGetCollectionController;
use App\Sale\Infrastructure\Entrypoint\Http\GetController as SaleGetController;
use App\Sale\Infrastructure\Entrypoint\Http\PutController as SalePutController;
use App\Sale\Infrastructure\Entrypoint\Http\DeleteController as SaleDeleteController;
use App\Sale\Infrastructure\Entrypoint\Http\PostController as SalePostController;
use App\Sale\Infrastructure\Entrypoint\Http\CancelSaleController;
use App\Sale\Infrastructure\Entrypoint\Http\CreateCreditNoteController;
use App\Cash\Infrastructure\Entrypoint\Http\OpenCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\GetActiveCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\GetLastClosedCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\RegisterCashMovementController;
use App\Cash\Infrastructure\Entrypoint\Http\StartClosingCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\CancelClosingCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\CloseCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\ForceCloseCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\GenerateZReportController;
use App\Cash\Infrastructure\Entrypoint\Http\GetZReportController;
use App\Cash\Infrastructure\Entrypoint\Http\GetCashSessionSummaryController;
use App\Tax\Infrastructure\Entrypoint\Http\DeleteController as TaxDeleteController;
use App\Tax\Infrastructure\Entrypoint\Http\GetCollectionController as TaxGetCollectionController;
use App\Tax\Infrastructure\Entrypoint\Http\GetController as TaxGetController;
use App\Tax\Infrastructure\Entrypoint\Http\PostController as TaxPostController;
use App\Tax\Infrastructure\Entrypoint\Http\PutController as TaxPutController;
use App\Tables\Infrastructure\Entrypoint\Http\DeleteController as TableDeleteController;
use App\Tables\Infrastructure\Entrypoint\Http\GetCollectionController as TableGetCollectionController;
use App\Tables\Infrastructure\Entrypoint\Http\GetController as TableGetController;
use App\Tables\Infrastructure\Entrypoint\Http\PostController as TablePostController;
use App\Tables\Infrastructure\Entrypoint\Http\PutController as TablePutController;
use App\User\Infrastructure\Entrypoint\Http\GetMeController;
use App\User\Infrastructure\Entrypoint\Http\GetQuickUsersController;
use App\User\Infrastructure\Entrypoint\Http\LoginByPinController;
use App\User\Infrastructure\Entrypoint\Http\LoginForDeviceLinkController;
use App\User\Infrastructure\Entrypoint\Http\LogoutController;
use App\User\Infrastructure\Entrypoint\Http\PostController;
use App\User\Infrastructure\Entrypoint\Http\LoginController;
use App\User\Infrastructure\Entrypoint\Http\Admin\AdminGetCollectionController as UserAdminGetCollectionController;
use App\User\Infrastructure\Entrypoint\Http\Admin\AdminPostController as UserAdminPostController;
use App\User\Infrastructure\Entrypoint\Http\Admin\AdminPutController as UserAdminPutController;
use App\User\Infrastructure\Entrypoint\Http\Admin\AdminDeleteController as UserAdminDeleteController;
use App\Zone\Infrastructure\Entrypoint\Http\DeleteController as ZoneDeleteController;
use App\Zone\Infrastructure\Entrypoint\Http\GetCollectionController as ZoneGetCollectionController;
use App\Zone\Infrastructure\Entrypoint\Http\GetController as ZoneGetController;
use App\Zone\Infrastructure\Entrypoint\Http\PostController as ZonePostController;
use App\Zone\Infrastructure\Entrypoint\Http\PutController as ZonePutController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::post('/users', PostController::class);

Route::middleware([
	EncryptCookies::class,
	AddQueuedCookiesToResponse::class,
	StartSession::class,
])->group(function (): void {
	Route::post('/auth/login', LoginController::class);
	Route::post('/auth/login-pin', LoginByPinController::class);
	Route::post('/auth/login-for-device-link', LoginForDeviceLinkController::class);
	Route::get('/auth/quick-users', GetQuickUsersController::class);
	Route::get('/auth/me', GetMeController::class);
	Route::post('/auth/logout', LogoutController::class);

	Route::post('/superadmin/login', SuperAdminLoginController::class);
	Route::get('/superadmin/me', SuperAdminGetMeController::class);
	Route::post('/superadmin/logout', SuperAdminLogoutController::class);
});

Route::middleware([
	EncryptCookies::class,
	AddQueuedCookiesToResponse::class,
	StartSession::class,
	ResolveTenantContext::class,
])->group(function (): void {
	Route::get('/tpv/families', FamilyGetCollectionController::class);
	Route::get('/tpv/products', ProductGetCollectionController::class);
	Route::get('/tpv/zones', ZoneGetCollectionController::class);
	Route::get('/tpv/tables', TableGetCollectionController::class);
	Route::get('/tpv/taxes', TaxGetCollectionController::class);

	Route::post('/tpv/orders', OrderPostController::class);
	Route::post('/tpv/orders/lines', OrderAddLineController::class);
	Route::delete('/tpv/orders/lines/{lineId}', OrderDeleteLineController::class)->whereUuid('lineId');
	Route::get('/tpv/orders', OrderGetCollectionController::class);
	Route::get('/tpv/orders/{id}', OrderGetController::class)->whereUuid('id');
	Route::get('/tpv/orders/{id}/lines', OrderGetLinesController::class)->whereUuid('id');
	Route::put('/tpv/orders/{id}', OrderPutController::class)->whereUuid('id');
	Route::delete('/tpv/orders/{id}', OrderDeleteController::class)->whereUuid('id');

	Route::post('/tpv/sales', SalePostController::class);
	Route::post('/tpv/sales/lines', SaleAddLineController::class);
	Route::post('/tpv/sales/cancel', CancelSaleController::class);
	Route::post('/tpv/sales/credit-note', CreateCreditNoteController::class);
	Route::get('/tpv/sales', SaleGetCollectionController::class);
	Route::get('/tpv/sales/{id}', SaleGetController::class)->whereUuid('id');
	Route::put('/tpv/sales/{id}', SalePutController::class)->whereUuid('id');
	Route::delete('/tpv/sales/{id}', SaleDeleteController::class)->whereUuid('id');

	Route::post('/tpv/cash-sessions', OpenCashSessionController::class);
	Route::get('/tpv/cash-sessions/active', GetActiveCashSessionController::class);
	Route::get('/tpv/cash-sessions/last-closed', GetLastClosedCashSessionController::class);
	Route::get('/tpv/cash-sessions/{id}/summary', GetCashSessionSummaryController::class)->whereUuid('id');
	Route::post('/tpv/cash-movements', RegisterCashMovementController::class);
	Route::post('/tpv/cash-sessions/start-closing', StartClosingCashSessionController::class);
	Route::post('/tpv/cash-sessions/cancel-closing', CancelClosingCashSessionController::class);
	Route::post('/tpv/cash-sessions/close', CloseCashSessionController::class);
	Route::post('/tpv/z-reports/generate', GenerateZReportController::class);
	Route::get('/tpv/z-reports/{id}', GetZReportController::class)->whereUuid('id');
});

Route::middleware([
	EncryptCookies::class,
	AddQueuedCookiesToResponse::class,
	StartSession::class,
	ResolveTenantContext::class,
	RequireAdminSession::class,
])->group(function (): void {
	Route::get('/admin/families', FamilyGetCollectionController::class);
	Route::get('/admin/families/{id}', FamilyGetController::class)->whereUuid('id');
	Route::post('/admin/families', FamilyPostController::class);
	Route::put('/admin/families/{id}', FamilyPutController::class)->whereUuid('id');
	Route::delete('/admin/families/{id}', FamilyDeleteController::class)->whereUuid('id');
	Route::patch('/admin/families/{id}/activate', FamilyActivateController::class)->whereUuid('id');
	Route::patch('/admin/families/{id}/deactivate', FamilyDeactivateController::class)->whereUuid('id');


	Route::get('/admin/taxes', TaxGetCollectionController::class);
	Route::get('/admin/taxes/{id}', TaxGetController::class)->whereUuid('id');
	Route::post('/admin/taxes', TaxPostController::class);
	Route::put('/admin/taxes/{id}', TaxPutController::class)->whereUuid('id');
	Route::delete('/admin/taxes/{id}', TaxDeleteController::class)->whereUuid('id');


	Route::get('/admin/zones', ZoneGetCollectionController::class);
	Route::get('/admin/zones/{id}', ZoneGetController::class)->whereUuid('id');
	Route::post('/admin/zones', ZonePostController::class);
	Route::put('/admin/zones/{id}', ZonePutController::class)->whereUuid('id');
	Route::delete('/admin/zones/{id}', ZoneDeleteController::class)->whereUuid('id');


	Route::get('/admin/tables', TableGetCollectionController::class);
	Route::get('/admin/tables/{id}', TableGetController::class)->whereUuid('id');
	Route::post('/admin/tables', TablePostController::class);
	Route::put('/admin/tables/{id}', TablePutController::class)->whereUuid('id');
	Route::delete('/admin/tables/{id}', TableDeleteController::class)->whereUuid('id');

	Route::get('/admin/products', ProductGetCollectionController::class);
	Route::get('/admin/products/{id}', ProductGetController::class)->whereUuid('id');
	Route::post('/admin/products', ProductPostController::class);
	Route::put('/admin/products/{id}', ProductPutController::class)->whereUuid('id');
	Route::delete('/admin/products/{id}', ProductDeleteController::class)->whereUuid('id');
	Route::patch('/admin/products/{id}/activate', ProductActivateController::class)->whereUuid('id');
	Route::patch('/admin/products/{id}/deactivate', ProductDeactivateController::class)->whereUuid('id');

	Route::post('/tpv/cash-sessions/force-close', ForceCloseCashSessionController::class);
});

Route::middleware([
	EncryptCookies::class,
	AddQueuedCookiesToResponse::class,
	StartSession::class,
	RequireManagementSession::class,
])->group(function (): void {
	Route::get('/admin/restaurants', RestaurantAdminGetCollectionController::class);
	Route::post('/admin/context/restaurant', AdminSelectRestaurantContextController::class);

	Route::post('/admin/restaurants', RestaurantPostController::class);
	Route::get('/admin/restaurants/{id}', RestaurantGetController::class)->whereUuid('id');
	Route::put('/admin/restaurants/{id}', RestaurantPutController::class)->whereUuid('id');
	Route::delete('/admin/restaurants/{id}', RestaurantDeleteController::class)->whereUuid('id');

	Route::get('/admin/restaurants/{uuid}/users', UserAdminGetCollectionController::class)->whereUuid('uuid');
	Route::post('/admin/restaurants/{uuid}/users', UserAdminPostController::class)->whereUuid('uuid');
	Route::put('/admin/restaurants/{uuid}/users/{userUuid}', UserAdminPutController::class)->whereUuid('uuid')->whereUuid('userUuid');
	Route::delete('/admin/restaurants/{uuid}/users/{userUuid}', UserAdminDeleteController::class)->whereUuid('uuid')->whereUuid('userUuid');
});

Route::middleware([
	EncryptCookies::class,
	AddQueuedCookiesToResponse::class,
	StartSession::class,
	RequireSuperAdminSession::class,
])->group(function (): void {
	Route::post('/superadmin/restaurants', RestaurantPostController::class);
	Route::get('/superadmin/restaurants/{id}', RestaurantGetController::class)->whereUuid('id');
	Route::put('/superadmin/restaurants/{id}', RestaurantPutController::class)->whereUuid('id');
	Route::delete('/superadmin/restaurants/{id}', RestaurantDeleteController::class)->whereUuid('id');

	Route::get('/superadmin/restaurants/{uuid}/users', UserAdminGetCollectionController::class)->whereUuid('uuid');
	Route::post('/superadmin/restaurants/{uuid}/users', UserAdminPostController::class)->whereUuid('uuid');
	Route::put('/superadmin/restaurants/{uuid}/users/{userUuid}', UserAdminPutController::class)->whereUuid('uuid')->whereUuid('userUuid');
	Route::delete('/superadmin/restaurants/{uuid}/users/{userUuid}', UserAdminDeleteController::class)->whereUuid('uuid')->whereUuid('userUuid');
});
