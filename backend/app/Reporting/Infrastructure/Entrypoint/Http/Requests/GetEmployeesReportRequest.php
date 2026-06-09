<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetEmployeesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', Rule::in(['today', 'yesterday', 'week', 'month'])],
        ];
    }
}
