<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

enum ExportFormat: string
{
    case Csv = 'csv';
    case Ndjson = 'ndjson';

    public function contentType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv; charset=utf-8',
            self::Ndjson => 'application/x-ndjson; charset=utf-8',
        };
    }

    public function fileExtension(): string
    {
        return $this->value;
    }
}
