<?php

namespace Tests\Unit\Family\Application;

use App\Family\Application\DeleteFamily\DeleteFamily;
use App\Family\Application\DeleteFamily\DeleteFamilyCommand;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Event\FamilyDeleted;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteFamilyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const FAMILY_ID = '00000000-0000-4000-8000-000000000000';

    public function test_deletes_family_and_publishes_event(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $family = Family::fromPersistence(self::FAMILY_ID, 'To delete', null, null, true, new \DateTimeImmutable(), new \DateTimeImmutable());

        $repository->shouldReceive('findById')->once()->with(self::FAMILY_ID)->andReturn($family);
        $repository->shouldReceive('deleteById')->once()->with(self::FAMILY_ID)->andReturnTrue();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(FamilyDeleted::class));

        $useCase = new DeleteFamily($repository, $eventBus);

        $useCase(new DeleteFamilyCommand(id: self::FAMILY_ID));
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('deleteById');
        $eventBus->shouldNotReceive('publish');

        $useCase = new DeleteFamily($repository, $eventBus);

        $this->expectException(FamilyNotFoundException::class);

        $useCase(new DeleteFamilyCommand(id: 'non-existent-id'));
    }
}
