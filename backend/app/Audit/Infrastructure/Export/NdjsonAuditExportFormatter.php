<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Export;

use App\Audit\Domain\Entity\AuditLog;

/**
 * Newline-delimited JSON — one full event per line. Suited for forensic
 * or ETL pipelines that want the structured payload (metadata, before,
 * after) without CSV escaping. No preamble, no footer.
 */
final class NdjsonAuditExportFormatter implements AuditExportFormatter
{
    public function header(): string
    {
        return '';
    }

    public function formatRow(AuditLog $log): string
    {
        $payload = [
            'uuid' => $log->uuid()->value(),
            'created_at' => $log->createdAt()->value()->format(\DateTimeInterface::ATOM),
            'archived_at' => $log->archivedAt()?->format(\DateTimeInterface::ATOM),
            'category' => $log->category()->value(),
            'severity' => $log->severity()->value(),
            'action' => $log->action()->value(),
            'entity_type' => $log->entityType(),
            'entity_id' => $log->entityId(),
            'summary' => $log->summary(),
            'reason' => $log->reason(),
            'user_id' => $log->userId()?->value(),
            'device_id' => $log->deviceId(),
            'ip_address' => $log->ipAddress(),
            'session_id' => $log->sessionId()?->value(),
            'anomaly_kind' => $log->anomalyKind(),
            'integrity_hash' => $log->integrityHash(),
            'prev_hash' => $log->prevHash(),
            'metadata' => $log->metadata(),
            'before' => $log->before(),
            'after' => $log->after(),
        ];

        try {
            return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        } catch (\Throwable) {
            return "\n";
        }
    }

    public function footer(): string
    {
        return '';
    }
}
