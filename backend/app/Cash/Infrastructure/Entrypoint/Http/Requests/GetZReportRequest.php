<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\GetZReport\GetZReportCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetZReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetZReportCommand
    {
        return new GetZReportCommand(
            zReportId: (string) $this->route('id'),
        );
    }
}
