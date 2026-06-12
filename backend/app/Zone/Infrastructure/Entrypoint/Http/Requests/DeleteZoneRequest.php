<?php

namespace App\Zone\Infrastructure\Entrypoint\Http\Requests;

use App\Zone\Application\DeleteZone\DeleteZoneCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id): DeleteZoneCommand
    {
        return new DeleteZoneCommand(id: $id);
    }
}
