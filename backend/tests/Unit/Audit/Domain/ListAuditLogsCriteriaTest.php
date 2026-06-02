<?php

namespace Tests\Unit\Audit\Domain;

use App\Audit\Domain\ListAuditLogsCriteria;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class ListAuditLogsCriteriaTest extends TestCase
{
    public function test_create_with_required_fields(): void
    {
        $restaurantId = Uuid::generate();
        $criteria = new ListAuditLogsCriteria(restaurantId: $restaurantId);

        $this->assertSame($restaurantId->value(), $criteria->restaurantId->value());
        $this->assertNull($criteria->category);
        $this->assertNull($criteria->severity);
        $this->assertNull($criteria->userId);
        $this->assertNull($criteria->deviceId);
        $this->assertNull($criteria->dateFrom);
        $this->assertNull($criteria->dateTo);
        $this->assertNull($criteria->search);
        $this->assertFalse($criteria->anomalyOnly);
        $this->assertNull($criteria->cursorCreatedAt);
        $this->assertNull($criteria->cursorInternalId);
        $this->assertNull($criteria->sinceUuid);
        $this->assertSame(50, $criteria->limit);
    }

    public function test_create_with_all_fields(): void
    {
        $now = new \DateTimeImmutable;
        $userId = Uuid::generate();
        $sinceUuid = Uuid::generate();

        $criteria = new ListAuditLogsCriteria(
            restaurantId: Uuid::generate(),
            category: 'caja',
            severity: 'warning',
            userId: $userId,
            deviceId: 'device-1',
            dateFrom: $now,
            dateTo: $now,
            search: 'test',
            anomalyOnly: true,
            cursorCreatedAt: $now,
            cursorInternalId: 5,
            sinceUuid: $sinceUuid,
            limit: 25,
        );

        $this->assertSame('caja', $criteria->category);
        $this->assertSame('warning', $criteria->severity);
        $this->assertSame($userId->value(), $criteria->userId->value());
        $this->assertSame('device-1', $criteria->deviceId);
        $this->assertEquals($now, $criteria->dateFrom);
        $this->assertSame('test', $criteria->search);
        $this->assertTrue($criteria->anomalyOnly);
        $this->assertSame(5, $criteria->cursorInternalId);
        $this->assertSame($sinceUuid->value(), $criteria->sinceUuid->value());
        $this->assertSame(25, $criteria->limit);
    }
}
