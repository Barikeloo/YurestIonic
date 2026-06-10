<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http\Requests;

use App\Reporting\Application\Shared\DateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetTaxReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period'  => ['required', Rule::in(['today', 'yesterday', 'week', 'month'])],
            'quarter' => ['sometimes', Rule::in(['T1', 'T2', 'T3', 'T4'])],
        ];
    }

    public function validatedQuarter(): string
    {
        return $this->validated()['quarter'] ?? DateRange::currentQuarter();
    }
}
