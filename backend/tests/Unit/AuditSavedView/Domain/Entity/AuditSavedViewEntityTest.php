<?php

namespace Tests\Unit\AuditSavedView\Domain\Entity;

use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class AuditSavedViewEntityTest extends TestCase
{
    public function test_ddd_create_builds_view(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();

        $view = AuditSavedView::dddCreate(
            uuid: $uuid,
            restaurantId: $restaurantId,
            userId: $userId,
            name: 'Mis eventos de caja',
            icon: 'cash',
            filters: ['category' => 'caja', 'severity' => 'warning'],
        );

        $this->assertSame($uuid->value(), $view->uuid()->value());
        $this->assertSame($restaurantId->value(), $view->restaurantId()->value());
        $this->assertSame($userId->value(), $view->userId()->value());
        $this->assertSame('Mis eventos de caja', $view->name());
        $this->assertSame('cash', $view->icon());
        $this->assertSame(['category' => 'caja', 'severity' => 'warning'], $view->filters());
        $this->assertInstanceOf(DomainDateTime::class, $view->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $view->updatedAt());
    }

    public function test_ddd_create_without_icon(): void
    {
        $view = AuditSavedView::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: Uuid::generate(),
            userId: Uuid::generate(),
            name: 'Simple',
            icon: null,
            filters: [],
        );

        $this->assertNull($view->icon());
    }

    public function test_ddd_create_with_custom_timestamps(): void
    {
        $createdAt = DomainDateTime::now();
        $updatedAt = DomainDateTime::now();

        $view = AuditSavedView::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: Uuid::generate(),
            userId: Uuid::generate(),
            name: 'Test',
            icon: null,
            filters: [],
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $this->assertEquals($createdAt->value()->format('U'), $view->createdAt()->value()->format('U'));
        $this->assertEquals($updatedAt->value()->format('U'), $view->updatedAt()->value()->format('U'));
    }

    public function test_from_persistence_rebuilds_view(): void
    {
        $uuid = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();
        $userId = Uuid::generate()->value();
        $now = new \DateTimeImmutable;

        $view = AuditSavedView::fromPersistence(
            uuid: $uuid,
            restaurantId: $restaurantId,
            userId: $userId,
            name: 'Vista guardada',
            icon: 'filter',
            filters: ['search' => 'test'],
            createdAt: $now,
            updatedAt: $now,
        );

        $this->assertSame($uuid, $view->uuid()->value());
        $this->assertSame($restaurantId, $view->restaurantId()->value());
        $this->assertSame($userId, $view->userId()->value());
        $this->assertSame('Vista guardada', $view->name());
        $this->assertSame('filter', $view->icon());
        $this->assertSame(['search' => 'test'], $view->filters());
        $this->assertEquals($now, $view->createdAt()->value());
        $this->assertEquals($now, $view->updatedAt()->value());
    }

    public function test_with_updated_filters_returns_new_instance(): void
    {
        $view = AuditSavedView::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: Uuid::generate(),
            userId: Uuid::generate(),
            name: 'Original',
            icon: null,
            filters: ['old' => 'filter'],
        );

        $now = DomainDateTime::now();
        $updated = $view->withUpdatedFilters(['new' => 'filter'], $now);

        $this->assertSame('Original', $updated->name());
        $this->assertSame(['new' => 'filter'], $updated->filters());
        $this->assertSame($view->uuid()->value(), $updated->uuid()->value());
        $this->assertEquals($now->value()->format('U'), $updated->updatedAt()->value()->format('U'));
    }

    public function test_with_updated_name_returns_new_instance(): void
    {
        $view = AuditSavedView::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: Uuid::generate(),
            userId: Uuid::generate(),
            name: 'Original',
            icon: null,
            filters: [],
        );

        $now = DomainDateTime::now();
        $updated = $view->withUpdatedName('Renombrado', $now);

        $this->assertSame('Renombrado', $updated->name());
        $this->assertSame($view->icon(), $updated->icon());
        $this->assertSame($view->uuid()->value(), $updated->uuid()->value());
    }
}
