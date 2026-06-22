<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\JoinGuestSession\JoinGuestSessionCommand;
use App\GuestOrder\Domain\ValueObject\IdentityMode;
use Illuminate\Foundation\Http\FormRequest;

final class JoinGuestSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_token' => ['required', 'string', 'size:64'],
            'identity_mode' => ['required', 'string', 'in:' . implode(',', [
                IdentityMode::ANONYMOUS,
                IdentityMode::NAMED,
                IdentityMode::REGISTERED,
            ])],
            'guest_name'           => ['nullable', 'string', 'max:100'],
            'customer_auth_token'  => ['nullable', 'string', 'size:64'],
        ];
    }

    public function toCommand(): JoinGuestSessionCommand
    {
        return new JoinGuestSessionCommand(
            token: (string) $this->route('token'),
            sessionToken: (string) $this->input('session_token'),
            identityMode: (string) $this->input('identity_mode'),
            guestName: $this->input('guest_name'),
            customerAuthToken: $this->input('customer_auth_token'),
        );
    }
}
