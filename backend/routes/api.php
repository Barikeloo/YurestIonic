<?php

use App\Printer\Infrastructure\Entrypoint\Http\CreatePrinterConfigController;
use App\Printer\Infrastructure\Entrypoint\Http\DeletePrinterConfigController;
use App\Printer\Infrastructure\Entrypoint\Http\ListPrinterConfigsController;
use App\Printer\Infrastructure\Entrypoint\Http\PrintTicketController;
use App\Printer\Infrastructure\Entrypoint\Http\TestPrinterConfigController;
use App\Printer\Infrastructure\Entrypoint\Http\UpdatePrinterConfigController;
use App\Audit\Infrastructure\Entrypoint\Http\ExportAuditEventsController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetCashPdfController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetDailyPdfController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetFamiliesPdfController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetDashboardSummaryController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetSalesReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetSaleDetailController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetHeatmapController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetProductsPdfController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetProductsReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetEmployeesReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\ExportReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetTaxPdfController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetTipsPdfController;
use App\Reporting\Infrastructure\Entrypoint\Http\ListReportExportsController;
use App\Reporting\Infrastructure\Entrypoint\Http\DownloadReportExportController;
use App\Reporting\Infrastructure\Entrypoint\Http\GetTaxReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\SendTaxReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\ListScheduledReportsController;
use App\Reporting\Infrastructure\Entrypoint\Http\CreateScheduledReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\UpdateScheduledReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\DeleteScheduledReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\ToggleScheduledReportController;
use App\Reporting\Infrastructure\Entrypoint\Http\SendScheduledReportNowController;
use App\Audit\Infrastructure\Entrypoint\Http\GetArchivedAuditStatsController;
use App\Audit\Infrastructure\Entrypoint\Http\GetAuditEventController;
use App\Audit\Infrastructure\Entrypoint\Http\ListAuditAlertsController;
use App\Audit\Infrastructure\Entrypoint\Http\ListAuditEventsController;
use App\Audit\Infrastructure\Entrypoint\Http\MarkAlertReadController;
use App\Audit\Infrastructure\Entrypoint\Http\MarkAllAlertsReadController;
use App\Audit\Infrastructure\Entrypoint\Http\GetLatestVerifyResultController;
use App\Audit\Infrastructure\Entrypoint\Http\VerifyAuditChainController;
use App\AuditSavedView\Infrastructure\Entrypoint\Http\CreateAuditSavedViewController;
use App\AuditSavedView\Infrastructure\Entrypoint\Http\DeleteAuditSavedViewController;
use App\AuditSavedView\Infrastructure\Entrypoint\Http\ListAuditSavedViewsController;
use App\AuditSavedView\Infrastructure\Entrypoint\Http\UpdateAuditSavedViewController;
use App\Cash\Infrastructure\Entrypoint\Http\CancelClosingCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\CloseCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\ForceCloseCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\GenerateZReportController;
use App\Cash\Infrastructure\Entrypoint\Http\GetActiveCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\GetCashSessionSummaryController;
use App\Cash\Infrastructure\Entrypoint\Http\GetLastClosedCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\GetZReportController;
use App\Cash\Infrastructure\Entrypoint\Http\ListCashMovementsController;
use App\Cash\Infrastructure\Entrypoint\Http\ListCashSessionsController;
use App\Cash\Infrastructure\Entrypoint\Http\OpenCashSessionController;
use App\Cash\Infrastructure\Entrypoint\Http\RegisterCashMovementController;
use App\Cash\Infrastructure\Entrypoint\Http\StartClosingCashSessionController;
use App\Family\Infrastructure\Entrypoint\Http\ActivateController as FamilyActivateController;
use App\Family\Infrastructure\Entrypoint\Http\DeactivateController as FamilyDeactivateController;
use App\Family\Infrastructure\Entrypoint\Http\DeleteController as FamilyDeleteController;
use App\Family\Infrastructure\Entrypoint\Http\GetCollectionController as FamilyGetCollectionController;
use App\Family\Infrastructure\Entrypoint\Http\GetController as FamilyGetController;
use App\Family\Infrastructure\Entrypoint\Http\PostController as FamilyPostController;
use App\Family\Infrastructure\Entrypoint\Http\PutController as FamilyPutController;
use App\Family\Infrastructure\Entrypoint\Http\TpvGetCollectionController as FamilyTpvGetCollectionController;
use App\Menu\Infrastructure\Entrypoint\Http\ActivateController as MenuActivateController;
use App\Menu\Infrastructure\Entrypoint\Http\DeactivateController as MenuDeactivateController;
use App\Menu\Infrastructure\Entrypoint\Http\DeleteController as MenuDeleteController;
use App\Menu\Infrastructure\Entrypoint\Http\GetCollectionController as MenuGetCollectionController;
use App\Menu\Infrastructure\Entrypoint\Http\GetController as MenuGetController;
use App\Menu\Infrastructure\Entrypoint\Http\PostController as MenuPostController;
use App\Menu\Infrastructure\Entrypoint\Http\PutController as MenuPutController;
use App\Order\Infrastructure\Entrypoint\Http\AddLineController as OrderAddLineController;
use App\Order\Infrastructure\Entrypoint\Http\AddMenuLineController as OrderAddMenuLineController;
use App\Order\Infrastructure\Entrypoint\Http\BatchAddLinesController;
use App\Order\Infrastructure\Entrypoint\Http\CancelOrderController;
use App\Order\Infrastructure\Entrypoint\Http\DeleteController as OrderDeleteController;
use App\Order\Infrastructure\Entrypoint\Http\DeleteLineController as OrderDeleteLineController;
use App\Order\Infrastructure\Entrypoint\Http\GetCollectionController as OrderGetCollectionController;
use App\Order\Infrastructure\Entrypoint\Http\GetController as OrderGetController;
use App\Order\Infrastructure\Entrypoint\Http\GetLinesController as OrderGetLinesController;
use App\Order\Infrastructure\Entrypoint\Http\GetOrderPreTicketController;
use App\Order\Infrastructure\Entrypoint\Http\GetOrderTotalController;
use App\Order\Infrastructure\Entrypoint\Http\GetOrderTransfersController;
use App\Order\Infrastructure\Entrypoint\Http\MarkOrderToChargeController;
use App\Order\Infrastructure\Entrypoint\Http\PostController as OrderPostController;
use App\Order\Infrastructure\Entrypoint\Http\PutController as OrderPutController;
use App\Order\Infrastructure\Entrypoint\Http\ReopenOrderController;
use App\Order\Infrastructure\Entrypoint\Http\TransferOrderController;
use App\Product\Infrastructure\Entrypoint\Http\ActivateController as ProductActivateController;
use App\Product\Infrastructure\Entrypoint\Http\DeactivateController as ProductDeactivateController;
use App\Product\Infrastructure\Entrypoint\Http\DeleteController as ProductDeleteController;
use App\Product\Infrastructure\Entrypoint\Http\DirectProductPhotoUploadController;
use App\Product\Infrastructure\Entrypoint\Http\GeneratePhotoUploadTokenController;
use App\Product\Infrastructure\Entrypoint\Http\GetCollectionController as ProductGetCollectionController;
use App\Product\Infrastructure\Entrypoint\Http\GetController as ProductGetController;
use App\Product\Infrastructure\Entrypoint\Http\PublicPhotoUploadContextController;
use App\Product\Infrastructure\Entrypoint\Http\PublicPhotoUploadController;
use App\Product\Infrastructure\Entrypoint\Http\PostController as ProductPostController;
use App\Product\Infrastructure\Entrypoint\Http\PutController as ProductPutController;
use App\Product\Infrastructure\Entrypoint\Http\TpvGetCollectionController as ProductTpvGetCollectionController;
use App\ProductModifier\Infrastructure\Entrypoint\Http\CreateProductModifierController;
use App\ProductModifier\Infrastructure\Entrypoint\Http\DeleteProductModifierController;
use App\ProductModifier\Infrastructure\Entrypoint\Http\ListProductModifiersController;
use App\ProductModifier\Infrastructure\Entrypoint\Http\ReorderProductModifiersController;
use App\ProductModifier\Infrastructure\Entrypoint\Http\UpdateProductModifierController;
use App\ProductVariant\Infrastructure\Entrypoint\Http\CreateProductVariantController;
use App\ProductVariant\Infrastructure\Entrypoint\Http\DeleteProductVariantController;
use App\ProductVariant\Infrastructure\Entrypoint\Http\ListProductVariantsController;
use App\ProductVariant\Infrastructure\Entrypoint\Http\UpdateProductVariantController;
use App\Restaurant\Infrastructure\Entrypoint\Http\AdminGetCollectionController as RestaurantAdminGetCollectionController;
use App\Restaurant\Infrastructure\Entrypoint\Http\AdminSelectRestaurantContextController;
use App\Restaurant\Infrastructure\Entrypoint\Http\DeleteController as RestaurantDeleteController;
use App\Restaurant\Infrastructure\Entrypoint\Http\GetController as RestaurantGetController;
use App\Restaurant\Infrastructure\Entrypoint\Http\PostController as RestaurantPostController;
use App\Restaurant\Infrastructure\Entrypoint\Http\PutController as RestaurantPutController;
use App\Sale\Infrastructure\Entrypoint\Http\AddLineController as SaleAddLineController;
use App\Sale\Infrastructure\Entrypoint\Http\AssignChargeSessionLinesController;
use App\Sale\Infrastructure\Entrypoint\Http\CancelChargeSessionController;
use App\Sale\Infrastructure\Entrypoint\Http\CancelSaleController;
use App\Sale\Infrastructure\Entrypoint\Http\CreateChargeSessionController;
use App\Sale\Infrastructure\Entrypoint\Http\CreateCreditNoteController;
use App\Sale\Infrastructure\Entrypoint\Http\DeleteController as SaleDeleteController;
use App\Sale\Infrastructure\Entrypoint\Http\GetCollectionController as SaleGetCollectionController;
use App\Sale\Infrastructure\Entrypoint\Http\GetController as SaleGetController;
use App\Sale\Infrastructure\Entrypoint\Http\GetCurrentChargeSessionController;
use App\Sale\Infrastructure\Entrypoint\Http\GetFinalTicketPrintController;
use App\Sale\Infrastructure\Entrypoint\Http\GetOrderFinalTicketController;
use App\Sale\Infrastructure\Entrypoint\Http\GetOrderPaidTotalController;
use App\Sale\Infrastructure\Entrypoint\Http\GetPaymentTicketController;
use App\Sale\Infrastructure\Entrypoint\Http\PostController as SalePostController;
use App\Sale\Infrastructure\Entrypoint\Http\PutController as SalePutController;
use App\Sale\Infrastructure\Entrypoint\Http\RecordChargeSessionPaymentController;
use App\Sale\Infrastructure\Entrypoint\Http\RefundChargeSessionLineController;
use App\Sale\Infrastructure\Entrypoint\Http\UpdateChargeSessionDinersController;
use App\Shared\Infrastructure\Http\Middleware\RequireAdminSession;
use App\Shared\Infrastructure\Http\Middleware\RequireManagementSession;
use App\Shared\Infrastructure\Http\Middleware\RequireSuperAdminSession;
use App\Shared\Infrastructure\Http\Middleware\RequireSupervisorSession;
use App\Shared\Infrastructure\Http\Middleware\ResolveTenantContext;
use App\SuperAdmin\Infrastructure\Entrypoint\Http\GetMeController as SuperAdminGetMeController;
use App\SuperAdmin\Infrastructure\Entrypoint\Http\LoginController as SuperAdminLoginController;
use App\SuperAdmin\Infrastructure\Entrypoint\Http\LogoutController as SuperAdminLogoutController;
use App\Tables\Infrastructure\Entrypoint\Http\DeleteController as TableDeleteController;
use App\Tables\Infrastructure\Entrypoint\Http\GetCollectionController as TableGetCollectionController;
use App\Tables\Infrastructure\Entrypoint\Http\GetController as TableGetController;
use App\Tables\Infrastructure\Entrypoint\Http\MergeTablesController;
use App\Tables\Infrastructure\Entrypoint\Http\PostController as TablePostController;
use App\Tables\Infrastructure\Entrypoint\Http\PutController as TablePutController;
use App\Tables\Infrastructure\Entrypoint\Http\SaveZoneLayoutController;
use App\Tables\Infrastructure\Entrypoint\Http\UnmergeTablesController;
use App\Tax\Infrastructure\Entrypoint\Http\DeleteController as TaxDeleteController;
use App\Tax\Infrastructure\Entrypoint\Http\GetCollectionController as TaxGetCollectionController;
use App\Tax\Infrastructure\Entrypoint\Http\GetController as TaxGetController;
use App\Tax\Infrastructure\Entrypoint\Http\PostController as TaxPostController;
use App\Tax\Infrastructure\Entrypoint\Http\PutController as TaxPutController;
use App\User\Infrastructure\Entrypoint\Http\Admin\AdminDeleteController as UserAdminDeleteController;
use App\User\Infrastructure\Entrypoint\Http\Admin\AdminGetCollectionController as UserAdminGetCollectionController;
use App\User\Infrastructure\Entrypoint\Http\Admin\AdminPostController as UserAdminPostController;
use App\User\Infrastructure\Entrypoint\Http\Admin\AdminPutController as UserAdminPutController;
use App\User\Infrastructure\Entrypoint\Http\GetMeController;
use App\User\Infrastructure\Entrypoint\Http\GetQuickUsersController;
use App\User\Infrastructure\Entrypoint\Http\LoginByPinController;
use App\User\Infrastructure\Entrypoint\Http\LoginController;
use App\User\Infrastructure\Entrypoint\Http\LoginForDeviceLinkController;
use App\User\Infrastructure\Entrypoint\Http\LogoutController;
use App\User\Infrastructure\Entrypoint\Http\PostController;
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

Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/public/photo-upload/{token}', PublicPhotoUploadContextController::class)
        ->where('token', '[A-Fa-f0-9]{64}');
    Route::post('/public/photo-upload/{token}', PublicPhotoUploadController::class)
        ->where('token', '[A-Fa-f0-9]{64}');
});

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
    Route::get('/tpv/families', FamilyTpvGetCollectionController::class);
    Route::get('/tpv/products', ProductTpvGetCollectionController::class);
    Route::get('/tpv/zones', ZoneGetCollectionController::class);
    Route::get('/tpv/tables', TableGetCollectionController::class);
    Route::post('/tpv/tables/merge', MergeTablesController::class);
    Route::post('/tpv/tables/unmerge', UnmergeTablesController::class);
    Route::get('/tpv/taxes', TaxGetCollectionController::class);

    Route::post('/tpv/orders', OrderPostController::class);
    Route::post('/tpv/orders/lines', OrderAddLineController::class);
    Route::post('/tpv/orders/batch-lines', BatchAddLinesController::class);
    Route::post('/tpv/orders/menu-lines', OrderAddMenuLineController::class);

    Route::get('/tpv/menus', MenuGetCollectionController::class);
    Route::get('/tpv/orders', OrderGetCollectionController::class);
    Route::get('/tpv/orders/{id}', OrderGetController::class)->whereUuid('id');
    Route::get('/tpv/orders/{id}/total', GetOrderTotalController::class)->whereUuid('id');
    Route::get('/tpv/orders/{id}/pre-ticket', GetOrderPreTicketController::class)->whereUuid('id');
    Route::get('/tpv/orders/{id}/final-ticket', GetOrderFinalTicketController::class)->whereUuid('id');
    Route::get('/tpv/orders/{id}/final-ticket/print', GetFinalTicketPrintController::class)->whereUuid('id');
    Route::post('/tpv/orders/{id}/print-ticket', PrintTicketController::class)->whereUuid('id');
    Route::post('/tpv/orders/{id}/print-pre-ticket', \App\Printer\Infrastructure\Entrypoint\Http\PrintPreTicketController::class)->whereUuid('id');
    Route::get('/tpv/orders/{id}/lines', OrderGetLinesController::class)->whereUuid('id');
    Route::put('/tpv/orders/{id}', OrderPutController::class)->whereUuid('id');
    Route::post('/tpv/orders/{id}/mark-to-charge', MarkOrderToChargeController::class)->whereUuid('id');
    Route::post('/tpv/orders/{id}/transfer', TransferOrderController::class)->whereUuid('id');
    Route::get('/tpv/orders/{id}/transfers', GetOrderTransfersController::class)->whereUuid('id');

    Route::post('/tpv/sales', SalePostController::class);
    Route::post('/tpv/sales/lines', SaleAddLineController::class);
    Route::post('/tpv/sales/cancel', CancelSaleController::class);
    Route::post('/tpv/sales/credit-note', CreateCreditNoteController::class);
    Route::get('/tpv/sales', SaleGetCollectionController::class);
    Route::get('/tpv/sales/{id}', SaleGetController::class)->whereUuid('id');
    Route::get('/tpv/sales/{id}/payment-ticket', GetPaymentTicketController::class)->whereUuid('id');
    Route::put('/tpv/sales/{id}', SalePutController::class)->whereUuid('id');
    Route::delete('/tpv/sales/{id}', SaleDeleteController::class)->whereUuid('id');
    Route::get('/tpv/sales/order/{orderId}/paid-total', GetOrderPaidTotalController::class)->whereUuid('orderId');

    Route::post('/tpv/cash-sessions', OpenCashSessionController::class);
    Route::get('/tpv/cash-sessions', ListCashSessionsController::class);
    Route::get('/tpv/cash-sessions/active', GetActiveCashSessionController::class);
    Route::get('/tpv/cash-sessions/last-closed', GetLastClosedCashSessionController::class);
    Route::get('/tpv/cash-sessions/{id}/summary', GetCashSessionSummaryController::class)->whereUuid('id');
    Route::post('/tpv/cash-movements', RegisterCashMovementController::class);
    Route::get('/tpv/cash-movements', ListCashMovementsController::class);
    Route::post('/tpv/cash-sessions/start-closing', StartClosingCashSessionController::class);
    Route::post('/tpv/cash-sessions/cancel-closing', CancelClosingCashSessionController::class);
    Route::post('/tpv/cash-sessions/close', CloseCashSessionController::class);
    Route::get('/tpv/z-reports/{id}', GetZReportController::class)->whereUuid('id');

    Route::post('/tpv/charge-sessions', CreateChargeSessionController::class);
    Route::get('/tpv/charge-sessions/current', GetCurrentChargeSessionController::class);
    Route::put('/tpv/charge-sessions/{id}/diners', UpdateChargeSessionDinersController::class)->whereUuid('id');
    Route::put('/tpv/charge-sessions/{id}/assignments', AssignChargeSessionLinesController::class)->whereUuid('id');
    Route::post('/tpv/charge-sessions/{id}/payments', RecordChargeSessionPaymentController::class)->whereUuid('id');
    Route::post('/tpv/charge-sessions/{id}/refund-line', RefundChargeSessionLineController::class)->whereUuid('id');
    Route::post('/tpv/charge-sessions/{id}/cancel', CancelChargeSessionController::class)->whereUuid('id');
});

