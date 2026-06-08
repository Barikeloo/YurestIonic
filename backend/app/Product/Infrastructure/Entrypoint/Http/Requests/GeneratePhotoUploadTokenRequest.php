<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\GenerateProductPhotoUploadToken\GenerateProductPhotoUploadTokenCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class GeneratePhotoUploadTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GenerateProductPhotoUploadTokenCommand
    {
        $tenantContext = app(TenantContext::class);

        return new GenerateProductPhotoUploadTokenCommand(
            productId: (string) $this->route('id'),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            ttlMinutes: (int) config('product_photos.token_ttl_minutes', 10),
            uploadBaseUrl: (string) config('product_photos.public_base_url'),
        );
    }
}
