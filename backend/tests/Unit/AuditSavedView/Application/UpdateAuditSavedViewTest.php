<?php

namespace Tests\Unit\AuditSavedView\Application;

use App\AuditSavedView\Application\UpdateAuditSavedView\UpdateAuditSavedView;
use App\AuditSavedView\Application\UpdateAuditSavedView\UpdateAuditSavedViewCommand;
use App\AuditSavedView\Application\UpdateAuditSavedView\UpdateAuditSavedViewResponse;
use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\AuditSavedView\Domain\Exception\AuditSavedViewNotFoundException;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class UpdateAuditSavedViewTest extends TestCase
{
    private AuditSavedViewRepositoryInterface&MockInterface $repository;
    private UpdateAuditSavedView $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditSavedViewRepositoryInterface::class);
        $this->useCase = new UpdateAuditSavedView($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_updates_name(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();

        $view = AuditSavedView::dddCreate(
            uuid: $uuid,
            restaurantId: $restaurantId,
            userId: $userId,
            name: 'Original',
            icon: 'cash',
            filters: ['category' => 'caja'],
        );

        $command = new UpdateAuditSavedViewCommand(
            restaurantId: $restaurantId->value(),
            userId: Uuid::generate()->value(),
            uuid: $uuid->value(),
            name: 'Renombrado',
            icon: null,
            filters: null,
        );

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($view);

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (AuditSavedView $v): bool => $v->name() === 'Renombrado' && $v->icon() === 'cash'));

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(UpdateAuditSavedViewResponse::class, $response);
        $this->assertSame('Renombrado', $response->name);
    }

    public function test_updates_filters(): void
    {
        $uuid = Uuid::generate();
        $view = AuditSavedView::dddCreate(
            uuid: $uuid,
            restaurantId: Uuid::generate(),
            userId: Uuid::generate(),
            name: 'View',
            icon: null,
            filters: ['old' => 'filter'],
        );

        $command = new UpdateAuditSavedViewCommand(
            restaurantId: Uuid::generate()->value(),
            userId: Uuid::generate()->value(),
            uuid: $uuid->value(),
            name: null,
            icon: null,
            filters: ['new' => 'filter'],
        );

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($view);

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (AuditSavedView $v): bool => $v->filters() === ['new' => 'filter']));

        $response = ($this->useCase)($command);

        $this->assertSame(['new' => 'filter'], $response->filters);
    }

    public function test_updates_icon(): void
    {
        $uuid = Uuid::generate();
        $view = AuditSavedView::dddCreate(
            uuid: $uuid,
            restaurantId: Uuid::generate(),
            userId: Uuid::generate(),
            name: 'View',
            icon: 'old-icon',
            filters: [],
        );

        $command = new UpdateAuditSavedViewCommand(
            restaurantId: Uuid::generate()->value(),
            userId: Uuid::generate()->value(),
            uuid: $uuid->value(),
            name: null,
            icon: 'new-icon',
            filters: null,
        );

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($view);

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (AuditSavedView $v): bool => $v->icon() === 'new-icon' && $v->name() === 'View'));

        $response = ($this->useCase)($command);

        $this->assertSame('new-icon', $response->icon);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $command = new UpdateAuditSavedViewCommand(
            restaurantId: Uuid::generate()->value(),
            userId: Uuid::generate()->value(),
            uuid: Uuid::generate()->value(),
            name: 'Test',
            icon: null,
            filters: null,
        );

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->repository->shouldNotReceive('save');

        $this->expectException(AuditSavedViewNotFoundException::class);

        ($this->useCase)($command);
    }
}
