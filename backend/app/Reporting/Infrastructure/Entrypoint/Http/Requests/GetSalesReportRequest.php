<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GetSalesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period'   => ['required', 'string', Rule::in(['today', 'yesterday', 'week', 'month'])],
            'page'     => ['sometimes', 'int', 'min:1'],
            'per_page' => ['sometimes', 'int', 'min:1', 'max:200'],
        ];
    }
}
