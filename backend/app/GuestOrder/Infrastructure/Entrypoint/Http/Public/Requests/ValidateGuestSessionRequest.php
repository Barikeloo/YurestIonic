<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\ValidateGuestSession\ValidateGuestSessionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ValidateGuestSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): ValidateGuestSessionCommand
    {
        return new ValidateGuestSessionCommand(
            token: (string) $this->route('token'),
            sessionToken: (string) $this->header('X-Guest-Session', ''),
        );
    }
}
