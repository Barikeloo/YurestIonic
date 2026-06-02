<?php

namespace Tests\Unit\AuditSavedView\Application;

use App\AuditSavedView\Application\ListAuditSavedViews\ListAuditSavedViews;
use App\AuditSavedView\Application\ListAuditSavedViews\ListAuditSavedViewsCommand;
use App\AuditSavedView\Application\ListAuditSavedViews\ListAuditSavedViewsResponse;
use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ListAuditSavedViewsTest extends TestCase
{
    private AuditSavedViewRepositoryInterface&MockInterface $repository;
    private ListAuditSavedViews $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditSavedViewRepositoryInterface::class);
        $this->useCase = new ListAuditSavedViews($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_views(): void
    {
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();

        $view1 = AuditSavedView::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: $restaurantId,
            userId: $userId,
            name: 'Vista 1',
            icon: 'cash',
            filters: ['category' => 'caja'],
        );
        $view2 = AuditSavedView::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: $restaurantId,
            userId: $userId,
            name: 'Vista 2',
            icon: 'orders',
            filters: ['category' => 'order'],
        );

        $command = new ListAuditSavedViewsCommand(
            restaurantId: $restaurantId->value(),
            userId: $userId->value(),
        );

        $this->repository
            ->shouldReceive('listByRestaurantAndUser')
            ->once()
            ->andReturn([$view1, $view2]);

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(ListAuditSavedViewsResponse::class, $response);
        $this->assertCount(2, $response->items);
        $this->assertSame('Vista 1', $response->items[0]->name);
        $this->assertSame('Vista 2', $response->items[1]->name);
    }

    public function test_returns_empty_list(): void
    {
        $command = new ListAuditSavedViewsCommand(
            restaurantId: Uuid::generate()->value(),
            userId: Uuid::generate()->value(),
        );

        $this->repository
            ->shouldReceive('listByRestaurantAndUser')
            ->once()
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertCount(0, $response->items);
    }
}
