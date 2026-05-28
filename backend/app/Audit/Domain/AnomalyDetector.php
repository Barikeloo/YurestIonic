<?php

declare(strict_types=1);

namespace App\Audit\Domain;

use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;

final class AnomalyDetector
{
    private const AUTH_FAILED_BURST_THRESHOLD = 3;

    private const AUTH_FAILED_BURST_WINDOW_SECONDS = 300;

    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
    ) {}

    /**
     * Evaluates anomaly rules against the draft. Returns the anomaly_kind to stamp on
     * the row, or null if no rule matched.
     */
    public function detect(AuditEventDraft $draft): ?string
    {
        $slug = $draft->slug->value();

        if ($slug === 'auth.login_pin_failed' && $draft->userId !== null) {
            $priorFailures = $this->repository->countRecentByActionAndUser(
                restaurantId: $draft->restaurantId,
                slug: $draft->slug,
                userId: $draft->userId,
                withinSeconds: self::AUTH_FAILED_BURST_WINDOW_SECONDS,
            );

            if ($priorFailures + 1 >= self::AUTH_FAILED_BURST_THRESHOLD) {
                return 'auth_failed_burst';
            }
        }

        if ($slug === 'caja.closed' || $slug === 'caja.force_closed') {
            $delta = $draft->metadata['delta_final_cents'] ?? null;
            if (is_int($delta) && $delta !== 0) {
                return 'caja_mismatch';
            }
        }

        return null;
    }
}
