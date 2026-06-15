<?php

namespace Tests\Unit\Tables\Application;

use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Application\CreateTable\CreateTable;
use App\Tables\Application\CreateTable\CreateTableCommand;
use App\Tables\Application\CreateTable\CreateTableResponse;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Event\TableCreated;
use App\Tables\Domain\Exception\TableNameAlreadyExistsInZoneException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateTableTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ZONE_ID = '00000000-0000-4000-8000-0000000000aa';

    public function test_creates_table_and_publishes_event(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findByZoneIdAndName')->once()->andReturn(null);
        $repository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Table $t): bool => $t->name()->value() === 'Mesa 1'
        ));
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(TableCreated::class));

        $useCase = new CreateTable($repository, $eventBus);

        $response = $useCase(new CreateTableCommand(zoneId: self::ZONE_ID, name: 'Mesa 1'));

        $this->assertInstanceOf(CreateTableResponse::class, $response);
        $this->assertSame('Mesa 1', $response->name);
    }

    public function test_throws_when_name_exists_in_zone(): void
    {
        $repository = Mockery::mock(TableRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $existing = Table::fromPersistence('00000000-0000-4000-8000-000000000001', self::ZONE_ID, 'Mesa 1', null, new \DateTimeImmutable(), new \DateTimeImmutable());
        $repository->shouldReceive('findByZoneIdAndName')->once()->andReturn($existing);
        $repository->shouldNotReceive('save');
        $eventBus->shouldNotReceive('publish');

        $useCase = new CreateTable($repository, $eventBus);

        $this->expectException(TableNameAlreadyExistsInZoneException::class);

        $useCase(new CreateTableCommand(zoneId: self::ZONE_ID, name: 'Mesa 1'));
    }
}
