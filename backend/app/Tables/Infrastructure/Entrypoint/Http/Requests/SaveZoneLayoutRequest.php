<?php

declare(strict_types=1);

namespace App\Tables\Infrastructure\Entrypoint\Http\Requests;

use App\Tables\Application\SaveZoneLayout\SaveZoneLayoutCommand;
use App\Tables\Application\SaveZoneLayout\SaveZoneLayoutTableDto;
use Illuminate\Foundation\Http\FormRequest;

final class SaveZoneLayoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tables'          => ['present', 'array'],
            'tables.*.uuid'   => ['required', 'uuid'],
            'tables.*.pos_x'  => ['required', 'integer', 'min:0', 'max:1200'],
            'tables.*.pos_y'  => ['required', 'integer', 'min:0', 'max:800'],
            'tables.*.width'  => ['required', 'integer', 'min:20', 'max:600'],
            'tables.*.height' => ['required', 'integer', 'min:20', 'max:600'],
            'tables.*.shape'  => ['required', 'string', 'in:rect,circle'],
        ];
    }

    public function toCommand(string $zoneId): SaveZoneLayoutCommand
    {
        $dtos = array_map(
            static fn (array $row): SaveZoneLayoutTableDto => new SaveZoneLayoutTableDto(
                uuid:   $row['uuid'],
                posX:   (int) $row['pos_x'],
                posY:   (int) $row['pos_y'],
                width:  (int) $row['width'],
                height: (int) $row['height'],
                shape:  $row['shape'],
            ),
            $this->input('tables', []),
        );

        return new SaveZoneLayoutCommand(zoneId: $zoneId, tables: $dtos);
    }
}