Route::middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ResolveTenantContext::class,
    RequireSupervisorSession::class,
])->group(function (): void {
    Route::post('/tpv/orders/{id}/cancel', CancelOrderController::class)->whereUuid('id');
    Route::post('/tpv/orders/{id}/reopen', ReopenOrderController::class)->whereUuid('id');
    Route::delete('/tpv/orders/{id}', OrderDeleteController::class)->whereUuid('id');
    Route::delete('/tpv/orders/lines/{lineId}', OrderDeleteLineController::class)->whereUuid('lineId');
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
    Route::put('/admin/zones/{id}/layout', SaveZoneLayoutController::class)->whereUuid('id');

    Route::get('/admin/tables', TableGetCollectionController::class);
    Route::get('/admin/tables/{id}', TableGetController::class)->whereUuid('id');
    Route::post('/admin/tables', TablePostController::class);
    Route::put('/admin/tables/{id}', TablePutController::class)->whereUuid('id');
    Route::delete('/admin/tables/{id}', TableDeleteController::class)->whereUuid('id');
    Route::post('/admin/tables/merge', MergeTablesController::class);
    Route::post('/admin/tables/unmerge', UnmergeTablesController::class);

    Route::get('/admin/printers', ListPrinterConfigsController::class);
    Route::post('/admin/printers', CreatePrinterConfigController::class);
    Route::put('/admin/printers/{id}', UpdatePrinterConfigController::class)->whereUuid('id');
    Route::delete('/admin/printers/{id}', DeletePrinterConfigController::class)->whereUuid('id');
    Route::post('/admin/printers/{id}/test', TestPrinterConfigController::class)->whereUuid('id');

    Route::get('/admin/products', ProductGetCollectionController::class);
    Route::get('/admin/products/{id}', ProductGetController::class)->whereUuid('id');
    Route::post('/admin/products', ProductPostController::class);
    Route::put('/admin/products/{id}', ProductPutController::class)->whereUuid('id');
    Route::delete('/admin/products/{id}', ProductDeleteController::class)->whereUuid('id');
    Route::patch('/admin/products/{id}/activate', ProductActivateController::class)->whereUuid('id');
    Route::patch('/admin/products/{id}/deactivate', ProductDeactivateController::class)->whereUuid('id');
    Route::post('/admin/products/{id}/photo-upload-token', GeneratePhotoUploadTokenController::class)->whereUuid('id');
    Route::post('/admin/products/{id}/photo', DirectProductPhotoUploadController::class)->whereUuid('id');
    Route::get('/admin/products/{id}/variants', ListProductVariantsController::class)->whereUuid('id');
    Route::post('/admin/products/{id}/variants', CreateProductVariantController::class)->whereUuid('id');
    Route::put('/admin/products/{productId}/variants/{variantId}', UpdateProductVariantController::class)->whereUuid('productId')->whereUuid('variantId');
    Route::delete('/admin/products/{productId}/variants/{variantId}', DeleteProductVariantController::class)->whereUuid('productId')->whereUuid('variantId');
    Route::get('/admin/products/{id}/modifiers', ListProductModifiersController::class)->whereUuid('id');
    Route::post('/admin/products/{id}/modifiers', CreateProductModifierController::class)->whereUuid('id');
    Route::put('/admin/products/{productId}/modifiers/reorder', ReorderProductModifiersController::class)->whereUuid('productId');
    Route::put('/admin/products/{productId}/modifiers/{modifierId}', UpdateProductModifierController::class)->whereUuid('productId')->whereUuid('modifierId');
    Route::delete('/admin/products/{productId}/modifiers/{modifierId}', DeleteProductModifierController::class)->whereUuid('productId')->whereUuid('modifierId');

    Route::get('/admin/menus', MenuGetCollectionController::class);
    Route::get('/admin/menus/{id}', MenuGetController::class)->whereUuid('id');
    Route::post('/admin/menus', MenuPostController::class);
    Route::put('/admin/menus/{id}', MenuPutController::class)->whereUuid('id');
    Route::delete('/admin/menus/{id}', MenuDeleteController::class)->whereUuid('id');
    Route::patch('/admin/menus/{id}/activate', MenuActivateController::class)->whereUuid('id');
    Route::patch('/admin/menus/{id}/deactivate', MenuDeactivateController::class)->whereUuid('id');

    Route::post('/tpv/cash-sessions/force-close', ForceCloseCashSessionController::class);
    Route::post('/tpv/z-reports/generate', GenerateZReportController::class);

    Route::get('/admin/audit-log', ListAuditEventsController::class);
    Route::get('/admin/audit-log/archived-stats', GetArchivedAuditStatsController::class);
    Route::get('/admin/audit-log/export', ExportAuditEventsController::class);
    Route::get('/admin/audit-log/verify', VerifyAuditChainController::class);
    Route::get('/admin/audit-log/verify/latest', GetLatestVerifyResultController::class);
    Route::get('/admin/audit-log/{uuid}', GetAuditEventController::class)->whereUuid('uuid');

    Route::get('/admin/audit-saved-views', ListAuditSavedViewsController::class);
    Route::post('/admin/audit-saved-views', CreateAuditSavedViewController::class);
    Route::patch('/admin/audit-saved-views/{uuid}', UpdateAuditSavedViewController::class)->whereUuid('uuid');
    Route::delete('/admin/audit-saved-views/{uuid}', DeleteAuditSavedViewController::class)->whereUuid('uuid');

    Route::get('/admin/audit-alerts', ListAuditAlertsController::class);
    Route::post('/admin/audit-alerts/read-all', MarkAllAlertsReadController::class);
    Route::post('/admin/audit-alerts/{uuid}/read', MarkAlertReadController::class)->whereUuid('uuid');

    Route::get('/admin/reports/summary', GetDashboardSummaryController::class);
    Route::get('/admin/reports/daily/pdf', GetDailyPdfController::class);
    Route::get('/admin/reports/sales', GetSalesReportController::class);
    Route::get('/admin/reports/sales/{uuid}', GetSaleDetailController::class)->whereUuid('uuid');
    Route::get('/admin/reports/heatmap', GetHeatmapController::class);
    Route::get('/admin/reports/products', GetProductsReportController::class);
    Route::get('/admin/reports/products/pdf', GetProductsPdfController::class);
    Route::get('/admin/reports/families/pdf', GetFamiliesPdfController::class);
    Route::get('/admin/reports/tips/pdf', GetTipsPdfController::class);
    Route::get('/admin/reports/cash/pdf', GetCashPdfController::class);
    Route::get('/admin/reports/employees', GetEmployeesReportController::class);
    Route::get('/admin/reports/taxes', GetTaxReportController::class);
    Route::get('/admin/reports/taxes/pdf', GetTaxPdfController::class);
    Route::post('/admin/reports/taxes/send', SendTaxReportController::class);
    Route::get('/admin/reports/export/{type}', ExportReportController::class);
    Route::get('/admin/reports/exports', ListReportExportsController::class);
    Route::get('/admin/reports/exports/{uuid}/download', DownloadReportExportController::class)->whereUuid('uuid');

    Route::get('/admin/reports/scheduled', ListScheduledReportsController::class);
    Route::post('/admin/reports/scheduled', CreateScheduledReportController::class);
    Route::put('/admin/reports/scheduled/{uuid}', UpdateScheduledReportController::class)->whereUuid('uuid');
    Route::delete('/admin/reports/scheduled/{uuid}', DeleteScheduledReportController::class)->whereUuid('uuid');
    Route::put('/admin/reports/scheduled/{uuid}/toggle', ToggleScheduledReportController::class)->whereUuid('uuid');
    Route::post('/admin/reports/scheduled/{uuid}/send', SendScheduledReportNowController::class)->whereUuid('uuid');
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

// ─── Guest / Autoservicio QR — rutas públicas (sin autenticación) ────────────
Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/public/table/{token}', \App\GuestOrder\Infrastructure\Entrypoint\Http\Public\GetTableStatusController::class);
});

// ─── Guest / Autoservicio QR — rutas de administración ──────────────────────
Route::middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ResolveTenantContext::class,
    RequireAdminSession::class,
])->group(function (): void {
    Route::post('/admin/tables/{tableId}/qr-token', \App\GuestOrder\Infrastructure\Entrypoint\Http\Admin\GenerateTableQrTokenController::class)->whereUuid('tableId');
});
