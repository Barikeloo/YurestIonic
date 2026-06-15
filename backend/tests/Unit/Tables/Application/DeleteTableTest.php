<?php

namespace Tests\Unit\Tables\Application;

use App\Shared\Application\Event\EventBusInterface;
use App\Tables\Application\DeleteTable\DeleteTable;
use App\Tables\Application\DeleteTable\DeleteTableCommand;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Event\TableDeleted;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteTableTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const TABLE_ID = '00000000-0000-4000-8000-000000000000';
    private const ZONE_ID = '00000000-0000-4000-8000-0000000000aa';

    public function test_deletes_table_and_publishes_event(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $table = Table::fromPersistence(self::TABLE_ID, self::ZONE_ID, 'Mesa 1', null, new \DateTimeImmutable(), new \DateTimeImmutable());

        $repository->shouldReceive('findById')->once()->with(self::TABLE_ID)->andReturn($table);
        $repository->shouldReceive('deleteById')->once()->with(self::TABLE_ID)->andReturnTrue();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(TableDeleted::class));

        $useCase = new DeleteTable($repository, $eventBus);

        $useCase(new DeleteTableCommand(id: self::TABLE_ID));
    }

    public function test_throws_when_not_found(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('missing')->andReturn(null);
        $repository->shouldNotReceive('deleteById');
        $eventBus->shouldNotReceive('publish');

        $useCase = new DeleteTable($repository, $eventBus);

        $this->expectException(TableNotFoundException::class);

        $useCase(new DeleteTableCommand(id: 'missing'));
    }
}
