<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\GetCashSessionSummary\GetCashSessionSummaryCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetCashSessionSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetCashSessionSummaryCommand
    {
        return new GetCashSessionSummaryCommand(
            cashSessionId: (string) $this->route('id'),
        );
    }
}
