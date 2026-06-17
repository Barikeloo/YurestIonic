<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\UploadProductPhotoDirectly\UploadProductPhotoDirectlyCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class DirectProductPhotoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
        ];
    }

    public function toCommand(): UploadProductPhotoDirectlyCommand
    {
        $restaurantId = app(TenantContext::class)->restaurantUuid();

        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        return new UploadProductPhotoDirectlyCommand(
            productId: (string) $this->route('id'),
            restaurantId: $restaurantId,
            temporaryPath: (string) $this->file('photo')->getRealPath(),
        );
    }
}
