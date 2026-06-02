<?php

namespace Tests\Unit\Audit\Domain\Entity;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Audit\Domain\ValueObject\Category;
use App\Audit\Domain\ValueObject\Severity;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class AuditLogEntityTest extends TestCase
{
    public function test_ddd_create_builds_audit_log(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();
        $now = DomainDateTime::now();

        $log = AuditLog::dddCreate(
            uuid: $uuid,
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: 'order-123',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Pedido order-123 creado.',
            integrityHash: 'hash123',
            prevHash: null,
            reason: null,
            sessionId: null,
            anomalyKind: null,
            metadata: ['key' => 'value'],
            userId: $userId,
            before: null,
            after: null,
            ipAddress: '127.0.0.1',
            deviceId: 'device-abc',
            createdAt: $now,
        );

        $this->assertSame($uuid->value(), $log->uuid()->value());
        $this->assertSame($restaurantId->value(), $log->restaurantId()->value());
        $this->assertSame('order', $log->entityType());
        $this->assertSame('order-123', $log->entityId());
        $this->assertSame('order.created', $log->action()->value());
        $this->assertSame('order', $log->category()->value());
        $this->assertSame('info', $log->severity()->value());
        $this->assertSame('Pedido order-123 creado.', $log->summary());
        $this->assertNull($log->reason());
        $this->assertNull($log->sessionId());
        $this->assertNull($log->anomalyKind());
        $this->assertSame('hash123', $log->integrityHash());
        $this->assertNull($log->prevHash());
        $this->assertSame(['key' => 'value'], $log->metadata());
        $this->assertSame($userId->value(), $log->userId()->value());
        $this->assertNull($log->before());
        $this->assertNull($log->after());
        $this->assertSame('127.0.0.1', $log->ipAddress());
        $this->assertSame('device-abc', $log->deviceId());
        $this->assertEquals($now->value()->format('U'), $log->createdAt()->value()->format('U'));
    }

    public function test_ddd_create_without_created_at_defaults_to_now(): void
    {
        $log = AuditLog::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: Uuid::generate(),
            entityType: 'order',
            entityId: '1',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Test',
            integrityHash: 'hash',
            prevHash: null,
        );

        $this->assertInstanceOf(DomainDateTime::class, $log->createdAt());
    }

    public function test_ddd_create_with_all_optional_fields(): void
    {
        $sessionId = Uuid::generate();
        $userId = Uuid::generate();

        $log = AuditLog::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: Uuid::generate(),
            entityType: 'sale',
            entityId: 'sale-456',
            action: ActionSlug::create('sale.closed'),
            category: Category::create('sale'),
            severity: Severity::create('success'),
            summary: 'Venta cerrada',
            integrityHash: 'abc',
            prevHash: 'prev123',
            reason: 'Pago completo',
            sessionId: $sessionId,
            anomalyKind: 'caja_mismatch',
            metadata: ['delta' => 100],
            userId: $userId,
            before: ['status' => 'open'],
            after: ['status' => 'closed'],
            ipAddress: '192.168.1.1',
            deviceId: 'device-xyz',
        );

        $this->assertSame('Pago completo', $log->reason());
        $this->assertSame($sessionId->value(), $log->sessionId()->value());
        $this->assertSame('caja_mismatch', $log->anomalyKind());
        $this->assertSame('prev123', $log->prevHash());
        $this->assertTrue($log->hasAnomaly());
        $this->assertSame(['status' => 'open'], $log->before());
        $this->assertSame(['status' => 'closed'], $log->after());
    }

    public function test_has_anomaly_returns_false_when_no_anomaly(): void
    {
        $log = AuditLog::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: Uuid::generate(),
            entityType: 'order',
            entityId: '1',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Test',
            integrityHash: 'hash',
            prevHash: null,
        );

        $this->assertFalse($log->hasAnomaly());
    }

    public function test_from_persistence_rebuilds_audit_log(): void
    {
        $uuid = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();
        $userId = Uuid::generate()->value();
        $now = new \DateTimeImmutable;

        $log = AuditLog::fromPersistence(
            uuid: $uuid,
            restaurantId: $restaurantId,
            entityType: 'product',
            entityId: 'prod-789',
            action: 'product.created',
            category: 'catalog',
            severity: 'info',
            summary: 'Producto creado',
            reason: null,
            sessionId: null,
            anomalyKind: null,
            integrityHash: 'hash_val',
            prevHash: null,
            metadata: ['price' => 1000],
            userId: $userId,
            before: null,
            after: null,
            ipAddress: null,
            deviceId: null,
            createdAt: $now,
        );

        $this->assertSame($uuid, $log->uuid()->value());
        $this->assertSame($restaurantId, $log->restaurantId()->value());
        $this->assertSame('product', $log->entityType());
        $this->assertSame('product.created', $log->action()->value());
        $this->assertSame('catalog', $log->category()->value());
        $this->assertSame('info', $log->severity()->value());
        $this->assertSame('Producto creado', $log->summary());
        $this->assertNull($log->reason());
        $this->assertNull($log->sessionId());
        $this->assertNull($log->anomalyKind());
        $this->assertSame('hash_val', $log->integrityHash());
        $this->assertNull($log->prevHash());
        $this->assertSame(['price' => 1000], $log->metadata());
        $this->assertSame($userId, $log->userId()->value());
        $this->assertNull($log->ipAddress());
        $this->assertNull($log->deviceId());
        $this->assertEquals($now, $log->createdAt()->value());
    }
}
