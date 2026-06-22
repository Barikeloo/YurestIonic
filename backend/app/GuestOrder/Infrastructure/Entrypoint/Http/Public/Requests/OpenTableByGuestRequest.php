<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\OpenTableByGuest\OpenTableByGuestCommand;
use App\GuestOrder\Domain\ValueObject\IdentityMode;
use Illuminate\Foundation\Http\FormRequest;

final class OpenTableByGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_token'  => ['required', 'string', 'size:64'],
            'diners_count'   => ['required', 'integer', 'min:1', 'max:99'],
            'identity_mode'  => ['required', 'string', 'in:' . implode(',', [
                IdentityMode::ANONYMOUS,
                IdentityMode::NAMED,
                IdentityMode::REGISTERED,
            ])],
            'guest_name'           => ['nullable', 'string', 'max:100'],
            'customer_auth_token'  => ['nullable', 'string', 'size:64'],
        ];
    }

    public function toCommand(): OpenTableByGuestCommand
    {
        return new OpenTableByGuestCommand(
            token: (string) $this->route('token'),
            sessionToken: (string) $this->input('session_token'),
            dinersCount: (int) $this->input('diners_count'),
            identityMode: (string) $this->input('identity_mode'),
            guestName: $this->input('guest_name'),
            customerAuthToken: $this->input('customer_auth_token'),
        );
    }
}
