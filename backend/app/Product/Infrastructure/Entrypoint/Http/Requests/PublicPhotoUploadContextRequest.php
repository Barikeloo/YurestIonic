<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\GetProductPhotoUploadContext\GetProductPhotoUploadContextCommand;
use Illuminate\Foundation\Http\FormRequest;

final class PublicPhotoUploadContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetProductPhotoUploadContextCommand
    {
        return new GetProductPhotoUploadContextCommand(
            token: (string) $this->route('token'),
        );
    }
}
