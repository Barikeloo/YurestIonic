<?php

namespace Tests\Unit\Tables\Application;

use App\Shared\Application\Event\EventBusInterface;
use App\Tables\Application\UpdateTable\UpdateTable;
use App\Tables\Application\UpdateTable\UpdateTableCommand;
use App\Tables\Application\UpdateTable\UpdateTableResponse;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Event\TableUpdated;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateTableTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const TABLE_ID = '00000000-0000-4000-8000-000000000000';
    private const ZONE_ID = '00000000-0000-4000-8000-0000000000aa';

    private function existingTable(): Table
    {
        return Table::fromPersistence(self::TABLE_ID, self::ZONE_ID, 'Mesa 1', null, new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function test_updates_table_and_publishes_event(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);
        $table = $this->existingTable();

        $repository->shouldReceive('findById')->once()->with(self::TABLE_ID)->andReturn($table);
        $repository->shouldReceive('findByZoneIdAndName')->once()->andReturn(null);
        $repository->shouldReceive('save')->once();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(TableUpdated::class));

        $useCase = new UpdateTable($repository, $eventBus);

        $response = $useCase(new UpdateTableCommand(id: self::TABLE_ID, zoneId: self::ZONE_ID, name: 'Mesa 2'));

        $this->assertInstanceOf(UpdateTableResponse::class, $response);
        $this->assertSame('Mesa 2', $response->name);
    }

    public function test_throws_when_not_found(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('missing')->andReturn(null);
        $repository->shouldNotReceive('save');
        $eventBus->shouldNotReceive('publish');

        $useCase = new UpdateTable($repository, $eventBus);

        $this->expectException(TableNotFoundException::class);

        $useCase(new UpdateTableCommand(id: 'missing', zoneId: self::ZONE_ID, name: 'Mesa 2'));
    }
}
