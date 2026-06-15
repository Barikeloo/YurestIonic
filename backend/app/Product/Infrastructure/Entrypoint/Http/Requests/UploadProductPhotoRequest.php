<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\UploadProductPhoto\UploadProductPhotoCommand;
use Illuminate\Foundation\Http\FormRequest;

final class UploadProductPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:8192'],
        ];
    }

    public function toCommand(): UploadProductPhotoCommand
    {
        return new UploadProductPhotoCommand(
            token: (string) $this->route('token'),
            temporaryPath: (string) $this->file('photo')->getRealPath(),
        );
    }
}
