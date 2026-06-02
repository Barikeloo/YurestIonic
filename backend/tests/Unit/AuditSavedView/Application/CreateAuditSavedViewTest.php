<?php

namespace Tests\Unit\AuditSavedView\Application;

use App\AuditSavedView\Application\CreateAuditSavedView\CreateAuditSavedView;
use App\AuditSavedView\Application\CreateAuditSavedView\CreateAuditSavedViewCommand;
use App\AuditSavedView\Application\CreateAuditSavedView\CreateAuditSavedViewResponse;
use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CreateAuditSavedViewTest extends TestCase
{
    private AuditSavedViewRepositoryInterface&MockInterface $repository;
    private CreateAuditSavedView $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditSavedViewRepositoryInterface::class);
        $this->useCase = new CreateAuditSavedView($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_creates_view(): void
    {
        $restaurantId = Uuid::generate()->value();
        $userId = Uuid::generate()->value();

        $command = new CreateAuditSavedViewCommand(
            restaurantId: $restaurantId,
            userId: $userId,
            name: 'Vista de caja',
            icon: 'cash',
            filters: ['category' => 'caja'],
        );

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(AuditSavedView::class));

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(CreateAuditSavedViewResponse::class, $response);
        $this->assertSame('Vista de caja', $response->name);
        $this->assertSame('cash', $response->icon);
        $this->assertSame(['category' => 'caja'], $response->filters);
    }

    public function test_creates_view_without_icon(): void
    {
        $command = new CreateAuditSavedViewCommand(
            restaurantId: Uuid::generate()->value(),
            userId: Uuid::generate()->value(),
            name: 'Simple',
            icon: null,
            filters: [],
        );

        $this->repository
            ->shouldReceive('save')
            ->once();

        $response = ($this->useCase)($command);

        $this->assertNull($response->icon);
        $this->assertSame([], $response->filters);
    }
}
