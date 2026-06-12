<?php

namespace Tests\Unit\Family\Application;

use App\Family\Application\UpdateFamily\UpdateFamily;
use App\Family\Application\UpdateFamily\UpdateFamilyCommand;
use App\Family\Application\UpdateFamily\UpdateFamilyResponse;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Event\FamilyUpdated;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateFamilyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const FAMILY_ID = '00000000-0000-4000-8000-000000000000';

    private function existingFamily(): Family
    {
        return Family::fromPersistence(self::FAMILY_ID, 'Original', true, new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function test_updates_family_name_and_publishes_event(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);
        $family = $this->existingFamily();

        $repository->shouldReceive('findById')->once()->with(self::FAMILY_ID)->andReturn($family);
        $repository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Family $f): bool => $f->name()->value() === 'Updated'
        ));
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(FamilyUpdated::class));

        $useCase = new UpdateFamily($repository, $eventBus);

        $response = $useCase(new UpdateFamilyCommand(id: self::FAMILY_ID, name: 'Updated'));

        $this->assertInstanceOf(UpdateFamilyResponse::class, $response);
        $this->assertSame('Updated', $response->name);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('save');
        $eventBus->shouldNotReceive('publish');

        $useCase = new UpdateFamily($repository, $eventBus);

        $this->expectException(FamilyNotFoundException::class);

        $useCase(new UpdateFamilyCommand(id: 'non-existent-id', name: 'Updated'));
    }
}
