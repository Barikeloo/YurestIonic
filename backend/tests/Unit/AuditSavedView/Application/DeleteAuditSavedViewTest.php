<?php

namespace Tests\Unit\AuditSavedView\Application;

use App\AuditSavedView\Application\DeleteAuditSavedView\DeleteAuditSavedView;
use App\AuditSavedView\Application\DeleteAuditSavedView\DeleteAuditSavedViewCommand;
use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\AuditSavedView\Domain\Exception\AuditSavedViewNotFoundException;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class DeleteAuditSavedViewTest extends TestCase
{
    private AuditSavedViewRepositoryInterface&MockInterface $repository;
    private DeleteAuditSavedView $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditSavedViewRepositoryInterface::class);
        $this->useCase = new DeleteAuditSavedView($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_deletes_view(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $view = AuditSavedView::dddCreate(
            uuid: $uuid,
            restaurantId: $restaurantId,
            userId: Uuid::generate(),
            name: 'To delete',
            icon: null,
            filters: [],
        );

        $command = new DeleteAuditSavedViewCommand(
            restaurantId: $restaurantId->value(),
            userId: Uuid::generate()->value(),
            uuid: $uuid->value(),
        );

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($view);

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with(Mockery::on(fn (Uuid $r): bool => $r->value() === $restaurantId->value()), Mockery::on(fn (Uuid $u): bool => $u->value() === $uuid->value()));

        ($this->useCase)($command);

        $this->addToAssertionCount(1);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $uuid = Uuid::generate()->value();
        $command = new DeleteAuditSavedViewCommand(
            restaurantId: Uuid::generate()->value(),
            userId: Uuid::generate()->value(),
            uuid: $uuid,
        );

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->repository->shouldNotReceive('delete');

        $this->expectException(AuditSavedViewNotFoundException::class);

        ($this->useCase)($command);
    }
}
