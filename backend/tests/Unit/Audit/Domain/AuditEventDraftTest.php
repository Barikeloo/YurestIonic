<?php

namespace Tests\Unit\Audit\Domain;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class AuditEventDraftTest extends TestCase
{
    public function test_create_with_required_fields(): void
    {
        $restaurantId = Uuid::generate();
        $slug = ActionSlug::create('caja.opened');

        $draft = new AuditEventDraft(
            restaurantId: $restaurantId,
            slug: $slug,
            entityType: 'cash_session',
            entityId: 'session-1',
        );

        $this->assertSame($restaurantId->value(), $draft->restaurantId->value());
        $this->assertSame('caja.opened', $draft->slug->value());
        $this->assertSame('cash_session', $draft->entityType);
        $this->assertSame('session-1', $draft->entityId);
        $this->assertNull($draft->userId);
        $this->assertNull($draft->ipAddress);
        $this->assertNull($draft->deviceId);
        $this->assertNull($draft->sessionId);
        $this->assertNull($draft->reason);
        $this->assertNull($draft->before);
        $this->assertNull($draft->after);
        $this->assertSame([], $draft->metadata);
    }

    public function test_create_with_all_fields(): void
    {
        $userId = Uuid::generate();
        $sessionId = Uuid::generate();

        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('order.created'),
            entityType: 'order',
            entityId: 'order-1',
            userId: $userId,
            ipAddress: '192.168.1.1',
            deviceId: 'device-abc',
            sessionId: $sessionId,
            reason: 'Test reason',
            before: ['status' => 'draft'],
            after: ['status' => 'confirmed'],
            metadata: ['key' => 'val'],
        );

        $this->assertSame($userId->value(), $draft->userId->value());
        $this->assertSame('192.168.1.1', $draft->ipAddress);
        $this->assertSame('device-abc', $draft->deviceId);
        $this->assertSame($sessionId->value(), $draft->sessionId->value());
        $this->assertSame('Test reason', $draft->reason);
        $this->assertSame(['status' => 'draft'], $draft->before);
        $this->assertSame(['status' => 'confirmed'], $draft->after);
        $this->assertSame(['key' => 'val'], $draft->metadata);
    }

    public function test_to_catalog_context_returns_structured_array(): void
    {
        $userId = Uuid::generate();
        $sessionId = Uuid::generate();

        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('caja.closed'),
            entityType: 'cash_session',
            entityId: 'session-1',
            userId: $userId,
            ipAddress: '10.0.0.1',
            deviceId: 'device-xyz',
            sessionId: $sessionId,
            reason: 'Cierre normal',
            before: ['amount' => 500],
            after: ['amount' => 600],
            metadata: ['delta' => 100],
        );

        $context = $draft->toCatalogContext();

        $this->assertSame('session-1', $context['entity_id']);
        $this->assertSame('cash_session', $context['entity_type']);
        $this->assertSame('device-xyz', $context['device_id']);
        $this->assertSame('10.0.0.1', $context['ip_address']);
        $this->assertSame($sessionId->value(), $context['session_id']);
        $this->assertSame($userId->value(), $context['user_id']);
        $this->assertSame('Cierre normal', $context['reason']);
        $this->assertSame(['amount' => 500], $context['before']);
        $this->assertSame(['amount' => 600], $context['after']);
        $this->assertSame(['delta' => 100], $context['metadata']);
    }

    public function test_to_catalog_context_uses_placeholder_for_null_values(): void
    {
        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('test.event'),
            entityType: 'test',
            entityId: '1',
        );

        $context = $draft->toCatalogContext();

        $this->assertSame('—', $context['device_id']);
        $this->assertSame('—', $context['ip_address']);
        $this->assertSame('—', $context['session_id']);
        $this->assertSame('—', $context['user_id']);
        $this->assertSame('—', $context['reason']);
    }
}
