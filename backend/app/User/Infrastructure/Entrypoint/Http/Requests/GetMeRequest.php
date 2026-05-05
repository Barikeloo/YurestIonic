<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\GetMe\GetMeCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $userId): GetMeCommand
    {
        return new GetMeCommand(userId: $userId);
    }
}
