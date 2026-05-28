<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Services;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AlertNotifierInterface;
use Illuminate\Support\Facades\Http;

final class SlackAlertNotifier implements AlertNotifierInterface
{
    public function __construct(
        private readonly string $webhookUrl,
    ) {}

    public function notifyCriticalAnomaly(AuditEventDraft $draft, string $anomalyKind): void
    {
        if ($this->webhookUrl === '') {
            return;
        }

        $emoji = match ($anomalyKind) {
            'auth_failed_burst' => ':warning:',
            'caja_mismatch' => ':money_with_wings:',
            default => ':rotating_light:',
        };

        $text = sprintf(
            "%s *Anomalía detectada: `%s`*\n• Restaurante: `%s`\n• Usuario: `%s`\n• Dispositivo: `%s`\n• Entidad: `%s` / `%s`\n• Resumen: %s\n• Metadata: `%s`",
            $emoji,
            $anomalyKind,
            $draft->restaurantId->value(),
            $draft->userId?->value() ?? '—',
            $draft->deviceId ?? '—',
            $draft->entityType,
            $draft->entityId,
            $draft->metadata['summary'] ?? '—',
            json_encode($draft->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        try {
            Http::timeout(5)->post($this->webhookUrl, ['text' => $text]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
