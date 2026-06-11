<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http\Requests;

use App\Reporting\Application\CreateScheduledReport\CreateScheduledReportCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateScheduledReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', 'max:32', Rule::in(['daily', 'products', 'families', 'cash', 'tips', 'taxes'])],
            'format'      => ['required', 'string', 'max:8', Rule::in(['PDF', 'CSV'])],
            'frequency'   => ['required', 'string', 'max:16', Rule::in(['daily', 'weekly', 'monthly', 'quarterly'])],
            'time'        => ['required', 'string', 'max:5', 'regex:/^([01]\d|2[0-3]):([0-5]\d)$/'],
            'weekday'     => ['nullable', 'integer', 'min:1', 'max:7'],
            'day_of_month'=> ['nullable', 'integer', 'min:1', 'max:28'],
            'recipients'  => ['required', 'array', 'min:1'],
            'recipients.*'=> ['required', 'email'],
            'name'        => ['required', 'string', 'max:255'],
            'active'      => ['boolean'],
        ];
    }

    public function toCommand(): CreateScheduledReportCommand
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        return new CreateScheduledReportCommand(
            restaurantId:       (int) $restaurantId,
            reportType:         (string) $this->input('report_type'),
            format:             (string) $this->input('format'),
            frequency:          (string) $this->input('frequency'),
            time:               (string) $this->input('time'),
            weekday:            $this->input('weekday') !== null ? (int) $this->input('weekday') : null,
            dayOfMonth:         $this->input('day_of_month') !== null ? (int) $this->input('day_of_month') : null,
            recipients:         (array) $this->input('recipients'),
            name:               (string) $this->input('name'),
            active:             (bool) $this->input('active', true),
            createdByUserUuid:  $this->session()->get('auth_user_id'),
        );
    }
}
