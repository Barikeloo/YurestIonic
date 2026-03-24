<?php

use App\Family\Infrastructure\Entrypoint\Http\ActivateController as FamilyActivateController;
use App\Family\Infrastructure\Entrypoint\Http\DeactivateController as FamilyDeactivateController;
use App\Family\Infrastructure\Entrypoint\Http\DeleteController as FamilyDeleteController;
use App\Family\Infrastructure\Entrypoint\Http\GetCollectionController as FamilyGetCollectionController;
use App\Family\Infrastructure\Entrypoint\Http\GetController as FamilyGetController;
use App\Family\Infrastructure\Entrypoint\Http\PostController as FamilyPostController;
use App\Family\Infrastructure\Entrypoint\Http\PutController as FamilyPutController;
use App\Product\Infrastructure\Entrypoint\Http\ActivateController as ProductActivateController;
use App\Product\Infrastructure\Entrypoint\Http\DeactivateController as ProductDeactivateController;
use App\Product\Infrastructure\Entrypoint\Http\DeleteController as ProductDeleteController;
use App\Product\Infrastructure\Entrypoint\Http\GetCollectionController as ProductGetCollectionController;
use App\Product\Infrastructure\Entrypoint\Http\GetController as ProductGetController;
use App\Product\Infrastructure\Entrypoint\Http\PostController as ProductPostController;
use App\Product\Infrastructure\Entrypoint\Http\PutController as ProductPutController;
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
use App\User\Infrastructure\Entrypoint\Http\LogoutController;
use App\User\Infrastructure\Entrypoint\Http\PostController;
use App\User\Infrastructure\Entrypoint\Http\LoginController;
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
	Route::get('/auth/me', GetMeController::class);
	Route::post('/auth/logout', LogoutController::class);
});

Route::get('/families', FamilyGetCollectionController::class);
Route::get('/families/{id}', FamilyGetController::class)->whereUuid('id');
Route::post('/families', FamilyPostController::class);
Route::put('/families/{id}', FamilyPutController::class)->whereUuid('id');
Route::delete('/families/{id}', FamilyDeleteController::class)->whereUuid('id');
Route::patch('/families/{id}/activate', FamilyActivateController::class)->whereUuid('id');
Route::patch('/families/{id}/deactivate', FamilyDeactivateController::class)->whereUuid('id');

Route::get('/taxes', TaxGetCollectionController::class);
Route::get('/taxes/{id}', TaxGetController::class)->whereUuid('id');
Route::post('/taxes', TaxPostController::class);
Route::put('/taxes/{id}', TaxPutController::class)->whereUuid('id');
Route::delete('/taxes/{id}', TaxDeleteController::class)->whereUuid('id');

Route::get('/zones', ZoneGetCollectionController::class);
Route::get('/zones/{id}', ZoneGetController::class)->whereUuid('id');
Route::post('/zones', ZonePostController::class);
Route::put('/zones/{id}', ZonePutController::class)->whereUuid('id');
Route::delete('/zones/{id}', ZoneDeleteController::class)->whereUuid('id');

Route::get('/tables', TableGetCollectionController::class);
Route::get('/tables/{id}', TableGetController::class)->whereUuid('id');
Route::post('/tables', TablePostController::class);
Route::put('/tables/{id}', TablePutController::class)->whereUuid('id');
Route::delete('/tables/{id}', TableDeleteController::class)->whereUuid('id');

Route::get('/products', ProductGetCollectionController::class);
Route::get('/products/{id}', ProductGetController::class)->whereUuid('id');
Route::post('/products', ProductPostController::class);
Route::put('/products/{id}', ProductPutController::class)->whereUuid('id');
Route::delete('/products/{id}', ProductDeleteController::class)->whereUuid('id');
Route::patch('/products/{id}/activate', ProductActivateController::class)->whereUuid('id');
Route::patch('/products/{id}/deactivate', ProductDeactivateController::class)->whereUuid('id');
