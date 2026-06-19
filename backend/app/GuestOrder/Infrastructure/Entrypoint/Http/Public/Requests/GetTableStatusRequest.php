<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\GetTableStatus\GetTableStatusCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetTableStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetTableStatusCommand
    {
        return new GetTableStatusCommand(
            token: (string) $this->route('token'),
        );
    }
}
