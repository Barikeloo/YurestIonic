<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Export;

use App\Audit\Domain\Entity\AuditLog;

/**
 * RFC-4180 CSV writer. Excel-friendly: UTF-8 BOM up front so Excel
 * detects the encoding and renders accents correctly, comma delimiter,
 * CRLF line endings, and standard double-quote escaping.
 */
final class CsvAuditExportFormatter implements AuditExportFormatter
{
    private const HEADERS = [
        'uuid',
        'created_at',
        'archived_at',
        'category',
        'severity',
        'action',
        'entity_type',
        'entity_id',
        'summary',
        'reason',
        'user_id',
        'device_id',
        'ip_address',
        'session_id',
        'anomaly_kind',
        'integrity_hash',
        'prev_hash',
        'metadata',
        'before',
        'after',
    ];

    public function header(): string
    {
        return "\xEF\xBB\xBF".$this->csvLine(self::HEADERS);
    }

    public function formatRow(AuditLog $log): string
    {
        return $this->csvLine([
            $log->uuid()->value(),
            $log->createdAt()->value()->format(\DateTimeInterface::ATOM),
            $log->archivedAt()?->format(\DateTimeInterface::ATOM) ?? '',
            $log->category()->value(),
            $log->severity()->value(),
            $log->action()->value(),
            $log->entityType(),
            $log->entityId(),
            $log->summary(),
            $log->reason() ?? '',
            $log->userId()?->value() ?? '',
            $log->deviceId() ?? '',
            $log->ipAddress() ?? '',
            $log->sessionId()?->value() ?? '',
            $log->anomalyKind() ?? '',
            $log->integrityHash(),
            $log->prevHash() ?? '',
            $this->jsonCell($log->metadata()),
            $this->jsonCell($log->before()),
            $this->jsonCell($log->after()),
        ]);
    }

    public function footer(): string
    {
        return '';
    }

    /**
     * @param  list<string>  $fields
     */
    private function csvLine(array $fields): string
    {
        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            return '';
        }

        fputcsv($fh, $fields, ',', '"', '\\', "\r\n");
        rewind($fh);
        $line = stream_get_contents($fh);
        fclose($fh);

        return $line === false ? '' : $line;
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    private function jsonCell(?array $value): string
    {
        if ($value === null || $value === []) {
            return '';
        }

        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return '';
        }
    }
}
