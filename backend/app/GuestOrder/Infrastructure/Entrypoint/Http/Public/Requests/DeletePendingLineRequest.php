<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\DeletePendingLine\DeletePendingLineCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeletePendingLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): DeletePendingLineCommand
    {
        return new DeletePendingLineCommand(
            token: (string) $this->route('token'),
            sessionToken: (string) $this->header('X-Guest-Session', ''),
            lineId: (string) $this->route('lineId'),
        );
    }
}
